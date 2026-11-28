// Flexible billing — product tour (Free + Pro).
// Story: plans list -> plan wizard creates "Herbal Tea Club" (every 2 weeks) -> variable tiers + Pro
// intro price -> customer-chosen schedule (Pro) -> fixed end date (Pro) -> store-wide rules ->
// trial + sign-up fee checkout -> terms locked in (My Account + admin).
// Store changes are logged in notes/flexible-billing.md.
import { wp, wpJson, SITE } from '../lib/director.mjs';

const CUSTOMER = 866; // Freya Kelly (block 861-921)
const CUSTOMER_EMAIL = 'freya.kelly@example.com'; // real value; shown as freya.kelly@mail.com (clean map)
const PLANS = '/wp-admin/admin.php?page=arraysubs-mainadmin#/plans';
const FRESH = 25875; // Fresh Roast Coffee Plan (Full Flexible, week/month, max 12)
const CLUB = 25877; // Coffee Club Membership (variable: Monthly 7-day trial / Annual / Lifetime)
const SEASON = 31767; // Studio Season Pass (fixed end date, annual cutoff 12-31, renew)
const WIZ_TITLE = 'Herbal Tea Club';
const TEA_IMG = 25742; // Herbal Tea Collection image
const editBilling = (id) => `/wp-admin/post.php?post=${id}&action=edit&arraysubs_from=plans&arraysubs_edit=billing`;
const productUrl = (id) => wp(['eval', `echo get_permalink(${Number(id)});`]).replace('http://localhost:10013', '');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Storefront only (recording browser): hide the test store's extra menu items (Checkout,
// Checkout Classic, Cart Page), the theme's placeholder footer links and the empty Reviews tab.
const STORE_CSS = `
header .wp-block-navigation-item:has(> a[href$="/checkout/"]),
header .wp-block-navigation-item:has(> a[href$="/checkout-2/"]),
header .wp-block-navigation-item:has(> a[href$="/cart-2/"]),
footer nav.wp-block-navigation,
.woocommerce-tabs li.reviews_tab, .wc-tabs li.reviews_tab, .woocommerce-Tabs-panel--reviews,
.wp-block-woocommerce-product-details:not(:has(li.description_tab)) { display: none !important; }
`;

const php = {
  emptyCart: `global $wpdb; $wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>'${CUSTOMER}']); delete_user_meta(${CUSTOMER},'_woocommerce_persistent_cart_1'); echo json_encode(['ok'=>1]);`,
  wizProducts: `$q=new WP_Query(['post_type'=>'product','post_status'=>['publish','draft','pending','private'],'title'=>'${WIZ_TITLE}','posts_per_page'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'DESC']); echo json_encode($q->posts);`,
  // Block checkout submits the displayed (cleaned) email and saves it to the customer: put the real one back.
  restoreEmail: `update_user_meta(${CUSTOMER}, 'billing_email', '${CUSTOMER_EMAIL}'); echo json_encode([get_user_meta(${CUSTOMER}, 'billing_email', true)]);`,
};

const log = (...a) => console.log('  [fb]', ...a);
const emptyCart = () => wpJson(php.emptyCart);
const wizProducts = () => wpJson(php.wizProducts);
const latestWiz = () => wizProducts()[0] || 0;

/** Trash every earlier "Herbal Tea Club" plan made by this scene (so the list looks fresh),
 *  after removing the demo customer's orders/subscriptions on any of them (trashed ones too). */
function trashWizProducts() {
  const all = wpJson(`$q=new WP_Query(['post_type'=>'product','post_status'=>'any','title'=>'${WIZ_TITLE}','posts_per_page'=>-1,'fields'=>'ids']); $t=get_posts(['post_type'=>'product','post_status'=>'trash','title'=>'${WIZ_TITLE}','posts_per_page'=>-1,'fields'=>'ids']); echo json_encode(array_values(array_unique(array_merge($q->posts,$t))));`);
  for (const pid of all) removeEarlierTakes(pid);
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
      global $wpdb;
      foreach ($wpdb->get_col($wpdb->prepare("SELECT action_id FROM {$wpdb->prefix}actionscheduler_actions WHERE status='pending' AND (args=%s OR args LIKE %s)", '['.$sid.']', '['.$sid.',%')) as $aid) { ActionScheduler::store()->cancel_action((int) $aid); }
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

/** Navigate off camera and wait for a selector plus a short settle (slow editors, late scrolls),
 *  then crossfade (or lift the chapter card) onto the finished page. `after` runs before the fade. */
async function gotoReady(v, url, sel, { ms = 900, after = null } = {}) {
  const full = /^https?:/.test(url) ? url : `${SITE}${url}`;
  v.cam = { s: 1, x: 0, y: 0 };
  await v.act(async (f) => {
    await f.goto(full, { waitUntil: 'domcontentloaded', timeout: 60000 });
    if (sel) await f.locator(sel).first().waitFor({ state: 'visible', timeout: 60000 });
    await sleep(ms);
    if (after) await after(f);
  });
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

/** Union rect of the FIRST visible match of each CSS selector. */
async function firstsRect(v, sels, pad = 0) {
  await v.frame.locator(sels[0]).first().waitFor({ state: 'visible', timeout: 12000 });
  return v.eval(({ sels, pad }) => {
    let x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity;
    for (const css of sels) {
      const e = [...document.querySelectorAll(css)].find((n) => n.getClientRects().length);
      if (!e) continue;
      const r = e.getBoundingClientRect();
      x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom);
    }
    return { x: x1 - pad, y: y1 - pad, w: x2 - x1 + pad * 2, h: y2 - y1 + pad * 2 };
  }, { sels, pad });
}

/** Union rect of the plan wizard's form fields whose label starts with each given text. */
async function wizFields(v, labels, pad = 6) {
  return v.eval(({ labels, pad }) => {
    const root = document.querySelector('.arraysubs-quick-product');
    const fields = [...root.querySelectorAll('.field-warper')].filter((w) => w.getClientRects().length);
    let x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity;
    for (const text of labels) {
      const w = fields.find((f) => (f.querySelector('.field-item-label')?.innerText || '').trim().startsWith(text));
      if (!w) throw new Error('no wizard field ' + text);
      const r = w.getBoundingClientRect();
      x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom);
    }
    return { x: x1 - pad, y: y1 - pad, w: x2 - x1 + pad * 2, h: y2 - y1 + pad * 2 };
  }, { labels, pad });
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

/** Order received: bring the totals and the new subscription row into view, point at the row. */
async function showOrderReceived(v, caption, label) {
  await v.hideCursor();
  await v.scrollTo('.wc-block-order-confirmation-totals__table, .woocommerce-table--order-details', { block: 'start' });
  await v.say(caption, { hold: 1.0 });
  await v.focus('.woocommerce-table--order-subscriptions tbody tr', { label, side: 'right', pad: 6, hold: 2.0 });
  await v.unfocus();
}

export default {
  slug: 'flexible-billing',
  tier: 'Free + Pro',
  intro: { title: 'Billing that fits the way *you sell*', sub: 'Schedules, trials, sign-up fees, plan tiers and terms your customers choose.', hold: 2.3 },
  outro: { title: 'Sell plans, not *workarounds*.', sub: 'Flexible billing in ArraySubs. Most of it is free; Pro adds customer choice.', url: 'subscriptionplugin.pro', hold: 2.6 },

  setup: async (v) => {
    // Studio Season Pass is the fixed-date demo product; it must be live.
    wp(['eval', `if (get_post_status(${SEASON}) !== 'publish') { wp_untrash_post(${SEASON}); wp_update_post(['ID'=>${SEASON},'post_status'=>'publish']); } echo get_post_status(${SEASON});`]);
    emptyCart();
    await v.context.addInitScript((css) => {
      if (window.top === window || /^\/wp-admin/.test(location.pathname)) return;
      const add = () => {
        if (document.getElementById('fv-fb-store-css')) return;
        const s = document.createElement('style');
        s.id = 'fv-fb-store-css';
        s.textContent = css;
        const host = document.head || document.documentElement;
        if (host) host.appendChild(s);
      };
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add);
      add();
    }, STORE_CSS);
    // Block checkout fills the email field from the Store API, which the display-only clean map
    // does not reach: show the same address the rest of the video shows (display only; React state
    // and the submitted order keep the real value).
    await v.context.addInitScript(([from, to]) => {
      if (window.top === window || /^\/wp-admin/.test(location.pathname)) return;
      setInterval(() => {
        const i = document.querySelector('input#email');
        if (i && i.value === from && document.activeElement !== i) i.value = to;
      }, 80);
    }, [CUSTOMER_EMAIL, 'freya.kelly@mail.com']);
  },

  teardown: async () => {
    emptyCart();
    log('billing email', JSON.stringify(wpJson(php.restoreEmail)));
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
        v.captionPos('tr');
        await v.goto(PLANS, { hold: 0.5 });
        await v.say('**Subscription Plans** lists every plan in your store.');
        await v.focus(await unionRect(v, 'tbody tr:nth-child(-n+4) .arraysubs-plan-name', 6), { label: 'The plan type under each name', dim: true, side: 'right' });
        await v.unfocus();
        await v.say('Price and billing terms sit in **one column**.');
        await v.zoom(await columnRect(v, 'Price & billing', 3), { scale: 1.75, pad: 40 });
        await v.hold(1.2, { note: 'billing column' });
        await v.zoomOut();
        await v.say('Search, or filter by status and type.');
        await v.click('button.arraysubs-dl-status-tab-item:has-text("Published")', { hold: 0.2 });
        await v.select('label.arraysubs-dl-filter select', { label: 'Variable Subscription' });
        await v.type('input[placeholder*="Search subscription plans"]', 'Coffee', { cps: 10, hold: 0.1 });
        await v.act(async (f) => {
          const filtered = () => f.waitForFunction(() => {
            const rows = [...document.querySelectorAll('tbody tr')];
            return rows.length >= 1 && rows.every((r) => /Coffee/.test(r.innerText));
          }, null, { timeout: 5000 });
          try { await filtered(); } catch { await f.locator('input[placeholder*="Search subscription plans"]').first().press('Enter'); await filtered(); }
          await sleep(400);
        });
        await v.hover('tr:has-text("Coffee Club Membership") .arraysubs-plan-title', { hold: 0.4 });
        await v.focus('tr:has-text("Coffee Club Membership") .arraysubs-plan-billing', { label: 'Billing varies by variation', side: 'right' });
        await v.say('**Duplicate** a plan as a draft to test a new price.');
        await v.focus('tr:has-text("Coffee Club Membership") .arraysubs-plan-actions button:has-text("Duplicate")', { label: 'Subscribers keep their price', pad: 6, side: 'bottom' });
        await v.unfocus();
      },
    },

    // 2 ------------------------------------------------------------------ plan wizard
    {
      kicker: 'Plan wizard',
      title: 'A new plan in *7 steps*',
      cardHold: 1.5,
      sub: 'Type, details, schedule, trial, end date, shipping, review.',
      before: async () => { trashWizProducts(); },
      run: async (v) => {
        await v.as(1);
        await v.goto(PLANS, { hold: 0.4 });
        await v.focus('button:has-text("Add new plan")', { label: 'Start the plan wizard', pad: 6, hold: 0.9 });
        await v.click('button:has-text("Add new plan")', { hold: 0.3 });
        v.captionPos('bc');
        await v.say('The wizard walks through **seven short steps**.', { hold: 1.2 });
        await v.focus('.boxed-fullscreen-modal__progress', { pad: 4, hold: 1.0 });
        await wizNext(v);

        // details
        await v.say('Only the **title and price** are required.', { hold: 0.7 });
        await v.type('.arraysubs-quick-product input.arraysubs-fb-input >> visible=true >> nth=0', WIZ_TITLE, { cps: 24 });
        await v.type('.arraysubs-quick-product input.arraysubs-fb-input >> visible=true >> nth=1', '18', { cps: 8 });
        await v.hold(0.3, { note: 'details' });
        await wizNext(v);

        // recurring billing
        await v.say('Fixed schedule, or let **customers choose** (Pro).', { hold: 1.0 });
        await v.focus(await unionRect(v, '.arraysubs-quick-product label.arraysubs-fb-grid-select-item', 2), { label: 'Fixed, Flexible Length, Full Flexible', hold: 1.7 });
        await v.unfocus();
        await v.say('Bill every **1 to 12** days, weeks, months or years.', { hold: 1.0 });
        await v.click('.arraysubs-quick-product .arraysubs-fb-select-control >> visible=true', { hold: 0.25, settle: false });
        await v.click('.arraysubs-fb-select-option:has-text("Week") >> visible=true', { hold: 0.1, settle: false });
        await v.type('.arraysubs-quick-product input.arraysubs-fb-input >> visible=true >> nth=0', '2', { cps: 8, hold: 0.2 });
        await v.hideCursor();
        await v.focus(await wizFields(v, ['Billing period', 'Billing interval']), { label: 'Every 2 weeks', hold: 1.4 });
        await v.say('Stop after a set number of payments, or **never**.', { hold: 0.9 });
        await v.focus(await wizFields(v, ['Subscription length']), { label: '0 = never ends', hold: 1.5 });
        await v.unfocus();
        await wizNext(v);

        // trial & signup
        await v.say('Add a **free trial** and a one-time **sign-up fee**.', { hold: 0.8 });
        await v.type('.arraysubs-quick-product input.arraysubs-fb-input >> visible=true >> nth=0', '14', { cps: 8 });
        await v.type('.arraysubs-quick-product input.arraysubs-fb-input >> visible=true >> nth=1', '5', { cps: 8 });
        await v.focus(await wizFields(v, ['Trial length', 'Trial period', 'Signup fee']), { label: '14 days free, $5 to join', hold: 1.5 });
        await v.unfocus();
        await wizNext(v);

        // fixed membership (Pro, shown in chapter 5)
        await v.say('Pro: end every membership on **one calendar date**.', { hold: 1.1 });
        await v.focus('.arraysubs-quick-product .field-warper:has(input[type=checkbox]) >> visible=true', { label: 'More in chapter 5', pad: 6, hold: 1.0 });
        await wizNext(v);

        // shipping
        v.captionPos('tr');
        await v.say('Pro: charge shipping on **every renewal** or once.', { hold: 1.0 });
        await v.focus(await unionRect(v, '.arraysubs-quick-product label.arraysubs-fb-grid-select-item', 2), { label: 'Recurring or one-time shipping', hold: 1.6 });
        await v.unfocus();
        await v.type('.arraysubs-quick-product input[placeholder*="Initial shipping"]', '4.95', { cps: 10 });
        await v.hold(0.3, { note: 'shipping' });
        await wizNext(v);
        v.captionPos('bc');

        // review
        await v.say('Review it all, with **Edit** links, before anything is created.', { hold: 1.5 });
        await v.focus('.arraysubs-quick-product h3:has-text("Product details") button:has-text("Edit")', { label: 'Jump back to any step', pad: 6, hold: 1.1 });
        await v.unfocus();
        await v.click('.arraysubs-quick-product button.button-primary:has-text("Create product")', { hold: 0.2 });
        await v.focus('.arraysubs-confirm-dialog', { pad: 6, hold: 0.6 });
        await v.hover('.arraysubs-confirm-dialog__actions button.button-primary', { hold: 0.2 });
        await v.act(async (f) => {
          await f.locator('.arraysubs-confirm-dialog__actions button.button-primary').first().click();
          await f.locator('.arraysubs-quick-product__success').waitFor({ state: 'visible', timeout: 150000 });
          await sleep(600);
        });
        await v.hideCursor();
        await v.say('Published. Open the page, the editor or member access.', { hold: 1.0 });
        await v.focus('.arraysubs-quick-product__links', { pad: 8, hold: 1.4 });
        await v.unfocus();

        // off camera: give the new plan an image, a short line and a category, like a merchant would
        const id = latestWiz();
        if (!id) throw new Error('wizard product not found');
        wp(['eval', `$p = wc_get_product(${id}); $p->set_image_id(${TEA_IMG}); $p->set_short_description('Loose-leaf herbal tea, a new calming blend every two weeks.'); $p->set_category_ids([19]); $p->save(); echo $p->get_id();`]);
        log('wizard product', id, wp(['eval', `echo json_encode(['period'=>get_post_meta(${id},'_subscription_period',true),'interval'=>get_post_meta(${id},'_subscription_interval',true),'trial'=>get_post_meta(${id},'_trial_length',true),'fee'=>get_post_meta(${id},'_signup_fee',true)]);`]));
      },
    },

    // 3 ------------------------------------------------------------------ variable tiers + intro price
    {
      kicker: 'Variable plans',
      title: 'Monthly, yearly and *lifetime*',
      cardHold: 1.5,
      sub: 'One product, one page, a plan for everyone. Free.',
      run: async (v) => {
        await v.as(1);
        await gotoReady(v, editBilling(CLUB), '.woocommerce_variation', { ms: 1400 });
        await v.say('Tick **Subscription** and each variation becomes a plan.', { hold: 1.0 });
        await v.focus('label[for="_is_subscription"]', { label: 'Subscription on', pad: 6, side: 'right', hold: 1.3 });
        await v.focus(await unionRect(v, '.woocommerce_variation > h3', 2), { label: 'Monthly, Annual, Lifetime', dim: true });
        await v.click('.woocommerce_variation:has-text("#25879") a.edit_variation', { wait: 700 });
        // the ready links hold this dev site's address: show the store's public one instead (display only)
        await v.eval(() => document.querySelectorAll('.arraysubs-product-helper-links__input').forEach((i) => {
          i.value = i.value.replace('http://localhost:10013', 'https://brightside.market').replace('/cart-2/', '/cart/').replace('/checkout-2/', '/checkout/');
        }));
        await v.scrollTo('.woocommerce_variation:has-text("#25879") .arraysubs-product-helper-links__input', { block: 'center' });
        await v.say('Each variation gets **ready checkout links**.', { hold: 0.9 });
        await v.focus(await unionRect(v, '.woocommerce_variation .arraysubs-product-helper-links__input', 6), { label: 'Add-to-cart and one-click checkout', hold: 1.4 });
        await v.unfocus();
        await v.scrollTo('.woocommerce_variation:has-text("#25879") .arraysubs-variation-fields', { block: 'start', offset: -20 });
        await v.say('Each one gets its own **price, schedule and trial**.', { hold: 1.0 });
        await v.focus(await topSlice(v, '.woocommerce_variation .arraysubs-variation-fields >> visible=true', 560, 4), { label: 'Price, schedule, 7-day trial', side: 'left', hold: 1.6 });
        await v.unfocus();

        // Pro intro price (shown in the editor, never saved)
        const renew = '.woocommerce_variation:has-text("#25879") label.arraysubs-checkbox-label:has-text("Different Renewal Price")';
        await v.scrollTo(renew, { block: 'center', offset: 60 });
        await v.say('Pro: an **intro price**, then a new price after set payments.', { hold: 1.0 });
        await v.click(renew, { dx: 0.04, hold: 0.2, settle: false });
        await v.type('.woocommerce_variation:has-text("#25879") input[name="_renewal_price[0]"]', '15', { cps: 8, hold: 0.1 });
        await v.type('.woocommerce_variation:has-text("#25879") input[name="_renewal_price_after[0]"]', '3', { cps: 8, hold: 0.1 });
        await v.hideCursor();
        await v.focus(await firstsRect(v, ['label.arraysubs-checkbox-label:has(input.variation_enable_renewal_price)', '.woocommerce_variation input[name="_renewal_price[0]"]', '.woocommerce_variation input[name="_renewal_price_after[0]"]'], 10), { label: '15.00$ after 3 payments', side: 'right', hold: 1.6 });
        await v.unfocus();
        await dropUnload(v);

        await v.as(CUSTOMER);
        await v.goto(productUrl(CLUB), { hold: 0.5 });
        await v.say('Shoppers pick a plan on **one page**.', { hold: 0.9 });
        await v.select('select#plan', { label: 'Monthly' });
        await v.focus('.arraysubs-variable-subscription-info', { label: 'Monthly includes a 7-day trial', pad: 8, hold: 1.5 });
        await v.unfocus();
        await v.select('select#plan', { label: 'Annual' });
        await v.focus('.woocommerce-variation-price', { label: 'Billed yearly', pad: 8, side: 'right', hold: 1.2 });
        await v.select('select#plan', { label: 'Lifetime' });
        await v.focus('.woocommerce-variation-price', { label: 'Pay once, never renews', pad: 8, side: 'right', hold: 1.4 });
        await v.unfocus();
      },
    },

    // 4 ------------------------------------------------------------------ customer choice (Pro)
    {
      kicker: 'Pro · Customer choice',
      title: 'Customers set *their own* cycle',
      cardHold: 1.5,
      sub: 'Full Flexible: how often and how many payments.',
      before: async () => { emptyCart(); removeEarlierTakes(FRESH); },
      run: async (v) => {
        await v.as(1);
        await gotoReady(v, editBilling(FRESH), '.arraysubs-flexsub-toggle__btn', { ms: 900 });
        await v.say('**Full Flexible** lets the shopper pick the period and length.', { hold: 1.0 });
        await v.focus(await unionRect(v, '.arraysubs-flexsub-toggle__btn', 4), { label: 'Fixed, Flexible Length, Full Flexible', hold: 1.6 });
        await v.focus(await unionRect(v, '.arraysubs-flexsub-periods__option', 6), { label: 'Allowed: Week and Month', hold: 1.4 });
        await v.focus('#_subscription_length', { label: 'Up to 12 payments', pad: 6, hold: 1.2 });
        await v.unfocus();
        await dropUnload(v);

        await v.as(CUSTOMER);
        await v.goto(productUrl(FRESH), { hold: 0.5 });
        await v.say('The choice sits right in the **add-to-cart form**.', { hold: 0.9 });
        await v.focus('.arraysubs-flexible-options', { label: 'Billing period and length', pad: 6, hold: 1.4 });
        await v.select('select[name=arraysubs_flexible_period]', 'week');
        await v.say('Pick **Week** and the price now reads "/ week".', { hold: 0.9 });
        await v.focus('.wp-block-woocommerce-product-price, .summary .price', { label: 'Same price, new period', pad: 8, side: 'bottom', hold: 1.4 });
        await v.unfocus();
        await v.type('input[name=arraysubs_flexible_length]', '8', { cps: 6 });
        await v.say('Eight weekly payments, then it ends on its own.', { hold: 1.8 });
        await v.click('button.single_add_to_cart_button', { nav: true, wait: 800, hold: 0.3 });
        await v.hideCursor();
        await v.say('Checkout repeats the choice: **every week**, 8 cycles.', { hold: 0.6 });
        await v.zoom('.wc-block-components-order-summary-item', { scale: 1.9, pad: 30 });
        await v.hold(2.0, { note: 'checkout fresh' });
        await v.zoomOut();
        await v.say(null);
        await v.click('.wc-block-components-checkout-place-order-button', { nav: true, wait: 1500, hold: 0.3 });
        await showOrderReceived(v, 'Order placed: a subscription billed **every week**.', 'Billed every week');
      },
    },

    // 5 ------------------------------------------------------------------ fixed date (Pro)
    {
      kicker: 'Pro · Fixed dates',
      title: 'One end date for *everyone*',
      cardHold: 1.5,
      sub: 'Seasons, school years and clubs.',
      run: async (v) => {
        await v.as(1);
        await gotoReady(v, editBilling(SEASON), '.arraysubs-fixed-end-date-section', { ms: 700, after: async (f) => {
          await f.evaluate(() => { const s = document.querySelector('.arraysubs-fixed-end-date-section'); window.scrollTo(0, s.getBoundingClientRect().top + scrollY - 32 - 30); });
        } });
        await v.say('Pro: end every membership on the **same date**.', { hold: 1.0 });
        await v.focus('.arraysubs-fixed-end-toggle', { label: 'Use fixed end date', pad: 6, hold: 1.1 });
        await v.focus('.arraysubs-fixed-end-recurring', { label: 'Every year on December 31', pad: 6, hold: 1.4 });
        await v.say('Open and close **enrollment**, and renew into the next period.', { hold: 1.2 });
        await v.focus(await unionRect(v, '.arraysubs-fixed-end-date-section .arraysubs-fixed-end-fields > .arraysubs-variation-row:nth-last-child(-n+2)', 6), { label: 'Enrollment window and period end', hold: 1.7 });
        await v.unfocus();
        await dropUnload(v);

        await v.as(CUSTOMER);
        await v.goto(productUrl(SEASON), { hold: 0.5 });
        await v.say('Shoppers see the end date **before they pay**.', { hold: 0.9 });
        await v.focus('.arraysubs-fixed-period-info', { label: 'Ends Dec 31, renews, enrollment dates', pad: 10, dim: true, hold: 2.4 });
        await v.unfocus();
      },
    },

    // 6 ------------------------------------------------------------------ store-wide rules
    {
      kicker: 'Settings',
      title: 'Rules for *every plan*',
      cardHold: 1.5,
      sub: 'Cart rules, one-click checkout and trial rules. Free.',
      run: async (v) => {
        await v.as(1);
        await v.goto('/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general', { hold: 0.4 });
        await v.say('Store-wide rules live in **Settings, General**.', { hold: 1.0 });
        await v.focus(await toggleRect(v, ['multiple_subscriptions-allow_multiple_in_cart', 'multiple_subscriptions-one_per_customer']), { label: 'What can share a cart', side: 'right', hold: 1.5 });
        await v.unfocus();
        await v.scrollTo('label:has-text("One Click Checkout")', { block: 'center' });
        await v.say('**One Click Checkout** sends shoppers straight to checkout.', { hold: 1.0 });
        await v.focus('.field-warper:has(> label:has-text("One Click Checkout")), .field-warper:has(label:text-is("One Click Checkout"))', { label: 'Skip the cart', pad: 8, side: 'right', hold: 1.6 });
        await v.unfocus();
        await v.scrollTo('#trials-one_trial_per_customer', { block: 'center' });
        await v.say('Trials can require a payment method and stay **one per customer**.', { hold: 1.0 });
        await v.focus(await toggleRect(v, ['trials-require_payment_method', 'trials-one_trial_per_customer']), { label: 'Trial rules', side: 'right', hold: 2.0 });
        await v.unfocus();
      },
    },

    // 7 ------------------------------------------------------------------ trial + fee checkout
    {
      kicker: 'Storefront',
      title: 'Trial and fee, *spelled out*',
      cardHold: 1.5,
      sub: 'The new plan, from product page to order.',
      before: async () => { emptyCart(); const id = latestWiz(); if (id) removeEarlierTakes(id); },
      run: async (v) => {
        const id = latestWiz();
        if (!id) throw new Error('no wizard product: record chapter 2 first');
        await v.as(CUSTOMER);
        await v.goto(productUrl(id), { hold: 0.5 });
        await v.say('The plan from the wizard, live in the store.', { hold: 0.9 });
        await v.focus(await firstsRect(v, ['.wp-block-woocommerce-product-price', '.arraysubs-product-info', '.arraysubspro-shipping-info'], 10), { label: 'Price, sign-up fee, trial, shipping', side: 'bottom', hold: 2.2 });
        v.poster();
        await v.unfocus();
        await v.click('button.single_add_to_cart_button', { nav: true, wait: 800, hold: 0.3 });
        await v.hideCursor();
        await v.say('Checkout lists the terms under the product name.', { hold: 0.6 });
        await v.zoom('.wc-block-components-order-summary-item', { scale: 1.9, pad: 30 });
        await v.hold(2.0, { note: 'checkout tea item' });
        await v.zoomOut();
        await v.scrollTo('.wc-block-components-totals-footer-item', { block: 'center' });
        v.captionPos('br');
        await v.say('Today: the **sign-up fee** and shipping. The trial costs nothing.', { hold: 0.8 });
        await v.zoom(await unionRect(v, '.wc-block-components-totals-item, .wc-block-components-totals-footer-item', 4), { scale: 1.7, pad: 30 });
        await v.hold(2.4, { note: 'checkout totals' });
        await v.zoomOut();
        v.captionPos('bl');
        await v.say(null);
        await v.click('.wc-block-components-checkout-place-order-button', { nav: true, wait: 1500, hold: 0.3 });
        await showOrderReceived(v, 'Order placed: the trial first, then **every 2 weeks**.', 'Next payment after the trial');
      },
    },

    // 8 ------------------------------------------------------------------ locked in
    {
      kicker: 'Result',
      title: 'Terms *locked in*',
      cardHold: 1.5,
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
        // open My Account scrolled to the first plan card (the store's contact line above stays off screen)
        await gotoReady(v, '/my-account/as-subscriptions/', '.arraysubs-subscription-card', { ms: 500, after: async (f) => {
          await f.evaluate(() => { const c = document.querySelector('.arraysubs-subscription-card'); window.scrollTo(0, c.getBoundingClientRect().top + scrollY - 10); });
        } });
        await v.say('In My Account, each plan keeps **its own schedule**.', { hold: 1.0 });
        const cards = await v.eval(() => [...document.querySelectorAll('.arraysubs-subscription-card')].map((c) => (/Fresh Roast/.test(c.innerText) ? 'fresh' : /Herbal Tea Club/.test(c.innerText) ? 'tea' : '')).filter(Boolean));
        log('card order', cards.join(','));
        const beats = { fresh: ['.arraysubs-subscription-card:has-text("Fresh Roast")', 'Every week, as she chose'], tea: ['.arraysubs-subscription-card:has-text("Herbal Tea Club")', 'Trial first, then every 2 weeks'] };
        for (const [i, key] of cards.entries()) {
          const [sel, label] = beats[key];
          if (i > 0) await v.scrollTo(sel, { block: 'start', offset: -4 });
          await v.focus(await topSlice(v, sel, 216, 4), { label, side: 'right', hold: 1.5 });
          await v.unfocus();
        }

        await v.as(1);
        await v.goto('/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions', { hold: 0.4 });
        await v.say('In admin, every subscription shows its **billing schedule**.', { hold: 1.0 });
        await v.click('input[placeholder*="customer"]', { hold: 0.1, settle: false });
        await v.act(async (f) => {
          await f.locator('input[placeholder*="customer"]').first().fill('Freya Kelly');
          await f.locator('tbody tr:has-text("Herbal Tea Club")').first().waitFor({ state: 'visible', timeout: 90000 });
          await sleep(900);
        });
        await v.hideCursor();
        await v.focus(await unionRect(v, 'tbody tr', 2), { label: 'Three plans, three schedules', hold: 1.8 });
        await v.unfocus();
        await v.click('tbody tr:has-text("Herbal Tea Club") a:has-text("Herbal Tea Club")', { wait: 600, hold: 0.3 });
        await v.hideCursor();
        await v.say('Price, trial and fee are **copied at checkout**.', { hold: 1.0 });
        await v.focus('.arraysubs-sd__stats', { label: 'Locked in for this subscriber', pad: 6, hold: 1.3 });
        await v.unfocus();
        await v.scrollTo('section.arraysubs-sd__panel:has(h3:has-text("Plan & Billing"))', { block: 'start', offset: -70 });
        await v.focus(await topSlice(v, 'section.arraysubs-sd__panel:has(h3:has-text("Plan & Billing"))', 470, 2), { label: 'Every 2 weeks, trial and sign-up fee', side: 'right', hold: 2.2 });
        await v.unfocus();
      },
    },
  ],
};
