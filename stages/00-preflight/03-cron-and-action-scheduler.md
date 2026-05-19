# Stage 00 — Task 03: Cron and Action Scheduler

| Key | Value |
|---|---|
| Stage | Pre-flight & Environment Verification |
| Module | Cron / Action Scheduler |
| Plugin Coverage | Both |
| Estimated Time | 25 min |
| Depends On | 00-preflight/01-fresh-install-check.md |

## Objective
Confirm WP-Cron is disabled, a real system cron hits `wp-cron.php` at least once per minute, the Action Scheduler page is reachable, and there are no failed or runaway scheduled actions. Renewals, cancellations, and email jobs all run through this pipeline, so it must be healthy before any lifecycle test.

## Pre-conditions
- Stage 00 Task 01 passed.
- SSH or hosting-panel cron access is available.
- The free **WP Crontrol** plugin can be installed (used as the verification surface in Sub-Task 3.4).
- You can edit `wp-config.php`.

## Test Data
- Site URL: copy the value from **Settings → General → Site Address (URL)**.
- Expected cron line: `* * * * * curl -s https://your-site.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1`.
- Expected `wp-config.php` line: `define('DISABLE_WP_CRON', true);`.

## Sub-Tasks

### Sub-Task 3.1 — Confirm DISABLE_WP_CRON is set
**Steps:**
1. Open `wp-config.php` over SSH or the hosting file manager.
2. Search for `DISABLE_WP_CRON`.
3. Confirm the line `define('DISABLE_WP_CRON', true);` exists **above** the `/* That's all, stop editing! */` comment.
4. If the constant is missing or set to `false`, add or update it, save the file.

**Expected Result:**
- `DISABLE_WP_CRON` is defined as `true`.
- The constant is placed above the "stop editing" comment so it is parsed.
- Saving the file does not produce any visible WordPress error on the next page load.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Confirm a system cron is configured
**Steps:**
1. SSH into the server (or open the hosting cron-jobs panel).
2. Run `crontab -l` for the same user that owns the WordPress files (often `www-data`, `nginx`, or the cPanel user).
3. Look for a line that hits `wp-cron.php` on this site.
4. Confirm the schedule is `* * * * *` (every minute) or, at most, every 5 minutes.

**Expected Result:**
- A cron line for this site exists.
- Schedule is at least every 5 minutes (preferably every 1 minute).
- The URL uses `https://` (matching the site's WordPress address).
- The cron user can read the WordPress files.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Manually fire wp-cron.php and confirm a 200 response
**Steps:**
1. From a terminal run `curl -v "https://your-site.com/wp-cron.php?doing_wp_cron"` substituting your site URL.
2. Read the HTTP status line of the response.
3. Read the response body.

**Expected Result:**
- HTTP status is `200`.
- Response body is empty (or a single line of whitespace).
- No redirect, no 401/403, no Cloudflare challenge page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Verify scheduled events are advancing in WP Crontrol
**Steps:**
1. In wp-admin, install and activate the free **WP Crontrol** plugin.
2. Go to **Tools → Cron Events**.
3. Identify any event with a "Next Run" timestamp under 1 minute from now.
4. Wait until the timestamp is in the past, then click **Refresh** in the browser.
5. Confirm the timestamp updates to a future value.

**Expected Result:**
- The "Next Run" column updates within 1 to 2 minutes of its scheduled time.
- No event remains in the past for more than 2 minutes.
- No event shows a status like "missed" persistently.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Inspect the Action Scheduler queue
**Steps:**
1. Go to **Tools → Scheduled Actions** (Action Scheduler is bundled with WooCommerce).
2. Click each tab in turn: **Pending**, **In-progress**, **Failed**, **Complete**, **Canceled**.
3. Note the count under each tab.
4. Filter the **Failed** tab by the search term `arraysubs` and re-check.

**Expected Result:**
- The page loads without error.
- The **Failed** tab count for ArraySubs hooks is `0`.
- The **In-progress** tab does not have items stuck for more than 5 minutes.
- The **Pending** tab shows ArraySubs hooks scheduled in the future (e.g., `arraysubs_generate_upcoming_renewals`, `arraysubs_run_scheduled_*`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Run a known scheduled action manually
**Steps:**
1. In **Tools → Scheduled Actions → Pending**, locate a hook starting with `arraysubs_`.
2. Hover over its row and click **Run** in the row actions.
3. Wait 30 seconds, then refresh.

**Expected Result:**
- The action moves to the **Complete** tab with status `complete`.
- The log column for that action shows a "started" and "finished" entry.
- No "Action failed" log line appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.7 — Confirm cron is not double-firing
**Steps:**
1. Re-open `crontab -l` for the cron user.
2. Count the number of lines that hit this site's `wp-cron.php`.
3. Check any external cron service (cron-job.org, EasyCron, Cronitor) used during setup.

**Expected Result:**
- Exactly one cron line targets this site's `wp-cron.php`.
- No external service is also pinging the same URL on top of the system cron.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-check `wp-content/debug.log` after running Sub-Task 3.6 to confirm no new errors were logged during the manual action run.
- Visit a public-facing page in incognito and confirm page-load time is not affected (WP-Cron disabled should make pages slightly faster, never slower).
- Note in Sign-off whether the cron fires every 1 minute or every 5 minutes — later lifecycle stages will reference this interval.

## Sign-off
- Tester:
- Date:
- Browser & version:
- System cron interval:
- Action Scheduler failed count:
- Notes:
