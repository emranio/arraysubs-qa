// Flexible billing — product tour (Free + Pro).
// Story: plans list -> plan wizard creates "Herbal Tea Club" -> variable tiers -> customer-chosen
// schedule (Pro) -> fixed end date (Pro) -> store-wide rules -> trial + sign-up fee checkout ->
// terms locked in (My Account + admin).
// Store changes are logged in notes/flexible-billing.md.
import { wp, wpJson } from '../lib/director.mjs';

const CUSTOMER = 866; // Freya Kelly (block 861-921)
const PLANS = '/wp-admin/admin.php?page=arraysubs-mainadmin#/plans';
const FRESH = 25875; // Fresh Roast Coffee Plan (Full Flexible, week/month, max 12)
const CLUB = 25877; // Coffee Club Membership (variable: Monthly 7-day trial / Annual / Lifetime)
const SEASON = 31767; // Studio Season Pass (fixed end date, annual cutoff 12-31, renew)
const WIZ_TITLE = 'Herbal Tea Club';
const TEA_IMG = 25742; // Herbal Tea Collection image
const editBilling = (id) => `/wp-admin/post.php?post=${id}&action=edit&arraysubs_from=plans&arraysubs_edit=billing`;
const productUrl = (id) => wp(['eval', `echo get_permalink(${Number(id)});`]).replace('http://localhost:10013', '');

const php = {
  emptyCart: `global $wpdb; $wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>'${CUSTOMER}']); delete_user_meta(${CUSTOMER},'_woocommerce_persistent_cart_1'); echo json_encode(['ok'=>1]);`,
  wizProducts: `$q=new WP_Query(['post_type'=>'product','post_status'=>['publish','draft','pending','private'],'title'=>'${WIZ_TITLE}','posts_per_page'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'DESC']); echo json_encode($q->posts);`,
};

const log = (...a) => console.log('  [fb]', ...a);
const emptyCart = () => wpJson(php.emptyCart);
const wizProducts = () => wpJson(php.wizProducts);
const latestWiz = () => wizProducts()[0] || 0;

/** Trash every earlier "Herbal Tea Club" plan made by this scene (so the list looks fresh). */
function trashWizProducts() {
  const ids = wizProducts();
  if (ids.length) wp(['eval', `foreach (${JSON.stringify(ids)} as $id) { wp_trash_post((int) $id); } echo 'ok';`]);
  if (ids.length) log('trashed earlier wizard products', ids.join(','));
}

/** Remove the subscriptions + orders that earlier takes created for the customer on a product. */
function removeEarlierTakes(productId) {
  const out = wpJson(`
    $ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids',
      'meta_query'=>[['key'=>'_customer_id','value'=>${CUSTOMER}],['key'=>'_product_id','value'=>${Number(productId)}]]]);
    $done = [];
    foreach ($ids as $sid) {
      $oid = (int) get_post_meta($sid, '_parent_order_id', true);
      if ($oid && ($o = wc_get_order($oid))) { $o->delete(true); }
      wp_delete_post($sid, true);
      $done[] = [$sid, $oid];
    }
    echo json_encode($done);`);
  if (out.length) log('removed earlier takes', JSON.stringify(out));
}

/** Product editor pages warn before leaving; drop that so the next goto is clean. */
async function dropUnload(v) {
  await v.eval(() => {
    window.onbeforeunload = null;
    if (window.jQuery) { window.jQuery(window).off('beforeunload'); }
  }).catch(() => {});
}

/** Union rect (page px) of every element matching a CSS selector inside the store frame. */
async function unionRect(v, css, pad = 0) {
  await v.frame.locator(css).first().waitFor({ state: 'visible', timeout: 12000 }).catch(() => {});
  return v.eval(({ css, pad }) => {
    const els = [...document.querySelectorAll(css)].filter((e) => e.getClientRects().length);
    if (!els.length) throw new Error('no match ' + css);
    let x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity;
    for (const e of els) { const r = e.getBoundingClientRect(); x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom); }
    return { x: x1 - pad, y: y1 - pad, w: x2 - x1 + pad * 2, h: y2 - y1 + pad * 2 };
  }, { css, pad });
}

/** Top part (max h px) of an element, for tall cards. */
async function topSlice(v, sel, maxH = 420, pad = 0) {
  const l = v.frame.locator(sel).first();
  await l.waitFor({ state: 'visible', timeout: 12000 });
  return l.evaluate((el, { maxH, pad }) => {
    const r = el.getBoundingClientRect();
    return { x: r.left - pad, y: r.top - pad, w: r.width + pad * 2, h: Math.min(r.height, maxH) + pad * 2 };
  }, { maxH, pad });
}

/** Union rect of settings switches (by id) plus the help box under each one. */
async function toggleRect(v, ids, pad = 8) {
  await v.frame.locator('#' + ids[0]).waitFor({ state: 'visible', timeout: 12000 });
  return v.eval(({ ids, pad }) => {
    let x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity;
    const add = (e) => { const r = e.getBoundingClientRect(); if (!r.height) return; x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom); };
    for (const id of ids) {
      const w = document.getElementById(id).closest('.field-warper');
      add(w);
      const n = w.nextElementSibling;
      if (n && n.classList.contains('arraysubs-fb-alert-wrapper')) add(n);
    }
    return { x: x1 - pad, y: y1 - pad, w: x2 - x1 + pad * 2, h: y2 - y1 + pad * 2 };
  }, { ids, pad });
}

/** Rect of a table column (header + first n rows). */
async function columnRect(v, headerText, rows = 3) {
  return v.eval(({ headerText, rows }) => {
    const ths = [...document.querySelectorAll('thead th')];
    const i = ths.findIndex((t) => t.innerText.trim().startsWith(headerText));
    const th = ths[i].getBoundingClientRect();
    const trs = [...document.querySelectorAll('tbody tr')].slice(0, rows);
    const last = trs[trs.length - 1].children[i].getBoundingClientRect();
    return { x: th.left, y: th.top, w: th.width, h: last.bottom - th.top };
  }, { headerText, rows });
}

async function wizNext(v) {
  await v.click('.arraysubs-quick-product button.button-primary:has-text("Next")', { hold: 0.2 });
}

export default {
  slug: 'flexible-billing',
  tier: 'Free + Pro',
  intro: { title: 'Billing that fits the way *you sell*', sub: 'Schedules, trials, sign-up fees, plan tiers and terms your customers choose.' },
  outro: { title: 'Sell plans, not *workarounds*.', sub: 'Flexible billing in ArraySubs. Most of it is free; Pro adds customer choice.', url: 'subscriptionplugin.pro' },

  setup: async () => {
    // Studio Season Pass is the fixed-date demo product; it must be live.
    wp(['eval', `if (get_post_status(${SEASON}) !== 'publish') { wp_untrash_post(${SEASON}); wp_update_post(['ID'=>${SEASON},'post_status'=>'publish']); } echo get_post_status(${SEASON});`]);
    emptyCart();
  },

  teardown: async () => {
    emptyCart();
  },

  chapters: [
    // 1 ------------------------------------------------------------------ plans list
    {
      kicker: 'Admin',
      title: 'Every plan on *one screen*',
      sub: 'Subscription Plans, free in ArraySubs.',
      before: async () => { trashWizProducts(); },
      run: async (v) => {
        await v.as(1);
        await v.goto(PLANS, { hold: 0.6 });
        await v.say('**Subscription Plans** lists every plan in your store.');
        await v.focus(await unionRect(v, 'tbody tr:nth-child(-n+3) .arraysubs-plan-name', 6), { label: 'Simple, variable, box and bundle plans', dim: true });
        await v.unfocus();
        await v.say('Price and billing terms sit in **one column**.');
        await v.zoom(await columnRect(v, 'Price & billing', 3), { scale: 1.75, pad: 40 });
        await v.hold(1.6, { note: 'billing column' });
        await v.zoomOut();
        await v.say('Filter by status and type to find a plan fast.');
        await v.click('button.arraysubs-dl-status-tab-item:has-text("Published")', { hold: 0.2 });
        await v.select('label.arraysubs-dl-filter select', { label: 'Variable Subscription' });
        await v.hold(0.8, { note: 'filtered' });
        await v.hover('tr:has-text("Coffee Club Membership") .arraysubs-plan-title', { hold: 0.4 });
        await v.focus('tr:has-text("Coffee Club Membership") .arraysubs-plan-billing', { label: 'Billing varies by variation' });
        await v.focus('tr:has-text("Coffee Club Membership") .arraysubs-plan-actions a:has-text("Edit subscription billings")', { label: 'Opens the billing tab', pad: 6, side: 'bottom' });
        await v.say('**Duplicate** a plan as a draft to test a new price.');
        await v.focus('tr:has-text("Coffee Club Membership") .arraysubs-plan-actions button:has-text("Duplicate")', { label: 'Subscribers keep their price', pad: 6, side: 'bottom' });
        await v.unfocus();
      },
    },

    // 2 ------------------------------------------------------------------ plan wizard
    {
      kicker: 'Plan wizard',
      title: 'A new plan in *7 steps*',
      sub: 'Type, details, schedule, trial, end date, shipping, review.',
      before: async () => { trashWizProducts(); },
      run: async (v) => {
        await v.as(1);
        await v.goto(PLANS, { hold: 0.4 });
        await v.focus('button:has-text("Add new plan")', { label: 'Start the plan wizard', pad: 6, hold: 1.2 });
        await v.click('button:has-text("Add new plan")', { hold: 0.3 });
        v.captionPos('bc');
        await v.say('The wizard walks through **seven short steps**.');
        await v.focus('.boxed-fullscreen-modal__progress', { pad: 4, hold: 1.4 });
        await v.focus('label.arraysubs-quick-product__type-main:has-text("Simple subscription")', { label: 'A simple subscription', pad: 4, hold: 1.4 });
        await wizNext(v);

        // details
        await v.say('Only the **title and price** are required.');
        await v.type('.arraysubs-quick-product input.arraysubs-fb-input >> visible=true >> nth=0', WIZ_TITLE, { cps: 16 });
        await v.type('.arraysubs-quick-product input.arraysubs-fb-input >> visible=true >> nth=1', '18', { cps: 8 });
        await v.hold(0.6, { note: 'details' });
        await wizNext(v);

        // recurring billing
        await v.say('Fixed schedule, or let **customers choose** (Pro).');
        await v.focus(await unionRect(v, '.arraysubs-quick-product label.arraysubs-fb-grid-select-item', 2), { label: 'Fixed, Flexible Length, Full Flexible' });
        await v.unfocus();
        await v.say('Bill every **1 to 12** days, weeks, months or years.');
        await v.focus(await unionRect(v, '.arraysubs-quick-product .field-warper:has(.arraysubs-fb-select-control), .arraysubs-quick-product .field-warper:has(input.arraysubs-fb-input)', 6), { label: 'Every 1 month, until cancelled', hold: 1.6 });
        await v.focus('.arraysubs-quick-product .field-warper:has(input.arraysubs-fb-input) >> visible=true >> nth=1', { label: '0 = no end. 12 = twelve payments', pad: 6, hold: 1.8 });
        await v.unfocus();
        await wizNext(v);

        // trial & signup
        await v.say('Add a **free trial** and a one-time **sign-up fee**.');
        await v.type('.arraysubs-quick-product input.arraysubs-fb-input >> visible=true >> nth=0', '14', { cps: 8 });
        await v.type('.arraysubs-quick-product input.arraysubs-fb-input >> visible=true >> nth=1', '5', { cps: 8 });
        await v.focus(await unionRect(v, '.arraysubs-quick-product .field-warper:has(.arraysubs-fb-select-control), .arraysubs-quick-product .field-warper:has(input.arraysubs-fb-input)', 6), { label: '14 days free, $5 to join', hold: 1.6 });
        await v.unfocus();
        await wizNext(v);

        // fixed membership
        await v.say('Pro: end every membership on **one calendar date**.');
        await v.focus('.arraysubs-quick-product label:has-text("Use fixed end date")', { label: 'Optional. Shown later', pad: 8, side: 'right', hold: 1.4 });
        await v.unfocus();
        await wizNext(v);

        // shipping
        await v.say('Pro: charge shipping on **every renewal** or once.');
        await v.focus(await unionRect(v, '.arraysubs-quick-product label.arraysubs-fb-grid-select-item', 2), { label: 'Recurring or one-time shipping' });
        await v.unfocus();
        await v.type('.arraysubs-quick-product input[placeholder*="Initial shipping"]', '4.95', { cps: 10 });
        await v.hold(0.6, { note: 'shipping' });
        await wizNext(v);

        // review
        await v.say('Review it all, with **Edit** links, before anything is created.');
        await v.focus('.arraysubs-quick-product h3:has-text("Product details") button:has-text("Edit")', { label: 'Jump back to any step', pad: 6, hold: 1.3 });
        await v.unfocus();
        await v.scrollTo('.arraysubs-quick-product h3:has-text("Trial & signup")', { block: 'start' });
        await v.hold(1.2, { note: 'review trial' });
        await v.click('.arraysubs-quick-product button.button-primary:has-text("Create product")', { hold: 0.2 });
        await v.focus('.arraysubs-confirm-dialog', { pad: 6, hold: 1.0 });
        await v.hover('.arraysubs-confirm-dialog__actions button.button-primary', { hold: 0.2 });
        await v.act(async (f) => {
          await f.locator('.arraysubs-confirm-dialog__actions button.button-primary').first().click();
          await f.locator('.arraysubs-quick-product__success').waitFor({ state: 'visible', timeout: 150000 });
          await new Promise((r) => setTimeout(r, 600));
        });
        await v.say('Published. Open the page, the editor or member access.');
        await v.focus('.arraysubs-quick-product__links', { pad: 8, hold: 2.0 });
        await v.unfocus();

        // off camera: give the new plan an image, a short line and a category, like a merchant would
        const id = latestWiz();
        if (!id) throw new Error('wizard product not found');
        wp(['eval', `$p = wc_get_product(${id}); $p->set_image_id(${TEA_IMG}); $p->set_short_description('Loose-leaf herbal tea, a new calming blend in every box.'); $p->set_category_ids([19]); $p->save(); echo $p->get_id();`]);
        log('wizard product', id);
      },
    },

    // 3 ------------------------------------------------------------------ variable tiers
    {
      kicker: 'Variable plans',
      title: 'Monthly, yearly and *lifetime*',
      sub: 'One product, one page, a plan for everyone. Free.',
      run: async (v) => {
        await v.as(1);
        await v.goto(editBilling(CLUB), { hold: 0.5 });
        await v.say('A variable product: **each variation** is its own plan.');
        await v.focus(await unionRect(v, '.woocommerce_variation > h3', 2), { label: 'Monthly, Annual, Lifetime', dim: true });
        await v.click('.woocommerce_variation:has-text("#25879") a.edit_variation', { wait: 700 });
        await v.scrollTo('.woocommerce_variation:has-text("#25879") .arraysubs-variation-fields', { block: 'start', offset: -20 });
        await v.say('Each one gets its own **price, schedule, trial and fee**.');
        await v.focus(await topSlice(v, '.woocommerce_variation .arraysubs-variation-fields >> visible=true', 400, 4), { label: 'Subscription Billings per variation', side: 'left', hold: 1.5 });
        await v.scrollTo('.woocommerce_variation:has-text("#25879") input[name="_trial_length[0]"]', { block: 'center' });
        await v.focus(await unionRect(v, 'input[name="_trial_length[0]"], select[name="_trial_period[0]"]', 6), { label: 'Monthly gets a 7-day trial', hold: 1.5 });
        await v.unfocus();
        await v.scrollTo('.woocommerce_variation:has-text("#25879") .arraysubs-product-helper-links__input', { block: 'center' });
        await v.say('Each variation also gets **ready checkout links**.');
        await v.focus(await unionRect(v, '.woocommerce_variation .arraysubs-product-helper-links__input', 6), { label: 'Add-to-cart and one-click checkout', hold: 1.8 });
        await v.unfocus();
        await dropUnload(v);

        await v.as(CUSTOMER);
        await v.goto(productUrl(CLUB), { hold: 0.5 });
        await v.say('Shoppers pick a plan on **one page**.');
        await v.select('select#plan', { label: 'Monthly' });
        await v.focus('.woocommerce-variation-price', { label: '$12 / month', pad: 8, hold: 1.2 });
        await v.focus('.arraysubs-variable-subscription-info', { label: 'Monthly includes a 7-day trial', pad: 8, hold: 1.6 });
        await v.unfocus();
        await v.select('select#plan', { label: 'Annual' });
        await v.focus('.woocommerce-variation-price', { label: '$120 / year', pad: 8, hold: 1.3 });
        await v.select('select#plan', { label: 'Lifetime' });
        await v.focus('.woocommerce-variation-price', { label: 'Pay once, never renews', pad: 8, hold: 1.5 });
        await v.unfocus();
      },
    },

    // 4 ------------------------------------------------------------------ customer choice (Pro)
    {
      kicker: 'Pro · Customer choice',
      title: 'Customers set *their own* cycle',
      sub: 'Full Flexible: how often and how many payments.',
      before: async () => { emptyCart(); removeEarlierTakes(FRESH); },
      run: async (v) => {
        await v.as(1);
        await v.goto(editBilling(FRESH), { hold: 0.4 });
        await v.say('**Full Flexible** lets the shopper pick the period and length.');
        await v.focus(await unionRect(v, '.arraysubs-flexsub-toggle__btn', 4), { label: 'Fixed, Flexible Length, Full Flexible', hold: 1.6 });
        await v.focus(await unionRect(v, '.arraysubs-flexsub-periods__option', 6), { label: 'Allowed: Week and Month', hold: 1.5 });
        await v.focus('#_subscription_length', { label: 'Up to 12 payments', pad: 6, hold: 1.3 });
        await v.unfocus();
        await dropUnload(v);

        await v.as(CUSTOMER);
        await v.goto(productUrl(FRESH), { hold: 0.5 });
        await v.say('The choice sits right in the **add-to-cart form**.');
        await v.focus('.arraysubs-flexible-options', { label: 'Billing period and length', pad: 6, hold: 1.3 });
        await v.select('select[name=arraysubs_flexible_period]', 'week');
        await v.say('Pick **Week** and the price now reads "/ week".');
        await v.focus('.wp-block-woocommerce-product-price, .summary .price', { label: 'Same price, new period', pad: 8, hold: 1.5 });
        await v.unfocus();
        await v.type('input[name=arraysubs_flexible_length]', '8', { cps: 6 });
        await v.say('Eight weekly payments, then it ends on its own.');
        await v.click('button.single_add_to_cart_button', { nav: true, wait: 800, hold: 0.3 });
        await v.say('Checkout repeats the choice: **every week**, 8 cycles.');
        await v.zoom('.wc-block-components-order-summary-item', { scale: 1.9, pad: 30 });
        await v.hold(2.2, { note: 'checkout fresh' });
        await v.zoomOut();
        await v.click('.wc-block-components-checkout-place-order-button', { nav: true, wait: 1500, hold: 0.4 });
        await v.say('Order placed on the customer\'s own schedule.');
        await v.hold(1.0, { note: 'order received fresh' });
      },
    },

    // 5 ------------------------------------------------------------------ fixed date (Pro)
    {
      kicker: 'Pro · Fixed dates',
      title: 'One end date for *everyone*',
      sub: 'Seasons, school years and clubs.',
      run: async (v) => {
        await v.as(1);
        await v.goto(editBilling(SEASON), { hold: 0.3 });
        await v.scrollTo('.arraysubs-fixed-end-date-section', { block: 'start', offset: -30 });
        await v.say('Pro: end every membership on the **same date**.');
        await v.focus('.arraysubs-fixed-end-toggle', { label: 'Use fixed end date', pad: 6, hold: 1.2 });
        await v.focus('.arraysubs-fixed-end-recurring', { label: 'Every year on December 31', pad: 6, hold: 1.5 });
        await v.say('Open and close **enrollment**, and renew into the next period.');
        await v.focus(await unionRect(v, '.arraysubs-fixed-end-date-section .arraysubs-fixed-end-fields > .arraysubs-variation-row:nth-last-child(-n+2)', 6), { label: 'Enrollment window and period end', hold: 1.8 });
        await v.unfocus();
        await dropUnload(v);

        await v.as(CUSTOMER);
        await v.goto(productUrl(SEASON), { hold: 0.5 });
        await v.say('Shoppers see the end date **before they pay**.');
        await v.focus('.arraysubs-fixed-period-info', { label: 'Ends Dec 31, renews, enrollment dates', pad: 10, dim: true, hold: 2.2 });
        await v.unfocus();
        await v.say('Outside the window the product cannot be bought.');
        await v.hold(0.6, { note: 'season page' });
      },
    },

    // 6 ------------------------------------------------------------------ store-wide rules
    {
      kicker: 'Settings',
      title: 'Rules for *every plan*',
      sub: 'Cart rules, one-click checkout and trial rules. Free.',
      run: async (v) => {
        await v.as(1);
        await v.goto('/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general', { hold: 0.4 });
        await v.say('Store-wide rules live in **Settings, General**.');
        await v.focus(await toggleRect(v, ['multiple_subscriptions-allow_multiple_in_cart', 'multiple_subscriptions-one_per_customer']), { label: 'What can share a cart', side: 'right', hold: 1.6 });
        await v.unfocus();
        await v.scrollTo('#multiple_subscriptions-allow_different_cycles', { block: 'center' });
        await v.focus(await toggleRect(v, ['multiple_subscriptions-allow_mixed_cart', 'multiple_subscriptions-allow_different_cycles']), { label: 'Mixed carts and billing cycles', side: 'right', hold: 1.6 });
        await v.unfocus();
        await v.scrollTo('label:has-text("One Click Checkout")', { block: 'center' });
        await v.say('**One Click Checkout** sends shoppers straight to checkout.');
        await v.focus('.field-warper:has(> label:has-text("One Click Checkout")), .field-warper:has(label:text-is("One Click Checkout"))', { label: 'Skip the cart', pad: 8, side: 'right', hold: 1.4 });
        await v.unfocus();
        await v.scrollTo('#trials-one_trial_per_customer', { block: 'center' });
        await v.say('Trials can require a card and stay **one per customer**.');
        await v.focus(await toggleRect(v, ['trials-require_payment_method', 'trials-one_trial_per_customer']), { label: 'Trial rules', side: 'right', hold: 1.6 });
        await v.unfocus();
      },
    },

    // 7 ------------------------------------------------------------------ trial + fee checkout
    {
      kicker: 'Storefront',
      title: 'Trial and fee, *spelled out*',
      sub: 'The new plan, from product page to order.',
      before: async () => { emptyCart(); const id = latestWiz(); if (id) removeEarlierTakes(id); },
      run: async (v) => {
        const id = latestWiz();
        if (!id) throw new Error('no wizard product: record chapter 2 first');
        await v.as(CUSTOMER);
        await v.goto(productUrl(id), { hold: 0.5 });
        await v.say('The plan from the wizard, live in the store.');
        await v.focus(await unionRect(v, '.wp-block-woocommerce-product-price, .arraysubs-product-info, .arraysubspro-shipping-info', 8), { label: 'Price, trial and sign-up fee', dim: true, hold: 2.0 });
        await v.unfocus();
        await v.click('button.single_add_to_cart_button', { nav: true, wait: 800, hold: 0.3 });
        await v.say('Checkout lists the terms under the product name.');
        await v.zoom('.wc-block-components-order-summary-item', { scale: 1.9, pad: 30 });
        await v.hold(2.4, { note: 'checkout tea item' });
        await v.zoomOut();
        await v.scrollTo('.wc-block-components-totals-footer-item', { block: 'center' });
        await v.say('Today: the **sign-up fee** and shipping. The trial costs nothing.');
        await v.zoom(await unionRect(v, '.wp-block-woocommerce-checkout-order-summary-totals-block, .wc-block-components-totals-wrapper, .wc-block-components-totals-footer-item', 4), { scale: 1.8, pad: 30 });
        await v.hold(2.2, { note: 'checkout totals' });
        v.poster();
        await v.zoomOut();
        await v.click('.wc-block-components-checkout-place-order-button', { nav: true, wait: 1500, hold: 0.4 });
        await v.say('Order placed. The subscription starts with its trial.');
        await v.hold(1.0, { note: 'order received tea' });
      },
    },

    // 8 ------------------------------------------------------------------ locked in
    {
      kicker: 'Result',
      title: 'Terms *locked in*',
      sub: 'What the customer and the store see next.',
      before: async () => {
        // The store confirms the two bank transfers (as an admin would), so both subscriptions start.
        const out = wpJson(`
          $res = [];
          $ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids',
            'meta_query'=>[['key'=>'_customer_id','value'=>${CUSTOMER}],['key'=>'_product_id','value'=>[${FRESH}, ${latestWiz()}],'compare'=>'IN']]]);
          foreach ($ids as $sid) { $oid = (int) get_post_meta($sid, '_parent_order_id', true); $o = wc_get_order($oid);
            if ($o && $o->has_status(['on-hold','pending'])) { $o->update_status('processing', 'Bank transfer received.'); }
            $res[] = [$sid, $oid, get_post_status($sid)]; }
          echo json_encode($res);`);
        log('confirmed transfers', JSON.stringify(out));
      },
      run: async (v) => {
        await v.as(CUSTOMER);
        await v.goto('/my-account/as-subscriptions/', { hold: 0.5 });
        await v.say('In My Account, each plan keeps **its own schedule**.');
        await v.scrollTo('.arraysubs-subscription-card:has-text("Fresh Roast")', { block: 'start' });
        await v.focus(await topSlice(v, '.arraysubs-subscription-card:has-text("Fresh Roast")', 200, 4), { label: 'Weekly, as she chose', hold: 1.6 });
        await v.unfocus();
        await v.scrollTo('.arraysubs-subscription-card:has-text("Herbal Tea Club")', { block: 'start' });
        await v.focus(await topSlice(v, '.arraysubs-subscription-card:has-text("Herbal Tea Club")', 200, 4), { label: 'Trial first, then monthly', hold: 1.6 });
        await v.unfocus();

        await v.as(1);
        await v.goto('/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions', { hold: 0.4 });
        await v.say('In admin, every subscription shows its **billing schedule**.');
        await v.type('input[placeholder*="Search"]', 'Freya', { cps: 10 });
        await v.press('Enter');
        await v.focus(await unionRect(v, 'tbody tr', 2), { label: 'One customer, three schedules', hold: 1.8 });
        await v.unfocus();
        await v.click('tbody tr:has-text("Herbal Tea Club") a:has-text("View Details")', { wait: 600, hold: 0.4 });
        await v.say('Price, trial and fee are **copied at checkout**.');
        await v.focus('.arraysubs-sd__stats', { label: 'Locked in for this subscriber', pad: 6, hold: 1.6 });
        await v.unfocus();
        await v.scrollTo('section.arraysubs-sd__panel:has(h3:has-text("Plan & Billing"))', { block: 'start' });
        await v.focus(await topSlice(v, 'section.arraysubs-sd__panel:has(h3:has-text("Plan & Billing"))', 520, 2), { label: 'Schedule, trial and sign-up fee', hold: 2.0 });
        await v.unfocus();
        await v.say('Change the product later: only **new buyers** get new terms.');
        await v.hold(0.4, { note: 'end' });
      },
    },
  ],
};
