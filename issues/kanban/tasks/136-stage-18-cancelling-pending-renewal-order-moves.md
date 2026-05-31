---
id: 136
title: 'stage-18: Cancelling pending renewal order moves subscription on-hold'
status: closed
priority: high
created: 2026-05-23T16:18:09.364838334+02:00
updated: 2026-05-24T20:42:17.452518382+02:00
started: 2026-05-24T20:35:59.068856456+02:00
completed: 2026-05-24T20:42:17.45251738+02:00
tags:
    - qa
    - stage-18
    - renewals
    - orders
claimed_by: shell-quartz
claimed_at: 2026-05-24T20:42:17.452518281+02:00
class: standard
---

Task: stages/18-renewal-followup/01-time-travel-method.md\n\nSub-Task 01.7 says cancelling the pending renewal order created during time-travel should preserve the subscription for downstream tasks and final state should be Active.\n\nObserved during cleanup:\n- Subscription #357 generated pending renewal order #1401.\n- Admin cleanup cancelled order #1401.\n- Immediately after order cancellation, subscription #357 status became arraysubs-on-hold even though this was a QA cleanup cancellation, not a real payment failure path.\n- I restored #357 to arraysubs-active and restored _next_payment_date to 2026-05-27 09:39:25.\n\nExpected: cancelling a generated pending renewal order for cleanup should not automatically put the active subscription on hold, or the documented cleanup procedure should be changed to avoid this side effect.

[[2026-05-24]] Sun 20:36
Claimed. Reading QA plan and order/subscription status hooks before patch.

[[2026-05-24]] Sun 20:38
Plan: keep renewal-order failure guard, add cleanup for cancelled renewal invoices only. If _pending_renewal_order_id points to the cancelled renewal order, delete it; do not move subscription on-hold and do not clear failed renewal orders used for retry.

[[2026-05-24]] Sun 20:39
Implemented: cancelled renewal orders now clear _pending_renewal_order_id when it points at that order; failed renewal orders remain linked for retry.

[[2026-05-24]] Sun 20:42
Fixed and verified. Repro before fix now showed active status already guarded but stale _pending_renewal_order_id remained after cancelling renewal order #3011. After patch, verification order #3015: before cancel pending/meta=3015; after cancel order=cancelled, subscription #357 stayed arraysubs-active, _pending_renewal_order_id=0; restored _next_payment_date=2026-05-30 13:21:55. Browser detail verified ACTIVE, order #3015 cancelled in Order History. Screenshot qa/artifacts/issue-136/subscription-357-active-after-cancelled-renewal.png.
