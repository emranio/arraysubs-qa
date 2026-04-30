# Stage 18 — Task 02: Successful Manual Renewal

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Renewal Operations (manual payment path) |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | Task 18.01 (time-travel procedure documented) |

## Objective
Time-travel a manual-payment subscription on **Standard Weekly $19.99/wk** to its next payment date, confirm the **Renewal Invoice** email is delivered to the customer with a working payment link, complete payment via **BACS** (Direct bank transfer), then verify the **Payment Successful** email arrives, the completed-payments counter increments, the next-payment-date advances by one **week**, and the subscription status remains Active throughout (no transition to On-Hold).

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- Customer `member1@example.com` has an Active subscription on **Standard Weekly** ($19.99 every 1 week) with manual payment (BACS / Direct bank transfer / Cheque). The subscription does NOT have a saved Stripe card.
- Time-travel approach from Task 18.01 chosen and documented.
- Mail catcher (MailHog / Mailpit / WP Mail Logger) reachable so we can read every email.
- Settings → General Settings: Renewal Invoice email is **Enabled**, Payment Successful email is **Enabled**.

## Test Data
- Test subscription ID: ____
- Original `_next_payment_date`: ____
- Time-travel target: now minus 1 hour
- Original completed-payments counter: ____ (record from subscription detail before starting)
- Recurring price: **$19.99 / week** (Standard Weekly)
- Billing period: weekly (every 1 week)
- Payment method to use during checkout: **BACS** (Direct bank transfer)

## Sub-Tasks

### Sub-Task 02.1 — Capture starting state
**Steps:**
1. Open the test subscription's admin detail page.
2. Record the following values into Test Data above:
   - `_next_payment_date` (original)
   - Completed payments counter (the number of paid renewals so far)
   - Status (must be **Active**)
3. Confirm there is NO pending renewal order linked to this subscription (Related Orders card).

**Expected Result:**
- All three baseline values captured.
- No pending renewal order present.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.2 — Time-travel the subscription to one hour ago
**Steps:**
1. Apply the time-travel approach from Task 18.01: edit `_next_payment_date` to one hour in the past.
2. Save and refresh the subscription detail page to confirm the new date.

**Expected Result:**
- Next Payment Date now shows a past timestamp.
- Subscription remains Active.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.3 — Trigger renewal invoice generation
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Search for `arraysubs_generate_upcoming_renewals`.
3. Click **Run** on the next pending row.
4. Wait 5 seconds.
5. Refresh the subscription detail page.

**Expected Result:**
- A new pending renewal order appears in the **Related Orders** card.
- The order total = $19.99.
- The subscription's `_pending_renewal_order_id` meta is set.
- Subscription status remains **Active** (no automatic charge happened — manual payment path).
- Scheduled-Job Logs (Pro) shows a Success row labelled "Generate Upcoming Renewals".

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.4 — Verify the Renewal Invoice email arrived
**Steps:**
1. Open the mail catcher.
2. Filter by recipient `member1@example.com`.
3. Find the **Renewal Invoice** email.
4. Open it and inspect:
   - Subject line (record exact text: ____)
   - Body contains the order total $19.99, subscription product name, and a **Pay Now** / payment link button.
5. Copy the payment link URL.

**Expected Result:**
- Email exists with timestamp matching "just now".
- Subject and body include the correct order amount and a working payment link.
- Activity Audits (Pro) records an Email entity audit row for this send.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.5 — Customer pays the renewal invoice
**Steps:**
1. Open an incognito browser window. Paste the payment link from Sub-Task 02.4.
2. (Login may be required; log in as `member1@example.com`.)
3. On the WooCommerce **Pay for Order** page, select **BACS / Direct bank transfer** (or **Cheque**).
4. Click **Pay**.
5. Confirm the order-received page appears with status "Order received" / "On Hold" (BACS shows On Hold by default until admin marks it Processing/Completed).
6. Switch to admin and open the renewal order in WooCommerce.
7. Change the order status to **Completed**. Save.

**Expected Result:**
- Order status moves to Completed.
- The subscription's lifecycle hooks fire: status restored to Active (it was already Active), counter increments, next-payment-date advances.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.6 — Verify Payment Successful email and counter increment
**Steps:**
1. Open the mail catcher.
2. Find the **Payment Successful** email sent to `member1@example.com` (timestamp just after the order completion).
3. Confirm the body shows:
   - The renewal order ID
   - Payment amount $19.99
   - Payment method "Direct bank transfer" (or BACS / Cheque, whichever you used)
4. Switch to the subscription detail page and refresh.
5. Read the completed-payments counter.
6. Read the new `_next_payment_date`.

**Expected Result:**
- Payment Successful email received.
- Completed-payments counter = original + 1.
- New `_next_payment_date` = original `_next_payment_date` + **1 week** (one billing cycle from the previous due date — confirm date arithmetic on weekly cadence).
- Subscription status remains **Active** (never went to On-Hold).
- `_pending_renewal_order_id` is now empty (cleared after success).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.7 — Verify Action Scheduler dispatched the right hooks
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Filter by Status = **Complete**, Hook contains `arraysubs_`.
3. Confirm completed rows for at least: `arraysubs_generate_upcoming_renewals` (the manual run from Sub-Task 02.3).
4. Confirm a NEW pending row exists for `arraysubs_send_renewal_reminder` scheduled at the new `_next_payment_date` minus the configured Renewal Reminder Days Before (1 day for weekly, per task 18.11). Click into it; the args should reference this subscription ID.
5. (Pro) Open Scheduled-Job Logs and confirm the Success row labelled "Generate Upcoming Renewals" is logged.

**Expected Result:**
- All expected hooks observed in the queue.
- A new renewal reminder is queued for the next cycle.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Activity Audits (Stage 17 / Task 17.01) — confirm rows exist for the Subscription audit (status renewed) and Order audit (renewal order completed) under Author = System.
- Confirm the customer's My Account → Subscriptions screen shows the new Last Payment date and updated Next Payment date.
- Confirm Subscription On-Hold email was NOT sent (this would indicate an unwanted grace transition).
- Restore the recorded original `_next_payment_date` if you want to re-run; otherwise leave the subscription at the new advanced state and document that downstream tasks should account for the +1 cycle.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- Time-travel approach used (refer to Task 18.01):
- Counter before / after: ____ / ____
- New next-payment-date:
- Notes:
