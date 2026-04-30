# Stage 07 — Task 10: Payment Method, Auto-Renew Toggle, and Shipping

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / Payment & Shipping |
| Plugin Coverage | Pro (auto-renew toggle, card-on-file); Free (manage payment methods link, shipping update) |
| Estimated Time | 25 min |
| Depends On | 02 — View Subscription detail |

## Objective
Verify the payment-method controls (Manage payment methods link, Pro card-on-file details, Update payment method link, auto-renew toggle off then on) and the shipping address update flow including the 3-day cutoff that blocks updates close to the next renewal.

## Pre-conditions
- ArraySubs Pro active. Stripe is already configured by the developer in test mode (no setup required). PayPal and Paddle are out of scope for this regression cycle.
- **General Settings → Automatic Payments → Allow Auto-Renew Toggle:** Enabled.
- `cust1@example.com` owns:
  - Subscription K — Active subscription paid via Stripe (auto gateway), next payment > 7 days away.
  - Subscription L — Active subscription on a physical product with next payment more than 7 days away.
  - Subscription M — Active subscription on a physical product whose next payment date is set to **2 days from today** (manually adjusted in admin to test the 3-day cutoff).

## Test Data
- Stripe sandbox card for replacement: `4000 0000 0000 0077` (or any other test card ending different from current)

## Sub-Tasks

### Sub-Task 10.1 — Manage payment methods link
**Steps:**
1. As `cust1@example.com`, open Subscription K.
2. Locate the Payment Method row in the overview table.
3. Click **Manage payment methods**.

**Expected Result:**
- The link navigates to **My Account → Payment methods** (URL `/my-account/payment-methods/`).
- The page lists the customer's saved payment methods.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.2 — Card on file details (Pro)
**Steps:**
1. Return to Subscription K's detail page.
2. Look directly below the Payment Method row.

**Expected Result:**
- Card brand and last 4 digits visible (e.g. **"Visa ending in 4242"**).
- Expiry date shown.
- Gateway name shown (e.g. **Stripe**).
- An **Update payment method** link is visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.3 — Update payment method redirect
**Steps:**
1. Click **Update payment method**.

**Expected Result:**
- The browser is redirected to the Stripe-hosted update session (URL host contains `stripe.com`).
- After entering the new test card and completing the session, the browser is redirected back to the subscription detail page.
- The card details on the page now reflect the new card's last 4 digits.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.4 — Turn auto-renew off
**Steps:**
1. Below the card details on Subscription K, locate the auto-renew toggle.
2. Click the toggle (currently labeled **On**).
3. Confirm any prompt.

**Expected Result:**
- The toggle switches to **Off**.
- The status text reads: **"You will receive an invoice for manual payment at your next renewal."**
- A subscription note records the change to manual billing.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.5 — Turn auto-renew back on with valid payment method
**Steps:**
1. Click the toggle again.
2. Confirm any prompt.

**Expected Result:**
- The toggle returns to **On**.
- The status text reads: **"Your subscription will be renewed automatically."**
- No error appears (the payment method is still on file and valid).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.6 — Update Shipping Address (within cutoff window)
**Steps:**
1. Open Subscription L (next payment > 7 days away).
2. Scroll to the Shipping Address section.
3. Click **Update Shipping Address**.
4. In the form modal, change the **Street Address** to a new value (e.g. add ", Apt 5" suffix).
5. Click **Save Address**.

**Expected Result:**
- A modal opens with all required and optional fields pre-filled (First Name, Last Name, Company, Street Address, Apartment/Suite, City, State/Province, Postcode/ZIP, Country, Phone).
- After Save, a success notice confirms the address was saved.
- The Shipping Address section now displays the new address.
- A subscription note records the address change.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.7 — Update Shipping Address blocked within 3-day cutoff
**Steps:**
1. Open Subscription M (next payment is 2 days away).
2. Scroll to the Shipping Address section.

**Expected Result:**
- The current shipping address is displayed.
- The **Update Shipping Address** button is **hidden** (not just disabled).
- A message is displayed explaining the address cannot be changed this close to the next renewal.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.8 — Auto-renew toggle hidden where it should be
**Steps:**
1. Find a Cancelled subscription belonging to `cust1@example.com`.
2. Open its detail page.

**Expected Result:**
- The auto-renew toggle is **not** visible (it requires Active / On-Hold / Trial status).
- The card-on-file details may still appear in muted form for record purposes only.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the WooCommerce Order corresponding to the next renewal of Subscription K reflects the new card after the update (when generated).
- The auto-renew off state is reflected on the admin subscription detail screen as "Manual" billing.
- Verify changing shipping country on Subscription L does not affect already-placed orders, only future renewal orders.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
