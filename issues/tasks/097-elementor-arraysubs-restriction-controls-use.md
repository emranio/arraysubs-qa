---
id: 97
title: Elementor ArraySubs restriction controls use Elementor accents instead of the WordPress admin scheme
status: closed
priority: low
created: 2026-10-24T03:19:09.930043+06:00
updated: 2026-10-24T03:24:11.189999+06:00
started: 2026-10-24T03:19:09.976478+06:00
completed: 2026-10-24T03:24:11.149128+06:00
tags:
    - bug
    - qa
    - elementor
class: standard
---

Active QA: task 2; scheduled day 2026-10-24 (Asia/Dhaka); plan qa/unified-content-restrictions-2026-10-24/plan.md.
Affected subscription/order IDs: N/A.
Affected user: WordPress administrator ID 1, login admin, role administrator.
Fixture: disposable Elementor page 20008, QA unified author Elementor 20261024.
Exact URL: http://localhost:10013/wp-admin/post.php?post=20008&action=elementor; isolated admin session unified-author-elementor.
Steps: Open Container > Advanced > ArraySubs Content Restrictions and enable restriction. Inspect enabled switches and the default-message settings link.
Expected: ArraySubs switches, links and focused/selected controls follow var(--wp-admin-theme-color), per workspace Admin Accent Color rule.
Actual: Switches inherit Elementor pink rgb(243,186,253), links use rgb(37,99,235); editor body has no --wp-admin-theme-color or admin-color-* class.
Proof: qa/unified-content-restrictions-2026-10-24/evidence/elementor-accent-before.json and elementor-switch-native-class.png.
Known scope: ArraySubs controls within Elementor. Elementor own Publish/native layout controls intentionally keep their existing appearance.
Fix plan: Load WordPress native admin-scheme CSS variables, provide the current profile scheme class, and apply only scoped ArraySubs control/dropdown CSS using WP variables. Verify computed colors and screenshots after reload without changing profile/global settings.

[[2026-10-24]] Sat 03:24
Implemented: enqueued WordPress native wp-components/wp-base-styles variable definitions, supplied the current user admin-color class, and scoped accent styling only to ArraySubs controls plus named ArraySubs dropdowns. Real editor reload on page 20008 confirms body admin-color-modern and --wp-admin-theme-color #3858e9; ArraySubs checked switch backgrounds and helper links both compute rgb(56,88,233). Native Elementor controls retain Elementor styling. Evidence elementor-final-reloaded.png/.json. No profile or global setting changed.
