# Stage 12 — Task 04: Buy Credits Product

| Key | Value |
|---|---|
| Stage | 12 — Store Credit (Pro) |
| Module | Store Credit — Purchase Product |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | 12.01, 12.02 |

## Objective
Create two Store Credit products — one fixed-amount and one custom-amount — both with a **10% bonus**. A customer purchases each variant and we verify their balance increases by `purchase + bonus`, the **Credit Added** email arrives with source `Credit Purchase`, the order shows the credit grant note, and the min/max validation on custom amounts blocks invalid entries.

## Pre-conditions
- 12.01 complete; **Enable Credit Purchases** is **On** with min `10`, max `500`, default `50`.
- `cust3@test.local` baseline balance `$40.00` (from 12.02).
- BACS or Cash on Delivery is enabled. Stripe test mode is fine if available.

## Test Data
- **Product A — `Credit Pack $50` (Fixed)**:
  - Product type: Store Credit
  - Credit Amount Type: Fixed
  - Credit Amount: `50.00`
  - Bonus Percentage: `10`
  - Regular Price: `45.00` (customer pays $45, receives $55 credit — base $50 + 10% bonus)
- **Product B — `Custom Credit` (Custom)**:
  - Product type: Store Credit
  - Credit Amount Type: Custom
  - Bonus Percentage: `10`
- Customer purchase of Product B: amount entered = `$100`, expected credit granted = `$110`.

## Sub-Tasks

### Sub-Task 4.1 — Create the Fixed credit product
**Steps:**
1. **WP-Admin → Products → Add New**.
2. Title: `Credit Pack $50`.
3. In the **Product data** dropdown choose **Store Credit**.
4. Confirm the **Attributes**, **Linked Products**, **Shipping**, and **Inventory** tabs are hidden.
5. In the **General** tab:
   - **Credit Amount Type** = `Fixed`.
   - **Credit Amount** = `50.00`.
   - **Bonus Percentage** = `10`.
6. Set **Regular price** = `45.00`.
7. Click **Publish**.

**Expected Result:**
- The product publishes with no validation error.
- The standard tabs (Attributes, Linked Products, Shipping, Inventory) are hidden.
- Reload the edit page and confirm all credit fields persist.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Create the Custom credit product
**Steps:**
1. **Products → Add New**.
2. Title: `Custom Credit`.
3. Product type: **Store Credit**.
4. **Credit Amount Type** = `Custom`.
5. **Bonus Percentage** = `10`.
6. Leave Regular Price blank (custom amount drives the price).
7. Click **Publish**.

**Expected Result:**
- The product publishes.
- A note or hint near the price field explains that the price is dynamic for custom credit products.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Verify the storefront page (Fixed)
**Steps:**
1. Open the `Credit Pack $50` permalink in incognito.

**Expected Result:**
- The product page shows the title and description.
- Credit information shows `$50.00` credit will be received (or similar).
- A bonus badge `+10% Bonus` is visible.
- The buy button label is `Buy Credits` (not the default Add to Cart wording).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Verify the storefront page (Custom)
**Steps:**
1. Open the `Custom Credit` permalink.
2. Read the input field for amount.
3. Try entering `5` (below minimum) and click **Buy Credits**.
4. Try entering `9999` (above maximum) and click **Buy Credits**.

**Expected Result:**
- The amount input is pre-filled with the **Default Purchase Amount** (`50`).
- A bonus badge `+10% Bonus` is shown.
- Entering `5` blocks add-to-cart with an error similar to `Amount must be at least $10.00`.
- Entering `9999` blocks add-to-cart with an error similar to `Amount must be at most $500.00`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Customer buys the Fixed credit
**Steps:**
1. Log in as `cust3@test.local`.
2. Open `Credit Pack $50`.
3. Click **Buy Credits**.
4. Go to **Cart**, then **Checkout**.
5. Pay $45 via BACS / COD.
6. As admin, set the resulting order to **Processing** (or **Completed**).

**Expected Result:**
- The cart line item shows `Credit Pack $50` for `$45.00`.
- The order completes without error.
- No subscription is created (Store Credit is a one-time purchase).
- An order note appears confirming the credit grant (e.g., `Granted $55.00 store credit (base $50 + 10% bonus).`).
- `cust3`'s balance increases from `$40.00` to `$95.00`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Customer buys the Custom credit
**Steps:**
1. Open `Custom Credit` as `cust3`.
2. Enter `100` in the amount field.
3. Click **Buy Credits**.
4. Cart line item should show `Custom Credit` priced at `$100.00`.
5. Checkout via BACS / COD; pay `$100`.
6. As admin, set the order to **Processing** / **Completed**.

**Expected Result:**
- Customer pays `$100` — credit granted is `$110` (`$100 + 10% bonus`).
- Order note logs the `$110` credit.
- `cust3`'s balance increases from `$95.00` to `$205.00`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.7 — Verify Credit History entries
**Steps:**
1. **ArraySubs → Store Credit → Credit History**.
2. Filter by Source = `Credit Purchase`.

**Expected Result:**
- Two rows appear: `+$55.00` and `+$110.00` for `cust3`.
- Description shows `Credit Purchase` plus optional order reference.
- Balance After columns show `$95.00` and `$205.00` respectively.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.8 — Verify Credit Added emails arrived
**Steps:**
1. Open `cust3@test.local`'s inbox.
2. Find the two newest **Store Credit Added** emails.

**Expected Result:**
- Two distinct Credit Added emails — one for $55, one for $110.
- Each email's body shows the correct `Credit Added`, `New Balance`, and Source `Credit Purchase`.
- Placeholders rendered (no raw `{customer_name}` etc.).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm sold individually behaviour: try increasing quantity of `Credit Pack $50` in cart — quantity field should be locked at 1.
- Confirm the product is virtual (no shipping fields appear at checkout).
- DevTools Network: no 4xx / 5xx during checkout.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Final cust3 balance:
- Notes:
