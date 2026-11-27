// Renewal flow tour (Free). Story: set the timing in Renewal Flow -> emails -> a renewal
// invoice goes out -> the customer pays it from My Account -> admin confirms, date moves on ->
// what a declined card looks like for the customer -> failures queue + Home billing health.
//
// Chapters 4-6 mutate one bank-transfer subscription from the 922-982 customer block
// (default 29689, Theo Ferrari). They form one unit: re-record 4,5,6 together and pass a
// fresh subscription with FV_RF_SUB=<id> (another bacs Plus Membership, e.g. 29697, 29673, 29717).
// Every store change is logged in notes/renewal-flow.md.
import { wp, wpJson } from '../lib/director.mjs';

const SUB = Number(process.env.FV_RF_SUB || 29689);
const ADMIN = (p) => `/wp-admin/admin.php?page=arraysubs-mainadmin#/${p}`;
// Read-only failure example (Stripe, on hold, insufficient funds). Customer 65 is only viewed.
const FAIL_SUB = 25905;
const FAIL_CUSTOMER = 65;

function subInfo() {
  return wpJson(`$id=${SUB}; clean_post_cache($id); wp_cache_delete($id,'post_meta'); $ids=get_post_meta($id,'_order_ids',true); if(is_string($ids)) $ids=json_decode($ids,true); $ids=(array)$ids; echo json_encode(['cust'=>(int)get_post_meta($id,'_customer_id',true),'next'=>get_post_meta($id,'_next_payment_date',true),'pend'=>(int)get_post_meta($id,'_pending_renewal_order_id',true),'last'=>(int)end($ids),'status'=>get_post_status($id)]);`);
}
function orderInfo(id) {
  return wpJson(`$o=wc_get_order(${Number(id)}); echo json_encode(['status'=>$o?$o->get_status():'','key'=>$o?$o->get_order_key():'']);`);
}
function ensureInvoice() {
  let s = subInfo();
  if (!s.pend) {
    wpJson(`$p=new ArraySubs\\Features\\RecurringBilling\\Services\\RenewalProcessor(); echo json_encode(['ok'=>$p->createRenewalInvoice(${SUB})]);`);
    s = subInfo();
    console.log(`  created renewal invoice #${s.pend} for subscription ${SUB}`);
  }
  return s;
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
async function previewRect(v, text) {
  const frameEl = v.frame.locator('iframe[src*="preview_woocommerce_mail"]').first();
  const outer = await frameEl.evaluate((el) => { const r = el.getBoundingClientRect(); return { x: r.x, y: r.y }; });
  const inner = await frameEl.contentFrame().locator(`text=${text}`).first().evaluate((el) => { const r = el.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; });
  return { x: outer.x + inner.x, y: outer.y + inner.y, w: inner.w, h: inner.h };
}


// Concurrent recording agents mint sessions for the same users; the session_tokens user meta
// is read-modify-write, so a fresh token can be dropped by another writer. Mint our own
// cookies, wait, confirm they still validate, then use them.
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
async function login(v, user) {
  await v.page.mouse.move(1300, 760).catch(() => {});
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

// The store is under heavy load (many recorders at once): REST-driven screens can take far
// longer than the harness settle window. open()/clickTo() wait for a ready element off-camera,
// so the crossfade lands on the finished screen, never on a spinner.
const SITE = 'http://localhost:10013';
const LONG = 150000;
async function open(v, url, ready) {
  const full = url.startsWith('http') ? url : `${SITE}${url}`;
  await v.zoomOut();
  await v.act(async (f) => {
    await f.goto(full, { waitUntil: 'domcontentloaded', timeout: LONG });
    for (const r of [].concat(ready || [])) await f.locator(r).first().waitFor({ state: 'visible', timeout: LONG });
  });
  await v.hold(0.4, { note: 'open' });
}
async function clickTo(v, target, ready, { nav = false } = {}) {
  const p = await v.moveTo(target);
  if (v.ring) await v.unfocus({ dur: 0.15 });
  await v.animate(0.1, (t) => { v.cursor.press = t; });
  await v.act(async (f, page) => {
    const navP = nav ? f.waitForNavigation({ timeout: LONG }).catch(() => {}) : null;
    await page.mouse.click(p.x, p.y);
    if (navP) await navP;
    for (const r of [].concat(ready || [])) await f.locator(r).first().waitFor({ state: 'visible', timeout: LONG });
  });
  v.cursor.press = 0;
  await v.hold(0.35, { still: false });
}

const state = { sub: null, order: 0 };

export default {
  slug: 'renewal-flow',
  tier: 'Free',
  intro: { title: 'The renewal *flow*', sub: 'Invoices, reminders, retries and a grace period, set once and run for you.' },
  outro: { title: 'Stop writing *reminders*.', sub: 'Reminders, invoices, retries and a grace period, in the free plugin.', url: 'subscriptionplugin.pro' },
  setup: async () => {
    const r = wpJson(`$s=get_option('arraysubs_settings'); echo json_encode(['renewals'=>$s['renewals']??null,'emails'=>['up'=>$s['emails']['renewal_upcoming']['days_before']??null,'exp'=>$s['emails']['expiring_soon']['days_before']??null,'rate'=>$s['emails']['renewal_reminder_rate_limit']??null],'retry'=>$s['payment_retry']??null]);`);
    console.log('  settings at record time:', JSON.stringify(r));
    state.sub = subInfo();
    console.log(`  subscription ${SUB}:`, JSON.stringify(state.sub));
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
        await v.say('**Renewal Flow** sets the timing for every renewal.');
        await v.focus(await union(v, ['a.arraysubs-tab-link >> nth=0', 'a.arraysubs-tab-link >> nth=4']), { label: 'Five tabs, one flow', side: 'right' });
        await v.focus('.arraysubs-settings-left-grid >> nth=0', { label: 'Invoice lead time', side: 'right', dim: true, hold: 0.6 });
        await v.say('Each renewal invoice is created **3 days** before the due date.');
        await v.focus('.arraysubs-settings-left-grid >> nth=1', { label: 'Grace period', side: 'right', dim: true, hold: 0.6 });
        await v.say('If a payment is missed, the subscription stays **active for 3 days**.');
        v.poster();
        await v.say('Then it waits **7 days on hold** before it cancels.');
        await v.unfocus();
        await v.zoom(await union(v, ['.arraysubs-settings-left-grid >> nth=1', '.arraysubs-alert-warning']), { scale: 1.7 });
        await v.say('Customers keep access while active. It pauses during the hold.');
        await v.zoomOut();
        await clickTo(v, 'a.arraysubs-tab-link:has-text("Email Reminders")', 'label:has-text("Renewal Reminder (Days Before)")');
        await v.focus('.arraysubs-settings-left-grid >> nth=0', { label: 'Days before', side: 'right', dim: true, hold: 0.6 });
        await v.say('A **reminder email** goes out 3 days before each renewal.');
        await v.say('Plans with an end date get an **expiring soon** notice 7 days ahead.');
        await v.focus('input.arraysubs-fb-input >> nth=2', { label: '0 = no limit', side: 'right', dim: true, pad: 10, hold: 0.6 });
        await v.say('Busy day? Cap reminders per minute. Extras wait, never dropped.');
      },
    },
    // 2 ------------------------------------------------------------------ retries
    {
      kicker: 'Settings',
      title: 'Retry *declined* cards',
      sub: 'Renewal Flow: Renewal Settings',
      run: async (v) => {
        await login(v, 1);
        await open(v, ADMIN('renewal-flow/settings'), '.arraysubs-fb-card:has-text("Payment Retries") button.arraysubs-fb-select-control');
        await v.scrollTo('.arraysubs-fb-card:has-text("Payment Retries")', { block: 'start', offset: -90 });
        await v.focus('.arraysubs-fb-card:has-text("Payment Retries") .arraysubs-settings-left-grid', { label: 'Up to 3 retries', side: 'right', dim: true, hold: 0.6 });
        await v.say('A declined card renewal is **retried up to 3 times**.');
        await v.click('.arraysubs-fb-card:has-text("Payment Retries") button.arraysubs-fb-select-control >> nth=0');
        await v.say('Choose the gap before each try: 6 hours to 2 days.');
        await v.act(async (f, page) => { await page.keyboard.press('Escape'); await f.locator('.arraysubs-fb-card:has-text("Payment Retries") h3').first().click(); });
        await v.scrollTo('.arraysubs-alert:has-text("not retried automatically")', { block: 'center' });
        await v.focus('.arraysubs-alert:has-text("not retried automatically")', { label: 'Hard declines', side: 'right', dim: true, hold: 0.6 });
        await v.say('Expired or blocked cards skip retries. The customer updates the card.');
        await v.focus('.arraysubs-alert:has-text("Applies to Stripe and Mollie")', { label: 'Stripe and Mollie', side: 'right', hold: 0.6 });
        await v.say('Retries run for **Stripe** and **Mollie**. PayPal and Paddle retry on their side.');
        await v.scrollTo('.arraysubs-fb-card:has-text("Renewal Performance")', { block: 'start', offset: -90 });
        await v.focus(await union(v, ['.arraysubs-fb-card:has-text("Renewal Performance") .arraysubs-settings-left-grid', '.arraysubs-alert:has-text("spread deterministically")']), { label: 'Big billing days', side: 'right', dim: true, hold: 0.6 });
        await v.say('Big billing days are **spread over hours**. Customer dates never change.');
      },
    },
    // 3 ------------------------------------------------------------------ emails
    {
      kicker: 'Emails',
      title: 'Every email, *ready*',
      sub: 'Settings: Emails',
      run: async (v) => {
        await login(v, 1);
        await open(v, ADMIN('settings/emails'), ['#email-group-renewals-payments', '#email-group-admin-notifications']);
        await v.say('Each subscription email has a card: when it goes out and to whom.');
        await v.scrollTo('#email-group-renewals-payments', { block: 'start', offset: -100 });
        await v.focus('section.arraysubs-email-settings__group:has(#email-group-renewals-payments)', { label: 'Renewals & payments', side: 'bottom', dim: true, hold: 0.6 });
        await v.say('Reminders, invoices, receipts and failure notices for customers.');
        await v.scrollTo('#email-group-cancellations-expiry', { block: 'start', offset: -100 });
        await v.focus(await union(v, ['.arraysubs-email-settings__card:has(h3:text-is("Subscription Scheduled to Cancel"))', '.arraysubs-email-settings__card:has(h3:text-is("Subscription Cancelled"))']), { label: 'Cancellation emails', side: 'bottom', dim: true, hold: 0.6 });
        await v.say('Cancelling customers hear when it is scheduled and when it is done.');
        await v.scrollTo('#email-group-admin-notifications', { block: 'start', offset: -100 });
        await v.focus('.arraysubs-email-settings__card:has(h3:text-is("Payment Failed (Admin)"))', { label: 'Team alert', side: 'bottom', dim: true, hold: 0.6 });
        await v.say('A failed renewal also **alerts your team**, at its own address.');
        await v.scrollTo('.arraysubs-email-settings__card:has(h3:text-is("Renewal Payment Failed"))', { block: 'center' });
        await v.eval(() => document.querySelectorAll('a.arraysubs-email-settings__edit').forEach((a) => a.removeAttribute('target')));
        await v.focus('.arraysubs-email-settings__card:has(h3:text-is("Renewal Payment Failed"))', { label: 'Open the email', side: 'bottom', hold: 1 });
        await clickTo(v, '.arraysubs-email-settings__card:has(h3:text-is("Renewal Payment Failed")) a.arraysubs-email-settings__edit', ['.arraysubs-placeholders-panel', 'iframe[src*="preview_woocommerce_mail"]'], { nav: true });
        await v.frame.locator('iframe[src*="preview_woocommerce_mail"]').first().contentFrame().locator('text=Pay Now').first().waitFor({ state: 'attached', timeout: LONG });
        await v.say('It opens in WooCommerce, to **switch off** or reword.');
        await v.focus('.arraysubs-placeholders-panel', { label: 'Placeholders', side: 'bottom', dim: true, hold: 0.6 });
        await v.say('Placeholders fill in the name, amount and payment links.');
        await v.scrollTo('h2:has-text("Email preview")', { block: 'start' });
        await v.say('Preview it on desktop or mobile, then send yourself a test.');
        await v.scrollBy(380);
        await v.focus(await previewRect(v, 'Pay Now'), { label: 'Straight to payment', side: 'right', pad: 10, hold: 0.6 });
        await v.say('**Pay Now** opens the payment page for the unpaid renewal.');
      },
    },
    // 4 ------------------------------------------------------------------ invoice
    {
      kicker: 'Admin',
      title: 'An invoice goes *out*',
      sub: 'A bank-transfer membership, before its due date',
      before: async () => { state.sub = ensureInvoice(); state.order = state.sub.pend; },
      run: async (v) => {
        await login(v, 1);
        await open(v, ADMIN(`subscriptions/detail/${SUB}`), ['.arraysubs-sd__stat >> nth=1', '.arraysubs-payment-timeline .timeline-event']);
        await v.focus('.arraysubs-sd__stat >> nth=1', { label: 'Due December 2', side: 'bottom', dim: true, hold: 0.6 });
        await v.say('This member pays by bank transfer. The next payment is due soon.');
        await v.scrollTo('table.arraysubs-sd__table', { block: 'center' });
        await v.focus('table.arraysubs-sd__table tbody tr >> nth=0', { label: 'New renewal order', side: 'top', dim: true, hold: 0.6 });
        await v.say('Ahead of the due date, ArraySubs created the **renewal invoice**.');
        await v.zoom('table.arraysubs-sd__table tbody tr >> nth=0', { scale: 1.6 });
        await v.say('It stays **Pending payment** until the customer pays.');
        await v.zoomOut();
        await v.scrollTo('.arraysubs-payment-timeline', { block: 'start' });
        await v.focus(await union(v, ['.arraysubs-payment-timeline .timeline-event >> nth=0', '.arraysubs-payment-timeline .timeline-event >> nth=1']), { label: 'Invoice emailed', side: 'left', dim: true, hold: 0.6 });
        await v.say('The invoice email went out, and the timeline logs both steps.');
      },
    },
    // 5 ------------------------------------------------------------------ customer pays
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
        await v.zoom('table.order_details', { scale: 1.5 });
        await v.focus('table.order_details tr:has-text("Next Payment")', { label: 'Next payment', side: 'bottom', dim: true, hold: 0.6 });
        await v.say('In **My Account**, the member sees the next payment date.');
        await v.zoomOut();
        await v.scrollTo('table.shop_table_responsive', { block: 'center' });
        await v.focus('table.shop_table_responsive tbody tr >> nth=0', { label: 'Pending payment', side: 'top', dim: true, hold: 0.6 });
        await v.say('The open renewal invoice has a **Pay** button.');
        await clickTo(v, 'table.shop_table_responsive a:text-is("Pay")', ['form#order_review table.shop_table', '#place_order'], { nav: true });
        await v.focus('form#order_review table.shop_table', { label: 'Amount due', side: 'bottom', hold: 0.6 });
        await v.say('The payment page shows the renewal and the amount due.');
        await v.focus('li.payment_method_bacs', { label: 'Bank transfer', side: 'top', dim: true, hold: 0.6 });
        await v.say('This member pays by **bank transfer**.');
        await clickTo(v, '#place_order', '.wc-block-order-confirmation-status', { nav: true });
        await v.focus('.wc-block-order-confirmation-status', { label: 'Order received', side: 'bottom', hold: 0.6 });
        await v.say('Done. The store marks it paid once the transfer arrives.');
      },
    },
    // 6 ------------------------------------------------------------------ admin confirms
    {
      kicker: 'Admin',
      title: 'Paid. The date *moves on*',
      sub: 'Order, then subscription detail',
      before: async () => {
        state.sub = subInfo();
        state.order = state.sub.pend || state.sub.last;
        const o = orderInfo(state.order);
        if (o.status !== 'on-hold') throw new Error(`renewal order ${state.order} is ${o.status}, expected on-hold`);
      },
      run: async (v) => {
        await login(v, 1);
        await open(v, `/wp-admin/admin.php?page=wc-orders&action=edit&id=${state.order}`, '#select2-order_status-container');
        await v.focus('#select2-order_status-container', { label: 'Awaiting the transfer', side: 'right', dim: true, hold: 0.6 });
        await v.say('The money arrived, so the admin marks the order **Completed**.');
        await v.click('#select2-order_status-container', { hold: 0.5 });
        await v.click('.select2-results__option:text-is("Completed")', { hold: 0.5 });
        await clickTo(v, 'button.save_order', '#select2-order_status-container', { nav: true });
        await open(v, ADMIN(`subscriptions/detail/${SUB}`), ['.arraysubs-sd__stats', '.arraysubs-payment-timeline .timeline-event']);
        await v.zoom('.arraysubs-sd__stats', { scale: 1.45 });
        await v.focus('.arraysubs-sd__stat >> nth=1', { label: 'Moved one cycle', side: 'bottom', hold: 0.6 });
        await v.say('The next payment moves to **January 2**, one cycle after the due date.');
        await v.zoomOut();
        await v.scrollTo('.arraysubs-payment-timeline', { block: 'start' });
        await v.focus(await union(v, ['.arraysubs-payment-timeline .timeline-event >> nth=0', '.arraysubs-payment-timeline .timeline-event >> nth=1']), { label: 'Paid + receipt', side: 'left', dim: true, hold: 0.6 });
        await v.say('A receipt email goes out, and the timeline shows every step.');
      },
    },
    // 7 ------------------------------------------------------------------ declined card
    {
      kicker: 'My Account',
      title: 'When a card is *declined*',
      sub: 'A card renewal that failed, seen by the customer',
      run: async (v) => {
        await login(v, FAIL_CUSTOMER);
        await open(v, `/my-account/as-view-subscription/${FAIL_SUB}/`, ['.arraysubs-retry-payment', 'ul.subscription-notes-list']);
        await v.scrollTo('h2:has-text("Subscription #")', { block: 'start' });
        await v.zoom('table.order_details', { scale: 1.5 });
        await v.focus('table.order_details .subscription-status-badge', { label: 'On hold', side: 'right', dim: true, pad: 8, hold: 0.6 });
        await v.say('Here a card renewal **failed**, and it was not paid in time.');
        await v.zoomOut();
        await v.scrollTo('ul.subscription-notes-list', { block: 'start' });
        await v.zoom(await union(v, ['.subscription-note >> nth=0', '.subscription-note:has-text("Payment failed for")']), { scale: 1.75 });
        await v.focus('.subscription-note:has-text("Payment failed for")', { dim: true, hold: 0.8 });
        await v.say('The note gives the reason in plain words: **insufficient funds**.');
        await v.focus('.subscription-note:has-text("automatically put on hold")', { dim: true, hold: 0.8 });
        await v.say('After the 3 active days, the subscription went **on hold**.');
        await v.unfocus();
        await v.zoomOut();
        await v.scrollTo('.arraysubs-retry-payment', { block: 'center' });
        await v.focus(await union(v, ['.arraysubs-retry-payment', 'table.shop_table_responsive a:text-is("Pay")'], 6), { label: 'Fix it here', side: 'right', dim: true, hold: 0.6 });
        await v.say('The customer can fix it here: **Retry Payment** or **Pay**.');
        await v.click('.arraysubs-retry-payment', { hold: 0.4 });
        await v.zoom('.arraysubs-portal-confirm .arraysubs-modal__content', { scale: 1.9 });
        await v.focus('.arraysubs-portal-confirm .arraysubs-modal__message', { label: 'Checked first', side: 'right', hold: 0.6 });
        await v.say('Before a retry, ArraySubs checks they were **not already charged**.');
        await v.unfocus();
        await v.zoomOut();
        await v.click('.arraysubs-portal-confirm__cancel', { hold: 0.4 });
      },
    },
    // 8 ------------------------------------------------------------------ failures + health
    {
      kicker: 'Admin',
      title: 'Failures and health at a *glance*',
      sub: 'Renewal Failures and the Home dashboard',
      run: async (v) => {
        await login(v, 1);
        await open(v, ADMIN('renewal-flow/renewal-failures'), 'table.arraysubs-renewal-failures-table tbody tr');
        await v.say('**Renewal Failures** lists failed renewals that still need action.');
        await v.zoom(await union(v, ['table.arraysubs-renewal-failures-table tbody tr >> nth=0 >> td >> nth=0', 'table.arraysubs-renewal-failures-table tbody tr >> nth=2 >> td >> nth=2']), { scale: 1.6 });
        await v.focus(await union(v, ['table.arraysubs-renewal-failures-table tbody tr >> nth=0 >> td >> nth=2', 'table.arraysubs-renewal-failures-table tbody tr >> nth=2 >> td >> nth=2']), { label: 'Plain reasons', side: 'right', dim: true, hold: 0.6 });
        await v.say('Gateway decline codes become **plain reasons**.');
        await v.unfocus();
        await v.zoomOut();
        await v.focus('table.arraysubs-renewal-failures-table tbody tr >> nth=0 >> .arraysubs-renewal-failures-table__actions', { label: 'Retry or resolve', side: 'left', dim: true, hold: 0.6 });
        await v.say('Retry a payment or mark it resolved, right from the list.');
        await open(v, ADMIN('overview'), ['.arraysubs-overview__windows', '.arraysubs-overview__ops', '.arraysubs-overview__signals']);
        await v.scrollTo('.arraysubs-overview__windows', { block: 'center' });
        await v.zoom(await union(v, ['.arraysubs-overview__windows', '.arraysubs-overview__ops']), { scale: 1.6 });
        await v.focus('.arraysubs-overview__windows', { label: 'Renewals due ahead', side: 'top', dim: true, hold: 0.6 });
        await v.say('**Home** shows renewals due in the next 7, 30 and 90 days.');
        await v.focus('.arraysubs-overview__ops', { label: 'Renewal queue', side: 'bottom', dim: true, hold: 0.6 });
        await v.say('Billing operations shows whether the **renewal queue** is healthy.');
        await v.unfocus();
        await v.zoom('.arraysubs-overview__signals', { scale: 1.5 });
        await v.focus('.arraysubs-overview__signals li:has-text("Overdue or failed renewals")', { label: 'Needs review', side: 'right', dim: true, hold: 0.6 });
        await v.say('Overdue or failed renewals are flagged for review.');
        await v.unfocus();
        await v.zoomOut();
      },
    },
  ],
};
