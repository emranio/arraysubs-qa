# Stage 09 — Task 08: Content Gating Messages & Default Redirect

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access → Content Restriction (Settings + Per-post overrides) |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 03-post-type-rules.md |

## Objective
Configure the global default restricted-content message and default redirect URL in **ArraySubs → Member Access → Settings**. Override the global message for one specific post via per-post meta. Verify both behaviors (global default + per-post override) appear in the correct places, and confirm merge tags `{site_name}`, `{pricing_link}`, `{login_link}` render correctly.

## Pre-conditions
- The Stage 09 Task 03 rule `KB Articles — Pro Plan only` (or an equivalent Post Type rule) is enabled and gating at least one test post (KB-A).
- A second test post **KB-D** exists, published, body text "KB-D BODY OK", part of the same `kb_article` post type so it is covered by the global Post Type rule.
- WordPress site name (Settings → General → Site Title) is recorded — needed to verify the `{site_name}` merge tag.
- `/pricing` page exists.

## Test Data
- Default Redirect URL: `/pricing`
- Default Message (global): `This content is restricted. Please subscribe to access.` (built-in default)
- New global message to set: `This content is available exclusively to {site_name} subscribers. {pricing_link} to view our plans, or {login_link} if you already have an account.`
- Per-post override message for KB-D: `KB-D requires the Enterprise tier. Contact sales for an upgrade.`

## Sub-Tasks

### Sub-Task 08.1 — Open Content Restriction settings
**Steps:**
1. Go to **ArraySubs → Member Access → Settings** (or the **Content Restriction** tab if separated).
2. Locate the four global settings:
   - Default Redirect URL
   - Default Message
   - Require Login
   - Cache Compatibility
3. Record the current values in Sign-off.

**Expected Result:**
- All four settings are visible with their current values. Default Redirect URL is `/pricing` and Default Message reads "This content is restricted. Please subscribe to access." (factory default).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.2 — Update the global default message
**Steps:**
1. Replace the Default Message value with:
   ```
   This content is available exclusively to {site_name} subscribers. {pricing_link} to view our plans, or {login_link} if you already have an account.
   ```
2. Confirm Default Redirect URL is `/pricing`.
3. Click **Save Changes**.

**Expected Result:**
- A "Settings saved." (or equivalent) admin notice appears.
- Reloading the settings page shows the new message persisted.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.3 — Verify global message + merge tag rendering
**Steps:**
1. Log in as `nonmember@example.com`.
2. Navigate to **KB-D** (or any post covered by the Post Type rule with no per-post override).
3. Inspect the rendered restriction message.

**Expected Result:**
- `{site_name}` is replaced with the actual site title (e.g., "Mirror Help Demo" or whatever the test site title is).
- `{pricing_link}` is replaced with a clickable link (anchor href = `/pricing`, default link text "View Plans & Pricing" or similar).
- `{login_link}` is replaced with a clickable link to `/wp-login.php` (or the configured login URL).
- The literal merge tags do NOT appear in the rendered output.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.4 — Verify the standard restriction page elements
**Steps:**
1. Stay on the restriction page from 08.3.
2. Visually confirm the page has:
   - A lock icon (theme/plugin-rendered).
   - The restriction message.
   - A **View Plans & Pricing** button linking to `/pricing`.
   - A **Log In** button (because the visitor is logged in already, the login button may be hidden — for full verification, log out and reload).
   - A **Return to Home** link.
3. Log out, navigate to KB-D again, and re-confirm the **Log In** button is present for logged-out visitors.

**Expected Result:**
- All four elements are visible (lock icon, message, View Plans & Pricing button, Return to Home link).
- The Log In button is visible only when logged out, hidden when logged in.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.5 — Per-post override for KB-D
**Steps:**
1. In wp-admin, edit **KB-D**.
2. Locate the per-post restriction meta (the **ArraySubs Restrictions** metabox or Custom Fields panel).
3. Set:
   - `_arraysubs_restrict_enabled` = `1`
   - `_arraysubs_restrict_conditions` = same as the global rule (active subscription to Pro Plan) — this keeps the post restricted.
   - `_arraysubs_restrict_message` = `KB-D requires the Enterprise tier. Contact sales for an upgrade.`
4. Click **Update**.

**Expected Result:**
- KB-D now has a per-post message saved.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.6 — Verify per-post message overrides global
**Steps:**
1. Log in as `nonmember@example.com`.
2. Navigate to **KB-D**.

**Expected Result:**
- The displayed message is exactly: `KB-D requires the Enterprise tier. Contact sales for an upgrade.`
- The global default message is NOT shown.
- Merge tags are NOT rendered (since the per-post message has no merge tags — the literal text is shown).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.7 — Verify another post still uses global message
**Steps:**
1. Navigate to **KB-A** (no per-post override).

**Expected Result:**
- The global default message (with `{site_name}`, `{pricing_link}`, `{login_link}` rendered) is shown.
- This confirms the per-post override on KB-D did NOT bleed into KB-A.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.8 — Cleanup
**Steps:**
1. Restore the global Default Message to factory default: `This content is restricted. Please subscribe to access.` (or to whatever value was recorded in 08.1).
2. Save.
3. KB-D per-post override may remain — note final state in Sign-off.

**Expected Result:**
- Global message reverted. KB-D per-post override status documented.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm `{site_name}` matches Settings → General → Site Title exactly (case-sensitive).
- Confirm misspelled merge tags (e.g., `{sitename}`, `{ pricing_link }`) appear literally and are NOT replaced.
- Confirm the URL Rule restriction page from Task 02 also uses the same default redirect URL `/pricing`.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Site Title used in `{site_name}` test:
- Original Default Message (recorded in 08.1):
- KB-D per-post override final state (still set / cleared):
- Notes:
