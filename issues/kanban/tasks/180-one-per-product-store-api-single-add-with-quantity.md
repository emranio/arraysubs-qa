---
id: 180
title: 'one-per-product: Store API single add with quantity >1 bypasses limit'
status: closed
priority: medium
created: 2026-07-08T10:59:31.18038882+02:00
updated: 2026-07-08T11:06:03.97943068+02:00
started: 2026-07-08T11:06:03.979429729+02:00
completed: 2026-07-08T11:06:03.979429729+02:00
tags:
    - qa
    - cart
    - store-api
    - multiple-subscriptions
class: standard
---

Found during settings audit on 2026-07-08 (post fix-round for issues #172-#179). Pre-existing behavior, NOT a regression from that fix round — the involved code paths were not modified.

Setting under test: arraysubs_settings.multiple_subscriptions.one_per_product = true ("One Subscription per Product").

Affected subscription/order IDs: N/A — cart-level only, no order placed.
Affected WordPress user/customer: N/A — fresh guest Store API session.

Exact test URL/route: Store API POST https://mirror-help.arrayhash.com/wp-json/wc/store/v1/cart/add-item with body {"id":8648,"quantity":3} (FRS Monthly 30). WP-CLI used only to toggle the setting.

Reproduction steps:
1. Set multiple_subscriptions.one_per_product = true.
2. Fresh guest session, Store API add-item product 8648 with quantity 3 in a single request.
3. Observe cart contents.
4. Then POST add-item for the same product again with quantity 1.

Expected result: With One Subscription per Product enabled, each subscription product should appear at most once per order with quantity 1 — the first add should be clamped/rejected to quantity 1.

Actual result: Step 2 succeeds with quantity 3 in the cart. Step 4 is correctly blocked ("One Subscription per Product is enabled. Each subscription product can appear only once per order..."), so the rule only bites on the SECOND add, not on an initial multi-quantity add.

Root cause analysis (two contributing gaps):
1. arraysubs/src/Features/SubscriptionCheckout/Services/Traits/CartValidationTrait.php::validateSingleProductQuantity() deliberately returns pass-through when existing_quantity === 0 && quantity > 1 (first add of the product), so a single qty-3 add is never rejected.
2. The classic-checkout clamp limitAddToCartSubscriptionQuantity() hooks woocommerce_add_to_cart_quantity, but WooCommerce's Store API CartController never applies that filter, so Block Checkout / Store API requests bypass the clamp entirely. enforceStoreApiSingleProductQuantity() covers quantity UPDATES (internal_woocommerce_cart_item_updated_from_user_request) but not the initial add-item request.

Counterexamples / working paths: second add of the same product is blocked on both classic and Store API; quantity-update to >1 via Store API is reset by enforceStoreApiSingleProductQuantity; classic add-to-cart form clamps to 1 via the woocommerce_add_to_cart_quantity filter.

Suggested fix direction: drop the first-add exemption in validateSingleProductQuantity (reject or clamp when quantity > 1 and one_per_product is on), or hook woocommerce_store_api_cart_item_added / the Store API add flow to clamp quantity to 1 like enforceStoreApiSingleProductQuantity does for updates.

Site state after test: one_per_product restored to false (audit left all settings as found).

[[2026-07-08]] Fix applied (clamp-everywhere design, arraysubs):
- New CartValidationTrait::enforceSingleProductQuantityOnAdd() hooked on woocommerce_add_to_cart (priority 30): when one_per_product is enabled and a subscription line lands with quantity > 1, the line is clamped to 1, the arraysubs_single_product_quantity_error session flag is set (surfaced by CartValidation cart-error display), and the standard notice is added. Covers the Store API add-item route, which never applies the classic woocommerce_add_to_cart_quantity clamp filter.
- validateSingleProductQuantity() keeps its first-add pass-through intentionally (now documented): rejecting at handler-level validation runs BEFORE WC_Cart::add_to_cart's clamp filter and would leave a fresh guest with an empty cart and a lost notice (session not yet established). An interim reject-based fix was tried and reverted for exactly that reason.
- Hook registration: arraysubs/src/Features/SubscriptionCheckout/Services/Hooks.php.
Verified live:
- one_per_product=ON, Store API add-item {id:8648, quantity:3} -> cart holds quantity 1.
- one_per_product=ON, second add of same product -> blocked with the one-per-product notice.
- one_per_product=ON, classic /checkout/?add-to-cart=8648&quantity=3 -> session created, cart holds quantity 1.
- one_per_product=OFF, Store API quantity 3 -> quantity 3 lands (no regression).
- Existing coverage untouched: Store API quantity-update reset (enforceStoreApiSingleProductQuantity) and cart-update validation still in place.
Site state restored: one_per_product=false (as found). debug.log clean.
