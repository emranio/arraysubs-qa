# Stage 13 — Task 05: Enable / Disable Toggles

| Key | Value |
|---|---|
| Stage | 13 — Email Notifications |
| Module | Emails — Toggle behaviour |
| Plugin Coverage | Both |
| Estimated Time | 35 min |
| Depends On | 13.01, 13.02 |

## Objective
Disable each customer email type one at a time, trigger the related lifecycle event, and confirm no email is delivered. Re-enable and confirm delivery is restored. This proves that each per-email toggle in **WC → Settings → Emails** independently controls whether that one email type fires, without affecting any other email.

## Pre-conditions
- 13.01 and 13.02 complete.
- All ArraySubs / ArraySubsPro emails are currently **Enabled** in **WC → Settings → Emails**.
- Mailbox capture for `cust1@test.local`, `cust2@test.local`, and `cust3@test.local`.
- Customers across `cust1` / `cust2` have access to: an active `Trial Weekly` subscription, an active `Standard Weekly` subscription, and a `Fixed-Length Weekly (6 cycles)` subscription.
- Stripe is already configured in test mode (used as the automatic gateway for these scenarios).
- Time-travel method available.

## Test Data
- Iteration list (one row per Sub-Task — disable, trigger, verify, re-enable):
  1. New Subscription
  2. Trial Started
  3. Trial Converted
  4. Renewal Reminder
  5. Renewal Invoice
  6. Payment Successful
  7. Payment Failed
  8. Subscription On Hold
  9. Subscription Cancelled
  10. Subscription Expired
  11. Subscription Reactivated
  12. Auto-Downgrade
  13. Retention Discount Accepted

## Sub-Tasks

### Sub-Task 5.1 — Disable / re-enable: New Subscription
**Steps:**
1. **WC → Settings → Emails → New Subscription → Manage**.
2. Untick **Enable this email notification**, save.
3. As `cust2@test.local`, checkout `Standard Weekly`. Mark order Processing if using a manual gateway.
4. Confirm `cust2`'s inbox.
5. Re-enable the email and trigger another activation; confirm delivery resumes.

**Expected Result:**
- With the toggle Off, no New Subscription email arrives at `cust2`.
- The subscription still activates correctly.
- The Admin New Subscription email still fires (independent toggle).
- After re-enabling, the next activation produces a New Subscription email.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.2 — Disable / re-enable: Trial Started
**Steps:**
1. Disable Trial Started in WC email settings.
2. As `cust2`, checkout `Trial Weekly` (7-day trial, $19.99/week).
3. Confirm inbox.
4. Re-enable and re-test by checking out the trial product with another customer (or reset cust2 if needed).

**Expected Result:**
- No Trial Started email when toggle Off.
- Email returns when re-enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.3 — Disable / re-enable: Trial Converted
**Steps:**
1. Disable Trial Converted.
2. Time-travel a trial subscription to its conversion date; run cron.
3. Confirm inbox.
4. Re-enable and run another conversion event.

**Expected Result:**
- Conversion happens (subscription switches to Active) but no email is sent.
- After re-enable, next conversion triggers the email.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.4 — Disable / re-enable: Renewal Reminder
**Steps:**
1. Disable Renewal Reminder.
2. Time-travel an active subscription so the reminder schedule fires (or run the `arraysubs_send_renewal_reminder` action).
3. Confirm inbox.
4. Re-enable and test again.

**Expected Result:**
- No Renewal Reminder email when Off.
- Returns when On.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.5 — Disable / re-enable: Renewal Invoice
**Steps:**
1. Disable Renewal Invoice.
2. Time-travel a renewal so an invoice order is generated.
3. Confirm inbox.
4. Re-enable and run another renewal.

**Expected Result:**
- No Renewal Invoice email when Off.
- Renewal order is still created.
- Email returns when On.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.6 — Disable / re-enable: Payment Successful
**Steps:**
1. Disable Payment Successful.
2. Process a successful renewal payment.
3. Confirm inbox.
4. Re-enable.

**Expected Result:**
- Payment processes successfully but no email fires.
- Re-enabling restores delivery for the next successful payment.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.7 — Disable / re-enable: Payment Failed
**Steps:**
1. Disable Payment Failed.
2. Trigger a failed renewal using `4000 0000 0000 0341`.
3. Confirm inbox.
4. Re-enable.

**Expected Result:**
- No Payment Failed customer email when Off.
- The subscription still goes into the failed-payment / on-hold flow.
- Re-enabling restores delivery.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.8 — Disable / re-enable: Subscription On Hold
**Steps:**
1. Disable Subscription On Hold.
2. Force a subscription into On Hold (admin status change).
3. Confirm inbox.
4. Re-enable.

**Expected Result:**
- No On Hold email when Off; subscription state still changes.
- Re-enabling restores delivery.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.9 — Disable / re-enable: Subscription Cancelled
**Steps:**
1. Disable Subscription Cancelled.
2. Cancel a subscription via admin or customer portal.
3. Confirm inbox.
4. Re-enable.

**Expected Result:**
- No customer Cancelled email when Off.
- Admin Subscription Cancelled email still fires (independent toggle).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.10 — Disable / re-enable: Subscription Expired
**Steps:**
1. Disable Subscription Expired.
2. Time-travel a fixed-length subscription past its end date.
3. Confirm inbox.
4. Re-enable.

**Expected Result:**
- No Expired email when Off.
- Status changes to Expired regardless.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.11 — Disable / re-enable: Subscription Reactivated
**Steps:**
1. Disable Subscription Reactivated (note the manual states this email always fires unless explicitly toggled off in WC email settings — confirm the toggle is honoured).
2. Reactivate a cancelled subscription.
3. Confirm inbox.
4. Re-enable.

**Expected Result:**
- With the WC toggle Off, no Reactivated email is delivered.
- Re-enabling restores delivery.
- Document any deviation from the manual's note.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.12 — Disable / re-enable: Auto-Downgrade
**Steps:**
1. Disable Auto-Downgrade.
2. Trigger an auto-downgrade event (Stage 03 / 08 setup).
3. Confirm inbox.
4. Re-enable.

**Expected Result:**
- No Auto-Downgrade email when Off.
- Product change still happens.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.13 — Disable / re-enable: Retention Discount Accepted
**Steps:**
1. Disable Retention Discount Accepted.
2. As a customer, run the cancellation flow and accept a discount offer.
3. Confirm inbox.
4. Re-enable.

**Expected Result:**
- No Retention Discount Accepted email when Off.
- Discount is still applied to the subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After all 13 disable/re-enable cycles, confirm every email in **WC → Settings → Emails** is back to **Enabled**.
- Run a full lifecycle event (e.g., another new subscription activation) and confirm both the customer New Subscription and the Admin New Subscription emails fire — a sanity check that nothing was left disabled.
- DevTools / debug.log: zero new errors.

## Sign-off
- Tester:
- Date:
- Browser & version:
- All emails restored to Enabled: yes / no
- Notes:
