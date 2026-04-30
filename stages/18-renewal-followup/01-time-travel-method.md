# Stage 18 — Task 01: Time-Travel Method

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Test Methodology |
| Plugin Coverage | Free + Pro |
| Estimated Time | 30 min |
| Depends On | Stage 02 (Action Scheduler reachable), Stage 03 (test catalog), Stage 06 (test subscriptions seeded) |

## Objective
Document and validate the **time-travel approach** used by every subsequent task in Stage 18. The approach is: (1) edit a subscription's `_next_payment_date` (or `_trial_end_date`, `_end_date`, etc.) directly in the admin to move it into the past; (2) manually trigger the relevant Action Scheduler hook (e.g., `arraysubs_generate_upcoming_renewals`, `arraysubs_check_overdue_renewals`, `arraysubs_process_trial_conversions`) by clicking **Run** in **WooCommerce → Status → Scheduled Actions**; (3) verify the expected lifecycle hook fires by reading the Scheduled-Job Logs and the resulting subscription/order state. Establish a single, repeatable procedure that every later task references. With most products in the canonical catalog being **weekly**, time-travel windows in this stage are typically measured in days (1 hour ago, 4 days ago, 8 days ago) rather than months.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Free + Pro active.
- Action Scheduler accessible at **WooCommerce → Status → Scheduled Actions**.
- Test subscription `member1@example.com` exists on **Standard Weekly $19.99/wk** with **Active** status and a known `_next_payment_date` in the future (record below).
- WP-CLI optionally available on the server (used as fallback if admin meta editing is blocked).

## Test Data
- Test subscription ID: ____
- Original `_next_payment_date` (full timestamp in site timezone): ____
- New `_next_payment_date` for this task (one hour ago): ____
- Site timezone: ____
- Action Scheduler queue runs every: ____ minutes (record from system cron config; default WP-Cron tick on each page request)
- Server local time at start of test: ____

## Sub-Tasks

### Sub-Task 01.1 — Document the time-travel approach in Sign-off
**Steps:**
1. Read the three accepted approaches below and pick ONE that this entire stage will use:
   - **Approach A (RECOMMENDED): Admin meta edit + manual Action Scheduler Run.** Edit `_next_payment_date` on the subscription detail page (or via the Custom Fields panel). Then go to Scheduled Actions and click Run on the next pending hook.
   - **Approach B: System clock change.** Move the server clock forward by N days. Wait for WP-Cron / Action Scheduler to tick naturally. Restore clock at end of each task. (Riskier — affects unrelated tests, gateways, JWTs.)
   - **Approach C: WP-CLI + scheduled-action runner.** Use `wp post meta update SUBSCRIPTION_ID _next_payment_date "YYYY-MM-DD HH:MM:SS"` then `wp action-scheduler run --hooks=arraysubs_generate_upcoming_renewals --batch=10`.
2. Record the chosen approach (A / B / C) in the Sign-off block at the bottom of this file.
3. Every other task in Stage 18 will reference "the chosen time-travel approach from Task 18.01". If a tester deviates for a specific task, they must note the deviation in that task's Sign-off.

**Expected Result:**
- A single approach is selected and recorded.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.2 — Locate the test subscription and record the original next-payment-date
**Steps:**
1. Go to **ArraySubs → Subscriptions**.
2. Open `member1@example.com`'s Active subscription.
3. Find the **Next Payment Date** field on the detail page (or in the Custom Fields panel for `_next_payment_date`).
4. Record the original value in the Test Data block above.

**Expected Result:**
- Original next-payment-date is captured in writing so it can be restored after the task.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.3 — Edit `_next_payment_date` to one hour ago
**Steps:**
1. Using the chosen approach:
   - **A:** Edit the Next Payment Date field directly in the admin UI to a timestamp one hour earlier than the current server time. Save.
   - **C:** Run `wp post meta update SUBSCRIPTION_ID _next_payment_date "YYYY-MM-DD HH:MM:SS"` substituting the current time minus 1 hour.
2. Refresh the subscription detail page.
3. Confirm the Next Payment Date now shows the past timestamp.
4. Confirm the subscription status remains **Active** (date editing alone does not change status).

**Expected Result:**
- Next Payment Date displays a past timestamp.
- Subscription status unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.4 — Run `arraysubs_generate_upcoming_renewals` manually
**Steps:**
1. Open **WooCommerce → Status → Scheduled Actions** in a new tab.
2. In the search box, type `arraysubs_generate_upcoming_renewals`.
3. Filter status to **Pending**. Find the next scheduled occurrence.
4. Click **Run** on that row.
5. Wait 5 seconds, then refresh the Scheduled Actions list.
6. Confirm the action moves to **Complete** (or shows a green check).
7. Read the action's log (click into it) to confirm it processed without exception.

**Expected Result:**
- The `arraysubs_generate_upcoming_renewals` action ran successfully.
- The action log records a completion timestamp matching "just now".
- No errors are logged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.5 — Verify a pending renewal order was created on the test subscription
**Steps:**
1. Return to the test subscription detail page.
2. Refresh.
3. Scroll to the **Related Orders** card.
4. Read the most recent row.

**Expected Result:**
- A new order with status **Pending payment** is now linked to the subscription.
- The order's total matches the subscription's recurring amount ($19.99 for Standard Weekly).
- The order's notes include text like "Renewal order for subscription #NNN (Cycle #X, scheduled for ...)".
- The subscription's `_pending_renewal_order_id` meta is set to this new order ID.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.6 — Verify Scheduled-Job Logs records the hook (Pro)
**Steps:**
1. Go to **ArraySubs → Audits [beta] → Scheduled-Job Logs** (Pro only — skip if Free-only environment).
2. Click **Refresh**.
3. Look for the most recent **Generate Upcoming Renewals** row.

**Expected Result:**
- The most recent row has Status **Success**, Job label "Generate Upcoming Renewals", timestamp matching "just now".
- (Pro) An audit row also appears in **Activity Audits** under Author = System for the renewal invoice creation.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.7 — Restore the original next-payment-date and cancel the test order
**Steps:**
1. Cancel the pending renewal order created in Sub-Task 01.5 (set order status to Cancelled in WooCommerce, OR pay it — record which: ____). Cancelling preserves the subscription for downstream tasks.
2. On the subscription detail page, restore `_next_payment_date` to the original value recorded in Sub-Task 01.2.
3. Confirm the subscription detail page now shows the restored date.
4. Confirm subscription status is still **Active**.

**Expected Result:**
- The subscription is back to its original state (next-payment-date restored, no orphaned pending renewal order, status Active).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.8 — Document the procedure for downstream tasks
**Steps:**
1. In a shared QA notes file (or directly in the Sign-off below), write a short, unambiguous version of the procedure that downstream task files will reference. Example:
   ```
   TIME-TRAVEL PROCEDURE (chosen approach: A)
   1. Open subscription detail page.
   2. Change _next_payment_date (or _trial_end_date, _end_date) to the desired past timestamp.
   3. Save.
   4. Go to WooCommerce → Status → Scheduled Actions.
   5. Manually click Run on the relevant arraysubs_* hook listed in the task.
   6. Wait 30 seconds for any gateway round-trip.
   7. Verify expected lifecycle outcome: status change, order created, email sent.
   8. Restore original timestamps and clean up at end of task.
   ```
2. Confirm every downstream task references this procedure by name ("Use the time-travel approach from Task 18.01").

**Expected Result:**
- Downstream tasks have a single source of truth for time-travel.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm no other test subscriptions were inadvertently affected by editing the Action Scheduler queue.
- Confirm Action Scheduler queue is healthy: search **arraysubs_** and verify there are pending future hooks (renewal invoice generation hourly, trial conversions daily, overdue checker hourly).
- Confirm the test subscription's `_pending_renewal_order_id` meta is now empty (cleared because we cancelled the test order).
- If approach B (system clock) was used, confirm the clock is restored to current real-world time before continuing to Task 18.02 — failing to do so will corrupt every downstream test.

## Sign-off
- Tester:
- Date:
- Browser & version:
- **Chosen time-travel approach (A / B / C):** ____
- Test subscription ID:
- Original next-payment-date / restored: ____ / ____
- Action Scheduler hooks observed firing:
- Notes:
