# Analytics feature video (slug `analytics`)

Scene: `scenes/analytics.mjs` · Output: `$FV_OUT/analytics/analytics.mp4` + `poster.webp`
Website: `web-content/src/pages/features/analytics.astro` (tier free+pro) · Placeholder: "Analytics: Home dashboard, KPIs and AI reports"

## Storyboard (9 chapters, Free + Pro)

1. **Home at a glance** (Free, Pro cards at the end): health hero sentence, Current MRR + ARR run rate + trial note, 12-month momentum, three KPI tiles, range switch 7 days and back to 30 days, then the Pro insight row (Revenue forecast, Revenue at risk, Top products by MRR).
2. **Renewals, movement and status** (Free): Upcoming renewals, Recent subscriptions, Subscriber movement (hover tooltip), Status mix (hover Active), Renewal outlook 7/30/90, Billing operations, then Next 7 days opens the filtered Subscriptions list.
3. **Action Center** (Free): six signals busiest first, zoom, Clear rows, Review opens the filtered list.
4. **Storefront**: customer 497 (Abigail Cook) subscribes to Weekly Coffee Delivery (14$/week), block checkout shows renewal + next charge, order placed by bank transfer.
5. **The sign-up lands in reports**: (off camera: order marked paid, synced to WC Analytics) Home Refresh, Recent subscriptions row, WooCommerce Analytics Orders filtered by Type = Subs Purchase, Revenue report split cards (Pro).
6. **Subscription KPIs in WooCommerce Analytics** (Pro): 10 cards (month to date vs previous period), churn drop is green, date range picker, chart metric/interval, leaderboards.
7. **Churn risk** (Pro): Home Churn risk card, link to Churn Analysis, tiles, Analyse with AI button (optional), Why they are at risk, risk table, search, Export CSV. No AI layer on camera (see AI budget below).
8. **AI Revenue Forecast** (Pro): measured tiles, Last generated stamp, outlook chart tooltip, What the AI expects (badges, lists), provider/model/tokens line.
9. **Retention and refunds** (Pro): cohort table, customer/MRR retention, top cancellation reasons, refund KPI cards, time to refund.

## AI requests (cap 2, shared with a duplicate instance that ran 21:11-21:21 UTC)

- #1 2026-11-27 21:19:14 UTC: Revenue Forecast, MRR, next 12 months, generated through the UI (Generate forecast). Cached in transient `arraysubspro_ai_forecast_v2_c549f165fbd83e3b306db77efc7fa64a` (12 h TTL, until ~09:19 UTC). Provider Anthropic.
- #2 ~21:20 UTC: Churn analysis (all live, 25) started through the UI, but the recording process was killed by the coordinator at ~21:21 UTC (duplicate-instance cleanup). No result was cached. It may or may not have reached the provider, so it is counted as used. No further AI requests were made. The video shows the churn scores without the AI layer (the scores need no AI).
- Undo: Analytics > Revenue Forecast > Clear cache, or `FV_PRO=1 wp eval '(new \ArraySubsPro\Features\AiInsights\Services\ForecastReport())->flushCache();' --allow-root`.

## Store changes (log)

- `clean-map.d/analytics.json` (display-only, recording browser only): `Al Emran` -> `Grace Holloway` (admin account in the Top Subscribers leaderboard), the five `Demo: ...` refund reasons -> plain reasons, and every `demo.260927.NNN@example.test` email -> `first.last@example.com` from its display name. Delete the file to undo.
- `clean-map.d/analytics.json` also maps the product short description `Weekly Coffee Delivery — demo catalog.` (and its `\u2014` / `&#8212;` forms) to `Fresh-roasted beans at your door every week.` (display-only).
- Scene CSS (recording browser only, via `addInitScript`): hides the dismissible Home setup checklist (`.arraysubs-onboarding`) and WooCommerce's one-time Orders tour popup. Nothing stored. (Hiding the tour leaves `#wpwrap` catching pointer events on the Orders report, so the scene opens the Type filter with dispatched click events.)
- WooCommerce Analytics report cache invalidated once per chapter-5 take (`Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate()`), so the new order shows in Orders/Revenue. Reports rebuild on next load; nothing else changes.
- Home dashboard cache flushed before chapter 1 (`Overview\Builder::flush()`, transients `arraysubs_overview_v2_*` only; it rebuilds on the next load).
- Customer 497 (Abigail Cook): persistent cart + session row cleared before chapter 4 (a scouting probe had put Weekly Coffee Delivery in the cart).
- Chapter 4 places a real order: customer 497, Weekly Coffee Delivery (product 6713), bank transfer. Each take first permanently deletes the previous takes' orders (customer 497, product 6713, created after 2026-11-27 21:30 UTC) and their subscriptions.
- Chapter 5 `before` marks that order paid (`payment_complete()`, activates the subscription) and syncs it with `OrdersScheduler::import()`.
- Final records left on the store: see "Final state" below.
- No shared settings changed. No plugins activated/deactivated. No plugin code edited.

## Product bugs / observations

- **Home "Churn risk" card can never show the cached AI layer.** `arraysubspro/src/Features/OverviewWidgets/Support/Widgets.php` peeks the churn AI cache with `peekAiLayer('all', 200)` (`CHURN_SCOPE_LIMIT = 200`), but `InsightsController::normalizeLimit()` only allows 25/50/100, so an analysis is only ever cached under 25/50/100. Repro: Analytics > Churn Analysis > Analyse with AI (All live, 25 subscribers) > ArraySubs > Home > Refresh: the Churn risk footnote still reads "Scored from subscription signals. The AI layer has not been generated yet." Verified in code only (no churn AI run was spent to confirm in the browser).
- The AI reports' "Last generated" stamp is formatted in the browser's time zone, not the site's (the recording browser runs in UTC, so it reads Nov 27, 09:19 PM while the site's date is Nov 28). Cosmetic.

## Final state (after the final take, 2026-11-28 ~05:48 site time / 23:48 UTC Nov 27)

- Store records this scene left: order **#32687** (customer 497 Abigail Cook, Weekly Coffee Delivery, bank transfer, Processing/paid) and its subscription **#32688** (Active, weekly 14$, next payment 2026-12-04 23:47 UTC). Earlier takes' orders/subscriptions (#32301/#32303, #32381, #32470/#32471, #32583 and their subs) were permanently deleted by the scene's reset.
  - Undo: `FV_PRO=1 wp eval '\ArraySubs\Supports\ActionScheduler::unscheduleAllForSubscription(32688); wp_delete_post(32688, true); wc_get_order(32687)->delete(true);' --allow-root` (from app/public, after sourcing app/.envrc).
- Customer 497's cart is empty (cleared after the checkout checks).
- AI cache: forecast mrr/12 (generated 21:19 UTC, expires ~09:19 UTC). No churn AI cache.
- `clean-map.d/analytics.json` stays until recording is over (display-only; delete to undo).
- Shared settings: none changed. Plugins: none toggled. Plugin code: untouched.

## Harness notes

- No harness changes by this scene. Scene-local helpers: `openHome` / `openWc` (off-camera load + readiness wait, then crossfade), `prewarm` (in `before`), `park` (real mouse to a quiet corner), `pressOn` (dispatched click for WooCommerce filter dropdowns).
- Observed during the session: another agent changed `lib/director.mjs` / `run.mjs` (~04:41) to support `FV_W/FV_H/FV_DPR` and mixed segment sizes; chapters 2-3 recorded at ~03:59 came out 1440x810 and were re-recorded at 1920x1080.


## Revision 2 (2026-11-28 06:10-07:40 site time): reviewer fix pass, every chapter re-recorded at native 1920x1080

v1 scene backed up at `$FV_OUT/../analytics.v1.mjs` (scratchpad), v1 segments/video at `$FV_OUT/analytics/old-v1/`.
All nine segments of the final video were recorded in this pass with the harness's default device-pixel capture (ffprobe: 1920,1080 each). No 1440x810 segment is kept.

### Storyboard (9 chapters, 4 min 47 s)

1. **Home at a glance** (Free, Pro cards at the end). Poster = clean full-screen Home with a dimmed ring on the hero. Hero sentence label (top), 1.35x frame of sentence + MRR for the MRR label only (~3.5 s), 12-month spark unzoomed, KPI tiles, 7 days (chip label on top) and back to 30 days, then the Pro row scrolled to the middle: Revenue forecast, Revenue at risk, Top products.
2. **Renewals, movement and status** (Free). Upcoming renewals, Subscriber movement tooltip, Status mix hover, Renewal outlook, Billing operations ("No stalled or failed jobs"), Next 7 days -> filtered list -> pagination "N items" labelled "Same N as on Home" (only when the list count equals the card count, read at record time; caption moved top-right there).
3. **Action Center** (Free). Six signals (dim ring), 1.35x zoom on the list with "Failed payment or manual hold", Expiring fixed-term "Pro: fixed-term plans" (fixed end dates come from Pro), Review -> On-hold list -> "Same 20 as on Home".
4. **A customer subscribes** (Storefront). Customer 497 Abigail Cook, Weekly Coffee Delivery 14$/week, block checkout renewal + next charge (1.6x zoom ~3.5 s), Place Order scrolled to the middle first, order received ("Ordered with bank transfer: the order is in.").
5. **The sign-up lands on Home** (Free). Home rebuilt right before it is shown (transient + the admin's own localStorage copy dropped), "Before the refresh" on the hero MRR, the transfer is marked paid off-camera, Refresh (crossfade straight to the rebuilt page), then the measured change is labelled only when it is exactly hers (+1 live and +59..62$ MRR). Abigail's Recent subscriptions row with dim, then her member page: summary cards, 12-month orders & renewals chart, the new weekly plan row.
6. **Subscription KPIs in WooCommerce** (Pro, kicker "Pro reports"). Orders report Type filter (six types) -> Abigail's order as Subs Purchase -> Revenue split cards (sign-ups, renewals) -> 10 KPI cards -> Renewal Revenue card "Same as the Revenue report" (both values read at record time; this caption only runs when they match: 26,818.00$ both) -> churn card green -> date range -> chart (Week first, then Renewal Revenue) -> Top Subscription Products leaderboard.
7. **Find who might leave** (Pro). Home opened already scrolled to the Churn risk card (hero not shown again), Churn Analysis tiles (label on top), segment + sample size, Analyse with AI button (not clicked), Why they are at risk, risk row, search "Coffee", Export CSV.
8. **AI forecast** (Pro). Measured tiles, Last generated stamp, Metric/Horizon controls, outlook chart tooltip, outlook/confidence badges, assumptions/risks/opportunities (1.18x), provider/model/tokens line. Cached forecast only, no new AI request.
9. **Retention and refunds** (Pro). Cohort table (1.7x, framed right of the admin menu), customer + MRR retention, top 10 cancellation reasons, 8 refund cards, Time to Refund chart + median (label below the chart inside the card, held 2.6 s), outro.

### Display-only additions in this pass (recording browser only, nothing stored)

- Scene CSS: hides the storefront seed SKU (`DEMO-260927-WEEKLY`), the product category ("Member Plans"), breadcrumbs, the stock count, the theme menu's test links (Checkout Classic, Cart Page) and the "added to your cart" banner on checkout; hides the member page's seed role badges (`demo_member`, `demo_plus`; Customer stays); hides one old QA fixture row in the subscriptions list (`#5278`, on hold, "No product", Guest), which sits in the On-hold list. The list's "20 items" count is the product's real count (the fixture is one of them).
- Init script, AI Revenue Forecast page only: `Date.prototype.toLocale*String` default to the store's time zone (Asia/Dhaka, UTC+6), so the "Last generated" stamp reads in site time (Nov 28, 03:19 AM) instead of the recording browser's UTC. The harness context runs in UTC and CDP refuses a second time zone override, so this is the scene-local way to show what an admin in the store's zone sees. Every other page keeps the browser default.
- Home's localStorage copy is dropped in the recording browser before the chapter-5 "before" view.

### Store changes in this pass

- Orders/subscriptions from earlier takes deleted by the scene reset (customer 497, product 6713, created after 2026-11-27 21:30 UTC): #32687/#32688 (v1 final), #32805/#32806, #32926/#32927, #32987/#32988, #33111/#33112, #33148/#33149. Each chapter-4 take places one order by bank transfer; chapter 5 marks it paid (`payment_complete()`) and imports it into WooCommerce Analytics (`OrdersScheduler::import`), then invalidates the WC Analytics report cache.
- Home dashboard cache (`arraysubs_overview_v2_*` transients) flushed before chapters 1 and 5 (rebuilds on next load).
- No AI requests in this pass (the cached forecast from 2026-11-27 21:19 UTC was replayed; cache expires ~09:19 UTC Nov 28).
- No shared settings changed, no plugins toggled, no plugin code edited, no harness files edited.

### Final state after revision 2 (2026-11-28 ~07:30 site time)

- Store records this scene leaves: order **#33196** (customer 497 Abigail Cook, Weekly Coffee Delivery, bank transfer, Processing/paid) and subscription **#33197** (Active, 14$ weekly, next payment 2026-12-05 01:27 UTC). Customer 497's cart is empty.
  - Undo: `FV_PRO=1 wp eval '\ArraySubs\Supports\ActionScheduler::unscheduleAllForSubscription(33197); wp_delete_post(33197, true); wc_get_order(33196)->delete(true);' --allow-root` (from app/public, after sourcing app/.envrc).
- AI cache: forecast mrr/12 from 2026-11-27 21:19 UTC (expires ~09:19 UTC). No churn AI cache. No AI request in revision 2.
- `clean-map.d/analytics.json`: unchanged in revision 2 (display-only; delete to undo).
- Shared settings: none changed. Plugins: none toggled. Plugin code and harness: untouched.
- Final video: `$FV_OUT/analytics/analytics.mp4` (287.1 s, 1920x1080, 9 segments all native 1080p; ch4-ch6 last re-recorded 07:30) + `poster.webp` (clean Home with the hero ringed).
- Chapter 5 numbers in the final take: Home before 1,067 live / 41,701$, after Refresh 1,068 live / 41,762$ (+61$ = Abigail's 14$/week); chapter 6 renewal revenue 26,818.00$ on both the Revenue report and the KPI card.

### Product bugs seen (not fixed here: plugin code is off limits for this task)

- AI Revenue Forecast / Churn Analysis print money as `$41,574` while every other admin screen prints the store format `41,701$`: `arraysubspro/src/resources/pages/AiInsights/api.js` `formatCurrency()` always prefixes the symbol and ignores the WooCommerce currency position and decimals. Repro: WooCommerce > Settings > General, currency position Right; open Analytics > Revenue Forecast (or Churn Analysis "Monthly Revenue At Risk").
- AI reports' "Last generated" stamp (`api.js` date formatter, `toLocaleString` without the site time zone) follows the browser's time zone. Repro: site at UTC+6, browser at UTC: the stamp reads Nov 27, 09:19 PM while the site time is Nov 28, 03:19 AM.
- Home "Churn risk" card can never show the cached AI layer (limit 200 vs cache keys 25/50/100; see the v1 note above, code-only).
- Refunds > Time to Refund: at 1440 px wide the last two x-axis labels touch ("31-60 daysOver 60 days").


## Revision 3 (2026-11-28 07:45-08:10 site time): reviewer fix pass, chapters 6, 7 and 9 re-recorded

Backups: v2 scene at `$FV_OUT/../analytics.v2.mjs` (scratchpad), v2 segments 6/7/9 + v2 video/poster at `$FV_OUT/analytics/old-v2/`. Chapters 1-5 and 8 are the v2 segments, untouched.

### What changed

- **Ch7 (major): no "every live subscription" claim.** The Live Subscriptions tile (1,107) also counts the 351 meta-less `[Retention analytics seed]` QA posts, while the table scores ~756. Those records were NOT touched (owner's data). Scene-only fix:
  - Home caption is now "Pro scores live subscriptions from **0 to 100**: high, medium, low." with the card label "Risk mix and top drivers" (was "Pro gives every live subscription a churn risk score").
  - Churn Analysis caption "Each score comes from the subscription's **own history**. No AI needed." and the ring now covers only the High Risk, Medium Risk and Monthly Revenue At Risk tiles (`.arraysubs-ai-cards__card` union; label "High, medium, revenue at risk"). The Live Subscriptions tile is no longer ringed.
- **Ch7 (minor): row focus without the Recommended Action column.** The camera zooms (~1.4x, computed from the row) onto Subscription..Why of the first risk row and the ring stops at the Why column, so the AI empty state "Run the AI analysis for a tailored next step." is out of frame. The zoom is centred slightly low so the WP admin bar stays out of the zoomed frame.
- **Ch7 (minor): Export label.** After typing "Coffee" the cursor fades, the filtered table holds 1 s, then the "Download the list" label sits below Export CSV (side bottom), not on the search box.
- **Ch7 (minor): Home 0.00$ row.** Display-only (recording browser, nothing stored): before the Home view, rows of Home tables priced exactly `0.00$` are hidden by a style rule keyed by the row link (this take hid `#/subscriptions/detail/33167`, a parallel agent's free Starter Membership sign-up).
- **Ch7 (robustness): link to Churn Analysis.** `v.click(..., {nav:true})` on "AI churn analysis" stalled the stage's frame capture (the double-`requestAnimationFrame` wait in `capture()` never resolved) in 2 of 3 takes; the hung recorders (my own processes) were killed. The scene now uses a local `clickToPage()` helper: visible cursor travel + press, then the real link click, navigation and `READY.churn` wait run under the frozen picture, then crossfade. A standalone diagnostic (4 direct clicks, no capture loop) never hung, so the stall needs the capture loop running during the report's first render. No harness change.
- **Ch6 (minor):** after "Renewal revenue matches the Revenue report exactly" the chart now clicks **Week** only and stays on the default **Monthly Recurring Revenue** tab (headline 41,802.17$, same as the MRR card); label "MRR week by week". The Renewal Revenue tab is no longer clicked.
- **Ch9 (minor):** the "eight cards" ring is removed and the "Time to refund in seven buckets, plus the median." caption is shown before scrolling to the Time to Refund card.

### Store changes in this pass

- None. No orders, subscriptions, settings, options or plugins changed. No AI requests. No harness files edited.
- Records from revision 2 remain as documented above (order #33196 / subscription #33197 for customer 497).

### Result

- Final video: `$FV_OUT/analytics/analytics.mp4` 288.6 s, 1920x1080, 9 segments (ch6 43.0 s, ch7 36.5 s, ch9 31.0 s re-recorded). Poster unchanged (`poster.jpg` from chapter 1 -> `poster.webp`).
- Ch6 values at record time: Renewal Revenue 26,818.00$ on both the Revenue report and the KPI card; MRR 41,802.17$.
- Ch7 values at record time: Home churn card 19 high / 2 medium / 735 low; Churn Analysis 1,107 live (not ringed), 19 high, 2 medium, $801 at risk, "scored 756 in this segment".
