# Stage 05 — Task 06: Auto-Create Account as Guest

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout — Auto-Create Account |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | Task 01 |

## Objective
With **Auto-create account for subscription purchases** enabled, confirm that an unauthenticated (guest) buyer is forced to use the WooCommerce registration form at checkout when their cart contains a subscription product, that the account is created during checkout, and that the resulting subscription is linked to the new user account. The cart for this test uses `Standard Weekly` so the new subscription record is on a weekly schedule.

## Pre-conditions
- ArraySubs → Settings → General Settings → Checkout Options:
  - **Auto-create account for subscription purchases**: Enabled.
- WooCommerce → Settings → Accounts & Privacy:
  - **Allow customers to place orders without an account**: Enabled (so that the auto-create logic is what blocks guest checkout, not a global WooCommerce setting).
  - **Allow customers to create an account during checkout**: Enabled.
- A brand-new email address is reserved for this test that is **not yet registered**: `guest-auto@example.test`.
- Manual gateway (Direct bank transfer) is enabled.
- Confirm the user `guest-auto@example.test` does NOT exist in **Users → All Users** before starting.

## Test Data
- Product: `Standard Weekly` ($19.99 / week).
- New email: `guest-auto@example.test`.
- Password: `Test1234!Guest` (or whatever the registration form requires).

## Sub-Tasks

### Sub-Task 6.1 — Confirm setting is on
**Steps:**
1. As Administrator, open **ArraySubs → Settings → General Settings → Checkout Options**.
2. Confirm **Auto-create account for subscription purchases** is enabled and saved.

**Expected Result:**
- Setting is enabled and shows green / on state.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.2 — Add subscription as guest
**Steps:**
1. Open a fresh Incognito window — do **not** log in.
2. Navigate to the shop and add `Standard Weekly` to cart.
3. Open the cart page.
4. Click **Proceed to checkout**.

**Expected Result:**
- The checkout page renders.
- A registration / account creation form is shown — fields like "Account password" or "Create an account" are visible and the "Create an account?" toggle is forced ON (it cannot be toggled off, or the toggle is hidden and the form simply requires registration).
- The "Login as existing customer" link is still available for returning customers.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.3 — Try to opt out of account creation (negative test)
**Steps:**
1. Look for any UI control that would allow placing the order as a guest (for example a toggle "Create an account?").
2. Try to uncheck it.
3. Try to submit the form without entering a password.

**Expected Result:**
- The toggle is either hidden or forced on — guest-only checkout is not possible.
- Submitting without registration data triggers a validation error (e.g., "Please enter a password" or "Account creation is required for subscription purchases").

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.4 — Complete registration and place the order
**Steps:**
1. Fill the billing email with `guest-auto@example.test`.
2. Fill the rest of the billing details.
3. In the account creation area, enter the password `Test1234!Guest` (or use the auto-generated password if the option is disabled).
4. Select Direct bank transfer.
5. Click **Place order**.

**Expected Result:**
- The order is placed successfully and the customer is redirected to the order received page.
- The customer is now logged in (the My Account link in the header is visible / a "Hello, guest-auto" greeting appears).
- The Related Subscriptions table is visible on the order page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.5 — Confirm the user account exists and owns the subscription
**Steps:**
1. As Administrator, open **Users → All Users**.
2. Search for `guest-auto@example.test`.
3. Open the user profile and confirm role is **Customer**.
4. Open **ArraySubs → Subscriptions** and find the new subscription.
5. Open **View Details** and read the Customer Information card.

**Expected Result:**
- A new user `guest-auto@example.test` exists with role Customer.
- The Customer Information card shows this email, the customer's first/last name from the billing fields, and a Date Registered timestamp matching today.
- The subscription is linked to this user (not to user ID 0 / guest).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.6 — Confirm the customer can log in and see the subscription
**Steps:**
1. Log out (clear session).
2. Go to **My Account** and log in as `guest-auto@example.test` with password `Test1234!Guest`.
3. Click **Subscriptions** in the My Account menu.

**Expected Result:**
- Login succeeds.
- The Subscriptions tab shows the new subscription with the correct product, status, and next payment date.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- The customer should also receive the standard WooCommerce **New customer** account email (subject usually `Your account has been created on <site>`).
- The customer should also receive the **New Subscription** email once the order is marked Completed.
- Per the manual: *"ArraySubs does not auto-create the account silently — it makes WordPress display the registration form so the customer fills in their desired credentials."* Confirm the customer was prompted, not silently created.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order ID created:
- Subscription ID created:
- New user ID created:
- Notes:
