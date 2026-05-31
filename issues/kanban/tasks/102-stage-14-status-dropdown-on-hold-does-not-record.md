---
id: 102
title: 'stage-14: Status dropdown On Hold does not record on-hold date meta'
status: closed
priority: high
created: 2026-05-23T12:02:49.550224252+02:00
updated: 2026-05-24T16:06:14.826812182+02:00
started: 2026-05-24T15:54:49.88975199+02:00
completed: 2026-05-24T16:06:14.826811331+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
    - lifecycle
claimed_by: shell-quartz
claimed_at: 2026-05-24T16:06:14.826812082+02:00
class: standard
---

Stage 14 task 08 Sub-X #1135: Active -> On Hold via admin status dropdown showed On Hold and created notes/email records, but WP meta inspection found no _on_hold_date value. Future grace/on-hold cleanup depends on this timestamp per QA plan.

[[2026-05-24]] Sun 15:55
Fix pass started 2026-05-24 by shell-quartz. Verifying Stage 14 status side-effect plan and tracing on-hold status transition meta handling.

[[2026-05-24]] Sun 16:05
Fixed in arraysubs core lifecycle hook: every real transition into arraysubs-on-hold now writes _on_hold_date before downstream status-change listeners run. Verified with Alumnium UI status dropdown on temp subscription #2909, WP-CLI meta status=arraysubs-on-hold and _on_hold_date=2026-05-24 14:01:50, Playwright screenshot qa/artifacts/issue-102-temp-on-hold.png showing On Hold status and status-change note. Temp subscription #2909 and notes deleted. Syntax check passed: php -l arraysubs/src/Features/Subscriptions/Services/Hooks.php.
