---
id: 149
title: stage-18 renewal reminder hook sends duplicate emails
status: closed
priority: high
created: 2026-05-23T18:29:19.509694083+02:00
updated: 2026-05-24T22:03:21.023596028+02:00
started: 2026-05-24T21:59:05.693267626+02:00
completed: 2026-05-24T22:03:21.023594966+02:00
tags:
    - qa
    - stage-18
    - email
    - renewal
claimed_by: shell-quartz
claimed_at: 2026-05-24T22:03:21.023595928+02:00
class: standard
---

Stage 18 Task 11 duplicate-reminder check. Subscription #1668 (member1@example.com / Standard Weekly) had pending action #1390 [1668,1] scheduled at 2026-05-24 12:00 UTC. Running #1390 created email note #1676 and scheduled-job note #1677. A second manually scheduled same hook action #1391 [1668,1] also completed and created another Renewal Reminder email note #1678 plus scheduled-job note #1679. Expected: reminder should not send twice for the same subscription/cycle, or duplicate hook execution should be guarded by last-sent metadata. Actual: each hook execution sends another customer Renewal Reminder email.

[[2026-05-24]] Sun 22:00
Plan: add idempotency in EmailManager::send_renewal_reminder() using a per-subscription sent key built from current _next_payment_date and days_before. If the same cycle/reminder window already sent, return before triggering Woo mail. Mark the key only after trigger('renewal_reminder') succeeds so disabled/missing email classes do not poison future sends. Verify by clearing #1668 reminder sent meta, firing the hook twice for the same next_payment/days_before, and confirming only one Renewal Reminder email note appears.

[[2026-05-24]] Sun 22:03
Fixed and verified live. Change: EmailManager::send_renewal_reminder() now guards by _next_payment_date|days_before in _arraysubs_renewal_reminder_sent_for and only records it after trigger succeeds. Verification on #1668: cleared guard, set next_payment=2026-05-25 20:01:01 UTC, fired arraysubs_send_renewal_reminder [1668,1] twice. First fire created note #3092; second fire created no new note (new_after_second still [3092], new_count_after_second=1). agent-browser/agent-browser verified admin detail shows latest Renewal Reminder note once. Screenshot: qa/artifacts/issue-149/subscription-1668-renewal-reminder-single-send.png. Cleanup: restored #1668 next_payment=2026-05-25 12:00:00 UTC and sent guard=2026-05-25 12:00:00|1.
