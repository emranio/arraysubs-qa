---
id: 42
title: Removed Support menu persists in old saved My Account editor data
status: closed
priority: medium
created: 2026-09-19T18:14:01.246116+06:00
updated: 2026-09-19T18:38:57.139531+06:00
tags:
    - my-account-editor
    - bug
class: standard
---

Active QA task ID / scheduled day / plan: N/A (targeted fix requested 2026-09-19).
Affected subscriptions/orders: N/A.
Affected user: local admin ID 1, login admin, administrator role; old-site user IDs/URL not supplied.
Route: http://localhost:10003/wp-admin/admin.php?page=arraysubs-mainadmin#/profile-builder/my-account; isolated browser session support-menu-admin.
Reproduction: back up menu settings, append an old saved default row id=support/label=Support without a current menu or endpoint registration, and load the editor. Before the fix both REST and the actual editor retain Support despite no corresponding storefront item. User confirmed old saved data is involved.
Expected: removed default items disappear from the editor. Actual: saved rows are merged without checking current registrations.
Proof: qa/support-menu-2026-09-19/before.png and after.png. Raw saved option contained support during verification.
Scope/counterexamples: preserve custom items, renamed required rows, and registered endpoints hidden for the current viewer (Usages/features is absent from admin defaults but still registered).
Fix plan: filter stale defaults in REST responses only when neither current defaults nor endpoint registrations contain the ID; retain JSON array shape; verify browser save/reopen and restore local data.

Resolved 2026-09-19 in ArraySubsPro MenuConfigController::prepareItemsForResponse. Saved default rows without either a current menu entry or a registered WooCommerce endpoint are omitted and remaining rows are reindexed. Custom items and required rename/order behavior are unchanged.
Browser verification PASS: the actual stale support option produced a visible Support row before the fix; the same option remained stored but the row disappeared after the fix. Usages and required items remained. Save Configuration succeeded, a fresh navigation still had no Support, and WP-CLI confirmed the stale row was removed from the stored option. Original local menu and save timestamp were restored exactly. Screenshots inspected: qa/support-menu-2026-09-19/before.png and after.png.
The existing 47-check source integration suite and git diff --check passed; browser errors were empty. Changed PHP file has 417 lines. No lint/PHPCS or asset build was needed. Not deployed to the unspecified old site; it requires the updated Pro plugin.

Live deployment and verification (2026-09-19): Applied the same controller fix on https://thequillsacademy.com after confirming its original source exactly matched local HEAD. Support was present as a disabled default row in the actual My Account editor at /wp-admin/admin.php?page=arraysubs-mainadmin#/profile-builder/my-account before the change; reload removes it. Read-only admin REST comparison confirms Support is the only removed response item; all remaining labels, visibility flags, ordering, customization flag and settings-save timestamp are unchanged. No live menu settings were saved. Source readback matches local controller. Evidence: qa/quills-redirect-2026-09-19/editor-before.png and editor-after.png. User context: ID 3 arrayhashsupport administrator; subscription/order IDs N/A for this editor issue.
