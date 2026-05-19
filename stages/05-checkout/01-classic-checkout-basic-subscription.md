# Stage 05 — Task 01: Classic Checkout — Standard Weekly Subscription

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout (Classic) |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | Stage 03 product setup, Stage 04 cart rules |

## Objective
Confirm that a logged-in customer can complete a checkout for the `Standard Weekly` subscription product via the Classic (shortcode) WooCommerce checkout, that the order review table renders the full subscription summary block — recurring price, today's charge, next charge date, and the "By completing this purchase, you authorize us to charge..." authorization notice — and that the resulting subscription record is created with the correct status and weekly billing schedule.

## Pre-conditions
- WooCommerce → Settings → Advanced → Page setup uses the **Classic** Checkout shortcode page (not the block-based one).
- ArraySubs → Settings → General Settings:
  - **Allow mixed cart**: Enabled.
  - **Allow multiple subscriptions in cart**: Enabled.
  - **One-click checkout mode**: `Default`.
  - **Auto-create account for subscription purchases**: Enabled (does not affect a logged-in customer but should remain ON).
- Test customer `customer-classic@example.test` exists (from Stage 00). The customer has no prior subscriptions.
- A manual gateway (Direct bank transfer / Cash on delivery) is enabled in **WooCommerce → Settings → Payments**. This task uses a manual gateway so we can assert the order moves to **Processing/Completed** without third-party integration.

## Test Data
- Product: `Standard Weekly` ($19.99 / week, no trial, no signup fee).
- Customer: `customer-classic@example.test`.
- Payment method: Direct bank transfer (manual).
- Today's date placeholder: today (T).
- Expected next payment date: T + 7 days.

## Sub-Tasks

### Sub-Task 1.1 — Add the product to cart from the shop page
**Steps:**
1. Open a clean Incognito/Private window.
2. Log in as `customer-classic@example.test`.
3. Navigate to the shop page and find `Standard Weekly`.
4. Click **Add to cart** on the product card.
5. Watch for the WooCommerce "View cart" / "Added to your cart" confirmation.

**Expected Result:**
- The product is added without any error notice.
- The cart count in the header shows `1`.
- No red banners appear about cart restrictions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Verify the cart page subscription line
**Steps:**
1. Click the cart icon and go to the cart page.
2. Locate the `Standard Weekly` row.
3. Inspect the price column and the cart subtotals area.

**Expected Result:**
- The line item shows the product name `Standard Weekly`.
- The price column shows `$19.99` together with the recurring schedule text (e.g., `$19.99 / week` or `$19.99 every 1 week`).
- The cart total at the bottom shows `$19.99`.
- No error notice about mixed carts or trial restrictions appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Open the Classic Checkout page and inspect the subscription summary
**Steps:**
1. Click **Proceed to checkout**.
2. Confirm the URL points at the Classic Checkout page (the standard `[woocommerce_checkout]` shortcode page).
3. Scroll to the order review table on the right side.
4. Read every line of the subscription summary block under the `Standard Weekly` line item.

**Expected Result:**
- The order review table shows `Standard Weekly` as the product name with quantity `x1`.
- The recurring price text reads `$19.99 every 1 week` (or the manual's exact equivalent: `$19.99 every week`).
- A **Today's charge** line shows `$19.99`.
- A **Next charge** line shows the date for T + 7 days.
- The authorization notice appears verbatim: *"By completing this purchase, you authorize us to charge..."*
- No `Free trial` or `Signup fee` line appears (this product has neither).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Fill billing details and select payment method
**Steps:**
1. Confirm the billing email, first name, last name, and country fields are pre-filled from the customer profile.
2. Fill in the rest of the billing address (street, city, postcode, phone) with valid test data.
3. In the payment methods area, select **Direct bank transfer** (or whichever manual gateway is enabled).
4. Confirm the **Place order** button is enabled.

**Expected Result:**
- All required fields validate without inline errors.
- The selected payment method shows its default description.
- The order review summary still shows the same `$19.99` today total and the next charge date is unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Place the order and confirm the thank-you page
**Steps:**
1. Click **Place order**.
2. Wait for the redirect to the order received / thank-you page.
3. Note the order ID printed at the top of the page.
4. Scroll down to the order details / order summary section.

**Expected Result:**
- Redirect succeeds (no fatal error, no white screen).
- A new order ID is shown.
- The order summary shows `$19.99` total.
- A **Related Subscriptions** table is visible below the order summary with one row containing: subscription ID (linked), a status badge (Active or Pending depending on whether the manual gateway marks the order Processing immediately), the next payment date, and `$19.99 / week` recurring total.
- The page does not show a duplicate subscription row.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Mark the order as Completed and verify subscription activation
**Steps:**
1. In a new tab, log in as Administrator.
2. Open **WooCommerce → Orders** and find the order ID from Sub-Task 1.5.
3. Open the order edit screen and change the status from `Processing` (or `On hold`) to **Completed** (only if the manual gateway didn't already auto-complete).
4. Click **Update**.
5. Switch back to the customer-facing tab and refresh the order page.

**Expected Result:**
- The order status is `Completed`.
- The Related Subscriptions table now shows the subscription with a green **Active** badge.
- The next payment date is T + 7 days.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Cross-check the subscription record in admin
**Steps:**
1. As Administrator, go to **ArraySubs → Subscriptions**.
2. Filter by the **Active** tab.
3. Locate the new subscription belonging to `customer-classic@example.test`.
4. Click **View Details**.
5. Inspect the Subscription Info card and the Billing Information card.

**Expected Result:**
- The subscription is listed with status **Active** (green badge).
- Customer column shows the correct name and email.
- Product column shows `Standard Weekly`.
- Next Payment column shows T + 7 days.
- On the detail screen, the Billing Information card shows `Recurring Amount: $19.99` and `Billing Schedule: Every 1 week(s)`.
- The parent order link points to the order ID from Sub-Task 1.5.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-open the cart page during the same session — confirm the cart is now empty after checkout.
- Re-open the customer's `My Account → Subscriptions` page and confirm the new subscription appears in the customer-facing table with the same next payment date and recurring amount.
- Confirm no PHP notices or warnings appeared in `wp-content/debug.log` during checkout (timestamps within the last 5 minutes).
- Confirm the customer received the **New Subscription** email (this will be re-validated in Stage 06 task 06).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order ID created:
- Subscription ID created:
- Notes:
