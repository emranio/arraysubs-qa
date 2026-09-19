---
id: 41
title: Keep Orders and Subscriptions required in account navigation and editor
status: closed
priority: high
created: 2026-09-19T17:00:00+06:00
updated: 2026-09-19T17:50:00+06:00
tags:
    - customer-portal
    - my-account-editor
    - bug
class: standard
---

Active QA IDs RM-01–RM-10, scheduled day 2026-09-19, plan qa/required-account-menus-2026-09-19/plan.md.
Affected subscriptions/orders: local subscription 28240, order N/A; original Quills report IDs N/A.
Users: local admin for editor, customer 525 / qa_download_settings_second_20260917 / role customer for storefront; Quills user ID N/A.
Routes: http://localhost:10003/wp-admin/admin.php?page=arraysubs-mainadmin#/profile-builder/my-account and /my-account/subscriptions/. Reported site: https://thequillsacademy.com/wp-admin/admin.php?page=arraysubs-mainadmin#/profile-builder/my-account (user screenshot only).
Reproduction before fix: with a request-only local option fixture omitting saved subscriptions, a woocommerce_account_menu_items filter at priority20 unsets subscriptions. Builder calls the fully filtered menu for defaults and has no row to render. Store Credit and Features remain. Screenshot qa/artifacts/menu-discovery-20260919/removed.png and removed.json capture actual UI/REST results. No production cause established.
Expected under new user requirement: Orders and Subscriptions always exist, only reorderable. Actual before change: optional hide/rename controls, disabled config could redirect their routes, and external menu filters could omit them from default discovery.
Counterexamples: omitting saved Subscriptions alone appended it at the bottom from defaults; keeping a saved row retained it even if defaults lacked it. See missing-saved.png and removed-saved.png. A renamed row displayed Support while retaining subscriptions ID (renamed.png/json). These are local fixtures, not assertions about Quills's stored data.
Implementation plan: shared core required-menu contract plus final filter restoration; Pro runtime/read/save normalization and default discovery enforcement; protected row metadata and reorder-only React controls; prevent disabled-route guards and custom endpoint collisions for required pages; browser regression in both plugin states, then restore original state.

Resolved 2026-09-19: required rows now have drag controls only, read/save/runtime normalization, and final menu-filter restoration preserving saved positions. Pro optional menu injections restore required anchors first; builder discovery enforces required defaults and restores nested bypass state. User also requested as- for ArraySubs and custom public URLs only; implemented subscriptions, view-subscription, store-credit, features, automatic custom normalization and collision protection, query synchronization and one-time soft rewrite refresh. WooCommerce built-in URLs remain unchanged.

Browser PASS RM-01–RM-10 on the local site: actual drag/save/reopen, custom create/open/hide/remove and reserved-slug validation, customization off/on, actual Pro deactivate/reactivate, request-only legacy/removed/missing/blank-endpoint cases, prefixed list/detail/Store Credit/Features. 35 actual-source checks and independent 480 ordering/removal combinations passed; production build and diff checks passed. Saved menu/settings/active-plugin hashes restored exactly; subscription metadata unchanged versus prior QA baseline. Runtime fixtures removed, impersonation exited and sessions closed. Proof and limitations: qa/required-account-menus-2026-09-19/report.md. Customer incident 36 remains open: this closes the reproduced code defect and requested behavior change, not the unverified production cause.

Follow-up RM-11, 2026-09-19: user requested Subscriptions immediately after Dashboard by default, otherwise first. Shared core helper updated without overriding saved custom ordering. Browser PASS in storefront and builder with absent saved config and late Dashboard removal plus leading Support; custom fixture retaining Subscriptions last passed. User context: local admin ID 1 / administrator; subscription/order IDs N/A. Routes and screenshots: report.md follow-up section above. 39 source checks pass; fixture removed, browser closed, raw menu/settings/active-plugin hashes unchanged.

Correction RM-12, 2026-09-19: user clarified that required items must remain renameable, including their page headings. The earlier rename lock was incorrect. Restored expand/custom-label inputs while retaining required visibility and blocking removal; preserved labels through read/save/REST/final restoration and page-title filters. Actual admin ID 1 saved/reloaded My Memberships and Purchase History; customer 525 saw both menu labels and page H1/browser titles at /my-account/as-subscriptions/ and /my-account/orders/. Active subscription 28240 remained visible. Late-removal fixture restored saved names; clearing labels restored defaults. Screenshots and scope: qa/required-account-menus-2026-09-19/report.md RM-12 section. 47 source checks and production build passed. Original raw menu/settings/activation/subscription metadata hashes restored; runtime fixture removed, impersonation exited, sessions closed. Required means no hiding/removal, not a naming lock.
