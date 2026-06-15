---
id: 137
title: 'stage-18: mail catcher or email content log unavailable'
status: closed
priority: high
created: 2026-05-23T16:21:06.95737705+02:00
updated: 2026-05-24T20:47:29.776749828+02:00
started: 2026-05-24T20:42:27.451490639+02:00
completed: 2026-05-24T20:47:29.776748866+02:00
tags:
    - qa
    - stage-18
    - prerequisites
    - email
claimed_by: shell-quartz
claimed_at: 2026-05-24T20:47:29.776749728+02:00
class: standard
---

Stage: qa/stages/18-renewal-followup/README.md\n\nStage 18 requires a mail catcher (MailHog/Mailpit/WP Mail Logger) so QA can inspect Renewal Invoice, Payment Successful, Payment Failed, On-Hold, Cancelled, Trial, Reminder, and Expiring emails.\n\nObserved before Task 18.02:\n- Active plugins include wp-mail-smtp, but WP Mail SMTP logs are disabled: logs.enabled=false and log_email_content=false.\n- No MailHog/Mailpit mailbox URL is documented in QA materials or reachable from current workspace context.\n- Available DB tables include wp_wpmailsmtp_debug_events, but not a normal email log with message body content.\n\nImpact: Email delivery can be inferred from ArraySubs Email audit/notes, but actual inbox/body/payment-link verification cannot be completed without enabling a mail catcher or mail content logging.

[[2026-05-23]] Sat 20:33
Stage 19 Task 06: customer/admin Subscription Cancelled email body proof for cust6@example.com/sub #1758 remains unverified because mailbox/mail-trap access is unavailable in QA context. Browser/DB lifecycle verified; email delivery proof blocked here too.

[[2026-05-23]] Sat 20:36
Stage 19 Task 06 reactivation cross-check: admin status dropdown changed sub #1758 Cancelled -> Active and DB notes show reactivation, but Subscription Reactivated email body/delivery proof remains unavailable due mailbox gap.

[[2026-05-23]] Sat 20:54
Stage 20 Task 01: Renewal Invoice and Payment Successful email body/delivery proof for cust1@example.com/sub #1773/order #1784 remains unavailable; manual invoice/order flow browser+DB verified, mailbox proof blocked.

[[2026-05-23]] Sat 21:12
Stage 20 Task 03: Renewal Invoice email for Sub-A #1791/order #1799 and Trial Converted email for Sub-B #1796 could not be verified by mailbox/body due existing mail catcher gap. Browser/DB notes show invoice/trial conversion events.

[[2026-05-23]] Sat 22:15
Stage 20.06 H4: final smoke email sequence for smoke@example.com/sub #2591 still cannot be inspected. Browser/DB prove checkout, renewal, cancellation attempt, reactivation, and plan switch; mailbox/body/header proof remains blocked by absent mail catcher/content logs.

[[2026-05-24]] Sun 20:44
Plan/fix: active dev-assist plugin will provide an admin-only Tools > QA Mail Log. It captures last 200 wp_mail calls with recipients, subject, headers, and body, so QA can inspect email contents without MailHog/Mailpit/WP Mail SMTP Pro logs.

[[2026-05-24]] Sun 20:47
Fixed and verified. Added active dev-assist QA Mail Log at Tools > QA Mail Log. It captures last 200 wp_mail calls with To, Subject, Headers, and Body. Smoke wp_mail created log entry for qa-mail-log@example.test / QA Mail Log Smoke; option count increased 0 -> 1; body and X-QA-Issue header captured. agent-browser verified page exists; agent-browser screenshot qa/artifacts/issue-137/qa-mail-log-body.png shows expanded message body/headers.
