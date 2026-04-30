# Stage 05 — Task 04: Trial Checkout — With $4.99 Signup Fee

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout — Trial + Signup Fee |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | Task 03 |

## Objective
Confirm that buying a trial subscription with a non-zero signup fee charges only the signup fee today, displays the signup fee as a separate line item labeled "Subscription Signup Fee", and creates the subscription in `Trial` status (per the manual: *"Trial + signup fee → Today's charge: Signup fee only, Subscription Status: Trial"*). The recurring schedule must be **weekly** so the renewal can be exercised inside this regression cycle.

## Pre-conditions
- Customer `customer-trial-fee@example.test` exists and has no prior subscriptions or trials.
- ArraySubs → Settings → General Settings: same as Task 03 (One trial per customer enabled, Require payment method for trials enabled).
- Manual gateway (Direct bank transfer) is enabled.
- Use `Signup Fee Weekly` ($19.99 / week, $4.99 signup fee) and apply a temporary 7-day trial during this test, OR keep the trial pre-configured on a clone of `Signup Fee Weekly`. The exact path depends on which is already configured in Stage 03 — record what you used in Sign-off.

## Test Data
- Product: `Signup Fee Weekly` ($19.99 / week, 7-day trial added inline, $4.99 signup fee).
- Customer: `customer-trial-fee@example.test`.
- Expected today's charge: `$4.99`.
- Expected next payment date: T + 7 days (trial end date).

## Sub-Tasks

### Sub-Task 4.1 — Add product and review the cart
**Steps:**
1. Log in as `customer-trial-fee@example.test` in Incognito.
2. Add `Signup Fee Weekly` to cart.
3. Open the cart page.

**Expected Result:**
- The cart shows two visible lines for this single product: the recurring `Signup Fee Weekly` line at `$19.99`, plus a `Subscription Signup Fee` line at `$4.99`.
- Cart subtotal / total at the bottom shows `$4.99` (the signup fee — the recurring portion is free during the trial).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Inspect the subscription summary on the checkout page
**Steps:**
1. Click **Proceed to checkout**.
2. Read every line in the subscription summary block of the order review table.

**Expected Result:**
- Product name: `Signup Fee Weekly` (x1).
- Recurring price: `$19.99 every 1 week`.
- Free trial line: `7 days`.
- Signup fee line: `$4.99 one-time fee`.
- Today's charge line: `$4.99` (signup fee only — NOT `Free (trial starts today)`, because a fee is charged today).
- Next charge line: T + 7 days.
- Authorization notice: *"By completing this purchase, you authorize us to charge..."*

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Confirm the line item label is "Subscription Signup Fee"
**Steps:**
1. Inspect the order review table for the line label of the signup fee.
2. The label must read literally **`Subscription Signup Fee`** (matching the WooCommerce / ArraySubs default label).

**Expected Result:**
- The label text is **`Subscription Signup Fee`** with that exact capitalization.
- The amount column shows `$4.99`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Place the order
**Steps:**
1. Fill billing details. Select Direct bank transfer.
2. Click **Place order**.
3. Wait for the thank-you page.

**Expected Result:**
- Order placed successfully.
- Order total on thank-you page: `$4.99`.
- Related Subscriptions table shows one row with status badge **Trial**.
- Next payment shown is T + 7 days.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Verify the subscription record
**Steps:**
1. As Administrator, open **ArraySubs → Subscriptions** and filter by **Trial**.
2. Open the new subscription via **View Details**.
3. Inspect the Subscription Info, Billing Information, and Order History cards.

**Expected Result:**
- Status: **Trial**.
- Subscription Info card shows Trial Length `7` and Trial End Date T + 7 days.
- Billing Information card shows:
  - Recurring Amount: `$19.99`.
  - Signup Fee: `$4.99`.
  - Billing Schedule: `Every 1 week(s)`.
- Order History card shows the initial order with total `$4.99`, type `Initial`, and status `Processing` or `Completed`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Confirm prices are locked at checkout
**Steps:**
1. As Administrator, edit the `Signup Fee Weekly` product and change the recurring price to a new value (e.g., `$24.99`). Save.
2. Re-open the subscription detail screen.
3. Read the recurring amount.
4. Revert the product price to `$19.99` after the check.

**Expected Result:**
- The existing subscription continues to show recurring `$19.99` even after the product price changed (per manual: *"Prices are locked at checkout"*).
- Only future new subscriptions would use the updated price.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the customer received a **Trial Started** email (not a New Subscription email).
- Confirm no Active subscription was created — the status must be Trial until the trial period ends.
- In the Payment Timeline card, confirm the initial paid order appears with the correct timestamp and the `$4.99` charge.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order ID created:
- Subscription ID created:
- Notes:
