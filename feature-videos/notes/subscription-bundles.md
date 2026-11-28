# Subscription bundles — feature video notes

Slug: `subscription-bundles` · Tier: Pro · Scene: `scenes/subscription-bundles.mjs`
Output: `$FV_OUT/subscription-bundles/subscription-bundles.mp4` + `poster.webp`
Customer used for every customer-side mutation: **#1115 Grace Watson** (block 1105-1165, owns sub #30212
Quarterly Learning Pass, which gives no member price on coffee, so bundle prices show clean).

## Storyboard

Story: a draft product becomes "Morning Ritual Bundle" on camera, Grace buys it, and admin sees the result.
Bundle: 2 x Single-origin Coffee ($22) + 1 x Ceramic Member Mug ($16) + 1 x Starter Membership ($19/month,
subscription product) = $79.00, 15% off = $67.15 / month, own renewal-sync segment plan (defaults 1-10 full,
11-20 prorate, 21-30 full for next cycle). Bought on Nov 28 → first charge covers the next cycle, next charge Jan 1.

| # | Kicker | Title | What is shown |
| --- | --- | --- | --- |
| 1 | Admin | Start with a product | Draft product editor → product type select → "Subscription Bundle Details" empty state → Configure Bundle modal → 3-screen nav → schedule (Fixed, every 1 month, until cancelled), zoomed |
| 2 | Admin | Pick the products | Search "coffee" (zoomed dropdown, badges + prices) → pick twice (qty 2) → "mug" → "starter" (Subscription badge) → rows zoom-pan (names, then Qty + reorder/remove) → $79.00 subtotal |
| 3 | Admin | One discount | Discount screen: "applies to every payment" alert (zoom) → choice group → Percentage → type 15 → preview $79.00 − 15% = $67.15 |
| 4 | Admin | One billing day | Renewal sync screen: tick own segment plan → slider + legend → Save Configuration → summary facts + table (zoom, poster) → Publish → Status: Published |
| 5 | Storefront | A page that sells the kit | Product page as Grace: struck $79.00 -> $67.15 / month (zoom), What's included ("Same items every month"), Subscribe Now -> lands on checkout |
| 6 | Checkout | One line at checkout | Full-width block checkout: order summary line zoom (contents + discount rows, Next charge January 1, "Today's payment covers December"), bank transfer -> Place Order -> order received with $0.00 item lines ("What ships each month") |
| 7 | My Account | Clear in My Account | Included Starter Membership card ($0.00, "Included in a subscription bundle", access scope) → bundle detail: Bundle Contents + discount, Next Payment Jan 1 |
| 8 | Admin | The bundle in admin | Bundle sub stats -> Bundle Contents panel (zoom) + Included subscriptions -> included sub framed on $0.00 + Jan 1 cards only -> Schedule + "Part of" panel |
| 9 | Admin | Every renewal, a packing list | First renewal order (made off-camera): note "Renewal order … Cycle #2" ringed, then the $0.00 item lines ("Items to pack") |

Feature-page claims proven on screen: up to 50 products / 1-99 qty (card subline + Qty label), one discount fixed
or percentage on every payment, live price preview, struck full price on the product page, What's included list,
Subscribe Now, tidy cart rows, included $0.00 subscription that keeps membership access, renewal sync billing
day, packing list on the order. Not shown: shop grid/catalog sorting, coupons applied, emails, editing a bundle that already has subscribers (only the
lock-in bullet in the discount screen). Renewal orders are shown since pass 2.

## Store change log (every change, with undo)

| When (UTC) | Change | IDs | Undo |
| --- | --- | --- | --- |
| 2026-11-27 ~22:00 | Created draft product "Morning Ritual Bundle" (simple, draft, image #25751 Coffee Gift Set, category #24 Bundles & Boxes, weight 1.2, short description, marker meta `_fv_subscription_bundles=1`). Chapter 1 `before` resets it to a simple draft each take; chapter 4 turns it into a Subscription Bundle on camera and **publishes** it (config: products 6696 x2, 6698 x1, 6710 x1; 15% discount; own renewal-sync plan). | product 32209 | `wp post delete 32209 --force --allow-root` |
| 2026-11-27 ~22:30 | Dry-run take: order #32402 (bank transfer, set to processing via WP-CLI), bundle sub #32403, included sub #32409 for customer 1115. Deleted again by chapter 5 `before` (removeTakes). | order 32402, subs 32403/32409 | (deleted) |
| every take | Chapter 5 `before` deletes earlier takes (customer 1115 subs on product 32209, their included child subs, their parent orders) and empties 1115's cart. Chapter 6 places a new bank-transfer order; chapter 7 `before` marks it processing ("Bank transfer received.") so the subscriptions start. Final take IDs below. | see below | delete the order + both subs (same PHP as `php.removeTakes` in the scene) |
| each take | Each placed order reduces stock of managed-stock children (Single-origin Coffee x2, Ceramic Member Mug x1) if stock is managed; deleting the order does not restore it. | 6696, 6698 | `wc_update_product_stock()` if it matters |
| 2026-11-27 | Display-only clean map `clean-map.d/subscription-bundles.json`: demo SKUs `DEMO-260927-COFFEE/TEA/MUG/STARTER/BUNDLE` show as `COF-SINGLE-250`, `TEA-HERBAL-20`, `MUG-CERAMIC`, `MEM-STARTER`, `BND-COFFEE-TEA` (only `fv_clean=1` requests). Do not save those products' edit forms while recording. | – | delete the json file |

No shared settings were changed. Renewal sync is set on the bundle product only (store-wide sync stays Off).

## Final take

- Recorded 2026-11-28 (UTC 2026-11-27 ~21:00-22:00). Final file: `$FV_OUT/subscription-bundles/subscription-bundles.mp4`
  (299.8 s, 1920x1080, 30 fps, 9 chapters) + `poster.webp` (chapter 4: zoomed bundle summary, "Total per payment" ring).
- Segments: 01 34.9s · 02 55.5s · 03 26.9s · 04 37.1s · 05 24.4s · 06 37.8s · 07 29.4s · 08 30.7s · 09 23.0s.
- Records left in the store by the final take (keep them; chapters 7-9 were recorded from them):
  - product **32209** "Morning Ritual Bundle", published, type Subscription Bundle, `_price` 67.15, own renewal-sync plan.
  - order **32671** (customer 1115 Grace Watson, Direct bank transfer, set to processing with note "Bank transfer received."),
    bundle subscription **32672** (active, $67.15/month, next payment 2027-01-01 local), included subscription **32676**
    (Starter Membership, $0.00, parent 32672).
  - 1115's cart is empty (teardown). Stock: Single-origin Coffee and Ceramic Member Mug were reduced by each take's order
    (2 + 1 per take, ~5 takes; stock was 1922 / 1777, so no visible effect).
- Full undo: delete subs 32676 + 32672 (`wp post delete <id> --force`), delete order 32671 (`wc_get_order(32671)->delete(true)`),
  delete product 32209, delete `clean-map.d/subscription-bundles.json`.
- Re-recording: chapter 1 resets 32209 to a simple draft; chapters 2-4 rebuild the editor off-camera when recorded alone
  (re-record 2/3 together with 4, which publishes again). Chapter 5 and 6 `before` delete the previous take's order +
  subscriptions, so re-record 5 or 6 together with 7-9.

## Pass 2 (2026-11-28, reviewer fixes)

Reviewer verdict on v1 was "fix" (3 major, ~18 minor). The scene was reworked and fully re-recorded
(all 9 chapters, then chapters 3-9 again for one ring fix). v1 segments are kept in
`$FV_OUT/subscription-bundles/v1/`, the intermediate take in `pass3/`.

Final file: `$FV_OUT/subscription-bundles/subscription-bundles.mp4`, **282.3 s**, 1920x1080, 30 fps, 9 chapters,
+ `poster.webp` (chapter 4 bundle summary, ring on "Total per payment", label "$67.15 every month").
Segments: 01 34.8 · 02 47.9 · 03 30.4 · 04 34.9 · 05 24.9 · 06 32.5 · 07 29.4 · 08 26.9 · 09 20.6.

### What changed in the scene

| Reviewer issue | Fix |
| --- | --- |
| Major: included sub shows "Total paid $67.15" under the "$0.00" claim (ch 8) | Product bug (see below), not fixed in code. The click into the included subscription now lands already framed (2.0x) on its Recurring amount ($0.00) + Next payment (Jan 1) cards only, then crossfades to a 2.0x frame of Schedule + "Subscription Bundle / Part of" panel. The included sub's page is never shown at 1x, so Total paid never appears. Caption: "The included plan bills $0.00 and renews with the bundle." |
| Major: narrow block checkout (ch 6) | Recording-only CSS widens `body.woocommerce-checkout` (the store's Checkout Builder container is 860px); the order-received page keeps the store width. Ch 6 now shows the checkout order summary (Subscribe Now goes straight to checkout) instead of the cart page. |
| Major: "Checkout · Checkout Classic · Cart Page" header items | Hidden for this scene's requests by the new mu-plugin (see store changes). Header now reads Home · Shop · My Account · cart. |
| Qty merge not visible (ch 2) | After the second pick the picker closes off-camera, camera frames the row (name + Qty) at 1.3x, ring on Qty "Qty 2, still one row". |
| $ position mismatch | Mu-plugin prints prices symbol-first ($67.15) for this scene's requests, so Woo screens match the ArraySubs admin and the captions. |
| Ch 6 sync reason | Caption "Today's payment covers December; next charge **January 1**." |
| Renewal never shown (ch 9) | Ch 9 `before` creates the first renewal order off-camera (`RenewalProcessor::createRenewalInvoice`); ch 9 is now "Every renewal, a packing list": order note "Renewal order for subscription … (Cycle #2, scheduled for January 1, 2027…)" ringed, then the $0.00 item lines. |
| Saving repeated in cart line | Zoom stays on the left rows (description column, 1.85x); the struck price/"Save $11.85" badge remain visible at the right edge (product-side, listed below). |
| Empty description editor (ch 1) | `resetProduct` writes a two-sentence description; recording-only CSS shortens the editor to 96px. Caption now "a name, a photo, a description" (category is visible after the scroll). |
| "Renewals included" pill over "Fixed amount" (ch 3) | Ring on the alert heading only, label to its right; second beat rings the "locked at purchase" bullet with caption "Contents and price lock in at purchase; edits reach new buyers only." (covers the edit-after-purchase FAQ from the UI text). |
| "Reorder or remove" label over $16.00 (ch 2) | Labels `side: 'top'`; names + Qty frame then a pan to the actions column. Subtotal beat dropped (the $79.00 subtotal is visible in the pan and in ch 3's preview). Mug picked off-camera. Ch 2: 55.5 s -> 47.9 s. |
| Stale caption after Save Configuration (ch 4) | Caption cleared before the click, new caption before the scroll. |
| Missing chapter sublines | Sublines on chapters 5-9. |
| "Memberships" title + contact notice (ch 7) | Mu-plugin uses the plugin's default "Subscriptions" label for this scene; recording-only CSS hides `.arraysubs-cancellation-contact-notice` (store has customer cancellation off). |
| "Ready to pack" on a customer screen | Now "What ships each month". |
| Cursor on "Part of" link / Custom Fields empty state (ch 8) | Cursor hidden before the crossfade; camera window ends above the Custom Fields empty state. |
| "Items to pack" label on the admin bar (ch 9) | Label `side: 'bottom'`, renewal order rows framed at 1.4x. |
| "Fixed contents, in your order" | "Same items every month". |
| Labels that repeat the text | "A draft product", "New product type" (cursor parked in the panel), "$67.15 every month", "Straight to checkout". |
| Cursor on Update after Publish | Cursor moved to the Status row before the "Published" ring; Publish box and the bundle summary share the frame before the click. |
| Length | 299.8 s -> 282.3 s. |

### Store changes in pass 2 (with undo)

| Change | IDs | Undo |
| --- | --- | --- |
| New mu-plugin `wp-content/mu-plugins/zz-fv-subscription-bundles.php`. Active only with cookies `fv_clean=1` AND `fv_sb=1` (set by this scene's `asUser`). Display only, writes nothing: `woocommerce_currency_pos` read as `left`; header nav links "Checkout", "Checkout Classic", "Cart Page" not rendered; `arraysubs_myaccount_menu_config` subscriptions label read as empty (plugin default "Subscriptions"). | – | delete the file |
| Recording-only CSS (Playwright init script in the scene, nothing on the server): description editor 96px, block checkout full width (not order-received), contact notice hidden. | – | – |
| Product 32209 `post_content` set to the two-sentence description (also re-set by every chapter 1 take). | 32209 | `wp post update 32209 --post_content='' --allow-root` (or delete the product) |
| Every take: chapter 1 `before` (and `ensureEditor` rebuilds) now also delete earlier takes, so the editor never shows the "Active Subscriptions" notice. `removeTakes` deletes the bundle sub, its included subs and every order in `_parent_order_id`/`_order_ids` (initial + renewal). Deleted in this pass: 32671/32672/32676/32791 (v1 take + test renewal), 32864/32865/32869/32909, 33077/33078/33082/33135. | – | – |
| Chapter 9 `before` raises the first renewal invoice early (Nov 28 for the Jan 1 cycle) with `RenewalProcessor::createRenewalInvoice()`: pending renewal order + "[ArraySubs] Renewal Invoice" email to grace.watson@example.com (local mail only). | renewal 33179 | delete the order (removeTakes does it) |
| No shared settings or options were changed. `woocommerce_currency_pos` is still `right` in the database. 1115's cart is empty (teardown). | – | – |

**Records left by the final take (chapters 7-9 were recorded from them):** product 32209 (published Subscription Bundle),
order **33162** (processing, "Bank transfer received."), bundle subscription **33163** (active, $67.15/month, next 2027-01-01 local),
included subscription **33167** (Starter Membership, $0.00), renewal order **33179** (pending payment, cycle 2).
Full undo: `wc_get_order(33179)->delete(true); wc_get_order(33162)->delete(true);` then
`wp post delete 33167 33163 --force --allow-root`, `wp post delete 32209 --force --allow-root`, delete
`clean-map.d/subscription-bundles.json` and `wp-content/mu-plugins/zz-fv-subscription-bundles.php`.

### Product bugs found (not fixed, plugin code untouched)

1. **Included bundle subscription shows the parent bundle's money as "Total paid".**
   `SubscriptionController::calculateTotalPaid()` (`arraysubs/src/Features/SubscriptionAdmin/REST/SubscriptionController.php:823`)
   sums the full total of every paid order in `_order_ids`. The included child (meta `_arraysubs_bundle_parent_subscription`)
   carries the parent order in `_order_ids`, so its detail page shows "Total paid $67.15 · 0 completed payments" (stat tile and
   Plan & Billing) next to "Recurring amount $0.00". Repro: as customer 1115 buy product 32209 by bank transfer, set the order to
   processing, open Subscriptions > detail of the included Starter Membership (e.g. #33167). Expected $0.00 (only its own lines).
2. **ArraySubs subscription detail ignores WooCommerce currency formatting.** `createCurrencyFormatter()`
   (`arraysubs/src/resources/pages/subscription-detail/helpers.js:212`) uses `Intl.NumberFormat` with the currency code only, so a
   store with `woocommerce_currency_pos = right` (Woo prints 67.15$) shows $67.15 in the ArraySubs admin (separators/decimals too).
3. **Bundle saving repeated in block cart/checkout** (Clean Storefront Copy rule): struck $79.00 -> $67.15 under the name, a
   "Save $11.85" sale badge in the price column, and a "Bundle discount: 15% off (-$11.85)" row on the same line
   (`/cart-2/`, `/checkout-2/` order summary).
4. Page copy (not product): `web-content/src/pages/features/subscription-bundles.astro` ShopperView says "A Review details list
   shows each item"; the product page heading is "What's included" (`arraysubspro/src/Features/SubscriptionBundle/Services/FrontendHooks.php:103`).
