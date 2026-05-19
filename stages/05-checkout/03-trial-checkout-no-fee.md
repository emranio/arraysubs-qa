# Stage 05 — Task 03: Trial Checkout — No Signup Fee

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout — Trial Handling |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | Task 01 |

## Objective
Confirm that buying a trial-only subscription product (with no signup fee) charges nothing today, displays the exact "Free (trial starts today)" today-charge text, sets the subscription's status to `Trial`, and stores the next payment date equal to the trial end date as documented in the Subscription Checkout manual ("Trial only (no signup fee) → Today's charge: Free — no charge today, Subscription Status: Trial").

## Pre-conditions
- Customer `customer-trial@example.test` exists, has never had a subscription before (so the One-Trial-Per-Customer rule cannot block this purchase).
- ArraySubs → Settings → General Settings:
  - **One trial per customer**: Enabled (default).
  - **Require payment method for trials**: Enabled (default).
  - **Auto-create account for subscription purchases**: Enabled.
- A manual gateway (Direct bank transfer) is enabled — the trial total is $0, but the manual gateway is still selectable for capturing the payment-method-on-file metadata.

## Test Data
- Product: `Trial Weekly` (simple subscription, $19.99/week, 7-day trial, no signup fee).
- Customer: `customer-trial@example.test`.
- Today's date placeholder: today (call it T).
- Expected trial end date: T + 7 days.
- Expected next payment date: T + 7 days (= trial end date).

## Sub-Tasks

### Sub-Task 3.1 — Add the trial product to cart
**Steps:**
1. Open Incognito and log in as `customer-trial@example.test`.
2. Go to the shop and find `Trial Weekly`.
3. Click **Add to cart**.
4. Open the cart page.

**Expected Result:**
- Cart contains one row for `Trial Weekly`.
- The cart line shows `$19.99` plus the trial language (e.g., "7 days free trial").
- Cart total at the bottom shows `$0.00` (because the trial is free and there is no signup fee).
- No error banner about previous trials appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Open checkout and inspect the trial summary
**Steps:**
1. Click **Proceed to checkout** (Classic Checkout).
2. Read the subscription summary inside the order review table.

**Expected Result:**
- Product name: `Trial Weekly` (x1).
- Recurring text: `$19.99 every 1 week`.
- Free trial line shows `7 days` (the manual format is "Free trial: 7 days" or equivalent).
- Today's charge line shows the exact text: **`Free (trial starts today)`**.
- Next charge line shows the date for T + 7 days.
- Authorization notice present: *"By completing this purchase, you authorize us to charge..."*

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Confirm payment method is still required
**Steps:**
1. Look at the payment methods area of the checkout.
2. Confirm at least one payment method is selectable (Direct bank transfer / manual gateway).
3. Try to click **Place order** without selecting a payment method (if no method auto-selects).

**Expected Result:**
- A payment method must be selected even though today's total is $0 (because **Require payment method for trials** is on).
- If no method is selected, the form blocks submission with a payment-method validation error.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Place the order
**Steps:**
1. Select Direct bank transfer.
2. Complete any remaining billing fields.
3. Click **Place order**.
4. Note the order ID on the thank-you page.

**Expected Result:**
- Order is placed successfully.
- Thank-you page shows order total `$0.00`.
- A Related Subscriptions row is visible.
- The status badge for the new subscription is **Trial** (cyan).
- Next payment date displayed equals T + 7 days.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Verify subscription record in admin
**Steps:**
1. As Administrator, go to **ArraySubs → Subscriptions**.
2. Click the **Trial** filter tab.
3. Open the new subscription for `customer-trial@example.test` via **View Details**.
4. Inspect the Subscription Info card.

**Expected Result:**
- Status: **Trial** (cyan badge).
- Trial Length: `7` (with `Day(s)` period).
- Trial End Date: T + 7 days.
- Next Payment: T + 7 days.
- Recurring Amount on Billing Information card: `$19.99`.
- Billing Schedule: `Every 1 week(s)`.
- Parent order in Order History card shows the trial signup order with `$0.00` total and `Completed` (or `Processing`) status.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Verify customer-portal display
**Steps:**
1. Log back in as `customer-trial@example.test`.
2. Go to **My Account → Subscriptions**.
3. Locate the trial subscription and click **View**.

**Expected Result:**
- The list shows status badge **Trial**, next payment T + 7 days, total `$19.99` with "every 1 week" beneath it.
- The detail page top shows **Status: Trial** and **Next Payment: T + 7 days** (and *"Lifetime Deal — No recurring payment"* is **not** present).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Try to add a second trial product to the same customer's cart (e.g., re-add `Trial Weekly` after the order). Expect: *"You have already used a free trial. Free trials are limited to one per customer."* per the manual.
- Confirm `customer-trial@example.test` received the **Trial Started** email with subject `[<site_title>] Your free trial for Trial Weekly has started`.
- Confirm no `New Subscription` customer email was sent (Trial subscriptions get the Trial Started email instead).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order ID created:
- Subscription ID created:
- Notes:
