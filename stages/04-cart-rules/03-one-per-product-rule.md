# Stage 04 — Task 03: One Subscription Per Product Rule

| Key | Value |
|---|---|
| Stage | 04 — Cart Validation Rules |
| Module | Subscription Checkout / One Per Product |
| Plugin Coverage | Free |
| Estimated Time | 10 min |
| Depends On | Stage 03 catalog |

## Objective
Enable **One subscription per product** and confirm that attempting to set quantity > 1 for any subscription line item is blocked with the exact error:

> `Each subscription product can only appear once per order. Reduce the quantity to 1.`

## Pre-conditions
- `Standard Weekly` exists.
- Cart empty.

## Test Data
- Setting path: `ArraySubs → Settings → General Settings → Multiple Subscriptions → One subscription per product`.
- Default: Disabled.
- Expected error string (verbatim): `Each subscription product can only appear once per order. Reduce the quantity to 1.`

## Sub-Tasks

### Sub-Task 03.1 — Enable the setting
**Steps:**
1. **ArraySubs → Settings → General Settings**.
2. Toggle **One subscription per product** to ON.
3. Save.

**Expected Result:**
- Setting saved.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.2 — Try quantity 2 from the cart page
**Steps:**
1. Empty cart.
2. Add `Standard Weekly` once.
3. Open the cart page.
4. Change the quantity field from `1` to `2` and click **Update cart** (or apply the AJAX update).

**Expected Result:**
- Update is rejected with the exact text:
  > `Each subscription product can only appear once per order. Reduce the quantity to 1.`
- Quantity reverts to `1` (or remains `1` if blocked pre-update).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.3 — Try quantity 2 directly via add-to-cart URL
**Steps:**
1. Empty cart.
2. Visit `/?add-to-cart=<standard_weekly_id>&quantity=2`.

**Expected Result:**
- Same error string is shown.
- Cart contains either no line item or a single quantity of 1 (whichever the implementation does at add time).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.4 — Two distinct subscription products still allowed
**Steps:**
1. Empty cart.
2. Ensure **Allow multiple subscriptions in cart** is enabled.
3. Add `Standard Weekly` (qty 1) and `Trial Weekly` (qty 1).

**Expected Result:**
- Cart accepts both as separate line items at quantity 1 each.
- "One per product" rule applies per product, not across products (per manual: "prevents buying more than 1 quantity of any single subscription product in a single order but allows different products").

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.5 — Disable the setting and confirm rule clears
**Steps:**
1. Empty cart.
2. Disable **One subscription per product** and save.
3. Add `Standard Weekly` with quantity 2 (URL or cart-page update).

**Expected Result:**
- No error, line item shows quantity 2.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.6 — Restore default
**Steps:**
1. Confirm **One subscription per product** is set back to its default (Disabled).
2. Empty cart.

**Expected Result:**
- Setting restored, cart empty.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-test Sub-Task 03.2 against the Block Checkout in Stage 04.07 if time permits.
- Confirm error appears as a Store API cart error (not a 500) when triggered via REST.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
