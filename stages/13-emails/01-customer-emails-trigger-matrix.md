# Stage 13 — Task 01: Customer Emails Trigger Matrix

| Key | Value |
|---|---|
| Stage | 13 — Email Notifications |
| Module | Emails — Customer-facing |
| Plugin Coverage | Both (Free + Pro) |
| Estimated Time | 60 min |
| Depends On | 03, 06, 07, 08, 12 |

## Objective
Trigger and verify each of the customer lifecycle emails — at minimum: **New Subscription**, **Subscription Activated** (same as New Subscription on first activation), **On-Hold**, **Cancelled**, **Expired**, **Renewal Reminder**, **Renewal Invoice**, **Payment Successful**, **Payment Failed**, **Trial Started**, **Trial Ending Soon** (alias of Renewal Reminder during trials), **Trial Converted**, **Subscription Expiring Soon** (Renewal Reminder near fixed-term end), **Auto-Downgrade**, **Retention Discount Accepted**, and **Subscription Reactivated**. For each: confirm Subject line matches the configured default (or your customization), From address matches WC, and that body placeholders are rendered.

## Pre-conditions
- Stage 03 products available: `Standard Weekly` ($19.99/week — generic subscription used across most triggers), `Basic Monthly` ($29.99/month — used in Sub-Task 1.4 to verify monthly placeholders), `Trial Weekly` (7-day trial, $19.99/week), a fixed-length weekly product (`Fixed-Length Weekly (6 cycles)` $24.99/week), and a product with an `Auto-Downgrade Target` configured (Stage 03 / 08).
- Stage 08 Retention flow tested (cancellation modal with discount offer).
- `cust1@test.local` and `cust2@test.local` available.
- Stripe is already configured in test mode — card `4000 0000 0000 0341` for failed-payment scenarios.
- Time-travel method available.
- WC From name + From address noted from **WC → Settings → Emails → Email sender options**.

## Test Data
- WC default From name and From address (note in Sign-off).
- Time-travel method used (note in Sign-off).
- The 16 emails listed in the Sub-Tasks below — each has its own pass/fail row.

## Sub-Tasks

### Sub-Task 1.1 — New Subscription
**Steps:**
1. As `cust1@test.local`, complete checkout for `Standard Weekly` via Stripe test card `4242 4242 4242 4242` (or BACS as a manual fallback).
2. As admin, mark the order **Processing** / **Completed** if needed so the subscription transitions to **Active** from `Pending`.

**Expected Result:**
- Email arrives at `cust1@test.local`.
- Subject: `[<site_title>] Your subscription #<ID> is active`.
- Heading: `Your subscription is now active!`.
- Body lists product, price + billing period, start date, next payment date.
- From address matches WC sender.
- Placeholders all rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Trial Started
**Steps:**
1. As `cust2@test.local`, checkout `Trial Weekly` (7-day trial, $19.99/week) using the configured Stripe test card.
2. Activate the subscription if not auto-active.

**Expected Result:**
- Subject: `[<site_title>] Your free trial for Trial Weekly has started`.
- Heading: `Your free trial has started!`.
- Body shows trial length (`7 days`), trial end date, what happens after trial.
- `{trial_end_date}` and `{trial_length}` rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Renewal Reminder
**Steps:**
1. Confirm **ArraySubs → Settings → General → Email Reminder Schedule → Renewal Reminder (Days Before)** = `3` (default).
2. Time-travel `cust1`'s `Standard Weekly` next-payment-date so the scheduled reminder fires (or run the action-scheduler hook directly: `arraysubs_send_renewal_reminder`).

**Expected Result:**
- Subject: `[<site_title>] Your subscription #<ID> renews soon`.
- Heading: `Your subscription renews soon`.
- `{days_before}` rendered (e.g., `3`).
- Body shows product, recurring amount, next payment date.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Renewal Invoice (monthly placeholder check)
**Steps:**
1. Pre-req: ensure `cust1` (or any test customer) has an active `Basic Monthly` ($29.99/month) subscription. If not, complete a fresh `Basic Monthly` checkout and activate it. This is the only sub-task that uses a monthly product — the goal is to verify that monthly billing-period placeholders (e.g., `Every 1 month`, monthly recurring price) render correctly.
2. Time-travel the `Basic Monthly` subscription's next renewal so a renewal order is generated (a few hours before due-date is enough).
3. Run the action-scheduler hook for renewal invoice creation if needed.

**Expected Result:**
- Subject: `[<site_title>] Invoice for subscription #<ID>`.
- Heading: `Your renewal order`.
- Body shows order ID, total `$29.99`, billing period rendered as monthly (e.g., `Every 1 month`), due date.
- For BACS / manual gateways a **Pay for this order** link is present; for the Stripe automatic gateway it may be absent.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Payment Successful
**Steps:**
1. Continue the renewal from 1.4 — let the automatic gateway process payment OR manually mark a BACS renewal order as **Completed**.

**Expected Result:**
- Subject: `[<site_title>] Payment received for subscription #<ID>`.
- Heading: `Payment received`.
- Body shows order ID, amount paid, payment method, next payment date.
- Placeholders `{order_id}`, `{order_total}`, `{payment_method}` rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Trial Converted
**Steps:**
1. Time-travel `cust2`'s `Trial Weekly` 7 days forward so the trial ends and converts.
2. Run the cron / action scheduler.

**Expected Result:**
- Subject: `[<site_title>] Your trial for Trial Weekly has converted to a paid subscription`.
- Heading: `Your trial has been converted to a paid subscription`.
- Body shows product, recurring price `$19.99 / week`, next payment date.
- `{next_payment_date}`, `{subscription_price}` rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Payment Failed
**Steps:**
1. Reconfigure `cust1`'s `Standard Weekly` payment method to use Stripe test card `4000 0000 0000 0341` (charge will fail at renewal). If needed, replace via admin or the customer portal.
2. Time-travel the next renewal.

**Expected Result:**
- Subject: `[<site_title>] Payment failed for subscription #<ID>`.
- Heading: `Payment failed`.
- Body shows the reason callout (`insufficient funds on the card` or similar) and a red urgency block with a **Pay Now** button.
- `{order_id}`, `{order_total}`, `{order_pay_url}` or `{retry_url}` rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.8 — Subscription On Hold
**Steps:**
1. From the failed renewal in 1.7, let the grace period expire so the subscription transitions to **On Hold**, OR as admin manually change the status to **On Hold** on the subscription detail screen.

**Expected Result:**
- Subject: `[<site_title>] Your subscription #<ID> is on hold`.
- Heading: `Your subscription is on hold`.
- Body explains what on hold means and links to the customer portal.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.9 — Subscription Cancelled
**Steps:**
1. Manually change a `cust2` subscription to **Cancelled** via admin OR have the customer cancel through the portal (Stage 08 cancellation flow).

**Expected Result:**
- Subject: `[<site_title>] Your subscription #<ID> has been cancelled`.
- Heading: `Your subscription has been cancelled`.
- Body shows cancellation date and reason (if customer-supplied).
- `{cancellation_date}`, `{cancellation_reason}` rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.10 — Subscription Expired
**Steps:**
1. Use a fixed-length subscription (Stage 03 fixed-term product) and time-travel its end date so it transitions to **Expired**.

**Expected Result:**
- Subject: `[<site_title>] Your subscription #<ID> has expired`.
- Heading: `Your subscription has expired`.
- `{expiration_date}` rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.11 — Subscription Reactivated
**Steps:**
1. Take a cancelled or on-hold subscription from earlier sub-tasks and reactivate it (admin action OR customer portal restore action OR pay a pending renewal).

**Expected Result:**
- Subject: `[<site_title>] Your subscription for <product> has been reactivated`.
- Heading: `Your subscription has been reactivated!`.
- Body shows product, next payment date, recurring amount.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.12 — Auto-Downgrade
**Steps:**
1. Use a subscription configured with an Auto-Downgrade Target product (Stage 03 / 08).
2. Trigger the downgrade event — typically by letting a fixed-term subscription expire OR by cancelling with auto-downgrade-on-cancel configured.

**Expected Result:**
- Subject: `[<site_title>] Your subscription #<ID> has been changed to <new_product_name>`.
- Heading: `Your subscription plan has changed`.
- Body shows old and new plan names, updated pricing.
- `{old_product_name}`, `{new_product_name}` rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.13 — Retention Discount Accepted
**Steps:**
1. As `cust1` (or any customer with an active subscription), open the customer portal and click **Cancel Subscription**.
2. In the cancellation flow, accept a discount offer (Stage 08 retention).

**Expected Result:**
- Subject: `[<site_title>] Your retention discount for <product> is active`.
- Heading: `Your subscription discount is now active`.
- Body lists discount %, duration in cycles, base recurring amount, discounted amount, savings per renewal.
- All discount placeholders rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.14 — Subscription Expiring Soon (Renewal Reminder near fixed end)
**Steps:**
1. On a fixed-length subscription approaching its end date, trigger the renewal reminder logic (it doubles as the expiring-soon notification when no further renewal is scheduled).

**Expected Result:**
- The Renewal Reminder email body indicates the subscription is about to end / a final invoice will or will not follow, depending on plugin behaviour.
- Document the wording observed.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.15 — Trial Ending Soon
**Steps:**
1. With a Trial subscription whose trial end is approaching, trigger the renewal reminder while the subscription is still in `Trial` status.

**Expected Result:**
- Renewal Reminder email arrives with body wording reflecting the trial end (e.g., `Your trial ends in 3 days`).
- Note any specific copy variation that fires only during trials.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.16 — From-name / From-address consistency
**Steps:**
1. Pick any three of the emails received above.
2. Inspect the `From` header.

**Expected Result:**
- All emails use the same From name and From address — matching **WC → Settings → Emails → "From" name** and **"From" address** fields.
- No email is sent from `wordpress@<host>` unless that is the configured WC sender.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Note any email that did not fire and the suspected cause (gateway behaviour, missing toggle, missing trigger).
- DevTools / debug.log: zero email-related PHP errors.

## Sign-off
- Tester:
- Date:
- Browser & version:
- WC From name / address:
- Time-travel method:
- Notes:
