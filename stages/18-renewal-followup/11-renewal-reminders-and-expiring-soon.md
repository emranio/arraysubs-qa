# Stage 18 — Task 11: Renewal Reminders & Subscription Expiring Soon

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Renewal Communication (Renewal Reminder, Subscription Expiring Soon) |
| Plugin Coverage | Free + Pro |
| Estimated Time | 35 min |
| Depends On | Task 18.01, Task 18.02 (renewal cycle verified) |

## Objective
Configure **Renewal Reminder Days Before = 1** in General Settings (since the test catalog is dominated by **weekly** subscriptions, a 1-day reminder is the natural cadence; **3 days before** is also acceptable per the user manual — pick whichever the manual permits and you can reliably exercise). Time-travel a **Standard Weekly $19.99/wk** subscription to **2 days before** its next payment date and confirm NO Renewal Reminder email fires. Then time-travel to **1 day before** and run the per-subscription `arraysubs_send_renewal_reminder` hook to confirm the email fires at the 1-day mark and not earlier. Then, on the **Limited 3-Cycle Weekly** subscription (subscription length = 3) that has already completed **2** successful renewals, time-travel near the third (final) renewal and verify the **Subscription Expiring Soon** email fires.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- Customer `member1@example.com` has an Active subscription on **Standard Weekly** ($19.99/wk, no length limit) — used for the reminder test.
- Customer `member-limited@example.com` has an Active subscription on **Limited 3-Cycle Weekly** ($19.99/wk, **Subscription Length = 3**) — used for the expiring-soon test. This subscription should already have **2 completed paid renewals** so the next (third) renewal is the final paid renewal and the expiring-soon email should fire ahead of it.
- Settings → General Settings:
  - **Renewal Reminder Days Before = 1** (preferred for weekly cadence; **3** is an acceptable alternative per the user manual — record which you used)
  - Renewal Reminder email = Enabled
  - Subscription Expiring Soon email = Enabled
- Time-travel approach from Task 18.01 ready.
- Mail catcher reachable.

## Test Data
- Reminder-test subscription ID: ____ (`member1@example.com`, Standard Weekly $19.99/wk)
- Expiring-test subscription ID: ____ (`member-limited@example.com`, Limited 3-Cycle Weekly $19.99/wk)
- Renewal Reminder Days Before: **1** (or 3 — record actual: ____)
- Limited 3-Cycle Weekly length: 3 cycles
- Limited 3-Cycle Weekly starting completed-payments counter: **2** (so the next renewal is renewal #3, the last paid renewal)

## Sub-Tasks

### Sub-Task 11.1 — Verify Renewal Reminder Days Before
**Steps:**
1. Go to **ArraySubs → Settings → General Settings**.
2. Find **Renewal Reminder Days Before**.
3. Confirm value = **1** (preferred for weekly cadence). If the user manual permits **3** and you want to test that instead, set it to 3 and save. Record the value you tested with: ____.

**Expected Result:**
- Setting reads `1` (or `3` if you chose the alternative).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.2 — Time-travel reminder-test subscription to 2 days before next payment
**Steps:**
1. Open the reminder-test subscription detail page.
2. Record the original `_next_payment_date`: ____
3. Pick a target near-term date — e.g., today + 2 days at 12:00 — and edit `_next_payment_date` to that value. Save.
4. The system should have queued an `arraysubs_send_renewal_reminder` action for `_next_payment_date - 1 day` = today + 1 day at 12:00. Open Scheduled Actions and verify by searching `arraysubs_send_renewal_reminder` and finding the action whose args reference this subscription. Record the action's scheduled-at timestamp: ____.

**Expected Result:**
- A pending `arraysubs_send_renewal_reminder` is scheduled at `_next_payment_date - 1 day`.
- That scheduled-at is in the future (today + 1 day), NOT today.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.3 — Confirm no reminder email yet (we are at 2 days before)
**Steps:**
1. Open the mail catcher and search for **Renewal Reminder** emails to `member1@example.com`.
2. Confirm no new reminder has been sent for this cycle.

**Expected Result:**
- No Renewal Reminder email present (because the scheduled action's run-time is still in the future).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.4 — Time-travel to 1 day before next payment → reminder fires
**Steps:**
1. Edit `_next_payment_date` so that the date is today + 1 day at 12:00 (so we are exactly 1 day out). The previously-scheduled `arraysubs_send_renewal_reminder` should now be at "today at 12:00" or in the past — record the action's new scheduled-at: ____.
   - **Note:** Editing `_next_payment_date` does not automatically reschedule the existing reminder action. If your time-travel approach requires it, also edit the action's run-time in Scheduled Actions OR delete the existing reminder action and re-trigger it by re-editing the date or by running it manually.
2. In **WooCommerce → Status → Scheduled Actions**, find the `arraysubs_send_renewal_reminder` action for this subscription and click **Run**.
3. Wait 5 seconds.
4. Open the mail catcher.

**Expected Result:**
- The **Renewal Reminder** email arrives in the mail catcher with timestamp matching "just now".
- Email body says "Your subscription will renew in 1 day" (or "in 3 days" if you tested with reminder = 3), shows the recurring amount $19.99, the upcoming payment date, and the subscription product (Standard Weekly).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.5 — Verify reminder is NOT sent twice
**Steps:**
1. Click **Run** on the same `arraysubs_send_renewal_reminder` action again from Scheduled Actions (or trigger again).
2. Check the mail catcher for a second reminder.

**Expected Result:**
- The action either no longer exists (already executed) OR running it does NOT produce a duplicate email if the subscription tracks "last reminder sent" or the action was already removed after firing.
- (If the system DOES allow running the same hook again, that produces a second email — record this behaviour and flag it for product team review.)

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.6 — Capture starting state of expiring-test subscription
**Steps:**
1. Open the expiring-test subscription detail page.
2. Confirm:
   - Subscription Length = 3
   - Completed-payments counter = ≥ 2 (record exact: ____)
   - Status = Active
3. Record the current `_next_payment_date`: ____

**Expected Result:**
- Subscription is one renewal away from reaching the length limit.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.7 — Time-travel near subscription length end → Subscription Expiring Soon email
**Steps:**
1. The Subscription Expiring Soon email is normally fired by `arraysubs_send_expiring_soon` scheduled some configurable number of days before the subscription's effective end. With Limited 3-Cycle Weekly and counter = 2, the next renewal will be the **third (last) paid renewal** — the engine schedules the expiring-soon email shortly before that final renewal date.
2. Bring the next-payment-date forward (e.g., today + N days where N matches the Expiring Soon timing setting; if no specific setting exists, use 1–3 days to align with weekly cadence). Save.
3. In **WooCommerce → Status → Scheduled Actions**, search for `arraysubs_send_expiring_soon` and find the action scoped to this subscription.
4. Click **Run**.
5. Wait 5 seconds. Open the mail catcher.

**Expected Result:**
- The **Subscription Expiring Soon** email arrives at `member-limited@example.com` with timestamp matching "just now".
- Email body says "Your subscription is expiring soon" (or equivalent), references the upcoming end of the subscription length (after the third renewal), and shows the final renewal information.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.8 — Verify hooks fired and audit
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions** → filter Status = Complete.
2. Confirm completed rows for `arraysubs_send_renewal_reminder` and `arraysubs_send_expiring_soon`.
3. (Pro) Open Scheduled-Job Logs. Confirm Success rows for "Renewal Reminder Email" and "Expiring Soon Email".
4. Open Activity Audits. Filter Entity = Email. Confirm two new rows for the reminder and expiring-soon emails.

**Expected Result:**
- Both hooks completed.
- Audit trail records the two email sends.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Restore `Renewal Reminder Days Before` to its original value if you changed it.
- Restore the reminder-test subscription's `_next_payment_date` to the original value to keep state consistent for any later tests.
- Confirm that pausing or cancelling a subscription before the reminder fires causes the reminder NOT to fire (test optionally on a third subscription — record outcome: ____).
- Verify the Renewal Reminder email and Expiring Soon email use the configured email templates (preview in **WooCommerce → Settings → Emails**).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Reminder-test subscription ID:
- Expiring-test subscription ID:
- Reminder fired at (timestamp):
- Expiring-soon fired at (timestamp):
- Notes:
