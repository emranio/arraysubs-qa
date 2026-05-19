# Stage 12 — Task 05: Purchase with Credit at Checkout

| Key | Value |
|---|---|
| Stage | 12 — Store Credit (Pro) |
| Module | Store Credit — Allow at Checkout |
| Plugin Coverage | Pro |
| Estimated Time | 20 min |
| Depends On | 12.01, 12.02, 12.04 |

## Objective
With **Allow at Checkout** enabled, place an order containing the canonical `Standard Weekly` ($19.99/week) subscription product. The customer's credit must silently cover the order, the credit balance must drop by the order total, the order must be paid in full, the **Credit Used** email must arrive, and the **Minimum Order Amount** must correctly block credit application on too-small orders.

## Pre-conditions
- 12.01–12.04 complete; `cust3@test.local` balance entering this task is `$205.00`.
- The canonical `Standard Weekly` ($19.99/week) subscription product exists from Stage 03.
- A small `Tiny Daily` product priced at $2.99 exists (or reuse the daily-billed `Coffee Plan` $2.99 variation) for the minimum-order-block check.
- Stripe test mode is already configured. BACS / Cash on Delivery available as a manual fallback.
- **Allow at Checkout** = On, **Minimum Order Amount** = `5.00` (from 12.01).

## Test Data
- Customer: `cust3@test.local` (balance `$205.00`).
- Order A: subscription product `Standard Weekly` at $19.99/week (above $5 minimum → credit should apply).
- Order B: $2.99 daily product `Coffee Plan — Daily` (below $5 minimum → credit should NOT apply).

## Sub-Tasks

### Sub-Task 5.1 — Customer adds the $19.99 weekly product
**Steps:**
1. Log in as `cust3@test.local`.
2. Add `Standard Weekly` to cart.
3. Open the Cart page.

**Expected Result:**
- The cart shows the product line at `$19.99 / week`.
- A line item or totals area indicates that store credit will be applied. Wording may vary, e.g., `Store Credit (-$19.99)` as a fee or `Credit Applied: $19.99`.
- The order total drops to `$0.00` (or shows `$19.99` minus a `$19.99` credit fee).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.2 — Complete checkout
**Steps:**
1. Click **Proceed to Checkout**.
2. Read the order review block.
3. Place the order — the payment method may be hidden (because total is $0) or a `No payment required` placeholder may be shown.
4. Submit the order.

**Expected Result:**
- The order completes immediately and the order-received page loads.
- Order total is `$0.00` (or displayed as paid in full).
- The order has a fee line item with negative value `Store Credit -$19.99` (or equivalent).
- A weekly subscription is created in **Active** state for `cust3`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.3 — Balance reduced by $19.99
**Steps:**
1. As admin, **ArraySubs → Store Credit → Manage Credits**.
2. Search and load `cust3`.

**Expected Result:**
- Balance is `$185.01` (`$205.00 - $19.99`).
- A new Credit History row appears with source `Applied to Order`, amount `-$19.99`, balance after `$185.01`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.4 — Credit Used email arrived
**Steps:**
1. Open `cust3@test.local`'s inbox.
2. Find the latest **Store Credit Used** email.

**Expected Result:**
- Subject contains `Store credit used for Order #` and the order number.
- Body shows:
  - Credit Applied: `$19.99`.
  - Remaining Balance: `$185.01`.
  - A link to the order details.
- Placeholders rendered correctly.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.5 — Minimum order amount blocks small order
**Steps:**
1. As `cust3`, add `Coffee Plan — Daily` ($2.99) to cart.
2. Open Cart.

**Expected Result:**
- The cart shows `$2.99` total.
- NO credit is applied (the $2.99 order falls below the $5.00 **Minimum Order Amount**).
- Customer's balance remains `$185.01`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.6 — Verify the small order proceeds without credit
**Steps:**
1. Proceed to checkout.
2. Pay $2.99 via Stripe test card `4242 4242 4242 4242` (or BACS / COD as a manual fallback).
3. Confirm the resulting order.

**Expected Result:**
- The order goes through with the full $2.99 paid via Stripe / manual method (no credit fee line).
- `cust3`'s credit balance is still `$185.01`.
- No new Credit Used email was sent for the $2.99 order.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.7 — Negative check: Allow at Checkout off
**Steps:**
1. As admin, **ArraySubs → Store Credit → Settings → Allow at Checkout** = **Off** and save.
2. As `cust3`, empty cart and add `Standard Weekly` again.
3. Open Cart.
4. Re-enable **Allow at Checkout** before continuing.

**Expected Result:**
- With Allow at Checkout = Off, no credit fee line appears in the cart even though the customer has a balance.
- Customer would be required to pay the full $19.99 via Stripe / manual gateway.
- Remove the cart contents before re-enabling and continuing.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Order's WooCommerce **Order total** shown in admin includes the negative-fee credit line so that the original `$19.99` total stays for reporting.
- DevTools Network: no 4xx / 5xx during checkout.
- After re-enabling Allow at Checkout, confirm 12.06 can still proceed with `cust3` carrying `$185.01`.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Final cust3 balance:
- Notes:
