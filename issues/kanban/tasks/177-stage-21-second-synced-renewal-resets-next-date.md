---
id: 177
title: 'stage-21: Second synced renewal resets next date backward'
status: closed
priority: critical
created: 2026-07-08T01:01:52.246907935+02:00
updated: 2026-07-08T08:02:00.567739172+02:00
started: 2026-07-08T07:34:13.346657291+02:00
completed: 2026-07-08T08:02:00.56773831+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
    - renewals
    - scheduling
class: standard
---

QA progress task: #194, Stage 21, task 07.
QA plan path: qa/stages/21-flexible-renewal-sync/07-renewal-execution-and-advancement.md

Affected subscription/order IDs: subscription #8682; first renewal order #8818; second renewal order #8854.
Affected WordPress user/customer IDs: WP user/customer ID 331, login sync.full, email sync-full-20260708-0342@example.test, role customer.

Exact test URL/admin route: WP-CLI time travel and Action Scheduler execution for subscription #8682; payment simulated by updating BACS renewal orders to processing through `wp wc shop_order update ... --status=processing --user=admin --allow-root`.

Reproduction steps:
1. From the Stage 21 Segment 1 full BACS subscription #8682, set _next_payment_date to 2026-07-06 00:00:00 UTC.
2. Run renewal action IDs #9080 arraysubs_generate_renewal_invoice and #9081 arraysubs_process_renewal.
3. Mark renewal order #8818 processing. This correctly advances #8682 to 2026-08-31 18:00:00 UTC and completed payments 2.
4. For the second advancement check, set #8682 _next_payment_date to 2026-07-06 00:00:00 UTC again.
5. Run the new renewal action IDs #9131 and #9132.
6. Mark renewal order #8854 processing.

Expected result: The second renewal should continue boundary-to-boundary. After renewal order #8854 is paid, _next_payment_date should advance from the already-renewed August boundary to the following calendar boundary, expected 2026-09-30 18:00:00 UTC (Oct 1 site-local midnight UTC+6), not to a past or already-consumed boundary.

Actual result: After renewal order #8854 was paid, subscription #8682 stayed active and completed payments advanced to 3, but _next_payment_date became 2026-07-31 18:00:00 UTC, which is the first full renewal boundary that was already consumed by the previous cycle.

Concrete proof:
- After order #8818 payment: `sub status=arraysubs-active next=2026-08-31 18:00:00 completed=2 pending=`.
- Renewal order #8854 created with total $30.00, isrenew=yes, cycle=3, scheduled=2026-07-06 00:00:00.
- After order #8854 payment: `sub afterpay status=arraysubs-active next=2026-07-31 18:00:00 completed=3 pending=`.
- QA Mail Log captured invoice for subscription #8682 at 2026-07-07 23:00:49 UTC before the second BACS payment.

Known scope notes/counterexamples: First-cycle advancement worked for manual full #8682, manual prorate #8705, manual next_cycle #8728, and Stripe #8751. This defect appears on the second renewal advancement path for a flexible-sync subscription after completed payments were already 2.

[[2026-07-08]] Fix applied: OrderIntegration::getSyncedFirstRenewalBaseDate() anchored only renewal cycles <= 2 to _renewal_sync_first_full_renewal_date; cycle 3+ fell back to the order's _renewal_scheduled_date (the time-traveled date), so calculateAndSetNextPaymentDate derived the boundary from 2026-07-06 and reset next to the already-consumed 2026-07-31 boundary. Fix: consumed boundary is now derived from the stored anchor advanced (cycle_number - 2) billing intervals via arraysubs_increment_renewal_sync_boundary, so advancement is boundary-to-boundary regardless of when the renewal actually ran. File: arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php. Data repair: subscription #8682 _next_payment_date corrected to 2026-09-30 18:00:00. Verified live: third renewal re-run on #8682 (time-traveled to 2026-07-06, order #9059 cycle=4, paid via BACS->processing) advanced next to 2026-10-31 18:00:00 UTC (Nov 1 site-local midnight), completed=4, status active — forward boundary, no backward reset.
