# Stage 20 — Task 03: Cron Stoppage and Recovery

| Key | Value |
|---|---|
| Stage | 20 — Edge Cases & Final Regression |
| Module | Cron / Action Scheduler |
| Plugin Coverage | Free |
| Estimated Time | 35 min (mostly waiting) |
| Depends On | 02 — Core deactivation |

## Objective
Disable WP-Cron via `define('DISABLE_WP_CRON', true)` in `wp-config.php` and stop the server-side cron that triggers `wp-cron.php`. Allow several scheduled actions to pile up — at least one renewal invoice generation due, and one trial conversion due. Then re-enable cron. Verify that the **Generate Upcoming Renewals** hourly safety job catches up on every missed renewal invoice, and the daily **Process Trial Conversions** job catches up on the missed trial conversion. No subscription should be stuck in a half-state.

## Pre-conditions
- SSH or WP-CLI access to the server.
- ArraySubs core active.
- A subscription whose **next payment date** can be set to a value 30 minutes from now (call it Sub-A).
- A trial subscription whose trial end is set to ~10 minutes from now (call it Sub-B).
- Access to `wp-config.php` for editing.
- Server cron job (e.g. `crontab -l` or system cron config) that hits `wp-cron.php` is locatable so it can be disabled.

## Test Data
- Sub-A ID: `<record>`. Set its next-payment to `now + 30 min`.
- Sub-B ID: `<record>`. Set its trial-end / next-payment to `now + 10 min`.
- Time of cron stoppage: `<record>`.
- Time of cron resumption: `<record>`.

## Sub-Tasks

### Sub-Task 03.1 — Stop WP-Cron and server cron
**Steps:**
1. SSH to the server.
2. Edit `wp-config.php` and add `define('DISABLE_WP_CRON', true);` (or set its value to `true` if already present).
3. Disable the system cron entry that pings `wp-cron.php`. Comment it out in the crontab and reload cron, or stop the cron service.
4. From the host, run `wp cron event list` (WP-CLI) — record the output. Cron should be listed but the system has no scheduler to run it.
5. Open the WP admin **Tools → Scheduled Actions** page and note the queue.

**Expected Result:**
- WP-Cron is disabled.
- No external pinger is hitting `wp-cron.php`.
- The Action Scheduler queue page shows pending actions but they are not advancing.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.2 — Set up the missed actions
**Steps:**
1. Edit Sub-A's next-payment date to `now + 30 min`. Save.
2. Edit Sub-B's trial-end / next-payment to `now + 10 min`. Save.
3. Wait until both target times have passed (~ 35 minutes total — go do something else).

**Expected Result:**
- After waiting:
  - The hourly **Generate Upcoming Renewals** action should have been due to run at least once but did not.
  - The per-subscription renewal invoice generation action for Sub-A should be past due.
  - The daily trial conversion check should be past due (depending on time of day).
- Confirm via **Tools → Scheduled Actions** that the "Pending" queue has actions whose scheduled date is in the past.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.3 — Verify subscriptions are stuck (cron is genuinely off)
**Steps:**
1. Open Sub-A in admin. Confirm: status still **Active**, next payment date in the past, **no pending renewal order yet** generated.
2. Open Sub-B in admin. Confirm: status still **Trial**, trial-end date in the past, status **not yet** advanced to Active.

**Expected Result:**
- No automation has fired. Both subscriptions are visibly stuck.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.4 — Re-enable cron
**Steps:**
1. Edit `wp-config.php` and remove the `DISABLE_WP_CRON` line (or set it to `false`).
2. Re-enable the system cron entry; reload the cron service.
3. Optionally, force an immediate run with `wp cron event run --due-now --all` to avoid waiting for the next system cron cycle.

**Expected Result:**
- Cron is back. The next cron invocation will pick up overdue actions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.5 — Generate Upcoming Renewals catches up
**Steps:**
1. Wait at most one hour (or trigger via WP-CLI / the Run button in **Tools → Scheduled Actions**) for the hourly **Generate Upcoming Renewals** safety job to fire.
2. Refresh **Tools → Scheduled Actions** and look at recent completed actions.
3. Open Sub-A's detail page.

**Expected Result:**
- A new pending renewal order has been created for Sub-A. Its order number and creation timestamp are recent (after cron resumed).
- Subscription notes record the invoice creation.
- A Renewal Invoice email has been sent to the customer.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.6 — Trial conversion catches up
**Steps:**
1. Trigger or wait for the daily **Process Trial Conversions** job. To skip waiting, run it manually from **Tools → Scheduled Actions** by clicking **Run** on the relevant pending action, or `wp cron event run arraysubs_process_trial_conversions`.
2. Open Sub-B in admin.

**Expected Result:**
- Sub-B's status has advanced from Trial to **Active** (or to the auto-downgrade product if one was configured on its product).
- A Trial Converted email was sent.
- The trial-length meta has been cleared on Sub-B.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.7 — No actions stuck in Failed state
**Steps:**
1. Open **Tools → Scheduled Actions → Failed** tab.

**Expected Result:**
- No `arraysubs_*` actions are in the Failed list as a result of the stoppage.
- All formerly past-due actions are either Completed or rescheduled in the future.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm `wp-cron.php`'s lock mechanism didn't trigger a thundering-herd on resumption (no duplicate invoices for Sub-A — exactly one pending renewal order).
- Run the **Check Overdue Renewals** action manually one more time and confirm Sub-A is not erroneously moved to On-Hold (the grace period clock should reset based on the **new** invoice creation, not the original missed date).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
