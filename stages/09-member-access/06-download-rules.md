# Stage 09 — Task 06: Download Rules — Rate-Limited Downloads

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access → Downloads |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | 01-role-mapping.md |

## Objective
Configure a Download Rule that exposes a downloadable file to active **Pro Plan** subscribers, with a per-day download limit of 5. Verify the file appears on **My Account → Downloads**, the download counter increments on every download, and a "limit reached" message is rendered after the cap is hit. Confirm the counter resets when the day boundary is crossed (where feasible).

## Pre-conditions
- A test file uploaded to the Media Library: `pro-templates.zip` (small ~50 KB zip). Note the Media Library attachment ID.
- Pro Plan and `member1@example.com` (Active Pro Plan) ready.
- WooCommerce **My Account → Downloads** endpoint enabled (default WooCommerce setup).

## Test Data
- Rule name: `Pro Templates Library — 5/day`
- File display name: `Pro Templates`
- Attached file: `pro-templates.zip`
- Limit period: `day`
- Limit count: `5`
- IF condition: Has Active Subscription to **Pro Plan**

## Sub-Tasks

### Sub-Task 06.1 — Create the Download rule with rate limit
**Steps:**
1. Go to **ArraySubs → Member Access → Downloads**.
2. Click **Add Rule**.
3. Name the rule `Pro Templates Library — 5/day`.
4. **TARGET:**
   - Click **Add File**.
   - Display name: `Pro Templates`.
   - Click the upload icon and select `pro-templates.zip` from the Media Library.
5. **IF conditions:** Has Active Subscription to **Pro Plan**.
6. Locate the **Download Limits / Usage Tracking** section and configure:
   - Enable download limit: ON.
   - Limit count: `5`.
   - Limit period: `day`.
   - Reset on renewal: leave default (off).
7. Save.

**Expected Result:**
- Rule listed with the file `Pro Templates` and a download limit of 5 per day. Toggle is enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.2 — Member sees the file with usage counter
**Steps:**
1. Log in as `member1@example.com`.
2. Navigate to **My Account → Downloads**.
3. Locate the `Pro Templates` row.
4. Inspect the usage indicator next to it.

**Expected Result:**
- A row labelled `Pro Templates` is visible.
- A usage indicator shows `0 / 5 downloads used today` (or equivalent — "0/5", "Used 0 of 5", with a progress bar at 0%).
- The download link/button is active.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.3 — Counter increments per download
**Steps:**
1. Click the download link. The browser should download `pro-templates.zip`.
2. Reload **My Account → Downloads**.
3. Verify the usage counter.
4. Repeat for downloads 2, 3, 4 — checking the counter after each.

**Expected Result:**
- After download #1: counter reads `1 / 5` (or "1 of 5"), progress bar ~20%.
- After download #2: `2 / 5`, ~40%.
- After download #3: `3 / 5`, ~60%.
- After download #4: `4 / 5`, ~80%.
- The file actually downloads each time (no broken link).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.4 — 90% warning indicator
**Steps:**
1. After download #4 (80%), confirm whether a 90%-warning indicator appears yet.
2. Click download #5.
3. Reload Downloads page.

**Expected Result:**
- After download #5: counter reads `5 / 5` and the "limit reached" warning indicator is visible.
- A 90% warning may have already appeared at the `5/5` mark or before — record which threshold the indicator first appeared at.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.5 — Limit-reached message blocks further downloads
**Steps:**
1. With the counter at `5 / 5`, click the download link a 6th time.
2. Inspect the response (page or modal).

**Expected Result:**
- The 6th attempt is rejected. The customer sees a message indicating the daily download limit has been reached (e.g., "Download limit reached. Try again tomorrow.").
- The actual file is NOT served.
- The counter does not increment past `5 / 5`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.6 — Counter reset on day boundary
**Steps:**
1. Two options for verifying reset:
   - **Option A (preferred)**: Use server time-travel by SSH'ing in and running `sudo date -s '+1 day'` (only on isolated test box). Reload Downloads page.
   - **Option B (no time travel)**: Manually clear the usage counter from the database via WP-CLI or by deleting the relevant `arraysubs_download_usage_*` user meta entry, then reload.
2. After applying chosen reset, reload the Downloads page.
3. Document which option was used in Sign-off.

**Expected Result:**
- Counter is back to `0 / 5`.
- The download link is active again.
- Clicking the link successfully downloads the file and increments to `1 / 5`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.7 — Non-member does not see the file
**Steps:**
1. Log out, log in as `nonmember@example.com`.
2. Navigate to **My Account → Downloads**.

**Expected Result:**
- The `Pro Templates` row is NOT shown.
- (If WooCommerce native downloads exist for this user, they still display normally — the Member Access download rule does not interfere with native downloads.)

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.8 — Cleanup
**Steps:**
1. Restore server time if Option A was used.
2. Disable the download rule (toggle off) so it does not interfere with later stages.
3. Confirm in `member1@example.com` Downloads page that `Pro Templates` no longer appears.

**Expected Result:**
- Rule disabled. Subscriber no longer sees `Pro Templates` row.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm direct file URL access is rejected — copy the download link, log out, paste in incognito. Should return an error (signed URL nonce verification fails).
- Confirm WooCommerce native product downloads are unaffected for any test customer who has them.
- Confirm activity log records each download with timestamp and user ID at **ArraySubs → Audits → Activity** (filter by "download").

## Sign-off
- Tester:
- Date:
- Browser & version:
- Reset method used (Option A — time travel / Option B — direct meta wipe):
- Threshold at which 90% warning first appeared:
- Notes:
