---
id: 125
title: 'stage-12: 08 Store Credit Emails and Expiration'
status: closed
priority: medium
created: 2026-05-19T22:56:16.500840143+02:00
updated: 2026-05-23T10:35:17.027650738+02:00
started: 2026-05-23T08:06:53.435311678+02:00
completed: 2026-05-23T10:35:17.027649445+02:00
tags:
    - qa
    - stage-12
claimed_by: mold-glade
claimed_at: 2026-05-23T10:35:17.027650637+02:00
class: standard
---

Source: stages/12-store-credit/08-store-credit-emails-and-expiration.md

[[2026-05-23]] Sat 10:28
Starting Stage 12 Task 08. WC Store Credit Added/Used/Expiring/Expired emails are enabled per WC mailer. Live inbox/content verification remains blocked by issue #40; backend email-trigger paths and expiration behavior will be tested. Current cust3 balance before task: 5.00.

[[2026-05-23]] Sat 10:35
Stage 12 Task 08 complete. Email classes Added/Used/Expiring/Expired enabled per WC mailer. Direct URL section=arraysubs_credit_added showed blank settings, but Emails list Manage link section=arraysubspro_credit_added_email worked. Mailbox/body proof blocked by issue #40. Added expiry test credit log #1187 +30: balance 15 -> 45, expires_at initially 2027-05-23 08:29:06; customer portal showed +0 row. Time-travel method A: edited _expires_at. T-7 set 2026-05-30 08:30:13, ran CreditExpiration::processExpiredCredits(); _expiry_notified=1, one arraysubs_send_credit_expiring action scheduled, rerun did not duplicate; direct expiring hook fired. Then unscheduled pending action to avoid stale future send. Past expiry set 2026-05-23 08:29:52, processExpiredCredits() deducted 30: balance 45 -> 15, log #1188 source=expired amount=30, original #1187 marked expired/expired_amount=30; portal showed Expired -0 and expired source row. Disabled Credit Added in WC email settings via browser, added log #1189 +5 with email disabled; re-enabled via browser, added log #1190 +5 with email enabled. Final cust3 balance 5.00. Debug tail: no fresh PHP errors, only old 2026-05-22 Action Scheduler WP-CLI fatal.
