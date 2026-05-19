# Stage 09 — Task 11: Multi-Login Prevention *(Pro)* — Max 2 Sessions

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access → Login Limit *(Pro)* |
| Plugin Coverage | Pro |
| Estimated Time | 35 min |
| Depends On | 01-role-mapping.md (active Pro Plan subscriber) |

## Objective
Configure a Login Limit rule that caps the number of concurrent browser sessions to **2** for active **Pro Plan** subscribers. Log in as the same customer from 3 different browsers (or browser profiles) and verify the oldest session is automatically logged out via the WordPress heartbeat. Verify the cooldown period and the global default fallback.

## Pre-conditions
- ArraySubs **Pro** plugin is active.
- **ArraySubs → Settings → Toolkit → Multi-Login Prevention** is ENABLED. The Login Limit tab is now visible at **ArraySubs → Member Access → Login Limit**.
- Three independent browser sessions available:
  - Browser 1: Chrome regular profile.
  - Browser 2: Chrome incognito (or Chrome profile B).
  - Browser 3: Firefox / Edge / Safari (anything that does not share cookies with Browser 1 or 2).
- `member1@example.com` is the test customer (Active Pro Plan).
- WordPress heartbeat is enabled (default; verify at **Settings → General → Heartbeat** if a Heartbeat Control plugin is installed). Heartbeat tick interval is 15–60 seconds default — wait that long before declaring eviction failed.

## Test Data
- Login Limit rule name: `Pro Plan — 2 sessions max`
- Max Allowed Sessions: `2`
- IF condition: Has Active Subscription to **Pro Plan**
- Global default (Toolkit Settings): record current value before starting (e.g., `1`)

## Sub-Tasks

### Sub-Task 11.1 — Verify the Toolkit prerequisite and Login Limit tab
**Steps:**
1. Go to **ArraySubs → Settings → Toolkit**.
2. Confirm **Multi-Login Prevention** toggle is ON.
3. Record the current global default `multi_login_max_sessions` value in Sign-off.
4. Confirm **Include administrators** checkbox state and record it.
5. Go to **ArraySubs → Member Access**. Confirm a tab labelled **Login Limit** is visible.

**Expected Result:**
- The Login Limit tab is present.
- Toolkit Multi-Login Prevention is ON.
- Global default value is recorded.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.2 — Create the Login Limit rule
**Steps:**
1. Go to **ArraySubs → Member Access → Login Limit**.
2. Click **Add Rule**.
3. Name: `Pro Plan — 2 sessions max`.
4. **IF conditions:** Has Active Subscription to **Pro Plan**.
5. **THEN:** Max Allowed Sessions = `2`.
6. Save.

**Expected Result:**
- Rule listed with Max Sessions = 2 and toggle enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.3 — Login from Browser 1 and Browser 2
**Steps:**
1. In Browser 1, navigate to `/my-account/` and log in as `member1@example.com`. Note the timestamp.
2. In Browser 2 (incognito or different profile), log in as `member1@example.com`. Note the timestamp.
3. In each browser, navigate to **My Account → Subscriptions** and confirm the page loads normally — both sessions are active.

**Expected Result:**
- Both browsers display the My Account dashboard with Subscriptions visible.
- Neither browser is signed out yet (under the 2-session limit).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.4 — Login from Browser 3 (the 3rd session)
**Steps:**
1. In Browser 3, navigate to `/my-account/` and log in as `member1@example.com`. Note the timestamp.
2. Browser 3 is now the newest session. Browser 1 should be the oldest.
3. Stay on Browser 3's My Account dashboard.

**Expected Result:**
- Browser 3 logs in successfully (the newest login always succeeds).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.5 — Verify Browser 1 (oldest) is evicted via heartbeat
**Steps:**
1. Switch to Browser 1.
2. Wait up to 120 seconds (heartbeat tick window) without navigating.
3. Optionally, click any link or perform any action that triggers a heartbeat (e.g., open the WordPress admin bar's user menu).
4. Observe the response.

**Expected Result:**
- Within 15–120 seconds, Browser 1 is redirected to the WordPress login page (`/wp-login.php?...&loggedout=true` or similar).
- Browser 1's session is no longer authenticated.
- Browser 2 (still within the 2-session window) remains logged in.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.6 — Verify Browser 2 still active and Browser 3 active
**Steps:**
1. Switch to Browser 2. Reload **My Account → Subscriptions**.
2. Switch to Browser 3. Reload **My Account → Subscriptions**.

**Expected Result:**
- Both Browser 2 and Browser 3 load My Account normally — they are the 2 surviving sessions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.7 — Cooldown period verification
**Steps:**
1. The "cooldown period" mentioned in the manual prevents thrashing — after eviction, a brief block prevents the same evicted browser from immediately logging in and evicting another newer session in a loop.
2. From Browser 1 (currently logged out), immediately attempt to log in as `member1@example.com` again.
3. Observe whether login succeeds or whether a cooldown notice/restriction is shown.
4. Record the elapsed time between eviction and successful re-login attempt.

**Expected Result:**
- Either:
  - Login succeeds and Browser 1 becomes the newest session, evicting Browser 2 (the new oldest). This is acceptable if cooldown is set to 0/disabled by default.
  - Login is throttled with a cooldown message until the cooldown window expires.
- Whichever the configured behavior is, document it verbatim in Sign-off.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.8 — Admin exemption check
**Steps:**
1. Log in as `qa_admin` (administrator) in Browser 1.
2. Open Browser 2 and log in as `qa_admin`.
3. Open Browser 3 and log in as `qa_admin`.
4. Wait 2 minutes. Refresh Browser 1.

**Expected Result:**
- Browser 1 is still logged in as `qa_admin` — admins are exempt from session limits by default unless **Include administrators** is enabled.
- This validates the admin exemption documented in the manual.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.9 — Cleanup
**Steps:**
1. Disable the Login Limit rule (toggle off).
2. Log out from all three browsers.
3. Confirm the global default is unchanged from the value recorded in 11.1.

**Expected Result:**
- Login Limit rule disabled. Global default preserved.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm an impersonation session created via **Login as User** is NOT counted toward the limit (per the manual: "Sessions created via the Login as User feature are not counted... and are never evicted.").
- Confirm if Pro is deactivated, session limit enforcement stops but the rule definition is preserved (do not actually deactivate Pro in this run — just note expectation).
- Confirm the WordPress heartbeat default tick is in the 15–60 second range. If heartbeat is disabled by another plugin, document the mitigation.

## Sign-off
- Tester:
- Date:
- Browsers used (1 / 2 / 3):
- Time from 3rd login to Browser 1 redirect (in seconds):
- Cooldown behavior observed (none / time-throttle / message):
- Toolkit global default value (recorded in 11.1):
- Include administrators state:
- Notes:
