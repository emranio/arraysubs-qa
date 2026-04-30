# Stage 05 — Task 07: Guest Checkout with Auto-Create OFF

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout — Guest Subscriptions |
| Plugin Coverage | Both |
| Estimated Time | 20 min |
| Depends On | Task 06 |

## Objective
With **Auto-create account for subscription purchases** disabled and WooCommerce guest checkout enabled, confirm that a guest can purchase a subscription, that the subscription is created against the billing email (per the manual: *"if auto-create-account is disabled and WooCommerce allows guest checkout, the subscription is created with the order's billing email"*), and that the customer can later claim the subscription by registering with the same email.

## Pre-conditions
- ArraySubs → Settings → General Settings → Checkout Options:
  - **Auto-create account for subscription purchases**: **Disabled**.
- WooCommerce → Settings → Accounts & Privacy:
  - **Allow customers to place orders without an account**: Enabled.
  - **Allow customers to create an account during checkout**: Enabled (but optional, not forced).
- New email reserved for this task: `guest-claim@example.test` (not yet registered).
- Manual gateway (Direct bank transfer) is enabled.

## Test Data
- Product: `Basic Monthly` ($29.99 / month).
- Guest email: `guest-claim@example.test`.

## Sub-Tasks

### Sub-Task 7.1 — Disable auto-create account
**Steps:**
1. As Administrator, open **ArraySubs → Settings → General Settings → Checkout Options**.
2. Disable **Auto-create account for subscription purchases**. Save.
3. Reload the settings page and confirm the toggle is off.

**Expected Result:**
- The toggle is off and persisted.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.2 — Place a guest checkout
**Steps:**
1. Open Incognito (no login).
2. Add `Basic Monthly` to cart.
3. Click **Proceed to checkout**.
4. In the billing email field enter `guest-claim@example.test`.
5. Fill the rest of the billing details.
6. Confirm there is **no forced account-creation block** — either no "Create an account?" toggle is shown, or it can be left unchecked.
7. Select Direct bank transfer.
8. Click **Place order**.

**Expected Result:**
- The order is placed successfully without forcing registration.
- The customer is taken to the order received page as a guest (the My Account header item still says "My Account" / login, not the customer's name).
- The Related Subscriptions table is shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.3 — Verify the subscription is linked to the billing email
**Steps:**
1. As Administrator, open **ArraySubs → Subscriptions**.
2. Locate the new subscription created from this checkout.
3. Open **View Details** and read the Customer Information card.

**Expected Result:**
- The subscription was created.
- Customer Email row shows `guest-claim@example.test`.
- Customer Name shows the billing first/last name entered at checkout.
- The subscription's customer field internally references the order's billing email, even though no WordPress user account exists yet.
- No `User ID 0` warning or broken-link icon appears in admin (the customer-portal access may be limited until they register, but the admin record is intact).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.4 — Verify the user does NOT yet exist in Users
**Steps:**
1. Open **Users → All Users**.
2. Search for `guest-claim@example.test`.

**Expected Result:**
- No user with that email exists.
- Confirms the guest checkout did not silently create an account when auto-create is disabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.5 — Customer registers later with the same email
**Steps:**
1. Log out / open a fresh Incognito window.
2. Go to **My Account** registration form (or `Register` link).
3. Register with email `guest-claim@example.test` and password `Test1234!Claim`.
4. Complete registration.

**Expected Result:**
- Registration succeeds.
- The new user is logged in.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.6 — Confirm the subscription is now visible to the registered customer
**Steps:**
1. As the just-registered `guest-claim@example.test`, click **My Account → Subscriptions**.
2. Confirm the subscription bought as a guest appears in the list.
3. Click **View** on it.

**Expected Result:**
- The subscription appears in the customer's My Subscriptions table with the correct product, status, next payment date, and recurring total.
- The detail page renders normally — the subscription has been claimed by the new account because their email matches the billing email of the original order.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.7 — Cross-verify in admin that the subscription is now linked to the user
**Steps:**
1. As Administrator, open **ArraySubs → Subscriptions** and locate the same subscription.
2. Open **View Details** and read the Customer Information card.

**Expected Result:**
- Customer Name on the card now links to the new user's WordPress profile.
- Date Registered shows the timestamp of the registration step (Sub-Task 7.5), proving the WordPress account is attached.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Restore **Auto-create account for subscription purchases** to **Enabled** at the end of this task so subsequent tasks (e.g., 06 in retesting) behave as expected.
- Confirm the order's billing email matches exactly across the order, the subscription, and the new user account.
- Per the manual FAQ: *"Yes. If auto-account creation is disabled and WooCommerce allows guest checkout, the subscription is created with the order's billing email. The customer can claim the subscription later by creating an account with that same email."* Both halves of this guarantee must be confirmed.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order ID created:
- Subscription ID created:
- Notes:
