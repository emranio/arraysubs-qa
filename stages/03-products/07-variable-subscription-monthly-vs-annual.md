# Stage 03 — Task 07: Variable Subscription — Weekly vs Bi-weekly

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Variable Subscriptions |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 03.01 |

## Objective
Create a variable subscription product `PM Tool` with two variations: a Weekly $9.99 plan and a Bi-weekly (every 2 weeks) $14.99 plan. Verify that each variation owns its subscription configuration independently and that the storefront product page updates pricing dynamically when the customer changes the plan dropdown.

## Pre-conditions
- Stage 03.01 complete.
- Cart empty.
- WooCommerce attribute taxonomy `Plan` does not yet exist (will be created here, or reuse if present).

## Test Data
- Product name: `PM Tool`
- Product type: `Variable product`
- Attribute: `Plan` with terms `Weekly`, `Bi-weekly`
- Variation Weekly: Price `9.99`, Period `Week`, Interval `1`, Trial `0`, Length `0`, Sign-up Fee `0`
- Variation Bi-weekly: Price `14.99`, Period `Week`, Interval `2`, Trial `0`, Length `0`, Sign-up Fee `0`

## Sub-Tasks

### Sub-Task 07.1 — Create the variable parent product
**Steps:**
1. **Products → Add New**. Name `PM Tool`.
2. In Product data dropdown, select **Variable product**.
3. Tick the **Subscription** checkbox.
4. Click **Save draft**.

**Expected Result:**
- Product saves as Variable product.
- Subscription checkbox stays ticked.
- Per manual: per-variation Subscription checkbox will read `(controlled by product-level setting)` once variations are created.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.2 — Add the Plan attribute
**Steps:**
1. Open the **Attributes** tab.
2. Click **Add new** (custom product attribute).
3. Name: `Plan`. Values: `Weekly | Bi-weekly` (pipe-separated).
4. Tick **Used for variations**.
5. Click **Save attributes**.

**Expected Result:**
- Attribute saves; both terms are visible.
- "Used for variations" checkbox remains ticked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.3 — Generate and configure both variations
**Steps:**
1. Open the **Variations** tab.
2. Choose **Generate variations** (or **Create variations from all attributes**) and confirm.
3. Expand the **Weekly** variation:
   - Set Regular price `9.99`.
   - Confirm Subscription checkbox at variation level is disabled / shows `(controlled by product-level setting)`.
   - Set Billing Period `Week`, Interval `1`, Length `0`.
   - Trial Length `0`, Sign-up Fee `0`.
4. Expand the **Bi-weekly** variation:
   - Regular price `14.99`.
   - Billing Period `Week`, Interval `2`, Length `0`.
   - Trial Length `0`, Sign-up Fee `0`.
5. Click **Save changes**.
6. Click **Update** on the parent product.

**Expected Result:**
- Both variations save without validation errors.
- Each variation displays its own complete subscription field set.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.4 — Confirm independence after refresh
**Steps:**
1. Reload the product edit page.
2. Reopen each variation.

**Expected Result:**
- Weekly: $9.99 / Week / Interval 1 / no trial.
- Bi-weekly: $14.99 / Week / Interval 2 / no trial.
- Values are not crossed over between variations.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.5 — Storefront dropdown updates pricing
**Steps:**
1. Open the product permalink in an incognito window.
2. Initially observe the subscription info area before selecting a variation.
3. Select `Weekly` from the Plan dropdown.
4. Switch to `Bi-weekly`.

**Expected Result:**
- Before any selection: subscription info area is empty (per manual: "starts empty" for variable products).
- After selecting Weekly: shows `$9.99 / week`.
- After selecting Bi-weekly: shows `$14.99 every 2 weeks` (or equivalent locale-formatted recurring text).
- Switching back and forth updates the display via JavaScript without page reload.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.6 — Cart and checkout summary per variation
**Steps:**
1. Select Weekly and click **Add to cart**.
2. Open the cart and capture the line metadata.
3. **Proceed to checkout** and confirm subscription summary shows `$9.99 every week` with today's charge `$9.99` and a next charge date 7 days out.
4. Empty the cart.
5. Repeat with Bi-weekly: confirm summary shows `$14.99 every 2 weeks`, today's charge `$14.99`, next charge date 14 days out.

**Expected Result:**
- Each variation's cart and checkout summary uses the correct billing schedule.
- Today's charge equals the variation's recurring price (no trial, no signup fee).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the parent Subscription tab does not show product-level recurring price fields (because variable products defer to variations).
- Empty cart after sub-task 07.6.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
