---
id: 150
title: stage-18 subscription expiring soon hook does not send email
status: closed
priority: high
created: 2026-05-23T18:30:16.475837023+02:00
updated: 2026-05-24T22:09:56.212785846+02:00
started: 2026-05-24T22:03:31.915897142+02:00
completed: 2026-05-24T22:09:56.212784864+02:00
tags:
    - qa
    - stage-18
    - email
    - expiration
claimed_by: shell-quartz
claimed_at: 2026-05-24T22:09:56.212785745+02:00
class: standard
---

Stage 18 Task 11 expiring-soon check. Fixture: subscription #1673 for member-limited@example.com, product #1664 Limited 3-Cycle Weekly, _subscription_length=3, _completed_payments=2, _next_payment_date=2026-05-24 12:00:00. Ran arraysubs_send_expiring_soon action #1392 with args [1673]; Action Scheduler marked it complete and Scheduled-Job note #1680 says '[Expiring Soon Email] Success'. Actual: no 'Email sent' note/audit exists for Subscription Expiring Soon, and code in EmailManager::send_expiring_soon_email() has the trigger commented out. Expected: customer Subscription Expiring Soon email should be sent and Activity Audit should include an email row.

[[2026-05-24]] Sun 22:05
Plan: implement SubscriptionExpiringSoonEmail in core emails using the existing renewal reminder templates with expiring context, register it as arraysubs_expiring_soon, wire emails.expiring_soon.enabled into EmailManager, and update send_expiring_soon_email() to trigger the email with days_before plus a per-target-date guard. Verify on #1673 by clearing sent guard, running arraysubs_send_expiring_soon, and checking for one Email sent audit row/note.

[[2026-05-24]] Sun 22:09
Fixed and verified live. Change: added SubscriptionExpiringSoonEmail (arraysubs_expiring_soon), registered it in EmailManager, wired emails.expiring_soon.enabled, and made arraysubs_send_expiring_soon trigger it with target-date sent guard. Verification on #1673: status Active, product Limited 3-Cycle Weekly, length=3, completed_payments=2, next_payment=2026-05-24 12:00:00 UTC. Cleared expiring guard and ran arraysubs_send_expiring_soon; new email note #3095 created, sent_for=2026-05-24 12:00:00|7. agent-browser confirmed subscription notes contain 'Email sent: [ArraySubs] Subscription Expiring Soon' and Activity Audits has EMAIL row for Subscription #1673. Screenshot: qa/artifacts/issue-150/subscription-1673-expiring-soon-email-sent.png.
