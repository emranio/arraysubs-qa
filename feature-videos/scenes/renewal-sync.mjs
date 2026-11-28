// Renewal sync — product tour (Free + Pro).
// Story: store-wide switch + first-charge choice (Free) -> a Nov 28 signup pays only the days left
// and renews Dec 1 -> the order line records it -> Pro per-product signup-day plan (late joiners
// pay for next month) -> the same customer joins that plan: first cycle Dec 1, next charge Jan 1 ->
// box plan (Free) + plan-wizard builder (Pro) -> My Account and the subscriptions list.
//
// The store-wide switch is NOT changed for the shared store: the temporary mu-plugin
// mu-plugins/zz-fv-renewal-sync.php reads it from the private option `fv_rs_overlay` for requests
// with the `fv_rs=1` cookie (only this scene's browser), and the on-camera Save writes only there.
// Every store change is logged in notes/renewal-sync.md.
import { wp, wpJson } from '../lib/director.mjs';

const HERO = 1019; // Grace Woods (block 983-1043)
const CLUSTER = [1001, 1003, 1012]; // Parker Sinclair, Hudson Gray, Isabella Tran: more new subscribers, bought off camera in ch7
const PLUS = 6711; // Plus Membership, $39 / month, no trial (store-wide sync, prorated)
const ROAST = 26114; // Monthly Roast Club, $30 / month, Pro plan: 1-5 full, 6-25 prorate, 26-30 next cycle
const BOX = 31424; // Happy Paws Box (subscription box)
const PLUS_URL = '/product/demo-%c2%b7-plus-membership/';
const ROAST_URL = '/product/demo-%c2%b7-monthly-roast-club/';
const SETTINGS = '/wp-admin/admin.php?page=arraysubs-mainadmin#/renewal-flow/settings';
const SITE = 'http://localhost:10013';
const editBilling = (id) => `/wp-admin/post.php?post=${id}&action=edit&arraysubs_from=plans&arraysubs_edit=billing`;

const log = (...a) => console.log('  [rs]', ...a);

// ------------------------------------------------------------------ store helpers (WP-CLI)
function setOverlay(on) {
  wp(['option', 'update', 'fv_rs_overlay', JSON.stringify({ sync_to_billing_cycle: !!on, sync_first_charge_mode: 'prorate' }), '--format=json']);
}

function emptyCart(ids) {
  wpJson(`global $wpdb; foreach ([${ids.map(Number).join(',')}] as $u) { $wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>(string)$u]); delete_user_meta($u,'_woocommerce_persistent_cart_1'); } echo json_encode(1);`);
}

/** [{sid, oid, status, next}] newest first, for one customer and product. */
function takes(cust, pid) {
  return wpJson(`
    $ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'DESC',
      'meta_query'=>[['key'=>'_customer_id','value'=>${Number(cust)}],['key'=>'_product_id','value'=>${Number(pid)}]]]);
    $out = [];
    foreach ($ids as $sid) { $out[] = ['sid'=>$sid,'oid'=>(int)get_post_meta($sid,'_parent_order_id',true),'status'=>get_post_status($sid),'next'=>get_post_meta($sid,'_next_payment_date',true),'sync'=>get_post_meta($sid,'_renewal_sync_enabled',true)]; }
    echo json_encode($out);`);
}

/** Remove subscriptions + parent and renewal orders an earlier take created (this scene's customers only). */
function removeTakes(cust, pid) {
  const out = wpJson(`
    $ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids',
      'meta_query'=>[['key'=>'_customer_id','value'=>${Number(cust)}],['key'=>'_product_id','value'=>${Number(pid)}]]]);
    $done = [];
    foreach ($ids as $sid) {
      if (class_exists('\\\\ArraySubs\\\\Features\\\\RecurringBilling\\\\Services\\\\RenewalScheduler')) { \\ArraySubs\\Features\\RecurringBilling\\Services\\RenewalScheduler::unschedule($sid); }
      $orders = wc_get_orders(['limit'=>-1,'return'=>'ids','meta_query'=>[['key'=>'_subscription_id','value'=>$sid]]]);
      $oid = (int) get_post_meta($sid, '_parent_order_id', true);
      if ($oid) { $orders[] = $oid; }
      foreach (array_unique($orders) as $o) { if ($ord = wc_get_order($o)) { $ord->delete(true); } }
      wp_delete_post($sid, true);
      $done[] = [$sid, array_values(array_unique($orders))];
    }
    echo json_encode($done);`);
  if (out.length) log('removed earlier takes', cust, pid, JSON.stringify(out));
}

/** The store marks bank transfers as received (as an admin would), so the subscriptions start. */
function confirmTransfers(cust, pid) {
  const out = wpJson(`
    $res = [];
    $ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids',
      'meta_query'=>[['key'=>'_customer_id','value'=>${Number(cust)}],['key'=>'_product_id','value'=>${Number(pid)}]]]);
    foreach ($ids as $sid) { $oid = (int) get_post_meta($sid, '_parent_order_id', true); $o = wc_get_order($oid);
      if ($o && $o->has_status(['on-hold','pending'])) { $o->update_status('processing', 'Bank transfer received.'); }
      clean_post_cache($sid);
      $res[] = [$sid, $oid, get_post_status($sid), get_post_meta($sid,'_next_payment_date',true)]; }
    echo json_encode($res);`);
  log('confirmed', cust, pid, JSON.stringify(out));
  return out;
}

// ------------------------------------------------------------------ browser helpers
async function asRs(v, user) {
  await v.as(user);
  await v.context.addCookies([{ name: 'fv_rs', value: '1', domain: 'localhost', path: '/', expires: Math.floor(Date.now() / 1000) + 86400 }]);
}

/** Navigate and wait for a real content selector (heavy load: skeletons can outlast settle()). */
async function gotoWait(v, url, waitFor, { hold = 0.4, after = null } = {}) {
  if (v.ring) { v.ring = null; await v.apply(); }
  await v.transition(async () => {
    v.cam = { s: 1, x: 0, y: 0 };
    await v.apply();
    await v.frame.goto(`${SITE}${url}`, { waitUntil: 'domcontentloaded', timeout: 120000 });
    if (waitFor) await v.frame.locator(waitFor).first().waitFor({ state: 'visible', timeout: 120000 });
    if (after) { await after(v.frame); await new Promise((r) => setTimeout(r, 300)); }
  }, { fade: 0.35 });
  if (hold) await v.hold(hold, { note: 'goto' });
}

/** Scroll the document (off camera) so `sel` sits `gap` px below the top of the viewport. */
const scrollDocTo = (sel, gap = 60) => async (f) => {
  await f.evaluate(({ sel, gap }) => {
    const e = document.querySelector(sel);
    if (e) window.scrollTo(0, e.getBoundingClientRect().top + window.scrollY - gap);
  }, { sel, gap });
};

/** Visible click, then wait for a selector before the crossfade. */
async function clickWait(v, target, waitFor, { nav = true, extra = 0, hold = 0.35 } = {}) {
  const p = await v.moveTo(target);
  if (v.ring) await v.unfocus({ dur: 0.15 });
  await v.animate(0.1, (t) => { v.cursor.press = t; });
  v.ripple = { x: p.x, y: p.y, p: 0.02 };
  await v.transition(async () => {
    const navP = nav ? v.frame.waitForNavigation({ timeout: 120000 }).catch(() => {}) : null;
    await v.page.mouse.click(p.x, p.y);
    if (navP) await navP;
    if (waitFor) await v.frame.locator(waitFor).first().waitFor({ state: 'visible', timeout: 120000 });
    if (extra) await new Promise((r) => setTimeout(r, extra));
  }, { fade: 0.3, during: (t) => { v.cursor.press = 1 - t; v.ripple.p = Math.max(0.02, t); } });
  v.cursor.press = 0;
  v.ripple = null;
  if (hold) await v.hold(hold, { still: false });
}

/** Union rect (page px) of every visible element matching a Playwright selector (lists allowed). */
async function unionRect(v, sel, pad = 0) {
  const loc = v.frame.locator(sel);
  await loc.first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});
  const r = await loc.evaluateAll((els) => {
    const rs = els.map((e) => e.getBoundingClientRect()).filter((r) => r.width > 0 && r.height > 0);
    if (!rs.length) return null;
    const x1 = Math.min(...rs.map((r) => r.left)), y1 = Math.min(...rs.map((r) => r.top));
    const x2 = Math.max(...rs.map((r) => r.right)), y2 = Math.max(...rs.map((r) => r.bottom));
    return { x: x1, y: y1, w: x2 - x1, h: y2 - y1 };
  });
  if (!r) throw new Error('unionRect: nothing visible for ' + sel);
  return { x: r.x - pad, y: r.y - pad, w: r.w + pad * 2, h: r.h + pad * 2 };
}

/** Rect of the nearest boxed ancestor (border or background) of the element holding `text`. */
async function boxOf(v, text, pad = 0) {
  return v.eval(({ text, pad }) => {
    const all = [...document.querySelectorAll('body *')].filter((e) => e.getClientRects().length && (e.textContent || '').includes(text));
    let el = all[all.length - 1];
    for (let i = 0; el && i < 6; i++) {
      const cs = getComputedStyle(el);
      const boxed = (parseFloat(cs.borderTopWidth) > 0 && cs.borderTopStyle !== 'none') || (cs.backgroundColor && !/rgba\(0, 0, 0, 0\)|transparent/.test(cs.backgroundColor));
      if (boxed && el.getBoundingClientRect().width > 300) break;
      el = el.parentElement;
    }
    if (!el) throw new Error('boxOf: no element for ' + text);
    const r = el.getBoundingClientRect();
    return { x: r.left - pad, y: r.top - pad, w: r.width + pad * 2, h: r.height + pad * 2 };
  }, { text, pad });
}

/** Product editor pages warn before leaving; drop that so the next goto is clean. */
async function dropUnload(v) {
  await v.eval(() => {
    window.onbeforeunload = null;
    if (window.jQuery) { window.jQuery(window).off('beforeunload'); }
  }).catch(() => {});
  await v.page.evaluate(() => {
    const f = document.querySelector('iframe[name="app"]');
    try { f.contentWindow.onbeforeunload = null; } catch {}
  }).catch(() => {});
}

/** Drag a range-slider thumb (its value bubble marks it) from one day to another, on camera. */
async function dragThumb(v, { bubble, input, fromDay, toDay, perDay, dur = 1.1 }) {
  const b = await v.appRect(bubble);
  const inp = await v.appRect(input);
  const start = { x: b.x + b.w / 2, y: inp.y + inp.h / 2, w: 1, h: 1 };
  const p = await v.moveTo(start);
  await v.animate(0.12, (t) => { v.cursor.press = t; });
  await v.page.mouse.down();
  const dx = (toDay - fromDay) * perDay * v.cam.s;
  await v.animate(dur, async (t) => {
    v.cursor.x = p.x + dx * t;
    v.cursor.y = p.y;
    await v.page.mouse.move(v.cursor.x, v.cursor.y);
  });
  await v.page.mouse.up();
  await v.animate(0.12, (t) => { v.cursor.press = 1 - t; });
  v.dirty = true;
}

/** Buy a product off camera (not recorded): product page -> one-click checkout -> place order. */
async function buyOffCamera(v, cust, url) {
  await asRs(v, cust);
  await v.frame.goto(`${SITE}${url}`, { waitUntil: 'domcontentloaded', timeout: 120000 });
  await v.frame.locator('button.single_add_to_cart_button').first().waitFor({ state: 'visible', timeout: 120000 });
  await Promise.all([
    v.frame.waitForNavigation({ timeout: 120000 }).catch(() => {}),
    v.frame.locator('button.single_add_to_cart_button').first().click(),
  ]);
  if (!/checkout/.test(v.frame.url())) await v.frame.goto(`${SITE}/checkout-2/`, { waitUntil: 'domcontentloaded', timeout: 120000 });
  const btn = v.frame.locator('.wc-block-components-checkout-place-order-button').first();
  await btn.waitFor({ state: 'visible', timeout: 120000 });
  await v.frame.locator('.wc-block-cart-item__prices, .wc-block-components-order-summary-item').first().waitFor({ state: 'visible', timeout: 120000 });
  await v.settle({ timeout: 60000 });
  await Promise.all([
    v.frame.waitForURL(/order-received/, { timeout: 180000 }),
    btn.click(),
  ]);
  log('bought off camera', cust, url, v.frame.url());
}

export default {
  slug: 'renewal-sync',
  tier: 'Free + Pro',
  intro: { title: 'One billing day. *Fair* first charges.', sub: 'Renewal sync in ArraySubs: store-wide for free, per product with Pro.' },
  outro: { title: 'Bill on the 1st. Charge *fairly*.', sub: 'Store-wide sync is free. Pro adds a signup-day plan for each product.', url: 'subscriptionplugin.pro' },

  setup: async () => {
    // Later chapters assume the store-wide switch is on for this browser (chapter 1 turns it on).
    setOverlay(true);
    emptyCart([HERO, ...CLUSTER]);
  },

  teardown: async () => {
    emptyCart([HERO, ...CLUSTER]);
    // Nothing shared to restore: the store-wide switch was only ever on in the private overlay.
  },

  chapters: [
    // 1 ------------------------------------------------------------------ store-wide switch
    {
      kicker: 'Settings',
      title: 'One switch, *one billing day*',
      sub: 'Store-wide renewal sync. Free.',
      before: async () => { setOverlay(false); },
      run: async (v) => {
        await asRs(v, 1);
        await gotoWait(v, SETTINGS, '#renewals-sync_to_billing_cycle', { hold: 0.5 });
        // First line bottom-left so the tab it names stays visible; the rest of the chapter top-right.
        await v.say('Renewal sync lives in **Renewal Flow, Renewal Settings**.', { pos: 'bl', hold: 0.9 });
        await v.focus('a.arraysubs-tab-link--active', { pad: 6, hold: 1.6 });
        await v.focus(await unionRect(v, '.arraysubs-fb-card:has(h3:has-text("Renewal Sync")) .arraysubs-fb-switch-wrapper', 8), { label: 'Off: renewals follow each signup date', hold: 1.8 });
        await v.click('#renewals-sync_to_billing_cycle', { settle: false, wait: 700, hold: 0.4 });
        await v.say('Now new subscriptions renew on the **next calendar boundary**.', { pos: 'tr' });
        await v.focus('.arraysubs-fb-card:has(h3:has-text("Renewal Sync")) .arraysubs-alert-info >> nth=0', { label: 'The 1st, the week start or January 1', hold: 2.2 });
        await v.unfocus();
        await v.scrollTo('label.arraysubs-fb-grid-select-item:has-text("Prorate until")', { block: 'center' });
        await v.say('Then choose what a mid-cycle joiner **pays first**.');
        await v.focus('label.arraysubs-fb-grid-select-item:has-text("Prorate until")', { label: 'Only the days left in the cycle', side: 'top', hold: 1.9 });
        await v.focus('label.arraysubs-fb-grid-select-item:has-text("Charge the full")', { label: 'Or the full price at signup', side: 'top', hold: 1.7 });
        await v.unfocus();
        await v.say('Renewals always bill the **full recurring price**.');
        await v.focus('.arraysubs-fb-card:has(h3:has-text("Renewal Sync")) .arraysubs-alert-info >> nth=1', { hold: 1.6 });
        await v.scrollTo('.arraysubs-fb-card:has(h3:has-text("Renewal Sync")) .arraysubs-alert-warning', { block: 'center' });
        await v.say('Bank transfer, Stripe and Mollie can bill on a **shared day**.');
        await v.focus('.arraysubs-fb-card:has(h3:has-text("Renewal Sync")) .arraysubs-alert-warning', { hold: 2.0 });
        await v.unfocus();
        await v.click('button.button-primary:has-text("Save Settings")', { wait: 800, hold: 0.4 });
        await v.say('Saved. Existing subscribers **keep their dates**.');
        await v.focus(':text("Updated at")', { label: 'Saved just now', pad: 6, side: 'top', hold: 1.4 });
        await v.unfocus();
        await v.scrollTo('.arraysubs-fb-card:has(h3:has-text("Renewal Performance")) .arraysubs-settings-left-grid', { block: 'center' });
        await v.say('A busy 1st is **spread over a few hours**.');
        await v.focus('.arraysubs-fb-card:has(h3:has-text("Renewal Performance")) .arraysubs-settings-left-grid', { label: 'Spread over 6 hours by default', pad: 6, hold: 2.0 });
        await v.focus('.arraysubs-fb-card:has(h3:has-text("Renewal Performance")) .arraysubs-alert-info >> nth=0', { label: 'Renewal dates never change', pad: 4, side: 'bottom', hold: 2.0 });
        await v.unfocus();
      },
    },

    // 2 ------------------------------------------------------------------ prorated first charge
    {
      kicker: 'Storefront',
      title: 'A fair *first charge*',
      sub: 'Grace joins the Plus Membership on November 28.',
      before: async () => { removeTakes(HERO, PLUS); emptyCart([HERO]); },
      run: async (v) => {
        await asRs(v, HERO);
        await gotoWait(v, PLUS_URL, 'button.single_add_to_cart_button', { hold: 0.5 });
        await v.say('The Plus Membership costs **$39 a month**.');
        await v.focus('.wp-block-woocommerce-product-price', { label: '$39 / month', pad: 8, hold: 1.3 });
        await v.say(null);
        // For this browser the mu-plugin lands "Subscribe Now" on the cart (the store setting goes to checkout).
        await clickWait(v, 'button.single_add_to_cart_button', '.wc-block-cart-item__prices', { extra: 800 });
        await v.say('Joining on November 28, she pays **$2.60 today**.', { hold: 1.6 });
        await v.zoom('.wc-block-cart-items__row', { scale: 1.6, pad: 30 });
        await v.focus('.wc-block-cart-items__row .wc-block-cart-item__prices', { label: 'Today: only the days left in November', side: 'right', pad: 6, hold: 1.7 });
        await v.say('Short rows show the **renewal price** and the **next charge**.', { hold: 0.6 });
        await v.focus('.wc-block-cart-items__row .wc-block-components-product-details', { label: 'Then $39 on December 1', side: 'right', pad: 6, hold: 1.9 });
        await v.unfocus();
        await v.zoomOut();
        await v.say(null);
        await clickWait(v, 'a.wc-block-cart__submit-button', '.wc-block-components-checkout-place-order-button', { extra: 600 });
        await v.say('She checks out with **bank transfer**.');
        await v.focus('.wc-block-components-radio-control__option:has-text("Direct bank transfer")', { label: 'Can bill on the shared day', pad: 6, hold: 1.6 });
        await v.unfocus();
        await v.say(null); // keep the Place Order button clear of the caption
        await clickWait(v, '.wc-block-components-checkout-place-order-button', '.wc-block-order-confirmation-status, h1:has-text("Order received")', { extra: 800, hold: 0.4 });
        await v.scrollTo('h2:has-text("Related Subscriptions")', { block: 'start' });
        await v.say('Her subscription renews on **December 1**.');
        await v.focus('td:has-text("December 1, 2026")', { label: 'Next payment: December 1', pad: 6, dim: true, side: 'bottom', hold: 2.0 });
        await v.unfocus();
      },
    },

    // 3 ------------------------------------------------------------------ admin: order line + subscription
    {
      kicker: 'Admin',
      title: 'The order *remembers*',
      sub: 'First charge and first full renewal, saved on the order line.',
      before: async () => { confirmTransfers(HERO, PLUS); },
      run: async (v) => {
        const t = takes(HERO, PLUS)[0];
        if (!t || !t.oid) throw new Error('no Plus Membership order for the hero: record chapter 2 first');
        await asRs(v, 1);
        // Open the order already scrolled to the items (the header's payment/IP line stays off screen).
        await gotoWait(v, `/wp-admin/admin.php?page=wc-orders&action=edit&id=${t.oid}`, '#order_line_items', { hold: 0.4, after: scrollDocTo('#woocommerce-order-items', 70) });
        await v.say('In the order, the line item shows **how it was billed**.');
        // Whole item row incl. price/total cells and the totals below; framed so neither the admin menu
        // (x < 160) nor the right sidebar (x > 1140) shows at the edges.
        const row = await v.appRect('#order_line_items tr.item');
        await v.zoom({ x: 182, y: row.y - 10, w: 960, h: row.h + 20 }, { scale: 1.5, pad: 0 });
        await v.focus('#order_line_items tr.item table.display_meta tr:has(th:has-text("First charge:"))', { label: 'Prorated amount', pad: 4, side: 'right', hold: 1.2 });
        await v.focus(await unionRect(v, '#order_line_items tr.item table.display_meta tr:has(th:has-text("Billing cycle starts")), #order_line_items tr.item table.display_meta tr:has(th:has-text("First full renewal"))', 4), { label: 'This cycle, then the 1st', side: 'right', hold: 1.6 });
        await v.focus('#order_line_items tr.item table.display_meta tr:has(th:has-text("First charge amount"))', { label: '$2.60 today', pad: 4, side: 'right', hold: 1.2 });
        await v.unfocus();
        await v.zoomOut();
        await v.say(null);
        await gotoWait(v, `/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${t.sid}`, '.arraysubs-sd__stats', { hold: 0.5 });
        await v.say('The subscription bills the full **$39** on **December 1**.');
        await v.focus('.arraysubs-sd__stats', { label: 'Full price from the first renewal', pad: 6, hold: 2.2 });
        await v.unfocus();
      },
    },

    // 4 ------------------------------------------------------------------ Pro: per-product plan
    {
      kicker: 'Pro · Per product',
      title: 'Late joiners pay for *next month*',
      sub: 'Flexible Renewal Sync gives one product its own signup-day plan.',
      run: async (v) => {
        await asRs(v, 1);
        await gotoWait(v, editBilling(ROAST), '.arraysubs-flex-sync-slider', { hold: 0.4 });
        await v.scrollTo('.arraysubs-section-title:has-text("Flexible Renewal Sync")', { block: 'start', offset: -10 });
        await v.say('Pro: the Monthly Roast Club gets **its own plan**.');
        await v.focus('label.arraysubs-checkbox-label:has-text("Flexible Renewal Sync to Next Billing Cycle")', { label: 'Overrides the store default', pad: 6, side: 'right', hold: 1.6 });
        await v.unfocus();
        const plan = await unionRect(v, '.arraysubs-flex-sync-slider, .arraysubs-flex-sync-legend', 0);
        await v.say('The signup day decides the **first payment**.', { hold: 1.0 });
        await v.zoom(plan, { scale: 1.4, pad: 30 });
        await v.focus('.arraysubs-flex-sync-legend-row >> nth=0', { label: 'Early: full price', pad: 4, side: 'right', hold: 1.0 });
        await v.focus('.arraysubs-flex-sync-legend-row >> nth=1', { label: 'Mid-month: prorated', pad: 4, side: 'right', hold: 1.0 });
        await v.focus('.arraysubs-flex-sync-legend-row >> nth=2', { label: 'Late: pays for next month', pad: 4, side: 'right', hold: 1.3 });
        await v.unfocus();
        await v.zoomOut();

        // Switch the late rule off on camera (not saved): the other two rules take its days.
        const toggle3 = '.arraysubs-flex-sync-legend-row >> nth=2 >> label.arraysubs-flex-sync-toggle';
        await v.say('Switch a rule off and the others **share its days**.', { hold: 1.0 });
        await v.click(toggle3, { settle: false, wait: 450, hold: 0.3 });
        await v.focus(await unionRect(v, '.arraysubs-flex-sync-slider, .arraysubs-flex-sync-legend', 4), { label: 'Two rules now cover all 30 days', side: 'top', hold: 2.0 });
        await v.unfocus();

        // Back on (the editor resets to thirds), then drag both handles to the saved cutoffs.
        await v.say('Turn it back on, then drag the handles to set **cutoff days**.', { hold: 1.0 });
        await v.click(toggle3, { settle: false, wait: 450, hold: 0.4 });
        const day = (i) => v.eval((i) => Number(document.querySelectorAll('.arraysubs-flex-sync-bubble')[i].innerText.trim()), i);
        const bA = await v.appRect('.arraysubs-flex-sync-bubble >> nth=0');
        const bB = await v.appRect('.arraysubs-flex-sync-bubble >> nth=1');
        const dA = await day(0), dB = await day(1);
        const perDay = ((bB.x + bB.w / 2) - (bA.x + bA.w / 2)) / (dB - dA);
        log('handles before drag', dA, dB, 'perDay', perDay.toFixed(2));
        await dragThumb(v, { bubble: '.arraysubs-flex-sync-bubble >> nth=0', input: '.arraysubs-flex-sync-range-input >> nth=0', fromDay: dA, toDay: 5, perDay, dur: 0.9 });
        await v.hold(0.3, { still: false });
        await dragThumb(v, { bubble: '.arraysubs-flex-sync-bubble >> nth=1', input: '.arraysubs-flex-sync-range-input >> nth=1', fromDay: dB, toDay: 25, perDay, dur: 0.9 });
        const a = await day(0), b = await day(1);
        log('handles after drag', a, b);
        await v.focus(await unionRect(v, '.arraysubs-flex-sync-slider, .arraysubs-flex-sync-legend', 4), { label: `Cutoffs at day ${a} and day ${b}`, side: 'top', hold: 1.9 });
        await v.unfocus();
        await dropUnload(v);
      },
    },

    // 5 ------------------------------------------------------------------ Pro: next-cycle checkout
    {
      kicker: 'Storefront',
      title: 'Paid today, *covers December*',
      sub: 'Grace also joins the Monthly Roast Club on day 28.',
      before: async () => { removeTakes(HERO, ROAST); emptyCart([HERO]); },
      run: async (v) => {
        await asRs(v, HERO);
        await gotoWait(v, ROAST_URL, 'button.single_add_to_cart_button', { hold: 0.5 });
        await v.say('The Monthly Roast Club costs **$30 a month**.');
        await v.focus('.wp-block-woocommerce-product-price', { label: '$30 / month', pad: 8, hold: 1.2 });
        await v.say(null);
        await clickWait(v, 'button.single_add_to_cart_button', '.wc-block-cart-item__prices', { extra: 800 });
        await v.say('Day 28 is in the late window: she pays the **full $30**.', { hold: 1.6 });
        await v.zoom('.wc-block-cart-items__row', { scale: 1.6, pad: 30 });
        await v.focus('.wc-block-cart-items__row .wc-block-cart-item__prices', { label: 'Full price today', side: 'right', pad: 6, hold: 1.5 });
        await v.say('It covers December. The **next charge** is January 1.', { hold: 0.6 });
        await v.focus('.wc-block-cart-items__row .wc-block-components-product-details', { label: 'First cycle Dec 1, renews Jan 1', side: 'right', pad: 6, hold: 2.0 });
        await v.unfocus();
        await v.zoomOut();
        await v.say(null);
        await clickWait(v, 'a.wc-block-cart__submit-button', '.wc-block-components-checkout-place-order-button', { extra: 600 });
        await v.say('At checkout she pays **$30.00** today.');
        await v.focus('.wc-block-components-totals-footer-item', { label: 'Covers December', pad: 6, side: 'left', hold: 1.6 });
        await v.unfocus();
        await v.say(null); // keep the Place Order button clear of the caption
        await clickWait(v, '.wc-block-components-checkout-place-order-button', '.wc-block-order-confirmation-status, h1:has-text("Order received")', { extra: 800, hold: 0.4 });
        await v.scrollTo('h2:has-text("Related Subscriptions")', { block: 'start' });
        await v.say('Order placed. Next payment: **January 1**.');
        await v.focus('td:has-text("January 1, 2027")', { label: 'One cycle after the 1st', pad: 6, dim: true, side: 'bottom', hold: 1.8 });
        await v.unfocus();
      },
    },

    // 6 ------------------------------------------------------------------ boxes (Free) + plan wizard (Pro)
    {
      kicker: 'Boxes · Plan wizard',
      title: 'One date for *the whole box*',
      sub: 'Box plans are free. The builder in the plan wizard is Pro.',
      run: async (v) => {
        await asRs(v, 1);
        // Open the box editor already scrolled to Product data (the permalink row stays off screen).
        await gotoWait(v, `/wp-admin/post.php?post=${BOX}&action=edit`, 'button:has-text("Edit Box Configuration"), a:has-text("Edit Box Configuration")', { hold: 0.3, after: scrollDocTo('#woocommerce-product-data', 50) });
        await v.say('A subscription box has its **own sync screen**.');
        await v.focus('.arraysubs-box-fact:has-text("Flexible Renewal Sync")', { label: 'Store default for now', pad: 6, side: 'bottom', hold: 1.6 });
        await clickWait(v, 'button:has-text("Edit Box Configuration"), a:has-text("Edit Box Configuration")', 'button:has-text("Flexible Renewal Sync")', { nav: false, extra: 600 });
        await clickWait(v, 'button:has-text("Flexible Renewal Sync")', 'label:has-text("Give this box its own segment plan")', { nav: false, extra: 500 });
        const info = await boxOf(v, 'Renewal sync for this box is always decided here');
        await v.say('The box decides the date, **never the products inside it**.');
        await v.focus(info, { hold: 2.4 });
        await v.unfocus();
        await clickWait(v, 'label:has-text("Give this box its own segment plan")', '.arraysubs-box-sync__slider', { nav: false, extra: 400 });
        await v.scrollTo('.arraysubs-box-sync__legend', { block: 'center' });
        const boxSlider = await unionRect(v, '.arraysubs-box-sync__slider, .arraysubs-box-sync__legend', 6);
        await v.say('All three rules on a **full-width slider**. Free for boxes.');
        await v.focus(boxSlider, { label: 'Day 10 and day 20 by default', side: 'top', hold: 2.2 });
        await v.unfocus();
        await dropUnload(v);
        await v.say(null);

        await gotoWait(v, '/wp-admin/admin.php?page=arraysubs-mainadmin#/plans', 'button:has-text("Add new plan")', { hold: 0.3 });
        await v.say('Pro: set a plan **while you create a product**.');
        await clickWait(v, 'button:has-text("Add new plan")', 'label.arraysubs-quick-product__type-main:has-text("Simple subscription")', { nav: false, extra: 500 });
        await v.act(async (f) => {
          await f.locator('label.arraysubs-quick-product__type-main:has-text("Simple subscription")').first().click();
          await f.locator('.arraysubs-quick-product button.button-primary:has-text("Next")').first().click();
          const ins = f.locator('.arraysubs-quick-product input.arraysubs-fb-input >> visible=true');
          await ins.nth(0).waitFor({ state: 'visible', timeout: 60000 });
          await ins.nth(0).fill('Monthly Snack Box');
          await ins.nth(1).fill('24');
          await f.locator('.arraysubs-quick-product button.button-primary:has-text("Next")').first().click();
          await f.locator('.arraysubs-quick-product__renewal-sync').first().waitFor({ state: 'visible', timeout: 60000 });
          await new Promise((r) => setTimeout(r, 600));
        });
        await v.scrollTo('.arraysubs-quick-product__renewal-sync', { block: 'start' });
        await clickWait(v, '.arraysubs-quick-product__renewal-sync input[type=checkbox] >> nth=0', '.arraysubs-quick-product__renewal-sync .arraysubs-box-sync__slider', { nav: false, extra: 400 });
        await v.scrollTo('.arraysubs-quick-product__renewal-sync .arraysubs-box-sync__legend', { block: 'center' });
        const wizSlider = await unionRect(v, '.arraysubs-quick-product__renewal-sync .arraysubs-box-sync__slider, .arraysubs-quick-product__renewal-sync .arraysubs-box-sync__legend', 6);
        await v.zoom(wizSlider, { scale: 1.25, pad: 20 });
        await v.focus(wizSlider, { label: 'Same builder in the plan wizard', side: 'top', hold: 2.2 });
        await v.unfocus();
        await v.zoomOut();
        await dropUnload(v);
      },
    },

    // 7 ------------------------------------------------------------------ result
    {
      kicker: 'Result',
      title: 'Everyone renews *together*',
      sub: 'My Account and the subscriptions list.',
      before: async (v) => {
        confirmTransfers(HERO, ROAST);
        confirmTransfers(HERO, PLUS);
        // Three more new subscribers join the Plus Membership (same synced flow, off camera). Earlier takes
        // are replaced so every synced signup is created after chapter 1's on-camera Save.
        for (const c of CLUSTER) {
          removeTakes(c, PLUS);
          emptyCart([c]);
          await buyOffCamera(v, c, PLUS_URL);
          confirmTransfers(c, PLUS);
        }
        emptyCart([...CLUSTER]);
      },
      run: async (v) => {
        await asRs(v, HERO);
        await gotoWait(v, '/my-account/as-subscriptions/', '.arraysubs-subscription-card', { hold: 0.5 });
        await v.say('In My Account, Grace sees **each next renewal**.');
        const card = (name) => `.arraysubs-subscription-card:has-text("${name}")`;
        const nextRow = (name) => v.eval((name) => {
          const c = [...document.querySelectorAll('.arraysubs-subscription-card')].find((e) => e.innerText.includes(name));
          const dt = [...c.querySelectorAll('dt')].find((e) => /Next Renewal/i.test(e.innerText));
          const r = dt.parentElement.getBoundingClientRect();
          return { x: r.left, y: r.top, w: r.width, h: r.height };
        }, name);
        await v.scrollTo(card('Monthly Roast Club'), { block: 'start' });
        await v.focus(await nextRow('Monthly Roast Club'), { label: 'Roast Club: January 1', pad: 6, side: 'right', hold: 1.7 });
        await v.scrollTo(card('Plus Membership'), { block: 'start' });
        await v.focus(await nextRow('Plus Membership'), { label: 'Plus Membership: December 1', pad: 6, side: 'right', hold: 1.7 });
        await v.unfocus();
        await v.say(null);

        await asRs(v, 1);
        await gotoWait(v, '/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions', 'input[placeholder*="product"]', { hold: 0.4 });
        await v.say('In admin, filter the list by **Plus Membership**.', { hold: 0.8 });
        await v.type('input[placeholder*="product"]', 'Plus Membership', { cps: 14 });
        await v.act(async (f) => {
          await f.locator('input[placeholder*="product"]').first().press('Enter');
          await new Promise((r) => setTimeout(r, 1500));
          await f.locator('tbody tr:has-text("Plus Membership")').first().waitFor({ state: 'visible', timeout: 60000 });
        });
        // Table header at the top, so the newest signups and the older ones below fit at 1x.
        // offset: keep the table header below the sticky page header (~113 px).
        await v.scrollTo('table thead >> nth=0', { block: 'start', offset: -64 });
        const rows = await v.eval(() => {
          const trs = [...document.querySelectorAll('tbody tr')];
          const info = trs.map((tr) => {
            const np = tr.querySelector('.arraysubs-subscriptions-next-payment');
            const cust = tr.children[3] || tr;
            const a = cust.getBoundingClientRect();
            const t = tr.getBoundingClientRect();
            const right = t.right - 6;
            return { synced: !!np && /^December 1, 2026/.test(np.innerText.trim()), x: a.left, y: t.top + 4, w: right - a.left, h: t.height - 8 };
          });
          const block = []; let i = 0;
          while (i < info.length && !info[i].synced) i++;
          while (i < info.length && info[i].synced) block.push(info[i++]);
          const rest = info.slice(i).filter((x) => !x.synced && x.y + x.h < innerHeight - 10);
          const u = (a) => a.length ? { x: Math.min(...a.map((r) => r.x)), y: Math.min(...a.map((r) => r.y)), w: Math.max(...a.map((r) => r.x + r.w)) - Math.min(...a.map((r) => r.x)), h: Math.max(...a.map((r) => r.y + r.h)) - Math.min(...a.map((r) => r.y)) } : null;
          return { synced: u(block), n: block.length, rest: u(rest), m: rest.length };
        });
        log('list rows', JSON.stringify(rows));
        if (!rows.synced) throw new Error('no synced rows at the top of the list');
        await v.say('New subscribers all renew on **December 1**.');
        await v.focus(rows.synced, { label: `${rows.n} new signups, one billing day`, pad: 4, side: 'left', hold: 2.4 });
        v.poster();
        if (rows.rest) {
          await v.say('Earlier subscribers **keep their own dates**.');
          await v.focus(rows.rest, { label: 'Signed up before the switch', pad: 4, side: 'left', hold: 2.2 });
        }
        await v.unfocus();
      },
    },
  ],
};
