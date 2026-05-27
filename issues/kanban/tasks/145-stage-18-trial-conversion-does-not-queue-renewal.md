---
id: 145
title: 'stage-18: trial conversion does not queue renewal reminder'
status: closed
priority: medium
created: 2026-05-23T17:23:43.398005313+02:00
updated: 2026-05-24T21:44:25.623827942+02:00
started: 2026-05-24T21:38:33.86539716+02:00
completed: 2026-05-24T21:44:25.62382695+02:00
tags:
    - qa
    - stage-18
    - trials
    - renewals
    - scheduler
claimed_by: shell-quartz
claimed_at: 2026-05-24T21:44:25.623827832+02:00
class: standard
---

Stage: qa/stages/18-renewal-followup/07-trial-to-active-conversion.md\n\nAfter converting Trial Weekly subscription #1558 from Trial to Active via arraysubs_process_trial_conversions action #1038, the subscription state was correct: status Active, _trial_length=0, _trial_end_date preserved as historical value, _next_payment_date=2026-05-30 15:21:48 UTC (one week from conversion), completed_payments=0, Trial Converted email audit present.\n\nHowever Action Scheduler has no pending arraysubs_send_renewal_reminder action with args containing 1558, and no pending per-subscription trial-conversion action remained/appeared.\n\nExpected by Task 18.07: a renewal reminder should be queued for the new active cycle at next_payment_date minus the configured reminder days.\n\nImpact: converted trial subscriptions may not receive renewal reminders for their first paid renewal cycle unless another scheduler repair path later backfills them.

[[2026-05-24]] Sun 21:39
Plan/fix: trial conversion updates status before recalculating the first paid-cycle _next_payment_date, so the generic status-change reminder scheduler sees the expired trial date and schedules nothing. Added schedule_renewal_reminder() after the arraysubs_trial_converted email path, when the new active-cycle next payment date is already stored. Verifying live with #1558.

[[2026-05-24]] Sun 21:44
Verified fix live: reset subscription #1558 to Trial with expired _trial_end_date/_next_payment_date, reran arraysubs_process_trial_conversions. Result: status arraysubs-active, _trial_length=0, new _next_payment_date=2026-05-31 19:42:06 UTC, pending arraysubs_send_renewal_reminder action #1993 args [1558,3] scheduled 2026-05-28 19:42:06 UTC. Alumnium confirmed admin detail shows ACTIVE, next payment 1 June 2026 1:42 AM (UTC+6), Trial Converted notes. Screenshot: qa/artifacts/issue-145/subscription-1558-trial-converted-reminder-scheduled.png.
