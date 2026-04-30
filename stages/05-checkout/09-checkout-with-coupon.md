# Stage 05 — Task 09: Checkout with Coupon (One-Time + Recurring with Cycle Limit)

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout — Coupon Application |
| Plugin Coverage | Both |
| Estimated Time | 20 min |
| Depends On | Task 01 |

## Objective
Confirm that:
1. A coupon configured as "Apply to subscriptions" / one-time discount reduces today's total at checkout, but does NOT apply to future renewals.
2. A recurring coupon with a configured cycle limit (e.g., 3 cycles) is captured at checkout, applies the discount today, and reflects the remaining cycle count in the subscription summary and on the subscription detail screen as "X renewal cycle(s) remaining" / `Total Cycles: 3`.

## Pre-conditions
- Two coupons exist in **WooCommerce → Coupons** (or are created during Sub-Task 9.1):
  - `SUB10ONCE` — fixed-amount discount of `$10.00`, type **Apply to subscriptions** but NOT marked as recurring (one-time, applies only to checkout).
  - `RENEW20FOR3` — percentage discount of `20%`, type **recurring**, with cycle limit `3`.
- Customer `customer-coupon@example.test` exists.
- Manual gateway (Direct bank transfer) is enabled.
- Product `Basic Monthly` is published.

## Test Data
- Product: `Basic Monthly` ($29.99 / month).
- Customer: `customer-coupon@example.test`.
- One-time coupon: `SUB10ONCE` ($10 off, applies to subscriptions, one-time).
- Recurring coupon: `RENEW20FOR3` (20% off, recurring, 3-cycle limit).

## Sub-Tasks

### Sub-Task 9.1 — Verify the two coupons are configured correctly
**Steps:**
1. As Administrator, open **WooCommerce → Coupons**.
2. Open `SUB10ONCE` and confirm:
   - Discount type: Fixed cart discount or Fixed product discount with amount `$10.00`.
   - "Apply to subscriptions" / "Allow on subscription products" is enabled.
   - Recurring/cycle settings are off (this coupon applies only at the initial order).
3. Open `RENEW20FOR3` and confirm:
   - Discount type: Percentage discount, amount `20`.
   - Recurring coupon flag enabled.
   - Number of renewal cycles set to `3`.

**Expected Result:**
- Both coupons exist with the configurations above.
- Both are published / publicly usable.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.2 — Apply the one-time coupon at checkout
**Steps:**
1. Log in as `customer-coupon@example.test` in Incognito.
2. Add `Basic Monthly` to cart.
3. Open the checkout page.
4. Locate the coupon entry box (Classic Checkout: "Have a coupon? Click here to enter your code"; Block Checkout: the Coupon section in the order summary).
5. Apply `SUB10ONCE`.

**Expected Result:**
- A coupon-applied notice appears: e.g., *"Coupon code applied successfully."*
- The order review table now shows a new row labeled `Coupon: SUB10ONCE` with `-$10.00` discount.
- Today's total has decreased by `$10.00`: today's charge = `$29.99 - $10.00 = $19.99`.
- The recurring price line still shows `$29.99 every month` (the coupon does NOT change the recurring text — it only discounts today).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.3 — Remove the one-time coupon and apply the recurring coupon
**Steps:**
1. Click the X / Remove control on the applied `SUB10ONCE` row to remove it. Today's total returns to `$29.99`.
2. Apply coupon `RENEW20FOR3`.
3. Re-read the subscription summary block and the totals.

**Expected Result:**
- A success notice confirms the coupon was applied.
- Today's total decreases by 20%: today's charge = `$29.99 - $5.998 = $23.99` (rounded to two decimals).
- The subscription summary now contains a recurring-coupon indicator showing the cycle count, similar to: `Coupon RENEW20FOR3 — 20% off (3 cycles remaining)` or the equivalent wording defined by the plugin.
- The summary or the recurring price line includes the cycle limit (e.g., "3 renewal cycle(s) remaining").

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.4 — Place the order with the recurring coupon
**Steps:**
1. Fill the billing details, select Direct bank transfer.
2. Click **Place order**.
3. Note the order ID and subscription ID on the thank-you page.

**Expected Result:**
- Order placed at `$23.99` (today's discounted total).
- Related Subscriptions row shows the subscription with status Pending or Active.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.5 — Verify the Coupon Discount card on the subscription
**Steps:**
1. As Administrator, mark the order Completed.
2. Open the subscription via **ArraySubs → Subscriptions → View Details**.
3. Locate the **Coupon Discount** card.

**Expected Result:**
- The card is visible (per manual it appears when a coupon is applied).
- Coupon Code row: `RENEW20FOR3`.
- Discount: `20%`.
- Type Badge: **Recurring**.
- Total Cycles: `3`.
- Remaining Cycles: depends on whether checkout counts as cycle 1 — record the displayed value here:_____.
- Captured Date: today.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.6 — Verify cycle count reflected on the customer-facing portal
**Steps:**
1. Log in as `customer-coupon@example.test`.
2. Go to **My Account → Subscriptions** and open the subscription.
3. Scroll to the recurring amount / coupon area of the overview table.

**Expected Result:**
- Below the recurring amount row, a coupon row appears showing: `20% off (RENEW20FOR3)` and a cycle indicator like `3 renewal cycle(s) remaining` (or `2` if the initial order counts as one cycle — record exact wording).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.7 — Negative test: apply a non-subscription coupon
**Steps:**
1. As Administrator, briefly create or use an existing coupon `NONSUB5` (regular WooCommerce coupon, NOT marked "Apply to subscriptions").
2. As the customer, in a fresh cart with `Basic Monthly`, attempt to apply `NONSUB5` at checkout.

**Expected Result:**
- The coupon is rejected with a WooCommerce error message indicating it is not valid for subscription products. The exact wording depends on the WooCommerce / ArraySubs locale, but the discount must NOT apply to today's total.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Apply `SUB10ONCE` and `RENEW20FOR3` simultaneously (if combinations are allowed by the coupon settings) and verify both line items appear with correct math.
- Confirm that removing a coupon mid-checkout via AJAX restores today's total without breaking the subscription summary block.
- Confirm the AJAX update does not duplicate the authorization notice or the "next charge" line in the summary.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order ID created:
- Subscription ID created:
- Notes:
