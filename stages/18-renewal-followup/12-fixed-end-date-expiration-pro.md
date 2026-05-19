# Stage 18 — Task 12: Fixed-Period Membership Expiration (Pro)

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Renewal Operations (Fixed Period Membership end date) |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | Task 18.01, Stage 03 (Fixed-Period Plan product configured) |

## Objective
On a **Pro** subscription with a **Fixed Period Membership** end date set, time-travel past the end date and run the overdue/expiration checker. Verify the subscription transitions to status **Expired** (not Cancelled), the **Subscription Expired** email is sent, all future renewal scheduled actions are unscheduled, and the lifecycle hook `arraysubs_expire_subscription` was dispatched correctly.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active.
- Customer `member-fixed@example.com` has an Active subscription on **Fixed-Period Plan** with `_end_date` set to **today + 14 days** (a short window keeps the regression run fast).
- The product or subscription has the **Fixed Period Membership** feature enabled and the end date stored as `_end_date` (or `_subscription_end_date`).
- Subscription Expired email = Enabled.
- No auto-downgrade target configured for this product (we want the subscription to expire, not switch).
- Time-travel approach from Task 18.01 ready.

## Test Data
- Test subscription ID: ____
- Original `_end_date`: today + 14 days (record actual: ____)
- Original `_next_payment_date`: ____
- Recurring price: ____
- Status before: Active

## Sub-Tasks

### Sub-Task 12.1 — Capture starting state and verify Fixed Period config
**Steps:**
1. Open the test subscription's admin detail page.
2. Confirm:
   - Status = Active
   - End Date is set to **today + 14 days** (record: ____)
   - Subscription product has Fixed Period Membership enabled
3. Confirm the product has NO auto-downgrade target configured for `on_expire` timing (otherwise the subscription would switch instead of expire).
4. In **WooCommerce → Status → Scheduled Actions**, search for `arraysubs_send_expiring_soon` for this subscription and confirm an Expiring Soon email is queued in advance of the end date.

**Expected Result:**
- Baseline values captured.
- Expiring Soon email is queued (if applicable to your settings).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.2 — Time-travel `_end_date` past the 14-day window
**Steps:**
1. Apply the Task 18.01 procedure: edit `_end_date` to be **1 hour in the past** (i.e., simulate today + 14 days having elapsed; the simplest way is to move the end date back by 14 days + 1 hour from where it was set, or simply set it to "1 hour ago"). Save.
2. Refresh the subscription detail page.
3. Confirm the End Date now shows the past timestamp.

**Expected Result:**
- End Date is in the past.
- Status still **Active** (the expiration check has not yet run).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.3 — Run the overdue / expiration checker
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. The expiration checker runs alongside the overdue checker. Click **Run** on the next pending `arraysubs_check_overdue_renewals` action.
3. Alternatively (or in addition), find any pending `arraysubs_expire_subscription` action scoped to this subscription and click **Run**.
4. Wait 10 seconds.
5. Refresh the subscription detail page.

**Expected Result:**
- Subscription status transitions from **Active** to **Expired**.
- `_end_date` is recorded (already set; preserved).
- Activity log/system note records the natural expiration (NOT a cancellation — distinct from `overdue_payment` cause).
- `_pending_renewal_order_id` empty.
- All future arraysubs_* scheduled actions for this subscription have been unscheduled (verify in Sub-Task 12.5).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.4 — Verify the Subscription Expired email
**Steps:**
1. Open the mail catcher.
2. Find the **Subscription Expired** email to `member-fixed@example.com`.
3. Inspect the body.

**Expected Result:**
- Email exists with timestamp matching "just now".
- Body explains the subscription has reached its end date.
- The subject and body do NOT use the cancellation language (this is Expired, not Cancelled).
- Verify NO Subscription Cancelled email was sent for this subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.5 — Verify all future scheduled actions removed
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Filter Status = Pending.
3. Search the args column for this subscription's ID.

**Expected Result:**
- No pending arraysubs hooks reference this subscription's ID.
- Specifically: no pending `arraysubs_send_renewal_reminder`, no pending `arraysubs_generate_upcoming_renewals` for this subscription, no pending `arraysubs_send_expiring_soon`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.6 — Verify Activity Audits and Scheduled-Job Logs
**Steps:**
1. Open Activity Audits. Filter Author = System and Entity = Subscription.
2. Find the most recent row for this subscription.
3. Click `changes →`. Confirm Status: Active → Expired.
4. Open Scheduled-Job Logs. Confirm a Success row for "Expire Subscription" (or "Check Overdue Renewals" if expiration was handled inline by the overdue checker).

**Expected Result:**
- Audit row records the Active → Expired transition.
- Scheduled-Job Logs records the hook execution with Success status.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.7 — Verify customer portal reflects expiration
**Steps:**
1. Open incognito. Log in as `member-fixed@example.com`.
2. Go to **My Account → Subscriptions**.
3. Inspect the expired subscription.

**Expected Result:**
- Subscription is shown with status **Expired**.
- Reactivate option may be visible (depending on settings); customer cannot otherwise perform actions on an expired subscription except reactivate.
- Premium content access tied to this subscription is now restricted (verify by visiting a previously unlocked URL).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm `_cancellation_reason` is NOT set to `overdue_payment` for this subscription (would indicate the wrong code path fired). Expected: empty / unset, since this is natural expiration.
- Confirm the Subscription Cancelled email was NOT sent.
- Cross-check that the auto-downgrade filter (`arraysubs_should_expire_subscription`) was NOT intercepted to switch the subscription, because no auto-downgrade target was configured. If a downgrade target had been configured, the subscription would switch instead of expire — record outcome of optional secondary test on a separate subscription: ____.
- Confirm the overdue checker did not move other subscriptions inadvertently.
- For Stage 19+ tests, an admin can reactivate this subscription by changing status back to Active — restoring runs the renewal recalculation logic.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- Original / new end date: ____ / ____
- Expiration timestamp:
- Notes:
