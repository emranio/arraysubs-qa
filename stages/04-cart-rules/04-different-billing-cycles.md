# Stage 04 — Task 04: Different Billing Cycles Rule

| Key | Value |
|---|---|
| Stage | 04 — Cart Validation Rules |
| Module | Subscription Checkout / Different Billing Cycles |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | Stage 03 catalog (Standard Weekly, Basic Monthly, Coffee Plan) |

## Objective
Disable **Allow different billing cycles** and confirm that mixing two subscriptions whose billing periods or intervals differ in the same cart is blocked with the exact error:

> `These subscription plans use different billing cycles.`

The canonical "different billing cycles" pairing for this regression cycle is **Standard Weekly + Basic Monthly** (the only monthly product). A secondary pairing — **Standard Weekly + Coffee Plan Daily** — covers two non-month cadences differing only by period unit.

## Pre-conditions
- `Standard Weekly` (Week / 1) exists.
- `Basic Monthly` (Month / 1) exists — the catalog's sole monthly product.
- `Coffee Plan` Daily variation (Day / 1) exists.
- **Allow multiple subscriptions in cart** is currently ENABLED (so the multi-sub rule is not the one blocking us).
- Cart empty.

## Test Data
- Setting path: `ArraySubs → Settings → General Settings → Multiple Subscriptions → Allow different billing cycles`.
- Default: Enabled.
- Expected error string (verbatim): `These subscription plans use different billing cycles.`

## Sub-Tasks

### Sub-Task 04.1 — Disable the setting
**Steps:**
1. **ArraySubs → Settings → General Settings**.
2. Confirm **Allow multiple subscriptions in cart** is ON.
3. Toggle **Allow different billing cycles** to OFF.
4. Save.

**Expected Result:**
- Both settings saved correctly.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.2 — Weekly + Monthly conflict
**Steps:**
1. Empty cart.
2. Add `Standard Weekly` to the cart.
3. Add `Basic Monthly` to the cart.

**Expected Result:**
- The Monthly add is blocked with the exact text:
  > `These subscription plans use different billing cycles.`
- Cart still contains only `Standard Weekly`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.3 — Reverse order (Monthly first)
**Steps:**
1. Empty cart.
2. Add `Basic Monthly`.
3. Add `Standard Weekly`.

**Expected Result:**
- Same error string blocks the second add.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.4 — Same billing cycle products are allowed
**Steps:**
1. Empty cart.
2. Add `Standard Weekly` (Week/1).
3. Add `Trial Weekly` (Week/1).

**Expected Result:**
- Both products added without error (both are Weekly with interval 1, same cycle).
- Confirms the rule compares billing period + interval, not product identity.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.5 — Daily vs Weekly (two non-month different cycles)
**Steps:**
1. Empty cart.
2. Open `Coffee Plan`, select the **Daily** variation (Day / 1, $2.99), **Add to cart**.
3. Add `Standard Weekly` (Week / 1).

**Expected Result:**
- Same error string fires because the billing period differs (Day vs Week) even though neither side is monthly:
  > `These subscription plans use different billing cycles.`
- Cart still contains only the `Coffee Plan` Daily variation.
- This proves the rule compares `period + interval`, not just "month-vs-non-month".

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.6 — Re-enable and confirm
**Steps:**
1. Re-enable **Allow different billing cycles**. Save.
2. Empty cart.
3. Add `Standard Weekly` and `Basic Monthly`.

**Expected Result:**
- Both items added without error.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.7 — Restore default
**Steps:**
1. Verify **Allow different billing cycles** is back to default (Enabled).
2. Empty cart.

**Expected Result:**
- Setting restored, cart empty.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Block Checkout parity check in Stage 04.07.
- Note any gateway-level capability override per Pro Automatic Payments docs.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
