---
id: 126
title: 'stage-17: Failed process-renewal job is logged as Success'
status: closed
priority: high
created: 2026-05-23T15:38:07.694333475+02:00
updated: 2026-05-24T19:58:07.483308865+02:00
started: 2026-05-24T19:52:06.17370213+02:00
completed: 2026-05-24T19:58:07.483307552+02:00
tags:
    - qa
    - stage-17
    - audits
    - scheduled-jobs
    - renewals
claimed_by: shell-quartz
claimed_at: 2026-05-24T19:58:07.483308745+02:00
class: standard
---

Task: stages/17-audits-and-logs/03-scheduled-job-logs.md\n\nSub-Task 03.6 expects a Failed scheduled-job row when a renewal cannot be processed because the subscription product is missing.\n\nRepro:\n1. Subscription #959 active, original _product_id=197.\n2. Cancel pending renewal order #1382 and delete _pending_renewal_order_id.\n3. Set #959 _product_id=999999 and _next_payment_date one hour ago.\n4. Run Action Scheduler action #1243: hook arraysubs_process_renewal args [959].\n\nObserved:\n- Subscription #959 changed to arraysubs-on-hold.\n- _last_payment_failure_reason = Failed to create renewal order.\n- _payment_retry_attempts = 1.\n- Action #1243 status = complete.\n- Scheduled-job note #1393 says: [Process Renewal] Success / Processed renewal for subscription #959.\n\nExpected: Scheduled-Job Logs row should be Failed, red, with the failure reason, because the business renewal failed.

[[2026-05-24]] Sun 19:52
Plan: update ScheduledJobLogger so a completed Action Scheduler action can still be logged as Failed when arraysubs_process_renewal records a business failure on the subscription. Use _last_payment_failure_reason as the failure source, keep the existing handled renewal flow intact, then reproduce a missing-product renewal and verify the Scheduled-Job Logs UI shows a Failed Process Renewal row with the reason.

[[2026-05-24]] Sun 19:58
Fix applied: ScheduledJobLogger now detects handled business failures for arraysubs_process_renewal after Action Scheduler reports completion. If _last_payment_failure_reason is set on the subscription, it records the scheduled-job note as failed and uses that reason in the details. PHP syntax check passed and PHP-FPM was reloaded.

Verification: reproduced with subscription #959 by setting _product_id=999999 and _next_payment_date one hour in the past, then scheduling/running immediate action #1928. Action Scheduler action #1928 completed, but new scheduled-job note #2987 is _job_status=failed with error Failed to create renewal order. Browser Scheduled-Job Logs shows Failed / Process Renewal / #959 / Failed to create renewal order. Screenshot: qa/artifacts/issue-126/process-renewal-failed-row.png.

Cleanup: restored #959 to arraysubs-active, _product_id=197, _next_payment_date=2026-06-22 00:42:12, and cleared failure/retry/pending renewal meta.
