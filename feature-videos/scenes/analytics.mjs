// Analytics tour: free Home dashboard (health hero, KPIs, renewals, movement, status,
// Action Center) -> a customer subscribes on the storefront -> the sign-up lands on Home
// and in WooCommerce Analytics (order types) -> Pro KPIs, churn risk scores, the saved
// AI revenue forecast, Retention and Refunds.
// Store changes and how to undo them: notes/analytics.md
import { wp, SITE } from '../lib/director.mjs';

const CUSTOMER = 497; // Abigail Cook, in this scene's customer block (495-555)
const PRODUCT = 6713; // Weekly Coffee Delivery, 14$ / week
const PRODUCT_URL = '/product/demo-%c2%b7-weekly-coffee-delivery/';
const SINCE = '2026-11-27 21:30:00'; // UTC; orders by CUSTOMER for PRODUCT after this are this scene's takes

const HOME = '/wp-admin/admin.php?page=arraysubs-mainadmin#/overview';
const wc = (p, q = '') => `/wp-admin/admin.php?page=wc-admin&path=${encodeURIComponent(p)}${q}`;
const OVERVIEW = wc('/analytics/overview', '&period=month&compare=previous_period');
const REVENUE = wc('/analytics/revenue', '&period=month&compare=previous_period');
const ORDERS = wc('/analytics/orders', '&period=month&compare=previous_period&orderby=date&order=desc');
const CHURN = wc('/analytics/arraysubs-ai-churn');
const FORECAST = wc('/analytics/arraysubs-ai-forecast');
const RETENTION = wc('/analytics/arraysubs-retention');
const REFUNDS = wc('/analytics/arraysubs-refunds');

const php = (code) => wp(['eval', code]);

// Keep the real mouse away from the WP admin menu (a hover opens fly-outs that can catch clicks).
const park = (v) => v.page.mouse.move(v.cursor.x, v.cursor.y);

// Display-only CSS for the store frame: hide the dismissible setup checklist on Home and
// WooCommerce's one-time "Orders are now reported based on payment dates" tour.
const SCENE_CSS = '.arraysubs-onboarding{display:none!important}[class*="woocommerce-tour-kit"]{display:none!important}';

const flushHome = () => php('\\ArraySubs\\Features\\MainAdmin\\Support\\Overview\\Builder::flush(); echo "ok";');

// Empty Abigail's cart, and remove the order + subscription from earlier takes of chapter 4.
function resetCustomer() {
  php(`
    global $wpdb;
    delete_user_meta(${CUSTOMER}, '_woocommerce_persistent_cart_1');
    $wpdb->delete($wpdb->prefix . 'woocommerce_sessions', ['session_key' => '${CUSTOMER}']);
    $orders = wc_get_orders(['customer_id' => ${CUSTOMER}, 'limit' => -1, 'date_created' => '>' . strtotime('${SINCE} UTC'), 'status' => 'any']);
    foreach ($orders as $o) {
      $has = false;
      foreach ($o->get_items() as $it) { if ((int) $it->get_product_id() === ${PRODUCT}) { $has = true; } }
      if (!$has) { continue; }
      $subs = get_posts(['post_type' => 'arraysubs_data', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_parent_order_id', 'meta_value' => $o->get_id()]);
      foreach ($subs as $s) {
        if (class_exists('\\\\ArraySubs\\\\Supports\\\\ActionScheduler')) { \\ArraySubs\\Supports\\ActionScheduler::unscheduleAllForSubscription((int) $s); }
        wp_delete_post((int) $s, true);
      }
      $o->delete(true);
    }
    echo 'ok';
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

// Open a WooCommerce Analytics page off-camera, wait until its data is drawn (and optionally
// scroll), then crossfade to it.
async function openWc(v, url, ready, { scroll = null, settleMs = 600 } = {}) {
  await v.zoomOut();
  await v.act(async (f) => {
    await f.goto(`${SITE}${url}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await f.waitForFunction(ready, null, { timeout: 120000, polling: 250 });
    await new Promise((r) => setTimeout(r, settleMs));
    if (scroll) {
      await f.evaluate(([sel, off]) => {
        const el = document.querySelector(sel);
        if (el) window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - off);
      }, scroll);
    }
  });
  await v.hold(0.4, { note: 'open' });
}

// Open Home and wait until the dashboard (Pro rows and report cards included) is drawn.
async function openHome(v) {
  await v.zoomOut();
  await v.act(async (f) => {
    await f.goto(`${SITE}${HOME}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await f.waitForFunction(READY.home, null, { timeout: 150000, polling: 250 });
    await new Promise((r) => setTimeout(r, 500));
  });
  await v.hold(0.4, { note: 'open' });
}

const READY = {
  home: () => !!document.querySelector('.arraysubs-overview__widgets--history-risk')
    && !!document.querySelector('.arraysubs-overview__report-grid')
    && !!document.querySelector('.arraysubs-overview__widget')
    && ![...document.querySelectorAll('button')].some((b) => /Refresh/.test(b.innerText) && b.disabled),
  overview: () => document.querySelectorAll('.arraysubs-stat-card').length >= 10
    && !document.querySelector('.is-placeholder')
    && /\d/.test(document.querySelector('.arraysubs-overview-charts__body')?.innerText || ''),
  revenue: () => document.querySelectorAll('.woocommerce-summary__item').length >= 10 && !document.querySelector('.is-placeholder'),
  orders: () => document.querySelectorAll('.woocommerce-table__table tbody tr').length > 0 && !document.querySelector('.is-placeholder'),
  churn: () => document.querySelectorAll('a.arraysubs-ai-table__link').length > 5 && document.querySelectorAll('.recharts-surface').length >= 2 && !document.querySelector('.arraysubs-ai-cards__card--loading'),
  forecast: () => !!document.querySelector('.arraysubs-ai-summary') && document.querySelectorAll('.recharts-surface').length >= 2 && !document.querySelector('.arraysubs-ai-cards__card--loading'),
  retention: () => document.querySelectorAll('section.arraysubs-retention-report-card').length >= 6 && !document.querySelector('section[aria-busy="true"]') && document.querySelectorAll('.recharts-surface').length >= 3,
  refunds: () => document.querySelectorAll('section.arraysubs-refund-report-card').length >= 6 && !document.querySelector('section[aria-busy="true"]') && !!document.querySelector('.arraysubs-refund-kpis') && !document.querySelector('.arraysubs-refund-kpi__skeleton') && document.querySelectorAll('.recharts-surface').length >= 3,
};

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
  },

  chapters: [
    // 1 ------------------------------------------------------------------ Home at a glance
    {
      kicker: 'Home',
      title: 'Your business at a *glance*',
      before: async (v) => {
        flushHome();
        await v.as(1);
        await v.frame.goto(`${SITE}${HOME}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
        await v.frame.waitForFunction(READY.home, null, { timeout: 150000, polling: 250 });
      },
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openHome(v);
        await v.say('Home opens with one plain sentence: your subscriber base is **growing**.', { hold: 1.2 });
        await v.focus('.arraysubs-overview__hero-title', { label: 'Growing, shrinking or steady', side: 'bottom' });
        await v.zoom('.arraysubs-overview__hero-metrics', { scale: 1.7 });
        await v.say('**Current MRR** counts paying subscriptions. Trials sit apart.', { hold: 0.8 });
        await v.focus('.arraysubs-overview__hero-mrr', { label: 'Current MRR and ARR run rate', side: 'bottom' });
        v.poster();
        await v.focus('.arraysubs-overview__hero-spark', { label: '12 months of MRR', side: 'bottom' });
        await v.zoomOut();
        await v.say('Three key numbers, each with its own **trend line**.', { hold: 0.6 });
        await v.focus('.arraysubs-overview__kpis', { label: 'Live, new and collected', side: 'bottom' });
        await v.say('Switch to **7, 30 or 90 days** or this month.', { hold: 0.4 });
        await v.click('button.arraysubs-overview__range:has-text("7 days")');
        await v.focus('.arraysubs-overview__chip >> nth=1', { label: 'vs the 7 days before', side: 'bottom', pad: 6 });
        await v.click('button.arraysubs-overview__range:has-text("30 days")');
        await v.say('With **Pro**, insight cards join Home: forecast, revenue at risk, top products.', { hold: 0.8 });
        await v.focus('.arraysubs-overview__widget:has-text("Revenue forecast")', { label: 'Saved AI forecast', side: 'top' });
        await v.focus('.arraysubs-overview__widget:has-text("Revenue at risk")', { label: 'Monthly value at risk', side: 'top' });
        await v.focus('.arraysubs-overview__widget:has-text("Top products by MRR")', { label: 'Top products by MRR', side: 'top' });
      },
    },

    // 2 ------------------------------------------------------------------ renewals, movement, status
    {
      kicker: 'Home',
      title: 'Renewals, movement and *status*',
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openHome(v);
        await v.scrollTo('.arraysubs-overview__card:has(h3:has-text("Upcoming renewals"))', { block: 'center' });
        await v.say('**Upcoming renewals**: the next charges your customers will see.', { hold: 0.8 });
        await v.focus('.arraysubs-overview__card:has(h3:has-text("Upcoming renewals"))', { label: 'The next 5 charges', side: 'top' });
        await v.focus('.arraysubs-overview__card:has(h3:has-text("Recent subscriptions"))', { label: 'The latest sign-ups', side: 'top' });
        await v.scrollTo('.arraysubs-overview__split', { block: 'center' });
        await v.say('**Subscriber movement**: new against cancelled, with the net line.', { hold: 0.6 });
        await v.hover('.arraysubs-overview__chart', { dx: 0.79, dy: 0.45, hold: 1.8 });
        await v.say('**Status mix** counts every status. Select one to open its list.', { hold: 0.4 });
        await v.hover('.arraysubs-overview__status-legend a:has-text("Active")', { hold: 1.6 });
        await v.scrollTo('.arraysubs-overview__stack', { block: 'center' });
        await v.say('**Renewal outlook**: renewals due ahead, with the amount expected.', { hold: 0.6 });
        await v.focus('.arraysubs-overview__windows', { label: 'Due in 7, 30 and 90 days', side: 'top' });
        await v.focus('.arraysubs-overview__ops', { label: 'Renewal queue health', side: 'bottom' });
        await v.say('One click opens **exactly those** subscriptions.', { hold: 0.3 });
        await v.click('a.arraysubs-overview__window >> nth=0', { wait: 600 });
        await v.focus(v.frame.locator('button:has-text("Clear filter")').locator('xpath=..'), { label: 'Renewals due in 7 days', side: 'bottom' });
      },
    },

    // 3 ------------------------------------------------------------------ Action Center
    {
      kicker: 'Home',
      title: 'A to-do list: the Action *Center*',
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openHome(v);
        await v.scrollTo('.arraysubs-overview__card:has(h3:has-text("Action Center"))', { block: 'center' });
        await v.say('The **Action Center** counts six kinds of trouble, busiest first.', { hold: 0.6 });
        await v.focus('.arraysubs-overview__card:has(h3:has-text("Action Center"))', { label: 'Six signals, busiest first', side: 'right', dim: true });
        await v.zoom('.arraysubs-overview__signals', { scale: 1.6 });
        await v.focus('.arraysubs-overview__signals > *:has-text("Review") >> nth=0', { label: 'Held by a failed payment', side: 'bottom', pad: 4 });
        await v.say('Quiet signals simply read **Clear**.', { hold: 0.3 });
        await v.focus('.arraysubs-overview__signals > *:has-text("Clear") >> nth=0', { label: 'Nothing to do', side: 'bottom', pad: 4 });
        await v.zoomOut();
        await v.say('**Review** opens just those subscriptions, matching the count on Home.', { hold: 0.3 });
        await v.click('a.arraysubs-overview__signal-action >> nth=0', { wait: 600 });
        await v.focus(v.frame.locator('button:has-text("Clear filter")').locator('xpath=..'), { label: 'Same rule as on Home', side: 'bottom' });
      },
    },

    // 4 ------------------------------------------------------------------ customer subscribes
    {
      kicker: 'Storefront',
      title: 'A customer *subscribes*',
      before: async () => { resetCustomer(); },
      run: async (v) => {
        await v.as(CUSTOMER);
        await park(v);
        await v.goto(PRODUCT_URL);
        await v.say('Meanwhile, Abigail subscribes to **Weekly Coffee Delivery**.');
        await v.focus(':is(.summary .price, .wp-block-woocommerce-product-price, .wc-block-components-product-price) >> nth=0', { label: '14$ every week', side: 'right' });
        await v.click('button.single_add_to_cart_button', { nav: true, wait: 800 });
        await v.focus('.wc-block-components-order-summary-item', { label: 'Renewal and next charge', side: 'left' });
        await v.say('Checkout shows the renewal price and the **next charge**.');
        await v.click('.wc-block-components-checkout-place-order-button', { nav: true, wait: 1500 });
        await v.say('Paid by **bank transfer**, the order is in.');
        await v.hold(1.2);
      },
    },

    // 5 ------------------------------------------------------------------ outcome in reports
    {
      kicker: 'Reports',
      title: 'The sign-up lands in your *reports*',
      before: async () => { payLatestOrder(); },
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openHome(v);
        await v.say('Once the transfer clears, **Refresh** rebuilds Home on demand.');
        await v.click('button:has-text("Refresh")', { wait: 400, settle: false, hold: 0.3 });
        await v.act(async (f) => { await f.waitForFunction(READY.home, null, { timeout: 150000, polling: 250 }); });
        await v.focus('text=Last updated just now', { label: 'Fresh numbers', side: 'bottom', pad: 6 });
        await v.scrollTo('.arraysubs-overview__card:has(h3:has-text("Recent subscriptions"))', { block: 'center' });
        const row = v.frame.locator('a.arraysubs-overview__tr:has-text("Abigail Cook")').first();
        if (await row.count()) {
          await v.focus(row, { label: 'New sign-up', side: 'bottom', pad: 4 });
        } else {
          await v.focus('.arraysubs-overview__card:has(h3:has-text("Recent subscriptions"))', { label: 'The latest sign-ups', side: 'top' });
        }
        await v.say('The new subscription shows up in **Recent subscriptions**.');
        await openWc(v, ORDERS, READY.orders);
        await v.click('button.woocommerce-dropdown-button:has-text("All Types")', { settle: false, wait: 700 });
        await v.focus('.components-popover button.woocommerce-filters-filter__button:has-text("Subs Purchase")', { label: 'Six order types', side: 'right', pad: 4 });
        await v.click('.components-popover button.woocommerce-filters-filter__button:has-text("Subs Purchase")', { wait: 1500 });
        await v.act(async (f) => { await f.waitForFunction(READY.orders, null, { timeout: 60000 }); });
        await v.scrollTo('.woocommerce-table__table', { block: 'start' });
        const ord = v.frame.locator('.woocommerce-table__table tbody tr:has-text("Abigail")').first();
        if (await ord.count()) await v.focus(ord, { label: 'Type: Subs Purchase', side: 'bottom', pad: 4 });
        await v.say('Pro gives every order a **type**, so sign-ups and renewals stay apart.');
        await openWc(v, REVENUE, READY.revenue);
        await v.zoom('.woocommerce-summary', { scale: 1.3 });
        await v.focus('.woocommerce-summary__item:has-text("Total Subs Renew Amount")', { label: 'Renewals, apart', side: 'bottom' });
        await v.say('Revenue splits into **sign-ups, renewals**, plan switches and credit.');
        await v.zoomOut();
      },
    },

    // 6 ------------------------------------------------------------------ Pro KPIs
    {
      kicker: 'Reports',
      title: 'Subscription KPIs in *WooCommerce*',
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openWc(v, OVERVIEW, READY.overview);
        await v.focus('.arraysubs-overview-stats__grid', { label: '10 subscription cards', side: 'bottom', dim: true });
        await v.say('Pro adds **10 subscription cards** to WooCommerce Analytics.');
        await v.zoom('.arraysubs-stat-card >> nth=3', { scale: 1.8 });
        await v.focus('.arraysubs-stat-card >> nth=4', { label: 'A drop in churn is green', side: 'bottom' });
        await v.say('Green always means good, so **fewer cancellations** show green.');
        await v.zoomOut();
        await v.focus('.woocommerce-filters-date .woocommerce-dropdown-button', { label: 'Any range, any comparison', side: 'right' });
        await v.say('Pick any date range and compare with the **period or year before**.');
        await v.scrollTo('.arraysubs-overview-charts', { block: 'start' });
        await v.click('button.arraysubs-overview-charts__tab:has-text("Renewal Revenue")', { wait: 800 });
        await v.click('button.arraysubs-overview-charts__interval:has-text("Week")', { wait: 800 });
        await v.focus('.arraysubs-overview-charts__header', { label: '6 metrics, 5 intervals', side: 'bottom' });
        await v.say('Chart **six metrics** by day, week, month, quarter or year.');
        await v.scrollTo('.woocommerce-leaderboard:has-text("Top Subscription Products")', { block: 'start' });
        await v.focus('.woocommerce-leaderboard:has-text("Top Subscription Products — Active")', { label: 'Active subs and MRR', side: 'top' });
        await v.say('**Five leaderboards**, from top plans to top cancellation reasons.');
        await v.scrollTo('.woocommerce-leaderboard:has-text("Top Cancellation Reasons")', { block: 'center' });
        await v.focus('.woocommerce-leaderboard:has-text("Top Cancellation Reasons")', { label: 'Why people leave', side: 'top' });
      },
    },

    // 7 ------------------------------------------------------------------ churn risk
    {
      kicker: 'Reports',
      title: 'Find who might *leave*',
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openHome(v);
        await v.scrollTo('.arraysubs-overview__widget:has(h3:has-text("Churn risk"))', { block: 'center' });
        await v.focus('.arraysubs-overview__widget:has(h3:has-text("Churn risk"))', { label: 'High, medium, low', side: 'left' });
        await v.say('Pro gives every live subscription a **churn risk score**.');
        await v.click('a:has-text("AI churn analysis")', { nav: true, wait: 2500 });
        await v.act(async (f) => { await f.waitForFunction(READY.churn, null, { timeout: 90000 }); });
        await v.focus('.arraysubs-ai-report__card >> nth=0', { label: 'Scored from your own data', side: 'bottom' });
        await v.say('Scores from **0 to 100**, built from each subscription\'s own history.');
        await v.focus('button.arraysubs-ai-btn--primary', { label: 'Only when you click', side: 'left' });
        await v.say('AI is optional: **Analyse with AI** adds written next steps.');
        await v.scrollTo('.arraysubs-ai-report__charts', { block: 'center' });
        await v.focus('.arraysubs-ai-chart:has(h3:has-text("Why They Are At Risk"))', { label: 'The most common signals', side: 'top' });
        await v.say('**Why they are at risk**: the signals behind the scores.');
        await v.scrollTo('.arraysubs-ai-table__title', { block: 'start' });
        await v.focus('tr.arraysubs-ai-table__row >> nth=0', { label: 'Score and up to 3 reasons', side: 'bottom', pad: 4 });
        await v.say('Every row shows its **score**, value, tenure and reasons.');
        await v.type('input[placeholder^="Search"]', 'Coffee');
        await v.focus('button:has-text("Export CSV")', { label: 'Download the list', side: 'left' });
        await v.say('Search the table, or **export it as CSV** for your team.');
      },
    },

    // 8 ------------------------------------------------------------------ AI forecast
    {
      kicker: 'Reports',
      title: 'Plan ahead with an AI *forecast*',
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openWc(v, FORECAST, READY.forecast);
        await v.focus('.arraysubs-ai-report__card >> nth=0', { label: '8 measured tiles, no AI', side: 'bottom' });
        await v.say('The **Revenue Forecast** starts from measured numbers.');
        await v.focus('.arraysubs-ai-stamp', { label: 'Generated once, saved', side: 'right' });
        await v.say('A saved forecast loads with its **date**. AI runs only on click.');
        await v.scrollTo('.arraysubs-ai-chart:has(h3:has-text("Outlook"))', { block: 'center' });
        await v.say('MRR **month by month**, inside a conservative-to-optimistic range.');
        await v.hover('.arraysubs-ai-chart:has(h3:has-text("Outlook")) .recharts-surface', { dx: 0.67, dy: 0.5, hold: 2.2 });
        await v.scrollTo('.arraysubs-ai-summary', { block: 'start' });
        await v.focus('.arraysubs-ai-summary__badges', { label: 'Outlook, confidence, growth', side: 'bottom' });
        await v.say('The AI states its **outlook**, confidence and growth per month.');
        await v.zoom('.arraysubs-ai-summary', { scale: 1.4 });
        await v.say('Key **assumptions, risks** and opportunities, in plain words.');
        await v.zoomOut();
        await v.scrollTo('.arraysubs-ai-summary__meta', { block: 'center' });
        await v.focus('.arraysubs-ai-summary__meta', { label: 'Provider, model, tokens', side: 'top' });
        await v.say('Each report shows the **provider, model and tokens** it used.');
      },
    },

    // 9 ------------------------------------------------------------------ retention and refunds
    {
      kicker: 'Reports',
      title: 'Retention and *refunds*',
      run: async (v) => {
        await v.as(1);
        await park(v);
        await openWc(v, RETENTION, READY.retention, { scroll: ['section.arraysubs-retention-report-card', 110] });
        await v.say('**Retention** shows who stays, by signup month.');
        await v.zoom({ x: 200, y: 150, w: 720, h: 330 }, { scale: 1.7 });
        await v.hold(1.2);
        await v.zoomOut();
        await v.scrollTo('section.arraysubs-retention-report-card:has(h2:has-text("Customer Retention Rate"))', { block: 'start' });
        await v.focus('section.arraysubs-retention-report-card:has(h2:has-text("Customer Retention Rate"))', { label: 'Customers kept', side: 'bottom' });
        await v.focus('section.arraysubs-retention-report-card:has(h2:has-text("MRR Retention Rate"))', { label: 'MRR kept', side: 'bottom' });
        await v.say('Customer and **MRR retention** over time.');
        await v.scrollTo('section.arraysubs-retention-report-card:has(h2:has-text("Top 10 Cancellation Reasons"))', { block: 'start' });
        await v.focus('section.arraysubs-retention-report-card:has(h2:has-text("Top 10 Cancellation Reasons"))', { label: 'Top 10 reasons', side: 'left' });
        await v.say('And the **reasons** people give when they cancel.');
        await openWc(v, REFUNDS, READY.refunds, { scroll: ['.arraysubs-refund-kpis', 110] });
        await v.focus('.arraysubs-refund-kpis', { label: '8 cards, each defined', side: 'bottom' });
        await v.say('**Refunds**: eight cards, each with its definition.');
        await v.scrollTo('section.arraysubs-refund-report-card:has(h2:has-text("Time to Refund"))', { block: 'start' });
        await v.focus('section.arraysubs-refund-report-card:has(h2:has-text("Time to Refund"))', { label: 'Seven buckets and a median', side: 'right' });
        await v.say('Time to refund in **seven buckets**, plus the median.');
      },
    },
  ],
};
