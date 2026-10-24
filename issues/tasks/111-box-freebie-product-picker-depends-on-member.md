---
id: 111
title: Box freebie product picker depends on Member Access module
status: closed
priority: high
created: 2026-10-24T16:08:31.034087+06:00
updated: 2026-10-24T16:16:48.90017+06:00
started: 2026-10-24T16:08:31.047563+06:00
completed: 2026-10-24T16:16:48.900169+06:00
tags:
    - bug
    - module-switches
    - subscription-box
class: standard
---

Active QA task: module-switches board #2, scheduled 2026-10-24, plan qa/module-switches-2026-10-24/plan.md. Subscription/order IDs: N/A. Product: 5922 test box (read-only editor session, no product saved). User: administrator ID 1, login admin; affected customers N/A. URL: http://localhost:10013/wp-admin/post.php?post=5922&action=edit, module-admin browser, free+Pro active, Member Access off, Subscription Box on. Repro: Edit Box Configuration; fill existing empty element label temporarily; continue to Discounts & Freebies; open Freebies; search Demo. Expected: independent Box product results. Actual: empty picker because GET /wp-json/arraysubs/v1/members-access/select-options?type=products and search=Demo both return 404. Proof: qa/module-switches-2026-10-24/evidence/pro-box-freebie-before-fix.png and browser network observations; authenticated browser fetch returned rest_no_route 404. Scope: box product/category element pickers already use owned endpoints; only freebie picker uses Member Access endpoint. Fix plan: box-owned freebie endpoint reusing shared product select helper; point picker there, rebuild and verify free+Pro and free-only with Member Access off.

Implemented box-owned /subscription-box/freebie-products using shared ProductSelectOptions. Production build passed. Real browser with Member Access off shows Demo products; network changed from Member Access 404 to Box endpoint 200 with Pro active, and free-only browser repeated search successfully. No product configuration was saved; temporary modal changes discarded. Evidence pro-box-freebie-fixed.png and free-fixed-box-freebies.png under current QA evidence. PHP/whitespace/line counts passed.
