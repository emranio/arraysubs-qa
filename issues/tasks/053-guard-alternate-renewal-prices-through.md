---
id: 53
title: Guard alternate renewal prices through subscription billing constraints
status: closed
priority: high
created: 2026-10-18T17:26:25.814625+06:00
updated: 2026-10-18T17:31:10.079521+06:00
started: 2026-10-18T17:26:28.00922+06:00
completed: 2026-10-18T17:31:00.957611+06:00
tags:
    - bug
    - developer-api
    - billing
    - regression
class: standard
---

Active QA task: Task 7. Scheduled day: 2026-10-18 (Asia/Dhaka).
Plan: qa/developer-support-2026-10-18/plan.md.

Affected subscription/order IDs: N/A in initial read-only reproduction (synthetic, nonpersisted WP_Post ID 991991991); the real-fixture regression will record its temporary IDs before cleanup.
Affected WordPress customer: N/A in initial reproduction. Actor: trusted server-side PHP after wp_loaded, through WP-CLI with --allow-root; no logged-in customer required.
Exact site/route: http://localhost:10013/. Server API: ArraySubs\Developer\Subscriptions::update(); shared implementation: SubscriptionAdmin\Services\SubscriptionMutation::validateUpdates(). Browser context: N/A; this guard is a trusted PHP and admin REST mutation contract, not a separate customer-facing screen.

Reproduction:
1. Resolve an active subscription whose gateway owns its billing clock.
2. Update recurring_amount to 99: provider_rejection.
3. Instead update different_renewal_price to 99, different_renewal_price_after to 2, or enable_different_renewal_price to true.
Expected: each renewal-price field follows the same parent/provider/settlement/pending-switch/frozen-invoice guards as recurring_amount, without changing subscription metadata.
Actual: all three alternate renewal-price fields pass validation because they are absent from the guarded terms list. OrderCreation uses the alternate price and threshold to determine renewal charges.
Proof: read-only WP-CLI Reflection invocation of the actual validator, with an arraysubs_gateway_owns_billing_clock filter for synthetic ID 991991991, returned provider_rejection for recurring_amount=99 and true for all three alternate fields. No database fixture was changed by this initial reproduction.
Known scope/counterexamples: ordinary recurring_amount, quantity and billing-period changes are already guarded. This is a missing field classification in the shared update path; no new lock or billing system is needed.

Fix plan:
1. Add the three alternate renewal-price fields to the existing guarded terms comparison.
2. Add a separate real WP/WooCommerce fixture regression for all three fields across provider, child, settlement, pending-switch and frozen-invoice states, including unchanged metadata/event counts and valid unguarded edits.
3. Reproduce before patch, rerun with Pro and core only, clean all fixture posts/orders/users/schedules, update the existing changelog validation entry, then close this issue. Do not edit the main subscription/order matrix or run phpcs.

[[2026-10-18]] Sun 17:27
Real-fixture reproduction before the patch: all 21 guarded cases failed, because every alternate renewal-price update succeeded; the 10 control/cleanup checks passed. Affected temporary subscription IDs: provider 16393, box 16399, bundle 16405, external 16411, settlement 16417, pending 16423, frozen 16429. Order 16432. Customer 344, login alternate-price-fb0dfda2, email alternate-price-fb0dfda2@example.test, role customer. Fixture cleanup verified true. Evidence: qa/developer-support-2026-10-18/subscription-alternate-renewal-price-before.txt. The test exercises the public Subscriptions::update method and real WP/WooCommerce stores; provider/settlement states enter through their existing filters. No official Stripe browser claim. A test-harness cleanup-method typo in an earlier initial run was corrected and its own 9 fixtures were explicitly removed before this clean reproduction; no pre-existing fixture was changed.

[[2026-10-18]] Sun 17:31
Fixed and verified 2026-10-18: the existing guarded terms list now includes enable_different_renewal_price, different_renewal_price and different_renewal_price_after. No new service, lock or provider operation was added. All 31 targeted real-fixture checks pass with Pro active and all 31 pass core-only: provider ownership, box/bundle child, external billing, gateway settlement, pending switch, frozen renewal, unchanged-value controls, normal local update and cleanup. Every blocked combined request preserves all metadata and emits no terms event. Root/credits also reran the unchanged main subscription/order matrix: 77/77 in each mode. Evidence: subscription-alternate-renewal-price-pro.txt and subscription-alternate-renewal-price-core.txt under qa/developer-support-2026-10-18/.

Real browser retest: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/edit/17568, isolated agent-browser session alternate-renewal-price authenticated as existing admin user 1 using one temporary session token. Own fixture parent 17565, child 17568, product 17563; customer/actor user 1 admin (local existing administrator, no account fields changed), no linked orders. The limited edit UI does not render alternate-price controls, so authenticated browser fetches exercised the existing admin REST route /wp-json/arraysubs/v1/subscriptions/17568/update: each of the three alternate-price fields returned HTTP 409 parent_billed_subscription; a browser GET confirmed price and contact fields unchanged. Then the real form's Invoice Email was filled with browser-price-guard@example.test and Save Changes clicked: observer recorded disabled Saving… then enabled Save Changes, redirected to the detail view, and the saved invoice email was visible. Screenshots were captured and visually inspected; no UI/layout source was changed. Evidence: alternate-renewal-price-browser-http.json, alternate-renewal-price-browser-ui.json, alternate-renewal-price-browser-edit.png, alternate-renewal-price-browser-saved.png.

Cleanup verified: only own parent/child/product removed and schedules unscheduled, original admin account retained, only the newly created admin session token revoked, isolated browser closed, all temporary setup/auth/token files removed. Proof: alternate-renewal-price-browser-cleanup.json (all boolean checks true). No external gateway was contacted; this does not certify official Stripe browser behavior. Existing core changelog validation entry was extended instead of adding a duplicate. Main subscription/order matrix source and all Pro source remain untouched by this fix.
