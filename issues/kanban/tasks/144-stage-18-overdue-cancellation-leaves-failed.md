---
id: 144
title: 'stage-18: overdue cancellation leaves failed renewal linked'
status: closed
priority: high
created: 2026-05-23T17:14:35.979506435+02:00
updated: 2026-05-24T21:38:25.983036314+02:00
started: 2026-05-24T21:32:56.917882556+02:00
completed: 2026-05-24T21:38:25.983035343+02:00
tags:
    - qa
    - stage-18
    - renewals
    - cancellation
claimed_by: shell-quartz
claimed_at: 2026-05-24T21:38:25.983036204+02:00
class: standard
---

Stage: qa/stages/18-renewal-followup/06-on-hold-to-cancelled-transition.md\n\nAfter recreating overdue On-Hold state on subscription #1467, I set _next_payment_date=2026-05-12 15:12:42 UTC and _on_hold_date=2026-05-15 15:13:37 UTC, then ran arraysubs_check_overdue_renewals action #1302.\n\nCancellation worked partially:\n- Subscription status became arraysubs-cancelled.\n- _end_date and _cancelled_date set to 2026-05-23 15:13:57.\n- _cancelled_by=system.\n- _cancellation_reason=overdue_payment.\n- No pending Action Scheduler actions reference subscription #1467.\n\nFailures:\n- _pending_renewal_order_id remains 1520 after cancellation.\n- Renewal order #1520 remains failed, not cancelled.\n\nExpected: _pending_renewal_order_id cleared, and the unpaid/failed renewal order cancelled or otherwise detached according to task criteria.\n\nSuspect: RenewalProcessor::cancelPendingRenewalOrders only selects pending/on-hold orders, but failed renewal orders are also unpaid renewal orders in this flow.

[[2026-05-24]] Sun 21:35
Patched RenewalProcessor::cancelPendingRenewalOrders to include failed renewal orders as unpaid, include the _pending_renewal_order_id fallback, dedupe orders by ID, cancel only renewal orders for the subscription in unpaid statuses, and clear _pending_renewal_order_id after processing. Running live verification now against #1467/#3029.

[[2026-05-24]] Sun 21:38
Fix verified live. Used subscription #1467 and renewal order #3029. Setup: #1467 arraysubs-on-hold, _next_payment_date=2026-05-13 19:36:00 UTC, _on_hold_date=2026-05-16 19:36:00 UTC, _pending_renewal_order_id=3029, order #3029 status=failed. Ran arraysubs_check_overdue_renewals. Result: #1467 status arraysubs-cancelled, _pending_renewal_order_id empty, order #3029 status cancelled, _cancelled_by=system, _cancellation_reason=overdue_payment, _end_date=2026-05-24 19:36:08 UTC. Pending Action Scheduler actions containing 1467: 0. Alumnium/Playwright UI verified Cancelled + Overdue payment + order #3029 cancelled. Screenshot: qa/artifacts/issue-144/subscription-1467-overdue-cancelled-order-3029-cancelled.png.
