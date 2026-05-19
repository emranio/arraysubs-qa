# Stage 11 — Task 01: Define Product Features

| Key | Value |
|---|---|
| Stage | 11 — Feature Manager (Pro) |
| Module | Feature Manager — Product Features |
| Plugin Coverage | Pro |
| Estimated Time | 20 min |
| Depends On | 03.01 (Pro Plan product exists) |

## Objective
Open the Feature Manager tab inside a simple subscription product, define a mixed feature set covering all three feature types (Number, Number/Unlimited, Text, Toggle), save the product, and confirm every feature persists with the correct value, type, and order after a hard reload. This is the data foundation every later Feature Manager task depends on.

## Pre-conditions
- Logged in as `admin@test.local` (Administrator).
- ArraySubsPro is active.
- **ArraySubs → Settings → Feature Manager → Enable Feature Manager** is **On**.
- The simple subscription product `Pro Plan` from Stage 03 exists and is published.

## Test Data
- Target product: `Pro Plan` ($19.99 / week, simple subscription).
- Features to define (in this order):
  1. `Seats` — Number — `5` — Enabled
  2. `Storage (GB)` — Number — `10` — Enabled
  3. `API Calls` — Number — `10000` — Enabled
  4. `Priority Support` — Toggle — `Yes` — Enabled
  5. `Custom Domain` — Toggle — `No` — Enabled
  6. `Plan Tier` — Text — `Starter` — Enabled
  7. `Hidden Beta Flag` — Text — `internal-only` — **Disabled** (used to confirm disabled features hide from storefront in Task 02).

## Sub-Tasks

### Sub-Task 1.1 — Open the Feature Manager tab
**Steps:**
1. **WP-Admin → Products → All Products**.
2. Click **Edit** on `Pro Plan`.
3. Scroll to the **Product data** panel (below the price fields).
4. Locate the vertical tab list on the left side of the Product data panel.
5. Click the **Feature Manager** tab.

**Expected Result:**
- The **Feature Manager** tab appears in the Product data panel after **Attributes** and before **Advanced**.
- Clicking it loads a panel that shows either an **Add Feature** button (if no features exist yet) or an **Edit Features** button plus a preview table.
- No PHP notice or red banner appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Add the seven test features
**Steps:**
1. Click **Add Feature** (or **Edit Features** if a modal entry already exists). The **Manage Features** modal opens.
2. Inside the modal click **Add Feature**. A blank editing row appears.
3. For each row in the Test Data table above:
   - Type the feature name in the **Name** field.
   - Pick the matching value from the **Type** dropdown (Number / Toggle / Text).
   - Type the value into the **Value** field (for Toggle, choose `Yes` or `No` from the dropdown).
   - Toggle **Enabled** to match the test data (last row stays disabled).
   - Click the **✓** (check) icon on the row to confirm.
   - Click **Add Feature** again to start the next row.
4. After all seven rows are confirmed, click **Save** at the bottom of the modal.
5. Click **Update** on the main product editor to persist the change to the product.

**Expected Result:**
- All seven rows can be added without an "is required" validation error.
- Each row's **✓** confirm icon turns the row from edit mode to read mode.
- Modal **Save** closes the modal with a success state.
- The product update banner appears at the top of the page (`Product updated.`).
- The Feature Manager preview table now shows seven rows with columns **Title**, **Type**, **Value**, **Enabled**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Verify validation rules
**Steps:**
1. Click **Edit Features** to reopen the modal.
2. Click **Add Feature** to add a new blank row.
3. Leave the **Name** blank, set type Number, value `1`, click **✓**.
4. Observe the validation feedback.
5. Cancel that row (**✗**), add another, set name `Bad Number`, type Number, value `not-a-number`, click **✓**.
6. Observe the validation feedback.
7. Cancel that row, then **Save** the modal so no broken rows persist.

**Expected Result:**
- Empty name is rejected — either the row stays in edit mode with an inline error or the modal blocks save with a "Name is required" message.
- Number type rejects non-numeric / non-`Unlimited` text — an inline validation error appears (case-insensitive `Unlimited` is the only accepted text).
- Cancelling restores the row count to seven.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Reorder features
**Steps:**
1. Click **Edit Features**.
2. Use the drag handle (grip icon on the left of `API Calls`) to drag it above `Storage (GB)`. If drag does not work, use the **Move Up** arrow button in the Actions column.
3. Click **Save** in the modal.
4. Click **Update** on the product.
5. Hard-refresh the page (`Ctrl + Shift + R`).
6. Re-open Feature Manager.

**Expected Result:**
- After reload, the order in the preview table is: `Seats`, `API Calls`, `Storage (GB)`, `Priority Support`, `Custom Domain`, `Plan Tier`, `Hidden Beta Flag`.
- The order persists; no row has reverted.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Verify "Unlimited" value on a number feature
**Steps:**
1. Click **Edit Features**.
2. Click the **Edit** (pencil) icon on the `API Calls` row.
3. Change the value from `10000` to `Unlimited` (capital U).
4. Click **✓** to confirm, then **Save**.
5. Click **Update**.
6. Reload the page and re-open the modal.

**Expected Result:**
- `Unlimited` is accepted without a validation error.
- After reload the value column shows `Unlimited` exactly.
- Now revert: edit again and set the value back to `10000`, save, update.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Persistence after full reload
**Steps:**
1. Hard-refresh the product edit page (`Ctrl + Shift + R`).
2. Click the **Feature Manager** tab.
3. Read the preview table.

**Expected Result:**
- All seven feature rows are present.
- Names, types, values, and enabled state match the Test Data table exactly (after the reorder from 1.4 — meaning `API Calls` sits between `Seats` and `Storage (GB)`).
- The disabled row `Hidden Beta Flag` shows the **Enabled** column as off.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Negative check: tab hidden when module disabled
**Steps:**
1. **WP-Admin → ArraySubs → Settings → Feature Manager**.
2. Toggle **Enable Feature Manager** to **Off** and click **Save Settings**.
3. Re-open the `Pro Plan` product edit page.
4. Look for the Feature Manager tab in the Product data panel.
5. Re-enable **Enable Feature Manager** and save settings before continuing to Task 02.

**Expected Result:**
- With the module Off, the **Feature Manager** tab is not visible in the Product data panel.
- The previously saved features remain in the database — when the module is re-enabled the tab returns and all seven features are still present.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Open `wp-content/debug.log` and confirm no `PHP Notice`, `PHP Warning`, or `PHP Fatal error` was logged during any of the saves.
- Browser DevTools console must show zero JavaScript errors thrown by `feature-manager` script bundles when the modal opens, saves, and closes.
- Re-confirm the Stage 03 `Pro Plan` price ($19.99) and weekly billing schedule were not changed by the Feature Manager edits.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
