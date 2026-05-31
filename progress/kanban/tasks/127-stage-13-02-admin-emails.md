---
id: 127
title: 'stage-13: 02 Admin Emails'
status: closed
priority: medium
created: 2026-05-19T22:56:16.799522742+02:00
updated: 2026-05-24T11:41:38.396723232+02:00
started: 2026-05-23T08:06:53.438823452+02:00
completed: 2026-05-23T10:43:34.024330056+02:00
tags:
    - qa
    - stage-13
class: standard
---

Source: stages/13-emails/02-admin-emails.md

[[2026-05-23]] Sat 10:43
Stage 13 Task 02 complete with delivery blocker. Default admin recipient is admin@mirror-help.arrayhash.com. Render smoke via wp_mail interception: Admin New Subscription -> admin@mirror-help.arrayhash.com, subject New subscription #1135 from cust3; Admin Payment Failed -> admin@mirror-help.arrayhash.com, subject Payment failed for subscription #683; Admin Subscription Cancelled -> admin@mirror-help.arrayhash.com, subject Subscription #668 cancelled by cust1; all no raw placeholders. ArraySubs emails.admin_email override test failed: helper returned qa-billing@test.local but Admin New still sent to admin@mirror-help.arrayhash.com; issue #87. WC Recipient(s) override worked: admin@test.local, qa-support@test.local captured. WC disable for Admin New worked: 0 mails. ArraySubs admin_payment_failed toggle worked via EmailManager: with admin toggle off, only customer payment-failed mail captured. Reactivation: only customer reactivation mail captured, no admin mail. Restored arraysubs emails.admin_email blank, admin_payment_failed on, WC admin-new option removed. Delivery/headers blocked by issue #40. Debug log has no new product email errors; existing QA command typo warning remains noted in #126.

[[2026-05-24]] Sun 11:41
Issue #87 fixed/verified. Admin emails now default to arraysubs_get_admin_email() / emails.admin_email when WooCommerce per-email Recipient(s) is blank. wp_mail interception proved qa-billing@test.local override is used, and explicit WC multi-recipient list still wins. Original settings restored.
