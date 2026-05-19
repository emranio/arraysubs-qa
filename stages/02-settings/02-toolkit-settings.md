# Stage 02 — Task 02: Toolkit Settings

| Key | Value |
|---|---|
| Stage | General & Toolkit Settings |
| Module | Toolkit Settings |
| Plugin Coverage | Both (Multi-Login Prevention requires Pro) |
| Estimated Time | 35 min |
| Depends On | 02-settings/01-general-settings-each-section.md |

## Objective
Exercise every section of **ArraySubs → Settings → Toolkit**: Admin Bar visibility, wp-admin restriction (each redirect option, allowed roles), WordPress login page hiding, Login as User, and Multi-Login Prevention (Pro). Confirm each setting takes effect immediately when verified from a separate browser session.

## Pre-conditions
- Stage 02 Task 01 passed.
- Both ArraySubs and ArraySubsPro active.
- Test users exist: `admin`, `shopmgr`, `cust1`.
- A second browser (or incognito window) is available for verification.
- A My Account page exists at `/my-account/` with the WooCommerce shortcode/block.

## Test Data
- Page path: **ArraySubs → Settings → Toolkit**.
- Sections: **Admin Bar**, **Admin Dashboard Access**, **WordPress Login Page**, **Login as User**, **Multi-Login Prevention** (Pro).

## Sub-Tasks

### Sub-Task 2.1 — Hide Admin Bar for Non-Admin Users
**Steps:**
1. Open **ArraySubs → Settings → Toolkit**.
2. Toggle **Hide admin bar for non-admin users** to **On**.
3. Click **Save Settings**.
4. In a second incognito window, log in as `cust1` and visit any frontend page (e.g., `/`).
5. In the same Administrator browser, visit the frontend.

**Expected Result:**
- For `cust1` on the frontend: the WordPress admin bar is **not** rendered at the top of the page.
- For Administrator on the frontend: the admin bar is still rendered.
- After toggling Off and saving, `cust1` sees the admin bar again.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Restrict wp-admin Access — redirect to My Account
**Steps:**
1. Toggle **Restrict wp-admin access** to **On**.
2. Confirm **Redirect Unauthorized Users To** appears with default **WooCommerce My Account page**.
3. Confirm **Allowed Roles** multi-select appears with no role selected (Administrator is implicit).
4. Click **Save Settings**.
5. In an incognito window, log in as `cust1` and try to visit `/wp-admin/`.

**Expected Result:**
- `cust1` is redirected to the WooCommerce My Account page (`/my-account/` or whatever your store has set).
- The Administrator can still reach `/wp-admin/` normally.
- AJAX/REST/WP-Cron URLs are not blocked (visit `/wp-admin/admin-ajax.php?action=heartbeat` as `cust1` and confirm it returns the AJAX response, not a redirect).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Restrict wp-admin Access — redirect to 404
**Steps:**
1. Change **Redirect Unauthorized Users To** to **404 Not Found page**.
2. Click **Save Settings**.
3. As `cust1` in incognito, try to visit `/wp-admin/`.

**Expected Result:**
- Browser receives an HTTP 404 response (Network tab).
- The rendered page is the theme's 404 template.
- Administrator access to wp-admin remains unaffected.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Restrict wp-admin Access — Allowed Roles
**Steps:**
1. Set **Redirect Unauthorized Users To** back to **WooCommerce My Account page**.
2. In **Allowed Roles**, add **Shop manager**.
3. Click **Save Settings**.
4. In incognito, log in as `shopmgr` and visit `/wp-admin/`.
5. In a third window, log in as `cust1` and visit `/wp-admin/`.

**Expected Result:**
- `shopmgr` reaches the WordPress dashboard without being redirected.
- `cust1` is still redirected to My Account.
- Removing `shopmgr` from Allowed Roles and saving causes `shopmgr` to be redirected on the next attempt.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Hide WordPress Login Page — redirect to My Account
**Steps:**
1. Toggle **Hide WordPress login page** to **On**.
2. Leave **Redirect Login Page To** at the default **WooCommerce My Account page**.
3. Click **Save Settings**.
4. In incognito (logged out), visit `/wp-login.php`.

**Expected Result:**
- The browser is redirected to `/my-account/`.
- The WooCommerce login form is rendered there.
- Visiting `/wp-login.php?action=lostpassword` still works (password reset is exempt per the manual).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Hide WordPress Login Page — redirect to 404
**Steps:**
1. Change **Redirect Login Page To** to **404 Not Found page**.
2. Click **Save Settings**.
3. In incognito (logged out), visit `/wp-login.php`.

**Expected Result:**
- Response is HTTP 404 (verify in DevTools Network tab).
- The 404 page is rendered.
- After toggling **Hide WordPress login page** back to **Off** and saving, `/wp-login.php` resumes rendering the WP login form.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.7 — Login as User toggle and admin row action
**Steps:**
1. Confirm **Enable Login as User** is **On** (default).
2. Click **Save Settings**.
3. Go to **Users → All Users**, locate `cust1`, hover the row.
4. Look for a **Login as User** row action.
5. Click it. Note the impersonation banner, then click the link to return to your own admin account.
6. Now toggle **Enable Login as User** to **Off** and save.
7. Repeat the user-row hover.

**Expected Result:**
- With the toggle On: a **Login as User** action is visible on non-admin user rows; clicking it impersonates the customer and shows the impersonation banner per the manual.
- With the toggle Off: the **Login as User** action is no longer visible.
- Attempting to use the action on another administrator user (e.g., `admin` itself) is not offered (admin-to-admin impersonation is disallowed per the FAQ).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.8 — Multi-Login Prevention (Pro)
**Steps:**
1. Confirm the **Multi-Login Prevention** section is rendered (Pro is active).
2. Toggle **Enable Multi-Login Prevention** to **On**.
3. Set **Default Max Sessions Per User** to `1`.
4. Leave **Apply to Administrators** **Off**.
5. Click **Save Settings**.
6. In Browser A, log in as `cust1`. Confirm `cust1` is logged in.
7. In Browser B (different browser, not just another tab), log in as `cust1`.
8. Refresh Browser A.

**Expected Result:**
- Both logins succeed.
- After the second login, refreshing Browser A shows a logged-out state — the oldest session was destroyed.
- The newest session always succeeds.
- Now set Max Sessions to `2`, save, and confirm two browsers can stay logged in simultaneously without one being kicked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.9 — Multi-Login Prevention — Apply to Administrators
**Steps:**
1. Set **Default Max Sessions Per User** back to `1`.
2. Toggle **Apply to Administrators** to **On**.
3. Click **Save Settings**.
4. In a second browser, log in as `admin`. Then refresh the first browser where `admin` was already logged in.

**Expected Result:**
- The second admin login succeeds; the first admin session is destroyed (just like the cust1 case).
- Toggle **Apply to Administrators** Off again and save before continuing — admin sessions are otherwise vulnerable for the rest of the test plan.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.10 — Pro section disappears when Pro is deactivated
**Steps:**
1. Deactivate ArraySubsPro on **Plugins → Installed Plugins**.
2. Return to **ArraySubs → Settings → Toolkit**.
3. Inspect the page.
4. Re-activate Pro.

**Expected Result:**
- The **Multi-Login Prevention** section is not rendered while Pro is inactive.
- All other Toolkit sections are still rendered.
- After re-activating Pro the section returns with prior values intact.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After this task, leave Toolkit in a known baseline: hide admin bar **On**, restrict wp-admin **On** with redirect to My Account and Allowed Roles = Shop manager, hide WP login page **Off**, Login as User **On**, Multi-Login Prevention **Off**. Note any deviation in Sign-off.
- Tail `wp-content/debug.log` while toggling — no PHP notice should reference `arraysubs/toolkit`.
- Confirm DevTools Network shows HTTP 200 on every Save call.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Final state of Toolkit toggles:
- Notes:
