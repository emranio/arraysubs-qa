// Subscription bundles — product tour (Pro).
// Story: a draft product becomes "Morning Ritual Bundle" (type, schedule, products, discount,
// renewal sync, publish) -> the customer sees the product page, cart and checkout -> My Account
// shows the bundle and its included membership -> admin sees the frozen contents, the included
// subscription and the order that doubles as a packing list.
// Store changes are logged in notes/subscription-bundles.md.
import { wp, wpJson } from '../lib/director.mjs';

const PRODUCT = 32209; // "Morning Ritual Bundle" (draft created for this video)
const CUSTOMER = 1115; // Grace Watson (block 1105-1165)
const EDIT = `/wp-admin/post.php?post=${PRODUCT}&action=edit`;
const SUBS = '/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions';
const PICKS = [
  ['coffee', 'Single-origin Coffee'],
  ['coffee', 'Single-origin Coffee'],
  ['mug', 'Ceramic Member Mug'],
  ['starter', 'Starter Membership'],
];

const log = (...a) => console.log('  [sb]', ...a);
const productUrl = () => wp(['eval', `echo get_permalink(${PRODUCT});`]).replace('http://localhost:10013', '');

const php = {
  // Back to a plain draft (simple product, no bundle data), so chapter 1 starts from scratch.
  resetProduct: `
    $id = ${PRODUCT};
    foreach (['_arraysubs_bundle_config','_arraysubs_subscription_bundle','_is_subscription','_subscription_period','_subscription_interval','_subscription_length','_trial_length','_trial_period','_signup_fee','_arraysubs_subscription_mode','_arraysubs_flexible_periods','_price','_regular_price','_sale_price','_sold_individually','_arraysubs_renewal_sync_enabled'] as $k) { delete_post_meta($id, $k); }
    foreach (get_post_meta($id) as $k => $x) { if (strpos($k, '_arraysubs_renewal_sync') === 0 || strpos($k, '_arraysubs_bundle') === 0) delete_post_meta($id, $k); }
    wp_set_object_terms($id, 'simple', 'product_type');
    wp_update_post(['ID' => $id, 'post_status' => 'draft']);
    wc_delete_product_transients($id);
    clean_post_cache($id);
    echo json_encode(['ok' => 1, 'type' => WC_Product_Factory::get_product_type($id), 'status' => get_post_status($id)]);`,
  emptyCart: `global $wpdb; $wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>'${CUSTOMER}']); delete_user_meta(${CUSTOMER},'_woocommerce_persistent_cart_1'); echo json_encode(['ok'=>1]);`,
  // Every subscription (bundle + included children) and order an earlier take made for the customer.
  removeTakes: `
    $ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids',
      'meta_query'=>[['key'=>'_customer_id','value'=>${CUSTOMER}],['key'=>'_product_id','value'=>${PRODUCT}]]]);
    $done = [];
    foreach ($ids as $sid) {
      $kids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_arraysubs_bundle_parent_subscription','meta_value'=>$sid]);
      $oid = (int) get_post_meta($sid, '_parent_order_id', true);
      foreach ($kids as $k) { wp_delete_post($k, true); }
      if ($oid && ($o = wc_get_order($oid))) { $o->delete(true); }
      wp_delete_post($sid, true);
      $done[] = ['sub'=>$sid, 'kids'=>$kids, 'order'=>$oid];
    }
    echo json_encode($done);`,
  take: `
    $ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>1,'fields'=>'ids','orderby'=>'ID','order'=>'DESC',
      'meta_query'=>[['key'=>'_customer_id','value'=>${CUSTOMER}],['key'=>'_product_id','value'=>${PRODUCT}]]]);
    $sid = (int) ($ids[0] ?? 0);
    $kids = $sid ? get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_arraysubs_bundle_parent_subscription','meta_value'=>$sid]) : [];
    $oid = $sid ? (int) get_post_meta($sid, '_parent_order_id', true) : 0;
    $o = $oid ? wc_get_order($oid) : null;
    echo json_encode(['sub'=>$sid, 'status'=>$sid ? get_post_status($sid) : '', 'kids'=>$kids, 'order'=>$oid, 'ostatus'=>$o ? $o->get_status() : '', 'next'=>$sid ? get_post_meta($sid, '_next_payment_date', true) : '']);`,
};
const take = () => wpJson(php.take);

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

const SEL = {
  modal: '.arraysubspro-bundle-modal-editor',
  trigger: '.arraysubspro-bundle-ajax-select__trigger',
  search: '.arraysubspro-bundle-ajax-select__search input',
  option: (t) => `.arraysubspro-bundle-ajax-select__option:has-text("${t}")`,
  rows: '.arraysubspro-bundle-product-list',
  subtotal: '.arraysubspro-bundle-subtotal',
};

const modalOpen = (v) => v.frame.locator(SEL.modal).isVisible().catch(() => false);
const waitOption = (v, label) => v.frame.locator(SEL.option(label)).first().waitFor({ state: 'visible', timeout: 120000 });

/** Off-camera: rebuild the editor state a chapter starts from (only when re-recording it alone). */
async function ensureEditor(v, stage) {
  if (await modalOpen(v)) return;
  log(`rebuilding editor state: ${stage}`);
  wpJson(php.resetProduct);
  await v.as(1);
  await v.goto(EDIT, { hold: 0 });
  await v.frame.locator('#product-type').selectOption('arraysubs_subscription_bundle');
  await v.frame.locator('.arraysubspro-bundle-configure-btn').click();
  await v.settle();
  if (stage === 'schedule') return;
  for (const [q, label] of PICKS) {
    if (!(await v.frame.locator(SEL.search).count())) await v.frame.locator(SEL.trigger).click();
    await v.frame.locator(SEL.search).fill(q);
    await waitOption(v, label);
    await v.frame.locator(SEL.option(label)).first().click();
    await v.settle();
  }
  await v.frame.locator('.arraysubspro-bundle-products .arraysubspro-bundle-section__title').click();
  if (stage === 'products') return;
  await v.frame.locator('button:has-text("Continue to Discount")').click();
  await v.frame.locator('.arraysubspro-bundle-choice:has-text("Percentage")').click();
  await v.frame.locator('.arraysubspro-bundle-amount__input').fill('15');
  await v.settle();
}

/** Off-camera: put the bundle in the customer's cart (fresh cart). */
async function freshCartWithBundle(v) {
  wpJson(php.emptyCart);
  await v.as(CUSTOMER);
  await v.goto(productUrl(), { hold: 0 });
  await v.frame.locator('button.single_add_to_cart_button').click();
  await v.settle();
}

export default {
  slug: 'subscription-bundles',
  tier: 'Pro',
  intro: { title: 'Subscription *bundles*', sub: 'Sell a fixed set of products as one discounted subscription.' },
  outro: { title: 'One kit. One price. *Every month.*', sub: 'Subscription bundles in ArraySubs Pro.', url: 'subscriptionplugin.pro' },
  teardown: async () => { try { wpJson(php.emptyCart); } catch {} },
  chapters: [
    // 1 ------------------------------------------------------------------ product type + schedule
    {
      kicker: 'Admin',
      title: 'Start with a *product*',
      before: async () => { log('reset', JSON.stringify(wpJson(php.resetProduct))); },
      run: async (v) => {
        await v.as(1);
        await v.goto(EDIT, { hold: 0.4 });
        await v.say('A bundle starts as a normal product: a name, a photo, a category.');
        await v.focus('#titlediv', { label: 'Morning Ritual Bundle', side: 'bottom', hold: 1.4 });
        await v.unfocus();
        await v.scrollTo('#woocommerce-product-data', { block: 'start' });
        await v.say('Pick the **Subscription Bundle** product type.');
        await v.select('#product-type', 'arraysubs_subscription_bundle');
        await v.focus('#product-type', { label: 'Subscription Bundle [ArraySubs]', side: 'right', pad: 6, hold: 1.4 });
        await v.say('The price fields are gone. The products inside **set the price**.');
        await v.focus('.arraysubspro-bundle-panel', { label: 'Not for sale until products are added', side: 'bottom', dim: true, hold: 1.8 });
        await v.unfocus();
        await v.click('.arraysubspro-bundle-configure-btn', { wait: 300 });
        await v.say('One editor, three screens: **products**, **discount** and **renewal sync**.');
        await v.focus('.arraysubspro-bundle-wizard-nav', { label: '3 screens', side: 'bottom', hold: 1.4 });
        await v.say('First, how often the **whole bundle** bills: every month, until cancelled.');
        await v.focus(await unionRect(v, '.arraysubs-container-schedule, .arraysubspro-bundle-grid-3', 6), { label: 'Fixed, every 1 month, no end', side: 'bottom', dim: true, hold: 2.0 });
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
        await v.say('Search your catalog and add products to the kit.');
        await v.click(SEL.trigger, { hold: 0.2 });
        await v.type(SEL.search, 'coffee', { click: false, cps: 10 });
        await v.act(() => waitOption(v, 'Single-origin Coffee'));
        await v.say('Only products that fit a **monthly** bundle are listed.');
        await v.focus('.arraysubspro-bundle-ajax-select__options', { label: 'Prices and Subscription badges', side: 'right', hold: 1.6 });
        await v.click(SEL.option('Single-origin Coffee'), { hold: 0.5 });
        await v.say('Pick it again and the **quantity** goes up. No duplicate row.');
        await v.click(SEL.option('Single-origin Coffee'), { hold: 0.6 });
        await v.type(SEL.search, 'mug', { click: false, cps: 10 });
        await v.act(() => waitOption(v, 'Ceramic Member Mug'));
        await v.click(SEL.option('Ceramic Member Mug'), { hold: 0.4 });
        await v.type(SEL.search, 'starter', { click: false, cps: 10 });
        await v.act(() => waitOption(v, 'Starter Membership'));
        await v.say('A **membership** fits too, because it also bills every month.');
        await v.focus(SEL.option('Starter Membership'), { label: 'Monthly subscription product', side: 'bottom', hold: 1.4 });
        await v.click(SEL.option('Starter Membership'), { hold: 0.3 });
        await v.click('.arraysubspro-bundle-products .arraysubspro-bundle-section__title', { hold: 0.2 });
        await v.scrollTo(SEL.subtotal, { block: 'end' });
        await v.say('Set **how many** of each, and the order customers see them in.');
        await v.focus(SEL.rows, { label: '2 coffees, 1 mug, 1 membership', side: 'top', dim: true, hold: 1.8 });
        await v.zoom('.arraysubspro-bundle-product-row >> nth=0', { scale: 1.7 });
        await v.focus('.arraysubspro-bundle-product-row >> nth=0 >> .arraysubspro-bundle-product-row__qty', { label: 'Qty 1 to 99', side: 'bottom', pad: 6, hold: 1.2 });
        await v.focus('.arraysubspro-bundle-product-row >> nth=0 >> .arraysubspro-bundle-product-row__actions', { label: 'Reorder or remove', side: 'bottom', pad: 6, hold: 1.2 });
        await v.unfocus();
        await v.zoomOut();
        await v.say('The subtotal adds up every line, before the discount.');
        await v.focus(SEL.subtotal, { label: '$79.00 a month', side: 'top', hold: 1.6 });
        await v.unfocus();
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
        await v.click('button:has-text("Continue to Discount")', { hold: 0.4 });
        await v.say('Reward the kit with **one discount**, on every payment.');
        await v.focus('.arraysubspro-bundle-discount .arraysubspro-bundle-alert', { label: 'Renewals included', side: 'bottom', hold: 1.6 });
        await v.unfocus();
        await v.click('.arraysubspro-bundle-choice:has-text("Percentage")', { hold: 0.3 });
        await v.type('.arraysubspro-bundle-amount__input', '15', { cps: 6 });
        await v.say('The price preview updates **while you type**.');
        await v.scrollTo('.arraysubspro-bundle-preview', { block: 'center' });
        await v.zoom('.arraysubspro-bundle-preview', { scale: 1.6 });
        await v.focus('.arraysubspro-bundle-preview__row--total', { label: '$67.15 a month', side: 'top', pad: 6, hold: 1.8 });
        await v.unfocus();
        await v.zoomOut();
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
        await v.click('button:has-text("Continue to Flexible Renewal Sync")', { hold: 0.4 });
        await v.say('Optional: bill every new subscriber of this bundle on **one day**.');
        await v.click('.arraysubspro-bundle-checkbox input', { hold: 0.3 });
        await v.focus('.arraysubspro-bundle-checkbox', { label: 'Own segment plan', side: 'bottom', pad: 4, hold: 1.2 });
        await v.say('The **signup day** decides the first charge: full, prorated or the next cycle.');
        await v.focus(await unionRect(v, '.arraysubspro-bundle-sync__slider, .arraysubspro-bundle-sync__legend', 10), { label: 'Monthly bundles renew on the 1st', side: 'top', dim: true, hold: 2.2 });
        await v.unfocus();
        await v.click('button:has-text("Save Configuration")', { hold: 0.4 });
        await v.scrollTo('.arraysubspro-bundle-editor', { block: 'start' });
        await v.say('Saved: the product now shows the **whole bundle** at a glance.');
        await v.focus('.arraysubspro-bundle-facts', { label: 'Billing, discount, renewal sync', side: 'bottom', hold: 1.6 });
        await v.focus('.arraysubspro-bundle-summary-table', { label: 'Total per payment: $67.15', side: 'top', dim: true, hold: 1.8 });
        v.poster();
        await v.unfocus();
        await v.scrollTo(0);
        await v.say('Publish, and the bundle is **for sale**.');
        await v.click('#publish', { nav: true, wait: 600, hold: 0.4 });
        await v.focus('#submitdiv', { label: 'Published', side: 'left', hold: 1.2 });
        await v.unfocus();
        await dropUnload(v);
      },
    },

    // 5 ------------------------------------------------------------------ product page
    {
      kicker: 'Storefront',
      title: 'A page that *sells the kit*',
      before: async () => { log('removed takes', JSON.stringify(wpJson(php.removeTakes))); wpJson(php.emptyCart); },
      run: async (v) => {
        await v.as(CUSTOMER);
        await v.goto(productUrl(), { hold: 0.5 });
        await v.say('Shoppers see the **saving** first: full price struck out.');
        await v.zoom('.summary .price, .wp-block-woocommerce-product-price', { scale: 1.9 });
        await v.hold(1.6, { note: 'price' });
        await v.zoomOut();
        await v.say('**What\'s included** lists every item, quantity and price.');
        await v.focus('.arraysubspro-bundle-included', { label: 'Fixed contents, in your order', side: 'left', dim: true, hold: 2.0 });
        await v.unfocus();
        await v.say('One button adds the **whole kit**.');
        await v.focus('button.single_add_to_cart_button', { label: 'Subscribe Now', side: 'right', pad: 6, hold: 1.2 });
        await v.click('button.single_add_to_cart_button', { nav: true, wait: 800, hold: 0.5 });
      },
    },

    // 6 ------------------------------------------------------------------ cart + checkout
    {
      kicker: 'Checkout',
      title: 'One tidy *cart line*',
      before: async (v) => { await freshCartWithBundle(v); },
      run: async (v) => {
        await v.as(CUSTOMER);
        await v.goto('/cart-2/', { hold: 0.5 });
        await v.say('The cart holds **one bundle line** at the bundle price.');
        await v.zoom('.wc-block-cart-items__row, .woocommerce-cart-form__cart-item', { scale: 1.5 });
        await v.hold(2.4, { note: 'cart line' });
        await v.zoomOut();
        await v.click('.wc-block-cart__submit-button, a.checkout-button', { nav: true, wait: 800, hold: 0.4 });
        await v.say('Checkout repeats the contents and the discount as short rows.');
        await v.zoom('.wc-block-components-order-summary-item', { scale: 1.7 });
        await v.hold(2.4, { note: 'checkout line' });
        await v.zoomOut();
        await v.click('.wc-block-components-checkout-place-order-button', { nav: true, wait: 1500, hold: 0.4 });
        await v.say('Order placed by bank transfer.');
        await v.hold(1.2, { note: 'order received' });
      },
    },

    // 7 ------------------------------------------------------------------ My Account
    {
      kicker: 'My Account',
      title: 'Clear in *My Account*',
      before: async () => {
        // The store confirms the bank transfer (as an admin would), so the subscription starts.
        const t = take();
        if (t.order) wpJson(`$o = wc_get_order(${t.order}); if ($o && $o->has_status(['on-hold','pending'])) { $o->update_status('processing', 'Bank transfer received.'); } echo json_encode(['s'=>$o ? $o->get_status() : '']);`);
        log('take', JSON.stringify(take()));
      },
      run: async (v) => {
        await v.as(CUSTOMER);
        await v.goto('/my-account/as-subscriptions/', { hold: 0.5 });
        await v.say('Grace sees her bundle in **My Account**.');
        await v.hold(1.0, { note: 'ma list' });
      },
    },

    // 8 ------------------------------------------------------------------ admin outcome
    {
      kicker: 'Admin',
      title: 'Every renewal, a *packing list*',
      run: async (v) => {
        const t = take();
        await v.as(1);
        await v.goto(`${SUBS}/detail/${t.sub}`, { hold: 0.5 });
        await v.say('In admin, the subscription keeps the **contents it was bought with**.');
        await v.hold(1.0, { note: 'sd' });
        await v.goto(`/wp-admin/admin.php?page=wc-orders&action=edit&id=${t.order}`, { hold: 0.5 });
        await v.say('The order: one paid bundle line, then every item at $0.00.');
        await v.hold(1.0, { note: 'order' });
      },
    },
  ],
};
