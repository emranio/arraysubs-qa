# Stage 04 — Task 02: Multiple Subscriptions In Cart

| Key | Value |
|---|---|
| Stage | 04 — Cart Validation Rules |
| Module | Subscription Checkout / Multiple Subscriptions |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | Stage 03 catalog |

## Objective
Disable **Allow multiple subscriptions in cart** and confirm that attempting to add two distinct subscription products to the same cart is blocked with the exact error:

> `Only one subscription plan can be checked out at a time.`

The two subjects are both **weekly** so the test is purely about distinct products, not different billing cycles.

## Pre-conditions
- `Standard Weekly` and `Basic Plan` exist (both $/wk simple subscriptions).
- Cart empty.

## Test Data
- Setting path: `ArraySubs → Settings → General Settings → Multiple Subscriptions → Allow multiple subscriptions in cart`.
- Default: Enabled.
- Expected error string (verbatim): `Only one subscription plan can be checked out at a time.`

## Sub-Tasks

### Sub-Task 02.1 — Disable allow-multiple
**Steps:**
1. **ArraySubs → Settings → General Settings**.
2. Toggle **Allow multiple subscriptions in cart** to OFF.
3. Save.

**Expected Result:**
- Setting saved. Reload to confirm.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.2 — Two distinct subscription products
**Steps:**
1. Empty cart.
2. Add `Standard Weekly` to cart.
3. Add `Basic Plan` to cart.

**Expected Result:**
- The second add is blocked with the exact error string:
  > `Only one subscription plan can be checked out at a time.`
- Cart still contains only `Standard Weekly`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.3 — Reverse order
**Steps:**
1. Empty cart.
2. Add `Basic Plan` first.
3. Add `Standard Weekly` second.

**Expected Result:**
- Same error blocks the second product.
- Cart contains only `Basic Plan`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.4 — Two variations of a variable subscription
**Steps:**
1. Empty cart.
2. Open `PM Tool`, select Weekly variation, **Add to cart**.
3. Open `PM Tool`, select Bi-weekly variation, **Add to cart**.

**Expected Result:**
- The two variations are treated as two distinct subscription line items.
- Same error string blocks the second add.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.5 — Same product, additional quantity (control)
**Steps:**
1. Empty cart.
2. Add `Standard Weekly` once.
3. Add `Standard Weekly` again (same product).

**Expected Result:**
- Cart updates the existing line's quantity to 2 instead of creating a second line.
- The "Only one subscription plan" rule does NOT fire because there is still only one distinct subscription product (the manual: "more than one **distinct** subscription product").
- One Per Product rule may or may not fire depending on its setting (verified in 04.03).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.6 — Re-enable and verify no error
**Steps:**
1. Re-enable **Allow multiple subscriptions in cart** and save.
2. Empty cart.
3. Add `Standard Weekly` and `Basic Plan` to the same cart.

**Expected Result:**
- Both products are added without error.
- Cart shows both line items.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.7 — Restore default and clean up
**Steps:**
1. Confirm setting is back to default (Enabled).
2. Empty cart.

**Expected Result:**
- Setting restored, cart empty.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirmed in Block Checkout in Stage 04.07.
- If Automatic Payments Pro is active and a gateway lacks multi-sub support, the rule may persist even with the setting enabled.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
