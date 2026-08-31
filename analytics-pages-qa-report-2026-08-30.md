# QA Report — Analytics Pages (A–Z)

- **Date:** 2026-08-30
- **Tester:** Claude (browser QA via Vercel `agent-browser`, Chrome/CDP)
- **Site:** https://mirror-help.arrayhash.com (admin, role `administrator`)
- **Active QA task ID / scheduled day / plan path:** N/A — no active QA plan board; ad-hoc full sweep requested by the user
- **Plugins under test:** `arraysubs` (free) + `arraysubspro` (pro), WooCommerce 10.9.4, WC Admin 11.0.1
- **Store timezone:** `gmt_offset = 6` (no `timezone_string`); browser session timezone `UTC+2`

## Scope covered

All 15 entries under **WooCommerce ▸ Analytics**, plus the plugin's own report directory:

| Page | Path | Loads | Plugin extension under test |
|---|---|---|---|
| Overview | `/analytics/overview` | ✅ | 10 subscription KPI cards, 6-tab MRR/churn chart widget, 5 subscription leaderboards |
| Products | `/analytics/products` | ✅ | "Product Type → Subscription Products Only" filter |
| Revenue | `/analytics/revenue` | ✅ | 3 summary metrics + 3 table columns (Subs Renew / Subs Upgrade / Credit Purchase amount) |
| Revenue Forecast | `/analytics/arraysubs-ai-forecast` | ✅ | Custom AI page (snapshot cards, history/movement charts, billing mix) |
| Churn Analysis | `/analytics/arraysubs-ai-churn` | ✅ | Custom AI page (risk scoring, segments, sortable table, CSV export) |
| Retention | `/analytics/arraysubs-retention` | ✅ | Custom page (summary cards, reason/offer pies, trend chart, activity log) |
| Orders | `/analytics/orders` | ✅ | Type column, quick Type filter, advanced Type include/exclude filter |
| Variations | `/analytics/variations` | ✅ | "Subscription Variations Only" filter |
| Categories / Coupons / Taxes / Downloads / Stock / Settings | core paths | ✅ | none |
| Customers | `/analytics/customers` | ✅ | "Member details" row action |
| ArraySubs ▸ Reports | `admin.php?page=arraysubs-mainadmin#/reports` | ✅ | 49-card report directory |

Also exercised: SPA route changes between custom and core analytics pages, filters, date ranges, chart intervals/tabs, sorting, search, pagination, empty states, CSV export, drill-down links, REST auth, and a 900px viewport pass.

## Verified working (no defect)

- All 15 analytics routes render without JS errors; `agent-browser errors` and `console` clean apart from a core `wp.components.tooltip` deprecation warning.
- SPA navigation into and out of `arraysubs-retention` mounts/unmounts cleanly (Overview cards drop to 0 on leave, return to 10 on re-entry).
- Retention filters: date range, product multi-select with server-side search (`/retention-analytics/products?search=…`), event-type filter, pagination (`Page 1 of 13 (183 total)` → page 2), empty states ("No cancellation data for this period.").
- Churn Analysis: search, empty search state, sorting by Risk/Score/Monthly value/Tenure, segment filter (all 34 / active 30 / at_risk 3 / trial 1), CSV export produces a valid 25-row `arraysubs-churn-risk.csv`.
- Orders: quick Type filter via URL works (`arraysubs_type=Subs Renew` → 170 of 213); advanced **exclude** filter is correct (`arraysubs_type_is_not[0]=Subs Renew` → 43 = 213 − 170).
- Products "Subscription Products Only": 236 → 224 items sold, 26 → 23 rows.
- Variations showing "No data to display" is **correct** — both August variation line items belong to `wc-cancelled` / `wc-pending` orders.
- Customers report "Member details" links are correct (`…page=arraysubs-mainadmin#/manage-members/1`) and resolve.
- Reports directory: 13 categories, 49 cards, 3 free + 46 pro — all counts internally consistent, all cards linked.
- REST auth: `overview/performance`, `retention-analytics/summary`, `retention-analytics/logs`, `ai-insights/forecast` all return **401** when unauthenticated.

---

# Defects

## HIGH

### A-01 — Overview KPI cards ignore the WooCommerce date-range preset ("Last month" shows this month)
- **Route:** `admin.php?page=wc-admin&path=%2Fanalytics%2Foverview&period=last_month&compare=previous_period`
- **Steps:** Open the Overview page and set the date range to **Last month**.
- **Expected:** Subscription cards report July 1–31, 2026, matching the header.
- **Actual:** Header reads `Last month (Jul 1 - 31, 2026)` while every card is byte-identical to Month-to-date — `Active Subscriptions 28`, `MRR $7,200.38`, `New Subscriptions 73`, `Churned 51`, `Churn Rate 60.7%`. The REST call is still `…/analytics/overview/performance?after=2026-07-31T22:00:00.000Z&before=<now>`.
- **Cause (verified):** `getDateRange()` has no `case "last_month"`, so it falls through to `default:` (month-to-date). `last_week` / `last_quarter` / `last_year` are handled but with their own Sunday-based / non-WC week-start assumptions.
- **File:** `arraysubspro/src/resources/pages/AnalyticsOverview/performanceCards.js` — `getDateRange()`
- **Subscription/order IDs:** N/A · **User IDs:** N/A
- **Evidence:** `qa/artifacts/analytics-2026-08-30/05-last-month.png`

### A-02 — Point-in-time subscription metrics use *today's* status, so churn rate exceeds 100 %
- **Route:** `GET /wp-json/arraysubs/v1/analytics/overview/performance?after=2026-07-31T22:00:00.000Z&before=2026-08-30T19:24:33.523Z`
- **Actual response (verbatim):** `"churn_rate":{"value":60.71,"prev_value":166.67}` — a churn rate of 166.67 % is not reachable under any standard definition.
- **Cause (verified):** `getActiveCount($at_date)` counts posts whose **current** `post_status = 'arraysubs-active'` and whose `_start_date <= $at_date`. Any subscription cancelled between `$at_date` and now is retro-actively removed from the historical "active at period start" figure, which is the churn-rate denominator. `computeMrr()` has the same flaw (`post_status IN ('arraysubs-active','arraysubs-trial')`), which is why the Active Subscriptions and MRR charts are monotonically non-decreasing (Day interval: `11, 13, 14, 14, 17, 17, 17, 18 …`).
- **File:** `arraysubspro/src/Features/Analytics/REST/OverviewController.php:280` (`getActiveCount`), `:368` (`computeMrr`), `:443` (`computeChurnRate`)
- **Subscription/order IDs:** N/A (aggregate) · **User IDs:** N/A

### A-03 — Retention Analytics reports a 789.5 % churn rate
- **Route:** `admin.php?page=wc-admin&path=/analytics/arraysubs-retention` (default range 2026-07-31 → 2026-08-30)
- **Expected:** Churn rate ≤ 100 %.
- **Actual:** `Total Cancellations 150`, `Churn Rate 789.5%` (150 / 19).
- **Cause (verified):** the numerator counts **every** `cancelled` row in `wp_arraysubs_retention_logs` inside the window — including subscriptions created *inside* the window (absent from the denominator) and repeat cancel events for the same subscription — while the denominator (`$opening_base`) only counts subscriptions alive at the period start.
  - Repeat events proven: `SELECT subscription_id, COUNT(*) FROM wp_arraysubs_retention_logs WHERE event_type='cancelled' GROUP BY subscription_id HAVING COUNT(*)>1` → `697 → 6`, `1467 → 2`, `1758 → 2`, `1587 → 2`.
- **File:** `arraysubspro/src/Features/RetentionAnalytics/REST/AnalyticsController.php:106-166`
- **Subscription IDs:** 697, 1467, 1587, 1758 · **Order IDs:** N/A · **User IDs:** N/A
- **Counterexample / scope:** narrowing the range to 2026-08-01 → 2026-08-05 yields a sane `31.6%`; the blow-up only appears once cancellations of in-period subscriptions dominate.
- **Evidence:** `qa/artifacts/analytics-2026-08-30/06-retention.png`

### A-04 — "Top Subscribers — Lifetime Value" leaderboard links are dead
- **Route:** Overview ▸ Leaderboards ▸ Top Subscribers — Lifetime Value → click any customer name.
- **Generated href:** `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs#/member-insight/1`
- **Expected:** the member profile screen.
- **Actual:** page body is exactly `Sorry, you are not allowed to access this page.` — the menu slug is `arraysubs-mainadmin`, not `arraysubs`, and the SPA route is `#/manage-members/{id}`, not `#/member-insight/{id}`.
- **Correct pattern already in the codebase:** `arraysubspro/src/Features/MemberInsight/Services/Hooks.php:270` → `admin_url('admin.php?page=arraysubs-mainadmin#/manage-members/' . absint($user_id))`; the Customers report emits the same working URL. Verified that `…?page=arraysubs-mainadmin#/manage-members/1` loads "Manage Members".
- **File:** `arraysubspro/src/Features/Analytics/REST/OverviewController.php:1268`
- **User IDs affected:** every row — observed 1 (Emran Emran), 5 (John Doe), 347, 350, 474
- **Evidence:** `qa/artifacts/analytics-2026-08-30/02-member-insight-link.png`

### A-05 — Orders: `match=any` breaks the Type filter and returns more rows than exist
- **Route A:** `…path=%2Fanalytics%2Forders&filter=advanced&match=any&arraysubs_type_is[0]=Subs Renew` → **213 orders** (should be 170; 213 is the *unfiltered* total).
- **Route B:** same with `&arraysubs_type_is[1]=Subs Purchase` → **214 orders**, i.e. **more than the 213 unfiltered total**.
- **Baseline:** no filter → 213 orders. `match=all` + single type → 170. All measured back-to-back in the same session, Month-to-date.
- **Cause (verified):** for `OR`, `appendCustomWhereClauses()` does not append its own clause; it regex-rewrites WooCommerce's *existing* grouped `AND ( … )` clause into `AND ( … OR <type EXISTS> )`. That grouped clause carries WooCommerce's status restriction, so any order matching the type is admitted regardless of status — which is how the count climbs above the report total.
- **File:** `arraysubspro/src/Features/Analytics/Services/WooAnalyticsHooks.php:807-843` (`appendCustomWhereClauses`, `isMergeableGroupedWhereClause`)

### A-06 — Orders: `match=all` is not honoured — multiple "Type is" rows are OR-ed
- **Route:** `…path=%2Fanalytics%2Forders&filter=advanced&match=all&arraysubs_type_is[0]=Subs Renew&arraysubs_type_is[1]=Subs Purchase`
- **Expected:** 0 — an order has exactly one `_arraysubs_computed_type`, so no order can be both.
- **Actual:** **210** orders (= 170 Subs Renew + 40 Subs Purchase). The UI above the results reads "Orders match **All** filters".
- **Cause (verified):** `buildTypeExistsClause()` always collapses the requested types into a single `EXISTS ( … meta_value IN ('Subs Renew','Subs Purchase') )`, which is OR semantics, and the match operator never reaches it.
- **File:** `arraysubspro/src/Features/Analytics/Services/WooAnalyticsHooks.php:863-892`
- **Knock-on:** the Overview "Active Subscriptions" card links to `…match=all&arraysubs_type_is[0]=Subs Purchase&arraysubs_type_is[1]=Subs Trial`, so that drill-down silently relies on the broken semantics.
- **Evidence:** `qa/artifacts/analytics-2026-08-30/18-orders-advanced.png`

---

## MEDIUM

### A-07 — Orders with no computed type vanish from every Type bucket (not even "Other")
- **Steps:** On Analytics ▸ Orders (Month to date) record the order count for each Type option.
- **Actual:** `Subs Renew 170` + `Subs Purchase 40` + `Subs Upgrade 1` + `Credit Purchase 0` + `Subs Trial 0` + `Other 1` = **212**, but "All Types" = **213**.
- **Cause (verified):** the filter is an `EXISTS (… meta_key='_arraysubs_computed_type' AND meta_value IN (…))` test, so an order with no meta row matches nothing; `Other` is a literal value, not a catch-all.
- **Affected order ID:** **#27808** — `wc-completed`, `2026-08-15 15:07:04 GMT`, total `60.00`. Confirmed the only such order store-wide:
  `SELECT o.id … FROM wp_wc_orders o LEFT JOIN wp_wc_orders_meta om ON om.order_id=o.id AND om.meta_key='_arraysubs_computed_type' WHERE o.type='shop_order' AND om.meta_value IS NULL` → 1 row.
- **Scope note:** the "Compute Order Types" backfill notice exists **only** on WooCommerce ▸ Orders, not anywhere in Analytics, so an analyst has no way to know the report is short.
- **File:** `arraysubspro/src/Features/Analytics/Services/WooAnalyticsHooks.php` (`buildTypeExistsClause`), `…/Services/OrderListHooks.php:1130` (backfill entry point)

### A-08 — Overview date range is built from the *browser's* timezone, not the store's
- **Observed side by side on one page load (Month to date):**
  - WooCommerce: `…/wc-analytics/reports/revenue/stats?after=2026-08-01T00:00:00&before=2026-08-31T23:59:59` (store time)
  - ArraySubs:   `…/arraysubs/v1/analytics/overview/performance?after=2026-07-31T22:00:00.000Z&before=2026-08-30T19:24:33.523Z` (UTC derived from the browser clock)
- Store `gmt_offset = 6`, browser tz `UTC+2` → the ArraySubs window starts at store-local **Aug 1 04:00**, four hours after WooCommerce's. For an admin far enough west the ArraySubs cards will report the **previous month/day** while the rest of the page reports the current one.
- **Cause:** `getDateRange()` builds `new Date(now.getFullYear(), now.getMonth(), 1)` from the client clock and never consults the store timezone.
- **File:** `arraysubspro/src/resources/pages/AnalyticsOverview/performanceCards.js` — `getDateRange()`

### A-09 — Custom date ranges drop the final day
- **Route:** `…path=%2Fanalytics%2Foverview&period=custom&after=2026-08-01&before=2026-08-15`
- **Actual:** ArraySubs requests `after=2026-08-01T00:00:00.000Z&before=2026-08-15T00:00:00.000Z`; WooCommerce requests `before=2026-08-15T23:59:59`. All of 15 August is excluded from the subscription cards but included everywhere else on the page.
- **Cause:** `before = new Date(customBefore).toISOString()` — a bare `YYYY-MM-DD` parses to UTC midnight, with no end-of-day normalisation and no timezone adjustment.
- **File:** `performanceCards.js` — `getDateRange()`

### A-10 — Cards claim "vs previous period" while the picker says "vs. Previous year"
- **Route:** Overview with the default `Month to date (Aug 1 - 31, 2026) vs. Previous year (Aug 1 - 31, 2025)`.
- **Actual:** every card footer reads `… vs previous period` (e.g. `+154.5% vs previous period`), and the backend always derives its own previous window (`$prev_after = $after − period_length`). The `compare` query arg is read into a variable and never used.
- **File:** `performanceCards.js:126` (`const compare = …`, unused), `:339` (hardcoded `"vs previous period"`); `OverviewController.php:142-144`

### A-11 — Overview transient cache never hits and grows one row per request
- **Cause:** cache keys are `md5($after . $before)` / `md5($after.$before.$interval.$chart)`, but the client sends `before = new Date().toISOString()` with millisecond precision, so every request is a fresh key with a 1-hour TTL.
- **Proof:** `SELECT COUNT(*) FROM wp_options WHERE option_name LIKE '_transient_arraysubs_overview_%'` → **2** before, **9** after clicking the six chart tabs once each. The six chart requests differed only in `before`: `…19:27:24.608Z`, `…19:27:41.352Z`, `…19:27:43.421Z`, `…19:27:45.498Z`, `…19:27:47.548Z`, `…19:27:49.717Z`.
- **Files:** `OverviewController.php:135` and `:200`; client side `performanceCards.js` / `chartConfigs.js`

### A-12 — Leaderboard percentage columns lose their `%` sign
- **Actual (UI):** *Top Cancellation Reasons* → `% of Total` shows `51.3`, `2`, `1.3`; *Highest Churn Products* → `Churn Rate` shows `100`, `66.7`, `25`.
- **Cause (verified):** the controller sends both `'display' => "51.3%"` **and** `'format' => 'number'`; WooCommerce's leaderboard renderer re-formats from `value`, discarding `display`.
- **Files:** `OverviewController.php` — `getTopCancellationReasons()` (`:1352-1364`), `getTopProductsByChurn()` (`:1445-1457`)
- **Evidence:** `qa/artifacts/analytics-2026-08-30/01-overview.png`

### A-13 — Cancellation reasons are shown as raw machine slugs
- **Where:** Overview ▸ Top Cancellation Reasons, and the Retention page's *Cancellation Reasons* pie legend + Activity Log `REASON` column.
- **Actual:** `technical_issues`, `qa_cleanup`, `not_provided`, `overdue_payment`, `found_alternative`, `not_using`, `other` are printed verbatim, interleaved with human-authored strings such as `Full refund processed` and `QA fixture cleanup after payment migration regression`.
- **Cause:** `getTopCancellationReasons()` returns `esc_html($row->reason)` with no label map; the pie builds its legend from `reason_key`.
- **Files:** `OverviewController.php:1349`; `arraysubspro/src/resources/pages/RetentionAnalytics/components/ChurnReasonsChart.jsx`
- **Evidence:** `qa/artifacts/analytics-2026-08-30/07-retention-pies.png`

### A-14 — Activity Log `OFFER` column is always "—" for Offer Shown / Offer Declined
- **Route:** Retention ▸ Activity Log ▸ filter `Offer Shown`.
- **Actual:** e.g. `2026-08-18 07:16:46 | Offer Shown | John Doe | SLT Flex Qty Week | too_expensive | — | $8.49 | —`. Every `offer_shown` and `offer_declined` row shows `—`.
- **Cause (verified):** `RetentionLogger::onOfferShown()` writes `offer_data` but never `offer_type`; the `offer_declined` insert sets neither. `ActivityLogs.jsx:185` renders `{log.offer_type || "—"}`.
  DB confirms the data is there under the wrong key:
  `SELECT event_type, offer_data, COUNT(*) … GROUP BY 1,2` → `offer_shown / ["discount"] / 17`, `offer_shown / ["secure_discount"] / 8`, `offer_shown / [] / 3`, `offer_declined / (empty) / 8`, and `offer_type` is non-empty only for the single `offer_accepted` row (`coupon`).
- **Files:** `arraysubspro/src/Features/RetentionAnalytics/Services/RetentionLogger.php:91`, `:158`; `…/resources/pages/RetentionAnalytics/components/ActivityLogs.jsx:185`

### A-15 — The three ArraySubs analytics pages disagree with each other on the same numbers
Captured within one session, same store state:

| Metric | Overview | Revenue Forecast | Churn Analysis | Retention |
|---|---|---|---|---|
| Subscription count | `Active Subscriptions 28` | `Billing Subscriptions 27` (+ "excludes 4 lifetime") | `Live Subscriptions 34` | — |
| MRR | `$7,200.38` | `$6,874.36` | — | — |
| Churn rate | `60.7%` | — | `60.0%` (30 days) | `789.5%` |
| Renewal revenue | `Renewal Revenue $3,349.83` | — | — | — |
| Renewal revenue (Revenue report) | `Total Subs Renew Amount $3,454.42` | | | |

Each page implements its own population query and its own window, and none of the four subscription counts can be reconciled from the UI. For reference the DB has 30 `arraysubs-active`, 1 `arraysubs-trial`, 2 `arraysubs-on-hold`, 15 `arraysubs-pending`, 16 `arraysubs-expired`, 363 `arraysubs-cancelled`.

### A-16 — Custom analytics pages ignore the global WooCommerce date range
- Retention has its own two date inputs (default *today − 30 days* → *today*, i.e. `2026-07-31 → 2026-08-30`), and Churn Analysis / Revenue Forecast have **no** date control at all.
- Drilling in from an Overview card (e.g. "Churned Subscriptions" while the page is set to *Last month*) lands on Retention showing a different, unrelated window with no indication that the period changed.
- **Files:** `RetentionAnalyticsPage.jsx` / `RetentionFilters.jsx`; `performanceCards.js` `CARD_DEFS[].href`

### A-17 — "Highest Churn Products" denominator is a live count of `arraysubs-active` only
- **Actual:** `SLT Daily Core — 31 cancellations — 100 % churn`, while the same product is the store's top seller for the period (`87 items sold`, `$856.00`) on the core *Top products* leaderboard directly above it. `SLT Paddle Daily` and `SLT Plan Basic` likewise show 100 %.
- **Cause (verified):** `$active_for_product` counts posts with `post_status = 'arraysubs-active'` **at query time**, excluding `arraysubs-trial`, `arraysubs-on-hold` and `arraysubs-paused`, and is not scoped to the reporting period. `churn = cancels / (cancels + active_now)` therefore reaches 100 % whenever the survivors are not in the `active` status.
- **File:** `OverviewController.php:1423-1440`

### A-18 — "New Subscriptions" counts every subscription post, whatever its status
- **Actual:** Overview reports `New Subscriptions 73` for 2026-07-31 22:00Z → 2026-08-30 19:24Z. Breakdown from the DB for that exact window: `arraysubs-cancelled 46`, `arraysubs-active 19`, `arraysubs-pending 3`, `arraysubs-expired 2`, `arraysubs-on-hold 2`, `arraysubs-trial 1` = 73. `arraysubs-pending` records are counted as new subscriptions, and the query would equally count `draft` / `trash` posts.
- **Cause (verified):** `getNewSubscriptionCount()` filters on `post_type` and `post_date_gmt` only — there is no `post_status` predicate.
- **File:** `OverviewController.php:308-326`

### A-19 — Retention page filters compare local calendar dates against UTC timestamps
- **Cause (verified):** `getSummary()` builds `$date_start = $start_date . ' 00:00:00'` straight from the picker (a local calendar date) and compares it to `created_at`, which `RetentionLogger` writes as `current_time('mysql', true)` — UTC. With `gmt_offset = 6` every retention window is shifted 6 hours: the first six hours of the chosen start day are dropped and the first six hours of the day after the end date are included.
- **Files:** `AnalyticsController.php:119-120`; `RetentionLogger.php:263`

---

## LOW

### A-20 — Currency values render with a variable number of decimals
`Revenue at Risk` displays **`$103.3`** (raw value `103.3`) instead of `$103.30`; an integer amount would render as `$4,036`. `formatValue()` sets `minimumFractionDigits: 0, maximumFractionDigits: 2`, unlike WooCommerce's own price formatting on the same page.
**File:** `performanceCards.js` — `formatValue()`

### A-21 — Delta shows a fabricated `+100%` when the previous value is zero
`computeDelta()` returns `100` whenever `previous === 0 && current > 0`. Growth from 0 is undefined and should render as "—" / "n/a".
**File:** `performanceCards.js` — `computeDelta()`

### A-22 — Chart buckets are labelled with a date outside the selected range
With `Month to date (Aug 1 – 31)`: the **Day** interval renders 30 bars starting at `2026-07-31` (tooltip `2026-07-31: 11`); the **Month**, **Quarter** and **Year** intervals each collapse to a single bar also labelled `2026-07-31`. The bucket label is the raw UTC period start rather than a period name.
**File:** `OverviewController.php` — `getIntervalBoundaries()`; `chartConfigs.js` label formatting

### A-23 — Chart interval default disagrees with the control beside it
The ArraySubs chart widget defaults to **Week** while WooCommerce's own chart selector immediately above defaults to **By day**.

### A-24 — Revenue report's ArraySubs columns are not sortable
`Total Subs Renew Amount`, `Total Subs Upgrade Amount` and `Total Credit Purchase Amount` have no `Sort by …` control, unlike all nine core columns in the same table. There is also no `Total Subs Purchase Amount` counterpart to the Renew/Upgrade metrics.

### A-25 — Type filter popover opens upwards and covers the other filters
On Analytics ▸ Orders, clicking the **Type** dropdown renders the option list *above* its control, overlapping the **Date range** and **Show** selects.
**Evidence:** `qa/artifacts/analytics-2026-08-30/17-orders-typefilter.png`

### A-26 — Retention pie labels collide with the legend
On *Cancellation Reasons*, the `32%` and `8%` slice labels overlap the legend text `Full refund processed`, and only 3 of 10 slices are labelled at all.
**Evidence:** `qa/artifacts/analytics-2026-08-30/07-retention-pies.png`

### A-27 — Plural-only strings shown for a count of 1
- Churn Analysis, `Segment = Trial`: *"Showing the **1 highest-risk subscriptions** · scored 1 in this segment"*.
- WooCommerce ▸ Orders backfill notice: *"**1 orders** need type classification for analytics."*

### A-28 — Chart tab buttons expose no selected state to assistive tech
`.arraysubs-overview-charts__tab` buttons carry only an `--active` class; the interval buttons next to them correctly use `aria-pressed`. No `role="tab"` / `aria-selected` either.

### A-29 — "Offer Outcomes" pie omits offers that were shown but never answered
The pie is built from `offer_accepted` (grouped by `offer_type`) plus `offer_declined`, so it reads **"Declined 100 %"** while the cards on the same screen say `Offers Shown 19 / Offers Accepted 0`. The other 11 shown offers appear nowhere.
**File:** `AnalyticsController.php:281-310`

### A-30 — "Active Subscriptions" card drills down to an unrelated number
The card reads `28` and links to Orders filtered to *Subs Purchase + Subs Trial*, which returns **40 orders**. A subscription count and an order count are different units; the landing figure never matches the figure clicked.
**File:** `performanceCards.js` — `CARD_DEFS[0].href` / `ordersByType()`

---

## Not defects (checked and cleared)

- Full-page screenshots of the Retention and Revenue Forecast pages render Recharts panels as blank white. Re-captured at viewport size, all pies, lines and areas draw correctly — a screenshot artefact, not a rendering bug.
- Variations report "No data to display": correct. Both August variation line items (`order 13194 / variation 8656`, `order 31850 / variation 8654`) belong to `wc-cancelled` and `wc-pending` orders.
- Two visually identical Activity Log rows at `2026-08-26 08:04:46` are **not** duplicates — log ids 545/546 for subscriptions **33152** and **33169** (same customer, same product, same amount). Worth noting that the Activity Log exposes no subscription ID, so such rows are indistinguishable in the UI.
- A stray `Sorry, you are not allowed to access this page.` string observed once inside the WooCommerce Store Activity card was not reproducible on a clean load and appears to be core inbox behaviour.
- Retention Activity Log table at a 900 px viewport clips columns visually but its wrapper is `overflow-x: auto` (`scrollWidth 1017 > clientWidth 798`), so the content is reachable.

## Reproduction environment

```
agent-browser 0.27.3 (Chrome/CDP, HeadlessChrome 149), session "admin", viewport 1280×800
WordPress admin: https://mirror-help.arrayhash.com/wp-admin  (user "admin", role administrator)
Store: WooCommerce 10.9.4, WC Admin 11.0.1, HPOS enabled, gmt_offset = 6
Screenshots: qa/artifacts/analytics-2026-08-30/
```
