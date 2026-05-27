---
id: 87
title: 'stage-13: Admin emails ignore ArraySubs admin email override'
status: closed
priority: high
created: 2026-05-23T10:42:50.934161451+02:00
updated: 2026-05-24T11:41:30.74490932+02:00
started: 2026-05-24T11:37:06.308084219+02:00
completed: 2026-05-24T11:41:30.744908348+02:00
tags:
    - qa
    - stage-13
    - email
    - admin-settings
claimed_by: shell-quartz
claimed_at: 2026-05-24T11:41:30.74490922+02:00
class: standard
---

Original task: stages/13-emails/02-admin-emails.md subtask 2.4.\n\nExpected: setting ArraySubs emails.admin_email to qa-billing@test.local replaces the default admin recipient for admin notification emails.\n\nObserved via rendered wp_mail capture with delivery intercepted because issue #40 blocks inbox proof:\n- Baseline helper arraysubs_get_admin_email() returned admin@mirror-help.arrayhash.com.\n- After arraysubs_update_setting('emails.admin_email', 'qa-billing@test.local'), arraysubs_get_admin_email() returned qa-billing@test.local.\n- Triggering Admin New Subscription still produced To: admin@mirror-help.arrayhash.com.\n\nCode evidence: admin email classes (AdminNewSubscriptionEmail, AdminPaymentFailedEmail, AdminSubscriptionCancelledEmail, AdminSubscriptionPendingCancellationEmail) use get_option('admin_email') as their default recipient instead of arraysubs_get_admin_email().\n\nWC email-level Recipient(s) override still works: setting recipient to admin@test.local, qa-support@test.local produced To: admin@test.local, qa-support@test.local.

[[2026-05-24]] Sun 11:38
Plan: add a shared BaseSubscriptionEmail admin-recipient helper using arraysubs_get_admin_email() with get_option('recipient', default) so WC per-email recipient overrides still win; update all admin email classes and settings descriptions; verify with wp_mail interception after setting emails.admin_email.

[[2026-05-24]] Sun 11:41
Fixed/verified. Admin email classes now use BaseSubscriptionEmail::get_admin_email_recipient(), whose default comes from arraysubs_get_admin_email(), while WooCommerce per-email Recipient(s) remains the explicit override. Settings help text now displays the ArraySubs-resolved default admin address. Verification with wp_mail interception: blank WC recipient + emails.admin_email=qa-billing@test.local sent Admin New Subscription to qa-billing@test.local; WC recipient 'admin@test.local, qa-support@test.local' still sent to that multi-recipient list. Restored original ArraySubs and WC email settings. php -l passed for BaseSubscriptionEmail and all four admin email classes.
