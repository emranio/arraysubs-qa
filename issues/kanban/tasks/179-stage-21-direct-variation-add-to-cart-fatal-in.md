---
id: 179
title: 'stage-21: Direct variation add-to-cart fatal in checkout'
status: closed
priority: critical
created: 2026-07-08T01:39:41.463319677+02:00
updated: 2026-07-08T08:02:01.057850095+02:00
started: 2026-07-08T07:23:08.889508914+02:00
completed: 2026-07-08T08:02:01.057849284+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
    - checkout
    - variation
class: standard
---

QA progress task: #197, Stage 21 — Flexible Renewal Sync, Task 10. Plan: qa/stages/21-flexible-renewal-sync/10-variation-checkout.md.

Affected subscription ID(s) and order ID(s): N/A. The checkout did not reach cart/order creation.

Affected WordPress user/customer ID(s), login/email, and role(s): N/A. Guest browser context before checkout account creation.

Exact test URL/admin route: https://mirror-help.arrayhash.com/checkout/?add-to-cart=8654

Browser/user context: agent-browser session qa-stage21-197-silver-manual, guest/direct checkout URL. Product FRS Variable #8652, Silver variation #8654.

Reproduction steps:
1. Open https://mirror-help.arrayhash.com/checkout/?add-to-cart=8654 in a fresh browser session.
2. Wait for the page to load.

Expected result: Direct add-to-cart for a variation should either add the variation cleanly when enough variation data is present, or reject/redirect with a normal WooCommerce validation notice. It must not fatal.

Actual result: WordPress rendered the critical-error page and no cart/order/subscription was created.

Concrete proof:
- Screenshot: qa/artifacts/stage-21-task-197-direct-variation-fatal.png
- debug.log lines 1703-1718: PHP Fatal error: Uncaught TypeError: ArraySubs\Features\SubscriptionCheckout\Services\Hooks::validateAddToCart(): Argument #4 ($variation_id) must be of type int, string given, called by WooCommerce variable add-to-cart handler and defined in arraysubs/src/Features/SubscriptionCheckout/Services/Traits/CartValidationTrait.php:32.

Known scope notes/counterexamples: The normal product page variation selector did not show console errors while switching between Silver and Gold. This failure was observed only on the direct checkout URL with add-to-cart=8654 before continuing the intended product-page checkout path.

[[2026-07-08]] Fix applied: root cause was strict `int` typehints on `validateAddToCart()` in arraysubs CartValidationTrait (and MembersAccess EcommerceRestriction). WooCommerce's variable add-to-cart handler passes `$variation_id` as empty string when the request has no variation_id param (direct ?add-to-cart=<variation-id> URL), causing Uncaught TypeError. Fix: loosened signatures to untyped params with absint()/bool casts inside. Files: arraysubs/src/Features/SubscriptionCheckout/Services/Traits/CartValidationTrait.php, arraysubs/src/Features/MembersAccess/Services/EcommerceRestriction.php. Verified: https://mirror-help.arrayhash.com/checkout/?add-to-cart=8654 now returns 200, "FRS Variable – Silver has been added to your cart", checkout renders, debug.log unchanged (1719 lines).
