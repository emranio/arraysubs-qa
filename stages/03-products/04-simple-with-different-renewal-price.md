# Stage 03 — Task 04: Simple Subscription with Different Renewal Price

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Different Renewal Price |
| Plugin Coverage | Free |
| Estimated Time | 20 min |
| Depends On | 03.01 |

## Objective
Create a `Stepped Weekly` subscription priced at $19.99 for the first 3 billing cycles, then $29.99/week afterwards. Verify the Different Renewal Price fields persist, that the storefront product page shows `for the first 3 payments, then $29.99 / week`, and the checkout summary reflects the tier.

## Pre-conditions
- Stage 03.01 complete.
- Cart empty.

## Test Data
- Product name: `Stepped Weekly`
- Regular price: `19.99`
- Billing Period: `Week`, Interval: `1`, Length: `0`
- Different Renewal Price: enabled
- Renewal Price: `29.99`
- Apply Renewal Price After: `3`

## Sub-Tasks

### Sub-Task 04.1 — Create product with tiered pricing
**Steps:**
1. **Products → Add New**.
2. Name: `Stepped Weekly`. Regular price: `19.99`.
3. Tick **Subscription**.
4. Open **Subscription** tab; set Billing Period `Week`, Interval `1`, Length `0`.
5. Tick **Different Renewal Price**.
6. In the now-revealed fields, set Renewal Price `29.99` and Apply Renewal Price After `3`.
7. Click **Publish**.

**Expected Result:**
- Save succeeds without validation errors.
- Renewal Price and Apply After fields remain visible after the checkbox is ticked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.2 — Confirm persistence after refresh
**Steps:**
1. Reload the edit screen.
2. Reopen the **Subscription** tab.

**Expected Result:**
- Different Renewal Price still ticked.
- Renewal Price `29.99`, Apply After `3` still set.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.3 — Verify product page tier text
**Steps:**
1. Open the storefront product page in an incognito window.
2. Read the recurring price area.

**Expected Result:**
- Display text matches `$19.99 / week for the first 3 payments, then $29.99 / week` (per the user manual reference row).
- No signup fee or trial copy is visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.4 — Verify cart tier display
**Steps:**
1. Add to cart and open the cart page.
2. Inspect the line item subtotal and metadata.

**Expected Result:**
- Item subtotal mentions both prices (`$19.99` initial, `$29.99` after threshold) along with the `3` cycle threshold.
- No `Subscription Signup Fee` line.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.5 — Verify checkout summary text
**Steps:**
1. **Proceed to checkout**.
2. Read the subscription summary block under the order total.

**Expected Result:**
- Summary contains a "Different renewal price tiers" line equivalent to `$19.99 weekly for the first 3 payments, then $29.99 weekly` (matching the manual's example string).
- Today's charge equals `$19.99` (no signup fee, no trial).
- Next charge date approximately one week away. The 4th renewal (after 3 cycles at the initial price) lands 3 weeks out, not 3 months — confirms the stepped threshold uses the product's billing period.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.6 — Negative check: untick to hide fields
**Steps:**
1. Return to the product edit screen.
2. Open Subscription tab and untick **Different Renewal Price**.
3. Confirm the Renewal Price and Apply After fields collapse (hidden) and click **Update**.
4. Refresh and reopen the tab; reset the checkbox to enabled with `29.99` / `3` so the rest of the suite can rely on this product.

**Expected Result:**
- When unticked, Renewal Price + Apply After fields are hidden.
- After re-enabling and saving, original tiered config is restored.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Empty the cart at the end.
- Confirm that toggling the checkbox does not erase the previously entered values prematurely (only after Save).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
