---
id: 141
title: 'stage-18: failed renewal action is logged as success'
status: closed
priority: high
created: 2026-05-23T17:03:42.572105753+02:00
updated: 2026-05-24T21:05:52.190557263+02:00
started: 2026-05-24T21:02:18.529965278+02:00
completed: 2026-05-24T21:05:52.190556211+02:00
tags:
    - qa
    - stage-18
    - renewals
    - action-scheduler
claimed_by: shell-quartz
claimed_at: 2026-05-24T21:05:52.190557163+02:00
class: standard
---

Stage: qa/stages/18-renewal-followup/04-failed-renewal-grace-active.md\n\nTask expects the failed Stripe renewal attempt to appear in Scheduled-Job Logs as Process Renewal with status Failed and the gateway error message.\n\nObserved for subscription #1467 / renewal order #1471:\n- Stripe returned card_declined: Your card was declined.\n- Order #1471 became failed.\n- Subscription failure meta was set and retry scheduled.\n- Action Scheduler action #1287 arraysubs_process_renewal [1467] is status complete, not failed.\n- Browser Scheduled-Job Logs top rows show Process Renewal #1467 — Success — Processed renewal for subscription #1467, plus Check Overdue Renewals Success and Generate Upcoming Renewals Success. No failed Process Renewal row was visible.\n\nExpected: failed payment attempt should be represented as a failed/errored scheduled job log row, or at minimum success copy should not say the failed renewal was processed successfully.\n\nImpact: scheduled-job QA dashboard hides failed gateway renewals and can mislead operators during dunning troubleshooting.

[[2026-05-24]] Sun 21:02
Claimed. Inspecting ScheduledJobLogger and current logs; #126 may have fixed this class already.

[[2026-05-24]] Sun 21:05
Verified fixed by current ScheduledJobLogger business-failure detection. Synthetic logger check with temp _last_payment_failure_reason on sub #1467 produced Scheduled-Job Logs row: Process Renewal #1467, status Failed, error 'QA #141 synthetic decline reason'. Temp meta cleaned after check. Browser screenshot qa/artifacts/issue-141/process-renewal-failed-log.png shows failed red Process Renewal row. No additional code change needed for this issue.
