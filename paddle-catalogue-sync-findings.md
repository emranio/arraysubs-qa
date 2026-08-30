# Paddle Catalogue Sync — investigation (F-22)

**Raised by:** `qa/product-edit-regression-qa-report.md` §3, F-22 ("Paddle catalogue sync noise").
**Date:** 2026-08-30
**Scope:** why saving a subscription product talks to Paddle at all, why product 33324 produced a
second error notice, and what is still worth changing.
**Code read:** `arraysubs/src/Features/AutomaticPayments/Services/PaddleProductSyncHooks.php`,
`arraysubs/src/Features/AutomaticPayments/Gateways/Paddle/PaddleProductSync.php`,
`arraysubs/src/Features/AutomaticPayments/Gateways/Paddle/PaddleGateway.php`,
`arraysubs/src/Features/AutomaticPayments/Gateways/Paddle/Traits/PaddleProductsTrait.php`.

---

## 1. What actually happens on a product save

`PaddleProductSyncHooks::__construct()` registers

```php
add_action('woocommerce_process_product_meta', [$this, 'syncSimpleProduct'], 100, 1);
```

Priority 100 is deliberate: it runs after every metadata saver (core at 10, box/bundle/pro at 15,
Paddle tax category at 20), so it reads the finished product.

`syncSimpleProduct()` then requires **all** of the following before it does anything:

| Gate | Where |
|---|---|
| admin request, logged in, not an autosave | `isAuthenticatedAdminRequest()` |
| `$_POST['post_ID']` matches, valid `woocommerce_meta_nonce`, `edit_post` capability | `isAuthorizedSimpleSave()` |
| post type `product` and `WC_Product_Factory::get_product_type()` is exactly `simple` | `loadEligibleProduct()` |
| status `publish` | `loadEligibleProduct()` |
| `arraysubs_is_subscription_product()` is true | `loadEligibleProduct()` |
| billing period is one of `day, week, month, year` (lifetime is excluded) | `loadEligibleProduct()` |
| the `arraysubs_paddle` gateway is registered, enabled and past setup | `syncProduct()` |

If they all pass it calls `PaddleGateway::syncProduct()` → `PaddleProductSync::syncProduct()`, which
creates/reuses a Paddle product and an exact recurring price and stores the binding on the product.

**So the sync is not opt-in per product.** Enabling the Paddle gateway makes every published simple
subscription product sync on every save. That is the intended design (a subscriber must be able to
pick Paddle at checkout, and Paddle needs a catalogue price to bill against), but it explains the
report's "noise": nothing about product 33252 asked for Paddle.

### Meta written

Keys are suffixed with the environment (`sandbox` when the gateway is in test mode, `production`
otherwise) — `PaddleGateway::getPaddleEnvironment()` is simply `isTestMode() ? 'sandbox' : 'production'`:

- `_arraysubs_gateway_paddle_product_id_<env>`
- `_arraysubs_gateway_paddle_product_binding_<env>`
- `_arraysubs_gateway_paddle_price_id_<env>`
- `_arraysubs_gateway_paddle_price_binding_<env>`
- `_arraysubs_gateway_paddle_synced_at_<env>`

The environment suffix is what keeps a sandbox binding from being mistaken for a live one. The
report's observation that 33252 gained `…_sandbox` keys is the system working as designed with the
gateway in test mode.

---

## 2. Why product 33324 produced a second notice

33324 was a Subscription Box switched to **Simple** while it still had no price. The save produced
two notices:

> Subscription products must have a valid regular price greater than zero.
> Paddle catalogue synchronization failed for product #33324. Review the Paddle sync log, then save the product again.

Both were correct in isolation, and the second was a direct consequence of the first:

1. WooCommerce applied the new `simple` product type and the box saver dropped its marker, even
   though the core subscription saver had already refused to write anything (F-04).
2. The product was therefore left **published, `simple`, `_is_subscription = yes`, `_price = ''`**.
3. That state passes every eligibility gate in `loadEligibleProduct()`.
4. `PaddleProductSync::buildRecurringContract()` rejects it — `! is_numeric($price) || (float) $price <= 0`
   → `WP_Error('subscription_contract_invalid')` → `failure()` → the generic admin notice.

**The Paddle sync was not the bug.** It was the second symptom of the broken intermediate state that
F-04 creates. The two real defects were:

- a blocked save still let the product type change (F-04), and
- a saver that runs *after* a blocked save had no way to know the save was blocked (F-03).

---

## 3. Changes made

1. **`PaddleProductSyncHooks::syncSimpleProduct()` bails when the save was blocked.**
   It now checks `arraysubs_is_blocked_product_save($product_id)` — the shared flag introduced with
   the F-03 fix — before loading the product. A save that validation refused never reaches Paddle, so
   the second notice cannot stack on top of the real error the merchant has to fix.

2. **The failure notice links to the log it names.**
   `addSyncError()` now renders
   *"Paddle catalogue synchronization failed for product #123. [Review the Paddle sync log], then save
   the product again."* where the link points at
   `admin.php?page=wc-status&tab=logs&source=arraysubs_paddle_sync` — the WooCommerce log viewer
   filtered to the source `PaddleProductSync::log()` writes to. `WC_Admin_Meta_Boxes::output_errors()`
   passes messages through `wp_kses_post()`, so the anchor survives; the message text is escaped.

3. **F-04's fix removes the state that caused this instance.** A type change away from a Subscription
   Box or Bundle is now vetoed while the resulting subscription settings are invalid, so a box can no
   longer become a published priceless "simple subscription".

---

## 4. Still open (deliberately not changed)

These are design questions, not defects. They are listed so the decision is explicit rather than
implicit.

| # | Observation | Why it was left alone |
|---|---|---|
| P-1 | Sync is **store-wide, not per product**: enabling the Paddle gateway syncs every published simple subscription product. A merchant who offers Paddle only for some plans still pushes all of them into the Paddle catalogue. | Changing it means adding a per-product opt-in and deciding what happens to already-bound products. That is a feature decision, not a bug fix. |
| P-2 | Sync runs **synchronously inside the product save**, so a slow or unreachable Paddle API makes "Update product" slow. There is already an Action Scheduler in the codebase that could carry it. | Moving it changes when the binding exists relative to the redirect, and the error would then have to be surfaced somewhere other than the meta-box notice. |
| P-3 | The notice is intentionally generic — provider responses stay in the sanitized log. With the link added it is now actionable, but a merchant still cannot tell "wrong API key" from "invalid price" without opening the log. | Echoing provider errors into the product editor was an explicit security decision in `addSyncError()`'s docblock. |
| P-4 | Variations are never synced. `loadEligibleProduct()` accepts an `expected_type` of `variation` and `PaddleTaxCategoryFields` renders a per-variation tax category, but no hook calls the variation path, so a **variable** subscription product has no Paddle catalogue price. | Wiring `woocommerce_save_product_variation` into the sync is a real gap, but it is outside the product-edit regression scope and needs its own test pass against the Paddle sandbox. **Recommend filing as a separate issue.** |
| P-5 | Lifetime deals are excluded from sync (period must be `day/week/month/year`). Correct for a recurring catalogue price, but a lifetime product offered with Paddle enabled has no remote price and will fail at checkout rather than at save time. | Same as P-4: needs a checkout-side decision, not a product-edit one. |

---

## 5. How to verify the fixes

1. **Blocked save no longer reaches Paddle.** Edit a published simple subscription product, clear the
   regular price, Update. Expect exactly one notice (the price error) and **no** Paddle notice. Check
   `admin.php?page=wc-status&tab=logs&source=arraysubs_paddle_sync` — there must be no new
   `phase=attempt` line for that product ID.
2. **Log link.** Force a real sync failure (e.g. temporarily invalidate the Paddle API key) and save a
   valid subscription product. The notice must render "Review the Paddle sync log" as a link that
   opens the WooCommerce log viewer filtered to `arraysubs_paddle_sync`.
3. **Box type change.** Copy a configured Subscription Box, switch its type to Simple without giving
   it a price, Publish. Expect the price error plus "The product type was left as …", the product
   still a Subscription Box, and no Paddle notice.
