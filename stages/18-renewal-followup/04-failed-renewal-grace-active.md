# Stage 18 — Task 04: Failed Renewal — Active Grace Period (Pro)

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Recovery and Grace Flows (Phase 1: Active grace) |
| Plugin Coverage | Pro |
| Estimated Time | 35 min |
| Depends On | Task 18.01, Task 18.03 (automatic renewal succeeded so we know the happy path works) |

## Objective
Set a **Standard Weekly $19.99/wk** Stripe-paid subscription to use the declining test card `4000 0000 0000 0341`. Time-travel to its next payment date. Verify the automatic charge fails, the **Renewal Payment Failed** email is sent (with the failure category in the reason callout), and the subscription **stays Active** for the configured 3-day active grace period (no immediate transition to On-Hold). Verify the customer can still pay manually via the renewal invoice link during this window to recover the subscription.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active.
- **Stripe is already configured** (Test mode + webhook URL registered).
- Customer `member-decline@example.com` has an Active subscription on **Standard Weekly** ($19.99 every 1 week) with saved Stripe card `4000 0000 0000 0341` (set as default).
- Settings → General Settings:
  - Active Grace Days = **3** (default)
  - On-Hold Grace Days = **7** (default)
  - Renewal Payment Failed email = Enabled
- Time-travel approach from Task 18.01 ready.
- Mail catcher reachable.

## Test Data
- Test subscription ID: ____
- Original `_next_payment_date`: ____
- Saved card last four: 0341
- Recurring price: **$19.99 / week**
- Billing period: weekly
- Active grace days configured: ____ (verify = 3)
- On-hold grace days configured: ____ (verify = 7)

## Sub-Tasks

### Sub-Task 04.1 — Confirm declining card is the default
**Steps:**
1. Open the test subscription detail page in admin.
2. Inspect the payment method panel — confirm the saved card last four = `0341`.
3. If not, log in as the customer and replace the saved card with `4000 0000 0000 0341`. Set as default. Remove any other cards.
4. Return to admin and confirm.

**Expected Result:**
- The subscription's payment method is the declining test card `0341`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.2 — Time-travel to next payment date
**Steps:**
1. Apply the Task 18.01 procedure: edit `_next_payment_date` to one hour ago. Save.
2. Confirm the past timestamp on the detail page.

**Expected Result:**
- Next Payment Date now in the past. Status still Active.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.3 — Trigger renewal generation + charge attempt
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Run `arraysubs_generate_upcoming_renewals` (next pending row).
3. Wait 10 seconds, then run the new `arraysubs_process_renewal` row scoped to this subscription.
4. Wait 30 seconds for Stripe to return a decline.
5. Refresh the subscription detail page.

**Expected Result:**
- A renewal order was created.
- The Stripe charge attempt failed → order status is **Failed** (red badge in WooCommerce).
- Order notes contain the gateway decline message (e.g., "Your card was declined.").
- Subscription's `_last_payment_failure_category` meta is set to one of: `card_declined` / `generic_decline` / etc. Record value: ____
- **Subscription status remains Active** — no immediate On-Hold transition.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.4 — Verify the Renewal Payment Failed email
**Steps:**
1. Open the mail catcher.
2. Find the **Renewal Payment Failed** email to `member-decline@example.com`.
3. Open and inspect the body.

**Expected Result:**
- Email exists with timestamp matching "just now".
- Body contains a one-line reason callout above the order details (e.g., "Reason: the card was declined." or whichever maps to the failure category).
- Body contains a **Pay Now** link that opens the WooCommerce Pay-for-Order page for the failed renewal order.
- An admin copy of the Payment Failed email also exists in the catcher (Admin Payment Failed).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.5 — Confirm subscription stays Active during the active grace window
**Steps:**
1. On the subscription detail page, inspect the status. It should be **Active**.
2. Inspect the subscription notes — confirm there is a system note about the failed payment but NO note about transition to On-Hold yet.
3. Run `arraysubs_check_overdue_renewals` once manually from Scheduled Actions.
4. Refresh the subscription detail page.

**Expected Result:**
- Subscription status is still **Active** (because we are within the 3-day active grace period — `_next_payment_date` was only 1 hour ago, well inside 3 days).
- The On-Hold transition does NOT occur.
- The hourly checker logs an entry but takes no action against this subscription on this run.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.6 — Customer pays manually via the renewal invoice link to recover
**Steps:**
1. Open the **Renewal Payment Failed** email in the mail catcher.
2. Copy the Pay Now link.
3. Open an incognito browser. Paste the link. Log in as `member-decline@example.com` if prompted.
4. On the WooCommerce Pay-for-Order page, choose Stripe and enter a working test card: `4242 4242 4242 4242`, exp `12/34`, CVC `123`.
5. Click Pay. Confirm the order-received page.

**Expected Result:**
- Payment succeeds. Order moves to Completed/Processing.
- Subscription is restored: status remains Active, completed-payments counter increments, `_next_payment_date` advances by **1 week** (one billing cycle), `_pending_renewal_order_id` cleared, `_last_payment_failure_category` cleared.
- Customer receives **Payment Successful** email.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.7 — Verify Action Scheduler hooks fired and Scheduled-Job Logs
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions** → filter Status = Complete.
2. Confirm completed rows for `arraysubs_generate_upcoming_renewals`, `arraysubs_process_renewal` (failed), and `arraysubs_check_overdue_renewals` (no action) all exist.
3. Open **ArraySubs → Audits [beta] → Scheduled-Job Logs** (Pro).
4. Confirm a row for "Process Renewal" with status **Failed** (red badge, red row background) and the failure error message.
5. After the manual recovery payment, confirm an additional row for the successful payment processing (status update to Active / counter advance).

**Expected Result:**
- All hooks fired.
- Scheduled-Job Logs shows a Failed row for the initial decline plus subsequent Success entries from the recovery flow.
- Activity Audits shows a corresponding Subscription audit row (Author = Gateway for the decline, Author = Customer for the manual pay).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Cross-check the Gateway Health Dashboard's Webhook Event Log for two Stripe events: `payment_intent.payment_failed` (or `charge.failed`) and `payment_intent.succeeded`.
- Confirm Subscription On-Hold email was NOT sent.
- Verify the customer retained access to subscription content throughout this task (no access restriction).
- Restore the saved card to `0341` if you want to re-test, OR keep it as the working `4242` — record the final state for downstream tasks: ____.
- Note: Task 18.05 builds on this same subscription. If Task 04 ends with status Active and the decline card still attached, we are positioned correctly for Task 05.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- Failure category captured:
- Order ID of failed renewal:
- Order ID of successful recovery:
- Notes:
