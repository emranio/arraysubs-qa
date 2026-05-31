---
id: 117
title: 'stage-16: Credit Purchase order type label is lowercase'
status: closed
priority: medium
created: 2026-05-23T13:29:28.678909649+02:00
updated: 2026-05-24T18:24:50.696473903+02:00
started: 2026-05-24T18:12:12.176593018+02:00
completed: 2026-05-24T18:24:50.696472651+02:00
tags:
    - qa
    - stage-16
    - analytics
claimed_by: shell-quartz
claimed_at: 2026-05-24T18:24:50.696473803+02:00
class: standard
---

Original task: progress #146, Stage 16 Task 04 WooCommerce Analytics Extension, sub-task 4.1/4.4.

Expected: order Type values include `Credit Purchase` with title-case label.
Observed: seeded credit purchase order #1319 appears in WooCommerce Analytics Orders table as `credit purchase`; backend `TypeResolver::TYPE_CREDIT_PURCHASE` stores `credit purchase`, while filter option label says `Credit Purchase`.
Impact: inconsistent report labels and QA expected-value mismatch.
Evidence: Orders report row #1319 showed Type `credit purchase`; DB type aggregate included `credit purchase|1|25`.

[[2026-05-24]] Sun 18:24
Fix applied: TypeResolver::TYPE_CREDIT_PURCHASE now stores and labels Credit Purchase title-case, and Analytics Orders filter option value was aligned to Credit Purchase. Existing QA HPOS order meta was updated from lowercase to title-case (wc_orders_meta rows updated: 2). Verification: php -l passed for TypeResolver.php; npm run build passed in arraysubspro; WP-CLI REST /wc-analytics/reports/orders returned order 1130 and 1110 with arraysubs_type=Credit Purchase; Playwright verified WooCommerce Orders UI contains Credit Purchase and no lowercase credit purchase. Screenshot qa/artifacts/issue-117/wc-orders-credit-purchase-title-case.png. Note: WC Analytics Orders shell loaded in browser but did not expose table rows in ARIA/visual route during this run, so the analytics report data was verified through the authenticated REST layer used by that report.
