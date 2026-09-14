---
id: 31
title: Home refetches overview API whenever the browser tab regains focus
status: closed
priority: medium
created: 2026-09-14T17:46:25.399562+06:00
updated: 2026-09-14T17:49:14.728145+06:00
started: 2026-09-14T17:46:43.452357+06:00
completed: 2026-09-14T17:49:14.728145+06:00
tags:
    - bug
    - admin
    - performance
class: standard
---

Active QA task ID / scheduled day / plan: N/A; targeted user-reported fix, no active QA cycle. Subscription IDs / order IDs: N/A. WordPress user: ID 1, login admin, administrator; no customer account involved. Test URL: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/overview. Browser context: isolated agent-browser focus-fix-admin session, authenticated admin, Pro active. Reproduction: open Home, wait for initial data, note overview requests, switch to a blank tab for at least five seconds, return. Expected: tab focus preserves the displayed page and makes no API request. Actual: returning fires another GET /wp-json/arraysubs/v1/overview?range=30d. Proof: browser resource count rose from 2 to 3 after one trusted window focus event. Screenshot: /tmp/arraysubs-focus-before.png. Scope: shared core Home serves core and Pro widgets; QuickProductWizard also has a focus listener refetching its options. Manual Refresh and reporting-range changes are intentional reload triggers. Fix plan: remove both focus reload listeners, retain product-created and explicit refresh/range/setup actions, rebuild existing frontend assets, verify actual tab switches and manual controls in browser.

Resolution: Removed window-focus reload handlers from the core Overview page and QuickProductWizard. Retained explicit Refresh, range selection, setup completion, and product-created updates. Core owns the shared page and Pro supplies data through the existing payload filter; no Pro change needed. Existing unrelated edits were preserved. Validation: production frontend build succeeded. In the rebuilt local browser, a trusted focus event left overview resource count unchanged at 2; the page was not busy. Manual Refresh added exactly one request with refresh=1; choosing 7 days added exactly one request with range=7d. With the quick-product dialog open, a second real tab switch left total ArraySubs API resources unchanged at 6, preserving the product-type step and selection. No browser JavaScript errors reported. Final screenshots: /tmp/arraysubs-focus-fixed-home.png and /tmp/arraysubs-focus-fixed-dialog.png. Modified source line counts: Overview/index.jsx 231; QuickProductWizard.jsx 545. No lint or PHPCS run.
