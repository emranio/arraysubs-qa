# Stage 18 — Task 07: Trial → Active Conversion

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Trial Management (normal conversion path, no auto-downgrade) |
| Plugin Coverage | Free + Pro |
| Estimated Time | 30 min |
| Depends On | Task 18.01 (time-travel), Stage 03 (Trial Weekly product configured) |

## Objective
Time-travel a **Trial Weekly** subscription past its 7-day trial-end date, then trigger the **Process Trial Conversions** daily job (`arraysubs_process_trial_conversions`, normally at 2:00 AM). Verify the subscription transitions from **Trial** to **Active**, the **next payment date is recalculated using the weekly billing period (not the trial period)**, the trial length meta is cleared (trial end date preserved for analytics), and the **Trial Converted** email is sent to the customer.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- Customer `member-trial@example.com` has a **Trial** subscription on **Trial Weekly** (7-day free trial, $19.99 every 1 week after, NO auto-downgrade configured).
- The trial was set up so that:
  - `_trial_end_date` is in the future
  - `_next_payment_date` equals `_trial_end_date`
  - Subscription status is **Trial**
  - Trial length and period stored on the subscription
- Trial Converted email = Enabled in WooCommerce Email settings.
- Time-travel approach from Task 18.01 ready.

## Test Data
- Test subscription ID: ____
- Original `_trial_end_date`: ____
- Original `_next_payment_date` (= trial end): ____
- Trial length / period: **7 / days** (record actual: ____)
- Billing period: **weekly** (every 1 week)
- Recurring price: **$19.99 / week**
- Auto-downgrade timing: NONE (must NOT include `on_trial_expire`)

## Sub-Tasks

### Sub-Task 07.1 — Capture starting state
**Steps:**
1. Open the test subscription's admin detail page.
2. Confirm:
   - Status = **Trial**
   - `_trial_end_date` set
   - `_next_payment_date` = `_trial_end_date`
   - Trial length and period populated
3. Confirm the product **Trial Weekly** has no auto-downgrade target configured (Linked Products tab → empty, or auto_downgrade_timing does NOT include `on_trial_expire`).
4. Verify a **Trial Started** email was previously delivered to `member-trial@example.com` (mail catcher search).

**Expected Result:**
- Baseline values captured. Trial Started email exists.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.2 — Time-travel the trial end past
**Steps:**
1. Apply Task 18.01 procedure: edit BOTH `_trial_end_date` AND `_next_payment_date` to **1 hour ago**. Save.
2. Refresh and confirm both timestamps are in the past.

**Expected Result:**
- Trial end date and next payment date are now in the past.
- Status remains **Trial** (date editing alone does not run the conversion).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.3 — Trigger the Process Trial Conversions job
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Search for `arraysubs_process_trial_conversions` (the batch hook). It runs daily at 2:00 AM normally.
3. Find the next pending occurrence and click **Run**.
4. Wait 10 seconds.
5. Refresh Scheduled Actions; the action should be Complete.

**Expected Result:**
- The batch hook completed.
- Scheduled-Job Logs (Pro) shows a Success row labelled "Batch Trial Conversions".
- The system internally dispatched a per-subscription `arraysubs_process_trial_conversion` for our subscription (visible in Scheduled Actions or in the subscription notes).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.4 — Verify the subscription converted to Active
**Steps:**
1. Refresh the test subscription detail page.
2. Read:
   - Status (expected **Active**)
   - `_trial_end_date` (expected: still preserved as a historical record — not zeroed out)
   - Trial length meta (expected: cleared / set to 0)
   - `_next_payment_date` (expected: one billing cycle from the conversion timestamp — i.e., approximately one month from now)

**Expected Result:**
- Status = **Active**.
- Trial length is 0 (or empty) on the subscription. `_trial_end_date` is preserved.
- New `_next_payment_date` is calculated using the **weekly** billing period (1 week from conversion timestamp), NOT from the original trial-end date. So if conversion happened at e.g. 2026-04-29 14:00, the new next payment is 2026-05-06 14:00.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.5 — Verify the Trial Converted email
**Steps:**
1. Open the mail catcher.
2. Find the **Trial Converted** email to `member-trial@example.com`.
3. Inspect the body.

**Expected Result:**
- Email exists with timestamp matching "just now" (the conversion run).
- Body includes: subscription details, the new next payment date, the recurring price ($19.99), and the billing schedule (weekly).
- Verify that NO "Auto Downgrade" email was sent (this product has no auto-downgrade target).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.6 — Verify renewal scheduling resumed
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Search for `arraysubs_send_renewal_reminder` and `arraysubs_generate_upcoming_renewals`.
3. Confirm that for this subscription, a new pending `arraysubs_send_renewal_reminder` is scheduled at the new `_next_payment_date` minus the configured Renewal Reminder Days Before (1 day for weekly, per task 18.11).
4. The hourly `arraysubs_generate_upcoming_renewals` will pick it up in due course (no per-subscription pending action required if the engine uses the hourly batch).

**Expected Result:**
- Renewal reminder is queued for the new cycle.
- No leftover trial-conversion actions for this subscription remain pending.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.7 — Verify Activity Audits and Scheduled-Job Logs
**Steps:**
1. Open Activity Audits. Filter Author = System and Entity = Subscription.
2. Find the most recent row referencing this subscription.
3. Click `changes →`.
4. Open Scheduled-Job Logs.

**Expected Result:**
- Audit row shows Status: Trial → Active and Trial Length: 7 → 0 (or similar).
- Scheduled-Job Logs records both "Batch Trial Conversions" and "Trial Conversion" rows with Success status.
- Activity Audits Email entity also records the Trial Converted send.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm that admin manually changing a subscription from Trial → Active via the dropdown does NOT fire the trial_converted hook (per the manual: "Manual status changes skip trial hooks"). Optionally test on a separate subscription to confirm — record outcome: ____.
- Confirm a separate Trial subscription (whose trial is NOT yet expired) was NOT touched by the batch run.
- Confirm the customer portal now shows the subscription as Active with the new next payment date.
- Confirm no Renewal Invoice email was sent during this task (trials do not generate invoices during the trial — the first invoice will be created at the next-payment-date which is now ~1 week away).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- Conversion timestamp:
- New next-payment-date:
- Notes:
