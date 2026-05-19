# Stage 03 — Task 15: Recurring Coupon with Cycle Limit

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Coupon Integration / Recurring Discount |
| Plugin Coverage | Free |
| Estimated Time | 20 min |
| Depends On | 03.14 |

## Objective
Create a recurring coupon `HALFOFF3` that applies a 50% discount for 3 cycles with **Count initial checkout** enabled. Apply it to `Standard Weekly` at checkout and confirm the cart and checkout summary reflect the recurring nature, the cycle count, and the captured-on-subscription messaging.

## Pre-conditions
- Stage 03.14 complete.
- `Standard Weekly` exists.
- Cart empty.

## Test Data
- Coupon `HALFOFF3`:
  - Discount type: Percentage discount.
  - Amount: `50`.
  - Apply to subscriptions: ON.
  - Discount duration: `Recurring`.
  - Number of renewal cycles: `3`.
  - Count initial checkout: ON.

## Sub-Tasks

### Sub-Task 15.1 — Create the recurring coupon
**Steps:**
1. **Marketing → Coupons → Add coupon**.
2. Code `HALFOFF3`. Description optional: `First 3 cycles 50% off`.
3. Discount type `Percentage discount`. Coupon amount `50`.
4. In **ArraySubs Subscription Settings**:
   - Tick **Apply to subscriptions**.
   - Set **Discount duration** to `Recurring`.
   - Wait for the **Number of renewal cycles** field to appear; enter `3`.
   - Wait for the **Count initial checkout** checkbox to appear; tick it.
5. Click **Publish**.

**Expected Result:**
- All four fields are visible and saved as configured.
- Reload the coupon and confirm persistence.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 15.2 — Field gating behaviour
**Steps:**
1. Edit `HALFOFF3`. Toggle Discount duration to `One-time`.
2. Confirm the Number of renewal cycles and Count initial checkout fields hide.
3. Toggle back to `Recurring`. Set cycles to `0` and observe Count initial checkout visibility.
4. Restore cycles to `3` and Count initial checkout to ticked. Save.

**Expected Result:**
- Cycle and Count Initial fields are visible only when duration is Recurring.
- Count initial checkout is visible only when duration is Recurring AND cycles > 0 (per manual).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 15.3 — Apply at checkout
**Steps:**
1. Add `Standard Weekly` to cart.
2. Open the cart and apply `HALFOFF3`.
3. Confirm the discount line.

**Expected Result:**
- Cart subtotal shows the recurring price `$19.99`.
- A discount line `Coupon: halfoff3` shows `-$10.00` (50% of $19.99 ≈ $9.995 → $10.00 with rounding).
- New cart total approximately $9.99 + tax.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 15.4 — Verify cycle messaging in cart / checkout summary
**Steps:**
1. Proceed to checkout.
2. Inspect the order review, the discount line, and the subscription summary block beneath the totals.

**Expected Result:**
- Discount line still applied.
- Subscription summary indicates the coupon will apply to the configured cycles. Acceptable formats include:
  - `HALFOFF3 — 50% off for 3 cycles (initial counted)`, or
  - `Recurring discount: 50% — 3 of 3 cycles remaining`.
- Today's charge equals `$9.99` (after discount), not the full `$19.99`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 15.5 — Behavioural cross-check (documentation only)
**Steps:**
1. Read the manual cycle counting tables and confirm the test will exhaust at the 2nd renewal:
   - Initial counted → starts at cycle 2 of 3 remaining.
   - 1st renewal → 1 of 3 remaining.
   - 2nd renewal → 0 of 3 remaining (last discounted).
   - 3rd renewal onward → no discount.
2. With weekly billing, the 3rd renewal lands ~3 weeks from start — short enough to time-travel during this regression cycle. Stage 06 / 07 will exercise it.

**Expected Result:**
- Tester records the expected lifetime (3 discounted payments total: 1 initial + 2 renewals) for later verification.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 15.6 — Negative check: switching count-initial off
**Steps:**
1. Untick **Count initial checkout** on `HALFOFF3`. Save.
2. Re-add `Standard Weekly` to a fresh cart and reapply the coupon.
3. Re-read the subscription summary.

**Expected Result:**
- With Count initial off, total discounted payments expected = 4 (1 initial + 3 renewals) per manual.
- Restore Count initial back to ticked at end of test for downstream consistency.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Empty cart at end.
- Confirm `HALFOFF3` still has cycles `3` and Count initial ON for stage 06.
- Confirm `WELCOME15` (one-time) still publishes correctly without the recurring fields.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
