// Subscription boxes — product tour (Free).
// Story: free module + box plans -> box editor summary -> steps, questions and uploads -> reward
// tiers + renewal sync -> shopper builds the box (live total, tiers, free gift) -> extras,
// questions, photo upload, review -> checkout line + order -> My Account box contents ->
// admin: the subscription's box + a renewal order as the packing list.
// Store changes are logged in notes/subscription-boxes.md.
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { wp, wpJson } from '../lib/director.mjs';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const CUSTOMER = 1056; // Naomi Romero (block 1044-1104)
const BOX = 6729; // Build Your Monthly Coffee Box (3 steps, tiers at $40 / $60, free mug)
const BOX_URL = '/product/demo-%c2%b7-build-your-monthly-coffee-box/';
const EDIT = `/wp-admin/post.php?post=${BOX}&action=edit`;
const MODULES = '/wp-admin/admin.php?page=arraysubs-mainadmin#/modules';
const PLANS = '/wp-admin/admin.php?page=arraysubs-mainadmin#/plans';
const JUNK_BOXES = [5922, 5990, 5900]; // "test box", "wst", "QPC QA ... Box": hidden from the plans list during chapter 1 only
const GIFT_PHOTO = path.join(HERE, '..', 'assets', 'subscription-boxes', 'gift-card.jpg');
const CLIP_COOKIE = 'subscription-boxes-video'; // clips/overrides/subscription-boxes-video.json (chapter 7 only)

const log = (...a) => console.log('  [boxes]', ...a);

// Which chapters this process records (run.mjs --chapters=...). A full run cleans up earlier takes
// in chapter 1, so the product editor shows no leftover subscription notice.
const ARG_CH = (process.argv.find((a) => a.startsWith('--chapters=')) || '').split('=')[1] || '';
const runs = (n) => !ARG_CH || ARG_CH.split(',').map(Number).includes(n);

const php = {
  emptyCart: `global $wpdb; $wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>'${CUSTOMER}']); delete_user_meta(${CUSTOMER},'_woocommerce_persistent_cart_1'); echo json_encode(['ok'=>1]);`,
  junk: (status) => `global $wpdb; $out=[]; foreach ([${JUNK_BOXES.join(',')}] as $id) { $wpdb->update($wpdb->posts, ['post_status'=>'${status}'], ['ID'=>$id]); clean_post_cache($id); if (function_exists('wc_delete_product_transients')) wc_delete_product_transients($id); $out[$id] = get_post_status($id); } echo json_encode($out);`,
  takes: `$ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'DESC',
      'meta_query'=>[['key'=>'_customer_id','value'=>${CUSTOMER}],['key'=>'_product_id','value'=>${BOX}]]]);
    $out = []; foreach ($ids as $s) { $out[] = ['sub'=>(int)$s, 'order'=>(int) get_post_meta($s,'_parent_order_id',true), 'status'=>get_post_status($s)]; }
    echo json_encode($out);`,
};

let cartTouched = false; // only a process that recorded customer chapters cleans the cart up
const emptyCart = () => { cartTouched = true; return wpJson(php.emptyCart); };
let junkHidden = false; // only the process that hid the QA boxes puts them back
const hideJunk = () => { junkHidden = true; log('junk boxes hidden', JSON.stringify(wpJson(php.junk('private')))); };
const restoreJunk = () => { if (!junkHidden) return; log('junk boxes restored', JSON.stringify(wpJson(php.junk('publish')))); junkHidden = false; };
const takes = () => wpJson(php.takes);
const latestTake = () => takes()[0] || null;

/** Delete the box subscriptions, their parent orders and their renewal orders from earlier takes. */
function removeEarlierTakes() {
  const out = wpJson(`
    $ids = get_posts(['post_type'=>'arraysubs_data','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids',
      'meta_query'=>[['key'=>'_customer_id','value'=>${CUSTOMER}],['key'=>'_product_id','value'=>${BOX}]]]);
    $done = [];
    foreach ($ids as $sid) {
      $orders = [];
      $oid = (int) get_post_meta($sid, '_parent_order_id', true);
      if ($oid) $orders[] = $oid;
      foreach (wc_get_orders(['limit'=>-1,'return'=>'ids','customer_id'=>${CUSTOMER},'meta_query'=>[['key'=>'_subscription_id','value'=>$sid]]]) as $rid) $orders[] = (int) $rid;
      $orders = array_values(array_unique($orders));
      foreach ($orders as $id) { if ($o = wc_get_order($id)) { $o->delete(true); } }
      wp_delete_post($sid, true);
      $done[] = ['sub'=>$sid, 'orders'=>$orders];
    }
    echo json_encode($done);`);
  if (out.length) log('removed earlier takes', JSON.stringify(out));
}

/** The store confirms the bank transfer (as an admin would), so the box subscription starts. */
function confirmTransfer() {
  const t = latestTake();
  if (!t) throw new Error('no box order yet: record chapter 6 first');
  const out = wpJson(`$o = wc_get_order(${t.order}); if ($o && $o->has_status(['on-hold','pending'])) { $o->update_status('processing', 'Bank transfer received.'); } echo json_encode(['order'=>${t.order}, 'sub'=>${t.sub}, 'status'=>get_post_status(${t.sub})]);`);
  log('confirmed transfer', JSON.stringify(out));
  return t;
}

/** The next month's renewal order for the take (built by ArraySubs off-camera, left pending). */
function ensureRenewal(t) {
  const out = wpJson(`
    $existing = wc_get_orders(['limit'=>-1,'return'=>'ids','customer_id'=>${CUSTOMER},'meta_query'=>[['key'=>'_subscription_id','value'=>${t.sub}],['key'=>'_is_renewal_order','value'=>'yes']],'orderby'=>'ID','order'=>'ASC']);
    $rid = 0;
    foreach ($existing as $id) { $o = wc_get_order($id); if ($o && (int) $o->get_meta('_subscription_id') === ${t.sub}) { $rid = (int) $id; } }
    if (!$rid) { $o = (new \\ArraySubs\\Features\\RecurringBilling\\Services\\OrderCreation())->createRenewalOrder(${t.sub}); $rid = $o ? (int) $o->get_id() : 0; }
    echo json_encode(['renewal'=>$rid]);`);
  log('renewal order', JSON.stringify(out));
  if (!out.renewal) throw new Error('could not build the renewal order');
  return out.renewal;
}

/** Product editor pages warn before leaving; drop that so the next goto is clean. */
async function dropUnload(v) {
  await v.eval(() => {
    window.onbeforeunload = null;
    if (window.jQuery) { window.jQuery(window).off('beforeunload'); }
  }).catch(() => {});
}

/** Union rect (page px) of every visible element matching a Playwright selector inside the store frame. */
async function unionRect(v, sel, pad = 0) {
  const loc = v.frame.locator(sel);
  await loc.first().waitFor({ state: 'visible', timeout: 15000 }).catch(() => {});
  const rects = await loc.evaluateAll((els) => els
    .filter((e) => e.getClientRects().length)
    .map((e) => { const r = e.getBoundingClientRect(); return [r.left, r.top, r.right, r.bottom]; })
    .filter((r) => r[3] > r[1] && r[2] > r[0]));
  if (!rects.length) throw new Error('no match ' + sel);
  const x1 = Math.min(...rects.map((r) => r[0])), y1 = Math.min(...rects.map((r) => r[1]));
  const x2 = Math.max(...rects.map((r) => r[2])), y2 = Math.max(...rects.map((r) => r[3]));
  return { x: x1 - pad, y: y1 - pad, w: x2 - x1 + pad * 2, h: y2 - y1 + pad * 2 };
}

/** Top part (max h px) of an element, for tall panels. */
async function topSlice(v, sel, maxH = 420, pad = 0) {
  const loc = v.frame.locator(sel).first();
  await loc.waitFor({ state: 'visible', timeout: 15000 });
  return loc.evaluate((el, { maxH, pad }) => {
    const r = el.getBoundingClientRect();
    return { x: r.left - pad, y: r.top - pad, w: r.width + pad * 2, h: Math.min(r.height, maxH) + pad * 2 };
  }, { maxH, pad });
}

/** Rect of a table column (header + first n body rows). */
async function columnRect(v, headerStart, rows = 3) {
  return v.eval(({ headerStart, rows }) => {
    const ths = [...document.querySelectorAll('thead th')];
    const i = ths.findIndex((t) => t.innerText.trim().toLowerCase().startsWith(headerStart.toLowerCase()));
    const th = ths[i].getBoundingClientRect();
    const trs = [...document.querySelectorAll('tbody tr')].slice(0, rows);
    const last = trs[trs.length - 1].children[i].getBoundingClientRect();
    return { x: th.left, y: th.top, w: th.width, h: last.bottom - th.top };
  }, { headerStart, rows });
}

/** Tight box around the visible text of every element matching sel (block children are often full-width). */
async function textRect(v, sel, pad = 0) {
  const loc = v.frame.locator(sel);
  await loc.first().waitFor({ state: 'visible', timeout: 15000 });
  return loc.evaluateAll((els, pad) => {
    let x1 = 1e9, y1 = 1e9, x2 = -1e9, y2 = -1e9;
    for (const el of els) {
      if (!el.getClientRects().length) continue;
      const w = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
      let n, found = false;
      while ((n = w.nextNode())) {
        if (!n.textContent.trim()) continue;
        const pe = n.parentElement; const pr = pe.getBoundingClientRect(); const cs = getComputedStyle(pe);
        if (pr.width <= 2 || pr.height <= 2 || cs.visibility === 'hidden' || cs.position === 'absolute' && cs.clip !== 'auto') continue; // skip screen-reader-only text
        const rg = document.createRange(); rg.selectNodeContents(n);
        for (const r of rg.getClientRects()) { if (!r.width || !r.height) continue; found = true; x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom); }
      }
      if (!found) { const r = el.getBoundingClientRect(); x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom); }
    }
    return { x: x1 - pad, y: y1 - pad, w: x2 - x1 + 2 * pad, h: y2 - y1 + 2 * pad };
  }, pad);
}

/**
 * Camera rect whose visible area starts exactly at `left` (page px) at the given scale, so a zoom
 * never shows the dimmed page or the admin menu beside the subject.
 */
const fromLeft = (left, r, scale) => ({ x: left, y: r.y, w: 1440 / scale, h: r.h });

// Recording-only layout CSS (the store's data and settings do not change):
// - the builder pop-up stays above the caption strip, as in a slightly shorter browser window;
// - the checkout uses the full window width (not the store's narrow 860px Checkout Builder box),
//   hides the product's placeholder short description in the order summary and gets room below
//   Place Order so the button can scroll above the caption strip;
// - WooCommerce's hover zoom on the product image is off (the parked pointer triggered it).
const SCENE_CSS = [
  '.arraysubs-box-overlay.is-open{padding-bottom:120px !important;}',
  '.arraysubs-box-modal{max-height:640px !important;}',
  'body.woocommerce-checkout{max-width:none !important;width:auto !important;padding-bottom:260px !important;}',
  'body.woocommerce-checkout .wc-block-components-order-summary-item .wc-block-components-product-metadata__description{display:none !important;}',
  '.woocommerce-product-gallery .zoomImg{display:none !important;}',
].join('');
const BUILDER_FIT_CSS = '.arraysubs-box-overlay.is-open{padding-bottom:120px !important;}.arraysubs-box-modal{max-height:640px !important;}';
async function fitBuilder(v) {
  await v.eval((css) => {
    if (document.getElementById('fv-box-fit')) return;
    const s = document.createElement('style'); s.id = 'fv-box-fit'; s.textContent = css; document.head.appendChild(s);
  }, BUILDER_FIT_CSS);
}

const elRow = (label) => `.arraysubs-box-element-row:has(.arraysubs-box-element-row__summary:text-is("${label}"))`;
const COFFEE_ROW = elRow('Coffee');
const EXTRAS_ROW = elRow('Extras');
const GRIND_ROW = elRow('Grind');
const UPLOAD_ROW = elRow('Gift card photo');
const STEP3 = `.arraysubs-box-step-row:has(${GRIND_ROW})`;
const CARD40 = '.arraysubs-box-range-card:has-text("$40 – $60")';
const CARD60 = '.arraysubs-box-range-card:has-text("and above")';
const leftPart = (r, w) => ({ x: r.x, y: r.y, w: Math.min(r.w, w), h: r.h });

const card = (name) => `.arraysubs-box-step.is-active .arraysubs-box-card:has(.arraysubs-box-card__name:text-is("${name}"))`;
const ACTIVE = '.arraysubs-box-step.is-active';

/** Off-camera: open the builder and set the step-1 quantities (2 coffee + 1 tea). */
async function buildStepOne(f) {
  await f.locator('.arraysubs-box-open').first().click();
  await f.locator('.arraysubs-box-modal').first().waitFor({ state: 'visible', timeout: 15000 });
  for (const [name, n] of [['Single-origin Coffee', 2], ['Herbal Tea Collection', 1]]) {
    const c = f.locator(card(name)).first();
    for (let i = 0; i < 6; i++) {
      const q = Number(await c.locator('.arraysubs-box-qty-input').inputValue().catch(() => '0')) || 0;
      if (q >= n) break;
      await c.locator('.arraysubs-box-qty-plus').click();
      await f.waitForTimeout(250);
    }
  }
}

/** Off-camera: attach the gift card photo and wait until the upload finished. */
async function attachPhoto(f) {
  await f.locator(`${ACTIVE} .arraysubs-box-upload__input`).setInputFiles(GIFT_PHOTO);
  await f.waitForFunction(() => /gift-card/.test(document.querySelector('.arraysubs-box-step.is-active .arraysubs-box-upload__status')?.textContent || ''), null, { timeout: 30000 });
}

/** Camera target for the builder footer: starts at the pop-up's left edge, so the zoom never shows the page behind it. */
async function footerView(v, w) {
  const m = await unionRect(v, '.arraysubs-box-modal', 0);
  const t = await textRect(v, '.arraysubs-box-totals', 0);
  return { x: m.x, y: t.y - 30, w, h: t.h + 60 };
}

/** The box line in the block checkout's order summary. */
const SUMMARY_ITEM = '.wc-block-components-order-summary-item';
let checkoutCache = '';
const checkoutPath = () => checkoutCache || (checkoutCache = wp(['eval', 'echo wc_get_checkout_url();']).replace('http://localhost:10013', ''));

export default {
  slug: 'subscription-boxes',
  tier: 'Free',
  intro: { title: 'Customers build their own *box*', sub: 'Steps, product picks, questions and rewards, billed as one subscription.' },
  outro: { title: 'Stop guessing what to *pack*.', sub: 'Subscription boxes are free in ArraySubs.', url: 'subscriptionplugin.pro' },

  setup: async (v) => {
    log('box product', wp(['eval', `echo get_post_status(${BOX});`]));
    await v.context.addInitScript((css) => {
      if (window.top === window) return;
      const add = () => {
        if (document.getElementById('fv-boxes-css')) return;
        const st = document.createElement('style'); st.id = 'fv-boxes-css'; st.textContent = css;
        (document.head || document.documentElement).appendChild(st);
      };
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add); else add();
      // Recording-only: the box editor's Box Schedule help box has one bullet that is wrong
      // ("Adding a box empties the cart first ..."; the cart is not emptied, see the notes and the
      // product bug report). Keep that single line off camera; the rest of the box is unchanged.
      if (location.pathname.startsWith('/wp-admin/')) {
        const hide = () => {
          for (const li of document.querySelectorAll('.arraysubs-modal li')) {
            if (li.style.display !== 'none' && li.textContent.trim().startsWith('Adding a box empties the cart first')) li.style.display = 'none';
          }
        };
        const start = () => new MutationObserver(hide).observe(document.body, { childList: true, subtree: true });
        if (document.body) start(); else document.addEventListener('DOMContentLoaded', start);
      }
    }, SCENE_CSS);
  },

  teardown: async () => {
    // Always put the hidden QA boxes back (chapter 1 hides them for a minute).
    restoreJunk();
    if (cartTouched) emptyCart(); // a re-recorded chapter 5 leaves a built box in the customer's cart
  },

  chapters: [
    // 1 ------------------------------------------------------------------ module + plans + editor summary
    {
      kicker: 'Admin',
      title: 'A free *box builder*',
      sub: 'Switch on the module and boxes join your plans.',
      before: async () => {
        // A full run removes the previous take first (its subscription would show an
        // "Active Subscriptions" notice in the product editor); chapter 6 records a fresh one.
        if (runs(6)) removeEarlierTakes();
        hideJunk();
      },
      run: async (v) => {
        await v.as(1);
        await v.goto(MODULES, { hold: 0.4 });
        await v.say('**Subscription Box** is a free ArraySubs module.', { hold: 0.8 });
        await v.type('.arraysubs-modules__search input', 'box', { cps: 8, hold: 0.3 });
        await v.act(async () => { await v.settle(); });
        await v.focus('.arraysubs-module-card:has(h3:text-is("Subscription Box"))', { label: 'Free module, switched on', dim: true, hold: 1.5 });
        await v.unfocus();

        await v.goto(PLANS, { hold: 0.4 });
        await v.say('Boxes sit with your other **subscription plans**.', { hold: 0.8 });
        await v.click('button.arraysubs-dl-status-tab-item:has-text("Published")', { hold: 0.2 });
        await v.select('label.arraysubs-dl-filter select', 'box');
        await v.focus(await unionRect(v, 'tbody tr td:has(.arraysubs-plan-title)', 2), { label: 'Coffee, skincare and pet boxes', dim: true, hold: 1.8 });
        await v.unfocus();
        await v.say('No fixed price: **the picks** set the price.', { hold: 0.8 });
        await v.zoom(await columnRect(v, 'Price', 3), { scale: 1.8, pad: 40 });
        await v.hold(1.6, { note: 'priced by selection' });
        await v.zoomOut();
        // (Clicking the SPA link froze the stage once under heavy load; hover it, then load the editor.)
        await v.hover('tr:has-text("Build Your Monthly Coffee Box") a:has-text("Edit product")', { hold: 0.4 });
        await v.goto(EDIT, { hold: 0.2 });
        await v.scrollTo('#woocommerce-product-data', { block: 'start' });
        await v.say('**Subscription Box** is its own product type.', { hold: 0.8 });
        await v.focus('#product-type', { label: 'Product type', pad: 6, hold: 1.3 });
        await v.say('The General tab sums up the **whole box**.', { hold: 0.8 });
        await v.focus('.arraysubs-box-facts', { label: 'Billing, sign-up fees, renewal sync', pad: 6, hold: 1.8 });
        await v.scrollTo('.arraysubs-box-summary-table--ranges', { block: 'center' });
        await v.focus('.arraysubs-box-summary-table--ranges', { label: 'Three reward tiers', pad: 4, side: 'top', hold: 1.6 });
        await v.unfocus();
        await dropUnload(v);
      },
    },

    // 2 ------------------------------------------------------------------ schedule + steps + questions + uploads
    {
      kicker: 'Admin',
      title: 'Lay out the *steps*',
      sub: 'Schedule, product picks, questions and uploads in one editor.',
      before: async () => { restoreJunk(); },
      run: async (v) => {
        await v.as(1);
        await v.act(async (f) => {
          await f.goto(`http://localhost:10013${EDIT}`, { waitUntil: 'domcontentloaded' });
          await v.settle();
          // Start at the box summary (above it sits WooCommerce's "Active Subscriptions" notice
          // whenever the take from chapter 6 exists).
          await f.evaluate(() => { const el = document.querySelector('.arraysubs-box-configure-btn'); window.scrollTo(0, el.getBoundingClientRect().top + scrollY - 190); });
        });
        await v.say('Everything is set up in **one editor**.', { hold: 0.8 });
        await v.click('.arraysubs-box-configure-btn', { hold: 0.3 });
        await v.focus('.arraysubs-box-wizard-nav', { label: 'Steps, rewards, renewal sync', pad: 6, hold: 1.6 });
        await v.hideCursor();
        await v.say('The box bills as **one subscription** on one schedule.', { hold: 0.6 });
        const modalLeft = (await unionRect(v, '.arraysubs-modal', 0)).x;
        await v.zoom(fromLeft(modalLeft, await unionRect(v, '.arraysubs-container-schedule__modes button, .arraysubs-container-schedule__title', 30), 1.7), { scale: 1.7 });
        await v.focus(await unionRect(v, '.arraysubs-container-schedule__modes button', 4), { label: 'Pro adds shopper-chosen terms', side: 'right', hold: 1.6 });
        await v.unfocus();
        await v.zoomOut();
        await v.focus(await unionRect(v, '.arraysubs-box-modal-editor label.arraysubs-box-field', 6), { label: 'Every month, until cancelled', side: 'bottom', hold: 1.6 });
        await v.unfocus();

        // Collapse every element so the step outline reads at a glance.
        await v.act(async (f) => {
          const rows = f.locator('.arraysubs-box-element-row:not(.is-collapsed) .arraysubs-box-element-row__collapse');
          for (let i = 0; i < 14 && (await rows.count()); i++) await rows.first().click();
          await f.evaluate(() => { const h = [...document.querySelectorAll('h3')].find((e) => e.innerText.trim() === 'Box Steps'); h.scrollIntoView({ block: 'start' }); });
        }, { settle: false });
        await v.say('Each step is **one screen** the shopper walks through.', { hold: 0.8 });
        await v.focus('.arraysubs-box-step-row__header >> nth=0', { label: 'Step 1: Pick your coffee and tea', pad: 4, hold: 1.6 });
        await v.unfocus();

        await v.click(`${COFFEE_ROW} .arraysubs-box-element-row__collapse`, { hold: 0.3 });
        await v.say('A product pick has a **max quantity** per box.', { hold: 0.8 });
        await v.zoom(fromLeft(modalLeft, await unionRect(v, COFFEE_ROW, 4), 1.5), { scale: 1.5 });
        await v.focus(`${COFFEE_ROW} label.arraysubs-box-field-item:has-text("Max quantity")`, { label: 'Up to 3 per box', pad: 4, side: 'right', hold: 1.3 });
        await v.unfocus();
        await v.zoomOut();
        await v.click(`${COFFEE_ROW} .arraysubs-box-element-row__collapse`, { hold: 0.2 });

        await v.click(`${EXTRAS_ROW} .arraysubs-box-element-row__collapse`, { hold: 0.2 });
        await v.scrollTo(`${EXTRAS_ROW}`, { block: 'center' });
        await v.say('Or offer a **whole category** with min and max picks.', { hold: 0.8 });
        await v.zoom(fromLeft(modalLeft, await unionRect(v, EXTRAS_ROW, 4), 1.5), { scale: 1.5 });
        await v.focus(await unionRect(v, `${EXTRAS_ROW} label.arraysubs-box-field-item:has-text("Min items"), ${EXTRAS_ROW} label.arraysubs-box-field-item:has-text("Max items")`, 6), { label: 'Choose 0 to 2', side: 'right', hold: 1.3 });
        await v.unfocus();
        await v.zoomOut();
        await v.click(`${EXTRAS_ROW} .arraysubs-box-element-row__collapse`, { hold: 0.2 });

        await v.scrollTo(UPLOAD_ROW, { block: 'center' });
        await v.say('Questions collect **preferences, notes and files**.', { hold: 0.8 });
        await v.focus(await unionRect(v, `${STEP3} :is(.arraysubs-box-element-row__type-badge, .arraysubs-box-element-row__summary)`, 8), { label: 'Dropdown, tick boxes, text, upload', side: 'right', hold: 1.8 });
        await v.unfocus();
        await v.click(`${UPLOAD_ROW} .arraysubs-box-element-row__collapse`, { hold: 0.2 });
        const fileSettings = `${UPLOAD_ROW} label.arraysubs-box-field-item:has-text("Max File Size"), ${UPLOAD_ROW} div.arraysubs-box-field-item:has-text("Allowed File Types")`;
        await v.scrollTo(`${UPLOAD_ROW} div.arraysubs-box-field-item:has-text("Allowed File Types")`, { block: 'center' });
        await v.say('Uploads take **images, PDF or CSV**, with a size cap.', { hold: 0.8 });
        await v.zoom(fromLeft(modalLeft, await unionRect(v, fileSettings, 10), 1.4), { scale: 1.4 });
        await v.hideCursor();
        await v.focus(await textRect(v, `${UPLOAD_ROW} div.arraysubs-box-field-item:has-text("Allowed File Types") > span, ${UPLOAD_ROW} .arraysubs-box-check-group`, 6), { label: 'Images, PDF or CSV', side: 'left', pad: 6, hold: 1.8 });
        await v.unfocus();
        await v.zoomOut();
        await dropUnload(v);
      },
    },

    // 3 ------------------------------------------------------------------ discounts, freebies, renewal sync
    {
      kicker: 'Admin',
      title: 'Reward *bigger boxes*',
      sub: 'Discount tiers, free gifts and an optional billing day.',
      run: async (v) => {
        await v.as(1);
        await v.act(async (f) => {
          await f.goto(`http://localhost:10013${EDIT}`, { waitUntil: 'domcontentloaded' });
          await v.settle();
          await f.evaluate(() => { const el = document.getElementById('woocommerce-product-data'); window.scrollTo(0, el.getBoundingClientRect().top + scrollY - 60); });
          await f.locator('.arraysubs-box-configure-btn').click();
          await f.locator('.arraysubs-box-modal-editor__actions').waitFor({ state: 'visible', timeout: 15000 });
          await f.evaluate(() => { document.querySelector('.arraysubs-modal__content').scrollTop = 99999; });
        });
        await v.say('Next screen: **Discounts & Freebies**.', { hold: 0.8 });
        await v.click('.arraysubs-box-modal-editor__actions button.button-primary:has-text("Continue to Discounts")', { hold: 0.2 });
        // Park the pointer on the control we explain next (not on the next screen's Continue button).
        await v.hover('label.arraysubs-box-field-item:has-text("Ranges based on")', { hold: 0, dx: 0.9, dy: 0.2 });
        await v.hideCursor();
        const mLeft = (await unionRect(v, '.arraysubs-modal', 0)).x;
        await v.zoom(fromLeft(mLeft, await unionRect(v, 'label.arraysubs-box-field-item:has-text("Ranges based on")', 30), 1.7), { scale: 1.7 });
        await v.focus('label.arraysubs-box-field-item:has-text("Ranges based on")', { label: 'Box value or item count', pad: 6, side: 'right', hold: 1.6 });
        await v.unfocus();
        await v.zoomOut();
        await v.say('Points at **$40 and $60** make three reward tiers.', { hold: 0.8 });
        await v.focus('.arraysubs-box-range-slider__bar', { label: 'Drag points to set price breaks', pad: 14, hold: 1.8 });
        await v.unfocus();
        await v.scrollTo(CARD40, { block: 'start' });
        await v.say('From $40 the box takes **10% off**.', { hold: 0.8 });
        await v.zoom(fromLeft(mLeft, await unionRect(v, CARD40, 6), 1.55), { scale: 1.55 });
        await v.focus(`${CARD40} label.arraysubs-box-field-item:has-text("Percent off")`, { label: '10% off', pad: 6, side: 'right', hold: 1.4 });
        await v.unfocus();
        await v.zoomOut();
        await v.say('From $60: **15% off and a free mug**.', { hold: 0.8 });
        await v.scrollTo(CARD60, { block: 'start' });
        await v.zoom(fromLeft(mLeft, await unionRect(v, CARD60, 6), 1.55), { scale: 1.55 });
        await v.focus(`${CARD60} .arraysubs-box-ajax-select__tag`, { label: 'Free gift: Ceramic Member Mug', pad: 6, side: 'right', hold: 1.6 });
        await v.focus(`${CARD60} label.arraysubs-box-field-item:has-text("Percent off")`, { label: '15% off', pad: 6, side: 'right', hold: 1.2 });
        await v.unfocus();
        await v.zoomOut();
        await v.say('Only **one tier** applies, and it repeats on renewals.');

        await v.click('.arraysubs-box-modal-editor__actions button.button-primary:has-text("Continue to Flexible")', { hold: 0.3 });
        await v.say('Optional: bill every box subscriber **on one day**.', { hold: 0.8 });
        await v.click('label.arraysubs-box-checkbox:has-text("own segment plan") input', { hold: 0.2 });
        await v.act(async (f) => {
          await f.waitForFunction(() => !document.body.innerText.includes('Loading renewal sync defaults'), null, { timeout: 25000 }).catch(() => {});
        }, { settle: false });
        await v.scrollTo('.arraysubs-box-modal-editor__screen .arraysubs-box-range-slider__bar, .arraysubs-box-modal-editor__screen [class*="slider"]', { block: 'center' });
        await v.say('The **sign-up day** sets the first charge.', { hold: 0.8 });
        const segText = await unionRect(v, '.arraysubs-box-modal-editor__screen :text("Full amount"), .arraysubs-box-modal-editor__screen :text("Charge full for next billing cycle")', 6);
        const modalLeft = (await unionRect(v, '.arraysubs-box-modal-editor__screen', 0)).x;
        const segs = { x: modalLeft + 10, y: segText.y, w: segText.x + segText.w - modalLeft - 10, h: segText.h };
        await v.zoom(fromLeft(mLeft, { x: segs.x, y: segs.y - 20, w: segs.w, h: segs.h + 40 }, 1.8), { scale: 1.8 });
        await v.focus(segs, { label: 'Full, prorated or next cycle', side: 'right', hold: 2.0 });
        await v.unfocus();
        await v.zoomOut();
        await v.say('Works with Stripe, Mollie and manual payments.', { hold: 0.6 });
        await v.focus(await textRect(v, 'label.arraysubs-box-checkbox:has-text("own segment plan") :text("Supported for manual payment")', 3), { label: 'Manual, Stripe and Mollie', side: 'bottom', pad: 3, hold: 1.6 });
        await v.unfocus();
        await dropUnload(v);
      },
    },

    // 4 ------------------------------------------------------------------ shopper builds: live total + tiers
    {
      kicker: 'Storefront',
      title: 'Shoppers *build it*',
      sub: 'A live total and the next reward, always in view.',
      before: async () => { emptyCart(); },
      run: async (v) => {
        await v.as(CUSTOMER);
        await v.goto(BOX_URL, { hold: 0.5 });
        await fitBuilder(v);
        await v.say('The price reads **Priced by your selection**.', { hold: 0.8 });
        await v.focus(await textRect(v, ':is(.wc-block-components-product-price, .summary .price) >> nth=0', 8), { label: 'Set by the picks', side: 'right', hold: 1.5 });
        await v.click('.arraysubs-box-open', { hold: 0.4 });
        await v.say('Each **step** is a chip. Review comes last.', { hold: 0.8 });
        await v.focus('.arraysubs-box-modal__chips', { label: '3 steps, then Review details', pad: 8, hold: 1.6 });
        await v.unfocus();

        await v.say('Add a coffee and the **total** updates.', { hold: 0.8 });
        await v.click(`${card('Single-origin Coffee')} .arraysubs-box-qty-plus`, { hold: 0.3 });
        await v.zoom(await footerView(v, 800), { scale: 1.7 });
        await v.focus(await textRect(v, '.arraysubs-box-totals__hint', 6), { label: 'The next reward, in plain words', side: 'right', hold: 1.7 });
        await v.unfocus();
        await v.zoomOut();

        await v.say('Add a tea: **10% off** at $40, and the next reward shows.', { hold: 0.8 });
        await v.click(`${card('Herbal Tea Collection')} .arraysubs-box-qty-plus`, { hold: 0.3 });
        await v.zoom(await footerView(v, 800), { scale: 1.7 });
        await v.hold(2.4, { note: 'tier 2 hint' });
        await v.zoomOut();

        await v.say('A second coffee: **15% off plus a free mug**.', { hold: 0.8 });
        await v.click(`${card('Single-origin Coffee')} .arraysubs-box-qty-plus`, { hold: 0.3 });
        await v.zoom(await footerView(v, 940), { scale: 1.45 });
        await v.focus(await textRect(v, '.arraysubs-box-totals', 8), { label: 'You save 9.30$ and get a gift', side: 'top', hold: 2.0 });
        await v.unfocus();
        await v.zoomOut();
        // Poster: the whole builder at full size, the reward ringed, no cursor on top.
        await v.focus(await textRect(v, '.arraysubs-box-totals', 10), { label: '15% off and a free mug', side: 'right', hold: 0.3 });
        await v.hideCursor();
        await v.hold(0.2, { still: false });
        v.poster();
        await v.hold(1.3, { note: 'poster' });
        await v.unfocus();
      },
    },

    // 5 ------------------------------------------------------------------ extras, questions, upload, review
    {
      kicker: 'Storefront',
      title: 'Ask, then *review*',
      sub: 'Extras, answers, a photo and one review screen.',
      before: async () => { emptyCart(); },
      run: async (v) => {
        await v.as(CUSTOMER);
        await v.act(async (f) => {
          await f.goto(`http://localhost:10013${BOX_URL}`, { waitUntil: 'domcontentloaded' });
          await v.settle();
          await fitBuilder(v);
          await buildStepOne(f);
          await f.locator('.arraysubs-box-next').click();
          await f.locator(`${ACTIVE} .arraysubs-box-element__hint`).first().waitFor({ state: 'visible', timeout: 10000 });
        });
        await v.say('Step 2 offers **optional extras** from a category.', { hold: 0.8 });
        await v.focus(await textRect(v, `${ACTIVE} .arraysubs-box-element__label`, 6), { label: 'Choose up to 2', side: 'right', hold: 1.5 });
        await v.unfocus();
        await v.click('.arraysubs-box-next', { hold: 0.3 });

        await v.say('Step 3 asks **questions**. The star marks required.', { hold: 0.8 });
        await v.focus(await textRect(v, `${ACTIVE} label.arraysubs-box-element__label >> nth=0`, 6), { label: 'Grind is required', side: 'right', hold: 1.4 });
        await v.unfocus();
        await v.say('She picks a grind, a flavor and a **gift note**.', { hold: 0.6 });
        await v.select(`${ACTIVE} select.arraysubs-box-field`, 'Espresso');
        await v.click(`${ACTIVE} label.arraysubs-box-choice:has-text("Chocolatey")`, { hold: 0.2 });
        await v.scrollTo(`${ACTIVE} input.arraysubs-box-field:not([type="file"])`, { block: 'center' });
        await v.type(`${ACTIVE} input.arraysubs-box-field:not([type="file"])`, 'Happy birthday, Sam!', { cps: 16 });

        await v.scrollTo(`${ACTIVE} .arraysubs-box-upload`, { block: 'center' });
        await v.say('She adds a **photo** for the gift card.', { hold: 0.6 });
        await v.hover(`${ACTIVE} .arraysubs-box-upload__input`, { hold: 0.2, dx: 0.12 });
        await v.act(async (f) => { await attachPhoto(f); });
        await v.focus(await unionRect(v, `${ACTIVE} .arraysubs-box-element--input:has(.arraysubs-box-upload) :is(.arraysubs-box-element__label, .arraysubs-box-upload)`, 6), { label: 'Photo for the gift card', side: 'right', hold: 1.6 });
        await v.unfocus();
        await v.click('.arraysubs-box-next', { hold: 0.3 });
        await v.hideCursor();

        await v.say('**Review details** shows every pick and answer.', { hold: 0.8 });
        await v.focus('.arraysubs-box-review__step >> nth=0', { label: 'Each step, with Edit', pad: 4, hold: 1.8 });
        await v.scrollTo('.arraysubs-box-review__step >> nth=2', { block: 'end' });
        await v.focus('.arraysubs-box-review__step >> nth=2', { label: 'Answers and the photo', pad: 4, hold: 1.8 });
        await v.unfocus();
        await v.say('One click and the box goes **to checkout**.');
        await v.click('.arraysubs-box-submit', { nav: true, wait: 800, hold: 0.2 });
        if (!/checkout/.test(v.frame.url())) {
          await v.act(async (f) => { await f.waitForURL(/checkout/, { timeout: 90000 }).catch(() => {}); });
        }
        await v.hold(0.6, { note: 'arrived at checkout' });
      },
    },

    // 6 ------------------------------------------------------------------ checkout + order received
    {
      kicker: 'Checkout',
      title: 'One tidy *line*',
      sub: 'Contents, gift, discount and answers travel along.',
      before: async () => { removeEarlierTakes(); },
      run: async (v) => {
        const checkout = checkoutPath();
        await v.as(CUSTOMER);
        await v.act(async (f) => {
          await f.goto(`http://localhost:10013${checkout}`, { waitUntil: 'domcontentloaded' });
          await v.settle();
          const ready = await f.locator(`${SUMMARY_ITEM}:has-text("Happy birthday, Sam!"):has-text("gift-card.jpg")`).count();
          if (!ready) {
            // Re-record safety: rebuild the box off-camera (same picks and answers as chapters 4-5).
            emptyCart();
            await f.goto(`http://localhost:10013${BOX_URL}`, { waitUntil: 'domcontentloaded' });
            await v.settle();
            await buildStepOne(f);
            await f.locator('.arraysubs-box-next').click();
            await f.locator('.arraysubs-box-next').click();
            await f.locator(`${ACTIVE} select.arraysubs-box-field`).selectOption('Espresso');
            await f.locator(`${ACTIVE} label.arraysubs-box-choice:has-text("Chocolatey")`).click();
            await f.locator(`${ACTIVE} input.arraysubs-box-field:not([type="file"])`).first().fill('Happy birthday, Sam!');
            await attachPhoto(f);
            await f.locator('.arraysubs-box-next').click();
            await f.locator('.arraysubs-box-submit').click();
            await f.waitForURL(/checkout/, { timeout: 90000 }).catch(() => {});
            if (!f.url().includes(checkout)) await f.goto(`http://localhost:10013${checkout}`, { waitUntil: 'domcontentloaded' });
            await v.settle();
          }
          await f.evaluate(() => { const el = document.querySelector('.wc-block-components-order-summary-item'); window.scrollTo(0, el.getBoundingClientRect().top + scrollY - 40); });
        });
        await v.say('The whole box is **one line** at checkout.', { hold: 0.8 });
        await v.zoom(await unionRect(v, SUMMARY_ITEM, 10), { scale: 1.5 });
        await v.focus(await textRect(v, `${SUMMARY_ITEM} :is(.arraysubs-subscription-meta--renewals, .arraysubs-subscription-meta--next-charge)`, 4), { label: 'Renewal price and date', side: 'left', pad: 2, hold: 1.4 });
        await v.focus(await textRect(v, `${SUMMARY_ITEM} :is(.arraysubs-subscription-meta--box-contents, .arraysubs-subscription-meta--box-freebie, .arraysubs-subscription-meta--box-discount)`, 4), { label: 'Contents, free gift, discount', side: 'left', pad: 2, hold: 1.8 });
        await v.unfocus();
        await v.zoomOut();
        await v.focus(await textRect(v, `${SUMMARY_ITEM} .arraysubs-subscription-meta--box-input`, 5), { label: 'Her answers and photo', side: 'left', pad: 2, hold: 1.6 });
        await v.say('The price column shows **only the amount**.', { hold: 0.6 });
        await v.focus(await textRect(v, `${SUMMARY_ITEM} .wc-block-components-order-summary-item__total-price`, 6), { label: 'Just the amount', side: 'top', hold: 1.4 });
        await v.unfocus();
        await v.scrollTo('.wc-block-components-checkout-place-order-button', { block: 'center' });
        await v.say('She pays by **bank transfer** in this demo.', { hold: 0.8 });
        await v.click('.wc-block-components-checkout-place-order-button', { nav: true, wait: 1500, hold: 0.2 });
        if (!/order-received/.test(v.frame.url())) {
          await v.act(async (f) => { await f.waitForURL(/order-received/, { timeout: 90000 }).catch(() => {}); });
        }
        await v.scrollTo('table.wc-block-order-confirmation-totals__table, .woocommerce-table--order-details', { block: 'start' });
        await v.say('Each item becomes its own **0.00$ line** to pack.', { hold: 0.8 });
        await v.focus(await unionRect(v, ':is(table.wc-block-order-confirmation-totals__table, .woocommerce-table--order-details) tr.order_item', 4), { label: 'Box line, then every item', hold: 2.0 });
        await v.unfocus();
        await v.hold(0.4, { note: 'order received' });
      },
    },

    // 7 ------------------------------------------------------------------ My Account
    {
      kicker: 'My Account',
      title: 'Their box, *one subscription*',
      sub: 'Contents and answers, managed as one.',
      before: async () => { confirmTransfer(); },
      run: async (v) => {
        const t = latestTake();
        await v.as(CUSTOMER);
        // Display-only: the store titles this endpoint "Memberships"; the coffee-box tour reads "Subscriptions".
        await v.context.addCookies([{ name: 'fv_clip', value: CLIP_COOKIE, domain: 'localhost', path: '/', expires: Math.floor(Date.now() / 1000) + 86400 }]);
        await v.goto('/my-account/as-subscriptions/', { hold: 0.3 });
        const subCard = `.arraysubs-subscription-card:has-text("#${t.sub}")`;
        await v.scrollTo(subCard, { block: 'start', offset: 12 });
        await v.say('Once the transfer clears, the box is **active**.', { hold: 0.8 });
        await v.focus(subCard, { label: 'Active, 52.70$ every month', pad: 4, side: 'bottom', hold: 1.8 });
        await v.unfocus();
        await v.click(`${subCard} a:has-text("View Details")`, { nav: true, hold: 0.4 });
        await v.hideCursor();
        await v.scrollTo('tr:has(th:has-text("Box Contents"))', { block: 'start', offset: -30 });
        await v.say('**Box Contents** lists every item, the gift and the saving.', { hold: 0.8 });
        await v.focus('tr:has(th:has-text("Box Contents"))', { label: 'Ships every month', pad: 4, side: 'right', hold: 1.9 });
        await v.focus(await unionRect(v, 'tr:has(th:text-matches("^(Grind|Flavor notes|Gift note|Gift card photo):?$"))', 4), { label: 'Her answers', side: 'right', hold: 1.6 });
        await v.unfocus();
        await v.scrollTo('h3:has-text("Manage Your Subscription")', { block: 'start' });
        await v.say('She manages the **whole box** at once: skip or pause.', { hold: 0.8 });
        await v.focus(await unionRect(v, '.subscription-skip-pause__section', 6), { label: 'One box, one set of actions', hold: 1.7 });
        await v.unfocus();
        await v.hold(0.3, { note: 'my account end' });
      },
    },

    // 8 ------------------------------------------------------------------ admin: subscription + renewal packing list
    {
      kicker: 'Admin',
      title: 'A ready *packing list*',
      sub: 'Every renewal order lists the items and the answers.',
      before: async () => {
        const t = latestTake();
        if (!t) throw new Error('no box order yet');
        ensureRenewal(t);
      },
      run: async (v) => {
        const t = latestTake();
        const renewal = ensureRenewal(t);
        await v.as(1);
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${t.sub}`, { hold: 0.3 });
        await v.say('The subscription holds **the box she built**.', { hold: 0.8 });
        await v.scrollTo('section.arraysubs-sd__panel:has(h3:has-text("Box Contents"))', { block: 'start', offset: -70 });
        const panel = await topSlice(v, 'section.arraysubs-sd__panel:has(h3:has-text("Box Contents"))', 470, 2);
        await v.zoom({ x: panel.x - 12, y: panel.y, w: 1100, h: panel.h }, { scale: 1.3 });
        await v.focus(panel, { label: 'Items, gift, discount, answers', hold: 2.2 });
        await v.unfocus();
        await v.zoomOut();

        await v.goto(`/wp-admin/admin.php?page=wc-orders&action=edit&id=${renewal}`, { hold: 0.3 });
        await v.scrollTo('#woocommerce-order-items', { block: 'start' });
        await v.say('Every renewal order is a **ready packing list**.', { hold: 0.8 });
        await v.focus('#woocommerce-order-notes li.note:has-text("Renewal order for subscription")', { label: 'Next month\'s renewal', side: 'left', pad: 4, hold: 1.6 });
        await v.unfocus();
        const items = await unionRect(v, '#order_line_items tr.item', 4);
        const box = await unionRect(v, '#woocommerce-order-items', 0);
        await v.zoom(fromLeft(box.x - 14, { ...items, y: items.y + 30 }, 1.4), { scale: 1.4 });
        await v.focus('#order_line_items tr.item >> nth=0', { label: 'Same answers and photo', pad: 2, side: 'right', hold: 1.8 });
        await v.focus(await unionRect(v, '#order_line_items tr.item:not(:first-child) :is(td.thumb, td.name, td.item_cost, td.quantity, td.line_cost)', 2), { label: 'Each item and the gift at 0.00$', side: 'top', hold: 1.8 });
        await v.unfocus();
        await v.zoomOut();
        await v.say('Every renewal ships **these items and answers**.', { hold: 0.6 });
        await v.focus(await unionRect(v, '#order_line_items tr.item', 4), { label: 'Ready to pack', side: 'right', hold: 1.4 });
        await v.unfocus();
        await dropUnload(v);
      },
    },
  ],
};
