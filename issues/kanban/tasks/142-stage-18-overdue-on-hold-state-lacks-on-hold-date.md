---
id: 142
title: 'stage-18: overdue on-hold state lacks on_hold_date'
status: closed
priority: high
created: 2026-05-23T17:09:29.470773519+02:00
updated: 2026-05-24T21:24:28.76031056+02:00
started: 2026-05-24T21:06:07.010855628+02:00
completed: 2026-05-24T21:24:28.760309568+02:00
tags:
    - qa
    - stage-18
    - renewals
    - grace
claimed_by: shell-quartz
claimed_at: 2026-05-24T21:24:28.76031045+02:00
class: standard
---

Stage: qa/stages/18-renewal-followup/05-grace-to-on-hold-transition.md\n\nTask expects _on_hold_date to be set when an overdue renewal moves a subscription to On Hold.\n\nObserved with subscription #1467 / renewal order #1495:\n- Due date time-traveled to 2026-05-19 15:06:39 UTC (4 days overdue, active grace is 3 days).\n- Stripe renewal failed with card_declined.\n- Subscription became arraysubs-on-hold (immediately during process_renewal; see issue #139).\n- Ran arraysubs_check_overdue_renewals action #1290.\n- Subscription remained arraysubs-on-hold and _pending_renewal_order_id=1495.\n- _on_hold_date is still empty.\n\nExpected: when subscription enters or is confirmed in On Hold due to overdue renewal, _on_hold_date should be current timestamp so the later on-hold grace/cancel timeline can calculate correctly.\n\nImpact: Task 18.06 on-hold-to-cancelled timing may be unreliable because cancellation depends on a missing on-hold timestamp.

[[2026-05-24]] Sun 21:06
Claimed. Inspecting on-hold lifecycle paths and _on_hold_date writes.

[[2026-05-24]] Sun 21:24
Fix/verification: no additional code change needed for this specific issue after lifecycle sync fixes. Reproduced with subscription #1467 and renewal order #3029: before overdue check, status arraysubs-active, next_payment 2026-05-20 19:07:28 UTC, pending_order_id 3029, _on_hold_date empty. Ran arraysubs_check_overdue_renewals; after check, status arraysubs-on-hold, pending_order_id 3029, _on_hold_date 2026-05-24 19:07:33 UTC. Admin UI verified subscription #1467 as ON HOLD with pending order #3029 and on-hold timeline note. Screenshot: qa/artifacts/issue-142/subscription-1467-on-hold-date-set.png.
