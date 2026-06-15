---
id: 168
title: 'stage-19: full refund must sync order and subscription status'
status: closed
priority: critical
created: 2026-05-24T13:24:50.504548224+02:00
updated: 2026-05-24T17:24:49.450387788+02:00
started: 2026-05-24T17:24:49.450386786+02:00
completed: 2026-05-24T17:24:49.450386786+02:00
tags:
    - qa
    - stage-19
    - refunds
    - lifecycle
claimed_by: shell-quartz
claimed_at: 2026-05-24T17:24:49.450387678+02:00
class: standard
---

QA progress task: #172 stage-19: 06 Subscription State After Full Refund. Plan: qa/stages/19-refunds/06-subscription-state-after-full-refund.md

Affected subscription/order IDs: reproduced on fresh smoke subscription #2819 and order #2817; repaired stale QA fixtures subscription/order #2651/#2633, #1758/#1755, #1587/#1585.

Affected WordPress users/customers: fresh smoke user qa-full-refund-sync-* @example.test; existing fixture users tied to the listed subscriptions.

Exact route/context: WooCommerce order refund flow /wp-admin/post.php?post=<order_id>&action=edit and ArraySubs subscription admin detail. WP-CLI smoke used wc_create_refund() against the real site database to exercise the same WooCommerce refund hooks.

Steps: create linked active subscription + paid parent order; issue a full refund for the full order amount. Expected: Woo order status becomes refunded and linked subscription becomes arraysubs-cancelled with end date, cancelled_by=system, reason=Full refund processed. Actual before fix: full-refunded orders could leave active subscriptions when the hook only considered latest renewal/original-order edge cases or when status side effects did not fire.

Proof after fix: smoke order #2817 refund #2822 status=refunded total=8.50 refunded=8.5; subscription #2819 status=arraysubs-cancelled end=2026-05-24 11:20:34 cancelled_by=system reason=Full refund processed. Consistency scan now reports active_full_refunded_subscriptions=0.

Scope notes: refund-on-cancellation settings still control cancellation -> refund behavior. This fix covers the inverse path: a completed full refund always reverses the paid subscription entitlement.

[[2026-05-24]] Sun 13:25
Closed after code fix and smoke verification. File changed: arraysubs/src/Features/Refunds/Services/Hooks.php.

[[2026-05-24]] Sun 17:16
Regression follow-up from user report: full Stripe refund can arrive through Stripe webhook/external path and only logs payment notes; it does not create/sync a local Woo refund, so order/subscription status can stay unchanged. Reopened to harden Stripe refund webhook sync.

[[2026-05-24]] Sun 17:24
Fixed Stripe external/full refund sync path: Stripe charge.refunded webhook now extracts real latest/cumulative refund amount, resolves order by PaymentIntent or Charge ID, creates a missing local WooCommerce refund with refund_payment=false, stamps Stripe refund ID meta, and lets core refund hooks mark the order Refunded and cancel linked subscriptions. Verification: fake external Stripe payload for order #2946/sub #2948 created local refund #2951, order status=refunded, refunded=8.50, subscription status=arraysubs-cancelled, cancelled_by=system, reason=Full refund processed. Duplicate same refund payload kept refund_count=1. agent-browser verified Woo order page shows Order #2946 Refunded, Total Refunded .50, #2948 arraysubs-cancelled. agent-browser verified subscription detail #2948 Cancelled, Full refund processed, order #2946 refunded -.50. agent-browser screenshots: qa/artifacts/refund-status-regression/06-stripe-external-order-refunded.png and 07-stripe-external-subscription-cancelled.png. PHP syntax check passed. Active full-refunded consistency scan: 0.
