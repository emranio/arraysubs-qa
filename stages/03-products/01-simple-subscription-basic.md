# Stage 03 — Task 01: Simple Subscription (Basic Monthly)

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Create and Configure |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | Stage 02 settings complete |

## Objective
Create the canonical "Basic Monthly" simple subscription product priced at $29.99/month and verify that enabling the Subscription checkbox surfaces the Subscription tab, that the billing fields persist, and that the storefront product page renders the recurring price exactly as documented.

This is the **only** monthly-interval product in the catalog. Every other subscription test relies on weekly or daily intervals so renewals can fire inside a real-time regression run; `Basic Monthly` exists to keep calendar-aware month math under coverage.

## Pre-conditions
- Logged in as Administrator or Shop Manager.
- WooCommerce currency set to USD with two decimal places.
- ArraySubs core plugin active.
- No prior product named "Basic Monthly" exists (delete or rename if it does).

## Test Data
- Product name: `Basic Monthly`
- Regular price: `29.99`
- Billing Period: `Month`
- Billing Interval: `1`
- Subscription Length: `0` (never expires)
- Trial Length: `0`
- Sign-up Fee: `0`
- Different Renewal Price: unchecked

## Sub-Tasks

### Sub-Task 01.1 — Open the product editor
**Steps:**
1. From the WordPress admin sidebar, click **Products → Add New**.
2. Enter the product name `Basic Monthly`.
3. Under **Product data**, confirm the type dropdown shows **Simple product**.
4. In the General tab, enter `29.99` in the **Regular price** field. Leave Sale price empty.

**Expected Result:**
- The product editor loads without console errors.
- The Regular price field accepts `29.99`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.2 — Enable the Subscription checkbox
**Steps:**
1. In the **Product data** header (next to Virtual / Downloadable), tick the **Subscription** checkbox.
2. Wait for the product data tabs to refresh.
3. Confirm a new **Subscription** tab appears in the left tab list.

**Expected Result:**
- The Subscription tab is visible alongside General, Inventory, Linked Products, etc.
- No JavaScript errors are emitted in the browser console.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.3 — Configure subscription fields
**Steps:**
1. Click the **Subscription** tab.
2. Confirm the read-only **Recurring Price per Billing Cycle** section shows `$29.99` (matching the General regular price).
3. Set **Billing Period** to `Month`.
4. Set **Billing Interval** to `1`.
5. Set **Subscription Length** to `0`.
6. Leave **Trial Length** at `0` and **Sign-up Fee** at `0`.
7. Confirm **Different Renewal Price** is unchecked.

**Expected Result:**
- All fields accept the input above.
- No inline validation messages appear.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.4 — Publish and reload
**Steps:**
1. Click **Publish**.
2. After save, reload the edit screen via the browser refresh button.
3. Reopen the **Subscription** tab.

**Expected Result:**
- Product saves with no error notices.
- Billing Period is still `Month`, Interval `1`, Length `0`.
- Subscription checkbox is still ticked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.5 — Verify storefront product page
**Steps:**
1. Click **View product** (or open the product permalink in a new tab) while logged out or in an incognito window.
2. Locate the price display under the product title.

**Expected Result:**
- Recurring price displays as `$29.99 / month` (exact format may show currency symbol from store config).
- No trial text, no signup fee text, no "for the first N payments" text.
- "Add to cart" button is visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.6 — Verify shop / catalog listing
**Steps:**
1. Navigate to the **Shop** page (default `/shop/`).
2. Locate the `Basic Monthly` product card.

**Expected Result:**
- Product card price reads `$29.99 / month`.
- Card displays the product image (or placeholder) and Add-to-cart button.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.7 — Create the canonical Standard Weekly product
**Steps:**
1. **Products → Add New** again.
2. Name: `Standard Weekly`. Regular price: `19.99`.
3. Tick **Subscription**.
4. Open **Subscription** tab. Set Billing Period `Week`, Interval `1`, Length `0`. Trial Length `0`, Sign-up Fee `0`.
5. Click **Publish**.
6. Open the product permalink in incognito and confirm price text reads `$19.99 / week`.

**Expected Result:**
- `Standard Weekly` publishes with no validation errors and renders `$19.99 / week` on the storefront.
- This product is the canonical reusable subscription used by most lifecycle, portal, and cart-rule tests in later stages.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm a non-subscription product still saves without showing the Subscription tab.
- Confirm the recurring price text uses the WooCommerce currency symbol as configured in **WooCommerce → Settings → General**.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
