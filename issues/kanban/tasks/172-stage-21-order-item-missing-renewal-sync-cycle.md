---
id: 172
title: 'stage-21: Order item missing renewal sync cycle start meta'
status: closed
priority: high
created: 2026-07-07T23:50:30.924867537+02:00
updated: 2026-07-08T08:01:59.291191612+02:00
started: 2026-07-08T07:26:16.276242554+02:00
completed: 2026-07-08T08:01:59.291190831+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
    - checkout
class: standard
---

QA progress task: #189, Stage 21, task 02.
QA plan path: qa/stages/21-flexible-renewal-sync/02-manual-full-segment-checkout.md

Affected subscription/order IDs: subscription #8682, order #8668, order item #540.
Affected WordPress user/customer: WP user/customer ID 331, login sync.full, email sync-full-20260708-0342@example.test, role customer.

Exact test URL/admin route: customer checkout https://mirror-help.arrayhash.com/checkout/?add-to-cart=8648 and order received https://mirror-help.arrayhash.com/checkout/order-received/8668/?key=wc_order_4OUsCti8hXUCo. Browser context: agent-browser session qa-customer-full. WP-CLI context: plugins workspace with --allow-root.

Reproduction steps:
1. Configure FRS Monthly 30 (#8648) with flexible renewal sync enabled, all segments active, 10/20 boundaries, first-charge mode full.
2. Open checkout as a fresh customer, select Direct bank transfer, and place order #8668.
3. Verify subscription #8682 and order item #540 meta after checkout.

Expected result: Order item meta should mirror the subscription _renewal_sync_* values from the task, including _renewal_sync_cycle_start_date = 2026-06-30 18:00:00 UTC.

Actual result: Subscription #8682 has _renewal_sync_cycle_start_date = 2026-06-30 18:00:00, but order item #540 does not have _renewal_sync_cycle_start_date. The order item only has _renewal_sync_enabled=yes, _renewal_sync_first_charge_mode=full, _renewal_sync_first_full_renewal_date=2026-07-31 18:00:00, and _renewal_sync_initial_recurring_amount=30.

Concrete proof:
- Subscription meta query returned: _renewal_sync_cycle_start_date 2026-06-30 18:00:00; _renewal_sync_first_full_renewal_date 2026-07-31 18:00:00; _renewal_sync_initial_recurring_amount 30.
- Order item meta query returned no _renewal_sync_cycle_start_date for order_item_id 540.
- Browser checkout/order evidence screenshots: qa/artifacts/stage-21-task-189-checkout-summary.png, qa/artifacts/stage-21-task-189-order-received-8668.png.

Known scope notes/counterexamples: Customer-facing Segment 1 checkout display passed; order total was $30.00; subscription #8682 activated correctly after order #8668 moved to processing; next payment stayed 2026-07-31 18:00:00 UTC; scheduler rows were created at 2026-07-31 12:00:00 UTC and 2026-07-31 18:00:00 UTC. The discrepancy is limited to the missing cycle-start meta on the order item in this run.

[[2026-07-08]] Wed 00:05
Additional occurrence during progress task #190 / Stage 21 task 03: prorate order #8691, subscription #8705, order item #541. Subscription meta contains _renewal_sync_cycle_start_date=2026-06-30 18:00:00 and _renewal_sync_initial_recurring_amount=22.26. Order item #541 contains _renewal_sync_enabled=yes, _renewal_sync_first_charge_mode=prorate, _renewal_sync_first_full_renewal_date=2026-07-31 18:00:00, _renewal_sync_initial_recurring_amount=22.26, but still no _renewal_sync_cycle_start_date.

[[2026-07-08]] Wed 00:11
Additional occurrence during progress task #191 / Stage 21 task 04: next-cycle order #8714, subscription #8728, order item #542. Subscription meta contains _renewal_sync_cycle_start_date=2026-07-31 18:00:00 and _renewal_sync_first_full_renewal_date=2026-08-31 18:00:00. Order item #542 contains _renewal_sync_enabled=yes, _renewal_sync_first_charge_mode=next_cycle, _renewal_sync_first_full_renewal_date=2026-08-31 18:00:00, _renewal_sync_initial_recurring_amount=30, but still no _renewal_sync_cycle_start_date.

[[2026-07-08]] Wed 00:32
Additional occurrence during progress task #192 / Stage 21 task 05: Stripe Run A order #8737, subscription #8751, order item #543; Stripe Run B order #8762, subscription #8776, order item #544. Both subscription records contain _renewal_sync_cycle_start_date, but the Stripe order items have only _renewal_sync_enabled, _renewal_sync_first_charge_mode, _renewal_sync_first_full_renewal_date, and _renewal_sync_initial_recurring_amount, with no _renewal_sync_cycle_start_date.

[[2026-07-08]] Wed 00:46
Additional occurrence during progress task #193 / Stage 21 task 06 Run A: Stripe next-cycle order #8791, subscription #8805, order item #545. Subscription meta contains _renewal_sync_cycle_start_date=2026-07-31 18:00:00 and _renewal_sync_first_full_renewal_date=2026-08-31 18:00:00. Order item #545 contains _renewal_sync_enabled=yes, _renewal_sync_first_charge_mode=next_cycle, _renewal_sync_first_full_renewal_date=2026-08-31 18:00:00, and _renewal_sync_initial_recurring_amount=30, but still no _renewal_sync_cycle_start_date.

[[2026-07-08]] Wed 01:15
Additional occurrence during progress task #195 / Stage 21 task 08: weekly order #8862, subscription #8876, order item #551; quantity-3 monthly order #8885, subscription #8899, order item #552. Both subscription records contain _renewal_sync_cycle_start_date (weekly #8876: 2026-07-03 18:00:00; quantity #8899: expected monthly cycle start for the Aug 1 boundary), but the corresponding order items still have no _renewal_sync_cycle_start_date.

[[2026-07-08]] Wed 01:16
Clarification for the #195 occurrence above: subscription #8899 has _renewal_sync_cycle_start_date=2026-06-30 18:00:00; order item #552 still lacks the order-item copy of that meta.

[[2026-07-08]] Wed 01:43
Stage 21 task #197 additional occurrence: Silver variation manual BACS checkout created order #8913, order item #554, subscription #8927, customer #339 sync.var.silver.manual.197@example.test. Subscription meta has _renewal_sync_cycle_start_date=2026-06-30 18:00:00, but order item #554 has _renewal_sync_cycle_start_date blank while _renewal_sync_enabled=yes, _renewal_sync_first_charge_mode=prorate, _renewal_sync_first_full_renewal_date=2026-07-31 18:00:00, _renewal_sync_initial_recurring_amount=22.26. Plan path qa/stages/21-flexible-renewal-sync/10-variation-checkout.md.

[[2026-07-08]] Wed 01:52
Stage 21 task #197 additional occurrence: Silver variation Stripe checkout created order #8978, order item #557, subscription #8992, customer #342 sync.var.silver.stripe.197@example.test. Subscription meta has _renewal_sync_cycle_start_date=2026-06-30 18:00:00, but order item #557 has _renewal_sync_cycle_start_date blank while _renewal_sync_enabled=yes, _renewal_sync_first_charge_mode=prorate, _renewal_sync_first_full_renewal_date=2026-07-31 18:00:00, _renewal_sync_initial_recurring_amount=22.26. Plan path qa/stages/21-flexible-renewal-sync/10-variation-checkout.md.

[[2026-07-08]] Fix applied: SubscriptionCreationTrait.php wrote _renewal_sync_cycle_start_date to the subscription but the order-item add_meta_data block omitted it. Added `$item->add_meta_data('_renewal_sync_cycle_start_date', ...)` alongside the other _renewal_sync_* item meta. File: arraysubs/src/Features/SubscriptionCheckout/Services/Traits/SubscriptionCreationTrait.php. Verified live: Store API BACS checkout for FRS Monthly 30 (#8648) created order #9051 / item #559 / subscription #9054; item meta now has _renewal_sync_cycle_start_date=2026-06-30 18:00:00 matching subscription meta exactly.
