# Stage 04 — Task 01: Mixed Cart Rule

| Key | Value |
|---|---|
| Stage | 04 — Cart Validation Rules |
| Module | Subscription Checkout / Mixed Cart |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | Stage 03 catalog |

## Objective
Disable the **Allow mixed cart** General Settings toggle and confirm that adding a regular WooCommerce product to a cart already containing a subscription (or vice versa) is blocked with the exact error message:

> `This order cannot contain subscription and regular products together.`

## Pre-conditions
- `Standard Weekly` ($19.99/wk subscription, created in Stage 03.01) and `Plain Mug` (regular product) exist.
- Cart is empty.
- Logged-in test customer or guest with checkout allowed (rule applies regardless).

## Test Data
- Setting path: `ArraySubs → Settings → General Settings → Multiple Subscriptions → Allow mixed cart`.
- Default: Enabled (must be disabled for this test).
- Expected error string (verbatim): `This order cannot contain subscription and regular products together.`

## Sub-Tasks

### Sub-Task 01.1 — Disable Allow Mixed Cart
**Steps:**
1. Go to **ArraySubs → Settings → General Settings**.
2. Locate the **Multiple Subscriptions** section.
3. Toggle **Allow mixed cart** to OFF / disabled.
4. Click **Save changes**.

**Expected Result:**
- Setting saves; reload confirms it remains OFF.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.2 — Subscription first, then regular product
**Steps:**
1. Empty the cart.
2. Open `Standard Weekly` storefront page and click **Add to cart**.
3. Open `Plain Mug` storefront page and click **Add to cart**.

**Expected Result:**
- WooCommerce notice appears at the top of the page (or as a Store API error in AJAX) with the exact text:
  > `This order cannot contain subscription and regular products together.`
- The regular product is NOT added to the cart.
- The cart still contains only `Standard Weekly`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.3 — Regular first, then subscription
**Steps:**
1. Empty the cart.
2. Add `Plain Mug` to the cart first.
3. Open `Standard Weekly` and click **Add to cart**.

**Expected Result:**
- The same error message blocks the addition:
  > `This order cannot contain subscription and regular products together.`
- Cart still contains only `Plain Mug`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.4 — Cart-page revalidation
**Steps:**
1. Empty the cart.
2. Add `Standard Weekly` via direct URL/test-link.
3. Manually navigate to the cart page (`/cart/`).
4. Try to apply the URL `/?add-to-cart=<plain_mug_id>`.

**Expected Result:**
- The mixed-cart rule fires from the cart-page validation hook as well, not only the add-to-cart hook.
- Same exact error string is shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.5 — Re-enable and confirm rule clears
**Steps:**
1. Empty the cart.
2. Re-enable **Allow mixed cart** in settings and save.
3. Add `Standard Weekly` and `Plain Mug` to the same cart.

**Expected Result:**
- Both items are accepted into the cart with no error.
- Cart shows two distinct line items.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.6 — Restore default
**Steps:**
1. Confirm **Allow mixed cart** is back to its default (Enabled).
2. Empty the cart.

**Expected Result:**
- Setting restored.
- Cart empty.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- The same setting is also enforced on Block Checkout (verified in Stage 04 Task 07).
- Per manual *Gateway Cart Restrictions (Pro)*: if any enabled gateway does not support mixed carts, this rule may apply even when the setting is enabled. Note this in the test record if Automatic Payments Pro is active.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
