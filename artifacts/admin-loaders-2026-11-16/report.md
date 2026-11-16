# Unified admin loading verification

Date: 2026-11-16 (Asia/Dhaka)
Issue: `qa/issues/tasks/168-admin-pages-need-one-centered-wordpress-loading.md`
Site: `http://localhost:10013`, using the user's local auto-login route.
Scope: Free and Pro admin page/code/data loading, refreshes and pagination. This is targeted implementation verification, not a new release QA cycle.

## Implementation

- Reused core `PageLoader` everywhere for native WordPress `.spinner.is-active` with screen-reader-only status text.
- Removed visible page-loading messages and skeleton placeholders across core pages, shared lists, member details, settings, builders, audits and dashboard sections, plus Pro-owned analytics.
- The loader portals to the document body and imports shared styling. This keeps transformed panels and independent sections at the same content-viewport center. Pro resolves the same core component.
- Route-code loading shares that position with data loading. Removed the old 15-second timeout so slow downloads keep their indicator until completion; chunk-load errors clear route-pending state.
- Data refresh/retry/pagination buttons retain ordinary labels and stay disabled while the central spinner is active. Save and mutation buttons retain their existing progress feedback.
- Direct member and credit-detail routes begin in loading state. AI report refreshes preserve mounted search/sort controls.
- Both changelog drafts were updated without changing release versions. Architecture documentation now records the shared loading convention.

## Browser method

Used isolated agent-browser sessions, snapshots/ref interactions, real local pages and screenshot inspection. Browser-only wrappers delayed delivery of original fetch responses by 2.5–12 seconds; no response payloads were fabricated. One real lazy script insertion was delayed 18 seconds to exercise the code-to-data transition beyond the former timeout. Temporary instrumentation was never added to plugin source.

Free-only verification used a temporary local MU filter scoped to one browser cookie; it excluded Pro only for those requests. The stored active-plugin option was unchanged. Runtime evidence confirms no ArraySubsPro boot classes and Overview `is_pro: false`.

## Results

| Coverage | Result | Evidence |
| --- | --- | --- |
| Reported Store Credit Settings, Credit History, Cancellation Flow and Member Access routes | Native centered spinner, no visible loading label, clears when real data arrives | Matching `store-credit-*`, `cancellation-flow`, `members-access` PNG/JSON |
| Manage Members detail | Existing local customer 484 loads successfully; requested ID3309 returns HTTP404 User not found locally and exercised failure cleanup | `manage-members-484-*`, `manage-members-3309-*` |
| Renewal Failures | Cold load in Free and forced Refresh use one center spinner; no duplicate Refreshing button spinner | `free-renewal-flow-renewal-failures-*`, `free-renewal-refresh-*`, `renewal-refresh-*` |
| Broader main-admin pages | Subscriptions, member/credit lists, Plan Switching, Content Gate, Checkout Builder, Profile Form, Modules, Webhook Audit and Overview settle without stuck loaders | Matching PNG/JSON files |
| Free without Pro | Subscriptions, Manage Members, Member Access, General Settings, Renewal Failures and Refresh passed | `free-runtime.json`, `free-*.json/png` |
| Pro analytics | Retention, Refunds, Churn, Forecast and Overview, including pagination, refiltering and retry recovery, passed | `pro-report.md`, `pro-*` evidence |
| Slow code download | Spinner remains at the same point after 16 seconds and clears after the 18-second delayed chunk and data complete | `lazy-route.json`, `lazy-route-16-seconds.png` |
| Responsive positioning | Desktop, automatically collapsed menu and phone layouts center correctly with no loader text | `auto-folded-settings-*`, `mobile-credit-settings-*`, `mobile-members-access-*` |
| Failure recovery | Missing detail records and real network/server errors clear the loader; Retention/Refunds retry returns real content | Free invalid-detail and Pro retry evidence |

Measured native spinner dimensions: 20 × 20 pixels. Center positions match the available viewport exactly:

- 1440 × 1000 desktop, 160px menu and 32px toolbar: `(800, 516)`.
- 900 × 900 automatically collapsed 36px menu: `(468, 466)`.
- 390 × 844 phone, 46px toolbar and no sidebar: `(195, 445)`.
- 1280 × 577 Free/Pro checks: `(720, 304.5)`.

Concurrent Pro sections may mount multiple native spinners at identical coordinates; screenshot inspection confirms a single visual indicator. Already cached audit data and server-rendered Email Settings require no data wait and display directly; forced audit Refresh verifies their network-loading path. The `folded-settings` attempt did not retain manual collapse, so collapsed-menu sign-off uses the successful `auto-folded-settings` case.

No uncaught JavaScript errors in final successful flows. Expected invalid-record errors and a transient Refunds HTTP503 were inspected separately; Refunds Retry completed HTTP200. Native WooCommerce-owned placeholders on its Overview screen are outside the ArraySubs changes.

## Build and source checks

Both production webpack builds passed using the existing installed dependencies:

- Core: webpack build `e984a797730f777ceb13`, main asset version `f9a3cd5db0fba39e1030`; `core-build.log`.
- Pro: webpack build `65523b16843c31f1f7b5`; `pro-build.log`.

All created/modified plugin source files were checked with `wc -l`; largest is 1,032 lines, below the 3,000-line limit. JSX parsing/builds and independent cross-plugin review passed. No Composer install, lint, PHPCS, release/version bump, push or staging deployment was performed. The rebuilt assets are available on the local site.

## Cleanup

Owned browser sessions are closed after verification; offline mode is restored. Temporary browser delay/auth state and the request-scoped MU fixture were removed. No subscription, order, credit, customer or global plugin activation state was changed. Current local operator: user ID1, slug admin, roles administrator and demo_member; exact member404 and role proof is in local-member3309-and-role.json.
