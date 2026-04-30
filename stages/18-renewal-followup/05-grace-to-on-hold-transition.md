# Stage 18 — Task 05: Grace → On-Hold Transition

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Recovery and Grace Flows (Phase 2: On-Hold) |
| Plugin Coverage | Pro |
| Estimated Time | 35 min |
| Depends On | Task 18.04 (active-grace state established and verified) |

## Objective
Continuing from Task 04: re-create the failed-renewal state on `member-decline@example.com`, then time-travel further so the unpaid renewal is now more than 3 days past due. Run the `arraysubs_check_overdue_renewals` hook (which fires hourly) and confirm the subscription transitions automatically from **Active** to **On Hold**, the **Subscription On-Hold** email is sent, customer access is restricted as configured, and that paying the outstanding renewal during the on-hold window restores the subscription to Active.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active.
- Customer `member-decline@example.com` has an Active subscription on **Standard Weekly $19.99/wk** with the declining card `4000 0000 0000 0341` set as default. (If Task 04 ended with the working `4242` card, swap it back for this task.)
- Settings → General Settings: Active Grace Days = **3**, On-Hold Grace Days = **7**.
- Subscription On-Hold email = Enabled.
- Member access rules configured so On-Hold subscriptions are restricted (Stage 09 Task 10 verified).
- Time-travel approach from Task 18.01 ready.

## Test Data
- Test subscription ID: ____
- Saved card: 0341 (declining)
- Recurring price: **$19.99 / week**
- Billing period: weekly
- Pre-test status: Active
- Time-travel target for next-payment-date: **4 days ago** (past the 3-day active grace boundary)

## Sub-Tasks

### Sub-Task 05.1 — Re-create the failed-renewal state
**Steps:**
1. Confirm the saved card is `0341`. If not, replace it now.
2. Apply the Task 18.01 time-travel procedure: edit `_next_payment_date` to **4 days ago** (past the 3-day active grace period). Save.
3. Run `arraysubs_generate_upcoming_renewals` and then `arraysubs_process_renewal` from Scheduled Actions.
4. Wait 30 seconds.
5. Refresh the subscription detail page.

**Expected Result:**
- A new pending renewal order was created and immediately failed at Stripe (status Failed).
- Subscription `_pending_renewal_order_id` set, `_last_payment_failure_category` set.
- Subscription status is currently **Active** (immediately after process_renewal — the overdue checker has not yet run).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.2 — Run the hourly overdue checker
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Search for `arraysubs_check_overdue_renewals`. Find the next pending row.
3. Click **Run**.
4. Wait 5 seconds. Refresh the subscription detail page.

**Expected Result:**
- Subscription status transitions from **Active** to **On Hold**.
- `_on_hold_date` meta is set to the current timestamp.
- Subscription notes include a system note like "Subscription placed on hold — overdue renewal payment" or similar.
- `_pending_renewal_order_id` still references the failed renewal order (the unpaid invoice is still pending).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.3 — Verify the Subscription On-Hold email
**Steps:**
1. Open the mail catcher.
2. Find the **Subscription On-Hold** email to `member-decline@example.com` (timestamp matching the hourly check execution).
3. Open and inspect the body.

**Expected Result:**
- Email exists with the subscription details.
- Body explains the access restriction.
- No payment link in the on-hold email — the customer is expected to use the prior Renewal Invoice / Payment Failed email's pay link.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.4 — Verify customer access is restricted
**Steps:**
1. Open an incognito window. Log in as `member-decline@example.com`.
2. Visit a piece of premium content that the Standard Weekly subscription previously unlocked (e.g., `/premium/article-1` or a role-mapped resource — use any path that Stage 09's access rules protected).
3. Inspect the access result.

**Expected Result:**
- Access is restricted: the page shows the configured restriction message ("This content is restricted. Please subscribe to access." or your custom string).
- This confirms the role-mapping / member-access rules respect the On-Hold status.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.5 — Customer pays the outstanding invoice → restored to Active
**Steps:**
1. From the mail catcher, open the **Renewal Payment Failed** email (sent during Sub-Task 05.1).
2. Copy the Pay Now link.
3. As `member-decline@example.com` in the incognito session, paste the link.
4. On the Pay-for-Order page, choose Stripe and enter the working test card `4242 4242 4242 4242`.
5. Pay.
6. Switch to admin and refresh the subscription detail page.

**Expected Result:**
- Order moves to Completed / Processing.
- Subscription status is automatically restored to **Active**.
- `_on_hold_date` is cleared.
- `_pending_renewal_order_id` is cleared.
- Completed-payments counter incremented by 1.
- New `_next_payment_date` advanced by **1 week** (one billing cycle) from the prior due date.
- Customer receives **Payment Successful** email.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.6 — Verify access restored
**Steps:**
1. In the customer incognito session, hard-refresh the previously restricted page (Ctrl+Shift+R).
2. Confirm the content is now visible.

**Expected Result:**
- Premium content is accessible again — the role-mapping / access rules now permit access because the subscription is Active.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.7 — Verify Action Scheduler dispatched the right hooks
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Filter Status = Complete and Hook contains `arraysubs_`.
3. Confirm completed rows for `arraysubs_check_overdue_renewals` (the run that moved this subscription to On Hold).
4. The exact per-subscription hold action may be dispatched as `arraysubs_hold_subscription` or be embedded inside the overdue checker — record which: ____.
5. Open Scheduled-Job Logs (Pro).
6. Confirm Success rows for "Check Overdue Renewals" and (if applicable) "Hold Subscription".

**Expected Result:**
- All expected hooks observed.
- Scheduled-Job Logs reflects the on-hold transition with a Success row.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Activity Audits — confirm Author = System rows for both the On-Hold transition and the back-to-Active transition.
- Confirm Subscription Cancelled email was NOT sent (we are within the 7-day on-hold grace).
- Verify the saved card in the customer portal is now back to the working `4242` (since they paid with it). Reset it to `0341` if Task 06 needs to re-trigger failure.
- Verify Subscription On-Hold email is suppressed if the email setting is toggled off (optional — only test if you want to verify the toggle).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- On-hold date captured:
- Restored-to-Active timestamp:
- Notes:
