---
id: 85
title: Member details backend remains in Pro although the page should work in Free
status: closed
priority: medium
created: 2026-10-23T01:24:15.787824+06:00
updated: 2026-10-23T02:16:22.676429+06:00
started: 2026-10-23T01:58:09.62569+06:00
completed: 2026-10-23T02:15:23.540838+06:00
tags:
    - bug
    - admin
    - subscriptions
    - free-pro
    - customer-report
class: standard
---

Active QA task ID / scheduled day / plan: N/A; targeted investigation of Tec-Logic's 22 October 2026 email, requested 23 October 2026. No active QA cycle or execution schedule.

Affected subscription/order IDs: reporter subscription #4372; related order ID N/A (not supplied). Staging comparison subscription #97430, initial order #97429, renewal order #97541.

Affected WordPress users/customers: reporter customer AGUSTIN GORDILLO; user ID, login, email and role N/A (not supplied; emails are redacted in the screenshots). Reporter admin toolbar shows Ecommunicator; user ID and role N/A. Staging customer ID 2595, login sqa-fix81-c1, email sqa-fix81-c1@example.com, Customer role. Staging browser account admin, Administrator; admin user ID N/A (not inspected).

Exact URLs / contexts: reporter site domain and full routes N/A (cropped screenshots); visible flow is Subscriptions -> subscription #4372 -> Customer Information name. Expected React route from current source: /wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/4372 -> #/manage-members/{customer_id}. Reporter states in the email conversation that they use the free version. Isolated staging agent-browser session customer-link-investigation, authenticated as the documented QA admin: https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/97430 -> https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/manage-members/2595.

Reproduction steps for a Free-only installation (source-supported; a Free-only browser run remains to be performed):
1. Use ArraySubs Free with WooCommerce and an existing subscription assigned to a registered customer; leave Pro inactive/absent.
2. Open the subscription details page.
3. Click the customer name under Customer Information.
4. Observe the member details request and the error toast.

Expected (product scope clarified by the user): the member details page itself works in ArraySubs Free. Free can search members and view/edit permitted core profile data, subscriptions, purchases and ordinary customer statistics. Pro augments that working core screen with premium data such as Store Credit and Feature Manager integrations.

Reported actual result: customer data does not load; client_details_error.jpg displays “Error al cargar los datos del miembro” (Failed to load member data). The screenshot shows the search field but no populated member profile. Current code routes the Free detail link to the premium page unconditionally.

Concrete source proof:
- arraysubs/src/resources/pages/SubscriptionDetail.jsx:1046 hardcodes /manage-members/${data.customer.id}, with no live Member Insight availability check or WooCommerce fallback.
- arraysubs/src/resources/Main.jsx:537 registers /manage-members/:userId? directly, without a ModuleGate or availability guard.
- arraysubs/src/resources/pages/MembersAccess/ManageMembers.jsx:271 requests arraysubs/v1/members/${numericId}; lines 275-276 and 290-291 show the same generic failure for non-2xx responses. The underlying server error/status is hidden from the user.
- The member search/details REST routes are defined only in arraysubspro/src/Features/MemberInsight/REST/MemberController.php:55,71. Its Provider loads the REST directory, and arraysubspro/src/Boot.php:54 supplies that Provider. There is no matching core member endpoint. Without the Pro provider, WordPress is expected to return 404 rest_no_route; that response was not captured on the reporter's site and must not be represented as an observed customer-site result.
- Core already implements the desired Free fallback in arraysubs/src/Features/SubscriptionAdmin/Services/Hooks.php:96-114: a WooCommerce Customers URL filtered through arraysubs_subscription_admin_customer_link. Pro overrides that filter in arraysubspro/src/Features/MemberInsight/Services/Hooks.php:35,49-62. The detail page bypasses this existing system.

Browser counterexample verified on staging: open the subscription list, click #97430, then click Sqa sqa-fix81-c1 under Customer Information. Member #2595 renders with its name, Customer role and subscription #97430; browser errors are empty. Network: GET https://mirror-help.arrayhash.com/wp-json/arraysubs/v1/members/2595 (Fetch) 200. Read-only runtime inspection confirms MemberInsight is present in window.arraySubs.liveBootClasses. This verifies the working Pro path, not the failing Free-only path. No site modules, plugin activation state, customer data, subscription data or payment settings were changed.

Evidence files (plugins workspace relative):
- qa/artifacts/customer-link-investigation-2026-10-23/reporter-subscriptions.jpg
- qa/artifacts/customer-link-investigation-2026-10-23/reporter-subscription-details.jpg
- qa/artifacts/customer-link-investigation-2026-10-23/reporter-client-details-error.jpg
- qa/artifacts/customer-link-investigation-2026-10-23/pro-subscription-detail.png
- qa/artifacts/customer-link-investigation-2026-10-23/pro-member-details.png
- qa/artifacts/customer-link-investigation-2026-10-23/pro-member-request.txt

Known scope / limitations: the missing Pro backend is the strongest explanation for this Free user's report, but the reporter's HTTP response and installed version have not been inspected. A permission/nonce failure, missing user or server error also maps to the generic toast. Existing closed issue #34 repaired the old /customers/{id} link by pointing it at /manage-members/{id}; it verified the Pro-capable path without handling Free. Preserve #34 as closed historical evidence; this issue tracks the remaining Free/Pro boundary defect. Local WP-CLI cannot bootstrap because the local database connection is denied; staging comparison used the real browser.

Revised proposed fix plan (not implemented): make the member details feature available in Free as clarified by the user. Move the shared member search/read/update REST controller and ordinary profile, subscription, purchase and statistics retrieval into core, register it during core boot, and make core menu visibility and customer shortcuts follow that ownership. Keep premium Store Credit data and Feature Manager integrations in Pro through existing or appropriately scoped core extension points, preserving capability checks and visible loading/error behavior. Review Pro MemberInsight shortcut hooks to avoid duplicate providers/routes after the move. Update readme/product documentation that currently labels the entire Member Insight feature Pro. Verify complete Free-only and Pro browser flows, including search, profile view/edit permissions, customer shortcuts and optional premium sections. The earlier WooCommerce-fallback/Pro-route-guard recommendation was based on current code ownership and does not satisfy the clarified Free feature scope. This remains investigation only; no product source/build/changelog edits were made.

[[2026-10-23]] Fri 01:58
Implementation plan: move member REST/search/profile editing and common admin shortcuts into core; keep Store Credit enrichment in Pro using core filters; align boot/menu/docs and assets; audit all Free-visible callers of Pro endpoints; verify Free/Pro and permission failures through browser plus targeted regression checks. Preserve existing unrelated changes.

[[2026-10-23]] Fri 02:15
Resolution (2026-10-23): core now owns member search/details/profile edits and common subscription/order/user/WooCommerce customer shortcuts. Pro uses core response/shortcut filters for Store Credit, with no duplicate member REST controller. Manage Members is a core always-on module and its Reports/readme labels are Free. Fixed member totals to use the shared effective recurring amount and stored currency; paid subscription #8326 now displays 349 USD. Customer report shortcuts use native row-actions visible styling for keyboard access. Both canonical drafts updated with existing entries preserved. Source changes integrated on local main in core commits 5678c87 + 383e612 and Pro commits eb85d9c + 155b7bc; no push/release/remote deployment.

Verification: Free-only browser reproduced the original error twice before the fix (#8326 -> customer #224, member GET 404). After the fix, member navigation/search/route changes/profile save with loading feedback/Reports/list and native user shortcuts passed. Pro comparison retains Store Credit/Feature Manager links. Four Free-only premium direct routes (Store Credit, Checkout Builder, Feature Log, Cancellation Flow) show the expected Pro lock with zero new Fetch requests. WC customer links are unique, ordinary mouse navigation works, and Tab/Enter navigation works with a visible focus ring. All 20 real REST checks passed in each Free and Pro configuration, including admin/customer/anonymous/shop-manager permission boundaries, invalid writes, one core route owner, paid totals/currency and request-local disabled credit behavior. Disposable managers #381/#382 deleted. Existing customer profile/address values preserved; normal WooCommerce save bookkeeping noted. Pro activation and saved modules restored exactly. Local WP-CLI access was resolved using Local PHP/php.ini rather than changing database settings.

Scope/evidence: targeted local test site http://localhost:10013, admin context, existing subscription #8326/customer #224 Finley Sullivan (demo260927_220, demo.260927.220@example.test, customer/demo_member/demo_premium), search customer #223 Rowan, and WC customer #144 Finley Parker. No active full QA plan or scheduled day; this was targeted issue #85 verification on 2026-10-23. Full report and artifacts: qa/member-details-free-2026-10-23/report.md, before/report.md, after/report.md, rest-free.json, rest-pro.json, free-pro-boundary-audit.md. Broader source audit found no other independent unguarded core caller of an absent Pro endpoint. Existing notes/feature-log/credit/analytics member destinations are covered by the move. Both production builds passed; all touched product source files stay below 3000 lines. Unrelated workspace work preserved.

[[2026-10-23]] Fri 02:16
Final fixture clarification: local subscription #8326 is linked to order #8327; search customer #223 has subscription #8323, and WC shortcut customer #144 has subscription #7974. Final browser report confirms the ordinary and keyboard shortcut checks passed with visible links and no browser JavaScript errors. Both temporary capability-test users were confirmed deleted after the REST runs.
