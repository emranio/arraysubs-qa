# Stage 03 — Task 12: Feature Manager Entitlements (Pro)

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Feature Manager / Defining Product Features |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | 03.10 |

## Objective
Define product features (Seats, Storage, Priority Support, Custom Domain) on a subscription product. Verify that Toggle, Number, and Text feature types all save correctly, that the storefront `What's Included` section honours the **Show on Product Page** toggle, and that disabled features are not displayed publicly.

## Pre-conditions
- ArraySubs Pro installed and active.
- Feature Manager module enabled in **ArraySubs → Settings → Feature Manager** (master toggle ON).
- Stage 03.10 complete — using `Pro Plan` ($19.99/week) as the subject product.

## Test Data
- Target product: `Pro Plan` (created in 03.10).
- Features to define:
  | Order | Name | Type | Value | Enabled |
  |---|---|---|---|---|
  | 1 | `Seats` | Number | `5` | Yes |
  | 2 | `Storage (GB)` | Number | `10` | Yes |
  | 3 | `Priority Support` | Toggle | `Yes` | Yes |
  | 4 | `Custom Domain` | Toggle | `No` | Yes |
  | 5 | `Beta Access` | Text | `Invite-only` | No (disabled) |

## Sub-Tasks

### Sub-Task 12.1 — Pro/module gating check
**Steps:**
1. Open `Pro Plan` product editor.
2. Confirm the **Feature Manager** tab appears in the product data panel (between Attributes and Advanced).
3. (Optional) Toggle off **Enable Feature Manager** in settings, reload, and confirm the tab disappears. Re-enable before continuing.

**Expected Result:**
- Feature Manager tab appears only when both Pro is active AND the module setting is enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.2 — Open the Manage Features modal
**Steps:**
1. In `Pro Plan` product, click **Feature Manager** tab.
2. Click **Add Feature** (or **Edit Features** if features exist).
3. Confirm the **Manage Features** modal opens.

**Expected Result:**
- Modal is visible with an empty feature list and an `Add Feature` button.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.3 — Add Number features (Seats, Storage)
**Steps:**
1. Click **Add Feature** in the modal.
2. Name `Seats`, Type `Number`, Value `5`, Enabled on. Click the **✓** icon.
3. Click **Add Feature** again.
4. Name `Storage (GB)`, Type `Number`, Value `10`, Enabled on. Click **✓**.
5. Click **Save** at the bottom of the modal.
6. Click **Update** on the product.

**Expected Result:**
- Two number-type features saved.
- Preview table shows columns Title / Type / Value / Enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.4 — Add Toggle and Text features
**Steps:**
1. Reopen **Manage Features** via **Edit Features**.
2. Add `Priority Support` (Toggle, value `Yes`, enabled).
3. Add `Custom Domain` (Toggle, value `No`, enabled).
4. Add `Beta Access` (Text, value `Invite-only`, **disabled**).
5. Click **Save**, then **Update** on the product.

**Expected Result:**
- All five features appear in the preview table.
- `Beta Access` row shows Enabled = off (or false) in the table.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.5 — Number type validation
**Steps:**
1. Reopen the modal. Edit `Storage (GB)` row and change value to `Unlimited`. Click ✓.
2. Save the modal and Update the product.
3. Reopen and confirm value persists.
4. Edit `Storage (GB)` again, set value to `not-a-number-text`, click ✓.

**Expected Result:**
- `Unlimited` (case-insensitive) is accepted (per manual: "Must be a valid number or the word 'Unlimited' (case-insensitive)").
- Free-text other than a number/`Unlimited` is rejected with an inline validation hint.
- Reset value back to `10` before continuing.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.6 — Verify storefront `What's Included` section
**Steps:**
1. Go to **ArraySubs → Settings → Feature Manager** and ensure **Show on Product Page** is enabled.
2. Open `Pro Plan` product page in an incognito window.

**Expected Result:**
- A `What's Included` section is rendered under the product summary.
- Listed features (in order): `Seats — 5`, `Storage (GB) — 10`, `Priority Support — ✓`, `Custom Domain — ✗`.
- `Beta Access` is **not** displayed (it is disabled).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.7 — Toggle the storefront display off
**Steps:**
1. Disable **Show on Product Page** in Feature Manager settings.
2. Reload the product page in incognito.

**Expected Result:**
- The `What's Included` section disappears entirely.
- Re-enable the setting and confirm it returns.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.8 — Reorder check
**Steps:**
1. Reopen the modal and use the drag handle (or Move Up/Down buttons) to swap the order of `Seats` and `Storage (GB)`.
2. Save and Update.
3. View the storefront page.

**Expected Result:**
- The What's Included list reflects the new order.
- Restore original order after verification (Seats before Storage) for downstream consistency.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the variable product `Coffee Plan` exposes the Feature Manager fields per-variation (open a variation and verify the Feature Manager section is inside the variation panel).
- Confirm the storefront note "currently renders on simple subscription products only" — variable products should NOT show the What's Included section at the parent product level.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
