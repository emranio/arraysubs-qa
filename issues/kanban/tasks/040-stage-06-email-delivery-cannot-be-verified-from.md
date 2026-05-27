---
id: 40
title: 'stage-06: Email delivery cannot be verified from accessible QA tooling'
status: blocked
priority: high
created: 2026-05-20T14:25:13.08882755+02:00
updated: 2026-05-23T22:15:23.482610832+02:00
started: 2026-05-22T02:55:44.84847106+02:00
tags:
    - qa
    - stage-06
    - email
    - blocked
blocked: true
block_reason: Needs QA-accessible mailbox/catch-all, WP Mail SMTP content logs, local mail catcher, or read-capable Resend/API credentials. Current environment exposes none; delivery proof cannot be created or verified from browser/code.
class: standard
---

Stage 06 Task 06 requires opening customer/admin inboxes for subscription creation emails. QA environment currently exposes no accessible mailbox/catch-all. WP Mail SMTP logs are disabled (log_email_content=false), and the configured Resend API key is send-only: GET /emails returns 401 restricted_api_key. Without inbox/log/API read access, QA cannot prove delivery, subject, Message-ID, sender headers, or exact rendered live body for active/trial/admin emails. Expected: test environment provides mailbox, mail catcher, WP mail content logs, or Resend read-capable API for QA. Actual: only send path configuration is visible.

[[2026-05-22]] Fri 02:56
Plan/audit: verify Stage 06 Task 06 requirements, inspect SMTP/log/API access, then either enable/read a local proof source or block if no proof source exists. Findings: task requires real inbox/admin inbox/message headers. Active plugin is wp-mail-smtp; options show mailer=resend, logs.enabled=false, logs.log_email_content=false. No mail/email log DB table exists beyond Action Scheduler logs. No mail catcher/Mailpit/MailHog/env config found. Existing Resend key is send-only per original issue; no read-capable API credentials in workspace. Code previews cannot prove live delivery, Message-ID, headers, or received body. This cannot be fixed from code/browser without adding or receiving external QA mailbox/log access.

[[2026-05-23]] Sat 09:24
Stage 12 Task 02 Subtask 2.5 also blocked by same environment gap: no accessible Mailpit/WP Mail Logging table found; wp-mail-smtp active but no log table visible. Credit Added email registration is enabled and add action triggered, but mailbox arrival/body cannot be independently verified from available QA tooling.

[[2026-05-23]] Sat 09:54
Stage 12 Task 05 Subtask 5.4 also blocked by same mail-capture gap. Standard Weekly order #1133 had store credit applied via workaround and should trigger Credit Used email, but no accessible inbox/mail log is available to verify subject/body/placeholders.

[[2026-05-23]] Sat 10:14
Stage 12 Task 06 Subtasks 6.4-6.6 also blocked by same mail-capture gap. Renewal orders #1152, #1158, #1164, #1170, #1176 had store credit applied and should trigger Credit Used emails, but no accessible inbox/mail log/API read access exists to verify arrival/body/placeholders.

[[2026-05-23]] Sat 10:26
Stage 12 Task 07 Subtask 7.6 also blocked by same mail-capture gap. Refund-to-credit log #1186 added 5 to cust3 from order #1110 and should trigger Store Credit Added email with source Refund, but no accessible inbox/mail log/API read access exists to verify delivery/body/placeholders.

[[2026-05-23]] Sat 10:34
Stage 12 Task 08 email body/delivery checks also blocked by same mail-capture gap. Backend/UI triggered Credit Added (logs #1187/#1189/#1190), Credit Used already from #1152/#1158/#1164/#1170/#1176/#1182, Credit Expiring hook for log #1187, and Credit Expired for log #1188. WC email classes are enabled except during the intentional disabled window, but no accessible inbox/mail log/API read access exists to verify subjects/body/placeholders or disabled-delivery absence.

[[2026-05-23]] Sat 10:39
Stage 13 Task 01 customer lifecycle email delivery/header checks blocked by same mail-capture gap. Render smoke via wp_mail interception generated all 16 customer matrix outputs with no raw {...} placeholders, but this is not live delivery and cannot prove inbox arrival or From headers. Subjects captured: new subscription, on-hold, cancelled, expired, renewal reminder, renewal invoice, payment successful, payment failed, trial started, trial converted, reactivated, auto-downgrade, retention discount accepted. Trial-ending/fixed-end wording issue logged separately as #86.

[[2026-05-23]] Sat 10:42
Stage 13 Task 02 admin email delivery/header checks blocked by same mail-capture gap. Render smoke via wp_mail interception produced Admin New Subscription, Admin Payment Failed, Admin Subscription Cancelled to admin@mirror-help.arrayhash.com with no raw placeholders. WC recipient override, WC enable/disable, ArraySubs admin_payment_failed toggle, and no-admin-reactivation behavior were smoke-tested by captured sends, but live inbox delivery/multi-recipient headers cannot be proven without mailbox/log access. ArraySubs admin recipient override bug logged as #87.

[[2026-05-23]] Sat 10:48
Stage 13 Task 03 Store Credit email live delivery/body checks blocked by same mail-capture gap. Render smoke via wp_mail interception confirmed all four Store Credit emails registered/enabled and rendered to cust3 with no raw placeholders; Credit Used toggle off suppressed only Credit Used; Credit Added still fired; re-enable restored; Plain text type rendered text/plain/no HTML. Subject/body customization captured, but money placeholders in customized subject included wc_price HTML; issue #88.

[[2026-05-23]] Sat 10:51
Stage 13 Task 04 live Stripe email delivery checks blocked by same mail-capture gap. Card Expiring and Renewal Requires Verification are registered/enabled and hooked from StripeDelegate; render smoke via wp_mail interception generated both to cust1 with no raw placeholders. Per-email disable/re-enable worked. Actual Stripe SCA browser checkout/renewal inbox proof not possible without mailbox; PayPal/Paddle skipped by instruction. SCA email content gap logged as #89.

[[2026-05-23]] Sat 10:53
Stage 13 Task 05 live inbox checks blocked by same mail-capture gap. WC per-email toggles for 13 customer email types were smoke-tested with wp_mail interception: each disabled state produced 0 mails and each re-enabled state produced 1 captured mail. Sanity check captured both customer New Subscription and Admin New Subscription after restoring toggles. This proves code-level toggle behavior but not actual inbox delivery.

[[2026-05-23]] Sat 22:15
Stage 20.06 H4 blocked by same mailbox gap. Smoke run generated/should generate order confirmation, new subscription, renewal invoice, payment successful, cancellation/reactivation, and plan-switch related emails for smoke@example.com/sub #2591, but no accessible inbox/mail log/API read path exists to verify delivery/body/header sequence.
