---
id: 37
title: Subscriptions menu disappears when Orders is absent before the core menu filter
status: closed
priority: medium
created: 2026-09-18T02:23:36.58798+06:00
updated: 2026-09-18T18:49:29+06:00
started: 2026-09-18T18:28:26.508413+06:00
completed: 2026-09-18T18:31:09.351912+06:00
tags:
    - bug
    - customer-portal
    - menu
class: standard
---

Active QA task ID / scheduled day / plan: N/A; targeted investigation, no active QA cycle.
Affected subscription: local 28240, active. Order IDs: N/A; portal read-only fixture check, no order action. WordPress user: 525, login qa_download_settings_second_20260917, role customer (existing fixture).
Exact route/browser context: http://localhost:10003/my-account/?as_portal_probe=orders-restored; isolated agent-browser portal-probe-customer session authenticated using actual ArraySubs Login as User. Core development 2.0.4.1 / WC 11.0.1, Pro inactive; request-scoped probe preloads unmodified published 2.0.4 MyAccountHooks and templates. Temporary runtime probe removed after investigation; preserved source in artifact directory.
Reproduction: (1) At woocommerce_account_menu_items priority 5, remove orders. (2) Core addMenuItems executes at priority 10. (3) At priority 20, restore Orders after Dashboard. (4) Load My Account. Repeated successfully.
Expected: core Subscriptions menu remains available when Orders is removed/reordered independently. Actual: Dashboard, Orders, Downloads, Addresses, Payment methods, Account details, Support, Log out; Subscriptions missing even with an active subscription.
Concrete proof: MyAccountHooks::addMenuItems inserts subscriptions only inside if ($key === orders). Browser screenshots orders-restored-no-subscriptions.png and menu-missing-endpoint-still-works.png. Both 2.0.0 and 2.0.4 releases have the same code; current source also contains the condition at arraysubs/src/Features/CustomerPortal/Services/MyAccountHooks.php:184.
Scope/counterexample: direct http://localhost:10003/my-account/subscriptions/?as_portal_probe=orders-restored still renders active subscription 28240 correctly. Consequently this is not a confirmed complete explanation of Paddle Compass incident. Without these simulated menu filters, both released portal versions show Subscriptions and list normally. Removing Orders via a blank WC endpoint can expose the same insertion dependence.
Proposed fix plan: provide a stable fallback insertion when Orders is absent, preserving normal placement after Orders and respecting intentional later menu-editor visibility. Test missing Orders, ordinary menu and intentional Pro hiding. No code fix performed; investigation-only scope.
Evidence/report: qa/artifacts/portal-investigation-20260918/report.md; screenshots and portal-probe.php in same directory. Repro video N/A: static menu state visible on page load; screenshots inspected.

Implementation plan approved by user: always insert the default Subscriptions item. Use Orders only to choose placement; if absent, insert before Log out, or append when neither anchor exists. Keep priority 10 so intentional later Pro visibility remains effective. Verify the ordinary menu, Orders removed/restored, direct portal and Pro hiding with real local browser checks; preserve saved settings.

Fixed 2026-09-18: core addMenuItems now always inserts Subscriptions; Orders selects placement only, otherwise before Log out or at the end. Browser PASS on http://localhost:10003 with customer 525 / qa_download_settings_second_20260917 / customer role, active subscription 28240, order N/A. Verified normal menu, priority-5 Orders removal, priority-20 Orders restoration, neither Orders nor Log out, and intentional Pro editor hiding. Portal rendered the active subscription in all direct-URL menu cases. Actual Pro hooks were loaded only for a request-scoped fixture; full Pro remained inactive. Temporary MU fixture removed, impersonation exited, browser closed, settings/menu/rewrite hashes unchanged. Screenshots inspected; proof and exact routes: qa/artifacts/portal-investigation-20260918/menu-fix/verification.md. No lint/PHPCS or customer-site change. This closes the reproduced navigation defect, not customer incident 36.

Deeper browser regression 2026-09-18: all MA-01–MA-11 phases passed under qa/my-account-menu-2026-09-18/plan.md (scheduled day 2026-09-18). Same customer 525 / qa_download_settings_second_20260917 / customer role and active subscription 28240; order N/A. Actual editor drags, disabling/enabling Orders and Subscriptions, master customization toggle, repeated saves/reopens, and actual Plugins-screen Pro activation/deactivation were tested. Real WooCommerce Advanced settings with an empty Orders endpoint reproduced absent Orders; fixed Free still showed Subscriptions before Log out and rendered /my-account/subscriptions/ and /my-account/view-subscription/28240/. Pro with the blank endpoint also worked. Permalink save preserved both routes. Pro's intentional disabled Subscriptions setting still hid/redirected the list. No request-only fixtures or direct option writes in this cycle. Exact original menu, master toggle, endpoint, Pro state and permalink structure restored through UI; read-only baseline comparisons all match, including subscription status and full-meta SHA-256. Final screenshots 35–37, comprehensive evidence and counterexamples: qa/my-account-menu-2026-09-18/report.md. Customer incident 36 remains unconfirmed.
