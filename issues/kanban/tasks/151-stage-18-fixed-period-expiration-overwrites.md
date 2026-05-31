---
id: 151
title: stage-18 fixed-period expiration overwrites configured end date
status: closed
priority: high
created: 2026-05-23T18:41:48.473644235+02:00
updated: 2026-05-24T22:16:29.410284785+02:00
started: 2026-05-24T22:10:05.997344082+02:00
completed: 2026-05-24T22:16:29.410283703+02:00
tags:
    - qa
    - stage-18
    - fixed-period
    - expiration
claimed_by: shell-quartz
claimed_at: 2026-05-24T22:16:29.410284665+02:00
class: standard
---

Stage 18 Task 12. Fixture subscription #1687 fixed-period plan had original _end_date=2026-06-06 17:59:59 UTC, then time-traveled to past _end_date=2026-05-23 15:38:40 UTC. Running arraysubs_expire_subscription action #1397 changed status Active -> Expired, but ExpirationChecker::expire() overwrote _end_date to execution time 2026-05-23 16:40:10 UTC. Expected: fixed-period target/end date should be preserved as the configured/time-traveled end date so audit/admin/customer views can show the actual membership end, not the job execution timestamp.

[[2026-05-24]] Sun 22:10
Plan: update ExpirationChecker::expire() so fixed-period subscriptions preserve the already configured/time-traveled _end_date when _arraysubs_fixed_end_date_enabled=yes and the date is valid. Only non-fixed/payment-count expirations should stamp _end_date with execution time. Verify by resetting #1687 Active with _end_date=2026-05-23 15:38:40 UTC, running arraysubs_expire_subscription, and confirming status Expired while _end_date remains that exact value.

[[2026-05-24]] Sun 22:16
Fixed and verified live. Change: ExpirationChecker::expire() now preserves existing _end_date for fixed-period subscriptions (_arraysubs_fixed_end_date_enabled=yes) and only stamps execution time for non-fixed expirations. Verification on #1687: reset Active with _end_date=2026-05-23 15:38:40 UTC and next_payment=2026-05-30 16:36:56 UTC, ran arraysubs_expire_subscription. Result: status arraysubs-expired, _end_date still 2026-05-23 15:38:40 UTC, next payment empty, cancellation_reason empty. Alumnium/Playwright verified admin detail shows EXPIRED and End Date 23 May 2026 9:38 PM (UTC+6). Screenshot: qa/artifacts/issue-151/subscription-1687-fixed-end-date-preserved.png.
