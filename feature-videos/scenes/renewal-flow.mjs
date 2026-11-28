// Renewal flow tour (Free). Story: set the timing in Renewal Flow -> customer emails -> team
// alerts and cancellation emails -> a renewal invoice goes out on the 3-day lead -> the
// customer pays it from My Account -> admin confirms, the date moves one cycle -> what a
// declined card looks like for the customer -> failures queue + Home billing health.
//
// Chapters 5-7 use one bank-transfer subscription from the 922-982 customer block
// (default 29790, Roman Kennedy). Its renewal invoice must already exist, created by the real
// scheduler on the store's 3-day lead (see notes/renewal-flow.md for how it was prepared).
// Chapters 6 and 7 change it (pay, then complete): re-record 6,7 together only with a fresh
// subscription prepared the same way (FV_RF_SUB=<id>). Date captions are read from it.
// Every store change is logged in notes/renewal-flow.md.
import { wpJson } from '../lib/director.mjs';

const SUB = Number(process.env.FV_RF_SUB || 29790);
const ADMIN = (p) => `/wp-admin/admin.php?page=arraysubs-mainadmin#/${p}`;
const WCMAIL = (s) => `/wp-admin/admin.php?page=wc-settings&tab=email&section=${s}`;
// Read-only failure example (Stripe, on hold, insufficient funds). Customer 65 is only viewed.
const FAIL_SUB = 25905;
const FAIL_CUSTOMER = 65;

function subInfo() {
  return wpJson(`$id=${SUB}; clean_post_cache($id); wp_cache_delete($id,'post_meta'); $ids=get_post_meta($id,'_order_ids',true); if(is_string($ids)) $ids=json_decode($ids,true); $ids=(array)$ids; echo json_encode(['cust'=>(int)get_post_meta($id,'_customer_id',true),'next'=>get_post_meta($id,'_next_payment_date',true),'pend'=>(int)get_post_meta($id,'_pending_renewal_order_id',true),'last'=>(int)end($ids),'status'=>get_post_status($id)]);`);
}
// Display dates for captions: the due date and the date one billing cycle later (site timezone).
function dueDates() {
  return wpJson(`$id=${SUB}; $n=get_post_meta($id,'_next_payment_date',true); $t=strtotime($n.' UTC'); $i=max(1,(int)get_post_meta($id,'_billing_interval',true)); $p=get_post_meta($id,'_billing_period',true)?:'month'; echo json_encode(['due'=>wp_date('F j',$t),'after'=>wp_date('F j',strtotime("+{$i} {$p}",$t))]);`);
}
function orderInfo(id) {
  return wpJson(`$o=wc_get_order(${Number(id)}); echo json_encode(['status'=>$o?$o->get_status():'','key'=>$o?$o->get_order_key():'']);`);
}

// Union rect of several targets (app coordinates), for one ring around a group.
async function union(v, targets, pad = 0) {
  let x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity;
  for (const t of targets) {
    const r = await v.appRect(t);
    x1 = Math.min(x1, r.x); y1 = Math.min(y1, r.y); x2 = Math.max(x2, r.x + r.w); y2 = Math.max(y2, r.y + r.h);
  }
  return { x: x1 - pad, y: y1 - pad, w: x2 - x1 + pad * 2, h: y2 - y1 + pad * 2 };
}

// Rect of an element inside the WooCommerce email preview iframe, in app coordinates.
// `sel` is a Playwright selector inside the preview (css or text=).
async function previewRect(v, sel) {
  const frameEl = v.frame.locator('iframe[src*="preview_woocommerce_mail"]').first();
  const outer = await frameEl.evaluate((el) => { const r = el.getBoundingClientRect(); return { x: r.x, y: r.y }; });
  const inner = await frameEl.contentFrame().locator(sel).first().evaluate((el) => { const r = el.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; });
  return { x: outer.x + inner.x, y: outer.y + inner.y, w: inner.w, h: inner.h };
}
async function previewUnion(v, sels, pad = 0) {
  const rs = [];
  for (const s of sels) rs.push(await previewRect(v, s));
  return union(v, rs, pad);
}

// Caption first, then the ring, so text and ring change together. Hold = reading time.
const readT = (t) => Math.min(3.8, Math.max(1.7, 0.95 + String(t).replace(/\*\*/g, '').length * 0.032));
async function point(v, target, text, o = {}) {
  const { zoom, ...fo } = o;
  if (v.ring) await v.unfocus({ dur: 0.15 });
  if (text) await v.say(text, { hold: 0 });
  // Optional camera move that starts together with the new caption: zoom: [target, { scale, pad }]
  if (zoom) await v.zoom(zoom[0], { scale: zoom[1].scale ?? 1.6, pad: zoom[1].pad ?? 60 });
  await v.focus(target, { ...fo, hold: fo.hold ?? (text ? readT(text) : undefined) });
}
// Caption + camera move together (zoom in or pan), held for reading time.
async function look(v, target, text, o = {}) {
  if (v.ring) await v.unfocus({ dur: 0.15 });
  if (text) await v.say(text, { hold: 0 });
  await v.zoom(target, { scale: o.scale ?? 1.6, pad: o.pad ?? 60, hold: o.hold ?? (text ? readT(text) : 0) });
}
// The stage page must never scroll (a Playwright scroll-into-view while zoomed can shift it,
// and the captions with it). Reset it, cheaply, wherever that could have happened.
async function unscroll(v) {
  await v.page.evaluate(() => { window.scrollTo(0, 0); document.documentElement.scrollLeft = 0; document.body.scrollLeft = 0; }).catch(() => {});
}
// Park the pointer off the content (no hover tooltips) and hide the drawn cursor.
async function park(v) {
  await unscroll(v);
  await v.page.mouse.move(1432, 400).catch(() => {});
  await v.hideCursor();
}

// Concurrent recording agents mint sessions for the same users; the session_tokens user meta
// is read-modify-write, so a fresh token can be dropped by another writer. Mint our own
// cookies, wait, confirm they still validate, then use them.
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
async function login(v, user) {
  await v.page.mouse.move(1432, 400).catch(() => {});
  await v.as(user);
  const id = Number(user);
  if (!(id > 0)) return;
  for (let i = 0; i < 5; i++) {
    const d = wpJson(`$u=${id}; $e=time()+2*DAY_IN_SECONDS; echo json_encode(['hash'=>COOKIEHASH,'auth'=>wp_generate_auth_cookie($u,$e,'auth'),'li'=>wp_generate_auth_cookie($u,$e,'logged_in')]);`);
    await sleep(2500);
    const ok = wpJson(`echo json_encode(['ok'=>(int) wp_validate_auth_cookie('${d.li}','logged_in')]);`).ok;
    if (ok !== id) { console.log(`  [login] token for ${id} dropped, retrying`); continue; }
    const base = { domain: 'localhost', httpOnly: true, sameSite: 'Lax', secure: false, expires: Math.floor(Date.now() / 1000) + 86400 };
    await v.context.addCookies([
      { ...base, name: `wordpress_logged_in_${d.hash}`, value: d.li, path: '/' },
      { ...base, name: `wordpress_${d.hash}`, value: d.auth, path: '/wp-admin' },
      { ...base, name: `wordpress_${d.hash}`, value: d.auth, path: '/wp-content/plugins' },
    ]);
    return;
  }
  throw new Error(`could not get a stable login for user ${id}`);
}

// Off-camera display tidy-up (nothing is saved): test-page links in the storefront menu, a
// third-party PDF-invoice notice box and the localhost customer IP on the order editor.
async function tidy(f) {
  await f.evaluate(() => {
    document.querySelectorAll('.wp-block-navigation-item').forEach((li) => {
      if (/^(Checkout Classic|Cart Page)$/.test(li.textContent.trim())) li.style.display = 'none';
    });
    const pdf = document.getElementById('wpo_wcpdf-data-input-box');
    if (pdf) pdf.style.display = 'none';
    const ip = document.querySelector('.woocommerce-order-data__meta .woocommerce-Order-customerIP');
    if (ip && ip.parentElement) {
      const meta = ip.parentElement;
      ip.remove();
      meta.innerHTML = meta.innerHTML.replace(/\s*Customer IP:\s*/, ' ');
    }
  }).catch(() => {});
}

// The store is under heavy load (many recorders at once): REST-driven screens can take far
// longer than the harness settle window. open()/clickTo() wait for a ready element off-camera,
// so the crossfade lands on the finished screen, never on a spinner.
const SITE = 'http://localhost:10013';
const LONG = 150000;
async function open(v, url, ready, { prep } = {}) {
  const full = url.startsWith('http') ? url : `${SITE}${url}`;
  await v.say(null);
  await v.zoomOut();
  await v.act(async (f) => {
    await f.goto(full, { waitUntil: 'domcontentloaded', timeout: LONG });
    for (const r of [].concat(ready || [])) await f.locator(r).first().waitFor({ state: 'visible', timeout: LONG });
    await tidy(f);
    if (prep) await prep(f);
  });
  await v.hold(0.4, { note: 'open' });
}
// `href: true` follows the link's href in the same frame instead of a real click: the email
// settings links open in a new tab (target=_blank, re-added by React), and a second tab pauses
// the stage's animation frames, which hangs the recording.
async function clickTo(v, target, ready, { nav = false, href = false } = {}) {
  await v.say(null);
  const url = href ? await v.loc(target).evaluate((a) => a.href) : null;
  const p = await v.moveTo(target);
  if (v.ring) await v.unfocus({ dur: 0.15 });
  await v.animate(0.1, (t) => { v.cursor.press = t; });
  await v.act(async (f, page) => {
    if (url) {
      await f.goto(url, { waitUntil: 'domcontentloaded', timeout: LONG });
    } else {
      const navP = nav ? f.waitForNavigation({ timeout: LONG }).catch(() => {}) : null;
      await page.mouse.click(p.x, p.y);
      if (navP) await navP;
    }
    for (const extra of v.context.pages()) if (extra !== v.page) await extra.close().catch(() => {});
    for (const r of [].concat(ready || [])) await f.locator(r).first().waitFor({ state: 'visible', timeout: LONG });
    await tidy(f);
  });
  v.cursor.press = 0;
  await v.hold(0.35, { still: false });
}
// Scroll the document so `sel` sits `top` px below the viewport top (inside an act).
const prescroll = (sel, top) => async (f) => {
  await f.evaluate(({ sel, top }) => {
    const el = document.querySelector(sel);
    if (el) window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - top);
  }, { sel, top });
};

// Wait until a lazily laid-out page stops moving (charts and cards above the target load late).
const settleLayout = (sel) => async (f) => {
  let last = '', since = Date.now();
  const t0 = Date.now();
  while (Date.now() - t0 < 40000) {
    const key = await f.evaluate((s) => { const e = document.querySelector(s); return `${e ? Math.round(e.getBoundingClientRect().top + window.scrollY) : -1}|${document.scrollingElement.scrollHeight}`; }, sel);
    if (key !== last) { last = key; since = Date.now(); } else if (Date.now() - since > 2000) break;
    await new Promise((r) => setTimeout(r, 250));
  }
};

const state = { sub: null, order: 0, dates: { due: '', after: '' } };

export default {
  slug: 'renewal-flow',
  tier: 'Free',
  intro: { title: 'The renewal *flow*', sub: 'Invoices, reminders, retries and a grace period, set once and run for you.' },
  outro: { title: 'Stop writing *reminders*.', sub: 'Reminders, invoices, retries and a grace period, in the free plugin.', url: 'subscriptionplugin.pro' },
  setup: async () => {
    const r = wpJson(`$s=get_option('arraysubs_settings'); echo json_encode(['renewals'=>$s['renewals']??null,'emails'=>['up'=>$s['emails']['renewal_upcoming']['days_before']??null,'exp'=>$s['emails']['expiring_soon']['days_before']??null,'rate'=>$s['emails']['renewal_reminder_rate_limit']??null],'retry'=>$s['payment_retry']??null]);`);
    console.log('  settings at record time:', JSON.stringify(r));
    state.sub = subInfo();
    state.dates = dueDates();
    console.log(`  subscription ${SUB}:`, JSON.stringify(state.sub), JSON.stringify(state.dates));
  },
  chapters: [
    // 1 ------------------------------------------------------------------ timing
    {
      kicker: 'Settings',
      title: 'Set the *timing* once',
      sub: 'Renewal Flow: grace period and reminders',
      run: async (v) => {
        await login(v, 1);
        await open(v, ADMIN('renewal-flow/grace-period'), '.arraysubs-settings-left-grid');
        await point(v, await union(v, ['a.arraysubs-tab-link >> nth=0', 'a.arraysubs-tab-link >> nth=4']), '**Renewal Flow** sets the timing for every renewal.', { label: 'Five tabs, one flow', side: 'right' });
        await point(v, '.arraysubs-settings-left-grid >> nth=0', 'Each renewal invoice is created **3 days** before the due date.', { label: 'Invoice lead time', side: 'right', dim: true, pad: 4 });
        await point(v, '.arraysubs-settings-left-grid >> nth=1', 'If a payment is missed, it stays **active for 3 days**.', { label: 'Grace period', side: 'right', dim: true, pad: 4 });
        await v.say('Then it waits **7 days on hold** before it cancels.');
        await look(v, await union(v, ['.arraysubs-settings-left-grid >> nth=1', '.arraysubs-alert-warning']), 'Customers keep access while active. It pauses during the hold.', { scale: 1.7 });
        await v.zoomOut();
        await clickTo(v, 'a.arraysubs-tab-link:has-text("Email Reminders")', 'label:has-text("Renewal Reminder (Days Before)")');
        await park(v);
        await point(v, '.arraysubs-settings-left-grid >> nth=0', 'A **reminder email** goes out 3 days before each renewal.', { label: 'Days before', side: 'right', dim: true, pad: 4 });
        await v.say('Plans with an end date get an **expiring soon** notice 7 days ahead.');
        const cap = await union(v, ['input.arraysubs-fb-input >> nth=2', '.arraysubs-alert:has-text("Caps how many")'], 4);
        cap.y -= 30; cap.h += 30; // include the field label above the input
        await point(v, cap, 'Busy day? Cap reminders per minute. Extras wait, never dropped.', { label: '0 = no limit', side: 'right', dim: true, pad: 4 });
      },
    },
    // 2 ------------------------------------------------------------------ retries
    {
      kicker: 'Settings',
      title: 'Retry *declined* cards',
      sub: 'Renewal Flow: Renewal Settings',
      run: async (v) => {
        const card = '.arraysubs-fb-card:has-text("Payment Retries")';
        await login(v, 1);
        await open(v, ADMIN('renewal-flow/settings'), `${card} button.arraysubs-fb-select-control`);
        await v.scrollTo(card, { block: 'start', offset: -90 });
        await point(v, `${card} .arraysubs-settings-left-grid`, 'A declined card renewal is **retried up to 3 times**.', { label: 'Up to 3 retries', side: 'right', dim: true, pad: 6 });
        await v.unfocus();
        await v.click(`${card} button.arraysubs-fb-select-control >> nth=0`, { hold: 0.2 });
        await look(v, await union(v, [`${card} .arraysubs-settings-left-grid`, `${card} .arraysubs-alert >> nth=0`]), 'Choose the gap before each try: 6 hours to 2 days.', { scale: 1.6, pad: 40 });
        // Close the menu without a Playwright locator click: that would scroll the zoomed stage.
        await v.act(async (f, page) => {
          await page.keyboard.press('Escape');
          await f.evaluate((sel) => {
            const h = document.querySelector(sel);
            for (const t of ['mousedown', 'mouseup', 'click']) (h || document.body).dispatchEvent(new MouseEvent(t, { bubbles: true }));
            if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
          }, `.arraysubs-fb-card h3`);
        });
        await unscroll(v);
        await park(v);
        await point(v, '.arraysubs-alert:has-text("not retried automatically")', 'Expired or blocked cards skip retries. The customer updates the card.', { dim: true, pad: 6, zoom: ['.arraysubs-alert:has-text("not retried automatically")', { scale: 1.6, pad: 120 }] });
        await point(v, '.arraysubs-alert:has-text("Applies to Stripe and Mollie")', 'Retries run for **Stripe** and **Mollie**. PayPal and Paddle retry on their side.', { dim: true, pad: 6, zoom: ['.arraysubs-alert:has-text("Applies to Stripe and Mollie")', { scale: 1.6, pad: 120 }] });
        await v.unfocus();
        await v.zoomOut();
        await v.scrollTo('.arraysubs-fb-card:has-text("Renewal Performance")', { block: 'start', offset: -90 });
        await point(v, await union(v, ['.arraysubs-fb-card:has-text("Renewal Performance") .arraysubs-settings-left-grid', '.arraysubs-alert:has-text("spread deterministically")']), 'Big billing days are **spread over hours**. Customer dates never change.', { label: 'Big billing days', side: 'right', dim: true, pad: 10 });
      },
    },
    // 3 ------------------------------------------------------------------ customer emails
    {
      kicker: 'Emails',
      title: 'Every email, *ready*',
      sub: 'Settings: Emails',
      run: async (v) => {
        const rpf = '.arraysubs-email-settings__card:has(h3:text-is("Renewal Payment Failed"))';
        await login(v, 1);
        await open(v, ADMIN('settings/emails'), ['#email-group-renewals-payments', '#email-group-admin-notifications'], { prep: async (f) => { await f.evaluate(() => document.querySelectorAll('a.arraysubs-email-settings__edit').forEach((a) => a.removeAttribute('target'))); } });
        await v.say('Each subscription email has a card: when it goes out and to whom.');
        await v.scrollTo('#email-group-renewals-payments', { block: 'start', offset: -100 });
        await point(v, 'section.arraysubs-email-settings__group:has(#email-group-renewals-payments)', 'Reminders, invoices, receipts and failure notices go to the customer.', { label: 'Renewals & payments', side: 'bottom', dim: true });
        await point(v, rpf, 'Open any email to change it.', { label: 'Open the email', side: 'bottom' });
        await clickTo(v, `${rpf} a.arraysubs-email-settings__edit`, ['.arraysubs-placeholders-panel', 'iframe[src*="preview_woocommerce_mail"]'], { href: true });
        await v.frame.locator('iframe[src*="preview_woocommerce_mail"]').first().contentFrame().locator('text=Pay Now').first().waitFor({ state: 'attached', timeout: LONG });
        await park(v);
        await point(v, '.arraysubs-placeholders-panel', 'Placeholders fill in the name, amount and payment links.', { label: 'Placeholders', side: 'top', dim: true });
        await v.scrollTo('tr:has(#woocommerce_arraysubs_payment_failed_enabled)', { block: 'center' });
        await point(v, await union(v, ['tr:has(#woocommerce_arraysubs_payment_failed_enabled)', 'tr:has(#woocommerce_arraysubs_payment_failed_heading)'], 4), 'Switch it off, or rewrite the subject and heading.', { label: 'On, off and wording', side: 'right', dim: true });
        await v.scrollTo('h2:has-text("Email preview")', { block: 'start' });
        await point(v, await union(v, ['button[title="Email preview on desktop"]', 'button:has-text("Send a test email")'], 4), 'Preview it on desktop or mobile, then send yourself a test.', { label: 'Preview and test', side: 'left' });
        await v.unfocus();
        await v.scrollBy(380);
        const pay = await previewRect(v, 'text=Pay Now');
        await point(v, pay, '**Pay Now** opens the payment page for the unpaid renewal.', { label: 'Straight to payment', side: 'right', pad: 10, zoom: [pay, { scale: 1.5, pad: 160 }] });
        await v.unfocus();
        await v.zoomOut();
      },
    },
    // 4 ------------------------------------------------------------------ team alerts + cancellations
    {
      kicker: 'Emails',
      title: 'Your team, in the *loop*',
      sub: 'Admin alerts and cancellation emails',
      run: async (v) => {
        const field = '#woocommerce_arraysubs_admin_payment_failed_recipient';
        await login(v, 1);
        await open(v, ADMIN('settings/emails'), ['#email-group-admin-notifications'], {
          prep: async (f) => {
            await f.evaluate(() => document.querySelectorAll('a.arraysubs-email-settings__edit').forEach((a) => a.removeAttribute('target')));
            await prescroll('#email-group-admin-notifications', 130)(f);
          },
        });
        const adminCard = (t) => `.arraysubs-email-settings__card:has(h3:text-is("${t}"))`;
        await point(v, await union(v, [adminCard('New Subscription (Admin)'), adminCard('Subscription Cancelled (Admin)')]), 'Your team gets its own alerts for failed payments and cancellations.', { label: 'Admin alerts', side: 'bottom', dim: true });
        await clickTo(v, '.arraysubs-email-settings__card:has(h3:text-is("Payment Failed (Admin)")) a.arraysubs-email-settings__edit', [field, 'iframe[src*="preview_woocommerce_mail"]'], { href: true });
        await v.frame.locator('iframe[src*="preview_woocommerce_mail"]').first().contentFrame().locator('text=View Subscription').first().waitFor({ state: 'attached', timeout: LONG });
        await park(v);
        await v.scrollTo(`tr:has(${field})`, { block: 'center' });
        await point(v, `tr:has(${field})`, 'Choose who receives it, like your billing inbox.', { label: 'Recipient(s)', side: 'right', dim: true, pad: 6 });
        await v.unfocus();
        await v.type(field, 'billing@brightside.market', { hold: 0.8 });
        await park(v);
        await v.scrollTo('h2:has-text("Email preview")', { block: 'start' });
        await v.scrollBy(330);
        const alert = await previewUnion(v, ['table.td', 'text=View Subscription'], 6);
        await point(v, alert, 'It shows the customer, the amount and a subscription link.', { label: 'Who, what, where', side: 'right', zoom: [alert, { scale: 1.45, pad: 50 }] });
        await v.unfocus();
        // Leave the field as it was (nothing was saved).
        await v.eval((sel) => { const i = document.querySelector(sel); if (i) i.value = ''; window.onbeforeunload = null; if (window.jQuery) window.jQuery(window).off('beforeunload'); }, field);
        await open(v, WCMAIL('arraysubs_subscription_pending_cancellation'), 'iframe[src*="preview_woocommerce_mail"]', {
          prep: async (f) => {
            await f.locator('iframe[src*="preview_woocommerce_mail"]').first().contentFrame().locator('text=Manage subscription').first().waitFor({ state: 'attached', timeout: LONG });
          },
        });
        await v.say('When a customer cancels, they get it in writing.');
        await point(v, await union(v, ['button.arraysubs-placeholder-btn:text-is("{scheduled_cancel_date}")', 'button.arraysubs-placeholder-btn:text-is("{cancellation_reason}")'], 4), 'Real emails fill in the end date and the reason.', { label: 'Date and reason', side: 'right', dim: true });
        await v.scrollTo('h2:has-text("Email preview")', { block: 'start' });
        await v.scrollBy(300);
        const keep = await previewRect(v, 'p:has-text("continue to have access until then")');
        await point(v, keep, 'Access lasts until the paid period ends. No more charges after that.', { label: 'Access until the end', side: 'right', zoom: [keep, { scale: 1.5, pad: 120 }] });
        const undo = await previewUnion(v, ['p:has-text("Changed your mind?")', 'text=Manage subscription'], 6);
        await point(v, undo, 'Changed their mind? They can undo it from My Account.', { label: 'Undo before the date', side: 'right', zoom: [undo, { scale: 1.5, pad: 120 }] });
        await v.unfocus();
        await v.zoomOut();
      },
    },
    // 5 ------------------------------------------------------------------ invoice
    {
      kicker: 'Admin',
      title: 'An invoice goes *out*',
      sub: 'A bank-transfer membership, 3 days before it is due',
      before: async () => {
        state.dates = dueDates();
        state.sub = subInfo();
        if (!state.sub.pend) throw new Error(`subscription ${SUB} has no renewal invoice yet: let the scheduler create it (notes/renewal-flow.md)`);
        state.order = state.sub.pend;
      },
      run: async (v) => {
        await login(v, 1);
        await open(v, ADMIN(`subscriptions/detail/${SUB}`), ['.arraysubs-sd__stat >> nth=1', '.arraysubs-payment-timeline .timeline-event']);
        await point(v, '.arraysubs-sd__stat >> nth=1', `This member pays by bank transfer. The next payment is due **${state.dates.due}**.`, { label: `Due ${state.dates.due}`, side: 'bottom', dim: true });
        await v.scrollTo('table.arraysubs-sd__table', { block: 'center' });
        await point(v, 'table.arraysubs-sd__table tbody tr >> nth=0', 'Three days ahead, ArraySubs created the **renewal invoice**.', { label: 'New renewal order', side: 'right', dim: true });
        await look(v, 'table.arraysubs-sd__table tbody tr >> nth=0', 'It stays **Pending payment** until the customer pays.', { scale: 1.6 });
        await v.zoomOut();
        await v.scrollTo('.arraysubs-payment-timeline', { block: 'start', offset: -150 });
        const ev = (n) => `.arraysubs-payment-timeline .timeline-event >> nth=${n}`;
        await point(v, await union(v, [ev(0), ev(2)]), 'The invoice was emailed with it. The timeline logs each step.', { label: 'Invoice emailed', side: 'left', dim: true });
        await v.scrollTo(ev(4), { block: 'center' });
        await point(v, ev(4), 'The **renewal reminder** went out that morning too.', { label: 'Reminder sent', side: 'left', dim: true });
      },
    },
    // 6 ------------------------------------------------------------------ customer pays
    {
      kicker: 'My Account',
      title: 'The customer *pays*',
      sub: 'My Account, then the payment page',
      before: async () => {
        state.sub = subInfo();
        state.order = state.sub.pend || state.order;
        const o = orderInfo(state.order);
        if (o.status !== 'pending') throw new Error(`renewal order ${state.order} is ${o.status}, not pending: use a fresh FV_RF_SUB`);
      },
      run: async (v) => {
        await login(v, state.sub.cust);
        await open(v, `/my-account/as-view-subscription/${SUB}/`, 'table.shop_table_responsive a:text-is("Pay")');
        await v.scrollTo('h2:has-text("Subscription #")', { block: 'start' });
        await point(v, 'table.order_details tr:has-text("Next Payment")', 'In **My Account**, the member sees the next payment date.', { label: 'Next payment', side: 'right', dim: true });
        await v.scrollTo('table.shop_table_responsive', { block: 'center' });
        await point(v, 'table.shop_table_responsive tbody tr >> nth=0', 'The open renewal invoice has a **Pay** button.', { label: 'Pending payment', side: 'right', dim: true });
        await clickTo(v, 'table.shop_table_responsive a:text-is("Pay")', ['form#order_review table.shop_table', '#place_order'], { nav: true });
        await park(v);
        await point(v, 'form#order_review table.shop_table', 'The payment page shows the renewal and the amount due.', { label: 'Amount due', side: 'right' });
        await point(v, 'li.payment_method_bacs', 'This member pays by **bank transfer**.', { label: 'Bank transfer', side: 'right', dim: true });
        await clickTo(v, '#place_order', '.wc-block-order-confirmation-status', { nav: true });
        await park(v);
        await point(v, '.wc-block-order-confirmation-status', 'Done. The store marks it paid once the transfer arrives.', { label: 'Order received', side: 'right' });
      },
    },
    // 7 ------------------------------------------------------------------ admin confirms
    {
      kicker: 'Admin',
      title: 'Paid. The date *moves on*',
      sub: 'Order, then subscription detail',
      before: async () => {
        state.sub = subInfo();
        state.order = state.sub.pend || state.sub.last;
        const o = orderInfo(state.order);
        if (o.status !== 'on-hold') throw new Error(`renewal order ${state.order} is ${o.status}, expected on-hold`);
        state.dates = dueDates();
      },
      run: async (v) => {
        await login(v, 1);
        await open(v, `/wp-admin/admin.php?page=wc-orders&action=edit&id=${state.order}`, '#select2-order_status-container');
        await point(v, '#select2-order_status-container', 'The transfer arrived, so the admin marks the order **Completed**.', { dim: true, pad: 6 });
        await v.click('#select2-order_status-container', { hold: 0.5 });
        await v.click('.select2-results__option:text-is("Completed")', { hold: 0.5 });
        await clickTo(v, 'button.save_order', '#select2-order_status-container', { nav: true });
        await park(v);
        await open(v, ADMIN(`subscriptions/detail/${SUB}`), ['.arraysubs-sd__stats', '.arraysubs-payment-timeline .timeline-event']);
        await v.say(`The next payment moves to **${state.dates.after}**, one cycle after the due date.`, { hold: 0 });
        await v.zoom('.arraysubs-sd__stats', { scale: 1.45 });
        await v.focus('.arraysubs-sd__stat >> nth=1', { label: 'Moved one cycle', side: 'bottom', hold: 2.6 });
        await v.unfocus();
        await v.zoomOut();
        await v.scrollTo('.arraysubs-payment-timeline', { block: 'start', offset: -150 });
        await point(v, await union(v, ['.arraysubs-payment-timeline .timeline-event >> nth=0', '.arraysubs-payment-timeline .timeline-event >> nth=1']), 'A receipt email goes out, and the timeline shows every step.', { label: 'Paid + receipt', side: 'left', dim: true });
      },
    },
    // 8 ------------------------------------------------------------------ declined card
    {
      kicker: 'My Account',
      title: 'When a card is *declined*',
      sub: 'A card renewal that failed, seen by the customer',
      run: async (v) => {
        await login(v, FAIL_CUSTOMER);
        await open(v, `/my-account/as-view-subscription/${FAIL_SUB}/`, ['.arraysubs-retry-payment', 'ul.subscription-notes-list']);
        await v.scrollTo('h2:has-text("Subscription #")', { block: 'start' });
        await point(v, 'table.order_details .subscription-status-badge', 'Here a card renewal **failed**, and it was not paid in time.', { label: 'On hold', side: 'right', dim: true, pad: 8 });
        await v.unfocus();
        await v.scrollTo('ul.subscription-notes-list', { block: 'start' });
        await point(v, '.subscription-note:has-text("Payment failed for")', 'The note gives the reason in plain words: **insufficient funds**.', { dim: true, zoom: ['.subscription-note:has-text("Payment failed for")', { scale: 1.6, pad: 140 }] });
        await point(v, '.subscription-note:has-text("automatically put on hold")', 'After the 3 active days, the subscription went **on hold**.', { dim: true, zoom: ['.subscription-note:has-text("automatically put on hold")', { scale: 1.6, pad: 140 }] });
        await v.unfocus();
        await v.zoomOut();
        await v.scrollTo('.arraysubs-retry-payment', { block: 'center' });
        await point(v, await union(v, ['.arraysubs-retry-payment', 'table.shop_table_responsive a:text-is("Pay")'], 6), 'The customer can fix it here: **Retry Payment** or **Pay**.', { label: 'Fix it here', side: 'right', dim: true });
        await v.say(null);
        await v.click('.arraysubs-retry-payment', { hold: 0.3 });
        await park(v);
        await v.say('Before a retry, ArraySubs checks they were **not already charged**.', { hold: 0 });
        await v.zoom('.arraysubs-portal-confirm .arraysubs-modal__content', { scale: 1.8 });
        await v.focus('.arraysubs-portal-confirm .arraysubs-modal__content', { label: 'Checked first', side: 'right', pad: 4, hold: 3.0 });
        await v.unfocus();
        await v.zoomOut();
        await v.say(null);
        await v.click('.arraysubs-portal-confirm__cancel', { hold: 0.4 });
        await park(v);
      },
    },
    // 9 ------------------------------------------------------------------ failures + health
    {
      kicker: 'Admin',
      title: 'Failures and health at a *glance*',
      sub: 'Renewal Failures and the Home dashboard',
      run: async (v) => {
        const rows = 'table.arraysubs-renewal-failures-table tbody tr';
        await login(v, 1);
        await open(v, ADMIN('renewal-flow/renewal-failures'), `${rows}`);
        await v.say('**Renewal Failures** lists failed renewals that still need action.');
        await point(v, await union(v, [`${rows} >> nth=0 >> td >> nth=2`, `${rows} >> nth=2 >> td >> nth=2`]), 'Gateway decline codes become **plain reasons**.', { label: 'Plain reasons', side: 'right', dim: true, zoom: [await union(v, [`${rows} >> nth=0 >> td >> nth=0`, `${rows} >> nth=2 >> td >> nth=2`]), { scale: 1.6 }] });
        v.poster();
        await v.unfocus();
        await v.zoomOut();
        await point(v, `${rows} >> nth=0 >> .arraysubs-renewal-failures-table__actions`, 'Retry a payment or mark it resolved, right from the list.', { label: 'Retry or resolve', side: 'left', dim: true });
        await open(v, ADMIN('overview'), ['.arraysubs-overview__windows', '.arraysubs-overview__ops', '.arraysubs-overview__signals li'], {
          prep: async (f) => {
            await settleLayout('.arraysubs-overview__windows')(f);
            await f.evaluate(() => document.querySelector('.arraysubs-overview__windows').scrollIntoView({ block: 'center' }));
          },
        });
        await v.scrollTo('.arraysubs-overview__windows', { block: 'center', hold: 0 });
        const outlook = '.arraysubs-overview__card:has(.arraysubs-overview__windows)';
        await point(v, outlook, '**Home** shows renewals due in the next 7, 30 and 90 days.', { label: 'Renewal outlook', side: 'top', dim: true, zoom: [outlook, { scale: 1.5, pad: 40 }] });
        await v.unfocus();
        await point(v, '.arraysubs-overview__ops', 'Billing operations warns you if the **renewal queue** stalls.', { label: 'Queue health', side: 'bottom', dim: true, zoom: ['.arraysubs-overview__ops', { scale: 1.6, pad: 60 }] });
        await v.unfocus();
        await v.zoomOut();
        await point(v, await union(v, ['.arraysubs-overview__signals li:has-text("On-hold subscriptions")', '.arraysubs-overview__signals li:has-text("Overdue or failed renewals")']), 'Held and overdue renewals are flagged for review.', { label: 'Held or overdue', side: 'right', dim: true });
        await v.unfocus();
      },
    },
  ],
};
