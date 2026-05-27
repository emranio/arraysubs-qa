---
id: 125
title: 'stage-17: Email reactivation logs duplicate audit rows'
status: closed
priority: medium
created: 2026-05-23T15:14:33.906016675+02:00
updated: 2026-05-24T19:51:50.392760328+02:00
started: 2026-05-24T19:48:17.979558218+02:00
completed: 2026-05-24T19:51:50.392759416+02:00
tags:
    - qa
    - stage-17
    - audits
    - email
claimed_by: shell-quartz
claimed_at: 2026-05-24T19:51:50.392760228+02:00
class: standard
---

Task: stages/17-audits-and-logs/02-audit-logging-settings.md\n\nDuring Sub-Task 02.7, re-enabling Email logging and changing subscription #697 from On Hold back to Active created two identical Email audit notes for one reactivation event.\n\nEvidence:\n- Email logging was ON.\n- Subscription #697 transitioned arraysubs-on-hold -> arraysubs-active once at 2026-05-23 19:13:57.\n- Audit notes #1377 and #1379 both say: Email sent: [ArraySubs] Subscription Reactivated.\n\nExpected: one Email audit row per single email event / status reactivation.\nActual: duplicate Email audit rows.

[[2026-05-24]] Sun 19:49
Plan: remove the duplicate reactivation email path in EmailManager::on_status_change, keep arraysubs_data_reactivated as the single reactivation email trigger, syntax-check the email manager, then reproduce an on-hold -> active transition and verify only one Email audit note is created and visible in Activity Audits.

[[2026-05-24]] Sun 19:51
Fix applied: removed the reactivation email fallback from EmailManager::on_status_change. Reactivation email/reminder now come only from arraysubs_data_reactivated after reactivation billing checks finish. PHP syntax check passed for EmailManager.php and PHP-FPM was reloaded.

Verification: transitioned subscription #697 arraysubs-active -> arraysubs-on-hold -> arraysubs-active. New notes above previous max #1379 were #2978 subscription reactivation details and #2979 Email sent: [ArraySubs] Subscription Reactivated, so only one new email audit row was created. Browser Activity Audits with Entity=Email and search Subscription Reactivated shows a single latest May 24, 2026 11:50 PM row for Subscription #697. Screenshot: qa/artifacts/issue-125/email-reactivation-single-row.png.
