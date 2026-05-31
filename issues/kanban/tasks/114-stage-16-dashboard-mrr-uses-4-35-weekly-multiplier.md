---
id: 114
title: 'stage-16: Dashboard MRR uses 4.35 weekly multiplier instead of manual 4.333'
status: closed
priority: medium
created: 2026-05-23T13:08:20.717296728+02:00
updated: 2026-05-24T17:57:49.969549781+02:00
started: 2026-05-24T17:50:27.073148339+02:00
completed: 2026-05-24T17:57:49.969548399+02:00
tags:
    - qa
    - stage-16
    - bug
claimed_by: shell-quartz
claimed_at: 2026-05-24T17:57:49.969549661+02:00
class: standard
---

Original task: stage-16 task 02 Subscription Performance Dashboard, Sub-Task 2.4/2.4a.\n\nObserved: Dashboard MRR card shows ,301.55 for Month to date (May 1-23, 2026). Independent sum using the QA plan/manual weekly formula Amount × 4.333 gives ,298.49. Each 9.99 weekly subscription contributes 6.96 in the dashboard/leaderboard, but expected is about 6.62.\n\nExpected: Weekly subscription MRR normalization should use 4.333 (≈30.44/7) per QA plan/manual.\n\nLikely cause: arraysubspro/src/Features/Analytics/REST/OverviewController.php normalizeToMonthly() uses 4.35 for weekly.

[[2026-05-24]] Sun 17:57
Fix applied: weekly MRR normalization now uses 4.333 in arraysubspro/src/Features/Analytics/REST/OverviewController.php. Cleared ArraySubs overview performance transients. Verification: php -l passed; REST /arraysubs/v1/analytics/overview/performance for 2026-05-01..2026-05-23 returned MRR 9301.31, matching manual active/trial subscription sum with weekly multiplier 4.333. Browser verification: Alumnium showed Monthly Recurring Revenue 0,189.15 for Month to date May 1-24; WP-CLI manual sum for 2026-05-24T23:59:59 also returned 10189.15. Playwright screenshot: qa/artifacts/issue-114/mrr-weekly-4333.png.
