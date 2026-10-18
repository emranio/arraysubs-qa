---
id: 44
title: New admin subscriptions silently discard the different-renewal-price toggle
status: closed
priority: high
created: 2026-10-18T13:19:30.932842+06:00
updated: 2026-10-18T13:29:01.260678+06:00
started: 2026-10-18T13:24:21.406795+06:00
completed: 2026-10-18T13:29:01.260678+06:00
tags:
    - bug
    - subscription-admin
    - rest
    - renewal-price
class: standard
---

## QA linkage and verification level

- Active QA task ID: N/A.
- Scheduled day: N/A.
- Active plan markdown path: N/A; no active QA cycle.
- Investigated 2026-10-18 against ArraySubs main 525a2b6 and Pro main 95c3e25.
- Source-confirmed contract defect. Fresh end-to-end browser reproduction has not been run.

## Affected records and context

- Subscription ID(s): N/A; none created or changed.
- Order ID(s): N/A.
- WordPress user/customer ID, login/email, and role: N/A; no account used. Required reproduction actor: authenticated administrator with subscription-management permission.
- Exact local admin route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/form
- Save endpoint: POST /wp-json/wp/v2/arraysubs_data; edit endpoint counterexample: POST /wp-json/arraysubs/v1/subscriptions/{id}/update.
- Browser/user context during this investigation: N/A; source inspection only.

## Reproduction to execute

1. Open Add Subscription as an authorized administrator and choose valid customer/product inputs.
2. Enable Different Renewal Price; enter a positive alternate price and payment threshold.
3. Save the new subscription, then reopen it using the edit screen.
4. Inspect the create response and stored _enable_different_renewal_price, _different_renewal_price, and _different_renewal_price_after metadata.

## Expected

The toggle, price, and threshold all persist, and the reopened form retains the enabled state.

## Actual and concrete proof

- SubscriptionForm.jsx:448-477 creates subscriptions through the native WP REST endpoint and sends meta._enable_different_renewal_price as yes/no alongside the price and threshold.
- SubscriptionCPT.php:505-523 registers _different_renewal_price and _different_renewal_price_after for REST but never registers _enable_different_renewal_price. A source-wide search finds no registration elsewhere in either plugin.
- Local WordPress wp-includes/rest-api/fields/class-wp-rest-meta-fields.php:142-150 saves only fields returned by get_registered_fields(); the unregistered toggle is ignored.
- SubscriptionController.php:551 reads the absent toggle as false. The UI can report a successful creation while reopening disabled; the renewal engine requires the toggle before applying the alternate price (OrderIntegration.php:2757-2761).
- These results are inferred from the complete create/read code path, not newly observed in a browser or database.

## Scope and counterexamples

- The defect is specifically the Add Subscription/native REST creation path. The custom edit endpoint explicitly writes the toggle at SubscriptionController.php:986-989.
- Storefront subscription creation writes this metadata through arraysubs_create_subscription() in subscription-helpers.php:203 and is not the same path.
- Renewal-price application is license gated. The persistence defect lives in core; Pro does not register the missing field.

## Fix direction

Register the toggle using the existing subscription meta registration and permission/sanitization conventions. Verify create -> reopen -> toggle off/on -> save, and ensure price/threshold survive. No product code was changed during this investigation.

Fix plan: register only the missing yes/no renewal-price toggle beside its existing price fields, preserving current admin permissions and sanitization patterns. Reproduce creation and compare edit behavior in the browser, then verify creation, reopen and toggle-off persistence after the change.

Fixed on 2026-10-18 in core: missing yes/no metadata registration added with admin capability checks. Real browser reproduced loss on pending subscription 12091 (admin/customer 1, no orders): price=29 and threshold=3 persisted while toggle was absent. After fix, browser-created pending subscription 12094 persisted yes/29/3 and its detail page displayed Different Renewal Price $29.00, Applies After 3 payments. Supplemental actual custom REST update regression toggled false -> no and true -> yes with HTTP 200. Current edit route uses SubscriptionEdit and does not expose this switch; no edit-UI switch claim is made. Evidence: qa/three-bug-fixes-2026-10-18/screenshots/44-before-create.png and 44-after-detail.png. Code merged locally; no frontend build or Pro change required.
