---
id: 120
title: 'stage-17: Activity Audits entity filter returns wrong Type badges'
status: closed
priority: high
created: 2026-05-23T14:07:00.434756357+02:00
updated: 2026-05-24T18:55:53.388314089+02:00
started: 2026-05-24T18:46:36.604859186+02:00
completed: 2026-05-24T18:55:53.388313107+02:00
tags:
    - qa
    - stage-17
    - audits
claimed_by: shell-quartz
claimed_at: 2026-05-24T18:55:53.388313969+02:00
class: standard
---

Original task: progress #148, Stage 17 Task 01 Activity Audits, sub-task 01.4.

Expected: selecting Entity Type = Subscription shows only rows with Type badge Subscription.
Observed: Activity Audits UI set Entity filter to Subscription and pagination changed to 320 total, but first visible Type badges remained ORDER/ORDER/ORDER... for order status-change entries.
Impact: entity filter does not match displayed Type badge and cannot reliably narrow by entity.
Code clue: `AuditController::getEntitySqlCondition("subscription")` includes broad patterns like `Status changed`, so order status-change notes match the subscription filter before `classifyEntity()` displays them as ORDER.
Evidence: Alumnium session `plugins-1779536849`; entity filter value `Subscription`, first six Type badges extracted as `ORDER`.

[[2026-05-24]] Sun 18:55
Fix applied: Activity audit entity filtering now prefers stored _audit_entity metadata, which is the same source used by arraysubs_format_note() for the displayed Type badge; legacy content-pattern filtering only applies when a note has no stored entity. Verification: php -l passed for AuditController.php. WP-CLI REST entity=subscription returned total 922 with first 10 entities all subscription; entity=order returned total 189 with first 10 all order. Alumnium and Playwright browser verification after selecting Entity Type = Subscription showed first ten Type badges all SUBSCRIPTION and 922 total. Screenshot qa/artifacts/issue-120/activity-audits-subscription-filter.png.
