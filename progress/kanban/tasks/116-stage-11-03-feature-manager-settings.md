---
id: 116
title: 'stage-11: 03 Feature Manager Settings'
status: closed
priority: medium
created: 2026-05-19T22:56:15.097911619+02:00
updated: 2026-05-24T09:04:12.815239808+02:00
started: 2026-05-23T08:06:53.42519444+02:00
completed: 2026-05-23T08:55:14.382365386+02:00
tags:
    - qa
    - stage-11
class: standard
---

Source: stages/11-feature-manager/03-feature-manager-settings.md

[[2026-05-23]] Sat 08:53
QA done. Enterprise Plan #235 features seeded; cust1 active Pro #271 + Enterprise #678 verified. Per-subscription view PASS. Combined mode PASS (Seats 20, Storage 110, API Calls Unlimited, Plan Tier Enterprise). My Account usage toggle PASS via WP-CLI due known settings-click issue #69. Usage bump PASS: Pro Seats 3 / 5. Admin Feature Log usage toggle PASS: off hides Usage, on shows Pro Seats 3 / 5. Master switch off hides My Features + storefront feature list; on restores data. cust2 empty state PASS. Debug log: no fresh Feature Manager warnings; only old 2026-05-22 Action Scheduler WP-CLI fatal lines.

[[2026-05-24]] Sun 09:04
Issue #69 fix verified 2026-05-24 by shell-quartz. Browser clicks now toggle Feature Manager settings controls; confirmed Show on Product Page off + Combine mode persisted, then restored Show on Product Page on + Per Subscription mode.
