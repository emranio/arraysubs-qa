# Stage 00 — Task 02: Admin Menu and Capabilities

| Key | Value |
|---|---|
| Stage | Pre-flight & Environment Verification |
| Module | Admin Menu / Capability Mapping |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | 00-preflight/01-fresh-install-check.md |

## Objective
Verify the **ArraySubs** admin menu is rendered with the correct child items for an Administrator and a Shop Manager, that Pro-only sub-menus appear only when ArraySubsPro is active, and that the menu is completely hidden from Customer-role accounts. This guarantees the rest of the test plan can rely on a stable admin navigation surface.

## Pre-conditions
- Stage 00 Task 01 passed (both plugins active, no debug log errors).
- Administrator login is available.
- A Shop Manager and a Customer test account exist (or will be created here for the first time — see Sub-Task 2.1).

## Test Data
- Admin login: `admin` / known password.
- Shop Manager login: `shopmgr` / known password.
- Customer login: `cust1` / known password.
- Expected menu path: **ArraySubs** in the wp-admin sidebar with sub-items including (at minimum) **Settings**, **Easy Setup**, **Subscriptions**, **Profile Builder**, and Pro-only entries such as **Analytics**, **Manage Members**, and **Audit Logs**.

## Sub-Tasks

### Sub-Task 2.1 — Create / verify the role accounts
**Steps:**
1. Log in as Administrator.
2. Go to **Users → Add New** and create user `shopmgr` with role **Shop manager** if it does not already exist.
3. Go to **Users → Add New** and create user `cust1` with role **Customer** if it does not already exist.
4. Save passwords in your password manager.

**Expected Result:**
- Both users appear in the **Users → All Users** list with the correct role assignment.
- No "Couldn't create user" error appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Confirm the ArraySubs menu as Administrator
**Steps:**
1. Stay logged in as Administrator.
2. Look at the wp-admin left sidebar.
3. Click **ArraySubs** to expand the menu.
4. Note every sub-menu item displayed.

**Expected Result:**
- **ArraySubs** menu is visible.
- Sub-menu includes **Settings**, **Easy Setup**, **Subscriptions**, and **Profile Builder** at minimum.
- Pro-only sub-menus appear (e.g., **Manage Members**, **Analytics**, **Audit Logs**, **Store Credit**, **Feature Manager**) because ArraySubsPro is active.
- Clicking each sub-item loads its page with no 403 or 404.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Confirm the ArraySubs menu as Shop Manager
**Steps:**
1. Open an incognito / private window.
2. Log in as `shopmgr`.
3. Look at the wp-admin sidebar.
4. Click **ArraySubs** and try opening each sub-page.

**Expected Result:**
- **ArraySubs** menu is visible to the Shop Manager.
- The Shop Manager can open **Settings**, **Easy Setup**, **Subscriptions**, and **Profile Builder** without a "Sorry, you are not allowed to access this page" message.
- Pages render the same UI a tester would see as Administrator (any admin-only buttons that should be hidden are documented in the manuals — note any exceptions in Fail Notes for the team to triage).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Confirm the ArraySubs menu is hidden from Customers
**Steps:**
1. Open a second incognito window.
2. Log in as `cust1`.
3. WordPress will redirect a Customer-role user away from `/wp-admin` if Toolkit restriction is on. For this check, manually visit `/wp-admin/profile.php` (the profile page is allowed for all logged-in users).
4. Inspect the wp-admin sidebar.

**Expected Result:**
- The wp-admin sidebar does not contain an **ArraySubs** menu item.
- Visiting `/wp-admin/admin.php?page=arraysubs-settings` directly returns a "Sorry, you are not allowed to access this page" message or a redirect — never the settings UI.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Deactivate Pro and confirm Pro-only sub-menus disappear
**Steps:**
1. Return to the Administrator window.
2. Go to **Plugins → Installed Plugins**.
3. Click **Deactivate** under **ArraySubsPro**.
4. Reload the dashboard.
5. Click **ArraySubs** in the sidebar and inspect the sub-menu.

**Expected Result:**
- ArraySubs core menu still appears with **Settings**, **Easy Setup**, **Subscriptions**, **Profile Builder**.
- Pro-only sub-items (**Manage Members**, **Analytics**, **Audit Logs**, **Store Credit**, **Feature Manager**) no longer appear.
- No PHP fatal error or admin notice referencing `arraysubspro` is shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Re-activate Pro and confirm sub-menus return
**Steps:**
1. From **Plugins → Installed Plugins** click **Activate** on **ArraySubsPro**.
2. Reload the dashboard.
3. Click **ArraySubs** in the sidebar.

**Expected Result:**
- All Pro-only sub-menus are restored.
- No "Pro requires core plugin" dependency notice appears.
- Each previously-broken Pro page now loads without 403 or 404.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Open DevTools Network tab while reloading the ArraySubs settings page as Shop Manager. Confirm no admin-ajax or REST request returns 403.
- Check `wp-content/debug.log` for new errors after the deactivate / re-activate cycle.
- Confirm the WooCommerce menu is unaffected by Pro deactivation.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Admin sub-menu items observed:
- Shop Manager sub-menu items observed:
- Notes:
