# Stage 12 — Task 01: Store Credit Settings

| Key | Value |
|---|---|
| Stage | 12 — Store Credit (Pro) |
| Module | Store Credit — Settings |
| Plugin Coverage | Pro |
| Estimated Time | 15 min |
| Depends On | 00, 11 |

## Objective
Enable the Store Credit module and configure every settings group (General, Credit Application, Credit Expiration, Credit Purchase) with the values needed by the rest of Stage 12. Confirm that each setting persists across a full page reload and that the storefront / admin reacts to the master switch correctly.

## Pre-conditions
- Logged in as `admin@test.local`.
- ArraySubsPro is active.
- WooCommerce currency is set (note the symbol — used in later tasks).

## Test Data
- Settings to apply:
  - **Enable Store Credit System** = On
  - **Auto-Apply to Renewals** = On
  - **Allow at Checkout** = On
  - **Minimum Order Amount** = `5.00`
  - **Expiration Period (Days)** = `365`
  - **Enable Credit Purchases** = On
  - **Minimum Purchase Amount** = `10`
  - **Maximum Purchase Amount** = `500`
  - **Default Purchase Amount** = `50`

## Sub-Tasks

### Sub-Task 1.1 — Open the Store Credit settings page
**Steps:**
1. **WP-Admin → ArraySubs → Store Credit → Settings**.
2. Confirm the page loads with four sections: **General**, **Credit Application**, **Credit Expiration**, **Credit Purchase**.

**Expected Result:**
- The settings page loads with no PHP error or red banner.
- All four section headings are visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Configure General + Credit Application
**Steps:**
1. Toggle **Enable Store Credit System** = **On**.
2. Toggle **Auto-Apply to Renewals** = **On**.
3. Toggle **Allow at Checkout** = **On**.
4. Set **Minimum Order Amount** = `5.00`.
5. Click **Save Settings**.

**Expected Result:**
- A success notice appears (e.g., `Settings saved.`).
- Hard-refreshing the page (`Ctrl + Shift + R`) shows all four values still set.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Configure Credit Expiration
**Steps:**
1. Set **Expiration Period (Days)** = `365`.
2. Click **Save Settings**.
3. Hard-refresh the page.

**Expected Result:**
- The expiration period field shows `365` after reload.
- Reading the help text confirms the 7-day-before warning behaviour and the 3:00 AM daily job timing.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Configure Credit Purchase
**Steps:**
1. Toggle **Enable Credit Purchases** = **On**.
2. Set **Minimum Purchase Amount** = `10`.
3. Set **Maximum Purchase Amount** = `500`.
4. Set **Default Purchase Amount** = `50`.
5. Click **Save Settings**.
6. Hard-refresh the page.

**Expected Result:**
- All four fields persist.
- A note or hint about needing to create a Store Credit product is displayed (e.g., a link to the Purchase Product docs).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Verify the Store Credit product type appears
**Steps:**
1. **WP-Admin → Products → Add New**.
2. Open the **Product data** dropdown (the one with **Simple product**, **Variable product**, etc.).
3. Look for the **Store Credit** option in the list.
4. Cancel without saving.

**Expected Result:**
- The **Store Credit** option is now selectable in the Product data dropdown.
- It only appeared after enabling **Enable Credit Purchases** in 1.4.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Master switch off hides the module
**Steps:**
1. Return to **ArraySubs → Store Credit → Settings**.
2. Toggle **Enable Store Credit System** to **Off**, save.
3. Look at the **ArraySubs → Store Credit** sub-menu items.
4. Re-open **Products → Add New** → Product data dropdown.
5. Re-enable **Enable Store Credit System** and save before proceeding.

**Expected Result:**
- With the master switch Off, the **Manage Credits** and **Credit History** sub-menus disappear or become inactive.
- The **Store Credit** option in the Product data dropdown is hidden.
- Re-enabling restores both.
- No existing balance or log entry is lost — confirmed in 12.02 / 12.03.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Confirm WooCommerce email registrations
**Steps:**
1. **WC → Settings → Emails**.
2. Scroll the list looking for these four entries:
   - **Store Credit Added**
   - **Store Credit Used**
   - **Store Credit Expiring**
   - **Store Credit Expired**
3. Confirm each is **Enabled** (the default) and that **Manage** opens the editor.

**Expected Result:**
- All four emails appear in the list and are enabled.
- Clicking **Manage** loads the WooCommerce email editor (subject, heading, additional content, email type).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After all changes, the WooCommerce default currency symbol still renders correctly in the **Minimum Order Amount** field.
- DevTools console: zero JS errors on the Store Credit Settings page.
- `wp-content/debug.log`: no new warnings during the saves.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Currency symbol observed:
- Notes:
