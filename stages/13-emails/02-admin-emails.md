# Stage 13 — Task 02: Admin Emails

| Key | Value |
|---|---|
| Stage | 13 — Email Notifications |
| Module | Emails — Admin-facing |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | 13.01 |

## Objective
Trigger and verify the three admin notification emails — **Admin New Subscription**, **Admin Payment Failed**, **Admin Subscription Cancelled** — and confirm each is delivered to the admin email configured in WooCommerce, the recipient list can be customized (comma-separated), and the per-email enable/disable toggle in **WC → Settings → Emails** is honoured. Also validate that the ArraySubs `emails.admin_email` override (when set) replaces the default admin recipient.

## Pre-conditions
- 13.01 complete.
- The site admin email is set under **Settings → General → Administration Email Address** (e.g., `admin@test.local`).
- All three admin emails appear under **WC → Settings → Emails** and are **Enabled**.
- ArraySubs custom admin email field located at **ArraySubs → Settings → Email Settings → Admin Email Address** (key: `emails.admin_email`).

## Test Data
- Default admin recipient: `admin@test.local`.
- Override admin recipient (used in 2.4): `qa-billing@test.local` (any unique inbox you can read).
- Multi-recipient list (used in 2.5): `admin@test.local, qa-support@test.local`.

## Sub-Tasks

### Sub-Task 2.1 — Admin New Subscription
**Steps:**
1. Confirm the WC admin email is `admin@test.local` (Settings → General).
2. As `cust1@test.local` (or any test customer), checkout `Standard Weekly` ($19.99/week) using the configured Stripe test card or a manual fallback.
3. As admin, mark the resulting order **Processing** to activate the subscription (only required for manual gateways).
4. Inspect the inbox for `admin@test.local`.

**Expected Result:**
- Subject: `[<site_title>] New subscription #<ID> from <customer_name>`.
- Heading: `New subscription received`.
- Body lists customer full name, email, subscription details, payment method.
- A direct link to the subscription edit screen in the admin is present.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Admin Payment Failed
**Steps:**
1. Reuse the failed-renewal scenario from 13.01 Sub-Task 1.7 (Stripe card `4000 0000 0000 0341`).
2. Trigger the renewal so payment fails.
3. Inspect `admin@test.local`.

**Expected Result:**
- Subject: `[<site_title>] Payment failed for subscription #<ID>`.
- Heading: `Subscription payment failed`.
- Body shows customer name + email, order ID + total, product name, current subscription status.
- A direct link to the subscription in the admin is present.
- `{order_id}`, `{order_total}` rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Admin Subscription Cancelled
**Steps:**
1. Cancel any test subscription via the customer portal cancellation flow with a reason (e.g., `Too expensive`).
2. Inspect `admin@test.local`.

**Expected Result:**
- Subject: `[<site_title>] Subscription #<ID> cancelled by <customer_name>`.
- Heading: `Subscription cancelled`.
- Body shows subscription ID, customer name + email, product name, cancellation reason `Too expensive`.
- `{cancellation_reason}` rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — ArraySubs admin email override
**Steps:**
1. **WP-Admin → ArraySubs → Settings → Email Settings → Admin Email Address** = `qa-billing@test.local`.
2. Save settings.
3. Trigger a new subscription activation (as in 2.1).

**Expected Result:**
- The Admin New Subscription email is delivered to `qa-billing@test.local` instead of `admin@test.local`.
- Reverting the field to blank restores delivery to the WP admin email.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Multi-recipient list at the WooCommerce email level
**Steps:**
1. **WC → Settings → Emails → Admin New Subscription → Manage**.
2. Set **Recipient(s)** = `admin@test.local, qa-support@test.local`.
3. Save the email settings.
4. Trigger another new subscription activation.

**Expected Result:**
- Both inboxes receive the email.
- The header shows multiple recipients (e.g., `To: admin@test.local, qa-support@test.local`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Disable Admin New Subscription email
**Steps:**
1. **WC → Settings → Emails → Admin New Subscription → Manage**.
2. Untick **Enable this email notification** and save.
3. Trigger another subscription activation.
4. Re-enable after the test.

**Expected Result:**
- No Admin New Subscription email is sent during this round.
- Customer New Subscription email still fires (since the customer email is independent).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.7 — ArraySubs admin toggles
**Steps:**
1. **ArraySubs → Settings → Email Settings**.
2. Locate the toggles `emails.admin_new_subscription`, `emails.admin_cancelled`, `emails.admin_payment_failed`.
3. Disable `emails.admin_payment_failed` and save.
4. Trigger another payment failure scenario.
5. Re-enable after the test.

**Expected Result:**
- With the ArraySubs toggle Off, no Admin Payment Failed email is sent.
- The customer Payment Failed email still fires.
- Re-enabling restores Admin Payment Failed email delivery.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.8 — No reactivation admin email expected
**Steps:**
1. Reactivate any cancelled subscription as in 13.01 Sub-Task 1.11.
2. Inspect `admin@test.local`.

**Expected Result:**
- The customer receives a Subscription Reactivated email.
- The admin does NOT receive any reactivation email — there is no admin email for reactivation in the current release.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After all changes, the WC default admin email and ArraySubs override are restored to their original baseline values.
- Confirm `wp-content/debug.log` has no errors during the email-disable / re-enable cycles.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Default admin email:
- Override email used:
- Notes:
