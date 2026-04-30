# Stage 10 — Task 03: My Account Editor — Reorder, Rename, Hide, Custom Endpoint

| Key | Value |
|---|---|
| Stage | 10 — Profile Builder & My Account Customization |
| Module | Profile Builder → My Account Editor |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | none |

## Objective
Use the My Account Editor to reorder default WooCommerce/ArraySubs menu items, rename one, hide one, and add a custom endpoint linked to the **Help Center** page with **Prevent direct access** checked. Verify all four changes are reflected on the customer-facing My Account sidebar.

## Pre-conditions
- `qa_admin` logged in.
- `pf_test@example.com` available for the customer-facing verification.
- **Help Center** page exists at slug `/help-center` with body content built using Gutenberg blocks (a heading "Help Center" and a paragraph with at least 100 characters of body text).
- ArraySubs subscriptions feature active so the **Subscriptions** menu item is present.
- ArraySubs Pro NOT required for this task — Pro-only items (My Features, Store Credit) are noted in cross-checks if available.

## Test Data
- Custom endpoint label: `Help Center`
- Custom endpoint slug: `help-center`
- Custom endpoint linked content: WordPress page **Help Center**
- Prevent direct access: ON
- Reorder target: drag **Subscriptions** to the top.
- Rename target: rename **Orders** to `My Purchases`.
- Hide target: hide **Downloads**.

## Sub-Tasks

### Sub-Task 03.1 — Open My Account Editor and enable customization
**Steps:**
1. Go to **ArraySubs → Profile Builder → My Account**.
2. Toggle **Enable My Account menu items customization** to ON if not already.
3. Confirm a list of all current menu items is displayed below the toggle (Dashboard, Orders, Downloads, Addresses, Account Details, Subscriptions, Logout, plus any Pro items).

**Expected Result:**
- Toggle is ON.
- Menu items list is visible with grip handles, Move Up/Down arrows, expand arrows, enable toggles.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.2 — Reorder: move Subscriptions to top
**Steps:**
1. Locate the **Subscriptions** row.
2. Drag it by its grip handle to the top of the list (or use **Move Up** arrows repeatedly).
3. Confirm Subscriptions is now position #1.
4. Click **Save Configuration**.

**Expected Result:**
- Save success notice.
- After reload, Subscriptions remains at position #1.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.3 — Rename: Orders → My Purchases
**Steps:**
1. Click the expand arrow on the **Orders** row.
2. In the **Custom label** field, enter `My Purchases`.
3. Confirm the original label `Orders` is shown as a reference.
4. Click **Save Configuration**.

**Expected Result:**
- Save success.
- Field persists `My Purchases` after reload.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.4 — Hide: Downloads
**Steps:**
1. Locate the **Downloads** row.
2. Click the enable/disable toggle to disable it. The row should now appear dimmed.
3. Click **Save Configuration**.

**Expected Result:**
- Save success.
- Downloads row remains dimmed after reload.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.5 — Add custom endpoint Help Center
**Steps:**
1. Click **Add Custom Item** at the bottom of the list.
2. The new item appears at the end. Click the expand arrow.
3. Fill in:
   - **Menu Label**: `Help Center`
   - **Endpoint Slug**: should auto-fill as `help-center`. Confirm.
   - **Linked Content**: type `Help` in the search dropdown and select the **Help Center** page (Page type label visible in result).
   - **Prevent direct access**: check the box.
4. Click **Save Configuration**.

**Expected Result:**
- A success notice is shown.
- The new item appears at the bottom of the list, expanded with the saved values visible.
- Reloading the page preserves all values.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.6 — Verify changes on customer My Account
**Steps:**
1. Log in as `pf_test@example.com`.
2. Navigate to **My Account**.
3. Inspect the sidebar menu in order from top to bottom.

**Expected Result:**
- Menu order:
  1. **Subscriptions** (moved to top)
  2. Dashboard
  3. **My Purchases** (renamed from Orders)
  4. Addresses
  5. Account Details
  6. **Help Center** (custom endpoint, before Logout)
  7. Logout
- **Downloads** does NOT appear in the menu.
- Items may be in slightly different positions depending on the order set in Sub-Task 03.2 — check that:
  - Subscriptions is at top.
  - My Purchases label is used (not Orders).
  - Help Center item is present.
  - Downloads is absent.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.7 — Verify Help Center custom endpoint URL works
**Steps:**
1. As `pf_test@example.com`, click the **Help Center** menu item.
2. Observe the URL bar — it should be `/my-account/help-center/`.
3. Inspect the rendered content.

**Expected Result:**
- The URL is `/my-account/help-center/`.
- The Help Center page content (heading + body) is rendered inside the My Account layout.
- The My Account sidebar is still visible alongside the content.
- Theme styling of the My Account page applies.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.8 — Verify Prevent direct access redirect
**Steps:**
1. While logged in, navigate directly to `/help-center/` (the original page URL, not the My Account version).
2. Observe the destination URL.

**Expected Result:**
- The browser is redirected (302) to `/my-account/help-center/`.
- The page renders inside the My Account wrapper.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.9 — Verify Hidden vs URL-direct access (security note)
**Steps:**
1. Navigate directly to `/my-account/downloads/` even though Downloads is hidden from the menu.

**Expected Result:**
- The page LOADS — Downloads endpoint is still functional.
- Confirms the manual's note: "Hiding a menu item does not disable the underlying endpoint."

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.10 — Reserved-slug rejection
**Steps:**
1. Back as `qa_admin`, go to My Account Editor.
2. Click **Add Custom Item**. Try to set Endpoint Slug to `orders`.
3. Click **Save Configuration**.

**Expected Result:**
- Save fails with an error message exactly: "Endpoint conflicts with reserved name" (or close equivalent — record exact wording in Sign-off).
- The custom item is NOT saved with the reserved slug.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.11 — Cleanup
**Steps:**
1. Remove the test custom item with the reserved slug (it should not have saved).
2. Optionally restore Downloads visibility, restore Orders label, and remove Help Center custom endpoint — OR leave the configured customization in place if Stage 11+ needs it (note final state in Sign-off).
3. Save.
4. If permalinks misbehave, go to **Settings → Permalinks** and click **Save Changes** to flush rewrite rules.

**Expected Result:**
- Final My Account menu state documented.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Toggle **Enable My Account menu items customization** OFF and confirm WooCommerce defaults return on the customer side. Toggle back ON to restore.
- If Pro is active and Feature Manager / Store Credit features are enabled, confirm **My Features** and **Store Credit** menu items appear automatically and are configurable in the editor.
- Confirm the Subscriptions menu item label can be edited from this page (the manual notes General Settings no longer has separate Menu Title / Menu Position fields).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Final menu state (each item: visible/hidden, custom label if any):
- Exact wording of the reserved-slug error message:
- Notes:
