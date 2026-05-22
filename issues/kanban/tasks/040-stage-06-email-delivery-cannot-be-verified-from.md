---
id: 40
title: 'stage-06: Email delivery cannot be verified from accessible QA tooling'
status: blocked
priority: high
created: 2026-05-20T14:25:13.08882755+02:00
updated: 2026-05-22T02:56:34.132324671+02:00
started: 2026-05-22T02:55:44.84847106+02:00
tags:
    - qa
    - stage-06
    - email
    - blocked
blocked: true
block_reason: Needs QA-accessible mailbox/catch-all, WP Mail SMTP content logs, local mail catcher, or read-capable Resend/API credentials. Current environment exposes none; delivery proof cannot be created or verified from browser/code.
claimed_by: mold-glade
claimed_at: 2026-05-22T02:56:34.132324581+02:00
class: standard
---

Stage 06 Task 06 requires opening customer/admin inboxes for subscription creation emails. QA environment currently exposes no accessible mailbox/catch-all. WP Mail SMTP logs are disabled (log_email_content=false), and the configured Resend API key is send-only: GET /emails returns 401 restricted_api_key. Without inbox/log/API read access, QA cannot prove delivery, subject, Message-ID, sender headers, or exact rendered live body for active/trial/admin emails. Expected: test environment provides mailbox, mail catcher, WP mail content logs, or Resend read-capable API for QA. Actual: only send path configuration is visible.

[[2026-05-22]] Fri 02:56
Plan/audit: verify Stage 06 Task 06 requirements, inspect SMTP/log/API access, then either enable/read a local proof source or block if no proof source exists. Findings: task requires real inbox/admin inbox/message headers. Active plugin is wp-mail-smtp; options show mailer=resend, logs.enabled=false, logs.log_email_content=false. No mail/email log DB table exists beyond Action Scheduler logs. No mail catcher/Mailpit/MailHog/env config found. Existing Resend key is send-only per original issue; no read-capable API credentials in workspace. Code previews cannot prove live delivery, Message-ID, headers, or received body. This cannot be fixed from code/browser without adding or receiving external QA mailbox/log access.
