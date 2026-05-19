# Stage 03 — Task 14: Coupon — Apply to Subscriptions Toggle

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Coupon Integration |
| Plugin Coverage | Free |
| Estimated Time | 20 min |
| Depends On | 03.01 |

## Objective
Create two WooCommerce coupons — one with **Apply to subscriptions** enabled and one without — and confirm that only the eligible coupon discounts a subscription product at checkout. Validates the gating between standard WooCommerce coupons and ArraySubs subscription coupons.

## Pre-conditions
- Stage 03.01 complete (`Standard Weekly` available — created alongside `Basic Monthly` in Sub-Task 01.7).
- WooCommerce Coupons enabled in **WooCommerce → Settings → General → Enable coupons**.
- Cart empty.

## Test Data
- Coupon `WELCOME15` — Percentage 15%, **Apply to subscriptions** ON, Discount duration `One-time`.
- Coupon `NOSUB10` — Percentage 10%, **Apply to subscriptions** OFF.
- Subject product: `Standard Weekly` ($19.99/wk).

## Sub-Tasks

### Sub-Task 14.1 — Locate the ArraySubs coupon panel
**Steps:**
1. Go to **Marketing → Coupons** (or **WooCommerce → Coupons**) → **Add coupon**.
2. Scroll past the standard tabs to find the **ArraySubs Subscription Settings** section on the coupon edit page.

**Expected Result:**
- The ArraySubs Subscription Settings section is visible below or alongside the standard WooCommerce coupon settings.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.2 — Create `WELCOME15` (subscription-eligible)
**Steps:**
1. Coupon code: `WELCOME15`.
2. Discount type: `Percentage discount`.
3. Coupon amount: `15`.
4. In **ArraySubs Subscription Settings**: tick **Apply to subscriptions**.
5. Set **Discount duration** to `One-time`.
6. Click **Publish**.

**Expected Result:**
- Coupon publishes.
- The "Number of renewal cycles" and "Count initial checkout" fields are hidden because duration is One-time.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.3 — Create `NOSUB10` (NOT subscription-eligible)
**Steps:**
1. **Marketing → Coupons → Add coupon**.
2. Code: `NOSUB10`. Type: Percentage. Amount: `10`.
3. Leave **Apply to subscriptions** unticked.
4. Click **Publish**.

**Expected Result:**
- Coupon publishes with no ArraySubs subscription metadata enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.4 — Apply `NOSUB10` to a subscription cart
**Steps:**
1. Open `Standard Weekly` and add to cart.
2. Open the cart. Enter coupon code `NOSUB10` and click Apply.
3. Observe the cart totals.

**Expected Result:**
- Per manual: regular WooCommerce coupons "work as a one-time checkout discount" — `NOSUB10` should still apply to the initial checkout total (10% off the $19.99 line). It just is **not** captured for renewals.
- Confirm the discount line shows `Coupon: nosub10` `-$2.00` (or thereabouts; 10% of $19.99 ≈ $1.999).
- The subscription will not retain `NOSUB10` for future renewals (validated in stage 06 / 07 once we run renewals; this task only confirms the initial behaviour).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.5 — Replace with `WELCOME15`
**Steps:**
1. Remove `NOSUB10` from the cart.
2. Apply coupon `WELCOME15`.

**Expected Result:**
- Discount line shows `Coupon: welcome15` `-$3.00` (15% of $19.99 = $2.9985 → $3.00 with rounding).
- New subtotal `$16.99` (subject to rounding rules).
- No errors.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.6 — Try applying both coupons together
**Steps:**
1. With `WELCOME15` already applied, also try to apply `NOSUB10`.
2. Observe behaviour.

**Expected Result:**
- WooCommerce default behaviour governs whether multiple coupons stack. Both may apply to the initial order.
- ArraySubs side effect: per manual, "Only one subscription coupon per order is captured" — so only `WELCOME15` would be stored on the resulting subscription. `NOSUB10` is irrelevant to renewals because it lacks the subscription flag.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 14.7 — Negative check: subscription-restricted coupon on a regular product
**Steps:**
1. Create a quick non-subscription test product (Simple, $10) named `Plain Mug` if no existing regular product is available.
2. Add `Plain Mug` to a fresh cart.
3. Apply `WELCOME15`.

**Expected Result:**
- The coupon discount applies as a normal WooCommerce coupon at checkout (the "Apply to subscriptions" flag does not block usage on regular products; it just controls subscription capture).
- No error message.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Empty cart and remove coupons at end.
- Keep `WELCOME15` and `NOSUB10` published; downstream stages re-use them.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
