// Subscription bundles — product tour (Pro).
// Story: a draft product becomes "Morning Ritual Bundle" (type, schedule, products, discount,
// renewal sync, publish) -> the customer sees the product page and checkout -> My Account
// shows the bundle and its included membership -> admin sees the frozen contents, the included
// subscription and the renewal order that doubles as a packing list.
// Store changes are logged in notes/subscription-bundles.md.
// Display-only helpers for this scene: wp-content/mu-plugins/zz-fv-subscription-bundles.php
// (needs the fv_sb cookie, see asUser) and SCENE_CSS below (recording browser only).
import { wp, wpJson, W, H } from '../lib/director.mjs';

const PRODUCT = 32209; // "Morning Ritual Bundle" (draft created for this video)
const CUSTOMER = 1115; // Grace Watson (block 1105-1165)
const EDIT = `/wp-admin/post.php?post=${PRODUCT}&action=edit`;
const SUBS = '/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions';
const CHECKOUT = '/checkout-2/';
const DESCRIPTION = 'Start every morning the same way: two bags of freshly roasted single-origin coffee, a ceramic mug and a Starter Membership with brew guides and member perks. One bundle, one monthly payment.';

const log = (...a) => console.log('  [sb]', ...a);
const productUrl = () => wp(['eval', `echo get_permalink(${PRODUCT});`]).replace('http://localhost:10013', '');

// Recording-only layout CSS (nothing in the store changes):
// - the product description editor is shorter, so the title, text and product data share the first frame;
// - the block checkout uses the full window instead of the store's narrow 860px Checkout Builder container;
// - the "contact the site administrator" line (this store has customer cancellation switched off) is hidden.
const SCENE_CSS = [
  '#content_ifr, #wp-content-editor-container textarea#content{height:96px !important;min-height:0 !important;}',
  'body.woocommerce-checkout:not(.woocommerce-order-received){max-width:none !important;width:auto !important;}',
  '.arraysubs-cancellation-contact-notice{display:none !important;}',
].join('');

const php = {
  // Back to a plain draft (simple product, no bundle data), so chapter 1 starts from scratch.
  resetProduct: `
    $id = ${PRODUCT};
    foreach (['_arraysubs_bundle_config','_arraysubs_subscription_bundle','_is_subscription','_subscription_period','_subscription_interval','_subscription_length','_trial_length','_trial_period','_signup_fee','_arraysubs_subscription_mode','_arraysubs_flexible_periods','_price','_regular_price','_sale_price','_sold_individually','_arraysubs_renewal_sync_enabled'] as $k) { delete_post_meta($id, $k); }
    foreach (get_post_meta($id) as $k => $x) { if (strpos($k, '_arraysubs_renewal_sync') === 0 || strpos($k, '_arraysubs_bundle') === 0) delete_post_meta($id, $k); }
    wp_set_object_terms($id, 'simple', 'product_type');
    wp_update_post(['ID' => $id, 'post_status' => 'draft', 'post_content' => ${JSON.stringify(DESCRIPTION)}]);
    wc_delete_product_transients($id);
    clean_post_cache($id);
    echo json_encode(['ok' => 1, 'type' => WC_Product_Factory::get_product_type($id), 'status' => get_post_status($id)]);`,
  emptyCart: `global $wpdb; $wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>'${CUSTOMER}']); delete_user_meta(${CUSTOMER},'_woocommerce_persistent_cart_1'); echo json_encode(['ok'=>1]);`,
  // Every subscription (bundle + included children) and order (initial + renewals) an earlier take made.
  removeTakes: `
    $ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids',
      'meta_query'=>[['key'=>'_customer_id','value'=>${CUSTOMER}],['key'=>'_product_id','value'=>${PRODUCT}]]]);
    $done = [];
    foreach ($ids as $sid) {
      $kids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_arraysubs_bundle_parent_subscription','meta_value'=>$sid]);
      $oids = [];
      foreach (array_merge([$sid], $kids) as $x) {
        $oids[] = (int) get_post_meta($x, '_parent_order_id', true);
        $j = json_decode((string) get_post_meta($x, '_order_ids', true), true);
        if (is_array($j)) { $oids = array_merge($oids, array_map('intval', $j)); }
      }
      $oids = array_values(array_unique(array_filter($oids)));
      foreach ($kids as $k) { wp_delete_post($k, true); }
      foreach ($oids as $oid) { if ($o = wc_get_order($oid)) { $o->delete(true); } }
      wp_delete_post($sid, true);
      $done[] = ['sub'=>$sid, 'kids'=>$kids, 'orders'=>$oids];
    }
    echo json_encode($done);`,
  take: `
    $ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>1,'fields'=>'ids','orderby'=>'ID','order'=>'DESC',
      'meta_query'=>[['key'=>'_customer_id','value'=>${CUSTOMER}],['key'=>'_product_id','value'=>${PRODUCT}]]]);
    $sid = (int) ($ids[0] ?? 0);
    $kids = $sid ? get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_arraysubs_bundle_parent_subscription','meta_value'=>$sid]) : [];
    $oid = $sid ? (int) get_post_meta($sid, '_parent_order_id', true) : 0;
    $o = $oid ? wc_get_order($oid) : null;
    $all = $sid ? json_decode((string) get_post_meta($sid, '_order_ids', true), true) : [];
    $ren = 0; foreach ((array) $all as $x) { if ((int) $x !== $oid) { $ren = (int) $x; } }
    echo json_encode(['sub'=>$sid, 'status'=>$sid ? get_post_status($sid) : '', 'kids'=>$kids, 'order'=>$oid, 'ostatus'=>$o ? $o->get_status() : '', 'renewal'=>$ren, 'next'=>$sid ? get_post_meta($sid, '_next_payment_date', true) : '']);`,
};
const take = () => wpJson(php.take);

/** Log in (cookies) and add the fv_sb cookie for the scene's display-only mu-plugin. */
async function asUser(v, id) {
  await v.as(id);
  await v.context.addCookies([{ name: 'fv_sb', value: '1', domain: 'localhost', path: '/', expires: Math.floor(Date.now() / 1000) + 86400 }]);
}

/** Product editor pages warn before leaving; drop that so the next goto is clean. */
async function dropUnload(v) {
  await v.eval(() => {
    window.onbeforeunload = null;
    if (window.jQuery) window.jQuery(window).off('beforeunload');
  }).catch(() => {});
}

/** Union rect (page px) of every element matching a CSS selector inside the store frame. */
async function unionRect(v, css, pad = 0) {
  await v.frame.locator(css).first().waitFor({ state: 'visible', timeout: 20000 }).catch(() => {});
  return v.eval(({ css, pad }) => {
    const els = [...document.querySelectorAll(css)].filter((e) => e.getClientRects().length);
    if (!els.length) throw new Error('no match ' + css);
    let x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity;
    for (const e of els) { const r = e.getBoundingClientRect(); x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom); }
    return { x: x1 - pad, y: y1 - pad, w: x2 - x1 + pad * 2, h: y2 - y1 + pad * 2 };
  }, { css, pad });
}

/** Tight box around the visible text inside the first match (block children are often full-width). */
async function textRect(v, sel, pad = 0) {
  const loc = v.frame.locator(sel).first();
  await loc.waitFor({ state: 'visible', timeout: 15000 });
  return loc.evaluate((el, pad) => {
    const w = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    let x1 = 1e9, y1 = 1e9, x2 = -1e9, y2 = -1e9, n;
    while ((n = w.nextNode())) {
      if (!n.textContent.trim()) continue;
      const rg = document.createRange(); rg.selectNodeContents(n);
      for (const r of rg.getClientRects()) { if (!r.width || !r.height) continue; x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom); }
    }
    return { x: x1 - pad, y: y1 - pad, w: x2 - x1 + 2 * pad, h: y2 - y1 + 2 * pad };
  }, pad);
}

/** Camera that shows app rect starting at (left, top) at scale s (clamped to the page). */
const camAt = (left, top, s) => ({
  s,
  x: Math.max(W - W * s, Math.min(0, -left * s)),
  y: Math.max(H - H * s, Math.min(0, -top * s)),
});

const SEL = {
  modal: '.arraysubspro-bundle-modal-editor',
  trigger: '.arraysubspro-bundle-ajax-select__trigger',
  search: '.arraysubspro-bundle-ajax-select__search input',
  option: (t) => `.arraysubspro-bundle-ajax-select__option:has-text("${t}")`,
  rows: '.arraysubspro-bundle-product-list',
  row: '.arraysubspro-bundle-product-row',
  subtotal: '.arraysubspro-bundle-subtotal',
  productsTitle: '.arraysubspro-bundle-products .arraysubspro-bundle-section__title',
};

const modalOpen = (v) => v.frame.locator(SEL.modal).isVisible().catch(() => false);
const waitOption = (v, label) => v.frame.locator(SEL.option(label)).first().waitFor({ state: 'visible', timeout: 120000 });

/** Off-camera: search a product in the bundle picker and add it. */
async function pick(v, q, label) {
  if (!(await v.frame.locator(SEL.search).count())) await v.frame.locator(SEL.trigger).click();
  await v.frame.locator(SEL.search).fill(q);
  await waitOption(v, label);
  await v.frame.locator(SEL.option(label)).first().click();
  await v.settle();
}

/** Rect from the row's left edge to the right edge of its Qty field (names + quantities). */
async function namesQtyRect(v, pad = 10) {
  const rows = await unionRect(v, SEL.row, 0);
  const qty = await unionRect(v, `${SEL.row} .arraysubspro-bundle-product-row__qty`, 0);
  return { x: rows.x - pad, y: rows.y - pad, w: qty.x + qty.w - rows.x + 2 * pad + 20, h: rows.h + 2 * pad };
}

/** Off-camera: rebuild the editor state a chapter starts from (only when re-recording it alone). */
async function ensureEditor(v, stage) {
  const vis = (sel) => v.frame.locator(sel).first().isVisible().catch(() => false);
  const rows = await v.frame.locator(SEL.row).count().catch(() => 0);
  const open = await modalOpen(v);
  let ok = false;
  if (open && stage === 'schedule') ok = rows === 0 && (await vis('.arraysubspro-bundle-products'));
  if (open && stage === 'products') ok = rows === 3 && (await vis('.arraysubspro-bundle-products'));
  if (open && stage === 'discount') ok = (await v.frame.locator('.arraysubspro-bundle-amount__input').inputValue().catch(() => '')) === '15';
  if (ok) return;
  log(`rebuilding editor state: ${stage}`);
  log('removed takes', JSON.stringify(wpJson(php.removeTakes)));
  wpJson(php.resetProduct);
  await asUser(v, 1);
  await dropUnload(v);
  await v.goto(EDIT, { hold: 0 });
  await v.frame.locator('#product-type').selectOption('arraysubs_subscription_bundle');
  await v.frame.locator('.arraysubspro-bundle-configure-btn').click();
  await v.settle();
  if (stage === 'schedule') return;
  for (const [q, label] of [['coffee', 'Single-origin Coffee'], ['coffee', 'Single-origin Coffee'], ['mug', 'Ceramic Member Mug'], ['starter', 'Starter Membership']]) await pick(v, q, label);
  await v.frame.locator(SEL.productsTitle).click();
  await v.frame.locator(SEL.subtotal).scrollIntoViewIfNeeded();
  if (stage === 'products') return;
  await v.frame.locator('button:has-text("Continue to Discount")').click();
  await v.frame.locator('.arraysubspro-bundle-choice:has-text("Percentage")').click();
  await v.frame.locator('.arraysubspro-bundle-amount__input').fill('15');
  await v.settle();
}

/** Click something that starts a slow navigation (checkout), wait for the URL, crossfade. */
async function clickAndWaitUrl(v, target, re, { timeout = 240000 } = {}) {
  const p = await v.moveTo(target);
  if (v.ring) await v.unfocus({ dur: 0.15 });
  await v.animate(0.1, (t) => { v.cursor.press = t; });
  v.ripple = { x: p.x, y: p.y, p: 0.02 };
  await v.transition(async () => {
    await v.page.mouse.click(p.x, p.y);
    await v.frame.waitForURL(re, { timeout });
  }, { fade: 0.35, during: (t) => { v.cursor.press = 1 - t; v.ripple.p = Math.max(0.02, t); } });
  v.cursor.press = 0;
  v.ripple = null;
  await v.hold(0.3, { still: false });
}

/** Off-camera: put the bundle in the customer's cart (fresh cart). */
async function freshCartWithBundle(v) {
  wpJson(php.emptyCart);
  await asUser(v, CUSTOMER);
  await v.goto(productUrl(), { hold: 0 });
  await v.frame.locator('button.single_add_to_cart_button').click();
  await v.settle();
}

export default {
  slug: 'subscription-bundles',
  tier: 'Pro',
  intro: { title: 'Subscription *bundles*', sub: 'Sell a fixed set of products as one discounted subscription.' },
  outro: { title: 'One kit. One price. *Every month.*', sub: 'Subscription bundles in ArraySubs Pro.', url: 'subscriptionplugin.pro' },
  setup: async (v) => {
    await v.context.addInitScript((css) => {
      if (window.top === window) return;
      const add = () => {
        if (document.getElementById('fv-sb-css')) return;
        const st = document.createElement('style'); st.id = 'fv-sb-css'; st.textContent = css;
        (document.head || document.documentElement).appendChild(st);
      };
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add); else add();
    }, SCENE_CSS);
  },
  teardown: async () => { try { wpJson(php.emptyCart); } catch {} },
  chapters: [
    // 1 ------------------------------------------------------------------ product type + schedule
    {
      kicker: 'Admin',
      title: 'Start with a *product*',
      before: async () => {
        // Earlier takes keep subscriptions on this product (the editor would show an "Active Subscriptions" notice).
        log('removed takes', JSON.stringify(wpJson(php.removeTakes)));
        log('reset', JSON.stringify(wpJson(php.resetProduct)));
      },
      run: async (v) => {
        await asUser(v, 1);
        await v.goto(EDIT, { hold: 0.3 });
        await v.say('A bundle starts as a normal product: a name, a photo, a description.');
        await v.focus('#titlediv', { label: 'A draft product', side: 'bottom', hold: 1.2 });
        await v.unfocus();
        await v.scrollTo('#woocommerce-product-data', { block: 'start' });
        await v.say('Pick the **Subscription Bundle** product type.');
        await v.select('#product-type', 'arraysubs_subscription_bundle');
        await v.hover('.arraysubspro-bundle-panel', { dx: 0.45, dy: 0.62, hold: 0.1 });
        await v.focus('#product-type', { label: 'New product type', side: 'right', pad: 6, hold: 1.3 });
        await v.say('No price fields: the products inside **set the price**.');
        await v.focus('.arraysubspro-bundle-panel', { label: 'Not for sale until products are added', side: 'bottom', dim: true, hold: 1.8 });
        await v.unfocus();
        await v.click('.arraysubspro-bundle-configure-btn', { wait: 300 });
        await v.say('One editor, three screens: **products**, **discount** and **renewal sync**.');
        await v.focus('.arraysubspro-bundle-wizard-nav', { label: '3 screens', side: 'bottom', hold: 1.3 });
        await v.say('First, how often the **whole bundle** bills.');
        await v.focus(await unionRect(v, '.arraysubspro-bundle-grid-3', 6), { label: 'Every 1 month, until cancelled', side: 'top', dim: true, hold: 2.0 });
        await v.unfocus();
      },
    },

    // 2 ------------------------------------------------------------------ products
    {
      kicker: 'Admin',
      title: 'Pick the *products*',
      sub: 'Up to 50 products, 1 to 99 of each.',
      before: async (v) => { await ensureEditor(v, 'schedule'); },
      run: async (v) => {
        await v.cardOut();
        await v.scrollTo('.arraysubspro-bundle-products', { block: 'start' });
        await v.say('Search your catalog and add products to the kit.', { hold: 1.3 });
        await v.click(SEL.trigger, { hold: 0.2 });
        await v.type(SEL.search, 'coffee', { click: false, cps: 10 });
        await v.act(() => waitOption(v, 'Single-origin Coffee'));
        await v.zoom('.arraysubspro-bundle-ajax-select__dropdown', { scale: 1.8 });
        await v.say('Only products that fit a **monthly** bundle are listed.', { hold: 1.8 });
        await v.focus('.arraysubspro-bundle-ajax-select__options', { label: 'Prices and Subscription badges', side: 'bottom', hold: 1.3 });
        await v.click(SEL.option('Single-origin Coffee'), { hold: 0.3 });
        await v.say('Pick it again: the **quantity** goes up, no second row.', { hold: 1.2 });
        await v.click(SEL.option('Single-origin Coffee'), { hold: 0.2 });
        // close the picker so the single row shows
        await v.act(async (f) => { await f.locator(SEL.productsTitle).click(); });
        await v.zoom(await namesQtyRect(v), { scale: 1.3 });
        await v.focus(`${SEL.row} .arraysubspro-bundle-product-row__qty`, { label: 'Qty 2, still one row', side: 'bottom', pad: 6, hold: 1.5 });
        await v.unfocus();
        await v.zoomOut({ dur: 0.6 });
        // the mug goes in off-camera, the membership on camera
        await v.act(async () => {
          await pick(v, 'mug', 'Ceramic Member Mug');
          await v.frame.locator(SEL.productsTitle).click();
        });
        await v.say('A **membership** fits too: it also bills every month.');
        await v.click(SEL.trigger, { hold: 0.1 });
        await v.type(SEL.search, 'starter', { click: false, cps: 10 });
        await v.act(() => waitOption(v, 'Starter Membership'));
        await v.zoom('.arraysubspro-bundle-ajax-select__dropdown', { scale: 1.8 });
        await v.focus(SEL.option('Starter Membership'), { label: 'Monthly subscription product', side: 'bottom', hold: 1.3 });
        await v.click(SEL.option('Starter Membership'), { hold: 0.2 });
        await v.zoomOut({ dur: 0.6 });
        await v.click(SEL.productsTitle, { hold: 0.1 });
        await v.scrollTo(SEL.subtotal, { block: 'end' });
        await v.say('Two coffees, a mug and a membership, **in the order you set**.');
        const rows = await unionRect(v, SEL.row, 0);
        await v.zoom(await namesQtyRect(v), { scale: 1.3 });
        await v.focus(await unionRect(v, `${SEL.row} .arraysubspro-bundle-product-row__name`, 6), { label: 'Shown in this order everywhere', side: 'bottom', hold: 1.5 });
        await v.say('Set **how many** of each, move rows up or down, or remove one.');
        await v.focus(await unionRect(v, `${SEL.row} .arraysubspro-bundle-product-row__qty`, 4), { label: 'Qty 1 to 99', side: 'top', hold: 1.1 });
        await v.unfocus();
        await v.zoom({ x: rows.x + rows.w - 1080, y: rows.y - 10, w: 1080, h: rows.h + 20 }, { scale: 1.3, dur: 0.7 });
        await v.focus(await unionRect(v, `${SEL.row} .arraysubspro-bundle-product-row__actions`, 4), { label: 'Reorder or remove', side: 'top', hold: 1.2 });
        await v.unfocus();
        await v.zoomOut({ dur: 0.6 });
      },
    },

    // 3 ------------------------------------------------------------------ discount
    {
      kicker: 'Admin',
      title: 'One *discount*',
      sub: 'Fixed amount or percentage, on every payment.',
      before: async (v) => { await ensureEditor(v, 'products'); },
      run: async (v) => {
        await v.cardOut();
        await v.click('button:has-text("Continue to Discount")', { hold: 0.3 });
        await v.say('Reward the kit with **one discount**, on every payment.');
        const ALERT = '.arraysubspro-bundle-discount .arraysubspro-bundle-alert';
        const alertText = await textRect(v, ALERT, 10);
        await v.zoom(alertText, { scale: 1.4, pad: 0 });
        const head = await v.eval((sel) => {
          const a = document.querySelector(sel);
          const li = a.querySelector('li');
          const w = document.createTreeWalker(a, NodeFilter.SHOW_TEXT);
          let x1 = 1e9, y1 = 1e9, x2 = -1e9, y2 = -1e9, n;
          while ((n = w.nextNode())) {
            if (!n.textContent.trim() || (li && li.parentElement.contains(n.parentElement))) continue;
            const rg = document.createRange(); rg.selectNodeContents(n);
            for (const r of rg.getClientRects()) { if (!r.width) continue; x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom); }
          }
          return { x: x1 - 6, y: y1 - 4, w: x2 - x1 + 12, h: y2 - y1 + 8 };
        }, ALERT);
        await v.focus(head, { label: 'Renewals included', side: 'right', hold: 1.5 });
        await v.say('Contents and price **lock in** at purchase; edits reach new buyers only.');
        await v.focus(await textRect(v, `${ALERT} li >> nth=1`, 0), { pad: 3, hold: 1.2 });
        await v.unfocus();
        await v.zoomOut();
        await v.say('Pick a **fixed amount** or a **percentage**.', { hold: 1.4 });
        await v.focus('.arraysubspro-bundle-choice-group', { label: 'Fixed amount or percentage', side: 'bottom', hold: 1.2 });
        await v.click('.arraysubspro-bundle-choice:has-text("Percentage")', { hold: 0.3 });
        await v.type('.arraysubspro-bundle-amount__input', '15', { cps: 6 });
        await v.say('The price preview updates **while you type**.');
        await v.scrollTo('.arraysubspro-bundle-preview', { block: 'center' });
        await v.focus('.arraysubspro-bundle-preview', { label: '$79.00 − 15% = $67.15 a month', side: 'top', dim: true, hold: 2.0 });
        await v.unfocus();
      },
    },

    // 4 ------------------------------------------------------------------ renewal sync + publish
    {
      kicker: 'Admin',
      title: 'One *billing day*',
      sub: 'Optional renewal sync, then publish.',
      before: async (v) => { await ensureEditor(v, 'discount'); },
      run: async (v) => {
        await v.cardOut();
        await v.click('button:has-text("Continue to Flexible Renewal Sync")', { hold: 0.3 });
        await v.say('Optional: bill every new subscriber of this bundle on **one day**.');
        await v.click('.arraysubspro-bundle-checkbox input', { hold: 0.3 });
        await v.focus('.arraysubspro-bundle-checkbox', { label: 'Own segment plan', side: 'bottom', pad: 4, hold: 1.1 });
        await v.unfocus();
        await v.scrollTo('.arraysubspro-bundle-sync__legend', { block: 'center' });
        await v.say('The **signup day** sets the first charge.');
        await v.focus(await unionRect(v, '.arraysubspro-bundle-sync__slider, .arraysubspro-bundle-sync__legend', 10), { label: 'Full, prorated, or full for the next cycle', side: 'top', dim: true, hold: 2.2 });
        await v.unfocus();
        await v.say(null);
        await v.click('button:has-text("Save Configuration")', { hold: 0.3 });
        await v.say('Saved: the product shows the **whole bundle** at a glance.', { hold: 0.6 });
        await v.scrollTo('.arraysubspro-bundle-editor', { block: 'start' });
        await v.zoom('.arraysubspro-bundle-editor', { scale: 1.6, pad: 24 });
        await v.focus('.arraysubspro-bundle-facts', { label: 'Billing, discount, renewal sync', side: 'bottom', hold: 1.3 });
        await v.focus('.arraysubspro-bundle-summary-table__foot-row--total', { label: '$67.15 every month', side: 'top', pad: 4, hold: 1.4 });
        v.poster();
        await v.unfocus();
        await v.zoomOut();
        // Publish box and bundle summary in one frame
        const py = await v.eval(() => document.querySelector('#publish').getBoundingClientRect().top + window.scrollY);
        await v.scrollTo(Math.max(0, py - 190));
        await v.say('Publish, and the bundle is **for sale**.');
        await v.click('#publish', { nav: true, wait: 600, hold: 0.3 });
        await v.hover('#submitdiv .misc-pub-post-status', { dx: 0.9, hold: 0.1 });
        await v.focus('#submitdiv', { label: 'Published', side: 'left', hold: 1.2 });
        await v.unfocus();
        await dropUnload(v);
      },
    },

    // 5 ------------------------------------------------------------------ product page
    {
      kicker: 'Storefront',
      title: 'A page that *sells the kit*',
      sub: 'Struck price, every item, one button.',
      before: async () => { log('removed takes', JSON.stringify(wpJson(php.removeTakes))); wpJson(php.emptyCart); },
      run: async (v) => {
        await asUser(v, CUSTOMER);
        await v.goto(productUrl(), { hold: 0.5 });
        await v.say('Shoppers see the **saving** first: the full price struck out.');
        const head = await v.eval(() => {
          const a = document.querySelector('h1.wp-block-post-title, h1').getBoundingClientRect();
          const b = document.querySelector('.wp-block-woocommerce-product-price, .summary .price').getBoundingClientRect();
          return { x: a.x - 10, y: a.y - 10, w: Math.max(a.width, b.width) + 20, h: b.bottom - a.y + 20 };
        });
        await v.zoom(head, { scale: 1.6 });
        await v.hold(1.5, { note: 'price' });
        await v.zoomOut();
        await v.scrollTo('.arraysubspro-bundle-included', { block: 'start' });
        await v.say('**What\'s included** lists every item, quantity and price.');
        await v.zoom('.arraysubspro-bundle-included', { scale: 1.4 });
        await v.focus('.arraysubspro-bundle-included', { label: 'Same items every month', side: 'left', dim: true, hold: 1.8 });
        await v.unfocus();
        await v.zoomOut();
        await v.say('One button adds the **whole kit**.');
        await v.focus('button.single_add_to_cart_button', { label: 'Straight to checkout', side: 'right', pad: 6, hold: 1.2 });
        await clickAndWaitUrl(v, 'button.single_add_to_cart_button', /checkout-2/, { timeout: 60000 });
        await v.hold(0.6, { note: 'checkout' });
      },
    },

    // 6 ------------------------------------------------------------------ checkout
    {
      kicker: 'Checkout',
      title: 'One line at *checkout*',
      sub: 'Contents and discount as short rows.',
      before: async (v) => { log('removed takes', JSON.stringify(wpJson(php.removeTakes))); await freshCartWithBundle(v); },
      run: async (v) => {
        await asUser(v, CUSTOMER);
        await v.goto(CHECKOUT, { hold: 0.5 });
        const item = '.wc-block-components-order-summary-item';
        await v.say('The order holds **one bundle line** at the bundle price.');
        await v.zoom(`${item} .wc-block-components-order-summary-item__description`, { scale: 1.85 });
        await v.say('Contents and discount sit as **short rows** under the name.');
        await v.focus(await unionRect(v, `${item} .arraysubs-subscription-meta--bundle-contents, ${item} .arraysubs-subscription-meta--bundle-discount`, 4), { label: 'Bundle contents and discount', side: 'left', hold: 1.7 });
        await v.say('Today\'s payment covers December; next charge **January 1**.');
        await v.focus(`${item} .arraysubs-subscription-meta--next-charge`, { label: 'Synced billing day', side: 'left', pad: 4, hold: 1.4 });
        await v.unfocus();
        await v.zoomOut();
        await v.say('She pays by **bank transfer** and places the order.', { hold: 1.6 });
        await v.focus('.wc-block-checkout__payment-method', { label: 'Direct bank transfer', side: 'top', hold: 1.1 });
        await v.unfocus();
        await clickAndWaitUrl(v, '.wc-block-components-checkout-place-order-button', /order-received/);
        await v.scrollTo('.wc-block-order-confirmation-totals__table', { block: 'center' });
        await v.say('The order: one paid bundle line, then **each item at $0.00**.');
        await v.zoom('.wc-block-order-confirmation-totals__table', { scale: 1.45 });
        await v.focus('.wc-block-order-confirmation-totals__table tbody', { label: 'What ships each month', side: 'top', hold: 1.6 });
        await v.unfocus();
        await v.zoomOut();
      },
    },

    // 7 ------------------------------------------------------------------ My Account
    {
      kicker: 'My Account',
      title: 'Clear in *My Account*',
      sub: 'A $0.00 plan that keeps access.',
      before: async () => {
        // The store confirms the bank transfer (as an admin would), so the subscription starts.
        const t = take();
        if (t.order) wpJson(`$o = wc_get_order(${t.order}); if ($o && $o->has_status(['on-hold','pending'])) { $o->update_status('processing', 'Bank transfer received.'); } echo json_encode(['s'=>$o ? $o->get_status() : '']);`);
        log('take', JSON.stringify(take()));
      },
      run: async (v) => {
        const t = take();
        const child = t.kids[0];
        await asUser(v, CUSTOMER);
        await v.goto('/my-account/as-subscriptions/', { hold: 0.4 });
        const card = `.arraysubs-subscription-card:has(#arraysubs-card-title-${child})`;
        await v.scrollTo(card, { block: 'center' });
        await v.say('The membership inside gets its own **$0.00 subscription**.');
        await v.zoom(card, { scale: 1.35, pad: 30 });
        await v.focus(await unionRect(v, `${card} .arraysubs-subscription-card__header, ${card} .arraysubs-subscription-card__facts`, 6), { label: 'Included in the bundle, $0.00', side: 'right', hold: 1.7 });
        await v.say('So its **plan features** keep working while the bundle is active.');
        await v.focus(`${card} .arraysubs-subscription-card__access`, { label: 'Membership access', side: 'bottom', hold: 1.5 });
        await v.unfocus();
        await v.zoomOut();
        await v.click(`${card} .arraysubspro-bundle-child-parent a`, { nav: true, hold: 0.4 });
        await v.scrollTo('tr:has-text("Bundle Contents")', { block: 'center' });
        await v.say('The bundle lists **what is inside**, the discount and the next payment.');
        const rowsRect = await v.eval(() => {
          const rows = [...document.querySelectorAll('tr')].filter((r) => /Next Payment|Recurring Amount|Payment Method|Bundle Contents|Included/.test(r.innerText) && r.getClientRects().length && r.querySelectorAll('tr').length === 0);
          let x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity;
          for (const e of rows) { const r = e.getBoundingClientRect(); x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom); }
          return { x: x1, y: y1, w: x2 - x1, h: y2 - y1 };
        });
        await v.zoom(rowsRect, { scale: 1.45, pad: 30 });
        await v.focus('tr:has-text("Bundle Contents")', { label: 'Contents and discount', side: 'right', hold: 1.7 });
        await v.focus('tr:has-text("Next Payment")', { label: 'Synced: January 1', side: 'right', hold: 1.3 });
        await v.unfocus();
        await v.zoomOut();
      },
    },

    // 8 ------------------------------------------------------------------ admin subscription
    {
      kicker: 'Admin',
      title: 'The bundle *in admin*',
      sub: 'Frozen contents and the linked plan.',
      run: async (v) => {
        const t = take();
        await asUser(v, 1);
        await v.goto(`${SUBS}/detail/${t.sub}`, { hold: 0.4 });
        await v.say('One subscription bills the **whole kit**.');
        await v.focus('.arraysubs-sd__stats', { label: '$67.15 every month, next on Jan 1', side: 'bottom', hold: 1.7 });
        await v.unfocus();
        const panel = 'section.arraysubs-sd__panel:has(h3:has-text("Bundle Contents"))';
        await v.scrollTo(panel, { block: 'start' });
        await v.say('**Bundle Contents**: what ships with every renewal.');
        await v.zoom(panel, { scale: 1.4 });
        await v.focus(`${panel} table`, { label: 'Items, discount and total', side: 'top', hold: 1.7 });
        await v.focus(`${panel} .arraysubs-sd__links-list`, { label: 'Included subscription', side: 'top', pad: 6, hold: 1.1 });
        await v.unfocus();
        // Open the included subscription. Its stat row also shows "Total paid" (see notes: product bug),
        // so the camera lands framed on its own figures (Recurring amount, Next payment).
        const child = t.kids[0];
        const p = await v.moveTo(`${panel} .arraysubs-sd__links-list a`);
        await v.animate(0.1, (k) => { v.cursor.press = k; });
        v.ripple = { x: p.x, y: p.y, p: 0.02 };
        await v.transition(async () => {
          await v.page.mouse.click(p.x, p.y);
          await v.frame.waitForFunction((id) => location.hash.includes(`/detail/${id}`), child, { timeout: 30000 });
          await v.frame.locator('section.arraysubs-sd__panel:has(h3:has-text("Subscription Bundle"))').first().waitFor({ state: 'visible', timeout: 30000 });
          await v.settle();
          await v.eval(() => { document.scrollingElement.scrollTop = 0; });
          const r = await v.eval(() => {
            const s = [...document.querySelectorAll('.arraysubs-sd__stats > .arraysubs-sd__stat')].slice(0, 2).map((e) => e.getBoundingClientRect());
            return { x: s[0].left, y: s[0].top, w: s[1].right - s[0].left, h: s[0].height };
          });
          // right edge just past the second card, so the third card (Total paid) stays out of frame
          v.cam = camAt(r.x + r.w + 10 - W / 2.0, r.y - 200, 2.0);
          v.cursor.o = 0;
          await v.apply();
        }, { fade: 0.35, during: (k) => { v.cursor.press = 1 - k; v.ripple.p = Math.max(0.02, k); } });
        v.cursor.press = 0;
        v.ripple = null;
        await v.say('The included plan bills **$0.00** and renews with the bundle.');
        await v.focus(await unionRect(v, '.arraysubs-sd__stats > .arraysubs-sd__stat:nth-child(-n+2)', 0), { label: '$0.00, renews Jan 1', side: 'bottom', pad: 4, hold: 1.6 });
        await v.unfocus();
        const part = 'section.arraysubs-sd__panel:has(h3:has-text("Subscription Bundle"))';
        await v.act(async (f) => {
          // panel near the middle; camera window from the schedule rows down to just below the panel
          await f.locator(part).first().evaluate((el) => { const r = el.getBoundingClientRect(); document.scrollingElement.scrollTop += r.top - 330; });
          const pr = await v.appRect(part);
          v.cam = camAt(pr.x - 20, pr.y - 200, 2.0);
          await v.apply();
        });
        await v.focus(await textRect(v, part, 8), { label: 'Linked to the bundle', side: 'right', hold: 1.5 });
        await v.unfocus();
      },
    },

    // 9 ------------------------------------------------------------------ renewal order = packing list
    {
      kicker: 'Admin',
      title: 'Every renewal, a *packing list*',
      sub: 'One paid line, items at $0.00.',
      before: async () => {
        // The first renewal order (normally raised by the scheduler before January 1), made off-camera.
        const t = take();
        if (!t.renewal && t.sub) {
          log('renewal', JSON.stringify(wpJson(`$p = new \\ArraySubs\\Features\\RecurringBilling\\Services\\RenewalProcessor(); $ok = $p->createRenewalInvoice(${t.sub}); echo json_encode(['ok'=>$ok, 'orders'=>get_post_meta(${t.sub}, '_order_ids', true)]);`)));
        }
        log('take', JSON.stringify(take()));
      },
      run: async (v) => {
        const t = take();
        await asUser(v, 1);
        await v.goto(`/wp-admin/admin.php?page=wc-orders&action=edit&id=${t.renewal}`, { hold: 0.3 });
        await v.scrollTo('#woocommerce-order-items', { block: 'start' });
        await v.say('Each renewal creates a **new order** from the bundle.');
        await v.focus('#woocommerce-order-notes li.note:has-text("Renewal order for subscription")', { label: 'Renewal order, cycle 2', side: 'left', hold: 1.6 });
        await v.unfocus();
        await v.say('One paid bundle line, then **each item at $0.00**: a packing list.');
        const items = await unionRect(v, '#order_line_items tr.item', 0);
        await v.zoom(items, { scale: 1.4 });
        await v.focus(await unionRect(v, '#order_line_items tr.item:not(:first-child)', 2), { label: 'Items to pack', side: 'bottom', hold: 1.8 });
        await v.unfocus();
        await v.zoomOut();
      },
    },
  ],
};
