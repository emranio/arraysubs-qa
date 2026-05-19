# Stage 20 — Task 05: Performance With Many Subscriptions

| Key | Value |
|---|---|
| Stage | 20 — Edge Cases & Final Regression |
| Module | Performance |
| Plugin Coverage | Both |
| Estimated Time | 25 min |
| Depends On | Stage 14 (Admin Subscriptions) |

## Objective
Generate or batch-create 200+ subscriptions across multiple customers and statuses. Open the **Subscriptions** admin list, search, filter by status, and paginate. Confirm: page loads under 3 seconds, search returns within 2 seconds, filter changes return within 2 seconds, pagination is correct, no memory exhaustion, no PHP notices.

## Pre-conditions
- A clean ArraySubs install with at least the prior stages' subscriptions present.
- Access to a way to bulk-generate subscriptions: ArraySubsPro's batch admin tool if available; otherwise WP-CLI command or a one-off SQL/PHP seed script. Record which method you used.
- WP `WP_DEBUG=true` and `WP_DEBUG_LOG=true` in `wp-config.php` to catch PHP notices.
- A timer (browser dev tools Network tab, or stopwatch) to measure load times.
- Server PHP `memory_limit` is the production-realistic value (e.g. 256M) — do not artificially raise it just for the test.

## Test Data
- Target subscription count: **at least 200** (we'll aim for 250 to give headroom).
- Distribution: roughly 60% Active, 15% Trial, 10% On-Hold, 10% Cancelled, 5% Expired.
- Spread across at least 30 distinct customer accounts.

## Sub-Tasks

### Sub-Task 05.1 — Bulk-create subscriptions
**Steps:**
1. Use the chosen seeding method to create 250 subscriptions across the status distribution above.
2. Confirm the count via `wp post list --post_type=arraysubs_sub --format=count` (or via the admin Subscriptions header which shows total count).

**Expected Result:**
- Total subscription count is at least 200 (record actual: `<record>`).
- Status counts are roughly distributed as planned.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.2 — Subscriptions list page load time
**Steps:**
1. Clear the browser cache.
2. Open dev tools → Network tab. Disable cache for the session.
3. Navigate to **ArraySubs → Subscriptions**.
4. Record the time-to-first-byte and the time-to-DOMContentLoaded for the page request.

**Expected Result:**
- Page renders fully in **under 3 seconds** on typical staging hardware.
- The default first page shows 20 (or whatever the per-page setting is) subscriptions.
- Pagination controls show the correct total page count (e.g. `1 of 13`).
- No PHP notices appear in the page output or `debug.log`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.3 — Search performance
**Steps:**
1. In the Subscriptions list search box, type a known customer email (e.g. `cust2@example.com`) and submit.
2. Time how long until results render.

**Expected Result:**
- Results appear in **under 2 seconds**.
- Only subscriptions matching that customer email are shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.4 — Status filter performance
**Steps:**
1. Click the **Trial** status filter.
2. Time how long until the filtered list renders.
3. Repeat for **On-Hold**, **Cancelled**, **Expired**, and **Active**.

**Expected Result:**
- Each filter change returns in **under 2 seconds**.
- Counts shown next to each status filter match the actual filtered counts.
- Switching between filters does not double-trigger queries (verify in the Network tab — one XHR or one full page load per click).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.5 — Pagination correctness
**Steps:**
1. With **All** filter selected, click through pages 1 → 2 → 3 → last → page 5 → previous.
2. On each page, confirm 20 (or per-page setting) entries are shown without duplicates and the page indicator is correct.
3. Click the last-page button — confirm it loads correctly.

**Expected Result:**
- Pagination is consistent: no duplicated rows across pages, no skipped subscriptions.
- The last page shows the correct remainder count (e.g. 250 total, 20 per page → page 13 shows 10).
- Each page transition is under 2 seconds.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.6 — Memory and notice check
**Steps:**
1. Tail `wp-content/debug.log` while performing 05.2–05.5.
2. Check `top` / `htop` (or your hosting panel's process view) for PHP-FPM peak memory during these requests.

**Expected Result:**
- No PHP `Fatal error: Allowed memory size exhausted` lines appear.
- No PHP Notice/Warning lines from ArraySubs are added during the test.
- Peak PHP-FPM worker memory stays well under the configured `memory_limit`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.7 — Combined filter + search + pagination
**Steps:**
1. Click filter **Active**, then type a partial email like `cust` in search, then click page 2 of the filtered+searched results.

**Expected Result:**
- Combined filter + search + pagination returns the expected subset within 2 seconds.
- Pagination URL retains both the status filter and the search term across page navigations.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Reload **Analytics** (Pro) page — it should also load reasonably (<3s) under this volume.
- Confirm Action Scheduler queue listing under this volume of subscriptions still loads (the Pro Scheduled-Job Logs page may surface this).
- After the test, optionally clean up the seeded subscriptions if they are noisy. Record cleanup approach.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
