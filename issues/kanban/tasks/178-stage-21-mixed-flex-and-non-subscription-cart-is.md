---
id: 178
title: 'stage-21: Mixed flex and non-subscription cart is blocked'
status: closed
priority: high
created: 2026-07-08T01:32:40.533482+02:00
updated: 2026-07-08T08:02:00.835957952+02:00
started: 2026-07-08T07:57:32.067831259+02:00
completed: 2026-07-08T08:02:00.835957201+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
    - checkout
    - cart
class: standard
---

QA progress task: #196, Stage 21, task 09.
QA plan path: qa/stages/21-flexible-renewal-sync/09-exclusivity-and-gateway-gating.md

Affected subscription/order IDs: N/A - blocked before order placement.
Affected WordPress user/customer IDs: N/A - fresh guest checkout sessions qa-stage21-196-mixed and qa-stage21-196-mixed2; no order/customer was created.

Exact test URL/admin route: customer checkout https://mirror-help.arrayhash.com/checkout/ after adding FRS Monthly 30 (#8648) and Members Tee (#692). Browser context: agent-browser sessions qa-stage21-196-mixed and qa-stage21-196-mixed2.

Reproduction steps:
1. Configure FRS Monthly 30 (#8648) as a clean flexible-sync monthly subscription in segment 2.
2. Attempt a mixed cart with non-subscription product Members Tee (#692) plus FRS Monthly 30.
3. Try both add orders: non-subscription first then subscription, and subscription first then non-subscription.
4. Observe checkout notices and order summary.

Expected result: Stage 21 Task 09 Sub-task 9.6 expects a flex subscription plus a non-subscription product in the cart. Only the subscription line should be prorated, the simple product should stay normal, totals should add up, and checkout should proceed via Direct bank transfer.

Actual result: Mixed checkout is blocked. Adding the non-subscription product after the flex subscription shows: "Mixed carts are disabled for subscriptions. Remove either the subscription product or the regular product before continuing." The order summary contains only FRS Monthly 30 at $22.26 and never shows Members Tee, so the mixed-total behavior cannot be verified.

Concrete proof:
- Screenshot: qa/artifacts/stage-21-task-196-mixed-cart-attempt2.png.
- Browser summary after the second attempt contained the mixed-cart-disabled notice and only the FRS Monthly 30 subscription line.
- Current settings are inconsistent: `arraysubs_settings.cart.allow_mixed_cart=true`, but `arraysubs_settings.multiple_subscriptions.allow_mixed_cart=false`, matching the runtime block.

Known scope notes/counterexamples: Normal checkout with Members Tee alone works and exposes normal product payment methods. Flex-only checkout for FRS Monthly 30 works and exposes Stripe, Direct bank transfer, and cheque while hiding Paddle/COD. This issue is specifically the Stage 21 mixed cart cross-check being blocked by the mixed-cart runtime setting/path.

[[2026-07-08]] Fix applied + state repair:
- Root cause: runtime reads multiple_subscriptions.allow_mixed_cart (was false); the DB also carried an orphan legacy group cart.allow_mixed_cart=true that NOTHING in the codebase reads — that inconsistency is what the QA run flagged. Code fix in arraysubs/src/functions/settings-helpers.php::arraysubs_get_settings(): legacy cart.allow_mixed_cart / cart.allow_multiple_subscriptions keys are now migrated into multiple_subscriptions.* when the modern key was never saved, and the legacy keys are dropped, so a stale group can never contradict runtime behavior again.
- Site state repaired to the Stage 21 plan intent: multiple_subscriptions.allow_mixed_cart=true, orphan cart group removed.
Verified live (Store API):
- Cart FRS Monthly 30 (#8648) then Members Tee (#692): both lines accepted, FRS $30.00 + Tee $20.00 = total $50.00.
- BACS checkout placed order #9103 (on-hold -> processing). Item #563 FRS carries _renewal_sync_* meta incl. cycle_start 2026-06-30 18:00:00; item #564 Members Tee stays normal ($20, no sync meta). Subscription #9121 created and active, next payment 2026-07-31 18:00:00 UTC (synced boundary), parent order 9103.
- Note: reverse add order (simple first, then subscription) replaces the cart with only the subscription. That is the One-Click Checkout feature (checkout.one_click_mode=subscription_items is enabled on this store — subscription add-to-cart intentionally replaces the cart and goes straight to checkout). Not a mixed-cart block; set one_click_mode=disabled if Stage 21 needs simple-first add order.
