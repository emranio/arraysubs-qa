# Issue #69: "Today's Charge" leaves out initial-payment coupons and tax

- Date: 2026-10-20, 06:45–07:15 UTC. This was a read-only investigation: no code, settings, orders or fixtures were changed.
- Code reviewed: `arraysubs` b9d6c44 (ArraySubs 2.0.9), `arraysubspro` ffa7bc8 (Pro 1.2.8, licence active) and WooCommerce 11.0.1. None of the display or coupon files cited below have uncommitted changes, so the line numbers match HEAD.
- Staging evidence comes from an in-memory WP-CLI simulation (section 5.3). It used a memory-only WooCommerce session, set tax options and a 21% rate through in-process filters only, and created no orders. Before and after the runs, `wp_woocommerce_sessions` held 62 rows with 0 rows from the simulation, `wp_options` held 1343 rows, and coupon usage counts were unchanged. New posts that appeared during the window were Action Scheduler renewal notes for subscription #69676, run by the system cron (`DISABLE_WP_CRON=1`), not by these runs.

## 0. Summary

**Root cause.** Three separate pieces of code build the "Today's charge" line. All three take the amount from the product's subscription data instead of the calculated WooCommerce cart line. They use `$data['price'] × qty`, or the renewal-sync `initial_line_amount`, plus the raw `$data['signup_fee']`. The cart line already contains every coupon and its tax, but they never read it. The three copies:

1. `arraysubs/src/Features/SubscriptionProducts/Services/Hooks.php:1563-1629`, `getSubscriptionTodayChargeSummary()`. The bad lines are 1573-1578. It receives a `$cart_item` parameter but never uses it. This feeds the cart and checkout item details for the classic cart, classic checkout, classic mini-cart, the Cart, Checkout and Mini-Cart blocks, and the Pro e-mail snapshot.
2. `arraysubs/src/Features/SubscriptionCheckout/Services/Traits/CheckoutDisplayTrait.php:25-260`, `displaySubscriptionSummaryInTable()`. The bad lines are 76-88 and 176-221. This is the classic checkout totals table, and its labels ("Recurring", "Today's Charge", "Next Charge") match the customer's report.
3. `arraysubs/src/Features/SubscriptionCheckout/views/checkout-subscription-summary.php:80-86, 213-258`. This view is not included anywhere in either plugin. It is a third copy of the same logic.

**Reproduced with the customer's numbers** (case B1 in section 5.3): `line_total=188.10`, `line_tax=39.50`, cart total 227.60, but "Today's charge" shows $209.00. Both the item details and the classic table show the wrong amount.

**The bug is wider than the report:**
- **Tax is always missing, even without a coupon.** With 21% VAT and no coupon, Today's charge shows $20.00 against a total of $24.20 (T5).
- **Signup-fee tax is missing.** "$19.99 + $4.99 signup = $24.98" against a total of $26.60.
- **Renewal-sync proration ignores the coupon.** "$6.00 prorated" against a total of $6.53.
- **Pro initial-shipping fees and member cart discounts are missing.**
- **Plan switching at checkout shows two different amounts.** A "Due today" row shows the prorated net while "Today's charge" shows the full catalogue price.

**Two secondary display problems:**
- "Recurring"/"Renewals", "Next/First charge (amount)", the signup fee and the different-renewal-price amounts ignore WooCommerce's tax display mode. They always print the raw entered price. Example: with display set to "incl", WooCommerce's subtotal shows $252.89 (incl. VAT) but Recurring shows $209.00.
- With a *recurring* coupon, "Recurring" still shows the full price, even though the next N renewals will be discounted.

**Recommended meaning of Today's charge:** this subscription line's share of today's order total, including tax. Compute it from the calculated cart: `line_total + line_tax`, plus its weighted ArraySubs fees, plus its share of shipping, plus a reconciling remainder. This mirrors how ArraySubs already attributes charges after checkout (`arraysubs_get_subscription_order_charge_amounts()`). For a cart with one subscription, it equals the order Total (227.60 in the report).

**Recurring stays the renewal price.** It is shown in WooCommerce's cart tax-display mode, so it stays $209.00 in the reporter's setup. Ideally it also states the renewal discount when a recurring coupon applies.

## 1. Every code path that computes or shows the summary amounts

`SPH` = `arraysubs/src/Features/SubscriptionProducts/Services/Hooks.php` and `SCH` = `arraysubs/src/Features/SubscriptionCheckout/Services/Hooks.php`.

### 1.1 Display points

| # | Where it appears | Code | What it computes and from what | Status |
|---|---|---|---|---|
| D1 | Item details in the **classic cart** (`cart.php:126`), **classic checkout review** (`review-order.php:50`), **classic mini-cart** (`mini-cart.php:80`), and the **Cart, Checkout and Mini-Cart blocks** (Store API `item_data`, `CartItemSchema.php:72,156-183`) | `SPH:129` hooks `woocommerce_get_item_data` (priority 20) to `cartItemData()` (1353-1377), which calls `getSubscriptionCartItemData()` (1433-1506) | Rows: "Renewals"/"After trial" (1445-1451) from `getSubscriptionBillingSummary()` (1515-1554, uses `wc_price(price×qty)` and `wc_price(different_renewal_price×qty)`). "Signup fee" (1453-1464, raw `signup_fee`). "Free trial". **"Today's charge"** (1476-1480) from `getSubscriptionTodayChargeSummary()` (1563-1629). "Next charge"/"First charge" (1482-1487) from `getSubscriptionNextChargeSummary()` (1638-1675): a date, plus `(price×qty)` at 1670-1672 when there is a trial or renewal sync. "Duration". Then the filter `arraysubs_subscription_cart_item_data` (1505). | **Bug.** Today's charge ignores coupons, tax, fee tax and other fees. Recurring and next-charge amounts ignore the tax display mode and recurring coupons. |
| D2 | **Order e-mails** (only with Pro active) | `SPH:131` runs `addOrderItemEmailData()` (1392-1422) on `woocommerce_checkout_create_order_line_item` (priority 20). It calls `getSubscriptionCartItemData(..., 'email_snapshot')` and saves the result in the hidden item meta `_arraysubs_subscription_email_item_data`. Pro renders it: `arraysubspro/src/Features/CartInfoEditor/Services/Hooks.php:44` and `renderEmailOrderItemData()` (from line 117), e-mails only and never for renewal orders. | The same rows as D1, frozen at checkout. | **Bug** inherited from D1, so the order e-mail shows the wrong Today's charge. |
| D3 | **Classic checkout totals table** (the rows after "Total") | `SCH:82` hooks `woocommerce_review_order_after_order_total` to `displaySubscriptionSummaryInTable()` (`CheckoutDisplayTrait.php:25-260`) | Rows are printed per subscription line and **carry no product name**. Line 76: `$recurring_total = price × qty`. Lines 83-88: `first_payment` is 0 for a trial, the sync `initial_line_amount` when sync applies, otherwise `$recurring_total`; the signup fee is added. "Recurring"/"After Trial" are at 98-156, with checkout-migration "Due today" text at 100-121 and the renewal-price wording at 123-143. "First full renewal" is at 146-153. "Signup Fee" 160-165 (raw). "Free Trial" 168-174. **"Today's Charge" 176-221.** "Next/First Charge" 223-250, with the amount at 245-247. The authorisation notice is at 253-259. The loop at 35-57 drops the cart-item key and the line totals. | **Bug.** This is the path the customer reported. |
| D4 | Dead view | `SubscriptionCheckout/views/checkout-subscription-summary.php` (lines 1-305). A grep of both plugins found no `include`. | Third copy of D3. It reads `arraysubs_get_product_subscription_data()` (line 28), not cart-item data, so it would also miss Flexible/Box/Bundle changes. | Delete it (no backward compatibility is needed) or move it onto the shared helper. |
| D5 | Classic cart and checkout **price column** | `SPH:126`, `cartItemPrice()` (1112-1170) | WooCommerce's price HTML, which respects the display mode, plus "/ schedule", plus the raw signup fee (1137-1139) and the raw different renewal price (1149-1167). | Signup and renewal amounts are always the raw net values. |
| D6 | Classic cart and checkout **subtotal column** | `SPH:127`, `cartItemSubtotal()` (1180-1270) | Without a trial: WooCommerce's line subtotal (pre-discount, display-mode aware) plus the schedule. With a trial: "Free for X / Then {billing summary}". Different renewal price at 1228-1245 and signup fee "+ X signup fee (due today)" at 1247-1264, both raw. | Raw amounts. "Due today" in this text means the signup fee only. |
| D7 | Item name in the mini-cart (outside cart and checkout) | `SPH:128`, `cartItemName()` (1280-1341) | A schedule badge, plus raw signup fee and renewal price. | Raw amounts. |
| D8 | Classic mini-cart **quantity** | `SPH:133`, `widgetCartItemQuantity()` (1904-1954) | Schedule, raw signup fee, raw renewal price. | Raw amounts. |
| D9 | Classic checkout **quantity column** | `SPH:141`, `checkoutCartItemQuantity()` (1964-2029) | Schedule, renewal price × qty, trial, raw signup fee. | Raw amounts. |
| D10 | Classic mini-cart totals | `SPH:137-138`, `displayWidgetCartFees()` (2626-2664) | Shows the "Subscription Signup Fee" fee as `wc_price($fee->amount)` (net, whatever the display mode). Then **"Due Today" = `$cart->get_total()`** (2657-2663). That is the whole order, tax included, and it is the only existing order-level "due today". It appears only when the core signup fee exists. During fragment refreshes nothing is printed, because WooCommerce does not keep fee objects in the session. | The fee shows net only; the total is correct. |
| D11 | Block extension | `SubscriptionProducts/Services/StoreApiIntegration.php:66-124` (only `has_trial` and `trial_schedule`). `src/resources/storeApiCartSubscription.js:1-41` (trial line price "&lt;price/&gt; today (free for %s)", cart context only). | No other block script shows amounts. Blocks show D1's `item_data`. | Fixed automatically through D1. |
| D12 | Plan switch at checkout (checkout migration) | `SubscriptionCheckout/Services/Traits/CheckoutMigrationTrait.php`: `applyCheckoutMigrationPricing()` (30-107) sets the cart price to `display_price`, which is the prorated net divided by quantity (354). `appendCheckoutMigrationCartItemData()` (118-151) adds "Due today" (137) = prorated net, and "Credit applied". | D1 and D3 still print "Today's charge" from the catalogue price, so the summary shows two different amounts due today. | **Bug** (same root cause). |
| D13 | Renewal-sync note | `SubscriptionProducts/Services/RenewalSegmentRuntime.php:39,237-288` | Text only: "Today's payment covers the full billing cycle starting …". | OK. |
| D14 | Pro shipping text | `arraysubspro/src/Features/SubscriptionShipping/Services/Hooks.php:57-59,136-170,626-660` | Text only. Its charge comes through `addInitialShippingFees()` (82-104): a non-taxable cart fee with `arraysubs_charge_source_weights`. | That fee is missing from Today's charge. |
| D15 | Pro fixed-period end date | `arraysubspro/src/Features/FixedPeriodMembership/Services/Hooks.php:58,64,398-422` | "Membership ends" row only, no amounts. | OK. |
| D16 | Order-received page and My Account order view | `SCH:110` calls `displayOrderSubscriptions()` (`CheckoutDisplayTrait.php:267-280`), which renders `views/order-subscriptions.php`. "Related Subscriptions" Total = `OrderCreation::calculateRenewalTotal()` (`RecurringBilling/Services/OrderCreation.php:351-366`) = `_recurring_amount × _quantity`. `orderItemName()` (2038-2067) only adds the schedule. | Renewal amount, excluding tax and any recurring coupon. WooCommerce's own order totals are correct. | Same recurring-amount simplification (low priority). |
| D17 | Product page | `subscriptionPriceHtml()` (`SPH:856-915`): WooCommerce price HTML in shop display mode, plus a suffix. `views/product-subscription-info.php:60-84` and `addVariationSubscriptionData()` (968-1000), read by `src/resources/subscriptionVariableProduct.js`, show the raw signup fee and renewal price. | No "today" amount on the product page. | Raw extra amounts only (low priority). |
| D18 | Customer portal and subscription e-mails | `CustomerPortal/views/view-subscription.php:29-30,205-220` ("Recurring Amount", the effective amount excluding tax) and 265-325 (row for an active recurring coupon). Core e-mail templates show `_recurring_amount` (for example `Emails/templates/customer-renewal-reminder.php:78-79`). | Only after checkout. | Out of scope. |

### 1.2 Code that sets the cart line amounts

These hooks are what make `line_total` and `line_tax` the right source for the fix:

- `SPH:153`, `beforeCalculateTotals()` (1782-1833), runs on `woocommerce_before_calculate_totals` at priority 10. A trial line is priced 0 (1814). When renewal sync applies, the price becomes `initial_unit_amount` (1827). Otherwise it is `$data['price']`, which already reflects member pricing filters and Box/Bundle `pricing.total`.
- `SCH:85`, `applyCheckoutMigrationPricing()`, priority 30. Sets the migration price (`display_price`).
- `SPH:147`, `addSignupFeeToCart()` (1707-1771). Adds one fee, "Subscription Signup Fee": taxable, standard class, one per line and **not** multiplied by quantity, with `arraysubs_charge_source_weights[cart_item_key] = fee`.
- Core `SubscriptionBox/Services/CartHooks.php:321-361`, `addBoxSignupFee()`, and Pro `SubscriptionBundle/Services/CartHooks.php:315-361`, `addBundleSignupFee()`. Both add a taxable fee with source weights.
- Pro `SubscriptionShipping/Services/Hooks.php:82-104`, `addInitialShippingFees()`. Non-taxable fee with weights.
- Core `MembersAccess/Services/DiscountHooks.php:294-346`, `applyCartDiscount()`. A negative "Member discount" fee with weights.
- Pro `StoreCredit/Services/Hooks.php:212`. A negative "Store Credit Applied" fee **without** weights, so it applies to the whole order.
- Charge attribution after checkout, which the fix should mirror:
  - `SCH:179-194` copies fee weights to order fee items.
  - `SCH:209-253` copies shipping weights, using package contents quantities. Integrations can change them with the filter `arraysubs_checkout_shipping_charge_source_weights`.
  - `SubscriptionCreationTrait.php` (around 480-760) turns those into per-subscription allocations with `arraysubs_allocate_currency_amount_by_weights()` (`src/functions/order-item-charge-attribution.php:35-110`).
  - `arraysubs_get_subscription_order_charge_amounts()` (`src/functions/order-subscription-link.php:322-…`) defines a subscription's initial charge as its line gross plus its share of fees and shipping.

## 2. How ArraySubs models coupons

- **Storage.** There are no ArraySubs coupon types. A coupon is a normal WooCommerce coupon (`percent`, `fixed_cart` or `fixed_product`) plus four meta keys, defined in `CouponTracking/Services/Hooks.php`: constants at 38-59, admin fields at 252-304, saving at 313-397, and admin JS in `src/resources/couponEditPage.js`.
  - `_arraysubs_apply_to_subscriptions`: `yes` or empty. This is the gate.
  - `_arraysubs_discount_duration`: `one-time` (the default, "initial order only") or `recurring`.
  - `_arraysubs_discount_cycles`: a whole number; 0 means unlimited; used only for recurring.
  - `_arraysubs_count_initial_checkout`: `yes` means the initial paid checkout uses up one cycle.
- **First payment.** This always uses native WooCommerce discounting. The duration setting makes **no difference** to the first payment:
  - Coupons without `apply=yes` are removed from subscription lines by `filterSubscriptionCouponItems()` (`woocommerce_coupon_get_items_to_apply`, 468-477).
  - Such coupons are rejected on carts that contain only subscriptions by `validateSubscriptionCouponEligibility()` (`woocommerce_coupon_is_valid`, 435-458). Staging confirms it: applying `nosub10` failed.
  - So a subscription line's `line_total` and `line_tax` already include exactly the coupon discount that applies to it.
  - WooCommerce splits `fixed_cart` coupons **equally per item quantity**, not by price (`woocommerce/includes/class-wc-discounts.php:479-509`). Staging confirms it: a $10 `sub10once` coupon became $5 on the subscription and $5 on the mug.
- **Capture.** `captureOrderCoupons()` (525-663) runs on `arraysubs_data_created_from_order`. It takes the *first* coupon on the order that:
  - still exists and is not expired,
  - has `apply=yes`,
  - applies to the line (`isCouponApplicableToOrderItem()`, 1103-1144: product and category include/exclude rules, plus "exclude sale items"),
  - and is a supported type.

  It stores on the subscription: `_applied_coupon_id`, `_coupon_code`, `_coupon_discount_type` (the duration), `_coupon_remaining_cycles`, `_coupon_original_cycles`, `_coupon_discount_amount`, `_coupon_discount_percent`, `_coupon_wc_discount_type`, `_coupon_captured_at`, `_coupon_count_initial` and `_coupon_initial_cycle_pending`.

  `getCouponCaptureData()` (1051-1094) works out the value:

  | Coupon type | Value captured |
  |---|---|
  | `percent` | The percentage |
  | `fixed_product` | `min(amount, unit subtotal) × qty` |
  | `fixed_cart` | `amount × line_subtotal / eligible order subtotal` (line 1081) |

  For `fixed_cart` this is not the per-line split WooCommerce used at checkout (see section 7).
- **Counting the initial checkout.** `maybeCountInitialCheckoutCycle()` (672-703) runs on `arraysubs_order_paid` and subtracts one cycle.
- **Renewals.** `applyRecurringCoupons()` (714-823) runs on `arraysubs_renewal_invoice_created`, which fires only on the scheduled-invoice path. It applies only when:
  - the captured coupon is `recurring`,
  - it has cycles left (or is unlimited),
  - the coupon still exists, is not expired, and still applies,
  - and the filter `arraysubs_should_apply_coupon_to_renewal` allows it.

  The discount is the percentage × the renewal **order subtotal** (790), or the stored fixed amount. It is capped at the order total (796) and added as a negative fee "Recurring Discount: CODE" (`_subscription_coupon=yes`, tax status none, 812). WooCommerce still spreads tax over negative fees (`class-wc-order-item-fee.php:90-125`), so the renewal tax ends up correctly reduced. After a successful renewal, `onRenewalPaymentComplete()` (834-843) calls `decrementCouponCycles()` (873-938).
- **How "recurring excludes initial-only coupons".** Nothing removes the coupon from the recurring amount; it was never part of it. `_recurring_amount` is always the catalogue or locked price (`SubscriptionCreationTrait.php:1132-1166`), and renewal lines start from it (`OrderCreation.php:159-160`). One-time coupons are simply never applied again. So "Recurring $209.00" is correct for a one-time coupon and wrong (too high) for a recurring one.
- **Payment gateways.** `AutomaticPayments/Services/CartRestrictions.php:481-510` (`getCouponNeeds()`) requires the `coupons` capability for any applied subscription coupon, and `recurring_coupons` for a recurring one. Stripe (`StripeDelegate.php:299-300`), Mollie and Paddle declare both. PayPal declares only `coupons` (`PayPalConfigTrait.php:79`), so it is blocked when a recurring coupon is applied. If the customer can reach payment, the recurring discount will be applied to renewals.

## 3. Cart data at each display point, and the tax-display rules

### 3.1 What data is available

| Display point | Totals calculated in this request? | Cart-item `line_subtotal` / `line_subtotal_tax` / `line_total` / `line_tax` | Fee objects (`->total`, `->tax`, `->tax_data`, `->arraysubs_charge_source_weights`) | Shipping (`WC()->shipping()->get_packages()` and chosen rates) | Cart totals |
|---|---|---|---|---|---|
| Classic cart page (D1, D5, D6) | Yes: `class-wc-shortcode-cart.php:89/96` | Yes | Yes | Yes | Yes |
| Classic checkout review and table (D1, D3, D9) | Yes: `class-wc-shortcode-checkout.php:352`; AJAX `update_order_review`, `class-wc-ajax.php:363` | Yes | Yes | Yes | Yes |
| Blocks: cart, checkout, mini-cart (D1 through the Store API) | Yes: `CartController::get_cart_for_response()` calculates totals if they have not been calculated (`StoreApi/Utilities/CartController.php:69`) | Yes. The schema itself reads them (`CartItemSchema.php:74-80`). | Yes | Yes | Yes |
| E-mail snapshot (D2) | Yes, the checkout has just calculated them | Yes, in `$values`; `$item` has the same totals | Yes | Yes | Yes |
| Classic mini-cart widget and AJAX fragments (D1, D7, D8, D10) | **Not necessarily.** `get_refreshed_fragments` renders the mini-cart without recalculating. | Yes, restored from the session and possibly stale | **No.** The session keeps `cart_totals` and line totals but not fee objects (`class-wc-cart-session.php:106,415`). | No | Yes, from the session `cart_totals` |

The simulation confirmed that `$cart_item['key']`, `line_total` and `line_tax` exist on cart items, and that fee objects carry `total`, `tax` and the weights. For example: `fee Subscription Signup Fee total=4.99 tax=1.05 weights={"7eab…":4.99}`.

### 3.2 How WooCommerce shows the rest of the summary

- **Display mode.** `WC_Cart::get_tax_price_display_mode()` (`class-wc-cart.php:2472-2478`; a VAT-exempt customer forces "excl") and `display_prices_including_tax()` (391-401).
- **Line price and subtotal.** `get_product_price()` (2340-2347) and `get_product_subtotal()` (2360-2391) follow the display mode. The subtotal is before discounts. WooCommerce adds a tax label only when the display mode differs from how prices were entered:
  - "(incl. VAT)" when display is incl, prices are entered excl and the cart has tax.
  - "(ex. VAT)" when display is excl and prices are entered incl.
- **Cart subtotal row.** `get_cart_subtotal()` (2310-2332) uses the same rules.
- **Coupon row.** `wc_cart_totals_coupon_html()` (`wc-cart-functions.php:307-324`) shows the discount excl or incl tax according to the display mode.
- **Fee row.** `wc_cart_totals_fee_html()` (368-372) shows `total` in excl mode and `total + tax` in incl mode.
- **Tax rows** are shown only in excl mode (`review-order.php:95-110`).
- **Total row.** `wc_cart_totals_order_total_html()` (329-358) is always the gross amount. In incl mode it adds "(includes X VAT)".

### 3.3 Recommended rules for ArraySubs amounts

- **Today's charge behaves like the Total row.** Always show the gross amount, tax included. When the cart displays prices excluding tax and the line has tax, add WooCommerce's own label `WC()->countries->inc_tax_or_vat()` ("(incl. VAT)"). This makes clear why it differs from the net amounts above it.
- **Recurring, Renewals, Next/First charge amounts, "First full renewal" and the different renewal price behave like line prices.** Use `wc_get_price_including_tax()` or `wc_get_price_excluding_tax()` with `['price' => $unit, 'qty' => $qty]` on `$cart_item['data']`, and the same label rules as `get_product_subtotal()`. In the reporter's setup (entered excl, display excl) this stays **$209.00 with no label**.
- **Signup fee amounts behave like fee rows:** net in excl mode, net + tax in incl mode.
- **Today the code prints raw entered prices regardless of mode.** The simulation shows the inconsistency:
  - Display incl: WooCommerce subtotal "$252.89 (incl. VAT)", Recurring "$209.00".
  - Prices entered incl, display excl: WooCommerce "$172.73 (ex. VAT)", Recurring "$209.00".

## 4. Edge cases and the correct Today's charge

### 4.1 What Today's charge is meant to represent

**Per subscription line, not the whole order.**

- In the code it is built per line: item details per cart line, and the classic table repeats its rows for each subscription.
- The user manual describes it the same way. `user-manual/markdowns/checkout-and-payments/subscription-checkout.md` (around line 250) says "For every subscription line item … Today's charge calculation '$29.99 + $10.00 signup = $39.99'", with the trial table at around line 144.
- The only whole-order "due today" in the code is the mini-cart "Due Today" (D10), which equals the cart total.
- After checkout, ArraySubs records each subscription's share of the initial order as line gross plus its shares of fees and shipping (`arraysubs_get_subscription_order_charge_amounts()`).

**Recommendation:** Today's charge = this subscription line's share of today's order total, **gross (tax included)**:

```
line_total + line_tax
  + its share of every cart fee that carries arraysubs_charge_source_weights (total + tax)
  + its share of each package's chosen shipping rate (cost + taxes), split by package-contents quantity
  + its share of the remainder (cart total − everything above), split by line gross
```

The remainder covers order-level fees without weights (Pro "Store Credit Applied", third-party fees) and rounding.

Two properties follow:
- With one subscription line and nothing else, it **equals the order Total** (the reporter's 227.60).
- Across all product lines, the shares always add up exactly to the cart total. The prototype matched in all 35 scenario runs.

**Alternative:** use only `line_total + line_tax + weighted ArraySubs fees`. This fixes the reported case but would show less than the Total for physical subscriptions with shipping and for carts using store credit.

### 4.2 Case-by-case results

"Now" is the current output; "Correct" is the prototype result (Σ = cart total every time). Product IDs and coupons are from staging. **VAT** means 21% with prices entered excluding tax and display excl, simulated in memory.

| Case | Now | Correct | Notes |
|---|---|---|---|
| Report: $209/yr, 10% one-time coupon, VAT | $209.00 | **$227.60 (incl. VAT)** | Recurring stays $209.00 every year. |
| No coupon, VAT (2571, $20/mo) | $20.00 | $24.20 | Tax goes missing even without any coupon. |
| Staging as it is (no tax), 2571 + `qa-audit-coupon` | $20.00 | $18.00 | Reproducible without changing any settings. |
| Display incl, VAT | $209.00 | $227.60 | Recurring should be $252.89 (incl. VAT) every year. |
| Prices entered incl, display incl (2571 at $20 incl, 10% coupon) | $20.00 | $18.00 | Recurring $20.00. |
| Prices entered incl, display excl | $20.00 | $18.00 (incl. VAT) | Recurring $16.53 (ex. VAT), not $20.00. |
| Signup fee (206: $19.99 + $4.99) + `welcome15` (15%), VAT | "$19.99 + $4.99 signup = $24.98" | "$20.56 + $6.04 signup = $26.60" | The fee is not multiplied by quantity and coupons do not discount fees. The breakdown must be all gross so it adds up. |
| Signup fee, no tax | "$19.99 + $4.99 = $24.98" | "$16.99 + $4.99 = $21.98" | |
| Free trial + signup fee (55: trial 30d, $1,000 fee), VAT | "$1,000.00 (signup fee only)" | "$1,210.00 (signup fee only)" | Trial line price is 0, so the charge is the fee plus its tax. |
| Free trial only (202), with a coupon | "Free today" | "Free today" | Correct today. A one-time coupon on a trial discounts $0, so the first paid renewal is full price (current coupon model). |
| Zero total (100% coupon, or the line reduced to 0) | Catalogue price | "Free today" (show it whenever gross ≤ 0) | |
| Renewal sync, prorate (11943 Flex Week, 10% coupon, VAT) | "$6.00 prorated until the synced renewal date" | "$6.53 prorated …" | The prorated price is set on the cart line, so `line_total` already contains proration, coupon and tax. Depends on the date. |
| Renewal sync, full (10011, `welcome15`, VAT) | "$299.00 full first charge" | "$307.52 full first charge" | Next charge 1 Jan 2027 ($299.00) is a renewal amount and follows the display mode. |
| Renewal sync, next cycle (8648, 10% coupon, VAT) | $30.00 | $32.67 | Plus the existing "First billing cycle" note. |
| Quantity 2 (2571 ×2, 10% coupon, VAT) | $40.00 | $43.56 | Recurring $40.00 every month. |
| Two subscription lines (2571 + 2569, 10% coupon, VAT) | $20.00 / $10.00 | $21.78 / $10.89 (Σ $32.67 = Total) | The classic table repeats rows with no product name; label rows with the product name when there are two or more subscription lines. |
| Subscription + ordinary product (2571 + mug 266, `sub10once` $10 fixed_cart, VAT) | $20.00 | $18.15 (mug $6.05, Total $24.20) | The share covers only the subscription line. |
| Physical box with Pro initial shipping fee (61, $1,800 + $150 fee, VAT) | $1,800.00 | $2,110.20 (= Total) | The non-taxable fee "ArraySubs initial shipping" carries weights. |
| Package shipping (flat rate etc.) | Left out | Included as a quantity-weighted share | Staging only has free shipping, so this needs a temporary shipping method to test. Same weights as `SCH:209-253`. |
| Order-level negative fee without weights (store credit; simulated −$5) | Left out | Spread over lines by gross: single line $20.55 = Total; mixed cart $20.17 + $10.08 = $30.25 | Display only; refund attribution is unchanged. |
| Recurring coupon (`renew20for3`: 20%, 3 cycles), VAT | $20.00 | $19.36 | Recurring currently says "$20.00 every month". It should say "$16.00 every 1 month for the next 3 renewals, then $20.00 every 1 month". |
| Recurring coupon with "count initial" (`halfoff3`: 50%, 3 cycles) | $20.00 | $10.00 (no tax) | Recurring should say "… for the next 2 renewals, then …". |
| Recurring `fixed_cart` coupon in a mixed cart | — | Today's charge from the line | The renewal discount comes from the capture formula (subtotal share), so it can differ from the first-payment discount (see section 7). |
| Different renewal price (Pro, 209 Stepped Weekly / 55) | Raw | Gross line value | Recurring wording is unchanged, but amounts follow the display mode. |
| Plan switch at checkout (needs plan switching, one subscription per customer and auto-migrate; all off on staging) | Catalogue price, alongside a separate "Due today" (prorated net) row | Gross line (the line price is already the migration net) | Remove the duplicate "Due today" item row, or rename it to a breakdown such as "Credit applied". In D3 the Recurring row should show the new recurring price. |
| Lifetime (227, 10% coupon, VAT) | $199.00 | $216.71 | No recurring row. |
| Fixed-period plan (Pro; 241 / 1683) | Catalogue price | Gross line | No price adjustment. Section 7 notes a date inconsistency seen on 241. |
| Box / Bundle (price = `pricing.total`, signup fee via weighted "Box/Bundle Signup Fee") | `pricing.total` plus the raw fee | Gross line plus fee share | Covered by the weights. |
| Member price / member cart discount (negative fee with weights) | Member price only | Includes the discount share | |
| VAT-exempt customer | — | Gross = net | WooCommerce switches to excl mode and the line tax is 0. |
| Rounding with `woocommerce_tax_round_at_subtotal=yes`, or prices entered incl (unrounded `line_total`, e.g. 155.4545) | — | Round the sum to `wc_get_price_decimals()` | The weighted allocation keeps Σ exact. |
| Paddle | — | Same as the WooCommerce gross | Paddle is given the WooCommerce gross (`PaddleTaxResolver`, `tax_mode: internal`), so it matches. |

## 5. What exists on staging for reproduction (read only)

### 5.1 Configuration

**Tax and display settings**
- `woocommerce_calc_taxes=no`, and the `wp_woocommerce_tax_rates` table is empty, so taxes are off.
- `woocommerce_prices_include_tax=yes`.
- `woocommerce_tax_display_cart=excl` and `woocommerce_tax_display_shop=excl`.
- `woocommerce_tax_total_display=itemized`.
- `woocommerce_tax_based_on=shipping`.
- `woocommerce_tax_round_at_subtotal=no`.
- `woocommerce_calc_discounts_sequentially=no`.

**Store**
- Currency USD with 2 decimals.
- Base country GB; `woocommerce_default_customer_address=base`.
- Shipping: the GB zone and rest-of-world both offer **free shipping only**.

**Pages**
- The Cart (ID 7, `/cart/`) and Checkout (ID 8, `/checkout/`) pages use **blocks**.
- Classic checkout pages: `/classic-checkout-qa/` (338), `/slt-classic-checkout/` (11843), `/slt2-classic-checkout/` (31296), `/sqa-classic-checkout/` (68273).
- Classic cart pages: `/slt-classic-cart/` (11845), `/slt2-classic-cart/` (31298).

**ArraySubs settings**
- `renewals.sync_to_billing_cycle=false`, mode prorate. Product segment plans still apply on 8648, 10011, 11943, 12093, 12099 and others.
- Multiple subscriptions in one cart, mixed carts and different billing cycles are all allowed.
- `one_per_customer=false`, `auto_migrate_on_checkout=false`.
- `plan_switching.enabled=false`, so the migration case cannot be tested without a settings change.
- `trials.one_trial_per_customer=true`.
- `cart_info_editor`: all rows shown.

**Payment gateways** — enabled: Stripe (plus Link, Amazon Pay, Afterpay, US bank), Paddle, bacs, cheque, cod (cod is hidden for subscription checkouts). Disabled: PayPal.

### 5.2 Products and coupons

**Plain subscription products** (virtual, no trial, no fee, no segment plan, no fixed period):

| ID | Name | Price |
|---|---|---|
| 2571 | Smoke Plan Plus | $20/month |
| 2569 | Smoke Plan | $10/month |
| 495 | Pro Monthly | $49.99/month |
| 68271 | SQA Daily Stripe Plan | $7.31/day |

**No $209/year product exists.** For the reporter's exact numbers, create a fixture (yearly, $209, virtual) and register it with `register-fixture.php product <id>` using `SQA_PHASE=fix-65-69`.

**Edge-case products:**

| Case | Products |
|---|---|
| Signup fee | 206 Signup Fee Weekly ($19.99 + $4.99); 12577 SLT Signup Fee Daily ($9 + $15) |
| Trial | 202 Trial Weekly (7 days); 550 Trial 14-Day |
| Trial + signup fee + renewal price | 55 [QA] Premium Yearly (30-day trial, $1,000 fee, $18,000 after the first payment, 3 cycles) |
| Different renewal price | 209 Stepped Weekly ($19.99, then $29.99 after 3 payments) |
| Product-level renewal sync | 8648 ($30/month, next-cycle segment today); 10011 ($299/year, full); 11943 SLT Flex Week Segments ($14/week, prorate today) |
| Physical, with Pro initial/renewal shipping overrides | 61 [QA] Physical Box Monthly ($1,800, initial $150/unit, renewal $100) |
| Lifetime | 227 Lifetime Deal ($199) |
| Fixed period (Pro) | 241 Academic Year Pass ($99/year), 1683 |
| Box / Bundle | 12600 SLT Box Daily, 33290 PEQA Bundle QA, 11095 Welcome Bundle |
| Ordinary products for mixed carts | 266 Plain Mug ($10), 447 Standard Tee ($15) |

**Coupons:**

| ID | Code | Type | ArraySubs settings |
|---|---|---|---|
| 1344 | `qa-audit-coupon` | percent 10% | apply=yes, **one-time** (the reporter's coupon; cycles=1 and count_initial=yes are ignored for one-time), used 1 time |
| 257 | `welcome15` | percent 15% | apply=yes, one-time |
| 454 | `sub10once` | fixed_cart $10 | apply=yes, one-time |
| 456 | `renew20for3` | percent 20% | apply=yes, **recurring, 3 cycles**, count_initial off |
| 263 | `halfoff3` | percent 50% | apply=yes, recurring, 3 cycles, **count_initial on** |
| 260 | `nosub10` | percent 10% | not for subscriptions (rejected on subscription-only carts) |
| 458 | `nonsub5` | fixed_cart $5 | not for subscriptions |
| 1329 | `save20` | percent 20% | no ArraySubs meta |

The `slt*` coupons (12064-12076) expired on 2026-08-15. **No `fixed_product` coupon exists**; create one as a fixture if needed.

No subscription on staging has an NL billing or shipping address.

### 5.3 How the evidence was produced

The scratch script is `/tmp/claude-0/-home-server-manager-www-arrayhash-mirror-help-arrayhash-com-public-wp-content-plugins/38333750-918d-41d9-9e98-8f405025965a/scratchpad/simulate-todays-charge.php`. It is reusable to work out expected values for any date or product.

- It swaps in a session subclass of `WC_Session_Handler` that has no cookie, no database access and no save on shutdown.
- It sets taxes through `pre_option_*` and `woocommerce_find_rates`, and can override the price through `woocommerce_product_get_price`.
- It prints the line totals, fees, totals, D1 rows and D3 rows, together with the prototype's per-line Today's charge.
- Usage: `wp eval-file <script> <product_id> <coupon|-> <qty> <none|excl|incl> <prices_incl yes|no> <price|-> [<2nd product id>] [unweighted|nofees] --allow-root`

Key output for the customer's case (B1):

```
== line … product=2571 qty=1   line_subtotal=209.0000 line_subtotal_tax=43.8900 line_total=188.1000 line_tax=39.5000
   [item meta] Renewals: $209.00 every 1 month
   [item meta] Today's charge: $209.00
== totals subtotal=209 … discount=20.9 … total_tax=39.5 TOTAL=227.60
   [classic table] Recurring$209.00 every 1 month
   [classic table] Today's Charge$209.00
== PROTO line 283085d3: line_gross=227.6 fees=0 shipping=0 remainder=0 => TODAY=227.60 | recurring raw=209.00 excl=209.00 incl=252.89
```

## 6. Recommended fix

### 6.1 Shared core helper

Create a new file, `arraysubs/src/functions/cart-charge-helpers.php`. Everything in `src/functions/*.php` is loaded automatically by `arraysubs.php:125`. Keep the code out of `SPH`, which is already 2665 lines against the 3000-line limit. It should provide three functions.

1. **`arraysubs_get_cart_initial_charge_breakdown(?WC_Cart $cart = null): array`** splits today's cart total across cart lines. For each cart key it returns `line_net`, `line_tax`, `fee_net`, `fee_tax`, `signup_gross`, `shipping`, `remainder` and `gross`. Steps:
   1. Make sure totals have been calculated in this request: `if (!did_action('woocommerce_after_calculate_totals')) { $cart->calculate_totals(); }`. This is the pattern WooCommerce itself uses in `CartController.php:69`, and it is needed for the classic mini-cart, where fee objects are missing.
   2. For each line, `line = line_total + line_tax`, read from `$cart->get_cart()[$key]`.
   3. For each `$cart->get_fees()` entry with `arraysubs_charge_source_weights`, keep only weights for keys in the cart. Split `fee->total` and `fee->tax` separately with `arraysubs_allocate_currency_amount_by_weights()`.
   4. For shipping, take each package from `WC()->shipping()->get_packages()`. Find its chosen rate from `WC()->session->get('chosen_shipping_methods')[$package_key]`. Split `rate->get_cost() + array_sum(rate->get_taxes())` by `package['contents'][key]['quantity']`, the same weights as `SCH:209-253`.
   5. Compute the remainder: `round(cart->get_total('edit') − Σ, wc_get_price_decimals())`. Split it by each line's positive gross; if every line is 0, split equally.
   6. Cache the result for the request, keyed on `spl_object_id($cart)`, `$cart->get_cart_hash()` and `did_action('woocommerce_after_calculate_totals')`.

   A working prototype is in section 8. It returned Σ = cart total in all 35 scenario runs, including carts without fee objects and with an unweighted negative fee.

2. **`arraysubs_get_cart_item_initial_charge(array $cart_item, ?WC_Cart $cart = null): ?array`** looks up `$cart_item['key']`. It returns null when no cart is available, for example in admin or third-party callers, and callers then fall back to today's estimate.

3. **`arraysubs_format_cart_recurring_amount(WC_Product $product, float $unit_amount, int $qty, ?WC_Cart $cart = null): string`** matches `WC_Cart::get_product_subtotal()` (`class-wc-cart.php:2360-2391`):
   - When the product is taxable and taxes are on, use `wc_get_price_including_tax` or `wc_get_price_excluding_tax($product, ['price' => $unit_amount, 'qty' => $qty])`, according to `display_prices_including_tax()`.
   - Add the same inc/ex tax label in the same situations.
   - Otherwise return `wc_price($unit_amount * $qty)`.

**Signup portion of the breakdown text.** To show "first + signup = total" correctly, label the fee objects when they are added, for example `'arraysubs_charge_kind' => 'signup_fee'`. That means one extra fee argument in three places: `SPH:1763-1769` (`addSignupFeeToCart`), `SubscriptionBox/Services/CartHooks.php:358-364` and Pro `SubscriptionBundle/Services/CartHooks.php:353-359`. Then `signup_gross` is the total of the shares of fees with that label, and "first" = `gross − signup_gross`, so the three numbers always add up. If you would rather skip the equation, show "$26.60 (includes $6.04 signup fee)" instead.

### 6.2 Changes by function

- **`SPH::getSubscriptionTodayChargeSummary()` (1563-1629).** Get `$b = arraysubs_get_cart_item_initial_charge($cart_item)`. Keep the existing wording but use the gross amounts:
  - Trial with `signup_gross > 0`: "%s (signup fee only)" with `$b['gross']`.
  - `gross ≤ 0`: "Free today".
  - Signup fee present: "%1$s + %2$s signup = %3$s" with `gross − signup_gross`, `signup_gross` and `gross`, keeping the sync "prorated" variant.
  - Sync prorate and full: the existing labels with `gross`.
  - Otherwise: `wc_price(gross)`.
  - After the amount, add `<small class="tax_label">` + `WC()->countries->inc_tax_or_vat()` + `</small>` when `wc_tax_enabled()`, the cart is **not** displaying incl and the line or fee tax is above 0.
  - Keep the current estimate only when `$b` is null.
  - This fixes D1 (all classic and block surfaces) and D2 (e-mails).
- **`SPH::getSubscriptionBillingSummary()` (1515-1554)** and **`getSubscriptionNextChargeSummary()` (1638-1675, amount at 1671).** Format amounts with `arraysubs_format_cart_recurring_amount()`. Both need `$cart_item` for the product, and `getSubscriptionBillingSummary()` currently has no such parameter, so add it. Do the same for the signup fee row (1453-1464) and the raw amounts in D5-D9: `cartItemPrice` 1137-1167, `cartItemSubtotal` 1228-1264, `cartItemName` 1306-1336, `widgetCartItemQuantity` 1926-1950, `checkoutCartItemQuantity` 1993-2024. Fee amounts follow `wc_cart_totals_fee_html()` rules (net in excl mode, net + tax in incl mode); the shares from 6.1 can provide them.
- **`CheckoutDisplayTrait::displaySubscriptionSummaryInTable()` (25-260).**
  - Keep `$cart_item_key` and `$cart_item` in the loop (35-57).
  - Delete the duplicate calculation (76-88, 176-221). Call the same summary builders as D1 so the item details and the table can never disagree. Make them `public static` on a small new service, for example `SubscriptionProducts/Services/CartSummary.php`, which also keeps `SPH` under the size limit.
  - Format the Recurring, Next Charge (245-247) and "First full renewal" (151) amounts with the shared formatter.
  - With two or more subscription lines, put the product name in the row headings.
  - Migration branch (100-121): keep the "Recurring" heading and show the new recurring price ("After checkout …"). Today's Charge then carries the net amount through the cart line.
- **`CheckoutMigrationTrait::appendCheckoutMigrationCartItemData()` (118-151).** Remove the "Due today" row (136-140), which now duplicates the corrected "Today's charge", or turn it into a "Prorated price" detail. Keep "Checkout action" and "Credit applied".
- **`displayWidgetCartFees()` (2626-2664).** Show the fee with `wc_cart_totals_fee_html()`-style display. "Due Today" (`$cart->get_total()`) is already the correct whole-order amount.
- **Delete** `SubscriptionCheckout/views/checkout-subscription-summary.php`. It is never loaded and is a third copy of the same logic.
- **Recurring-coupon wording (recommended; same fix or a follow-up).** Add a helper such as `arraysubs_get_cart_item_recurring_coupon_preview($cart_item, $cart)` that mirrors the capture rules: the first applied coupon with `apply=yes` that exists, has not expired, applies to the product and has a supported type. Only `recurring` coupons matter.
  - Discount per renewal, mirroring the CouponTracking capture and renewal code:

    | Coupon type | Discount per renewal | Source |
    |---|---|---|
    | percent | renewal subtotal × % | 790 |
    | fixed_product | `min(amount, unit) × qty` | 1071-1073 |
    | fixed_cart | `amount × line_subtotal / eligible subtotal` | 1081 |

    Cap it at the renewal total (796).
  - Discounted renewals = cycles − (count_initial ? 1 : 0); 0 cycles means unlimited.
  - Text: "%1$s %2$s for the next %3$d renewals, then %4$s %2$s", or "%1$s %2$s" when unlimited. Amounts go through the recurring formatter.
  - Move the private CouponTracking calculations (`getCouponCaptureData()` 1051-1094 and `isCouponApplicableToOrderItem()` 1103-1144) into a shared helper rather than copying them.
  - Without this, a cart with `renew20for3` will say "Recurring $20.00 every month" even though the next three renewals are $16.00 + tax.
- **Strings.** Reuse WooCommerce's `inc_tax_or_vat()` and `ex_tax_or_vat()` labels. Any new `arraysubs` strings need translator comments.
- **Changelog.** Add `fixed:` lines to `arraysubs/changelog.draft.txt`, for example: "Today's charge in cart, checkout and order e-mails now shows the amount actually charged, including coupons, taxes and signup-fee tax; recurring amounts follow the store's tax display setting." Pro needs an entry only if Pro code changes (the bundle fee label).
- **Docs to update afterwards:**
  - `user-manual/markdowns/checkout-and-payments/subscription-checkout.md`: the Today's charge definition and example (around lines 144-150 and 250-270). It also says trials show "Free (trial starts today)", but the code prints "Free today".
  - `user-manual/markdowns/coupons/README.md` and `user-manual/markdowns/subscription-products/product-experience.md`: how the summary rows show discounts.
- **Core and Pro.** All changes are in core except the optional fee label in Pro `SubscriptionBundle/Services/CartHooks.php`. Pro filters on `arraysubs_subscription_cart_item_data`, such as CartInfoEditor at priority 100, keep working. The Today's-charge row keeps its `arraysubs-subscription-meta--today-charge` class.

### 6.3 Staging test plan

Use agent-browser with a guest session, as AGENTS.md requires. Check every case on the **Checkout block** (`/checkout/`), the **classic checkout** (`/classic-checkout-qa/`) and the **Cart block** (`/cart/`), plus the classic cart (`/slt-classic-cart/`) and mini-cart for P1-1.

The general rule for every case: with one subscription line and nothing else, Today's charge must equal the order Total; otherwise the shares add up to the Total.

**Phase 1: no settings change, current staging, no tax.**

| ID | Cart / coupon | Expected Today's charge | Expected Recurring | Before the fix |
|---|---|---|---|---|
| P1-1 | 2571 + `qa-audit-coupon` | $18.00 (= Total) | $20.00 every 1 month | $20.00 |
| P1-2 | 206 + `welcome15` | $16.99 + $4.99 signup = $21.98 | $19.99 every 1 week | $19.99 + $4.99 = $24.98 |
| P1-3 | 55 (trial + fee) | $1,000.00 (signup fee only) | After trial: $15,000.00 first payment, then $18,000.00 every 1 year | unchanged |
| P1-4 | 202 + `qa-audit-coupon` | Free today | $19.99 every 1 week | unchanged |
| P1-5 | 2571 + 266 mug + `sub10once` | $15.00 (Total $20.00) | $20.00 | $20.00 |
| P1-6 | 2571 + 2569 + `qa-audit-coupon` | $18.00 and $9.00 (Total $27.00), rows labelled with the product | $20.00 / $10.00 | $20.00 / $10.00 |
| P1-7 | 2571 ×2 + `qa-audit-coupon` | $36.00 | $40.00 | $40.00 |
| P1-8 | 11943 + `qa-audit-coupon` | "$5.40 prorated …" on 2026-10-20; recalculate on the test day with the scratch script | $14.00 every 1 week | $6.00 |
| P1-9 | 8648 + `qa-audit-coupon` | $27.00, plus the First billing cycle note | $30.00 | $30.00 |
| P1-10 | 61 + `qa-audit-coupon` | $1,770.00 ($1,620 + $150 initial shipping fee) | $1,800.00 | $1,800.00 |
| P1-11 | 2571 + `renew20for3` | $16.00 | If the recurring-coupon wording is built: "$16.00 every 1 month for the next 3 renewals, then $20.00 every 1 month" | $20.00 |
| P1-12 | 2571 + `halfoff3` | $10.00 | "… for the next 2 renewals …" | $20.00 |
| P1-13 | 227 lifetime + `qa-audit-coupon` | $179.10 | none (lifetime) | $199.00 |
| P1-14 | Place a bacs order for P1-1 with Pro active, then check Mailpit (`mailpit-agent wait-new … "order"`) | E-mail subscription rows show "Today's charge $18.00" | | Register the order and subscription fixtures (`SQA_PHASE=fix-65-69`) |

**Phase 2: tax window. This needs a temporary settings change.**

Snapshot first. `snapshot-settings.php` covers `woocommerce_calc_taxes` but **not** `woocommerce_prices_include_tax` (currently `yes`), `woocommerce_tax_display_cart` and `woocommerce_tax_display_shop` (both `excl`), or the tax-rate tables (currently empty). Extend the snapshot or record them by hand.

Then:

1. Set `woocommerce_calc_taxes=yes` and `woocommerce_prices_include_tax=no`.
2. Add **one NL-only standard rate**: 21%, label "VAT", applies to shipping. Use `WC_Tax::_insert_tax_rate()` and `_update_tax_rate_countries()`. Restricting it to NL keeps the many renewal invoices due today (e.g. at 07:15, 07:16, 08:05) untaxed, because no fixture subscription has an NL address.
3. In the browser, enter an NL billing and shipping address, e.g. Damrak 1, 1012 LG Amsterdam. Tax is based on the shipping address. On virtual-only block checkouts, confirm the VAT row appears once the address is entered.

| ID | Case | Expected Today's charge | Expected Recurring | Before the fix |
|---|---|---|---|---|
| T1 | 2571 + `qa-audit-coupon` | $21.78 (incl. VAT) = Total | $20.00 every 1 month | $20.00 |
| T1b | $209/yr fixture + `qa-audit-coupon` | $227.60 (incl. VAT), subtotal 209.00, coupon −20.90, VAT 39.50 | $209.00 every 1 year | $209.00 |
| T2 | T1 or T1b with `woocommerce_tax_display_cart=incl` | $21.78 / $227.60 | $24.20 / $252.89 (incl. VAT) | $20.00 / $209.00 |
| T3 | 206 + `welcome15` | $20.56 + $6.04 signup = $26.60 | $19.99 every 1 week | $24.98 |
| T4 | 55 (trial + fee) | $1,210.00 (signup fee only) | | $1,000.00 |
| T5 | 2571, no coupon | $24.20 | $20.00 | $20.00 |
| T6 | `prices_include_tax=yes`, display incl, 2571 + coupon | $18.00 | $20.00 | $20.00 |
| T7 | `prices_include_tax=yes`, display excl, 2571 + coupon | $18.00 (incl. VAT) | $16.53 (ex. VAT) every 1 month | $20.00 |

Restore afterwards:
1. Delete the rate (`WC_Tax::_delete_tax_rate($id)`) and confirm `SELECT COUNT(*) FROM wp_woocommerce_tax_rates` returns 0.
2. Restore the options with `restore-settings.php … apply`.
3. Run `diff-settings.php` and expect `all_identical: true`.
4. Compare the extra tax options by hand.
5. Record the window start and end times (`date -u`) in the fix log.

**Optional.**
- Test package shipping with a temporary flat rate on the GB zone, using a physical subscription plus the mug, and check that the shares add up to the Total.
- Test the plan switch at checkout by temporarily turning on `plan_switching.enabled`, `multiple_subscriptions.one_per_customer` and `auto_migrate_on_checkout`. This needs an existing subscription and allowed switch targets, so it is probably best as a separate window.

## 7. Related findings outside #69 (verify separately)

1. **Renewal tax for stores that enter prices including tax (likely overcharge, not checked on staging because taxes are off).**
   - `_recurring_amount` stores the raw entered price (`SubscriptionCreationTrait.php:1132-1166`). Renewal lines use it as a *net* amount (`OrderCreation.php:159-160`).
   - `WC_Order::calculate_totals()` then adds tax on top (`class-wc-order-item.php:289`, `calc_tax(..., false)`; there is no adjustment for inclusive prices in `abstract-wc-order.php:2146-2207`).
   - So $209 including tax at checkout would renew at $252.89.
   - PayPal plan tax treats the same stored amount as inclusive (`PayPalPlansTrait.php:384-411`), which is inconsistent.
2. **Renewal-sync gateway minimum ignores coupons and tax.** The Stripe minimum increase is worked out on the amount before any coupon (`renewal-sync-helpers.php:186-200`, `RenewalSegmentRuntime.php:216-232`), so a coupon can push the charge back below the minimum.
3. **Recurring `fixed_cart` coupons.** The renewal discount uses the subtotal share (`CouponTracking/Services/Hooks.php:1081`), while WooCommerce splits the first payment equally per item. In mixed carts the renewal discount therefore differs from the first-payment discount.
4. **Recurring coupons are only applied on the scheduled-invoice path** (`arraysubs_renewal_invoice_created`). Early renewals and renewals the gateway starts may not get them; this needs checking.
5. **Fixed-period product 241** shows "Next charge 20 Oct 2027" while "Membership ends 31 Aug 2027 / Auto-renews into next period". Needs a separate look.
6. **Wording.** `arraysubs_get_billing_schedule_phrase()` (`subscription-helpers.php:615-626`) produces "every 1 month" and "every 1 year".
7. **"Related Subscriptions" totals** (D16) and the portal "Recurring Amount" leave out tax and recurring coupons. Low priority.

## 8. Prototype of the split (scratch only, not product code)

```php
function sqa_proto_initial_charges(WC_Cart $cart): array {
    $dp = wc_get_price_decimals(); $lines = [];
    foreach ($cart->get_cart() as $key => $item) {
        $lines[$key] = ['line' => round((float) ($item['line_total'] ?? 0) + (float) ($item['line_tax'] ?? 0), $dp),
                        'fees' => 0.0, 'shipping' => 0.0, 'remainder' => 0.0];
    }
    foreach ($cart->get_fees() as $fee) {                       // ArraySubs-weighted fees (signup, box/bundle signup, initial shipping, member discount)
        if (!isset($fee->total)) { continue; }
        $w = is_array($fee->arraysubs_charge_source_weights ?? null) ? array_intersect_key($fee->arraysubs_charge_source_weights, $lines) : [];
        if (!$w) { continue; }                                     // unweighted (store credit, 3rd-party) -> remainder
        foreach (arraysubs_allocate_currency_amount_by_weights((float) $fee->total + (float) ($fee->tax ?? 0), $w) as $k => $v) { $lines[$k]['fees'] += $v; }
    }
    $chosen = WC()->session ? (array) WC()->session->get('chosen_shipping_methods', []) : [];
    foreach ((array) WC()->shipping()->get_packages() as $pk => $package) {   // same quantity weights as SCH:209-253
        $rate = $package['rates'][$chosen[$pk] ?? ''] ?? null; if (!$rate) { continue; }
        $w = []; foreach ((array) ($package['contents'] ?? []) as $k => $c) { if (isset($lines[$k]) && (float) ($c['quantity'] ?? 0) > 0) { $w[$k] = (float) $c['quantity']; } }
        $gross = (float) $rate->get_cost() + array_sum(array_map('floatval', (array) $rate->get_taxes()));
        if ($w && 0.0 !== $gross) { foreach (arraysubs_allocate_currency_amount_by_weights($gross, $w) as $k => $v) { $lines[$k]['shipping'] += $v; } }
    }
    $sum = 0.0; foreach ($lines as $l) { $sum += $l['line'] + $l['fees'] + $l['shipping']; }
    $remainder = round((float) $cart->get_total('edit') - $sum, $dp);
    if (0.0 !== $remainder) {
        $w = array_filter(array_map(static fn($l) => max(0, $l['line'] + $l['fees'] + $l['shipping']), $lines));
        foreach (arraysubs_allocate_currency_amount_by_weights($remainder, $w ?: array_fill_keys(array_keys($lines), 1)) as $k => $v) { $lines[$k]['remainder'] += $v; }
    }
    foreach ($lines as $k => $l) { $lines[$k]['gross'] = round($l['line'] + $l['fees'] + $l['shipping'] + $l['remainder'], $dp); }
    return $lines;
}
```

Before using this in product code, add three things: the totals-calculated guard and the request cache from section 6.1, and keep `fee_net` and `fee_tax` separate for the tax-label and signup breakdown.
