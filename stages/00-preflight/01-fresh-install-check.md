# Stage 00 — Task 01: Fresh Install Check

| Key | Value |
|---|---|
| Stage | Pre-flight & Environment Verification |
| Module | Installation / Activation |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | none |

## Objective
Confirm the WordPress, WooCommerce, ArraySubs, and ArraySubsPro stack is installed and activated cleanly on a fresh site, that minimum versions are met, and that no PHP notices, dependency warnings, or admin notices appear on activation. This is the gate for every subsequent stage.

## Pre-conditions
- A fresh or freshly reset WordPress site is running and reachable over HTTPS.
- Permalinks are set to **Post name** (Settings → Permalinks).
- ArraySubs and ArraySubsPro plugin ZIPs are available locally.
- WP_DEBUG is enabled and `WP_DEBUG_LOG` writes to `wp-content/debug.log`.
- You have an Administrator login.

## Test Data
- Plugin file paths: `arraysubs/arraysubs.php`, `arraysubspro/arraysubspro.php`.
- Required minimums (from Before You Launch): PHP 8.1+, WordPress 6.0+, WooCommerce 8.0+.
- Path to debug log: `wp-content/debug.log`.

## Sub-Tasks

### Sub-Task 1.1 — Confirm environment minimums
**Steps:**
1. Log in as Administrator.
2. In wp-admin go to **Tools → Site Health → Info**.
3. Expand the **Server** section and read the PHP version.
4. Expand the **WordPress** section and read the WordPress version.
5. Expand the **WooCommerce** section and read the WooCommerce version.

**Expected Result:**
- PHP version is 8.1 or higher.
- WordPress version is 6.0 or higher.
- WooCommerce version is 8.0 or higher.
- No version row is highlighted as a problem.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Activate WooCommerce first
**Steps:**
1. Go to **Plugins → Installed Plugins**.
2. Confirm WooCommerce is in the list. If not, install it from **Plugins → Add New** and activate.
3. After activation, dismiss any WooCommerce setup-wizard banner if present.
4. Browse to **WooCommerce → Home** and confirm it loads.

**Expected Result:**
- WooCommerce is listed as **Active**.
- WooCommerce admin pages render with no red error banner.
- No "WooCommerce requires…" admin notices anywhere.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Install and activate ArraySubs (free core)
**Steps:**
1. Go to **Plugins → Add New → Upload Plugin**.
2. Upload the ArraySubs core ZIP and click **Install Now**.
3. Click **Activate Plugin**.
4. Watch for any admin notice rendered immediately after activation.

**Expected Result:**
- The plugin appears as **Active** in the list.
- No red admin notices appear about missing PHP / WP / WooCommerce versions.
- A new **ArraySubs** menu item appears in the wp-admin sidebar.
- No "Plugin could not be activated because it triggered a fatal error" message.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Install and activate ArraySubsPro
**Steps:**
1. Go to **Plugins → Add New → Upload Plugin**.
2. Upload the ArraySubsPro ZIP and click **Install Now**.
3. Click **Activate Plugin**.
4. Watch for any admin notice rendered immediately after activation.

**Expected Result:**
- ArraySubsPro is listed as **Active**.
- No "ArraySubs core plugin is required" dependency notice (because the core is already active).
- No PHP version, WP version, or WooCommerce version warnings.
- ArraySubs menu now shows additional Pro-only sub-items.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Confirm version numbers in the plugin list
**Steps:**
1. Go to **Plugins → Installed Plugins**.
2. Locate **ArraySubs** and read the version number printed under the plugin name.
3. Locate **ArraySubsPro** and read its version number.
4. Note both versions in the Sign-off block at the bottom of this file.

**Expected Result:**
- ArraySubs shows the expected core version (e.g., 1.8.0 or higher per the export-file metadata in the manuals).
- ArraySubsPro shows the expected Pro version.
- Both rows show **Active** status.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Inspect the debug log for activation notices
**Steps:**
1. Open `wp-content/debug.log` (over SSH, SFTP, or the hosting file manager).
2. Scroll to the bottom of the file.
3. Look for any `PHP Notice`, `PHP Warning`, `PHP Deprecated`, or `PHP Fatal error` entries with timestamps from the last 10 minutes.
4. If the log file does not exist, confirm `define('WP_DEBUG', true);` and `define('WP_DEBUG_LOG', true);` are set in `wp-config.php`, then repeat activation.

**Expected Result:**
- No fatal errors in the last 10 minutes.
- No notices or warnings whose stack trace points to `arraysubs/` or `arraysubspro/`.
- Pre-existing third-party notices are acceptable but must be noted.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Smoke check the dashboard
**Steps:**
1. Visit **Dashboard → Home**.
2. Visit **WooCommerce → Settings**.
3. Visit **ArraySubs → Settings**.
4. Open the browser DevTools Console and Network tabs and refresh each of the three pages.

**Expected Result:**
- No red banners on any page.
- DevTools Console shows zero JavaScript errors thrown by `arraysubs` or `arraysubspro` script bundles.
- DevTools Network panel shows no 404 or 500 responses for ArraySubs JS, CSS, or REST endpoints.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-run **Tools → Site Health → Status** and confirm no critical or recommended issues are introduced by the activation.
- Confirm `wp-content/debug.log` did not start filling with deprecation notices on the next 5 page loads.
- If you have access to a staging snapshot, take a database snapshot now labelled `post-activation-clean` so later stages can roll back to it.

## Sign-off
- Tester:
- Date:
- Browser & version:
- ArraySubs version:
- ArraySubsPro version:
- Notes:
