# Stage 17 — Task 03: Scheduled-Job Logs

| Key | Value |
|---|---|
| Stage | 17 — Audits, Logs & Troubleshooting |
| Module | Audits → Scheduled-Job Logs |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | Task 17.01 (audit screen reachable) |

## Objective
Verify the **ArraySubs → Audits [beta] → Scheduled-Job Logs** screen renders the job log with all columns (Date, Status, Job, Details), shows correct human-readable labels for known Action Scheduler hooks, links subscription-specific jobs to the related subscription, distinguishes Success vs Failed rows visually, and that triggering a real renewal-invoice generation hook (`arraysubs_generate_upcoming_renewals`) produces a Success log row.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active.
- At least one Active subscription (`member1@example.com` recommended) whose **Next Payment Date** is within the next few hours, OR an admin can edit `_next_payment_date` to be in the past so the renewal hook is dispatched on the next Action Scheduler tick.
- Access to **WooCommerce → Status → Scheduled Actions** so we can trigger queued actions on demand.

## Test Data
- Test subscription (ID: ____)
- Original `_next_payment_date` value: ____ (record before editing)
- New `_next_payment_date` value (one hour ago, in site timezone): ____

## Sub-Tasks

### Sub-Task 03.1 — Open Scheduled-Job Logs and verify table columns
**Steps:**
1. Go to **ArraySubs → Audits [beta] → Scheduled-Job Logs**.
2. Read the column headers.
3. Note the pagination total at the bottom: ____

**Expected Result:**
- Four columns: **Date**, **Status**, **Job**, **Details**.
- Newest entries at the top.
- Failed rows (if any) have a red background tint; Status badge shows red **Failed** with X icon.
- Successful rows show green **Success** badge with checkmark.
- Pagination shows `Page X of Y (Z total)` at 30 per page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.2 — Verify Job labels are human-readable
**Steps:**
1. Scan the **Job** column on Page 1.
2. Look for at least three of these labels (depending on what has run): **Process Renewal**, **Generate Upcoming Renewals**, **Check Overdue Renewals**, **Trial Conversion**, **Renewal Reminder Email**, **Cleanup Webhook Events**.
3. For any subscription-scoped job, confirm a `#NNN` link is present in the Job cell.
4. Click one `#NNN` link and confirm it opens the subscription detail page in the same tab or a new tab.

**Expected Result:**
- All visible Job labels are human-readable (e.g., "Process Renewal" — NOT the raw hook name `arraysubs_process_renewal`).
- Subscription-scoped jobs include a clickable `#NNN` linking to the subscription.
- Batch jobs (Generate Upcoming Renewals, Check Overdue Renewals) have NO subscription link.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.3 — Filter by date range
**Steps:**
1. In the filter bar, set **From** = 7 days ago, **To** = today.
2. Confirm only rows in that window appear.
3. Click **Reset**.
4. Set **From** = today, **To** = today. Confirm only today's rows appear.
5. Click **Refresh** (spinning icon) to reload with the active filter.

**Expected Result:**
- Date filter narrows the result set inclusively on both ends.
- Reset clears both fields and restores the unfiltered list.
- Refresh reloads without changing the filter.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.4 — Trigger a known scheduled job (renewal invoice generation)
**Steps:**
1. Open **ArraySubs → Subscriptions** in a new tab. Open the test subscription.
2. Edit the **Next Payment Date** to one hour ago (record both the original and new values in Test Data above). Save.
3. Go to **WooCommerce → Status → Scheduled Actions**.
4. Search for `arraysubs_generate_upcoming_renewals`. The next scheduled run is hourly. Click **Run** on the next pending row to execute it now.
5. If `arraysubs_process_renewal` becomes scheduled for this specific subscription after the generate job runs, also click **Run** on that row.
6. Wait 5 seconds. Return to **ArraySubs → Audits [beta] → Scheduled-Job Logs**.
7. Click **Refresh**.

**Expected Result:**
- A new row at the top with Job label **Generate Upcoming Renewals** and Status **Success** (green badge, checkmark) appears with a timestamp matching "just now".
- Optionally, a **Process Renewal** row also appears, scoped to the test subscription with `#NNN` link.
- The Details column shows a context line (e.g., "1 invoice generated" or similar success message).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.5 — Verify the renewal invoice was created
**Steps:**
1. Navigate to the test subscription's detail page.
2. Scroll to the **Related Orders** card.
3. Confirm a new pending renewal order exists with status **Pending payment**.
4. Confirm the order's total matches the subscription's recurring price.

**Expected Result:**
- A pending renewal order is now linked to the subscription.
- The subscription's `_pending_renewal_order_id` meta is set (visible on the detail page).
- Activity Audits also records a corresponding Subscription/Order audit row (cross-check by switching to Activity Audits and filtering Entity = Order).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.6 — Force a known failure and verify the Failed row
**Steps:**
1. Edit the test subscription and clear the `_product_id` meta (or set it to a non-existent product ID like `999999`) — this will cause Process Renewal to fail when it next runs. (Use the Custom Fields panel or a debug snippet — record exact method used: ____.)
2. Edit `_next_payment_date` to one hour ago.
3. In **WooCommerce → Status → Scheduled Actions**, manually run the next `arraysubs_process_renewal` action for this subscription (or trigger via Generate Upcoming Renewals if no per-subscription action is scheduled).
4. Return to Scheduled-Job Logs and click Refresh.

**Expected Result:**
- A new row appears with Status **Failed** (red badge with X icon, red background tint).
- The Job label is **Process Renewal** with the test subscription `#NNN` link.
- The Details column shows the error message (e.g., "Product not found" or similar).
- Restore `_product_id` to the correct value before continuing — record original product ID: ____.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.7 — Pagination
**Steps:**
1. Reset filters.
2. If pagination shows more than one page, click **Next**, confirm Page indicator increments, click **Previous** to return.

**Expected Result:**
- Pagination works at 30 entries per page; Previous disabled on Page 1, Next disabled on the last page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Cancel the pending renewal order created in Sub-Task 03.5 (or pay it to keep the subscription healthy — record which: ____) so Stage 18 doesn't double-trigger.
- Restore `_next_payment_date` to its original value.
- Confirm Activity Audits (Task 17.01) shows correlated rows for the Process Renewal job (Subscription entity audit row mirroring the system action).
- Verify the Failed row is still visible after a page refresh — failed rows are persistent.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- Original next_payment_date / restored:
- Notes:
