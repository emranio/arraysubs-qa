# Stage 18 — Task 03: Successful Automatic Renewal (Pro)

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Renewal Operations (automatic gateway path) |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | Task 18.01, Task 18.02 (manual path verified — automatic path mirrors many of the same outcomes) |

## Objective
Time-travel a **Standard Weekly $19.99/wk** subscription paid by saved Stripe card (`4242 4242 4242 4242`) to its next payment date, confirm the automatic charge succeeds via Stripe, the renewal order moves to **Processing** or **Completed**, the completed-payments counter increments, the **Payment Successful** email arrives, and the next-payment-date advances by **1 week**. Verify the corresponding `payment_intent.succeeded` (or equivalent) webhook arrives in the Gateway Health Dashboard.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active.
- **Stripe is already configured** in Test mode with webhook URL registered — no setup required.
- `member-stripe@example.com` has an Active subscription on **Standard Weekly** ($19.99 every 1 week) with saved Stripe card `4242 4242 4242 4242` set as the default payment method.
- Time-travel approach from Task 18.01 documented and ready to use.
- Mail catcher reachable.

## Test Data
- Test subscription ID: ____
- Original `_next_payment_date`: ____
- Saved card last four: 4242
- Original completed-payments counter: ____
- Recurring price: **$19.99 / week**
- Billing period: weekly (every 1 week)

## Sub-Tasks

### Sub-Task 03.1 — Capture starting state
**Steps:**
1. Open the test subscription's admin detail page.
2. Confirm payment method panel shows "Stripe" (or `arraysubs_stripe`) and the card ending in 4242.
3. Record the original `_next_payment_date`, completed-payments counter, and status (Active).
4. Confirm no pending renewal order exists.

**Expected Result:**
- Baseline values captured. Subscription is Active. No pending renewal order.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.2 — Time-travel the subscription
**Steps:**
1. Apply the time-travel procedure from Task 18.01 — edit `_next_payment_date` to one hour ago. Save.
2. Refresh and confirm the past timestamp is shown.

**Expected Result:**
- Next Payment Date now in the past. Status still Active.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.3 — Trigger renewal generation and automatic charge
**Steps:**
1. Open **WooCommerce → Status → Scheduled Actions**.
2. Run the next pending `arraysubs_generate_upcoming_renewals` action.
3. Wait 10 seconds.
4. Refresh Scheduled Actions and find any new `arraysubs_process_renewal` action scoped to this subscription's ID. Click **Run** on it.
5. Wait 30 seconds for the Stripe API round-trip.
6. Refresh the subscription detail page.

**Expected Result:**
- A renewal order was created and immediately charged.
- The order status is now **Processing** or **Completed** (depending on WooCommerce settings — Stripe charges typically auto-complete to Processing first).
- The order's payment notes record the Stripe charge ID and a "Payment Successful" entry.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.4 — Verify the Payment Successful email
**Steps:**
1. Open the mail catcher.
2. Find the **Payment Successful** email to `member-stripe@example.com`.
3. Confirm the body shows:
   - Renewal order ID
   - Payment amount $19.99
   - Payment method "Stripe" (or matching Stripe label)
   - Subscription details with the new next-payment-date

**Expected Result:**
- Email exists with timestamp matching "just now".
- Body content matches the charged order details.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.5 — Verify counter and next-payment-date advance
**Steps:**
1. Refresh the subscription detail page.
2. Read the completed-payments counter.
3. Read the new `_next_payment_date`.

**Expected Result:**
- Completed-payments counter = original + 1.
- New `_next_payment_date` = original next-payment-date + **1 week** (one billing cycle from the previous due date).
- `_pending_renewal_order_id` empty.
- Subscription status still **Active**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.6 — Verify the Stripe webhook event arrived
**Steps:**
1. Go to **ArraySubs → Audits [beta] → Gateway Logs**.
2. Filter the Webhook Event Log to gateway = Stripe.
3. Look at the most recent event(s).

**Expected Result:**
- A `payment_intent.succeeded` (or `charge.succeeded`) event row appears at the top with timestamp matching "just now".
- The event has a unique Event ID starting with `evt_`.
- The Stripe card's **Last Webhook** stat updated to the new timestamp.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.7 — Verify Action Scheduler hooks fired
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Filter Status = Complete, Hook contains `arraysubs_`.
3. Confirm completed rows for `arraysubs_generate_upcoming_renewals` and `arraysubs_process_renewal` (the latter scoped to this subscription ID — confirm by clicking into the row and reading the args).
4. Confirm a NEW pending row exists for `arraysubs_send_renewal_reminder` scheduled at the new next-payment-date minus the configured Renewal Reminder Days Before (1 day for weekly, per task 18.11).
5. (Pro) Open Scheduled-Job Logs (Stage 17 / Task 17.03). Confirm Success rows for "Generate Upcoming Renewals" and "Process Renewal".

**Expected Result:**
- Both hooks completed successfully.
- New reminder is queued.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Activity Audits — confirm Author = Gateway entry from Stripe webhook receipt, plus Author = System entries for the renewal cycle.
- Confirm `_payment_failure_category` meta is empty/cleared (since the charge succeeded).
- Cross-check the customer portal: log in as `member-stripe@example.com`, open My Account → Subscriptions, confirm Last Payment shows today and Next Payment shows the new date.
- Confirm Subscription On-Hold email was NOT sent.
- Confirm no Renewal Payment Failed email was sent (mail catcher search returns no match for this customer).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- Stripe Event ID captured:
- Counter before / after: ____ / ____
- New next-payment-date:
- Notes:
