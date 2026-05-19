# Stage 05 — Task 08: One-Click Checkout

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout — One-Click Checkout |
| Plugin Coverage | Both |
| Estimated Time | 20 min |
| Depends On | Task 01 |

## Objective
Verify the "One-Click Checkout" feature in two modes:
1. **Subscription items** — clicking the product page CTA on a subscription product clears the cart and sends the customer straight to checkout.
2. **All items** — every product (subscription and regular) skips the cart and lands on checkout.
Confirm the CTA button text honors the configured **Add to Cart Text** (default `Subscribe Now`) and the **Non-Subscription Button Text** label.

## Pre-conditions
- Customer `customer-oneclick@example.test` exists.
- ArraySubs → Settings → General Settings:
  - **Button Text → Add to Cart Text**: `Subscribe Now` (default).
  - **Button Text → Non-Subscription Button Text**: `Buy Now` (or store-configured value — record exact value in Sign-off).
- ArraySubs → Settings → General Settings → Checkout Options:
  - **One-Click Checkout Mode**: starts at `Default`. Will be changed during this task.
  - **Disable cart page**: Off (toggled on during Sub-Task 8.5).
- Product `Basic Monthly` is published.
- Product `Standard Tee` (regular product, $15.00) is published.

## Test Data
- Subscription product: `Basic Monthly`.
- Regular product: `Standard Tee`.
- Customer: `customer-oneclick@example.test`.

## Sub-Tasks

### Sub-Task 8.1 — Set One-Click Checkout to "Subscription items"
**Steps:**
1. As Administrator, open **ArraySubs → Settings → General Settings → Checkout Options**.
2. Set **One-Click Checkout Mode** to `Subscription items`.
3. Save settings and reload the page to confirm the value persists.

**Expected Result:**
- The dropdown / radio shows `Subscription items` selected.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.2 — Confirm CTA wording on subscription product page
**Steps:**
1. In Incognito, log in as `customer-oneclick@example.test`.
2. Navigate to the `Basic Monthly` single product page.
3. Inspect the add-to-cart button text.

**Expected Result:**
- Button text reads exactly `Subscribe Now`.
- The subscription summary or price display below the button still shows `$29.99 / month`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.3 — Click "Subscribe Now" and verify direct redirect
**Steps:**
1. Pre-load some unrelated product into the cart first (open another tab and add `Standard Tee` to cart, but do NOT proceed to checkout).
2. Return to the `Basic Monthly` product page.
3. Click **Subscribe Now**.
4. Watch the page transition closely.

**Expected Result:**
- The page performs a full redirect (not an AJAX add-to-cart) directly to the checkout page.
- The checkout page shows ONLY `Basic Monthly` in the order review — `Standard Tee` is no longer in the cart (per manual: *"The cart is cleared — only the clicked product remains"*).
- No intermediate cart page is shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.4 — Confirm regular product still uses cart in "Subscription items" mode
**Steps:**
1. Stay logged in. Empty the cart if anything is left.
2. Open the `Standard Tee` (regular) product page.
3. Inspect the add-to-cart button text.
4. Click the button.

**Expected Result:**
- Button text is the default `Add to cart` (or store theme equivalent), NOT `Buy Now`.
- Clicking it adds the product via standard WooCommerce flow (AJAX or redirect to cart) — it does NOT skip to checkout.
- The customer is taken to the cart page or an "Added to cart" notice — not the checkout directly.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.5 — Enable "Disable cart page" and verify cart redirect
**Steps:**
1. As Administrator, in **General Settings → Checkout Options** enable **Disable cart page**. Save.
2. As the customer, empty the cart, then open `Basic Monthly` and click **Subscribe Now** to land on checkout.
3. Now manually navigate to the cart page URL (e.g., `/cart/`).

**Expected Result:**
- Visiting the cart URL redirects directly to the checkout page (per manual: *"any attempt to visit the cart page sends the customer to checkout instead"*).
- The cart page itself is never rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.6 — Switch to "All items" mode
**Steps:**
1. As Administrator, change **One-Click Checkout Mode** to `All items`.
2. Disable **Disable cart page** (turn it back off so the next test isn't affected). Save.
3. Reload the customer's storefront window.

**Expected Result:**
- Setting is saved and visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.7 — Verify regular product CTA changes in "All items" mode
**Steps:**
1. As the customer, open the `Standard Tee` product page.
2. Inspect the button text.
3. Click the button.

**Expected Result:**
- Button text now reads `Buy Now` (or whatever was configured in **Non-Subscription Button Text**).
- Clicking it performs a full redirect directly to checkout (skipping the cart page).
- The checkout shows only `Standard Tee` — anything previously in the cart was cleared.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.8 — Reset to default mode
**Steps:**
1. As Administrator, switch **One-Click Checkout Mode** back to `Default`.
2. Save.
3. As the customer, reload the shop and confirm products use the standard WooCommerce add-to-cart flow again.

**Expected Result:**
- Subscription product CTA returns to `Subscribe Now` text but uses standard WooCommerce add-to-cart (the redirect-to-checkout behavior is gone).
- Regular product CTA returns to `Add to cart` and adds via AJAX or normal cart flow.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm AJAX add-to-cart does NOT fire on a subscription product page in any one-click mode (per manual: *"AJAX add-to-cart is automatically disabled for one-click products"*).
- Confirm there are no JavaScript console errors on the product page in any of the three modes.
- After completing checkout once during this task, confirm a real subscription was created at the end (use the Subscriptions list to verify).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Final mode left in settings:
- Notes:
