---
id: 62
title: 'stage-08: Retention analytics churn chart shows not_provided despite reasoned logs'
status: closed
priority: medium
created: 2026-05-20T15:20:57.877253047+02:00
updated: 2026-05-22T05:40:11.662431008+02:00
started: 2026-05-22T05:34:51.890571006+02:00
completed: 2026-05-22T05:40:11.662430006+02:00
tags:
    - qa
    - stage-08
    - retention
    - analytics
claimed_by: mold-glade
claimed_at: 2026-05-22T05:40:11.662430927+02:00
class: standard
---

Task 07 dashboard: Activity Log contains Scheduled Cancel rows for cust1 / Basic Monthly with reason not_using, but Cancellation Reasons chart/legend shows only not_provided at 100%. With Require Reason enabled and reasoned logs present, chart should segment by actual cancellation reasons. Also older Cancelled rows show customer #0/product/reason empty, reducing analytics usefulness.

[[2026-05-22]] Fri 05:36
Plan: verify Task 08.07 and current retention log table; change Churn Reasons data to group reasoned cancellation-intent events (cancelled + scheduled_cancel) instead of only completed cancelled rows, with fallback to subscription cancellation/offer reason before not_provided. Also enrich Activity Log REST rows from current subscription/customer/product meta when old log snapshots are empty. Syntax-check, then browser/API verify reasons include real codes like not_using/temporary_pause/found_alternative and older rows no longer show customer #0/product blank in the UI response.

[[2026-05-22]] Fri 05:39
Fixed: Retention summary Cancellation Reasons now groups cancelled + scheduled_cancel reason rows with fallback to subscription cancellation/offer reason before not_provided. Activity Logs REST now enriches empty historical customer/product/reason fields from current subscription/user/product meta. Syntax checks passed. WP-CLI/API showed reasons: found_alternative, temporary_pause, not_using, qa role mapping, not_provided. agent-browser verified chart shows multiple real reasons and Activity Logs show member1/cust1 + product names instead of customer #0/product blank.
