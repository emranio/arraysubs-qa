---
id: 139
title: 'stage-15: 01 Member Search'
status: closed
priority: medium
created: 2026-05-19T22:56:18.629236468+02:00
updated: 2026-05-24T12:16:49.072234426+02:00
started: 2026-05-23T08:06:53.451769685+02:00
completed: 2026-05-23T12:19:05.554141832+02:00
tags:
    - qa
    - stage-15
class: standard
---

Source: stages/15-manage-members/01-member-search.md
anage Members page loaded with Find a Member field. Single-character 'c' showed no dropdown (passes visible 2-char behavior; network not directly observable). For 'customer', UI showed no dropdown. Browser-side fetch confirmed malformed URL/404 caused by apiBaseUrl index.php?rest_route=/ plus direct '?query=' concatenation; backend REST returns users for customer and vip@example.com. Direct URL #/manage-members/37 loaded VIP Customer profile. Logged critical issue #105. Fixture users created: customer2-5@arraysubs.test and vip@example.com.

[[2026-05-24]] Sun 12:16
Follow-up fix for linked issue #105 completed. Manage Members now uses `buildRestUrl()` for search/detail REST calls under plain permalink `index.php?rest_route=/`. Rebuilt ArraySubs assets. Playwright verification confirmed `customer` and `vip@example.com` searches return 200 responses and visible dropdown rows, one-character input sends no request, and selecting VIP loads `#/manage-members/37` profile. Screenshots saved under `qa/artifacts/issue-105-manage-members-search/`.
