# Free / Pro boundary audit — 2026-10-23

## Scope and method

Related issue: `qa/issues/tasks/085-member-details-backend-remains-in-pro-although-the.md`.

This record covers a read-only source audit of shared admin routes, customer portal requests, supporting PHP hooks, and Free / Pro availability checks. It records source evidence; it is **not a browser test report** and does not claim comprehensive runtime coverage. Browser reproduction and verification for the Member Details fix are recorded separately in this QA directory by the implementing agent.

The audit compared UI requests and links with their owning providers and REST controllers, including the behavior when Pro is absent, unlicensed, or a switchable module is disabled. No independent missing-Pro route defect was confirmed beyond MemberInsight in the checked surfaces. Two related defects identified during this work—the Member Insights report's Pro badge and incorrect zero member order/spending totals—are being fixed by the lead agent; this audit does not independently mark those fixes as runtime verified.

## Admin routes and reports

| Checked surface | Source evidence and result |
| --- | --- |
| Store Credit management, history, and settings | `arraysubs/src/resources/Main.jsx` wraps these pages in `ModuleGate` for `store_credit`; endpoints belong to `arraysubspro/src/Features/StoreCredit/REST/`. |
| Feature Manager settings and subscription feature log | `arraysubs/src/resources/Main.jsx` wraps both routes in `ModuleGate` for `feature_manager`; `arraysubspro/src/Features/FeatureManager/REST/FeatureController.php` owns the feature endpoints. |
| Profile Fields and My Account editors | `arraysubs/src/resources/Main.jsx` gates both routes with `profile_builder`; controllers remain in `arraysubspro/src/Features/ProfileFields/REST/` and `arraysubspro/src/Features/MyAccountEditor/REST/`. |
| Cancellation Flow | `arraysubs/src/resources/Main.jsx` uses `ModuleGate` for `cancellation_flow`; routes belong to `arraysubspro/src/Features/CancellationFlow/REST/CancellationController.php`. |
| Activity Audits, renewal failures, portal-action failures, and scheduled-job logs | `arraysubs/src/resources/Main.jsx` gates these routes with `audits`; controllers belong to `arraysubspro/src/Features/Audits/REST/`. Access-Rule Conflicts is intentionally core and uses the core MembersAccess controller. |
| Checkout Builder | `arraysubs/src/resources/Main.jsx` uses `ModuleGate` for `checkout_builder`; controllers belong to `arraysubspro/src/Features/CheckoutBuilder/REST/`. |
| Concurrent-login controls | `arraysubs/src/resources/Main.jsx` renders Login Limit only when `hasLoadedFeature("MultiLoginPrevention")` succeeds, otherwise redirects to Member Access. |
| Reports directory | `arraysubs/src/resources/pages/Reports/reportCatalog.js` resolves report availability from loaded features and route/module state; `arraysubs/src/resources/pages/Reports.jsx` renders links only for available reports. MemberInsight's incorrect Pro designation is part of the current fix. |

`arraysubs/src/resources/components/ModuleGate.jsx` returns its unavailable/disabled message before rendering the child page, preventing that child from issuing requests to absent routes. `arraysubs/src/resources/libs/modules.js` reads the module availability and enabled flags published by the core registry. `arraysubs/src/Supports/Abstracts/AbstractLoader.php` suppresses ordinary REST/UI classes when their provider's switchable module is off. These are source-level safeguards, not evidence that every individual screen was browser-tested.

## Portal and shared services

| Checked surface | Source evidence and result |
| --- | --- |
| Core customer actions | `arraysubs/src/Features/CustomerPortal/REST/CustomerController.php`, `arraysubs/src/Features/SkipRenewal/REST/`, and `arraysubs/src/Features/PlanSwitching/REST/` own the portal's core cancellation, undo/reactivate/retry, address maintenance, skip/pause/resume, and plan-switch actions. `arraysubs/src/resources/customerPortal.js` and `arraysubs/src/Features/CustomerPortal/views/view-subscription.php` use those routes. |
| Customer shipping address | The portal uses core `/my-subscriptions/{id}/shipping-address` in `CustomerController.php`; the similarly named Pro admin shipping endpoint is a separate route and is not a missing dependency for the portal. |
| Retention offers | `arraysubs_is_retention_offers_enabled()` in `arraysubs/src/functions/settings-helpers.php` requires the live Pro CancellationFlow provider. The portal template and script use the retention flow only when its UI is present; absent Pro retains the core cancellation flow. |
| Customer feature labels | `arraysubs/src/Features/CustomerPortal/Services/SubscriptionCard.php` requires live FeatureManager before exposing feature labels. Feature types remain internal. |
| Overview | `arraysubs/src/resources/pages/Overview/useOverview.js` calls the core `/overview` endpoint in `arraysubs/src/Features/MainAdmin/REST/OverviewController.php`. Pro extends its payload through `arraysubs_overview_payload`; `arraysubspro/src/Features/OverviewWidgets/Support/WidgetAvailability.php` removes report links for inactive providers. |
| Settings and Easy Setup | Shared settings and setup endpoints belong to core. `arraysubs/src/Features/EasySetup/REST/SetupController.php` guards optional Pro sanitizers and class calls. Wizard feature availability and module checks are in that controller and `arraysubs/src/resources/pages/EasySetup/wizard/`; module switches intentionally remain selectable while their installed module is off. |
| Developer APIs | `arraysubs/src/Developer/Customer.php` already owns account, billing, shipping, subscriptions, and orders. Optional Profile Builder, Store Credit, and login-limit adapters return unavailable state/errors when their Pro implementation cannot run. Sources: `arraysubspro/src/Developer/CustomerProfile.php`, `arraysubs/src/Developer/StoreCredit.php`, and `arraysubs/src/Developer/LoginLimits.php`. |
| Optional bundle references in core Paddle integration | `arraysubs/src/Features/AutomaticPayments/Gateways/Paddle/Traits/PaddleCheckoutItemsTrait.php` and `PaddlePaymentProcessingTrait.php` guard Pro `BundleConfig` calls with `class_exists()`. |

## MemberInsight migration and entry points

The Member Details UI is a Free feature. Its shared search, details, and editing controller previously lived in Pro, while the core route could render without Pro. Moving this backend and shared shortcuts to core addresses the confirmed product boundary error. Store Credit data and actions remain a Pro extension.

The migration review included these entry points and dependencies:

- Core `arraysubs/src/resources/pages/MembersAccess/ManageMembers.jsx` and `/manage-members/:userId?` in `arraysubs/src/resources/Main.jsx`.
- Core subscription detail customer link and the subscription-list link filter in `arraysubs/src/Features/SubscriptionAdmin/Services/Hooks.php`.
- Member references generated by `arraysubs/src/functions/subscription-notes.php` for subscription notes and audit references.
- WooCommerce order edit and preview shortcuts, WordPress profile/user-edit heading button, and WooCommerce Customers shortcuts supplied by MemberInsight's services hook class. The common implementation is migrating from `arraysubspro/src/Features/MemberInsight/` to `arraysubs/src/Features/MemberInsight/`.
- Profile heading script and build entry, plus `arraysubs/src/resources/loginAsUserProfileBtn.js`, whose button placement depends on the Member Details button ID.
- WooCommerce customer shortcuts are being separated from Pro analytics subscription-status filters/columns in `arraysubspro/src/resources/analyticsCustomers.js`; Store Credit links remain conditional.
- Pro analytics member links in `arraysubspro/src/Features/Analytics/REST/OverviewController.php`.
- Manage Members menu availability in `arraysubs/src/Features/MainAdmin/Services/MenuConfig.php`, the report catalog, and Free/Pro claims in `arraysubs/readme.txt`.
- Existing `arraysubs_member_profile_data` extension hook consumed by core `arraysubs/src/Features/LoginAsUser/Services/AdminHooks.php`.
- Additional customer shipping-address metadata already read by core `arraysubs/src/Features/SubscriptionAdmin/REST/SubscriptionController.php`.

These source findings identify the paths requiring alignment and the safeguards already present. Final acceptance of the migration depends on the separate browser checks with Pro active and inactive and with relevant optional modules disabled.
