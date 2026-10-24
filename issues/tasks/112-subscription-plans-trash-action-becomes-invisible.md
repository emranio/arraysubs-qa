---
id: 112
title: Subscription Plans Trash action becomes invisible on hover
status: closed
priority: medium
created: 2026-10-25T01:08:33.822783+06:00
updated: 2026-10-25T01:11:11.115868+06:00
started: 2026-10-25T01:08:44.621501+06:00
completed: 2026-10-25T01:11:11.115868+06:00
tags:
    - bug
    - admin-ui
    - plans
class: standard
---

Active QA task ID and scheduled day: N/A (targeted user-reported fix, 2026-10-25 Asia/Dhaka).
Plan markdown path: N/A; scoped fix plan below.
Affected subscription ID(s) and order ID(s): N/A (plan action styling); observed product #9624.
Affected WordPress user/customer ID(s), login/email and roles: admin, administrator; numeric ID/email N/A; customer N/A.
Exact route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/plans. Also observed plan actions at https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/plans.
Browser context: authenticated agent-browser session admin-display-local, Chromium, 1000 x 900.
Reproduction: open Subscription Plans, hover a plan row, then hover Trash.
Expected: destructive action remains visible against its light background.
Actual: Trash transitions to rgb(255, 255, 255) on rgba(0, 0, 0, 0), making the label disappear.
Proof: qa/admin-list-display-2026-10-25/evidence/plans-trash-hover-before.png; browser computed style confirms hover true and white text. Generic button-link-delete styling from another plugin collides with this row action.
Known scope/counterexamples: Duplicate and edit links remain visible; no product was trashed or changed.
Fix plan: replace the generic destructive class with a scoped ArraySubs class, style its default/hover/focus states, build assets, and verify real pointer and keyboard states with desktop and mobile layouts.

Completed: replaced the generic destructive-action class with arraysubs-plan-action--danger and scoped default/hover/focus styling. Production build passed. Real-browser hover now computes rgb(138, 36, 36); keyboard focus remains visible with the WordPress focus ring. Enter opens the existing confirmation and Cancel was selected. Plans remains 35 and Trash 0. Desktop and phone screenshots, measurements, and verification: qa/admin-list-display-2026-10-25/report.md. No product data changed.
