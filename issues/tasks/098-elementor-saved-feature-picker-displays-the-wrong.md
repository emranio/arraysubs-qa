---
id: 98
title: Elementor saved feature picker displays the wrong label after reload
status: closed
priority: medium
created: 2026-10-24T03:22:08.028696+06:00
updated: 2026-10-24T03:24:11.317428+06:00
started: 2026-10-24T03:22:08.071621+06:00
completed: 2026-10-24T03:24:11.275606+06:00
tags:
    - bug
    - qa
    - elementor
class: standard
---

Active QA: task 2; scheduled day 2026-10-24 (Asia/Dhaka); plan qa/unified-content-restrictions-2026-10-24/plan.md.
Affected subscriptions/order IDs: N/A; editor label issue only.
Affected user: WordPress administrator ID 1, login admin, role administrator.
Fixture: disposable Elementor page 20008, QA unified author Elementor 20261024.
Exact URL: http://localhost:10013/wp-admin/post.php?post=20008&action=elementor; isolated admin browser session unified-author-elementor-reload.
Steps: Set Plan Feature = Team Seats on Container > Advanced > ArraySubs Content Restrictions. Publish. Reload editor, reopen the same controls, and wait for saved AJAX labels.
Expected: Team Seats remains the visible selected feature.
Actual: The picker displays Support Level, the final returned feature option, even though the saved settings model still contains Team Seats. Feature options endpoint returns full list for include query; preloader appends every response row as selected.
Proof: qa/unified-content-restrictions-2026-10-24/evidence/elementor-feature-label-before.json and elementor-feature-label-before.png.
Known scope: Elementor AJAX saved-label preloader, observed on feature scalar picker; product/role routes may already respect include. Runtime saved key remains original until user changes selection.
Fix plan: Filter backfill response to the requested saved IDs before appending selected options. Verify selected feature/model agree after real publish/reload; preserve on-demand search behavior.

[[2026-10-24]] Sat 03:24
Implemented: AJAX saved-label backfill now filters server rows to the requested IDs that are still selected, safely locates existing options without interpolated CSS selectors, and ignores replies after the view is destroyed. Real publish/reload on page 20008 now displays Team Seats; DOM selected value, label and settings model all agree on Team Seats. Evidence elementor-final-reloaded.png/.json. Search remains on demand; no backend endpoint changes needed.
