---
id: 126
title: 'stage-13: 01 Customer Emails Trigger Matrix'
status: closed
priority: medium
created: 2026-05-19T22:56:16.647501545+02:00
updated: 2026-05-24T11:36:31.878092699+02:00
started: 2026-05-23T08:06:53.436235167+02:00
completed: 2026-05-23T10:40:37.989152308+02:00
tags:
    - qa
    - stage-13
class: standard
---

Source: stages/13-emails/01-customer-emails-trigger-matrix.md

[[2026-05-23]] Sat 10:40
Stage 13 Task 01 complete with delivery blocker. WC From address option: admin@mirror-help.arrayhash.com; from name option empty (WC preview/default uses site title mirror-help.arrayhash.com). Because no inbox/mail log/API access, live delivery, From headers, Message-ID, and received body are blocked by issue #40. Render smoke used wp_mail interception to prevent external sends and capture output for all 16 matrix entries. All direct email triggers returned ok=yes, one rendered mail each, no raw {...} placeholders: New Subscription/Activated (#1135), On-Hold (#653), Cancelled (#668), Expired (#673), Renewal Reminder (#1135), Renewal Invoice monthly (#683 order #1034), Payment Successful (#683/#1034), Payment Failed (#683/#1034), Trial Started (#663), Trial Ending Soon alias (#663), Trial Converted (#663), Subscription Expiring Soon alias (#673), Reactivated (#653), Auto-Downgrade (#683 old #233 -> new #197), Retention Discount Accepted (#683). Found copy gap: trial-ending and fixed/end reminders use generic renews soon wording; logged issue #86. Debug log contains old 2026-05-22 AS fatal plus one QA-command typo warning/deprecated from malformed preg_replace in my inspection command; no product runtime fatal from email triggers observed.

[[2026-05-24]] Sun 11:36
Issue #86 fixed/verified. Renewal reminder now renders trial-ending copy for subscription #663 and ending/final-invoice copy for subscription #673 when wp_mail is intercepted. Delivery/inbox proof remains blocked by issue #40.
