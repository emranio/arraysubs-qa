# Stage 18 — Task 06: On-Hold → Cancelled Transition (overdue_payment)

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Recovery and Grace Flows (Phase 3: Auto-cancellation) |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | Task 18.05 (On-Hold transition verified) |

## Objective
Re-create the On-Hold state on a test subscription, then time-travel further so the unpaid renewal is now more than 10 days past due (3 active grace + 7 on-hold grace). Run the `arraysubs_check_overdue_renewals` hook and confirm the subscription is automatically **Cancelled** with reason `overdue_payment`, the **Subscription Cancelled** email is sent, all pending renewal orders are cancelled, and all future scheduled actions are removed.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active.
- Customer `member-decline@example.com` has a **Standard Weekly $19.99/wk** subscription with the declining card `4000 0000 0000 0341`. (Reset card if Task 05 left it as `4242`.)
- Settings → General Settings: Active Grace Days = **3**, On-Hold Grace Days = **7** (defaults — total grace = 10 days).
- Subscription Cancelled email = Enabled. Admin Subscription Cancelled email = Enabled.
- Time-travel approach from Task 18.01 ready.

## Test Data
- Test subscription ID: ____
- Saved card: 0341
- Recurring price: **$19.99 / week**
- Active grace days: ____ (verify = 3)
- On-hold grace days: ____ (verify = 7)
- Time-travel target for `_next_payment_date`: **11 days ago** (past the full 10-day active+on-hold grace window)
- Time-travel target for `_on_hold_date`: **8 days ago** (must be > 7 days for the second on-hold grace check)

## Sub-Tasks

### Sub-Task 06.1 — Re-create the On-Hold state
**Steps:**
1. Confirm card is `0341`.
2. Apply Task 18.01 procedure: edit `_next_payment_date` to **11 days ago** (past the full 10-day grace window).
3. Run `arraysubs_generate_upcoming_renewals` and `arraysubs_process_renewal` from Scheduled Actions to produce a failed renewal order.
4. Run `arraysubs_check_overdue_renewals` to move the subscription to On Hold.
5. Refresh the subscription detail page. Confirm status = On Hold.

**Expected Result:**
- A new failed renewal order exists.
- Subscription status = **On Hold**.
- `_on_hold_date` set to current timestamp.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.2 — Time-travel `_on_hold_date` to past the on-hold grace
**Steps:**
1. The system uses `_on_hold_date` plus the on-hold grace days to determine when to cancel. The Check Overdue Renewals checker's second pass requires `on_hold_date` to be more than 7 days old.
2. Edit the subscription's `_on_hold_date` meta to **8 days ago** (this simulates having been on hold for 8 days). Save.
3. Confirm the meta value persists by refreshing the detail page.

**Expected Result:**
- `_on_hold_date` is now 8 days in the past.
- `_next_payment_date` is still 11 days in the past (set in Sub-Task 06.1).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.3 — Run the overdue checker to trigger auto-cancellation
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Run the next pending `arraysubs_check_overdue_renewals` action manually.
3. Wait 10 seconds.
4. Refresh the subscription detail page.

**Expected Result:**
- Subscription status transitions from **On Hold** to **Cancelled**.
- `_end_date` is set to the cancellation timestamp.
- `_cancelled_by` = `system`.
- `_cancellation_reason` = `overdue_payment` (record exact value: ____).
- `_pending_renewal_order_id` cleared.
- The previously-pending failed renewal order has been moved to status **Cancelled**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.4 — Verify the Subscription Cancelled email and admin email
**Steps:**
1. Open the mail catcher.
2. Find the **Subscription Cancelled** email to `member-decline@example.com`. Open and inspect.
3. Find the **Admin Subscription Cancelled** email to the admin recipient.

**Expected Result:**
- Both emails exist with timestamps matching the cancellation.
- Customer email body explains the subscription was cancelled and references the subscription ID.
- The cancellation reason `overdue_payment` is reflected in the body or subject line (or at least logged in the audit trail).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.5 — Verify all future scheduled actions removed
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Search for `arraysubs_` and filter Status = Pending.
3. Look for any pending actions whose args include this subscription ID.

**Expected Result:**
- No pending hooks reference this subscription ID — the cancellation handler unscheduled all future actions (`arraysubs_send_renewal_reminder`, `arraysubs_send_expiring_soon`, etc.).
- Confirm by clicking into a sample of pending arraysubs hooks; none should match this subscription's ID.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.6 — Verify Activity Audits and Scheduled-Job Logs
**Steps:**
1. Open **ArraySubs → Audits [beta] → Activity Audits**.
2. Filter Author = System and Entity = Subscription.
3. Find the most recent row for this subscription.
4. Click `changes →` and inspect.
5. Open **Scheduled-Job Logs**. Confirm a Success row for "Check Overdue Renewals".

**Expected Result:**
- Audit row shows Status: On Hold → Cancelled with the reason captured.
- Scheduled-Job Logs records "Check Overdue Renewals" with Success status.
- Optionally, a separate `arraysubs_cancel_subscription` row may also be present labelled "Cancel Subscription".

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.7 — Verify customer portal reflects the cancellation
**Steps:**
1. Open incognito. Log in as `member-decline@example.com`.
2. Go to **My Account → Subscriptions**.
3. Inspect the cancelled subscription.

**Expected Result:**
- Subscription status displayed as **Cancelled**.
- Reactivate option may be visible (depending on the **Allow Customers to Reactivate** setting).
- No new renewal invoice can be generated for this subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the on-hold-grace cancellation hook used the correct timing logic — `_on_hold_date` of 8 days ago + 7-day on-hold grace = 1 day overdue → cancelled. Without the `_on_hold_date` time-travel in Sub-Task 06.2, the cancellation would not fire even with `_next_payment_date` 11 days ago, because the safety check requires `on_hold_date` itself to be at least 7 days old.
- Confirm the Subscription Expired email was NOT sent (this is cancellation, not natural expiration).
- For Stage 19+ tests, you can reactivate this subscription as admin — change status back to Active. Note this if needed for downstream tasks.
- Restore the saved card to the working `4242` if you plan to reactivate the subscription for future tests.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- Cancellation reason captured: (expected `overdue_payment`)
- End date captured:
- Notes:
