# Stage 18 — Task 10: Skip and Pause Over Renewal Cycles

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Recovery and Grace Flows (skip and pause interactions) |
| Plugin Coverage | Free + Pro |
| Estimated Time | 45 min |
| Depends On | Task 18.01, Task 18.03 (automatic renewal verified) |

## Objective
Verify the **Skip** and **Pause** customer actions correctly suppress / shift renewal generation across the billing timeline. Skip **2 cycles** on a **Standard Weekly $19.99/wk** subscription — time-travel past the original renewal date and confirm NO invoice is generated; then time-travel to the new shifted renewal date (**original + 2 weeks**) and confirm an invoice IS generated. Then test Pause: pause for **14 days**, time-travel past the pause-end date, confirm auto-resume restores **Active** and the next-payment-date is shifted forward by exactly **14 days**.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- Customer `member-stripe@example.com` has an Active subscription on **Standard Weekly $19.99/wk** with saved Stripe card `4242 4242 4242 4242` (working). Billing every 1 week.
- Settings → General Settings:
  - Allow Customers to Skip = ON
  - Allow Customers to Pause = ON
  - Skip max cycles = 3
  - Skip cutoff days = 2
  - Pause max duration = 30 days (we will pause for 14 days, which is comfortably under the cap)
  - Pause max per subscription = 2
- Time-travel approach from Task 18.01 ready.

## Test Data
- Test subscription ID: ____
- Original `_next_payment_date`: ____
- Skip cycles to apply: **2** (each cycle = 1 week, so total shift = 14 days)
- Pause duration to apply: **14 days**
- Original completed-payments counter: ____

## Sub-Tasks

### Sub-Task 10.1 — Customer skips 2 renewal cycles
**Steps:**
1. Open incognito. Log in as `member-stripe@example.com`.
2. Go to **My Account → Subscriptions** and open the Active subscription.
3. Click **Skip Renewal**.
4. In the dialog, choose **2 cycles**.
5. Confirm.
6. Verify the success message shows the new shifted next-payment-date.
7. Switch to admin and refresh the subscription detail page.
8. Record the new `_next_payment_date`: ____ (should be original + **2 weeks** = 14 days).

**Expected Result:**
- Subscription is still **Active**.
- Next Payment Date now shows the original date + **2 weeks** (14 days).
- Skip metadata is set on the subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.2 — Time-travel past the ORIGINAL renewal date — verify NO invoice generated
**Steps:**
1. Apply Task 18.01 procedure: temporarily change `_next_payment_date` to be 1 hour *before* the original date (i.e. roll it back so it appears in the past). Save.
   - **Important:** This simulates "today is past the original renewal date but we are in a skipped cycle." We are testing that the SkipRenewal filter correctly blocks invoice generation.
2. Run `arraysubs_generate_upcoming_renewals` from Scheduled Actions.
3. Wait 5 seconds. Refresh the subscription detail page.
4. Inspect the Related Orders card.

**Expected Result:**
- NO new renewal order was created.
- The system's `arraysubs_should_generate_renewal_invoice` filter returned `false` because skip cycles remain.
- `_pending_renewal_order_id` remains empty.
- Subscription notes may include a system note like "Skipped renewal cycle — invoice generation blocked".

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.3 — Time-travel to the NEW shifted renewal — verify invoice IS generated
**Steps:**
1. Restore `_next_payment_date` to the shifted date (original + **2 weeks**) recorded in Sub-Task 10.1.
2. Now edit `_next_payment_date` to **1 hour ago** (simulating the new shifted date arriving).
3. Run `arraysubs_generate_upcoming_renewals` from Scheduled Actions.
4. Refresh the subscription detail page.

**Expected Result:**
- A new renewal order IS created (skip cycles have been consumed — counter went from 2 to 0).
- Order total = $19.99.
- The skip is automatically deactivated.
- Process Renewal then runs and the saved card `4242` charges successfully → order Completed.
- Counter increments by 1.
- Subscription remains Active throughout.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.4 — Capture state before pausing
**Steps:**
1. Record the new `_next_payment_date` after Sub-Task 10.3: ____
2. Confirm subscription is Active and counter has incremented appropriately.

**Expected Result:**
- State captured.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.5 — Customer pauses subscription for 14 days
**Steps:**
1. Return to the customer incognito session.
2. On the subscription detail in My Account, click **Pause Subscription**.
3. Choose duration **14 days**.
4. Confirm.
5. Verify the success message and the displayed pause-end date (today + 14 days).
6. Switch to admin and refresh.

**Expected Result:**
- Subscription status changes to **On Hold** (pause uses on-hold under the hood).
- `_pause_start_date` set to today.
- `_pause_end_date` set to today + 14 days.
- A pending `arraysubs_resume_subscription` action is queued for the pause-end date.
- Stripe gateway is also notified (gateway-side pause sync; verify in Webhook Event Log if applicable — relevant event type: `customer.subscription.updated`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.6 — Confirm no invoice generated during pause
**Steps:**
1. While paused, time-travel `_next_payment_date` to one hour ago (without altering the pause meta).
2. Run `arraysubs_generate_upcoming_renewals`.
3. Refresh the subscription.

**Expected Result:**
- NO renewal order created (status is On-Hold, the engine excludes On-Hold from invoice generation regardless of `_next_payment_date`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.7 — Time-travel past pause-end → verify auto-resume
**Steps:**
1. Edit `_pause_end_date` to **1 hour ago**. Save.
2. Find the pending `arraysubs_resume_subscription` action in Scheduled Actions and click **Run** (since the queued action's "scheduled at" was today + 14 days, you may need to manually run it OR also adjust its scheduled date; record the method used: ____).
3. Wait 10 seconds.
4. Refresh the subscription detail page.

**Expected Result:**
- Subscription status returns to **Active** (its pre-pause status).
- `_pause_end_date` and `_pause_start_date` cleared (or marked completed).
- New `_next_payment_date` is extended forward by exactly the paused duration. So if the date before pause was D and the pause lasted 14 days, the new date is D + 14 days.
- An end-date is also extended by 14 days if the subscription has one.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.8 — Verify hooks fired and Activity Audits
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions** → filter Status = Complete.
2. Confirm completed rows for `arraysubs_resume_subscription` and `arraysubs_generate_upcoming_renewals` (the run from Sub-Task 10.6 that generated nothing).
3. Open Activity Audits. Filter Author = Customer (skip and pause), Author = System (resume).
4. Click `changes →` on the resume audit row to confirm Status: On Hold → Active and `_next_payment_date` shifted by 14 days.
5. (Pro) Open Scheduled-Job Logs and confirm Success rows for "Resume Subscription" and "Process Skipped Cycle" (or equivalent).

**Expected Result:**
- All hooks fired.
- Audit trail complete.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the customer received NO Renewal Invoice email during the skipped cycles or the pause window.
- Confirm the subscription's "Next Payment" displayed in the customer portal matches the admin value at every step.
- Confirm the Stripe gateway-side pause was applied (if Pro auto-renew is using Stripe Subscriptions for billing context). Review the Webhook Event Log for relevant `customer.subscription.updated` events around the pause/resume.
- Confirm a second pause cannot be initiated within the configured cooldown period if Min days between pauses is set (try and verify the customer-side error message).
- Restore the saved card to `4242`, leave the subscription Active for downstream tasks.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- Skip cycles applied: 2
- Pause duration applied: 14 days
- Final next-payment-date:
- Notes:
