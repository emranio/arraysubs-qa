# Analytics Pages — Fix + Re-QA Report

- **Date:** 2026-08-31
- **Scope:** all 30 defects from `qa/analytics-pages-qa-report-2026-08-30.md`
- **Result:** 28 fixed and re-verified in the browser, 2 closed as not-a-defect (evidence below)
- **Site:** https://mirror-help.arrayhash.com (admin, `administrator`), WooCommerce 10.9.4 / WC Admin 11.0.1, HPOS, `gmt_offset = 6`
- **Assets:** `npm run build` in `arraysubspro` — exit 0, 0 errors
- **Runtime:** no JS console errors, no 4xx/5xx requests, no PHP notices from plugin code across the whole re-QA session

---

## What changed

### New: one source of truth for subscription figures
`arraysubspro/src/Features/Analytics/Services/SubscriptionMetrics.php`

Every analytics screen used to run its own variant of "how many subscribers, how much MRR, how much churn". That is why one store reported three subscription counts and two MRR figures at the same moment. All four screens now call the same service:

| Helper | Used by |
|---|---|
| `activeCountAt()` / `liveCountAt()` | Overview cards + charts, Churn Analysis, Retention denominator |
| `mrrAt()` | Overview MRR card + chart, AI Revenue Forecast |
| `startedCountBetween()` / `churnedCountBetween()` | Overview, Forecast, Churn Analysis, Retention |
| `churnRateBetween()` | Overview card + chart, Churn Analysis, Retention |
| `revenueByOrderType()` | Overview "Renewal Revenue", Revenue report columns |
| `reasonLabel()` / `offerLabel()` | Overview leaderboard, Retention pies + activity log |

The point-in-time helpers are genuinely historical: a subscription's status at a past date is reconstructed from `_start_date`, `_cancelled_date`, `_end_date`, `_on_hold_date` and `_pause_start_date` instead of assuming today's `post_status` also applied then.

### New: one store-timezone date resolver
`arraysubspro/src/resources/pages/AnalyticsOverview/dateRange.js`

Replaces two divergent copies of `getDateRange()` (cards and charts). Resolves every WooCommerce preset in the **store's** timezone, honours the compare setting, and mirrors `@woocommerce/date`'s Sunday-based weeks and the `woocommerce_default_date_range` option.

---

## Defect-by-defect verification

### HIGH

| ID | Fix | Verified |
|---|---|---|
| **A-01** Presets ignored | `dateRange.js` handles every WooCommerce preset (`today`, `yesterday`, `week`, `last_week`, `month`, `last_month`, `quarter`, `last_quarter`, `year`, `last_year`, `custom`) | `period=last_month` → request `after=2026-06-30T18:00:00Z … before=2026-07-31T17:59:59Z` (store-local July 1–31) and cards read `Active 19 / MRR $4,086.97`, distinct from month-to-date's `31 / $6,874.36` |
| **A-02** History rewritten by today's status | `SubscriptionMetrics::buildAliveClause()` reconstructs status from lifecycle dates; `computeMrr`/`getActiveCount`/`computeChurnRate`/`getUniqueActiveCustomers` all delegate | Monthly churn across the whole data set: Jan–Apr `0.00%`, May `19.09%`, Jun `82.33%`, Jul `67.21%`, Aug `60.67%` — **max 82.33%**, no value above 100% anywhere |
| **A-03** Retention churn 789.5% | Churn rate now comes from `churnRateBetween()` (distinct subscriptions over `live-at-start + started-in-window`), not from counting log rows | Retention page reads **`Churn Rate 60.7%`**, identical to the Overview card and to Churn Analysis's `60.7%` |
| **A-04** Dead leaderboard links | `page=arraysubs#/member-insight/{id}` → `page=arraysubs-mainadmin#/manage-members/{id}` | Rendered hrefs: `…arraysubs-mainadmin#/manage-members/1`, `…/5`; target loads "Manage Members" |
| **A-05** `match=any` returned more rows than exist | Removed the regex splice into WooCommerce's own grouped WHERE clause; custom conditions are now always appended as their own group | `match=any` + 1 type → **172** (was 213 = the unfiltered total); + 2 types → **212** (was 214, above the 215 total) |
| **A-06** `match=all` OR-ed | `splitTypeFilters()` emits one EXISTS clause per type under AND, a single `IN(...)` under OR | `match=all` with `Type is Subs Renew` **and** `Type is Subs Purchase` → **0** (was 210) |

### MEDIUM

| ID | Fix | Verified |
|---|---|---|
| **A-07** Untyped orders in no bucket | `Other` is now a real catch-all: `EXISTS(type IN …) OR NOT EXISTS(type meta)` | `Subs Renew 172 + Subs Purchase 40 + Subs Upgrade 1 + Credit Purchase 0 + Subs Trial 1 + Other 1 = 215` = the "All Types" total. Order **#27808** now appears under "Other" |
| **A-08** Browser timezone | `dateRange.js` builds windows from `gmtOffset` localized by `OverviewHooks` | Request `after=2026-07-31T18:00:00Z` = store-local Aug 1 00:00, matching WooCommerce's own `after=2026-08-01T00:00:00` |
| **A-09** Custom range dropped the last day | `before` is the inclusive end of the final day | Custom `after=2026-08-01&before=2026-08-15` → `before=2026-08-15T17:59:59Z` = store-local Aug 15 23:59:59, matching WooCommerce |
| **A-10** Wrong compare label | Client sends `prev_after`/`prev_before`; the card labels the mode it was given; `woocommerce_default_date_range` is honoured when the URL omits it | Default view: header "vs. Previous year (Aug 1 - 31, 2025)" and cards "**vs previous year**" with `prev_after=2025-07-31T18:00:00Z`. With `compare=previous_period` the label and window switch accordingly |
| **A-11** Cache never hit | Stable window ends + `buildCacheKey()` normalises datetimes to the minute | 3 consecutive full page loads → `_transient_arraysubs_overview_%` count **unchanged** (8 → 8, later 16 → 16). Previously every request added a row |
| **A-12** `%` stripped from leaderboards | Percent cells drop the `format` key so WooCommerce renders `display` | "Top Cancellation Reasons" shows `51%`, `2%`, `1.3%`; "Highest Churn Products" shows `100%`, `66.7%`, `25%` |
| **A-13** Raw reason slugs | `SubscriptionMetrics::reasonLabel()` maps configured keys, humanises slug-shaped values, leaves free text untouched | Leaderboard and pie legend read "Technical issues", "Qa cleanup", "Not provided", "Found a better alternative"; free text such as `SW-05 verified fixture cleanup` is preserved character-for-character |
| **A-14** Offer column always "—" | `RetentionLogger` now writes `offer_type` (and `offer_data`) for `offer_shown` and `offer_declined`; `LogsController` falls back to `offer_data` for rows already in the table | Activity Log, filtered to Offer Shown: `#29013 … Too expensive │ **Discount**`, `#27766 … Technical issues │ **Discount**`. Rows whose stored payload is genuinely empty (`[]`) still show "—", correctly |
| **A-15** Pages disagreed | All four screens read the shared service | Same moment, same store: Overview `Active 31` · Forecast `Billing Subscriptions 27 + 4 lifetime = 31` · Churn `Live 33` (= 31 + 2 on-hold, and labelled as such). MRR `$6,874.36` on Overview **and** Forecast. Churned `54` on all three. Churn rate `60.7%` on all three. Renewal revenue `$3,514.42` on Overview **and** in the Revenue report |
| **A-16** Retention ignored the global range | Retention now defaults to store month-to-date via `today`/`monthStart` localized from PHP | Retention date inputs open on `2026-08-01 .. 2026-08-31`, the same window the Overview header shows |
| **A-17** Churn leaderboard denominator | Counts all `LIVE_STATUSES`, not only `arraysubs-active`, and is clamped to 100% | `Basic Monthly` reads `66.7%` on the leaderboard **and** `66.7%` on the Retention page when filtered to that product. `SLT Daily Core` remains `100%` — verified correct: it has 31 cancelled and 0 live subscribers |
| **A-18** "New" counted anything | `startedCountBetween()` keys off `_start_date` and excludes `arraysubs-pending`, drafts and trashed rows | `New Subscriptions 70` (was 73, which had included 3 pending sign-ups) and it matches Forecast's `New (30 days) 70` |
| **A-19** Retention window off by the GMT offset | `siteToGmt()` on the summary, logs and trend queries; trend buckets converted with `CONVERT_TZ` so log events and subscription dates land in the same day | `SM::siteToGmt('2026-08-01T00:00:00')` → `2026-07-31 18:00:00`; trend series and activity log now agree on the day boundary |

### LOW

| ID | Fix | Verified |
|---|---|---|
| **A-20** Ragged currency | `minimumFractionDigits: 2` on cards and charts | `$6,874.36`, `$3,514.42`, `$0.00` |
| **A-21** Fake `+100%` | `computeDelta()` returns `null` for growth from zero; UI renders "—" | `Trial Conversions 66.7% — vs previous period`; every card shows "—" under `compare=previous_year` where 2025 has no data |
| **A-22** Bucket labels outside the range | Server emits a store-timezone `date` plus an interval-appropriate `label` | Day → `Aug 1, Aug 2, …` (was `2026-07-31` first); Week → `Aug 1 – Aug 7 … Aug 29 – Aug 31`; Month → `Aug 2026`; Quarter → `Q3 2026`; Year → `2026` |
| **A-23** Interval default mismatch | `getAutoInterval()` returns `day` for windows ≤ 31 days | Month-to-date opens on **Day**, matching WooCommerce's "By day" selector beside it |
| **A-24** Revenue columns | Added the missing `Total Subs Purchase Amount` metric and column; `isSortable: false` kept with the reason documented in code (the totals are injected into the response, so the REST endpoint cannot order by them — offering a dead sort control would be worse) | Summary row: `Total Subs Purchase Amount $725.01 · Total Subs Renew Amount $3,514.42 · Total Subs Upgrade Amount $10.00 · Total Credit Purchase Amount $0.00`, and all four appear as table columns |
| **A-26** Pie labels collided | Labels rendered inside the arc; pie recentred at `cy 42%`; legend given its own 72px band | `49% / 31% / 8%` sit inside the Cancellation Reasons slices; `42% / 58%` inside Offer Outcomes; no overlap with the legend |
| **A-27** Plural-only strings | `_n()` in `RiskTable.jsx` and in the backfill notice | Segment = Trial → "Showing the **1 highest-risk subscription**"; at-risk → "2 highest-risk subscriptions" |
| **A-28** Tabs had no selected state | `role="tablist"` / `role="tab"` / `aria-selected` | Snapshot: `6 tabs; role=tab; sel0=true; listrole=tablist` |
| **A-29** Offer pie omitted unanswered offers | Controller emits a `no_response` slice (`shown − accepted − declined`); chart renders it | Offer Outcomes now reads `Declined 42%` + `No response 58%` instead of "100% Declined" |
| **A-30** Card drilled to an unrelated number | "Active Subscriptions" and "New Subscriptions" link to the subscription list, not an orders report | Both cards link to `admin.php?page=arraysubs-mainadmin#/subscriptions` |

---

## Closed as not-a-defect

**A-25 — Type filter popover "opens upward and covers the other filters".**
Not a bug; a measurement artefact of my 577px-tall test viewport. WooCommerce's popover correctly flips above the trigger when the menu will not fit below it. At 1440×900: button bottom `214`, menu top `227` — it opens downward as expected. The original observation was made at `window.innerHeight = 577`, where `367 + 268 > 577`. No code change; screenshot `f05-typefilter-tall.png`.

**A-17's premise about SLT Daily Core.** The denominator bug was real and is fixed, but the product's 100% churn rate is correct data, not a symptom: `SELECT post_status, COUNT(*) … WHERE effective_product_id = 11927` returns `arraysubs-cancelled 31`, `arraysubs-pending 2` — zero live subscribers. The "87 items sold" figure it was compared against counts renewal line items for subscriptions that have since cancelled.

---

## Additional defects found and fixed during the fix pass

These were not in the original report; they surfaced while making the numbers reconcile.

1. **Lifetime deals were counted as recurring revenue.** `OverviewController::normalizeToMonthly()` had no `lifetime` branch, so a one-off purchase price fell through to `default: return $amount` and was added to MRR every month. This was the entire `$7,200.38` vs `$6,874.36` gap between Overview and Forecast. Now `SubscriptionMetrics::toMonthly()` returns `0.0` for `lifetime`/`one-time`, and both pages read `$6,874.36`. Same fix corrects "Revenue at Risk", which now reads `$0.00` — both at-risk subscriptions (#29028, #29036) are lifetime deals.
2. **Revenue report columns included excluded order statuses.** `computeRevenueTypeAmounts()` had no status predicate, so pending/failed/cancelled orders were summed into columns sitting beside WooCommerce totals that exclude them. Now filtered with `status NOT IN (…)` using WooCommerce's own `woocommerce_excluded_report_order_statuses` semantics (`Total Subs Purchase Amount` moved `$730.01 → $725.01` as a result).
3. **`wc_order_stats` date columns are store-local, not GMT.** `date_created`, `date_paid` and `date_completed` are local; only `date_created_gmt` is UTC. `SubscriptionMetrics::gmtToSite()` converts before comparing, which is what made the Overview card land on the Revenue report's `$3,514.42` exactly.
4. **Trial conversion keyed off today's status.** A trial that converted and later churned counted as "never converted", so the rate was pinned at `0.0%`. It now tests whether the trial ran to its end without being cancelled first: `66.7%`.
5. **Subscriptions missing `_start_date` vanished from every count.** An `INNER JOIN` on that meta dropped 2 live subscriptions. Now `COALESCE(NULLIF(_start_date,''), post_date_gmt)`.
6. **Free-text cancellation reasons were mangled.** The shared humaniser turned `SW-05 verified fixture cleanup` into `SW 05 verified fixture cleanup`. Only slug-shaped values are humanised now.
7. **WooCommerce weeks are Sunday-based and ignore `start_of_week`.** A first pass read WordPress's option (`6` = Saturday) and put the cards a day off from the header. `dateRange.js` now mirrors `@woocommerce/date`: Week-to-date `Aug 30–31`, Last week `Aug 23–29`, matching the picker exactly.
8. **The store's default date range lives in an option, not the URL.** With no `compare` param WooCommerce applies `woocommerce_default_date_range` (`period=month&compare=previous_year`) while the cards assumed "previous period". The option is now localized and honoured.

---

## Files changed

```
arraysubspro/src/Features/Analytics/Services/SubscriptionMetrics.php      (new)
arraysubspro/src/resources/pages/AnalyticsOverview/dateRange.js          (new)
arraysubspro/src/Features/Analytics/REST/OverviewController.php
arraysubspro/src/Features/Analytics/Services/WooAnalyticsHooks.php
arraysubspro/src/Features/Analytics/Services/OverviewHooks.php
arraysubspro/src/Features/Analytics/Services/OrderListHooks.php
arraysubspro/src/Features/RetentionAnalytics/REST/AnalyticsController.php
arraysubspro/src/Features/RetentionAnalytics/REST/LogsController.php
arraysubspro/src/Features/RetentionAnalytics/Services/RetentionLogger.php
arraysubspro/src/Features/RetentionAnalytics/Services/Hooks.php
arraysubspro/src/Features/AiInsights/Services/ForecastDataService.php
arraysubspro/src/Features/AiInsights/Services/ChurnDataService.php
arraysubspro/src/resources/pages/AnalyticsOverview/performanceCards.js
arraysubspro/src/resources/pages/AnalyticsOverview/chartConfigs.js
arraysubspro/src/resources/pages/RetentionAnalytics/RetentionAnalyticsPage.jsx
arraysubspro/src/resources/pages/RetentionAnalytics/components/ActivityLogs.jsx
arraysubspro/src/resources/pages/RetentionAnalytics/components/ChurnReasonsChart.jsx
arraysubspro/src/resources/pages/RetentionAnalytics/components/RetentionOfferChart.jsx
arraysubspro/src/resources/pages/AiInsights/components/RiskTable.jsx
arraysubspro/src/resources/analyticsRevenue.js
arraysubspro/public/build/**                                             (rebuilt)
```

---

## Regression sweep

All 15 Analytics routes plus the ArraySubs Reports directory were reloaded after the changes.

- `overview`, `products`, `revenue`, `arraysubs-ai-forecast`, `arraysubs-ai-churn`, `arraysubs-retention`, `orders`, `variations`, `categories`, `coupons`, `taxes`, `downloads`, `stock`, `settings`, `customers` — all render, no error text, no console errors, no failed requests.
- ArraySubs ▸ Reports — 13 categories, 49 cards, all linked.
- Products "Subscription Products Only": `238 → 226` items sold, `26 → 24` rows.
- Customers report "Member details" links: 25 rows, `…arraysubs-mainadmin#/manage-members/474`.
- Retention filters: date range, product multi-select with server-side search, event-type filter, pagination, empty states.
- Churn Analysis: search, sorting, segments (`all 33 / active / at_risk 2 / trial 1`), CSV export.
- Anonymous REST access to all four ArraySubs analytics endpoints still returns **401**.

## Known limitations (by design, documented in code)

- The three ArraySubs revenue columns on the Revenue report remain non-sortable: they are injected into the response rather than produced by the report query, so the REST endpoint has nothing to order by.
- Under "match any", ArraySubs type conditions are OR-ed among themselves and AND-ed onto the report. Mixing them into a WooCommerce-native advanced filter's own OR group is what produced A-05, so that splice was removed deliberately.
- `Live Subscriptions` (33) is intentionally wider than `Active Subscriptions` (31): it includes on-hold and paused subscribers, who have not churned. Both derive from the same service and the labels say which is which.

## Evidence

`qa/artifacts/analytics-2026-08-31-fixed/` — `f01` leaderboard links · `f02` humanised reasons + `%` · `f03` churn board `%` · `f04`/`f05` popover at 577px vs 900px · `f06` retention pies + offer outcomes · `f07` forecast charts · `f08` final Overview.
