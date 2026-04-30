# Stage 06 — Task 06: Emails on Subscription Creation

| Key | Value |
|---|---|
| Stage | Initial Subscription Lifecycle |
| Module | Customer Emails / Admin Emails |
| Plugin Coverage | Both |
| Estimated Time | 20 min |
| Depends On | Task 01, Task 02 |

## Objective
Confirm the lifecycle creation emails were delivered:
- The **Active** subscription from Stage 05 Task 01 produced a customer **New Subscription** email and an admin **Admin New Subscription** email.
- The **Trial** subscription from Stage 05 Task 03 produced a customer **Trial Started** email and an admin **Admin New Subscription** email.
Open both emails per case, confirm the subject and heading match the manual's defaults, and confirm the placeholders (customer name, product, amount, dates) rendered as real values rather than literal `{placeholder}` strings.

## Pre-conditions
- WooCommerce → Settings → Emails:
  - **arraysubs_new_subscription** is enabled.
  - **arraysubs_trial_started** is enabled.
  - **Admin New Subscription** email is enabled (admin email recipient configured).
- Outbound email is reaching the inbox (verified during Stage 00 task 04).
- You have access to:
  - The customer mailbox for `customer-classic@example.test`.
  - The customer mailbox for `customer-trial@example.test`.
  - The admin mailbox configured in WooCommerce → Settings → Emails.

## Test Data
- Active subscription: from Stage 05 Task 01.
- Trial subscription: from Stage 05 Task 03.

## Sub-Tasks

### Sub-Task 6.1 — Confirm New Subscription email reached the customer (Active case)
**Steps:**
1. Open the inbox of `customer-classic@example.test`.
2. Find the most recent message from the store.
3. Open it.

**Expected Result:**
- An email is present.
- Subject matches the default pattern: `[<site_title>] Your subscription #<ID> is active` (with `<site_title>` replaced by the actual store name and `<ID>` replaced by the subscription ID from Stage 05 Task 01).
- Heading inside the email body reads: **Your subscription is now active!**

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.2 — Verify placeholders rendered (Active case)
**Steps:**
1. In the same email, locate the subscription details table.
2. Read every value.

**Expected Result:**
- Customer's first name appears in the greeting (e.g., `Hi <FirstName>,`) — not literal `{customer_first_name}`.
- Product name: `Basic Monthly` — not literal `{product_name}`.
- Price and billing period: `$29.99 / every month` (or equivalent formatted) — not literal `{subscription_price}`.
- Start date: Stage 05 Task 01 date — not literal `{start_date}`.
- Next payment date: Stage 05 Task 01 date + 1 month — not literal `{next_payment_date}`.
- A "View subscription" link is present and points at the customer-portal detail page for this subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.3 — Confirm Admin New Subscription email reached the admin (Active case)
**Steps:**
1. Open the admin inbox configured in WooCommerce → Settings → Emails.
2. Find the most recent admin notification email about the new subscription.
3. Open it.

**Expected Result:**
- An email is present (the **Admin New Subscription** email).
- The subject contains the new subscription ID and customer reference.
- The body includes: customer name + email (`customer-classic@example.test`), product `Basic Monthly`, recurring amount `$29.99`, start date, parent order link.
- All placeholders rendered as real values (no `{...}` literals).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.4 — Confirm Trial Started email reached the customer (Trial case)
**Steps:**
1. Open the inbox of `customer-trial@example.test`.
2. Find the most recent email from the store.
3. Open it.

**Expected Result:**
- An email is present.
- Subject matches: `[<site_title>] Your free trial for Trial 14-Day has started`.
- Heading reads: **Your free trial has started!**
- The body contains:
  - Greeting with customer first name.
  - Product name `Trial 14-Day`.
  - Trial length: `14 days` — not literal `{trial_length}`.
  - Trial end date: Stage 05 Task 03 date + 14 days — not literal `{trial_end_date}`.
  - Post-trial pricing line: `$19.99 / month` (or equivalent format).
  - Customer-portal link.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.5 — Confirm New Subscription email did NOT fire for the Trial case
**Steps:**
1. Inside `customer-trial@example.test`'s mailbox, search for any email with "is active" or "subscription is now active" subject.

**Expected Result:**
- No `arraysubs_new_subscription` email was sent to the trial customer. Per the manual: trial subscriptions get the Trial Started email, not the New Subscription email.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.6 — Confirm Admin New Subscription email reached the admin (Trial case)
**Steps:**
1. Open the admin inbox.
2. Locate the admin notification corresponding to `customer-trial@example.test`'s subscription.

**Expected Result:**
- An admin email is present for the trial subscription.
- The body lists the trial product (`Trial 14-Day`), the customer email, and the trial length.
- All placeholders rendered as real values.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.7 — Cross-check the From name and address
**Steps:**
1. In any of the four emails opened above, check the sender field.

**Expected Result:**
- The From name and From address match the values configured in **WooCommerce → Settings → Emails → Email sender options** (per manual: *"it is a WooCommerce-wide setting, not per-email"*).
- No emails are coming from `wordpress@<domain>` if a custom From address was set up — they should use the configured From address.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Open one of the customer emails in the WooCommerce email preview (**WooCommerce → Settings → Emails → <email name> → Preview**) and confirm the HTML template loads with placeholder data — used as a fallback if the live email rendering looked off.
- Confirm no duplicate emails were sent (Trial customer should NOT have both Trial Started AND New Subscription emails for the same status transition).
- Confirm the Reactivated email did NOT fire for these brand-new subscriptions (it should only fire on actual reactivation events).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Active customer email message-ID:
- Trial customer email message-ID:
- Admin email message-IDs:
- Notes:
