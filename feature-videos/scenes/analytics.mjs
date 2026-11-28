// Analytics tour: free Home dashboard (health hero, KPIs, renewals, movement, status,
// Action Center) -> a customer subscribes on the storefront -> the sign-up lands on Home
// and on her member page -> Pro order types and KPIs in WooCommerce Analytics, churn risk
// scores, the saved AI revenue forecast, Retention and Refunds.
// Store changes and how to undo them: notes/analytics.md
import { wp, SITE } from '../lib/director.mjs';

const CUSTOMER = 497; // Abigail Cook, in this scene's customer block (495-555)
const PRODUCT = 6713; // Weekly Coffee Delivery, 14$ / week
const PRODUCT_URL = '/product/demo-%c2%b7-weekly-coffee-delivery/';
const SINCE = '2026-11-27 21:30:00'; // UTC; orders by CUSTOMER for PRODUCT after this are this scene's takes
const SITE_TZ = 'Asia/Dhaka'; // the store's UTC+6 offset (gmt_offset 6, no DST)

const HOME = '/wp-admin/admin.php?page=arraysubs-mainadmin#/overview';
const MEMBER = `/wp-admin/admin.php?page=arraysubs-mainadmin#/manage-members/${CUSTOMER}`;
const wc = (p, q = '') => `/wp-admin/admin.php?page=wc-admin&path=${encodeURIComponent(p)}${q}`;
const OVERVIEW = wc('/analytics/overview', '&period=month&compare=previous_period');
const REVENUE = wc('/analytics/revenue', '&period=month&compare=previous_period');
const ORDERS = wc('/analytics/orders', '&period=month&compare=previous_period&orderby=date&order=desc');
const CHURN = wc('/analytics/arraysubs-ai-churn');
const FORECAST = wc('/analytics/arraysubs-ai-forecast');
const RETENTION = wc('/analytics/arraysubs-retention');
const REFUNDS = wc('/analytics/arraysubs-refunds');

const php = (code) => wp(['eval', code]);
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const num = (s) => { const m = String(s || '').match(/-?[\d,]+(?:\.\d+)?/); return m ? Number(m[0].replace(/,/g, '')) : NaN; };
const fmt = (n) => Math.round(n).toLocaleString('en-US');
// The first line that is only a number ("NEXT 7 DAYS / 107 / 7,055$ expected" -> 107).
const lineNum = (t) => { const l = String(t || '').split('\n').map((x) => x.trim()).find((x) => /^[\d,]+$/.test(x)); return l ? Number(l.replace(/,/g, '')) : NaN; };

// Keep the real mouse in a quiet spot: the right gutter of the page, away from the WP admin
// menu (hover fly-outs), charts (tooltips) and product images (WooCommerce hover zoom).
const park = async (v) => {
  v.cursor.x = 1432;
  v.cursor.y = 790;
  await v.page.mouse.move(1432, 790);
};

// Mid-chapter: fade the visible cursor out, then park the real mouse.
const rest = async (v) => {
  await v.hideCursor();
  await park(v);
};

// Click through Playwright (for menus that ignore the synthetic mouse click), with the
// visible cursor travelling there and pressing first.
async function pressOn(v, sel, after = null) {
  await v.moveTo(sel);
  await v.animate(0.1, (t) => { v.cursor.press = t; });
  await v.act(async (f) => {
    // dispatchEvent: the hidden one-time WooCommerce tour leaves #wpwrap catching pointer events.
    await f.locator(sel).first().dispatchEvent('click');
    if (after) await f.waitForSelector(after, { state: 'visible', timeout: 20000 });
  }, { settle: !after });
  v.cursor.press = 0;
  await v.hold(0.3, { still: false });
}

// Visible click on a link to another page: cursor travels and presses, then the real click,
// the navigation and the data wait run off-camera, and the crossfade shows the drawn page.
async function clickToPage(v, sel, ready) {
  await v.moveTo(sel);
  if (v.ring) await v.unfocus({ dur: 0.15 });
  await v.animate(0.1, (t) => { v.cursor.press = t; });
  await v.act(async (f) => {
    await Promise.all([
      f.waitForNavigation({ timeout: 60000 }),
      f.locator(sel).first().click({ timeout: 15000 }),
    ]);
    await f.waitForFunction(ready, null, { timeout: 180000, polling: 250 });
    await sleep(800);
  }, { settle: false });
  v.cursor.press = 0;
  await v.hold(0.3, { still: false });
}

// Visible click whose result needs a long wait (Home Refresh): the crossfade goes straight
// from the old picture to the finished one, never through a loading state.
async function clickAndWait(v, sel, ready, { delay = 500, extra = 700 } = {}) {
  const p = await v.moveTo(sel);
  if (v.ring) await v.unfocus({ dur: 0.15 }); // never leave a "before" label over the new numbers
  await v.animate(0.1, (t) => { v.cursor.press = t; });
  v.ripple = { x: p.x, y: p.y, p: 0.02 };
  await v.transition(async () => {
    await v.page.mouse.click(p.x, p.y);
    await sleep(delay);
    await v.frame.waitForFunction(ready, null, { timeout: 150000, polling: 250 });
    await sleep(extra);
  }, { settle: false, fade: 0.3, during: (t) => { v.cursor.press = 1 - t; v.ripple.p = Math.max(0.02, t); } });
  v.cursor.press = 0;
  v.ripple = null;
  await v.hold(0.3, { still: false });
}

// Display-only CSS for the store frame: hide the dismissible setup checklist on Home,
// WooCommerce's one-time "Orders are now reported based on payment dates" tour, and on the
// storefront the seed SKU, category, breadcrumb, stock count, the theme's test-page menu
// links (Checkout Classic, Cart Page), the "added to your cart" banner on checkout, and the
// seed role slugs on the member page.
const SCENE_CSS = [
  '.arraysubs-onboarding{display:none!important}',
  '[class*="woocommerce-tour-kit"]{display:none!important}',
  '.wp-block-woocommerce-breadcrumbs,.woocommerce-breadcrumb,.wp-block-woocommerce-product-sku,.taxonomy-product_cat.wp-block-post-terms,.single-product p.stock{display:none!important}',
  '.wp-block-navigation-item:has(> a[href$="/checkout/"]),.wp-block-navigation-item:has(> a[href*="/cart-2/"]){display:none!important}',
  'body.woocommerce-checkout .wc-block-components-notice-banner.is-success{display:none!important}',
  // member page: keep the Customer badge, hide the seed data's demo_* role slugs
  '.arraysubs-member-insight__role-badge:not(:first-child){display:none!important}',
  // subscriptions list: an old QA fixture (on hold, no product, guest) in the On-hold list
  'tr.arraysubs-dl-row:has(a[href="#/subscriptions/detail/5278"]){display:none!important}',
].join('');

// A take that includes chapter 4 must start before Abigail's sign-up: the first recorded
// chapter of such a take removes the previous take's order (chapters 1-3 show lists where
// her subscription would otherwise already appear).
let resetDone = false;
function resetBeforeSignup(v) {
  if (resetDone || (v.only && !v.only.has(4))) return;
  console.log(`  ${resetCustomer()}`);
  resetDone = true;
}

const flushHome = () => php('\\ArraySubs\\Features\\MainAdmin\\Support\\Overview\\Builder::flush(); echo "ok";');

// Empty Abigail's cart, and remove the order + subscription from earlier takes of chapter 4.
function resetCustomer() {
  return php(`
    global $wpdb;
    delete_user_meta(${CUSTOMER}, '_woocommerce_persistent_cart_1');
    $wpdb->delete($wpdb->prefix . 'woocommerce_sessions', ['session_key' => '${CUSTOMER}']);
    $orders = wc_get_orders(['customer_id' => ${CUSTOMER}, 'limit' => -1, 'date_created' => '>' . strtotime('${SINCE} UTC'), 'status' => 'any']);
    $gone = [];
    foreach ($orders as $o) {
      $has = false;
      foreach ($o->get_items() as $it) { if ((int) $it->get_product_id() === ${PRODUCT}) { $has = true; } }
      if (!$has) { continue; }
      $subs = get_posts(['post_type' => 'arraysubs_data', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_parent_order_id', 'meta_value' => $o->get_id()]);
      foreach ($subs as $s) {
        if (class_exists('\\\\ArraySubs\\\\Supports\\\\ActionScheduler')) { \\ArraySubs\\Supports\\ActionScheduler::unscheduleAllForSubscription((int) $s); }
        wp_delete_post((int) $s, true);
        $gone[] = 'sub#' . $s;
      }
      $gone[] = 'order#' . $o->get_id();
      $o->delete(true);
    }
    if (class_exists('\\\\Automattic\\\\WooCommerce\\\\Admin\\\\API\\\\Reports\\\\Cache')) { \\Automattic\\WooCommerce\\Admin\\API\\Reports\\Cache::invalidate(); }
    echo 'reset ' . implode(',', $gone);
  `);
}

// The bank transfer for this take's order has cleared: mark it paid (activates the
// subscription) and sync it into the WooCommerce Analytics tables. Idempotent.
function payLatestOrder() {
  return php(`
    $orders = wc_get_orders(['customer_id' => ${CUSTOMER}, 'limit' => 5, 'orderby' => 'date', 'order' => 'DESC', 'date_created' => '>' . strtotime('${SINCE} UTC'), 'status' => 'any']);
    foreach ($orders as $o) {
      $has = false;
      foreach ($o->get_items() as $it) { if ((int) $it->get_product_id() === ${PRODUCT}) { $has = true; } }
      if (!$has) { continue; }
      if (!$o->is_paid()) { $o->payment_complete(); }
      if (class_exists('\\\\Automattic\\\\WooCommerce\\\\Internal\\\\Admin\\\\Schedulers\\\\OrdersScheduler')) { \\Automattic\\WooCommerce\\Internal\\Admin\\Schedulers\\OrdersScheduler::import($o->get_id()); }
      if (class_exists('\\\\Automattic\\\\WooCommerce\\\\Admin\\\\API\\\\Reports\\\\Cache')) { \\Automattic\\WooCommerce\\Admin\\API\\Reports\\Cache::invalidate(); }
      echo $o->get_id();
      return;
    }
    echo 'none';
  `);
}

// Open a page off-camera, wait until its data is drawn (and optionally scroll a target to
// the middle or near the top), then crossfade to it.
async function openPage(v, url, ready, { scroll = null, block = 'center', settleMs = 600, fresh = false, prep = null } = {}) {
  await v.zoomOut();
  await v.act(async (f) => {
    // fresh: drop the recording browser's own copy of Home (the admin keeps it in localStorage)
    if (fresh) await f.evaluate(() => { try { for (const k of Object.keys(localStorage)) if (/overview/i.test(k)) localStorage.removeItem(k); } catch {} }).catch(() => {});
    await f.goto(`${SITE}${url}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await f.waitForFunction(ready, null, { timeout: 240000, polling: 250 });
    if (prep) console.log(`  prep: ${await prep(f)}`);
    await sleep(settleMs);
    if (scroll) {
      await f.locator(scroll).first().evaluate((el, b) => {
        const r = el.getBoundingClientRect();
        const y = b === 'center' ? r.top + window.scrollY - (window.innerHeight - Math.min(r.height, window.innerHeight * 0.8)) / 2 - 16
          : r.top + window.scrollY - 110;
        window.scrollTo(0, Math.max(0, y));
      }, block);
      await sleep(250);
    }
  });
  await v.hold(0.4, { note: 'open' });
}
const openWc = (v, url, ready, opts = {}) => openPage(v, url, ready, { block: 'start', ...opts });
const openHome = (v, opts = {}) => openPage(v, HOME, READY.home, { settleMs: 500, ...opts });

// Load a report off-camera (in a chapter's before) so the on-camera load comes from warm caches.
async function prewarm(v, url, ready) {
  await v.as(1);
  await v.frame.goto(`${SITE}${url}`, { waitUntil: 'domcontentloaded', timeout: 120000 });
  await v.frame.waitForFunction(ready, null, { timeout: 300000, polling: 500 }).catch(() => console.log(`  [prewarm] not ready: ${url}`));
}

// Live count and MRR as the Home hero prints them.
async function heroNumbers(v) {
  return v.frame.evaluate(() => {
    const title = document.querySelector('.arraysubs-overview__hero-title')?.innerText || '';
    const mrr = document.querySelector('.arraysubs-overview__hero-mrr')?.innerText || '';
    const live = title.match(/([\d,]+)\s+live/);
    const money = mrr.match(/([\d,]+(?:\.\d+)?)\s*\$/);
    return { live: live ? Number(live[1].replace(/,/g, '')) : NaN, mrr: money ? Number(money[1].replace(/,/g, '')) : NaN };
  });
}

// Union rect of several targets (app pixels), padded.
async function unionRect(v, targets, pad = 0) {
  const rs = [];
  for (const t of targets) rs.push(await v.appRect(t));
  const x = Math.min(...rs.map((r) => r.x)) - pad;
  const y = Math.min(...rs.map((r) => r.y)) - pad;
  const x2 = Math.max(...rs.map((r) => r.x + r.w)) + pad;
  const y2 = Math.max(...rs.map((r) => r.y + r.h)) + pad;
  return { x, y, w: x2 - x, h: y2 - y };
}

const READY = {
  home: () => !!document.querySelector('.arraysubs-overview__widgets--history-risk')
    && !!document.querySelector('.arraysubs-overview__report-grid')
    && !!document.querySelector('.arraysubs-overview__widget')
    && ![...document.querySelectorAll('button')].some((b) => /Refresh/.test(b.innerText) && b.disabled),
  list: () => /\d+\s+items?/.test(document.querySelector('.arraysubs-dl-pagination-info')?.innerText || ''),
  member: () => !!document.querySelector('.arraysubs-member-insight__stats')
    && !!document.querySelector('.arraysubs-member-insight__chart svg')
    && /Weekly Coffee Delivery/.test(document.querySelector('.arraysubs-member-insight__table')?.innerText || ''),
  overview: () => document.querySelectorAll('.arraysubs-stat-card').length >= 10
    && !document.querySelector('.woocommerce-summary.is-placeholder, .woocommerce-summary__item-container.is-placeholder')
    && /\d/.test(document.querySelector('.arraysubs-overview-charts__body')?.innerText || ''),
  revenue: () => document.querySelectorAll('.woocommerce-summary__item').length >= 10 && !document.querySelector('.is-placeholder'),
  orders: () => document.querySelectorAll('.woocommerce-table__table tbody tr').length > 0 && !document.querySelector('.is-placeholder'),
  churn: () => document.querySelectorAll('a.arraysubs-ai-table__link').length > 5 && document.querySelectorAll('.recharts-surface').length >= 2 && !document.querySelector('.arraysubs-ai-cards__card--loading'),
  forecast: () => !!document.querySelector('.arraysubs-ai-summary') && document.querySelectorAll('.recharts-surface').length >= 2 && !document.querySelector('.arraysubs-ai-cards__card--loading'),
  retention: () => document.querySelectorAll('section.arraysubs-retention-report-card').length >= 6 && !document.querySelector('section[aria-busy="true"]') && document.querySelectorAll('.recharts-surface').length >= 3,
  refunds: () => document.querySelectorAll('section.arraysubs-refund-report-card').length >= 6 && !document.querySelector('section[aria-busy="true"]') && !!document.querySelector('.arraysubs-refund-kpis') && !document.querySelector('.arraysubs-refund-kpi__skeleton') && document.querySelectorAll('.recharts-surface').length >= 3,
};

// "107" on the Home card, "107 items" on the list: label the match only when it is one.
async function countLabel(v, homeCount) {
  const info = await v.frame.locator('.arraysubs-dl-pagination-info').first().innerText().catch(() => '');
  const n = num(info);
  return Number.isFinite(homeCount) && n === homeCount ? `Same ${fmt(n)} as on Home` : 'The filtered list';
}

// Display-only (recording browser): hide Home table rows priced 0.00$ (parallel agents'
// free Starter Membership sign-ups land in Recent subscriptions). A style rule keyed by the
// row link survives React re-renders. Nothing stored.
const hideFreeRows = (f) => f.evaluate(() => {
  const hrefs = [...document.querySelectorAll('a.arraysubs-overview__tr[href]')]
    .filter((a) => a.innerText.split('\n').some((l) => /^0\.00\s*\$$/.test(l.trim())))
    .map((a) => a.getAttribute('href'));
  if (!hrefs.length) return 'no 0.00$ rows';
  const s = document.createElement('style');
  s.textContent = hrefs.map((h) => `a.arraysubs-overview__tr[href=${JSON.stringify(h)}]{display:none!important}`).join('');
  document.head.appendChild(s);
  return `hid ${hrefs.join(', ')}`;
});

const W_CARD = (h) => `.arraysubs-overview__card:has(h3:has-text("${h}"))`;
const TILE = (label) => `.arraysubs-ai-cards__card:has(.arraysubs-ai-cards__label:text-is("${label}"))`;
const W_WIDGET = (h) => `.arraysubs-overview__widget:has(h3:has-text("${h}"))`;

export default {
  slug: 'analytics',
  tier: 'Free + Pro',
  intro: { title: 'Know your MRR at a *glance*', sub: 'A free Home dashboard, Pro reports in WooCommerce Analytics, and AI only when you click.' },
  outro: { title: 'Stop exporting orders. See your *MRR*.', sub: 'Free Home dashboard. Pro KPIs, churn scores, forecasts and retention reports.', url: 'subscriptionplugin.pro' },

  setup: async (v) => {
    await v.context.addInitScript((css) => {
      if (window.top === window) return;
      const add = () => {
        const host = document.head || document.documentElement;
        if (!host || document.getElementById('fv-analytics-css')) return;
        const s = document.createElement('style');
        s.id = 'fv-analytics-css';
        s.textContent = css;
        host.appendChild(s);
      };
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add);
      add();
    }, SCENE_CSS);
    // The AI Revenue Forecast formats its "Last generated" stamp in the browser's time zone.
    // The recording browser runs in UTC (harness-wide, and CDP refuses a second override), so
    // on that page only, date formatting defaults to the store's zone: what an admin in the
    // store's time zone sees. Every other page keeps the browser default.
    await v.context.addInitScript((tz) => {
      if (window.top === window) return;
      if (!decodeURIComponent(location.search).includes('arraysubs-ai-forecast')) return;
      for (const name of ['toLocaleString', 'toLocaleDateString', 'toLocaleTimeString']) {
        const orig = Date.prototype[name];
        Date.prototype[name] = function (loc, opts) { return orig.call(this, loc, { timeZone: tz, ...(opts || {}) }); };
      }
    }, SITE_TZ);
  },

  chapters: [
    // 1 ------------------------------------------------------------------ Home at a glance
    {
      kicker: 'Home',
      title: 'Your business at a *glance*',
      before: async (v) => {
        resetBeforeSignup(v);
        flushHome();
        await prewarm(v, HOME, READY.home);
      },
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openHome(v);
        await v.focus('.arraysubs-overview__hero', { dim: true, hold: 0.9 });
        v.poster();
        await v.say('Home opens with one plain sentence: your subscriber base is **growing**.', { hold: 1.0 });
        await v.focus('.arraysubs-overview__hero-title', { label: 'Growing, shrinking or steady', side: 'top' });
        await v.say('**Current MRR** counts paying subscriptions. Trials sit apart.', { hold: 0.4 });
        await v.unfocus();
        const heroRect = await unionRect(v, ['.arraysubs-overview__hero-title', '.arraysubs-overview__hero-mrr'], 30);
        await v.zoom(heroRect, { scale: 1.35 });
        await v.focus('.arraysubs-overview__hero-mrr', { label: 'Current MRR and ARR run rate', side: 'bottom' });
        await v.zoomOut();
        await v.focus('.arraysubs-overview__hero-spark', { label: '12 months of MRR', side: 'bottom' });
        await v.say('Three key numbers, each with its own **trend line**.', { hold: 0.6 });
        await v.focus('.arraysubs-overview__kpis', { label: 'Live, new and collected', side: 'bottom' });
        await v.say('Switch to **7, 30 or 90 days** or this month.', { hold: 0.4 });
        await v.click('button.arraysubs-overview__range:has-text("7 days")', { wait: 1500 });
        await v.focus('.arraysubs-overview__chip >> nth=1', { label: 'vs the 7 days before', side: 'top', pad: 6 });
        await v.click('button.arraysubs-overview__range:has-text("30 days")', { wait: 1500 });
        await rest(v);
        await v.scrollTo('.arraysubs-overview__widget:has-text("Revenue at risk")', { block: 'center' });
        await v.say('With **Pro**, insight cards join Home: forecast, revenue at risk, top products.', { hold: 0.6 });
        await v.focus(W_WIDGET('Revenue forecast'), { label: 'Saved AI forecast', side: 'top' });
        await v.focus(W_WIDGET('Revenue at risk'), { label: 'Monthly value at risk', side: 'top' });
        await v.focus(W_WIDGET('Top products by MRR'), { label: 'Top products by MRR', side: 'top' });
      },
    },

    // 2 ------------------------------------------------------------------ renewals, movement, status
    {
      kicker: 'Home',
      title: 'Renewals, movement and *status*',
      sub: 'Free: what renews next, who joined and who left.',
      before: async (v) => { resetBeforeSignup(v); },
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openHome(v);
        await v.scrollTo(W_CARD('Upcoming renewals'), { block: 'center' });
        await v.say('**Upcoming renewals**: the next charges your customers will see.', { hold: 0.8 });
        await v.focus(W_CARD('Upcoming renewals'), { label: 'The next 5 charges', side: 'top' });
        await v.scrollTo('.arraysubs-overview__split', { block: 'center' });
        await v.say('**Subscriber movement**: new against cancelled, with the net line.', { hold: 0.6 });
        await v.hover('.arraysubs-overview__chart', { dx: 0.79, dy: 0.45, hold: 1.8 });
        await v.say('**Status mix** counts every status. Select one to open its list.', { hold: 0.4 });
        await v.hover('.arraysubs-overview__status-legend a:has-text("Active")', { hold: 1.6 });
        await rest(v);
        await v.scrollTo('.arraysubs-overview__stack', { block: 'center' });
        await v.say('**Renewal outlook**: renewals due ahead, with the amount expected.', { hold: 0.6 });
        await v.focus('.arraysubs-overview__windows', { label: 'Due in 7, 30 and 90 days', side: 'top' });
        await v.focus('.arraysubs-overview__ops', { label: 'No stalled or failed jobs', side: 'bottom' });
        const homeCount = lineNum(await v.frame.locator('a.arraysubs-overview__window >> nth=0').innerText());
        await v.say('One click opens **exactly those** subscriptions.', { hold: 0.3 });
        await v.click('a.arraysubs-overview__window >> nth=0', { wait: 600 });
        await v.act(async (f) => { await f.waitForFunction(READY.list, null, { timeout: 60000 }); });
        await rest(v);
        await v.focus(v.frame.locator('button:has-text("Clear filter")').locator('xpath=..'), { label: 'Renewals due in 7 days', side: 'top' });
        const label = await countLabel(v, homeCount);
        await v.scrollTo('.arraysubs-dl-pagination', { block: 'end' });
        // The count sits bottom-left, under the default caption spot: caption goes top-right.
        await v.say(label.startsWith('Same') ? 'The list holds the **same number** as the card.' : 'The list behind the number.', { pos: 'tr', hold: 0.2 });
        await v.focus('.arraysubs-dl-pagination-info', { label, side: 'top', pad: 8 });
      },
    },

    // 3 ------------------------------------------------------------------ Action Center
    {
      kicker: 'Home',
      title: 'A to-do list: the Action *Center*',
      sub: 'Free: six signals, each one click from its list.',
      before: async (v) => { resetBeforeSignup(v); },
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openHome(v);
        await v.scrollTo(W_CARD('Action Center'), { block: 'center' });
        await v.say('The **Action Center** counts six kinds of trouble, busiest first.', { hold: 0.6 });
        await v.focus(W_CARD('Action Center'), { label: 'Six signals, busiest first', side: 'right', dim: true });
        const firstRow = '.arraysubs-overview__signals > *:has-text("Review") >> nth=0';
        const firstText = await v.frame.locator(firstRow).innerText();
        const homeCount = lineNum(firstText);
        await v.unfocus();
        await v.zoom('.arraysubs-overview__signals', { scale: 1.35 });
        await v.focus(firstRow, { label: /hold/i.test(firstText) ? 'Failed payment or manual hold' : 'The busiest signal', side: 'right', pad: 4 });
        await v.zoomOut();
        await v.say('Quiet signals read **Clear**. Fixed end dates come from **Pro**.', { hold: 0.3 });
        await v.focus('.arraysubs-overview__signals > *:has-text("Expiring fixed-term")', { label: 'Pro: fixed-term plans', side: 'right', pad: 4 });
        await v.say('**Review** opens just those subscriptions, by the same rule.', { hold: 0.3 });
        await v.click(`${firstRow} >> a.arraysubs-overview__signal-action`, { wait: 600 });
        await v.act(async (f) => { await f.waitForFunction(READY.list, null, { timeout: 60000 }); });
        await rest(v);
        await v.focus(v.frame.locator('button:has-text("Clear filter")').locator('xpath=..'), { label: 'Same rule as on Home', side: 'top' });
        const label = await countLabel(v, homeCount);
        await v.scrollTo('.arraysubs-dl-pagination', { block: 'end' });
        // The count sits bottom-left, under the default caption spot: caption goes top-right.
        await v.say(label.startsWith('Same') ? 'The list holds the **same number** as the card.' : 'The list behind the number.', { pos: 'tr', hold: 0.2 });
        await v.focus('.arraysubs-dl-pagination-info', { label, side: 'top', pad: 8 });
      },
    },

    // 4 ------------------------------------------------------------------ customer subscribes
    {
      kicker: 'Storefront',
      title: 'A customer *subscribes*',
      sub: 'A real order on the store, paid by bank transfer.',
      before: async () => { console.log(`  ${resetCustomer()}`); },
      run: async (v) => {
        await v.as(CUSTOMER);
        await park(v);
        await v.goto(PRODUCT_URL);
        await v.say('Meanwhile, Abigail subscribes to **Weekly Coffee Delivery**.');
        await v.focus(':is(.summary .price, .wp-block-woocommerce-product-price, .wc-block-components-product-price) >> nth=0', { label: '14$ every week', side: 'right' });
        await v.click('button.single_add_to_cart_button', { nav: true, wait: 800 });
        await rest(v);
        await v.say('Checkout shows the renewal price and the **next charge**.', { hold: 0.4 });
        await v.zoom('.wc-block-components-order-summary-item', { scale: 1.6 });
        await v.focus('.wc-block-components-order-summary-item', { label: 'Renewal and next charge', side: 'left' });
        await v.zoomOut();
        // Place Order sits at the bottom-left, under the default caption spot: caption goes top-right.
        await v.say('She places the order.', { pos: 'tr', hold: 0.2 });
        await v.click('.wc-block-components-checkout-place-order-button', { nav: true, wait: 1500 });
        await rest(v);
        await v.say('Ordered with **bank transfer**: the order is in.', { pos: 'bl' });
        await v.zoom({ x: 320, y: 140, w: 800, h: 520 }, { scale: 1.3 });
        await v.hold(1.2);
        await v.zoomOut();
      },
    },

    // 5 ------------------------------------------------------------------ outcome on Home + member page
    {
      kicker: 'Home',
      title: 'The sign-up lands on *Home*',
      sub: 'Free: Home and her member page catch up.',
      run: async (v) => {
        await v.as(1);
        await park(v);
        // Rebuild Home's copy right before it is shown (the transfer is still unpaid), so the
        // window in which other activity on the store can move the numbers stays short.
        flushHome();
        await openHome(v, { fresh: true });
        const before = await heroNumbers(v);
        await v.say('Her transfer clears. **Refresh** rebuilds Home on demand.', { hold: 0.6 });
        await v.focus('.arraysubs-overview__hero-mrr', { label: 'Before the refresh', side: 'bottom' });
        console.log(`  paid order ${payLatestOrder()}`);
        await clickAndWait(v, 'button:has-text("Refresh")', READY.home);
        const after = await heroNumbers(v);
        const dLive = after.live - before.live;
        const dMrr = after.mrr - before.mrr;
        console.log(`  hero before ${JSON.stringify(before)} after ${JSON.stringify(after)}`);
        // Label only a change that is exactly Abigail's: one live subscription, 14$ a week
        // (about 60.67$ a month, so 60 or 61 between two rounded screens).
        if (dLive === 1 && dMrr >= 59 && dMrr <= 62) {
          await v.focus('.arraysubs-overview__hero-mrr', { label: `+${fmt(dMrr)}$ MRR, +1 live`, side: 'bottom', hold: 2.4 });
        } else if (dLive === 1) {
          await v.focus('.arraysubs-overview__hero-title', { label: `Now ${fmt(after.live)} live`, side: 'bottom', hold: 2.2 });
        } else {
          await v.focus('text=Last updated just now', { label: 'Rebuilt just now', side: 'bottom', pad: 6 });
        }
        await rest(v);
        await v.scrollTo(W_CARD('Recent subscriptions'), { block: 'center' });
        await v.say('The new subscription shows up in **Recent subscriptions**.', { hold: 0.4 });
        const row = v.frame.locator(`${W_CARD('Recent subscriptions')} a.arraysubs-overview__tr:has-text("Abigail Cook")`).first();
        if (await row.count()) {
          await v.focus(row, { label: 'New sign-up', side: 'bottom', pad: 4, dim: true });
        } else {
          await v.focus(W_CARD('Recent subscriptions'), { label: 'The latest sign-ups', side: 'top' });
        }
        await openPage(v, MEMBER, READY.member);
        await v.say('Her **member page** sums up her history on one screen.', { hold: 0.4 });
        await v.focus('.arraysubs-member-insight__stats', { label: 'Spent, orders, yearly value', side: 'bottom' });
        await v.focus('.arraysubs-member-insight__card:has(.arraysubs-member-insight__chart)', { label: '12 months of orders and renewals', side: 'top' });
        await v.scrollTo('.arraysubs-member-insight__section:has-text("Subscriptions")', { block: 'center' });
        const subRow = v.frame.locator('.arraysubs-member-insight__table tr:has-text("Weekly Coffee Delivery")').first();
        await v.focus(subRow, { label: 'The new weekly plan', side: 'bottom', pad: 4 });
      },
    },

    // 6 ------------------------------------------------------------------ Pro: order types + KPIs
    {
      kicker: 'Pro reports',
      title: 'Subscription KPIs in *WooCommerce*',
      sub: 'ArraySubs **Pro**: order types and 10 KPI cards.',
      before: async (v) => {
        await prewarm(v, ORDERS, READY.orders);
        await prewarm(v, REVENUE, READY.revenue);
        await prewarm(v, OVERVIEW, READY.overview);
      },
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openWc(v, ORDERS, READY.orders);
        await v.say('With **Pro**, every order gets a **type**: sign-ups and renewals stay apart.', { hold: 0.3 });
        const typeOption = '.components-popover button.woocommerce-filters-filter__button:has-text("Subs Purchase")';
        await pressOn(v, 'button.woocommerce-dropdown-button:has-text("All Types")', typeOption);
        await v.focus(typeOption, { label: 'Six order types', side: 'right', pad: 4 });
        await pressOn(v, typeOption);
        await v.act(async (f) => { await f.waitForFunction(READY.orders, null, { timeout: 60000 }); });
        await rest(v);
        await v.scrollTo('.woocommerce-table__table', { block: 'start', offset: -70 });
        const ord = v.frame.locator('.woocommerce-table__table tbody tr:has-text("Abigail")').first();
        if (await ord.count()) {
          const r = await v.appRect(ord);
          await v.focus({ x: r.x, y: r.y, w: Math.min(r.w, 1404 - r.x), h: r.h }, { label: 'Abigail: Subs Purchase', side: 'bottom', pad: 2 });
        }
        await openWc(v, REVENUE, READY.revenue);
        await v.say('Revenue splits into **sign-ups, renewals**, plan switches and credit.', { hold: 0.4 });
        await v.focus('.woocommerce-summary__item:has-text("Total Subs Purchase Amount")', { label: 'New sign-ups', side: 'bottom' });
        const renewSel = '.woocommerce-summary__item:has-text("Total Subs Renew Amount")';
        const renewReport = num((await v.frame.locator(renewSel).first().innerText()).replace(/^[^\d]*/, ''));
        await v.focus(renewSel, { label: 'Renewals', side: 'bottom' });
        await openWc(v, OVERVIEW, READY.overview);
        await v.say('Pro adds **10 subscription cards** to WooCommerce Analytics.', { hold: 0.4 });
        await v.focus('.arraysubs-overview-stats__grid', { label: '10 subscription cards', side: 'bottom', dim: true });
        const renewCard = '.arraysubs-stat-card:has-text("Renewal Revenue")';
        const renewKpi = num((await v.frame.locator(renewCard).first().innerText()).replace(/^[^\d]*/, ''));
        console.log(`  renewal revenue: report ${renewReport} kpi ${renewKpi}`);
        if (Number.isFinite(renewKpi) && renewKpi === renewReport) {
          await v.say('Renewal revenue matches the **Revenue report** exactly.', { hold: 0.2 });
          await v.focus(renewCard, { label: 'Same as the Revenue report', side: 'bottom' });
        }
        await v.say('Green always means good, so **fewer cancellations** show green.', { hold: 0.3 });
        await v.focus('.arraysubs-stat-card:has-text("Churn Rate")', { label: 'A drop in churn is green', side: 'bottom', dim: true });
        await v.say('Pick any date range and compare with the **period or year before**.', { hold: 0.3 });
        await v.focus('.woocommerce-filters-filter:has(.woocommerce-filters-label:text-is("Date range")) button.woocommerce-dropdown-button', { label: 'Any range, any comparison', side: 'right' });
        await v.scrollTo('.arraysubs-overview-charts', { block: 'center' });
        await v.say('Chart **six metrics** by day, week, month, quarter or year.', { hold: 0.2 });
        // Keep the default Monthly Recurring Revenue tab: a Renewal Revenue headline by week
        // would show last week only, right after "matches the Revenue report".
        await v.click('button.arraysubs-overview-charts__interval:has-text("Week")', { wait: 800 });
        await rest(v);
        await v.focus('.arraysubs-overview-charts', { label: 'MRR week by week', side: 'bottom' });
        await v.scrollTo('.woocommerce-leaderboard:has-text("Top Subscription Products — Active")', { block: 'center' });
        await v.say('**Five leaderboards**, from top plans to top cancellation reasons.', { hold: 0.3 });
        await v.focus('.woocommerce-leaderboard:has-text("Top Subscription Products — Active")', { label: 'Active subs and MRR', side: 'top' });
      },
    },

    // 7 ------------------------------------------------------------------ churn risk
    {
      kicker: 'Pro reports',
      title: 'Find who might *leave*',
      sub: 'ArraySubs **Pro**: risk scores that need no AI.',
      before: async (v) => { await prewarm(v, CHURN, READY.churn); },
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openHome(v, { scroll: W_WIDGET('Churn risk'), prep: hideFreeRows });
        await v.say('Pro scores live subscriptions from **0 to 100**: high, medium, low.', { hold: 0.6 });
        await v.focus(W_WIDGET('Churn risk'), { label: 'Risk mix and top drivers', side: 'left' });
        // Visible press on the link, then the real click, navigation and data wait all happen
        // under the frozen picture (v.click captured frames while the report was still
        // drawing, and the stage's frame capture stalled there in 2 of 3 takes).
        await clickToPage(v, 'a:has-text("AI churn analysis")', READY.churn);
        await rest(v);
        await v.say('Each score comes from the subscription\'s **own history**. No AI needed.', { hold: 0.6 });
        // Ring the risk tiles only (the Live Subscriptions tile also counts records with no
        // billing data, which are not scored).
        const tiles = await unionRect(v, [TILE('High Risk'), TILE('Medium Risk'), TILE('Monthly Revenue At Risk')]);
        await v.focus(tiles, { label: 'High, medium, revenue at risk', side: 'top' });
        await v.say('AI is optional: pick a segment, then **Analyse with AI**.', { hold: 0.3 });
        const seg = await unionRect(v, ['.arraysubs-ai-report__section--toolbar select >> nth=0', '.arraysubs-ai-report__section--toolbar select >> nth=1'], 6)
          .catch(() => null);
        if (seg) await v.focus({ x: seg.x, y: seg.y - 24, w: seg.w, h: seg.h + 24 }, { label: 'All, active, trial or at-risk', side: 'right' });
        await v.focus('button.arraysubs-ai-btn--primary', { label: 'Only when you click', side: 'left' });
        await v.scrollTo('.arraysubs-ai-report__charts', { block: 'center' });
        await v.say('**Why they are at risk**: the signals behind the scores.', { hold: 0.4 });
        await v.focus('.arraysubs-ai-chart:has(h3:has-text("Why They Are At Risk"))', { label: 'The most common signals', side: 'top' });
        await v.scrollTo('.arraysubs-ai-table__title', { block: 'start', offset: -70 });
        await v.say('Every row shows its **score**, value, tenure and reasons.', { hold: 0.4 });
        // Frame the first row from Subscription to Why: the camera stops before the
        // Recommended Action column (it only fills in after an AI analysis).
        const ROW = 'tr.arraysubs-ai-table__row >> nth=0';
        const rowR = await v.appRect(ROW);
        const why = await v.appRect(`${ROW} >> td.arraysubs-ai-table__reason`);
        const actR = await v.appRect(`${ROW} >> td.arraysubs-ai-table__action`);
        const left = rowR.x - 14;
        const right = actR.x + 6;
        const zs = Math.min(1.8, Math.max(1.3, 1440 / (right - left)));
        // h + 80: centre a little lower so the WP admin bar stays out of the zoomed frame.
        await v.zoom({ x: left, y: rowR.y, w: right - left, h: rowR.h + 80 }, { scale: zs });
        await v.focus({ x: rowR.x, y: rowR.y, w: why.x + why.w - rowR.x, h: rowR.h }, { label: 'Score and up to 3 reasons', side: 'bottom', pad: 4, hold: 2.4 });
        await v.zoomOut();
        await v.say('Search the table, or **export it as CSV** for your team.', { hold: 0.2 });
        await v.scrollTo('input[placeholder^="Search"]', { block: 'center' });
        await v.type('input[placeholder^="Search"]', 'Coffee');
        await rest(v);
        await v.hold(1.0);
        await v.focus('button:has-text("Export CSV")', { label: 'Download the list', side: 'bottom' });
      },
    },

    // 8 ------------------------------------------------------------------ AI forecast
    {
      kicker: 'Pro reports',
      title: 'Plan ahead with an AI *forecast*',
      sub: 'ArraySubs **Pro**: AI runs only when you click.',
      before: async (v) => { await prewarm(v, FORECAST, READY.forecast); },
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openWc(v, FORECAST, READY.forecast);
        await v.say('With **Pro**, the Revenue Forecast starts from measured numbers.', { hold: 0.4 });
        await v.focus('.arraysubs-ai-report__card >> nth=0', { label: '8 measured tiles, no AI', side: 'bottom' });
        await v.say('A saved forecast loads with its **date**. AI runs only on click.', { hold: 0.3 });
        await v.focus('.arraysubs-ai-stamp', { label: 'Generated once, saved', side: 'right' });
        const ctl = await unionRect(v, ['.arraysubs-ai-report__section--toolbar button:has-text("MRR")', '.arraysubs-ai-report__section--toolbar select >> nth=0'], 6).catch(() => null);
        if (ctl) await v.focus({ x: ctl.x, y: ctl.y - 24, w: ctl.w, h: ctl.h + 24 }, { label: 'MRR or ARR, 6 to 24 months', side: 'right' });
        await v.scrollTo('.arraysubs-ai-chart:has(h3:has-text("Outlook"))', { block: 'center' });
        await v.say('MRR **month by month**, inside a conservative-to-optimistic range.', { hold: 0.3 });
        await v.hover('.arraysubs-ai-chart:has(h3:has-text("Outlook")) .recharts-wrapper', { dx: 0.7, dy: 0.42, hold: 2.4 });
        await rest(v);
        await v.scrollTo('.arraysubs-ai-summary', { block: 'start', offset: -70 });
        await v.say('The AI states its **outlook**, confidence and growth per month.', { hold: 0.4 });
        await v.focus('.arraysubs-ai-summary__badges', { label: 'Outlook, confidence, growth', side: 'bottom' });
        const lists = v.frame.locator('.arraysubs-ai-summary').getByText(/key assumptions/i).first();
        await v.scrollTo(lists, { block: 'start', offset: -60 });
        await v.say('Key **assumptions, risks** and opportunities, in plain words.', { hold: 0.2 });
        const r = await v.appRect(lists);
        await v.zoom({ x: 180, y: r.y - 10, w: 1220, h: 560 }, { scale: 1.18, hold: 1.6 });
        await v.zoomOut();
        await v.scrollTo('.arraysubs-ai-summary__meta', { block: 'center' });
        await v.say('Each report shows the **provider, model and tokens** it used.', { hold: 0.3 });
        await v.focus('.arraysubs-ai-summary__meta', { label: 'Provider, model, tokens', side: 'top' });
      },
    },

    // 9 ------------------------------------------------------------------ retention and refunds
    {
      kicker: 'Pro reports',
      title: 'Retention and *refunds*',
      sub: 'ArraySubs **Pro**: who stays, and what gets refunded.',
      before: async (v) => {
        await prewarm(v, RETENTION, READY.retention);
        await prewarm(v, REFUNDS, READY.refunds);
      },
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openWc(v, RETENTION, READY.retention, { scroll: 'section.arraysubs-retention-report-card' });
        await v.say('With **Pro**, Retention shows who stays, by signup month.', { hold: 0.4 });
        // Frame starts right of the admin menu (no half-cut menu words at 1.7x).
        await v.zoom({ x: 170, y: 150, w: 840, h: 330 }, { scale: 1.7 });
        await v.hold(1.4);
        await v.zoomOut();
        await v.scrollTo('section.arraysubs-retention-report-card:has(h2:has-text("Customer Retention Rate"))', { block: 'start', offset: -70 });
        await v.say('Customer and **MRR retention** over time.', { hold: 0.3 });
        await v.focus('section.arraysubs-retention-report-card:has(h2:has-text("Customer Retention Rate"))', { label: 'Customers kept', side: 'bottom' });
        await v.focus('section.arraysubs-retention-report-card:has(h2:has-text("MRR Retention Rate"))', { label: 'MRR kept', side: 'bottom' });
        await v.scrollTo('section.arraysubs-retention-report-card:has(h2:has-text("Top 10 Cancellation Reasons"))', { block: 'start', offset: -70 });
        await v.say('And the **reasons** people give when they cancel.', { hold: 0.4 });
        await v.focus('section.arraysubs-retention-report-card:has(h2:has-text("Top 10 Cancellation Reasons"))', { label: 'Top 10 reasons', side: 'left' });
        await openWc(v, REFUNDS, READY.refunds, { scroll: '.arraysubs-refund-kpis' });
        await v.say('**Refunds**: eight cards, each with its definition.', { hold: 0.4 });
        await v.focus('.arraysubs-refund-kpis', { label: '8 cards, each defined', side: 'bottom' });
        const card = 'section.arraysubs-refund-report-card:has(h2:has-text("Time to Refund"))';
        // Caption first, so the "eight cards" caption does not ride along with the scroll.
        await v.unfocus();
        await v.say('Time to refund in **seven buckets**, plus the median.', { hold: 0.4 });
        await v.scrollTo(card, { block: 'start', offset: -70 });
        const tr = await unionRect(v, [`${card} h2`, `${card} >> text=/Median elapsed/i`], 14).catch(() => null);
        await v.focus(tr || card, { label: 'Seven buckets and a median', side: 'bottom', hold: 2.6 });
      },
    },
  ],
};
