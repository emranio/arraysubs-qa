---
id: 114
title: 'stage-11: 01 Define Product Features'
status: closed
priority: medium
created: 2026-05-19T22:56:14.654256901+02:00
updated: 2026-05-24T08:39:28.890009475+02:00
started: 2026-05-23T08:06:53.416656038+02:00
completed: 2026-05-23T08:21:13.829578886+02:00
tags:
    - qa
    - stage-11
class: standard
---

Source: stages/11-feature-manager/01-define-product-features.md

[[2026-05-23]] Sat 08:21
QA complete. Browser: Feature Manager tab visible when enabled; modal opens with Edit/Add Feature. Existing fixture had incomplete features; validated empty-name and bad-number validation. Empty-name cancel left blank read-only row, logged issue #67. Pro Plan pre-existing price is 9.99/week vs expected 9.99/week, logged issue #68. Set final Stage 11 feature fixture on Pro Plan #233 and verified after reload: Seats, API Calls, Storage (GB), Priority Support, Custom Domain, Plan Tier, Hidden Beta Flag; API Calls accepted Unlimited then reverted to 10000; Hidden Beta Flag disabled. Disabled module in settings via browser: product Feature Manager tab hidden. Re-enabled via browser: tab returned and features persisted. Debug log check found no fresh ArraySubs/Feature Manager PHP errors; only old 2026-05-22 Action Scheduler WP-CLI fatal lines.

[[2026-05-24]] Sun 08:30
Issue #67 fix verified 2026-05-24 by shell-quartz. Rechecked Sub-Task 1.3 invalid new feature row cancel via agent-browser on Pro Plan #233; modal returns to seven named rows and leaves no blank feature.

[[2026-05-24]] Sun 08:39
Issue #68 fixture verified 2026-05-24 by shell-quartz. Pro Plan #233 now matches Stage 11/Stage README contract: 9.99 / week in admin Subscription settings and storefront.
