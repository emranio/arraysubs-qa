# Stage 02 — Task 03: Import / Export Settings

| Key | Value |
|---|---|
| Stage | General & Toolkit Settings |
| Module | Import / Export |
| Plugin Coverage | Both |
| Estimated Time | 30 min |
| Depends On | 02-settings/01-general-settings-each-section.md, 02-settings/02-toolkit-settings.md |

## Objective
Export the current ArraySubs configuration to JSON, modify a handful of settings, re-import the original file with selective sections, and confirm the modified settings revert. Also verify export/import behavior with ArraySubsPro both active and inactive, and confirm payment gateway credentials are stripped from the export.

## Pre-conditions
- Stage 02 Tasks 01 and 02 passed.
- Both ArraySubs and ArraySubsPro active.
- Logged in as Administrator.
- Settings are in the documented baseline state from Tasks 01 and 02.

## Test Data
- Page path: **ArraySubs → Easy Setup**.
- Cards: **Export Settings** and **Import Settings**.
- Expected file naming: `arraysubs-settings-YYYY-MM-DD.json`.
- Eight import sections (per `import-export-settings.md`):
  - Subscription & Others
  - Retention Flow Builder
  - Store Credit
  - My Account Builder
  - Checkout Builder
  - Member Access
  - Emails
  - Profile Fields

## Sub-Tasks

### Sub-Task 3.1 — Export with Pro active and inspect the file
**Steps:**
1. Go to **ArraySubs → Easy Setup**.
2. On the **Export Settings** card, click **Export Settings**.
3. Save the downloaded file as `baseline-pro-active.json`.
4. Open the file in a text editor.

**Expected Result:**
- File downloads automatically.
- A success toast appears: "Settings exported successfully!"
- The file has top-level keys `meta` and `options`.
- `meta` includes `plugin_version`, `pro_version`, `module_version`, `export_date`, `site_url`, `php_version`, `wp_version`, `wc_version`.
- `options` includes at least `arraysubs_settings`, `arraysubs_profile_fields_config`, `arraysubs_avatar_settings`, `arraysubs_myaccount_menu_config`, and `wc_email_settings`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Confirm payment gateway keys are stripped
**Steps:**
1. In the JSON, search for `stripe_secret_key`, `stripe_publishable_key`, and any other gateway secret keys.
2. If any of those keys are present, confirm their values are empty strings or null (not real credentials).

**Expected Result:**
- No real Stripe API credentials appear in the file.
- Per the manual, those keys are deliberately stripped on export.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Modify a few settings before re-import
**Steps:**
1. Go to **ArraySubs → Settings → General**.
2. Set **Days Active After Due** to `10` (was 3 or whatever your baseline is).
3. Set **Allow Reactivation** to **Off** (was On).
4. Set **Renewal Reminder (Days Before)** to `15`.
5. Click **Save Settings**.
6. Go to **ArraySubs → Settings → Toolkit** and toggle **Hide admin bar for non-admin users** to the opposite value, save.

**Expected Result:**
- All four changes save successfully and persist after reload.
- The current state on the page is now visibly different from the exported baseline.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Re-import the baseline file selecting only Subscription & Others
**Steps:**
1. Go to **ArraySubs → Easy Setup**.
2. On the **Import Settings** card, click **Choose JSON File** and pick `baseline-pro-active.json`.
3. Click **Continue**.
4. Read the validation summary that appears (source site URL, export date, plugin version, available sections).
5. Untick **Select All**, then tick only **Subscription & Others**.
6. Click **Import Selected**.
7. In the confirmation modal, read the message and click **Confirm Import**.
8. View the result screen.

**Expected Result:**
- The validation summary correctly shows the source site URL and export date from `baseline-pro-active.json`.
- The list of available sections includes **Subscription & Others** plus any other sections that had data in the file.
- The confirmation modal reads: "The selected settings will be wiped out and replaced with the imported values. Unchecked sections will remain untouched. This action cannot be undone."
- The result screen shows **Imported** = Subscription & Others, **Skipped** = the rest, **Warnings** = none (since Pro is active).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Verify subscription settings reverted, but other sections intact
**Steps:**
1. Go to **ArraySubs → Settings → General**.
2. Read the values for **Days Active After Due**, **Allow Reactivation**, **Renewal Reminder (Days Before)**.
3. Go to **ArraySubs → Settings → Toolkit** and read **Hide admin bar for non-admin users**.

**Expected Result:**
- General Settings values match the baseline (3, On, 3 — or whatever values the baseline file held).
- Toolkit value is **also** the baseline because Toolkit settings live inside `arraysubs_settings`. (If the baseline had Toolkit changes, those revert too. Note in Fail Notes if behavior diverges.)

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Validate import error messages with bad input
**Steps:**
1. Click **Import Settings** again.
2. With nothing selected and the textarea empty, click **Continue**.
3. Note the error.
4. Paste the literal text `not json` into the textarea and click **Continue**.
5. Note the error.
6. Paste valid JSON that does not match the export schema, e.g., `{"foo":"bar"}`, and click **Continue**.

**Expected Result:**
- Empty input shows: "Please paste your exported JSON data or select a file".
- Garbage text shows: "Invalid JSON format — please check the pasted data".
- Wrong-shape JSON shows: "Invalid export format — missing required structure (meta and options)".
- All three errors are inline on the import card; nothing is saved.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.7 — Export with Pro inactive and compare to Pro-active export
**Steps:**
1. Deactivate ArraySubsPro from **Plugins → Installed Plugins**.
2. Go to **ArraySubs → Easy Setup → Export Settings** and click **Export Settings**.
3. Save the file as `baseline-pro-inactive.json`.
4. Diff `baseline-pro-active.json` against `baseline-pro-inactive.json` (use any text-based diff tool).
5. Re-activate Pro.

**Expected Result:**
- Both files export successfully with no error.
- The Pro-inactive export omits `pro_version` from `meta` (or shows it as empty/null).
- Pro-only settings keys (Store Credit, Feature Manager, Multi-Login config) may still be present in `arraysubs_settings` because they remain stored even when Pro is inactive — the manual states "Pro-specific settings remain stored, but they become dormant."
- Re-activating Pro does not erase any of those previously-stored values.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.8 — Import a Pro file on a Pro-inactive site (warning shown)
**Steps:**
1. Deactivate ArraySubsPro again.
2. Go to **ArraySubs → Easy Setup → Import Settings**.
3. Upload `baseline-pro-active.json`, click **Continue**.
4. Tick **Store Credit** (if present) plus **Subscription & Others**.
5. Click **Import Selected** and **Confirm Import**.
6. Read the result screen.
7. Re-activate Pro.

**Expected Result:**
- The import succeeds.
- The result screen shows a **Warnings** entry noting that Pro-specific settings were imported while Pro is not active (e.g., "Pro settings imported while Pro is not active — they will activate when Pro is enabled").
- After re-activating Pro, the imported Pro values are immediately effective on **ArraySubs → Settings**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Tail `wp-content/debug.log` during each import — no PHP notice should reference the importer code.
- DevTools Network tab should show HTTP 200 on every export download and every import POST.
- Save the **post-task** state as `post-stage-02.json` for the rest of the test plan.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Files saved (filename + size):
- Notes:
