// Cancellation flow: from cancel click to save offer.
// Story: set the flow up in admin (and save a new reason) -> a customer cancels and keeps a discount
// -> another pauses -> a third still leaves (booked for the period end) -> admin sees the history,
// the reason in the customer's words, undoes the booked cancel -> the Retention report.
// Store notes and the change log: qa/feature-videos/notes/cancellation-flow.md
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { wp, wpJson, W, H, SITE } from '../lib/director.mjs';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.join(process.env.FV_OUT || path.join(HERE, '..', 'out'), 'cancellation-flow');
const STATE = path.join(OUT, 'state.json');
const MAILPIT = 'http://localhost:10010';
const CF_URL = '/wp-admin/admin.php?page=arraysubs-mainadmin#/cancellation-flow';
const REPORT_URL = '/wp-admin/admin.php?page=wc-admin&path=/analytics/arraysubs-retention';
const VIEW_PATTERN = '**/my-account/as-view-subscription/**';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// The reason added (and saved) on camera in the builder. Removed again in setup/teardown.
const NEW_REASON = { key: 'prefer_one_off', label: 'Prefer one-off orders' };
// Customer C's own words for "Other".
const C_WORDS = 'Moving abroad next month, will be back.';

// Customers from this scene's block (617-677).
// A = accepts the discount, B = accepts the pause (Plus Membership, cheaper Starter plan available),
// C = still cancels (booked for the period end), then admin undoes it. C is reusable after the undo.
const CANDIDATES = {
  A: [27676, 27842, 28034], // Plus Membership $39/month
  B: [27996, 28034, 27842], // Plus Membership $39/month
  C: [28076, 27632, 27736, 28109], // Starter Membership $19/month (27933 used: an undo marks the sub as offer-accepted)
};

const S = { A: null, B: null, C: null, mailHtml: '' };

function subInfo(ids) {
  return wpJson(`$out=[]; foreach ([${ids.map(Number).join(',')}] as $id) { $c=(int)get_post_meta($id,'_customer_id',true); $u=get_userdata($c); $out[]=['sub'=>$id,'user'=>$c,'email'=>$u?$u->user_email:'','first'=>$u?$u->first_name:'','status'=>get_post_status($id),'accepted'=>(bool)(get_post_meta($id,'_retention_offer_accepted',true) || get_post_meta($id,'_accepted_retention_offer',true)),'waiting'=>(bool)get_post_meta($id,'_waiting_cancellation',true),'recurring'=>(float)get_post_meta($id,'_recurring_amount',true),'product'=>trim(preg_replace('/^Demo\\s*\\x{00B7}\\s*/u','',html_entity_decode(get_the_title((int)get_post_meta($id,'_product_id',true)))))]; } echo json_encode($out);`);
}

function assertPro() {
  // wp() runs with FV_PRO=1, the same Pro-on view the recording browser gets.
  const ok = wp(['eval', "echo (class_exists('\\\\ArraySubsPro\\\\Features\\\\CancellationFlow\\\\Services\\\\RetentionOfferProcessor') && function_exists('arraysubs_is_retention_offers_enabled') && arraysubs_is_retention_offers_enabled()) ? 'yes' : 'no';"]);
  if (!/yes$/.test(ok)) throw new Error('ArraySubs Pro retention offers are not available: refusing to record (Continue would cancel for real)');
}

/** Remove the reason this scene adds on camera (raw DB write, nothing else touched). */
function removeNewReason() {
  return wp(['eval', `$s=get_option('arraysubs_settings'); $r=$s['cancellation']['reasons']??[]; $f=array_values(array_filter($r, function($x){ return (($x['key']??'')!=='${NEW_REASON.key}'); })); if (count($f)!==count($r)) { $s['cancellation']['reasons']=$f; update_option('arraysubs_settings',$s); echo 'removed'; } else { echo 'none'; }`]);
}

/** fv_cf: lets the temporary mu-plugin apply this scene's display/runtime overrides (see notes). */
async function cfCookie(v) {
  await v.context.addCookies([{ name: 'fv_cf', value: '1', domain: 'localhost', path: '/', expires: Math.floor(Date.now() / 1000) + 86400 }]);
}
async function asAdmin(v) { await v.as(1); await cfCookie(v); }
async function asCustomer(v, c) { await v.as(c.user); await cfCookie(v); }

const viewUrl = (c) => `/my-account/as-view-subscription/${c.sub}/`;
const money = (n) => `$${Number(n).toFixed(2)}`;

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

/** Rect of an element's contents (text and children), not the whole cell. */
async function textRect(v, sel) {
  const r = await v.frame.locator(sel).first().evaluate((el) => {
    const rg = document.createRange();
    rg.selectNodeContents(el);
    const b = rg.getBoundingClientRect();
    return { x: b.left, y: b.top, w: b.width, h: b.height };
  });
  return { x: r.x - 4, y: r.y - 2, w: r.w + 8, h: r.h + 4 };
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
 * Zoom on a My Account window but keep it to the right and up, so the lower-left caption
 * sits on the dimmed page instead of the window's own buttons.
 */
async function modalView(v, sel = '#arraysubs-retention-modal .arraysubs-modal__container', s = 1.25) {
  const r = await v.appRect(sel);
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
 * Click Accept Offer, zoom onto the success toast, then crossfade to the reloaded page.
 * The reload document is held back until the toast frame has been captured.
 */
async function acceptOffer(v, btn, label) {
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
      await v.eval(() => {
        // Keep the real toast on screen while it is annotated (its own timer would remove it).
        const t = [...document.querySelectorAll('.arraysubs-portal-toast')].find((e) => /Offer accepted/.test(e.innerText));
        if (t) { const c = t.cloneNode(true); c.classList.add('fv-keep'); t.replaceWith(c); }
        // The work is done: show the button at rest instead of its spinner while the toast is read.
        const $ = window.jQuery;
        document.querySelectorAll('#arraysubs-retention-modal .arraysubs-btn--loading').forEach((b) => {
          const o = $ ? $(b).data('arraysubs-original-html') : null;
          if (o != null) b.innerHTML = o;
          b.classList.remove('arraysubs-btn--loading');
        });
      });
      await sleep(200);
    }, { settle: false, fade: 0.3, during: (t) => { v.cursor.press = 1 - t; v.ripple.p = Math.max(0.02, t); } });
    v.cursor.press = 0;
    v.ripple = null;
    await v.hideCursor();
    await v.zoom('.arraysubs-portal-toast.fv-keep', { scale: 1.35, dur: 0.7 });
    await v.focus('.arraysubs-portal-toast.fv-keep', { label, side: 'bottom', hold: 1.6 });
    await v.unfocus();
    await v.zoomOut({ dur: 0.6 });
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
  intro: { title: 'Keep subscribers about to *leave*', sub: 'Ask why, then offer a better option than leaving.' },
  outro: { title: 'Stop one-click *goodbyes*.', sub: 'The cancel survey and undo are free. Save offers, targeting and the report come with Pro.', url: 'subscriptionplugin.pro' },

  setup: async (v) => {
    assertPro();
    fs.mkdirSync(OUT, { recursive: true });
    console.log(`  leftover reason: ${removeNewReason()}`);
    let st = {};
    try { st = JSON.parse(fs.readFileSync(STATE, 'utf8')); } catch {}
    const only = v.only;
    const fresh = (chs) => !only || chs.some((c) => only.has(c));
    const ok = {
      A: (x) => x.status === 'arraysubs-active' && !x.accepted && !x.waiting,
      B: (x) => x.status === 'arraysubs-active' && !x.accepted && !x.waiting,
      C: (x) => x.status === 'arraysubs-active' && !x.accepted && !x.waiting,
    };
    for (const [role, chs] of [['A', [4, 5]], ['B', [6]], ['C', [7]]]) {
      if (fresh(chs) || !st[role]) {
        const used = ['A', 'B', 'C'].filter((r) => r !== role && S[r]).map((r) => S[r].sub);
        const pick = subInfo(CANDIDATES[role]).find((x) => ok[role](x) && !used.includes(x.sub));
        if (!pick) throw new Error(`no unused subscription left for customer ${role}`);
        st[role] = pick;
      }
      S[role] = st[role];
    }
    fs.writeFileSync(STATE, JSON.stringify(st, null, 2));
    for (const r of ['A', 'B', 'C']) console.log(`  customer ${r}: sub ${S[r].sub} user ${S[r].user} (${S[r].first}, ${S[r].product})`);
    await v.context.route('http://localhost:10013/__fvmail/**', (route) => route.fulfill({
      status: 200, contentType: 'text/html; charset=utf-8', body: S.mailHtml || '<!doctype html><html><body></body></html>',
    }));
  },

  teardown: async () => {
    console.log(`  restore reasons: ${removeNewReason()}`);
    if (S.C) {
      const c = subInfo([S.C.sub])[0];
      if (c?.waiting) {
        console.log(`  C ${S.C.sub} still has a booked cancel: undoing it`);
        console.log(wp(['eval', `echo arraysubs_undo_scheduled_cancellation(${Number(S.C.sub)}, 'admin:1') ? 'undone' : 'failed';`]));
      }
    }
  },

  chapters: [
    // 1 ------------------------------------------------------------------ overview page
    {
      kicker: 'Admin',
      title: 'One page for the whole *flow*',
      sub: 'Your reasons, your offers and who sees them.',
      run: async (v) => {
        await asAdmin(v);
        await v.goto(CF_URL);
        await v.say('Your **cancellation flow**: a reason, an offer, then a decision.', { hold: 1.4 });
        await v.focus('.arraysubs-flow-journey', { label: 'What a leaving customer sees', dim: true, side: 'bottom' });
        await v.scrollTo('.arraysubs-flow-summary-grid', { block: 'start', offset: -105 });
        await v.focus('.arraysubs-flow-summary-grid', { label: '8 reasons, 4 offers', side: 'top', hold: 1.7 });
        await v.scrollTo('.arraysubs-flow-section-heading', { block: 'start' });
        await v.say('Every offer has its own **reasons** and **rules**.', { hold: 1.2 });
        const cond = '.arraysubs-flow-offer >> nth=0 >> .arraysubs-flow-offer__conditions';
        await v.zoom(cond, { scale: 1.5 });
        await v.focus(cond, { label: 'Who qualifies', side: 'bottom', dim: true, hold: 1.6 });
        await v.zoomOut();
      },
    },

    // 2 ------------------------------------------------------------------ builder: reasons
    {
      kicker: 'Flow builder',
      title: 'Ask why in your *own words*',
      cardHold: 1.3,
      sub: 'Seven short steps, one save at the end.',
      run: async (v) => {
        await asAdmin(v);
        // Open the editor off camera: the card lifts straight onto it.
        await v.act(async (f) => {
          await f.goto(`${SITE}${CF_URL.replace('#', '&fv=builder#')}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
          await v.settle();
          await f.locator('text=Edit cancellation flow').first().click();
          await f.locator('.boxed-fullscreen-modal__progress').waitFor({ state: 'visible', timeout: 20000 });
        });
        await v.say('One editor walks you through the **whole flow**.', { hold: 1.1 });
        await v.focus('.boxed-fullscreen-modal__progress', { label: '7 short steps', side: 'bottom', hold: 1.3 });
        await v.focus(await union(v, ['#arraysubs-field-cancellation-require_reason', '.arraysubs-fb-switch-label:visible >> nth=0']), { label: 'Step 1: make a reason required', side: 'right', hold: 1.6 });
        await v.scrollTo('.arraysubs-fb-repeater-item >> nth=6', { block: 'center' });
        await v.say('Each reason can be **dragged**, edited or deleted.', { hold: 1.1 });
        await v.focus('.arraysubs-fb-repeater-item >> nth=6 >> .arraysubs-fb-repeater-item-header', { label: 'Drag, edit or delete', side: 'top', hold: 1.4 });
        await v.scrollTo('.arraysubs-fb-repeater-add-button', { block: 'center' });
        await v.say('Add one that fits **what you sell**.', { hold: 1.0 });
        await v.click('.arraysubs-fb-repeater-add-button');
        const open = '.arraysubs-fb-repeater-item-open';
        const inputs = `${open} input.arraysubs-fb-input`;
        await v.scrollTo(`${inputs} >> nth=1`, { block: 'center' });
        await v.type(`${inputs} >> nth=0`, NEW_REASON.key, { cps: 22 });
        await v.type(`${inputs} >> nth=1`, NEW_REASON.label, { cps: 20 });
        await v.hideCursor();
        const labelField = `${open} .field-warper:has(label:has-text("Display Label"))`;
        await v.zoom(await union(v, [`${open} .field-warper:has(label:has-text("Reason Key"))`, labelField]), { pad: 40 });
        await v.focus(labelField, { label: 'What customers read', side: 'bottom', hold: 1.3 });
        await v.focus(`${labelField} .arraysubs-tf-btn`, { label: 'Translate it', side: 'left', hold: 1.2 });
        await v.zoomOut();
        await nextStep(v);
        await v.say('Step 2: switch offers **on or off**.', { hold: 1.0 });
        await v.focus(await union(v, ['.boxed-fullscreen-modal__body .arraysubs-fb-switch:visible >> nth=0', '.boxed-fullscreen-modal__body .arraysubs-fb-switch-label:visible >> nth=0']), { label: 'Offers on or off', side: 'right', hold: 1.3 });
      },
    },

    // 3 ------------------------------------------------------------------ builder: offers + targeting + save
    {
      kicker: 'Flow builder',
      title: 'Offers that fit the *reason*',
      cardHold: 1.3,
      sub: 'Discount, pause, cheaper plan or your help page.',
      before: async (v) => {
        // Re-recording this chapter alone: open the editor, add the reason, go to step 2 off camera.
        const open = await v.frame.locator('.boxed-fullscreen-modal__progress').isVisible().catch(() => false);
        if (!open) {
          await asAdmin(v);
          await v.goto(CF_URL);
          await v.frame.locator('text=Edit cancellation flow').first().click();
          await v.settle();
          await v.frame.locator('.arraysubs-fb-repeater-add-button').click();
          await v.settle();
          const inputs = v.frame.locator('.arraysubs-fb-repeater-item-open input.arraysubs-fb-input');
          await inputs.nth(0).fill(NEW_REASON.key);
          await inputs.nth(1).fill(NEW_REASON.label);
          await v.frame.locator('.boxed-fullscreen-modal__footer-next').click();
          await v.settle();
        }
      },
      run: async (v) => {
        await v.cardOut();
        await nextStep(v);
        await v.say('Step 3: a **discount** for the reasons it can fix.', { hold: 1.1 });
        await v.focus('.boxed-fullscreen-modal__body .arraysubs-fb-multiselect:visible >> nth=0', { label: 'Shown only for these reasons', side: 'bottom', hold: 1.6 });
        const terms = await union(v, ['#arraysubs-field-cancellation-retention_offers-discount-discount_percent', '#arraysubs-field-cancellation-retention_offers-discount-discount_cycles'], '.field-warper');
        await v.zoom(terms, { pad: 60 });
        await v.focus(terms, { label: '20% off the next 2 renewals', side: 'bottom', hold: 1.6 });
        await v.zoomOut();
        await v.scrollTo('#arraysubs-field-cancellation-retention_offers-discount-min_subscription_age_days', { block: 'start', offset: -40 });
        await v.say('Then narrow it down: age, price, **lifetime spend**, days left.', { hold: 1.3 });
        const ids = ['min_subscription_age_days', 'min_subscription_value', 'max_subscription_value', 'min_user_total_value', 'max_user_total_value', 'min_remaining_days', 'max_remaining_days'].map((k) => `#arraysubs-field-cancellation-retention_offers-discount-${k}`);
        await v.focus(await union(v, ids, '.field-warper'), { label: 'Who sees this offer', dim: true, side: 'right', hold: 1.6 });
        await nextStep(v);
        await v.say('Step 4: a **pause** of up to 30 days, ending by itself.', { hold: 2.0 });
        await nextStep(v);
        await v.say('Step 5: a **cheaper plan**, from your plan switching paths.', { hold: 2.1 });
        await nextStep(v);
        await v.say('Step 6: send them to **your help page** instead.', { hold: 1.1 });
        const sup = await union(v, ['[id$="retention_offers-contact_support-support_url"]', '[id$="retention_offers-contact_support-headline"]'], '.field-warper');
        await v.focus(sup, { label: 'Your help page, your words', side: 'right', hold: 1.5 });
        await nextStep(v);
        await v.say('Step 7: review it all, then **save** once.', { hold: 1.2 });
        await v.hideCursor();
        await v.focus('.boxed-fullscreen-modal__footer-next', { label: 'Save cancellation flow', side: 'top', hold: 1.2 });
        await v.click('.boxed-fullscreen-modal__footer-next', { wait: 300 });
        await v.hideCursor();
        await v.scrollTo('.arraysubs-flow-summary-grid', { block: 'start', offset: -105 });
        await v.say('Saved. The new reason is **live**.', { hold: 1.0 });
        await v.focus(`.arraysubs-flow-summary-grid .arraysubs-flow-card >> nth=0 >> text=${NEW_REASON.label}`, { label: '9 reasons now', side: 'right', hold: 1.6 });
      },
    },

    // 4 ------------------------------------------------------------------ customer A cancels
    {
      kicker: 'My Account',
      title: 'They click *Cancel*',
      cardHold: 1.5,
      sub: 'A reason first, then a better option.',
      run: async (v) => {
        await asCustomer(v, S.A);
        await v.goto(viewUrl(S.A));
        await v.say(`${S.A.first} wants to cancel a **${S.A.product}**.`, { hold: 1.8 });
        await v.focus(await textRect(v, 'tr:has-text("Recurring Amount") td'), { label: `${money(S.A.recurring)} every month`, side: 'right', hold: 1.6 });
        await v.scrollTo('.subscription-skip-pause', { block: 'start' });
        await v.say('Gentler choices sit **above** the cancel link.', { hold: 1.6 });
        await v.focus(await union(v, ['.subscription-skip-pause__section:not(.subscription-skip-pause__section--cancel)']), { label: 'Change plan, skip or pause', side: 'left', hold: 1.6 });
        await v.focus('.subscription-skip-pause__section--cancel', { label: 'Cancel stays easy to find', side: 'left', hold: 1.6 });
        await v.click('.arraysubs-cancel-subscription');
        await v.zoom('#arraysubs-cancel-modal .arraysubs-modal__container', { scale: 1.5 });
        await v.say('First, a short **reason** survey.', { hold: 1.4 });
        await v.focus('#cancel-reason', { label: 'Why are you leaving?', side: 'bottom', hold: 1.4 });
        await v.select('#cancel-reason', 'too_expensive');
        await v.hideCursor();
        assertPro();
        await v.focus('#arraysubs-cancel-modal .arraysubs-modal__cancel-btn', { label: 'Keep is the big button', side: 'bottom', hold: 1.5 });
        await v.click('#arraysubs-cancel-modal .arraysubs-modal__confirm-btn');
        await modalView(v);
        await v.say('Then only the offers that fit **"Too expensive"**.');
        await v.hideCursor();
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer >> nth=0', { label: '20% off for 2 renewals', side: 'left' });
        v.poster();
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer >> nth=1', { label: 'Or a cheaper plan', side: 'left', hold: 1.4 });
        const plan = await v.frame.locator('#arraysubs-retention-modal .arraysubs-retention-offer__target-product option:not([value=""])').first().getAttribute('value');
        await v.select('#arraysubs-retention-modal .arraysubs-retention-offer__target-product', plan);
        await v.hideCursor();
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer__target-product', { label: 'Real plans, real prices', side: 'bottom', hold: 1.6 });
        await v.say('They can always keep it as is, or **still cancel**.', { hold: 1.6 });
        await v.focus('#arraysubs-retention-modal .arraysubs-modal__footer', { label: 'Never trapped', side: 'top', hold: 1.6 });
        await v.unfocus();
      },
    },

    // 5 ------------------------------------------------------------------ customer A accepts
    {
      kicker: 'My Account',
      title: 'One click *keeps* them',
      cardHold: 1.5,
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
        await v.say('They take the discount with **one click**.', { hold: 1.5 });
        await acceptOffer(v, '#arraysubs-retention-modal .arraysubs-retention-offer >> nth=0 >> .arraysubs-retention-offer__accept-btn', 'Saved. Nothing was cancelled.');
        await v.scrollTo(0);
        await v.say('The lower price shows on the subscription **right away**.');
        const row = 'tr:has-text("Recurring Amount")';
        await v.zoom(row, { scale: 1.6 });
        await v.focus(await textRect(v, `${row} td`), { label: '20% off, already applied', side: 'right', hold: 1.8 });
        await v.zoomOut();
        const id = await findMail(S.A.email, /retention discount/i, S.mailSince);
        if (!id) throw new Error('retention discount email not found in Mailpit');
        S.mailHtml = await buildMailPage(id);
        await v.goto('/__fvmail/retention-discount.html');
        v.captionPos('br');
        await v.say('An email confirms the **old and new** price.', { hold: 1.6 });
        await v.focus('.fv-mail__head', { label: 'Sent to the customer', side: 'bottom', hold: 1.4 });
        await v.scrollTo('.fv-mail__body table.td', { block: 'center' });
        await v.zoom('.fv-mail__body table.td', { scale: 1.45 });
        await v.focus('.fv-mail__body table.td', { label: 'Old price, new price, savings', side: 'right', hold: 1.8 });
        await v.zoomOut();
      },
    },

    // 6 ------------------------------------------------------------------ customer B pauses
    {
      kicker: 'My Account',
      title: 'Need a break? *Pause*',
      cardHold: 1.5,
      sub: 'A different reason brings a different offer.',
      run: async (v) => {
        await asCustomer(v, S.B);
        let before = '';
        // Lift the card straight onto B's offers window (the cancel window was shown for A).
        await v.act(async (f) => {
          await f.goto(`${SITE}${viewUrl(S.B)}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
          await v.settle();
          before = (await rowValue(v, 'Next Payment')).split(',')[0];
          await f.evaluate(() => document.querySelector('.subscription-skip-pause')?.scrollIntoView({ block: 'start' }));
          assertPro();
          await openOffersFast(v, 'not_using');
        });
        await modalView(v);
        await v.say(`${S.B.first} barely uses it: **"Not using it enough"**.`);
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer >> nth=0', { label: 'A pause first', side: 'left', hold: 1.4 });
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer >> nth=1', { label: 'Then a cheaper plan', side: 'left', hold: 1.4 });
        await v.unfocus();
        await v.zoomOut();
        await acceptOffer(v, '#arraysubs-retention-modal .arraysubs-retention-offer >> nth=0 >> .arraysubs-retention-offer__accept-btn', 'Paused. Nothing was cancelled.');
        await v.scrollTo(0);
        await v.say('Billing stops, and the next payment **moves out** by the pause.');
        await v.focus(await textRect(v, 'tr:has-text("Status") td'), { label: 'Paused', side: 'right', hold: 1.3 });
        await v.focus(await textRect(v, 'tr:has-text("Next Payment") td'), { label: `Was ${before}`, side: 'right', hold: 1.6 });
        await v.scrollTo('.subscription-skip-pause', { block: 'start' });
        await v.say('It **resumes by itself** on the scheduled date.', { hold: 1.6 });
        await v.focus('.subscription-skip-pause__section:has-text("Vacation Mode")', { label: 'Scheduled resume', side: 'left', hold: 1.5 });
        await v.unfocus();
      },
    },

    // 7 ------------------------------------------------------------------ customer C still leaves
    {
      kicker: 'My Account',
      title: 'Or they still *leave*',
      cardHold: 1.3,
      sub: 'Booked for the end of the paid period.',
      run: async (v) => {
        await asCustomer(v, S.C);
        await v.act(async (f) => {
          await f.goto(`${SITE}${viewUrl(S.C)}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
          await v.settle();
          await f.evaluate(() => document.querySelector('.subscription-skip-pause__section--cancel')?.scrollIntoView({ block: 'center' }));
        });
        await v.say(`${S.C.first} still wants to go.`, { hold: 1.0 });
        await v.click('.arraysubs-cancel-subscription');
        await v.zoom('#arraysubs-cancel-modal .arraysubs-modal__container', { scale: 1.5 });
        await v.hideCursor();
        await v.focus('#arraysubs-cancel-modal .arraysubs-modal__message', { label: 'Active until the paid period ends', side: 'bottom', hold: 1.6 });
        await v.select('#cancel-reason', 'other');
        await v.zoom('#arraysubs-cancel-modal .arraysubs-modal__container', { scale: 1.5, dur: 0.5 });
        await v.say('**Other** opens a box for their own words.', { hold: 1.0 });
        await v.type('#cancel-other-reason', C_WORDS, { cps: 26 });
        await v.hideCursor();
        await v.focus('#cancel-other-reason', { label: 'In their own words', side: 'bottom', hold: 1.2 });
        assertPro();
        await v.click('#arraysubs-cancel-modal .arraysubs-modal__confirm-btn');
        await modalView(v, '#arraysubs-retention-modal .arraysubs-modal__container', 1.4);
        await v.say('This reason brings **your help page** first.', { hold: 1.1 });
        await v.hideCursor();
        await v.focus('#arraysubs-retention-modal .arraysubs-retention-offer >> nth=0', { label: 'Opens your help page', side: 'left', hold: 1.4 });
        await v.unfocus();
        await v.zoomOut({ dur: 0.6 });
        await v.click('#arraysubs-retention-modal .arraysubs-retention__skip-btn', { nav: true });
        await v.hideCursor();
        await v.scrollTo(0);
        await v.say('The cancel is **booked** for the end of the paid period.', { hold: 1.2 });
        await v.focus(await textRect(v, 'tr:has-text("Status") td'), { label: 'Pending cancellation', side: 'right', hold: 1.3 });
        const cancelsOn = (await rowValue(v, 'Cancels On')).split(',')[0];
        await v.focus(await textRect(v, 'tr:has-text("Cancels On") td'), { label: `Last day: ${cancelsOn}`, side: 'right', hold: 1.5 });
        await v.scrollTo('.subscription-skip-pause', { block: 'start' });
        await v.say('Until then, they can **undo** it in one click.', { hold: 1.1 });
        await v.focus('.arraysubs-undo-cancellation', { label: 'Changed their mind?', side: 'right', hold: 1.4 });
        await v.unfocus();
      },
    },

    // 8 ------------------------------------------------------------------ admin: subscription history
    {
      kicker: 'Admin',
      title: 'The story on each *subscription*',
      cardHold: 1.3,
      sub: 'What they saw, what they took, and why.',
      before: async () => {
        // Re-recording this chapter alone after teardown undid C's cancel: book it again (no email
        // timing matters here; same reason and words as on camera).
        const c = subInfo([S.C.sub])[0];
        if (!c.waiting) {
          console.log(`  re-booking C's cancel: ${wp(['eval', `echo arraysubs_schedule_cancellation(${Number(S.C.sub)}, 'other', 'customer', ['reason'=>'other','other_reason'=>'${C_WORDS.replace(/'/g, "\\'")}']) ? 'ok' : 'failed';`])}`);
        }
      },
      run: async (v) => {
        await asAdmin(v);
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${S.A.sub}`);
        await v.say(`Back in admin: ${S.A.first}'s subscription, **kept**.`, { hold: 1.1 });
        const disc = '.arraysubs-sd__kv-row:has-text("Retention discount")';
        await v.zoom(await union(v, ['.arraysubs-sd__stats', disc]), { scale: 1.4 });
        await v.focus('.arraysubs-sd__stat >> nth=0', { label: `${money(S.A.recurring * 0.8)} instead of ${money(S.A.recurring)}`, side: 'right', hold: 1.6 });
        await v.focus(disc, { label: '2 renewals left', side: 'right', hold: 1.2 });
        await v.zoomOut();
        const panel = 'section.arraysubs-sd__panel:has-text("Retention History")';
        await v.scrollTo(panel, { block: 'start', offset: -110 });
        await v.say('**Retention History** keeps the offers shown and the one taken.', { hold: 1.2 });
        await v.zoom(panel, { scale: 1.45 });
        await v.focus(`${panel} .arraysubs-sd__kv`, { label: 'Shown and accepted, with dates', side: 'bottom', hold: 1.6 });
        await v.zoomOut();
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${S.C.sub}`);
        const sc = 'section.arraysubs-sd__panel:has-text("Scheduled Cancellation")';
        await v.scrollTo(sc, { block: 'start', offset: -110 });
        await v.say(`${S.C.first}'s **reason** is saved, in their own words.`, { hold: 1.2 });
        await v.zoom(sc, { scale: 1.3 });
        await v.focus(sc, { label: 'Reason, words and offers shown', side: 'top', hold: 1.7 });
        await v.zoomOut();
        await v.scrollTo(0);
        await v.say('You can **undo** a booked cancel too.', { hold: 1.0 });
        await v.click('.arraysubs-sd__hero-actions button:has-text("Undo Scheduled Cancellation")');
        await v.hideCursor();
        await v.focus('.arraysubs-modal:has(.arraysubs-confirm-dialog)', { label: 'Renewals simply continue', side: 'bottom', hold: 1.4 });
        await v.click('.arraysubs-confirm-dialog__actions button.button-primary', { wait: 400 });
        await v.hideCursor();
        await v.focus('.arraysubs-sd__hero-chips', { label: 'Active again', side: 'right', hold: 1.5 });
      },
    },

    // 9 ------------------------------------------------------------------ retention report
    {
      kicker: 'Reports',
      title: 'Know why people *leave*',
      cardHold: 1.3,
      sub: 'The Retention report in WooCommerce Analytics.',
      run: async (v) => {
        await asAdmin(v);
        await v.act(async (f) => {
          await f.goto(`${SITE}${REPORT_URL}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
          await f.waitForSelector('.arraysubs-retention-cohort__table', { timeout: 30000 });
          // The data-quality notice only describes this demo store's backfilled history.
          await f.addStyleTag({ content: '.arraysubs-retention-quality{display:none!important}' });
        });
        await v.say('The **Retention** report lives in WooCommerce Analytics.', { hold: 1.1 });
        await v.focus('#adminmenu .wp-submenu a:text-is("Retention"):visible', { label: 'Analytics > Retention', side: 'right', hold: 1.2 });
        await v.say('See **who stays**, month by month, from signup.', { hold: 1.1 });
        await v.focus('.arraysubs-retention-report-card--wide', { label: 'Retention by signup month', dim: true, side: 'top', hold: 1.6 });
        await v.scrollTo('#arraysubs-retention-card-rb', { block: 'start', offset: -90 });
        const pct = 'section:has(#arraysubs-retention-card-rb) .arraysubs-retention-mode__button:has-text("Percent")';
        if (await v.frame.locator(pct).count()) await v.click(pct, { wait: 300 });
        await v.hideCursor();
        await v.say('Churn by **choice** or **failed payment**, and the **top reasons**.', { hold: 1.3 });
        await v.focus(await union(v, ['#arraysubs-retention-card-rb'], 'section'), { label: 'Voluntary vs involuntary', side: 'bottom', hold: 1.5 });
        await v.focus(await union(v, ['#arraysubs-retention-card-rd'], 'section'), { label: 'Top 10 reasons', side: 'bottom', hold: 1.4 });
        await v.scrollTo('#arraysubs-retention-activity-title', { block: 'start', offset: -90 });
        await v.say('Every offer, cancel and undo is **logged**.', { hold: 1.1 });
        // C's booked cancel and its undo are the newest rows unless another session logged since.
        const cRows = await v.eval((first) => {
          const trs = [...document.querySelectorAll('.arraysubs-retention-logs__row')].slice(0, 2);
          if (trs.length < 2 || !trs.every((tr) => tr.innerText.includes(first))) return null;
          const a = trs[0].getBoundingClientRect(), b = trs[1].getBoundingClientRect();
          return { x: a.left, y: a.top, w: a.width, h: b.bottom - a.top };
        }, S.C.first);
        if (cRows) await v.focus(cRows, { label: 'Booked, then undone', side: 'top', hold: 1.5 });
        await v.select('#arraysubs-retention-event-filter', 'offer_accepted');
        await v.hideCursor();
        const rows = (n) => v.eval((n) => {
          const r = [...document.querySelectorAll('.arraysubs-retention-logs__subscription-link')].slice(0, n).map((a) => a.closest('tr').getBoundingClientRect());
          return { x: r[0].left, y: r[0].top, w: r[0].width, h: r[r.length - 1].bottom - r[0].top };
        }, n);
        await v.zoom(await rows(3), { pad: 24 });
        await v.focus(await rows(2), { label: 'Just now: a pause and a discount', side: 'bottom', hold: 1.6 });
        await v.zoomOut();
        await v.unfocus();
      },
    },
  ],
};
