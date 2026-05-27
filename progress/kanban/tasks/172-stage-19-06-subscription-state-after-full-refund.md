---
id: 172
title: 'stage-19: 06 Subscription State After Full Refund'
status: closed
priority: medium
created: 2026-05-19T22:56:24.251868374+02:00
updated: 2026-05-24T23:27:54.161652994+02:00
started: 2026-05-23T08:06:53.488132802+02:00
completed: 2026-05-23T20:36:52.717047277+02:00
tags:
    - qa
    - stage-19
class: standard
---

Source: stages/19-refunds/06-subscription-state-after-full-refund.md

[[2026-05-23]] Sat 20:15
Continuing #172: baseline captured for sub #1758/order #1755; now verifying Woo order refund path and post-refund state.

[[2026-05-23]] Sat 20:36
Result: PASS with known/issues logged. Parent order #1755 refunded in full via Stripe/Woo refund (#1761, 9.99, re_3TaK0vJG5OzSNVs20Hgvoj8r). Subscription #1758 auto-cancelled, end date set, cancelled_by=system, reason=Full refund processed. Admin/customer browser verified related order refund row, refund history, order #1755 Refunded /bin/bash.00, customer subscription detail refund history. Idempotency PASS: one Woo refund, total refunded 9.99. Known issues repeated/logged: #101 reminder action stayed pending before cleanup, #158 webhook audit/order note says /bin/bash.00, #103 vacation panel says active, #137 email body proof unavailable. New #162: cancelled portal detail exposed Update payment method. Reactivation regression: admin status dropdown changed #1758 Cancelled -> Active; notes/actions created; email proof still blocked by #137.

[[2026-05-24]] Sun 13:25
Follow-up hardening after user regression report: full Woo refund now always syncs the linked order to refunded and active linked subscription to arraysubs-cancelled. Smoke proof: order #2817 refund #2822 -> order_status=refunded, subscription #2819 -> arraysubs-cancelled. Existing inconsistent QA fixtures repaired: #2651/#2633, #1758/#1755, #1587/#1585. Consistency scan: active_full_refunded_subscriptions=0.

[[2026-05-24]] Sun 17:25
Follow-up for issue #168: Stripe external refund webhook path now mirrors missing gateway refunds into WooCommerce with refund_payment=false. Verified order #2946/sub #2948: local refund #2951, order Refunded, subscription Cancelled, reason Full refund processed; duplicate payload did not create a second refund. Screenshots: qa/artifacts/refund-status-regression/06-stripe-external-order-refunded.png and 07-stripe-external-subscription-cancelled.png. Active full-refunded scan remains 0.

[[2026-05-24]] Sun 23:27
Follow-up issue #162 fixed cancelled subscription action visibility. Pro payment-method updates are now lifecycle-gated: cancelled subscription #1758 no longer shows Update payment method in the customer portal, and the customer REST update endpoint returns 403. Active Stripe subscription #2591 still reports update capability available. Screenshot: qa/artifacts/issue-162/subscription-1758-cancelled-no-update-payment-method.png.
