// Cancellation flow: from cancel click to save offer.
// Story: set the flow up in admin -> a customer cancels and accepts a discount -> another pauses
// -> admin sees the history on the subscription and in the Retention report.
// Store notes and the change log: qa/feature-videos/notes/cancellation-flow.md
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { wp, wpJson, W, H } from '../lib/director.mjs';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.join(process.env.FV_OUT || path.join(HERE, '..', 'out'), 'cancellation-flow');
const STATE = path.join(OUT, 'state.json');
const MAILPIT = 'http://localhost:10010';
const CF_URL = '/wp-admin/admin.php?page=arraysubs-mainadmin#/cancellation-flow';
const REPORT_URL = '/wp-admin/admin.php?page=wc-admin&path=/analytics/arraysubs-retention';
const VIEW_PATTERN = '**/my-account/as-view-subscription/**';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Customers from this scene's block (617-677). A = accepts the discount, B = accepts the pause.
// The first one that is still Active and has never accepted an offer is used.
const CANDIDATES = {
  A: [27748, 27762, 27813, 27676, 27842], // Premium Membership 69$/month first (two cheaper plans)
  B: [27776, 27880, 27996, 28034], // Plus Membership 39$/month
};

const S = { A: null, B: null, mailHtml: '' };

function subInfo(ids) {
  return wpJson(`$out=[]; foreach ([${ids.map(Number).join(',')}] as $id) { $c=(int)get_post_meta($id,'_customer_id',true); $u=get_userdata($c); $out[]=['sub'=>$id,'user'=>$c,'email'=>$u?$u->user_email:'','first'=>$u?$u->first_name:'','status'=>get_post_status($id),'accepted'=>(bool)get_post_meta($id,'_retention_offer_accepted',true),'recurring'=>(float)get_post_meta($id,'_recurring_amount',true),'product'=>trim(preg_replace('/^Demo\\s*\\x{00B7}\\s*/u','',html_entity_decode(get_the_title((int)get_post_meta($id,'_product_id',true)))))]; } echo json_encode($out);`);
}

function assertPro() {
  // wp() runs with FV_PRO=1, the same Pro-on view the recording browser gets.
  const ok = wp(['eval', "echo (class_exists('\\\\ArraySubsPro\\\\Features\\\\CancellationFlow\\\\Services\\\\RetentionOfferProcessor') && function_exists('arraysubs_is_retention_offers_enabled') && arraysubs_is_retention_offers_enabled()) ? 'yes' : 'no';"]);
  if (!/yes$/.test(ok)) throw new Error('ArraySubs Pro retention offers are not available: refusing to record (Continue would cancel for real)');
}

async function asCustomer(v, c) {
  await v.as(c.user);
  // fv_cf: lets the temporary mu-plugin show the Cancel link for this session only.
  await v.context.addCookies([{ name: 'fv_cf', value: '1', domain: 'localhost', path: '/', expires: Math.floor(Date.now() / 1000) + 86400 }]);
}

const viewUrl = (c) => `/my-account/as-view-subscription/${c.sub}/`;

/** Union rect (iframe viewport px) of every element matched by Playwright selectors, optionally lifted to an ancestor. */
async function union(v, sels, closest = null) {
  let x1 = 1e9, y1 = 1e9, x2 = -1e9, y2 = -1e9;
  for (const s of sels) {
    const rects = await v.frame.locator(s).evaluateAll((els, closest) => els.map((e) => {
      if (closest && e.closest(closest)) e = e.closest(closest);
      const r = e.getBoundingClientRect();
      return { l: r.left, t: r.top, r: r.right, b: r.bottom, w: r.width, h: r.height };
    }), closest);
    for (const r of rects) {
      if (!r.w || !r.h) continue;
      x1 = Math.min(x1, r.l); y1 = Math.min(y1, r.t); x2 = Math.max(x2, r.r); y2 = Math.max(y2, r.b);
    }
  }
  if (x2 < x1) throw new Error(`union: nothing visible for ${sels.join(' | ')}`);
  return { x: x1, y: y1, w: x2 - x1, h: y2 - y1 };
}

/** Text of a table row's value cell on the My Account subscription page. */
async function rowValue(v, label) {
  return (await v.frame.locator(`tr:has-text("${label}") td`).first().innerText()).trim();
}

/** Builder: Continue to the next step and make sure its top is in view. */
async function nextStep(v) {
  await v.click('.boxed-fullscreen-modal__footer-next');
  await v.scrollTo('.boxed-fullscreen-modal__step-header:visible', { block: 'start' });
}

/**
 * Zoom on the "Before You Go..." window but keep it to the right and up, so the lower-left caption
 * sits on the dimmed page instead of the window's own buttons.
 */
async function offersView(v, s = 1.25) {
  const r = await v.appRect('#arraysubs-retention-modal .arraysubs-modal__container');
  const clamp = (n, a, b) => Math.max(a, Math.min(b, n));
  await v.moveCam({ s, x: clamp(W - 40 - (r.x + r.w) * s, W - W * s, 0), y: clamp(18 - r.y * s, H - H * s, 0) }, 0.9);
}

/** Off camera: open the cancel window, pick a reason and continue to the offers. */
async function openOffersFast(v, reason) {
  await v.frame.locator('.arraysubs-cancel-subscription').first().click();
  await v.settle();
  await v.frame.locator('#cancel-reason').selectOption(reason);
  await v.frame.locator('#arraysubs-cancel-modal .arraysubs-modal__confirm-btn').first().click();
  await v.settle();
  await v.frame.locator('#arraysubs-retention-modal .arraysubs-retention-offer').first().waitFor({ state: 'visible', timeout: 15000 });
}

/**
 * Click Accept Offer, show the success toast, then crossfade to the reloaded page.
 * The reload document is held back for a few seconds so the toast frame can be captured.
 */
async function acceptOffer(v, btn) {
  // Hold the reload document back until the toast has been shown (the page reloads 600 ms after
  // the toast in real time, and real time runs slower than video time while frames are captured).
  let release;
  const gate = new Promise((r) => { release = r; });
  const delayDoc = async (route) => {
    if (route.request().resourceType() === 'document') await Promise.race([gate, sleep(90000)]);
    await route.continue().catch(() => {});
  };
  await v.context.route(VIEW_PATTERN, delayDoc);
  try {
    const p = await v.moveTo(btn);
    await v.animate(0.1, (t) => { v.cursor.press = t; });
    v.ripple = { x: p.x, y: p.y, p: 0.02 };
    let nav = null;
    await v.transition(async () => {
      nav = v.frame.waitForNavigation({ timeout: 120000 }).catch(() => {});
      await v.page.mouse.click(p.x, p.y);
      // Accepting can take a while on a busy store; stepped time keeps it off the video.
      await v.frame.waitForFunction(() => /Offer accepted/.test(document.querySelector('.arraysubs-portal-toast-container')?.innerText || ''), null, { timeout: 90000, polling: 50 });
      // Keep the real toast on screen while it is annotated (its own timer would remove it).
      await v.eval(() => {
        const t = [...document.querySelectorAll('.arraysubs-portal-toast')].find((e) => /Offer accepted/.test(e.innerText));
        if (t) { const c = t.cloneNode(true); c.classList.add('fv-keep'); t.replaceWith(c); }
      });
      await sleep(200);
    }, { settle: false, fade: 0.3, during: (t) => { v.cursor.press = 1 - t; v.ripple.p = Math.max(0.02, t); } });
    v.cursor.press = 0;
    v.ripple = null;
    await v.focus('.arraysubs-portal-toast.fv-keep', { label: 'Saved. Nothing was cancelled.', side: 'bottom', hold: 1.6 });
    await v.unfocus();
    await v.transition(async () => { release(); await nav; }, { fade: 0.4 });
  } finally {
    release?.();
    await v.context.unroute(VIEW_PATTERN, delayDoc).catch(() => {});
  }
}

async function latestMailId() {
  try { const d = await (await fetch(`${MAILPIT}/api/v1/messages?limit=1`)).json(); return d.messages?.[0]?.ID || null; } catch { return null; }
}

async function findMail(to, re, sinceId, timeout = 30000) {
  const t0 = Date.now();
  while (Date.now() - t0 < timeout) {
    const d = await (await fetch(`${MAILPIT}/api/v1/messages?limit=25`)).json();
    for (const m of d.messages || []) {
      if (sinceId && m.ID === sinceId) break;
      if (re.test(m.Subject) && (m.To || []).some((x) => x.Address === to)) return m.ID;
    }
    await sleep(700);
  }
  // fall back to any earlier message for this customer (re-recording)
  const d = await (await fetch(`${MAILPIT}/api/v1/messages?limit=200`)).json();
  return (d.messages || []).find((m) => re.test(m.Subject) && (m.To || []).some((x) => x.Address === to))?.ID || null;
}

/** The real email the customer got, shown as a simple message view (display-only "Demo · " strip). */
async function buildMailPage(id) {
  const m = await (await fetch(`${MAILPIT}/api/v1/message/${id}`)).json();
  const clean = (s) => String(s || '').replace(/Demo (·|&middot;|&#183;|&#xB7;) /g, '');
  const html = clean(m.HTML);
  const body = (html.match(/<body[^>]*>([\s\S]*)<\/body>/i) || [, html])[1];
  const styles = (html.match(/<style[\s\S]*?<\/style>/gi) || []).join('\n');
  const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');
  const to = (m.To || []).map((x) => x.Address).join(', ');
  return `<!doctype html><html><head><meta charset="utf-8"><title>${esc(clean(m.Subject))}</title>${styles}
<style>
html,body{margin:0;background:#eceef2;}
.fv-mail{max-width:800px;margin:34px auto 60px;background:#fff;border-radius:14px;box-shadow:0 1px 2px rgba(0,0,0,.06),0 8px 30px rgba(0,0,0,.06);overflow:hidden;}
.fv-mail__head{padding:24px 34px 20px;border-bottom:1px solid #e6e8ec;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif;text-align:left;}
.fv-mail__subject{font-size:21px;font-weight:650;color:#16181d;line-height:1.3;}
.fv-mail__meta{margin-top:8px;font-size:14px;color:#5b6170;}
.fv-mail__meta b{color:#16181d;font-weight:600;}
.fv-mail__body{padding:0 0 8px;}
</style></head><body>
<div class="fv-mail"><div class="fv-mail__head"><div class="fv-mail__subject">${esc(clean(m.Subject))}</div>
<div class="fv-mail__meta">From <b>${esc(m.From?.Name || 'Brightside Market')}</b> &nbsp;·&nbsp; To <b>${esc(to)}</b></div></div>
<div class="fv-mail__body">${body}</div></div></body></html>`;
}

export default {
  slug: 'cancellation-flow',
  tier: 'Free + Pro',
  intro: { title: 'Keep subscribers who were about to *leave*', sub: 'Ask why they cancel, then offer a discount, a pause or a cheaper plan.' },
  outro: { title: 'Stop one-click *goodbyes*.', sub: 'The cancel survey and undo are free. Save offers, targeting and the report come with Pro.', url: 'subscriptionplugin.pro' },

  setup: async (v) => {
    assertPro();
    fs.mkdirSync(OUT, { recursive: true });
    let st = {};
    try { st = JSON.parse(fs.readFileSync(STATE, 'utf8')); } catch {}
    const only = v.only;
    const fresh = (chs) => !only || chs.some((c) => only.has(c));
    for (const [role, chs] of [['A', [4, 5]], ['B', [6]]]) {
      if (fresh(chs) || !st[role]) {
        const pick = subInfo(CANDIDATES[role]).find((x) => x.status === 'arraysubs-active' && !x.accepted);
        if (!pick) throw new Error(`no unused subscription left for customer ${role}`);
        st[role] = pick;
      }
    }
    fs.writeFileSync(STATE, JSON.stringify(st, null, 2));
    S.A = st.A;
    S.B = st.B;
    console.log(`  customer A: sub ${S.A.sub} user ${S.A.user} (${S.A.first}); customer B: sub ${S.B.sub} user ${S.B.user} (${S.B.first})`);
    await v.context.route('http://localhost:10013/__fvmail/**', (route) => route.fulfill({
      status: 200, contentType: 'text/html; charset=utf-8', body: S.mailHtml || '<!doctype html><html><body></body></html>',
    }));
  },

  chapters: [
    // 1 ------------------------------------------------------------------ overview page
    {
      kicker: 'Admin',
      title: 'One page for the whole *flow*',
      sub: 'Your reasons, your offers and who sees them.',
      run: async (v) => {
        await v.as(1);
        await v.goto(CF_URL);
        await v.say('**Cancellation Flow** has its own page in ArraySubs.');
        await v.focus('#adminmenu a[href*="#/cancellation-flow"]', { label: 'ArraySubs > Cancellation Flow', side: 'right' });
        await v.say('A leaving customer picks a reason, sees an offer, then decides.');
        await v.focus('.arraysubs-flow-journey', { label: 'Reason, offer, decision', dim: true, side: 'bottom' });
        await v.focus('.arraysubs-flow-summary-grid .arraysubs-flow-card >> nth=0', { label: '8 reasons, an answer is required', side: 'bottom' });
        await v.focus('.arraysubs-flow-summary-grid .arraysubs-flow-card >> nth=1', { label: 'All 4 offer types on', side: 'bottom' });
        await v.scrollTo('.arraysubs-flow-section-heading', { block: 'start' });
        await v.say('Every offer has its own **reasons** and **rules**.');
        await v.zoom('.arraysubs-flow-offer >> nth=0', { scale: 1.45 });
        await v.focus('.arraysubs-flow-offer >> nth=0 >> .arraysubs-flow-offer__value', { label: 'The offer', side: 'right' });
        await v.focus('.arraysubs-flow-offer >> nth=0 >> dl > div >> nth=0', { label: 'Shown only for these reasons', side: 'bottom' });
        await v.focus('.arraysubs-flow-offer >> nth=0 >> .arraysubs-flow-offer__conditions', { label: 'Who qualifies', side: 'right' });
        await v.zoomOut();
        await v.scrollTo('.arraysubs-flow-offer >> nth=2', { block: 'start' });
        await v.say('A **cheaper plan** and a **support** link are ready too.');
        await v.focus('.arraysubs-flow-offer >> nth=2 >> .arraysubs-flow-offer__value', { label: 'Step down a plan', side: 'bottom' });
        await v.focus('.arraysubs-flow-offer >> nth=3 >> .arraysubs-flow-offer__value', { label: 'Send them to your help page', side: 'bottom' });
        await v.unfocus();
      },
    },

    // 2 ------------------------------------------------------------------ builder: reasons
    {
      kicker: 'Flow builder',
      title: 'Ask why in your *own words*',
      sub: 'A 7-step editor, one save at the end.',
      run: async (v) => {
        await v.as(1);
        await v.goto(CF_URL.replace('#', '&fv=builder#'));
        await v.say('One editor walks you through the whole flow.');
        await v.focus('text=Edit cancellation flow', { label: 'Open the editor' });
        await v.click('text=Edit cancellation flow');
        await v.focus('.boxed-fullscreen-modal__progress', { label: '7 short steps', side: 'bottom' });
        await v.say('Step 1: the reasons customers can pick.');
        await v.focus('#arraysubs-field-cancellation-require_reason', { label: 'Make a reason required', side: 'right' });
        await v.scrollTo('.arraysubs-fb-repeater-edit >> nth=3', { block: 'start' });
        await v.say('Add, rename, **drag to reorder** or delete reasons.');
        await v.focus('.arraysubs-fb-repeater-add-button', { label: 'Add your own', side: 'top' });
        await v.click('.arraysubs-fb-repeater-edit >> nth=6');
        const keyField = '.arraysubs-fb-repeater-item-open .field-warper:has(label:has-text("Reason Key"))';
        const labelField = '.arraysubs-fb-repeater-item-open .field-warper:has(label:has-text("Display Label"))';
        await v.scrollTo(labelField, { block: 'center' });
        await v.say('Write reasons that fit what you sell.');
        await v.zoom(await union(v, [keyField, labelField]), { pad: 28 });
        await v.focus(labelField, { label: 'What customers read', side: 'bottom' });
        await v.focus(`${labelField} .arraysubs-tf-btn`, { label: 'Translate it', side: 'left' });
        await v.zoomOut();
        await v.click('.arraysubs-fb-repeater-edit >> nth=6');
        await nextStep(v);
        await v.say('Step 2: one switch turns the **save offers** on.');
        await v.focus('#arraysubs-field-cancellation-retention_offers_enabled', { label: 'Enable Retention Offers', side: 'right' });
        await v.unfocus();
      },
    },

    // 3 ------------------------------------------------------------------ builder: offers + targeting
    {
      kicker: 'Flow builder',
      title: 'Offers that fit the *reason*',
      sub: 'A discount, a pause, a cheaper plan or your support team.',
      before: async (v) => {
        // Re-recording this chapter alone: open the editor on step 2 off camera.
        const open = await v.frame.locator('.boxed-fullscreen-modal__progress').isVisible().catch(() => false);
        if (!open) {
          await v.as(1);
          await v.goto(CF_URL);
          await v.frame.locator('text=Edit cancellation flow').first().click();
          await v.settle();
          await v.frame.locator('.boxed-fullscreen-modal__footer-next').click();
          await v.settle();
        }
      },
      run: async (v) => {
        await nextStep(v);
        await v.say('Step 3: a **discount** for the reasons it can fix.');
        await v.focus('.boxed-fullscreen-modal__body .arraysubs-fb-multiselect:visible >> nth=0', { label: 'Shown only for these reasons', side: 'bottom' });
        const pct = await union(v, ['#arraysubs-field-cancellation-retention_offers-discount-discount_percent'], '.field-warper');
        const cyc = await union(v, ['#arraysubs-field-cancellation-retention_offers-discount-discount_cycles'], '.field-warper');
        await v.zoom(pct, { scale: 1.6 });
        await v.focus(pct, { label: '20% off', side: 'bottom' });
        await v.zoom(cyc, { scale: 1.6 });
        await v.focus(cyc, { label: 'For the next 2 renewals', side: 'bottom' });
        await v.unfocus();
        await v.zoomOut();
        await v.scrollTo('#arraysubs-field-cancellation-retention_offers-discount-min_subscription_age_days', { block: 'start', offset: -40 });
        await v.say('Then narrow it down: age, price, **lifetime spend**, days left.');
        const ids = ['min_subscription_age_days', 'min_subscription_value', 'max_subscription_value', 'min_user_total_value', 'max_user_total_value', 'min_remaining_days', 'max_remaining_days'].map((k) => `#arraysubs-field-cancellation-retention_offers-discount-${k}`);
        await v.focus(await union(v, ids, '.field-warper'), { label: 'Who sees this offer', dim: true, side: 'top' });
        await v.unfocus();
        await nextStep(v);
        await v.say('Step 4: a **pause** that ends by itself.');
        await v.focus(await union(v, ['[id$="retention_offers-pause-max_pause_days"]'], '.field-warper'), { label: 'Up to 30 days', side: 'bottom' });
        await nextStep(v);
        await v.say('Step 5: a **cheaper plan**, from your plan switching paths.');
        await v.focus('.boxed-fullscreen-modal__body .arraysubs-fb-multiselect:visible >> nth=0', { label: 'Too expensive or not using it', side: 'bottom' });
        await v.focus('.boxed-fullscreen-modal__body .arraysubs-alert-info:visible >> nth=0', { label: 'Uses your downgrade paths', side: 'right' });
        await nextStep(v);
        await v.say('Step 6: send them to **your help page** instead.');
        await v.focus(await union(v, ['[id$="retention_offers-contact_support-support_url"]'], '.field-warper'), { label: 'Your support page', side: 'bottom' });
        await nextStep(v);
        await v.say('Step 7: review it all, then **one save** puts it live.');
        await v.focus('.boxed-fullscreen-modal__body .arraysubs-flow-card >> nth=0', { label: 'Your reasons', side: 'bottom' });
        await v.focus('.boxed-fullscreen-modal__footer-actions', { label: 'Save cancellation flow', side: 'top' });
        await v.unfocus();
        await v.click('.boxed-fullscreen-modal__close');
      },
    },

    // 4 ------------------------------------------------------------------ customer A cancels
    {
      kicker: 'My Account',
      title: 'They click *Cancel*',
      sub: 'A reason first, then a better option.',
      run: async (v) => {
        await asCustomer(v, S.A);
        await v.goto('/my-account/as-subscriptions/');
        await v.say(`${S.A.first} wants to cancel a **${S.A.product}**.`);
        await v.zoom('.arraysubs-subscription-card', { scale: 1.35 });
        await v.focus('.arraysubs-subscription-card', { label: `${S.A.recurring.toFixed(2)}$ every month`, side: 'right' });
        await v.zoomOut();
        await v.click('.arraysubs-subscription-card__actions a:has-text("View Details")', { nav: true });
        await v.scrollTo('.subscription-skip-pause', { block: 'start' });
        await v.say('Gentler choices sit **above** the cancel link.');
        await v.focus(await union(v, ['.subscription-skip-pause__section:not(.subscription-skip-pause__section--cancel)']), { label: 'Change plan, skip or pause', side: 'left' });
        await v.focus('.subscription-skip-pause__section--cancel', { label: 'Cancel is still one click', side: 'left' });
        await v.click('.arraysubs-cancel-subscription');
        await v.zoom('#arraysubs-cancel-modal .arraysubs-modal__container', { scale: 1.6 });
        await v.say('First, a short **reason** survey.');
        await v.focus('#cancel-reason', { label: 'Why are you leaving?', side: 'bottom' });
        await v.select('#cancel-reason', 'too_expensive');
        assertPro();
        await v.focus('#arraysubs-cancel-modal .arraysubs-modal__cancel-btn', { label: 'Keep is the big button', side: 'bottom' });
        await v.click('#arraysubs-cancel-modal .arraysubs-modal__confirm-btn');
        await offersView(v);
        await v.say('Then only the offers that fit **"Too expensive"**.');
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer >> nth=0', { label: '20% off for 2 renewals', side: 'left' });
        v.poster();
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer >> nth=1', { label: 'Or a cheaper plan', side: 'left' });
        const plan = await v.frame.locator('#arraysubs-retention-modal .arraysubs-retention-offer__target-product option:not([value=""])').first().getAttribute('value');
        await v.select('#arraysubs-retention-modal .arraysubs-retention-offer__target-product', plan);
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer__target-product', { label: 'Real plans, real prices', side: 'bottom' });
        await v.say('They can always keep it as is, or **still cancel**.');
        await v.focus('#arraysubs-retention-modal .arraysubs-modal__footer', { label: 'Never trapped', side: 'top' });
        await v.unfocus();
        await v.zoomOut();
      },
    },

    // 5 ------------------------------------------------------------------ customer A accepts
    {
      kicker: 'My Account',
      title: 'One click *keeps* them',
      sub: 'The lower price applies by itself.',
      before: async (v) => {
        const open = await v.frame.locator('#arraysubs-retention-modal .arraysubs-retention-offer').first().isVisible().catch(() => false);
        if (!open) {
          await asCustomer(v, S.A);
          await v.goto(viewUrl(S.A));
          await v.frame.evaluate(() => document.querySelector('.subscription-skip-pause')?.scrollIntoView({ block: 'start' }));
          assertPro();
          await openOffersFast(v, 'too_expensive');
        }
        S.mailSince = await latestMailId();
      },
      run: async (v) => {
        await v.cardOut();
        await offersView(v);
        await v.say('They take the discount with **one click**.');
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer >> nth=0 >> .arraysubs-retention-offer__accept-btn', { label: 'Accept Offer', side: 'bottom' });
        await v.unfocus();
        await v.zoomOut();
        await acceptOffer(v, '#arraysubs-retention-modal .arraysubs-retention-offer >> nth=0 >> .arraysubs-retention-offer__accept-btn');
        await v.scrollTo(0);
        await v.say('The lower price shows on the subscription **right away**.');
        const row = 'tr:has-text("Recurring Amount")';
        await v.zoom(row, { scale: 1.7 });
        await v.focus(row, { label: 'Discounted for the next 2 renewals', side: 'bottom' });
        await v.zoomOut();
        await v.focus('tr:has-text("Status")', { label: 'Still Active', side: 'right' });
        const id = await findMail(S.A.email, /retention discount/i, S.mailSince);
        if (!id) throw new Error('retention discount email not found in Mailpit');
        S.mailHtml = await buildMailPage(id);
        await v.goto('/__fvmail/retention-discount.html');
        await v.say('An email confirms the **old and new** price.');
        await v.focus('.fv-mail__head', { label: 'Sent to the customer', side: 'bottom' });
        await v.scrollTo('.fv-mail__body table.td', { block: 'center' });
        await v.zoom('.fv-mail__body table.td', { scale: 1.45 });
        await v.focus('.fv-mail__body table.td', { label: 'Old price, new price, savings', side: 'right' });
        await v.zoomOut();
      },
    },

    // 6 ------------------------------------------------------------------ customer B pauses
    {
      kicker: 'My Account',
      title: 'Need a break? *Pause*',
      sub: 'A different reason brings a different offer.',
      run: async (v) => {
        await asCustomer(v, S.B);
        await v.goto(viewUrl(S.B));
        await v.say(`${S.B.first} is **not using** a ${S.B.product} enough.`);
        const before = (await rowValue(v, 'Next Payment')).split(',')[0];
        await v.focus('tr:has-text("Next Payment")', { label: `Next payment: ${before}`, side: 'bottom' });
        await v.scrollTo('.subscription-skip-pause__section--cancel', { block: 'center' });
        await v.click('.arraysubs-cancel-subscription');
        await v.zoom('#arraysubs-cancel-modal .arraysubs-modal__container', { scale: 1.6 });
        await v.select('#cancel-reason', 'not_using');
        assertPro();
        await v.focus('#cancel-reason', { label: 'Not using it enough', side: 'bottom', hold: 1.2 });
        await v.click('#arraysubs-cancel-modal .arraysubs-modal__confirm-btn');
        await offersView(v);
        await v.say('This reason brings a **pause** and a cheaper plan.');
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer >> nth=0', { label: 'Pause instead of leaving', side: 'left' });
        await v.unfocus();
        await v.zoomOut();
        await acceptOffer(v, '#arraysubs-retention-modal .arraysubs-retention-offer >> nth=0 >> .arraysubs-retention-offer__accept-btn');
        await v.scrollTo(0);
        await v.say('Billing stops, and the next payment **moves out** by the pause.');
        await v.focus('tr:has-text("Status")', { label: 'Paused', side: 'right' });
        const after = (await rowValue(v, 'Next Payment')).split(',')[0];
        await v.focus('tr:has-text("Next Payment")', { label: `Now ${after}`, side: 'bottom' });
        await v.scrollTo('.subscription-skip-pause', { block: 'start' });
        await v.say('It **resumes by itself** on the scheduled date.');
        await v.focus('.subscription-skip-pause__section:has-text("Vacation Mode")', { label: 'Scheduled resume', side: 'left' });
        await v.unfocus();
      },
    },

    // 7 ------------------------------------------------------------------ admin: subscription history
    {
      kicker: 'Admin',
      title: 'The story on each *subscription*',
      sub: 'What they saw, and what they took.',
      run: async (v) => {
        await v.as(1);
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${S.A.sub}`);
        await v.say(`Back in admin: ${S.A.first}'s subscription, **saved**.`);
        await v.focus('.arraysubs-sd__stat >> nth=0', { label: 'Retention discount applied', side: 'bottom' });
        await v.focus('.arraysubs-sd__kv-row:has-text("Retention discount")', { label: '2 renewals left', side: 'right' });
        await v.scrollTo('section.arraysubs-sd__panel:has-text("Retention History")', { block: 'start' });
        await v.say('**Retention History** keeps the offers shown and the one taken.');
        await v.zoom('section.arraysubs-sd__panel:has-text("Retention History")', { scale: 1.5 });
        await v.focus('section.arraysubs-sd__panel:has-text("Retention History") .arraysubs-sd__kv', { label: 'Shown and accepted, with dates', side: 'bottom' });
        await v.zoomOut();
        const note = '.timeline-event:has-text("accepted retention offer")';
        await v.scrollTo(note, { block: 'center' });
        await v.say('A note records the **terms** they accepted.');
        await v.focus(note, { label: 'The exact offer terms', side: 'left' });
        await v.unfocus();
      },
    },

    // 8 ------------------------------------------------------------------ retention report
    {
      kicker: 'Reports',
      title: 'Know why people *leave*',
      sub: 'The Retention report in WooCommerce Analytics.',
      run: async (v) => {
        await v.as(1);
        await v.act(async (f) => {
          await f.goto(`http://localhost:10013${REPORT_URL}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
          await f.waitForSelector('.arraysubs-retention-cohort__table', { timeout: 30000 });
          // The data-quality notice only describes this demo store's backfilled history.
          await f.addStyleTag({ content: '.arraysubs-retention-quality{display:none!important}' });
        });
        await v.say('The **Retention** report lives in WooCommerce Analytics.');
        await v.focus('#adminmenu a[href*="arraysubs-retention"]', { label: 'Analytics > Retention', side: 'right' });
        await v.focus('.arraysubs-retention-filters', { label: 'Any dates and products', side: 'bottom' });
        await v.say('See **who stays**, month by month, from signup.');
        await v.focus('.arraysubs-retention-report-card--wide', { label: 'Retention by signup month', dim: true, side: 'top' });
        await v.scrollTo('#arraysubs-retention-card-r9', { block: 'start', offset: -30 });
        await v.say('Split churn into **choice** and **failed payment**.');
        await v.focus(await union(v, ['#arraysubs-retention-card-r9'], 'section'), { label: 'Voluntary vs involuntary', side: 'left' });
        await v.scrollTo('#arraysubs-retention-card-rd', { block: 'start', offset: -30 });
        await v.say('And the **top reasons** customers give for leaving.');
        await v.focus(await union(v, ['#arraysubs-retention-card-rd'], 'section'), { label: 'Top 10 reasons', side: 'left' });
        await v.scrollTo('#arraysubs-retention-activity-title', { block: 'start', offset: -20 });
        await v.say('Every offer shown, accepted or declined is **logged**.');
        await v.select('#arraysubs-retention-event-filter', 'offer_accepted');
        const rows = (n) => v.eval((n) => {
          const r = [...document.querySelectorAll('.arraysubs-retention-logs__subscription-link')].slice(0, n).map((a) => a.closest('tr').getBoundingClientRect());
          return { x: r[0].left, y: r[0].top, w: r[0].width, h: r[r.length - 1].bottom - r[0].top };
        }, n);
        await v.zoom(await rows(3), { scale: 1.35 });
        await v.focus(await rows(2), { label: 'Just now: a pause and a discount', side: 'bottom' });
        await v.zoomOut();
        await v.unfocus();
      },
    },
  ],
};
