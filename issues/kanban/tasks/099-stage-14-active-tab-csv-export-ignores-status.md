---
id: 99
title: 'stage-14: Active-tab CSV export ignores status filter'
status: closed
priority: high
created: 2026-05-23T11:56:42.281145216+02:00
updated: 2026-05-24T15:37:57.970274194+02:00
started: 2026-05-24T15:28:51.035396357+02:00
completed: 2026-05-24T15:37:57.970273283+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
    - export
claimed_by: shell-quartz
claimed_at: 2026-05-24T15:37:57.970274094+02:00
class: standard
---

Original task: stages/14-admin-subscriptions/07-export-subscriptions.md\n\nBrowser QA: clicked Active(17), then Export CSV. Success toast reported '29 subscriptions exported' instead of 17. Code inspection confirms SubscriptionsList handleExport calls /subscriptions/export with only format=csv and does not pass current status filter.\n\nControl check via REST with status=arraysubs-active returns 17 Active rows and BOM/header are correct; default export returns all 29 rows.\n\nExpected: CSV from Active tab contains only Active rows and row count matches Active tab count.

[[2026-05-24]] Sun 15:29
Fix pass started 2026-05-24 by shell-quartz. Verifying Stage 14 task 07 active-tab export filter and tracing DataList status state into SubscriptionsList export button.

[[2026-05-24]] Sun 15:30
Plan: expose DataList current statusFilter to custom buttons and include status=arraysubs-active when Export CSV is clicked from Active tab.

[[2026-05-24]] Sun 15:37
Fixed and verified. Code: DataList passes current statusFilter to custom buttons; SubscriptionsList adds status to export URL when current tab is not All. Check: npm run build passed. Browser: Alumnium confirmed active filter URL + Export CSV button. Playwright clicked Active(181), exported CSV, verified 181 data rows, every Status=Active, BOM present, filename subscriptions-export-2026-05-24-133732.csv. Artifacts: qa/artifacts/issue-99-active-tab-before-export.png and qa/artifacts/issue-99-active-export.csv.
