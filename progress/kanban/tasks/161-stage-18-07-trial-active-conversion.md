---
id: 161
title: 'stage-18: 07 Trial → Active Conversion'
status: closed
priority: high
created: 2026-05-19T22:56:22.530490877+02:00
updated: 2026-05-24T21:44:25.333137459+02:00
started: 2026-05-23T08:06:53.474077473+02:00
completed: 2026-05-23T17:26:43.213140489+02:00
tags:
    - qa
    - stage-18
class: standard
---

Source: stages/18-renewal-followup/07-trial-to-active-conversion.md

[[2026-05-23]] Sat 17:26
QA complete with one issue. Seeded missing member-trial fixture because documented user was absent (#135): user #44, Trial Weekly product #202, subscription #1558, parent order #1556. Baseline browser: status Trial, product Trial Weekly, Trial End and Next Payment both 2026-05-30 15:20:36 UTC, trial length 7 days, recurring 19.99/week, completed_payments 0, Trial Started email audit present. Time-traveled _trial_end_date and _next_payment_date to 2026-05-23 14:21:26 UTC; status stayed Trial. Ran pending arraysubs_process_trial_conversions action #1038. Result: #1558 Active, _trial_length=0, _trial_end_date preserved as 2026-05-23 14:21:26 UTC, _next_payment_date recalculated to 2026-05-30 15:21:48 UTC (weekly from conversion), completed_payments 0. Browser admin verified Active, next 30 May 2026 9:21 PM, Trial Converted email audit. Scheduled-Job Logs showed Process Trial Conversions Success. Activity Audits showed Status changed from Trial to Active by System and Trial Converted email. Customer portal member-trial verified Active with next 30 May 2026 and 19.99 every week. No Auto Downgrade audit found. Mail body/inbox verification blocked by #137. Failure: no pending arraysubs_send_renewal_reminder action for #1558 after conversion; logged #145.

[[2026-05-24]] Sun 21:44
Issue #145 fixed and reverified: Trial Weekly subscription #1558 conversion now queues pending arraysubs_send_renewal_reminder action #1993 args [1558,3] at next_payment minus 3 days; admin detail still shows Active and Trial Converted audit. Screenshot: qa/artifacts/issue-145/subscription-1558-trial-converted-reminder-scheduled.png.
