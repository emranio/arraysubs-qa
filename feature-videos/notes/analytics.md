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
- Scene CSS (recording browser only, via `addInitScript`): hides the dismissible Home setup checklist (`.arraysubs-onboarding`) and WooCommerce's one-time Orders tour popup. Nothing stored.
- Home dashboard cache flushed before chapter 1 (`Overview\Builder::flush()`, transients `arraysubs_overview_v2_*` only; it rebuilds on the next load).
- Customer 497 (Abigail Cook): persistent cart + session row cleared before chapter 4 (a scouting probe had put Weekly Coffee Delivery in the cart).
- Chapter 4 places a real order: customer 497, Weekly Coffee Delivery (product 6713), bank transfer. Each take first permanently deletes the previous takes' orders (customer 497, product 6713, created after 2026-11-27 21:30 UTC) and their subscriptions.
- Chapter 5 `before` marks that order paid (`payment_complete()`, activates the subscription) and syncs it with `OrdersScheduler::import()`.
- Final records left on the store: see "Final state" below.
- No shared settings changed. No plugins activated/deactivated. No plugin code edited.

## Final state

(filled after the final take)
