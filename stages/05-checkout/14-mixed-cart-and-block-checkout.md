# Stage 05 — Task 14: Mixed Cart and Block Checkout

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout — Mixed Cart Rule |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | Task 02 (Block Checkout parity) |

## Objective
With **Allow mixed cart** enabled in General Settings, confirm that a cart containing one subscription product and one regular product checks out successfully through Block Checkout. Both line items must render in the order review summary, but only the subscription line shows the recurring text — the regular product line shows only the one-time price. (PayPal and Paddle are out of scope for this regression cycle; only Stripe and manual gateways are exercised.)

## Pre-conditions
- ArraySubs → Settings → General Settings:
  - **Allow mixed cart**: Enabled.
  - **Allow multiple subscriptions in cart**: Enabled (default).
- Stripe is already configured by the developer in test mode (or use a manual gateway such as BACS / COD).
- Block Checkout page is the active checkout page.
- Customer `customer-mixed@example.test` exists.
- Product `Basic Monthly` is published.
- Product `Standard Tee` (regular product, $15.00) is published.

## Test Data
- Subscription: `Basic Monthly` ($29.99 / month).
- Regular: `Standard Tee` ($15.00, one-time).
- Customer: `customer-mixed@example.test`.
- Expected today's total: `$29.99 + $15.00 = $44.99`.

## Sub-Tasks

### Sub-Task 14.1 — Confirm mixed-cart setting and gateway environment
**Steps:**
1. As Administrator, open **General Settings → Multiple Subscriptions**.
2. Confirm **Allow mixed cart** is **Enabled**.
3. Open **WooCommerce → Settings → Payments** and confirm Stripe is connected in test mode and at least one manual gateway (BACS / COD) is enabled.

**Expected Result:**
- Allow mixed cart toggle is ON.
- Only Stripe and/or manual gateways are enabled (PayPal/Paddle out of scope for this cycle).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.2 — Build a mixed cart
**Steps:**
1. In Incognito, log in as `customer-mixed@example.test`.
2. Add `Basic Monthly` to cart.
3. Add `Standard Tee` to cart.
4. Open the cart page (or the Cart block page).

**Expected Result:**
- The cart contains two distinct line items: `Basic Monthly` ($29.99) and `Standard Tee` ($15.00).
- No error notice appears about mixed carts.
- Cart totals show subtotal `$44.99` (excluding shipping and tax depending on store config).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.3 — Open Block Checkout and inspect the order review
**Steps:**
1. Click **Proceed to checkout** to open the Block Checkout page.
2. Examine the order review block on the right side / below the form.
3. Read the line for `Basic Monthly` and the line for `Standard Tee` carefully.

**Expected Result:**
- Both line items render in the order review.
- The `Basic Monthly` line shows the recurring summary block: `$29.99 every month`, today's charge contribution `$29.99`, next charge date today + 1 month, authorization notice for the subscription.
- The `Standard Tee` line shows only `$15.00` and the product name. It does NOT show recurring text, a next charge date, or an authorization notice — it is a one-time line.
- The grand "Today's total" at the bottom shows `$44.99` (sum of both lines).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.4 — Place the mixed-cart order
**Steps:**
1. Fill billing details.
2. Select Direct bank transfer (or the store's manual gateway).
3. Click **Place Order**.
4. Note the order ID.

**Expected Result:**
- Order placed successfully with total `$44.99`.
- Order received page shows both line items.
- Related Subscriptions table shows exactly one subscription (created from `Basic Monthly`); `Standard Tee` does NOT create a subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.5 — Verify subscription record + admin order detail
**Steps:**
1. As Administrator, mark the order Completed.
2. Open the order edit screen and confirm both line items are listed.
3. Open **ArraySubs → Subscriptions** and confirm only one new subscription was created for this order.
4. Open the subscription's **View Details** page.

**Expected Result:**
- WooCommerce order shows both `Basic Monthly` ($29.99) and `Standard Tee` ($15.00) line items.
- Subscriptions list shows the one new subscription on `Basic Monthly` with status Active.
- The subscription's parent order link points to the same order, but the subscription record only references the `Basic Monthly` line item (not `Standard Tee`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.6 — Negative test: disable mixed cart and re-attempt
**Steps:**
1. As Administrator, set **Allow mixed cart** to **Disabled**. Save.
2. As a new customer (or empty the cart), add both products again.
3. Open the cart page and the Block Checkout page.

**Expected Result:**
- The cart shows the error: *"This order cannot contain subscription and regular products together."* (exact wording from the manual's Mixed Cart Rule table).
- Block Checkout displays the error as a Store API cart error and the Place Order button is disabled / blocked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.7 — Restore Allow mixed cart
**Steps:**
1. As Administrator, re-enable **Allow mixed cart**. Save.
2. Refresh the cart and confirm the error clears.

**Expected Result:**
- Setting is back to enabled.
- Customer can proceed once both products are in cart again.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Reproduce Sub-Tasks 14.2–14.5 on Classic Checkout to confirm parity (the Block Checkout test must match Classic Checkout in this regard, per the manual).
- Confirm no JS console errors during the mixed-cart Block Checkout flow.
- Confirm the New Subscription email body still references only `Basic Monthly` (not `Standard Tee`).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order ID created:
- Subscription ID created:
- Notes:
