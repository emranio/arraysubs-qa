---
id: 165
title: 'stage-18: 11 Renewal Reminders & Subscription Expiring Soon'
status: closed
priority: high
created: 2026-05-19T22:56:23.267014234+02:00
updated: 2026-05-24T22:09:55.898795337+02:00
started: 2026-05-23T08:06:53.477470955+02:00
completed: 2026-05-23T18:33:39.732780672+02:00
tags:
    - qa
    - stage-18
class: standard
---

Source: stages/18-renewal-followup/11-renewal-reminders-and-expiring-soon.md

[[2026-05-23]] Sat 18:23
Continuing renewal reminder / expiring-soon QA. Confirmed code path: renewal reminder triggers email; expiring-soon handler currently has trigger commented, will verify in browser/logs.

[[2026-05-23]] Sat 18:33
QA complete. Fixtures: reminder subscription #1668 (member1@example.com / Standard Weekly #200) and expiring subscription #1673 (member-limited@example.com / Limited 3-Cycle Weekly #1664, length=3, completed_payments=2). Tested with renewal reminder days=1 and expiring-soon days=1, then restored to renewal=3 and expiring=7; restored #1668 next payment to 2026-05-25 12:00:00 UTC. 2-day state PASS: pending action #1390 scheduled for #1668 at 2026-05-24 12:00 UTC, no prior email note. 1-day fire PASS: ran #1390, browser verified Renewal Reminder email note and Scheduled-Job success. Duplicate check FAIL: second same-hook action #1391 created a second Renewal Reminder email note; issue #149. Expiring soon FAIL: ran action #1392 for #1673; Scheduled-Job success note appears, but no actual Email sent audit row; issue #150. Mailbox/body proof still blocked by existing #137. agent-browser verified sub details, Scheduled-Job Logs, and Activity Audits rows.

[[2026-05-24]] Sun 22:03
Issue #149 fixed and reverified. Duplicate arraysubs_send_renewal_reminder execution for #1668 now sends only one email note per _next_payment_date|days_before cycle; first replay created #3092, second replay created none. Screenshot: qa/artifacts/issue-149/subscription-1668-renewal-reminder-single-send.png. Fixture restored to next_payment=2026-05-25 12:00:00 UTC.

[[2026-05-24]] Sun 22:09
Issue #150 fixed and reverified. arraysubs_send_expiring_soon for #1673 now sends [ArraySubs] Subscription Expiring Soon email note #3095 and Activity Audits EMAIL row. Screenshot: qa/artifacts/issue-150/subscription-1673-expiring-soon-email-sent.png.
