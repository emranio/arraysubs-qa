---
id: 168
title: Admin pages need one centered WordPress loading spinner
status: closed
priority: medium
created: 2026-11-16T23:05:31.613154+06:00
updated: 2026-11-16T23:14:53.163075+06:00
started: 2026-11-16T23:14:53.163075+06:00
completed: 2026-11-16T23:14:53.163075+06:00
tags:
    - admin
    - loading
    - free
    - pro
class: standard
---

Active QA task and scheduled day: N/A (targeted user-requested bug fix on 2026-11-16 Asia/Dhaka; no new QA cycle). Plan: replace page skeleton/text states in both plugins with shared core PageLoader, cover lazy-route transitions, build both assets, verify actual local admin routes with delayed real responses and desktop/mobile layouts. Subscription/order IDs: N/A. Customer IDs: requested manage-members/3309; confirm locally before testing detail. Browser context: isolated loaders-admin session, authenticated WordPress admin, http://localhost:10013. Reproduction: open admin.php?page=arraysubs-mainadmin#/store-credit/settings, delay real fetch responses by 4 seconds, click Credit History. Expected: visible native WordPress spinner centered in content viewport without visible text. Actual: blank large content area during request, no spinner. Proof: /tmp/arraysubs-loader-history-before.png. Scope: user reports manage-members/3309, store-credit/history, store-credit/settings, cancellation-flow, members-access and renewal-flow/renewal-failures; inspect all admin page-loading states in both plugins. Save/action progress remains action-local. Admin user ID/login/role: to confirm from local runtime before closing.

Completed 2026-11-16 Asia/Dhaka. Active issue/task #168; targeted fix verification, no execution-plan schedule (N/A). Root plan and results: qa/artifacts/admin-loaders-2026-11-16/report.md; Pro detail: pro-report.md beside it. Operator: WP ID1, slug admin, roles administrator and demo_member. Successful local member fixture: WP customer484, login rqa2112_customer_a, email rqa2112-a@example.invalid, customer/demo_member labels. Requested member3309 returns HTTP404, success:false, User not found; exact proof local-member3309-and-role.json. Affected subscription/order IDs: N/A (presentation defect; no records modified). Shared native WP PageLoader now covers Free/Pro page code/data, skeleton/text states, lists, reports, refreshes/pagination; screen-reader-only status and body portal keep the same fixed position. Data-navigation buttons remain disabled with ordinary labels; save/mutation progress retained. Final Free-only 5-route cold-load plus refresh verified runtime without Pro, and broader Pro/main routes passed with delayed real responses. Desktop center800,516 at1440x1000; collapsed menu468,466 at900x900; mobile195,445 at390x844; native20px spinner. Slow18s code load kept spinner beyond16s and cleared. Existing member484 success, missing-record cleanup, actual server/network error recovery, and report search preservation verified. Both production webpack builds passed; all modified sources below3000 lines (max1032), drafts preserved/updated. Known boundaries: cached audit/server-rendered email pages may resolve instantly without a data wait; native WooCommerce-owned indicators excluded; mirror site not deployed. Temporary request-scoped MU filter removed, own browser sessions closed, no global activation/data changes. No remaining loader blocker.
