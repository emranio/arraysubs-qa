# Stage 12 — Task 03: Credit History Filters

| Key | Value |
|---|---|
| Stage | 12 — Store Credit (Pro) |
| Module | Store Credit — Credit History |
| Plugin Coverage | Pro |
| Estimated Time | 15 min |
| Depends On | 12.01, 12.02 |

## Objective
Open the global **Credit History** page, exercise both filter dropdowns (Source and Type), and verify the table refreshes correctly. Note that some filters will not yet have rows (e.g., Expired, Refund, Renewal Applied) — confirm the empty-state message is correct, since 12.04–12.08 will populate them later.

## Pre-conditions
- 12.01 and 12.02 complete; `cust3@test.local` has at least one Add and one Deduct entry from `Admin Adjustment`.
- Logged in as `admin@test.local`.

## Test Data
- Source filter values to test: All Sources, Plan Downgrade, Refund, Admin Adjustment, Promotional, Credit Purchase, Applied to Renewal, Applied to Order, Expired.
- Type filter values: All Types, Credit (Added), Debit (Deducted).

## Sub-Tasks

### Sub-Task 3.1 — Open Credit History
**Steps:**
1. **WP-Admin → ArraySubs → Store Credit → Credit History**.
2. Confirm the page loads.

**Expected Result:**
- Page heading is `Credit History` (or close to it).
- A table appears below two filter dropdowns and a `Total: <N> transactions` counter.
- The table is paginated (20 per page).
- The two `cust3` rows from 12.02 are visible (newest first).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Filter by Source = Admin Adjustment
**Steps:**
1. Set the **Source** dropdown to `Admin Adjustment`.
2. Wait for the table to refresh.

**Expected Result:**
- The table refreshes automatically (no full page reload required).
- Both `cust3` Admin Adjustment rows from 12.02 (the +$50 and the -$10) are visible.
- The `Total: <N> transactions` counter matches the row count.
- Pagination resets to page 1.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Filter by Source = each remaining option
**Steps:**
1. Iterate the **Source** dropdown through each option in turn:
   - `Plan Downgrade`
   - `Refund`
   - `Promotional`
   - `Credit Purchase`
   - `Applied to Renewal`
   - `Applied to Order`
   - `Expired`
2. After each selection, observe the table.

**Expected Result:**
- For sources that do not yet have any transactions, the table renders the empty state (e.g., `No transactions found.`) and the counter shows `0`.
- For any source that already has transactions (none expected at this stage besides Admin Adjustment), only matching rows appear.
- Switching back to **All Sources** shows all rows.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Filter by Type = Credit (Added)
**Steps:**
1. Reset Source to **All Sources**.
2. Set **Type** = `Credit (Added)`.

**Expected Result:**
- Only credit-add rows are shown (the +$50 admin adjustment for `cust3` is in this list).
- All amount cells show a `+` prefix in green.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Filter by Type = Debit (Deducted)
**Steps:**
1. Set **Type** = `Debit (Deducted)`.

**Expected Result:**
- Only debit rows appear (the -$10 deduction for `cust3`).
- All amount cells show a `-` prefix in red.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Combined filter
**Steps:**
1. Source = `Admin Adjustment`, Type = `Credit (Added)`.

**Expected Result:**
- Only the single +$50 admin adjustment row from 12.02 appears.
- Counter reads `Total: 1 transactions` (or singular form if the plugin pluralizes).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.7 — Read the row contents
**Steps:**
1. Inspect any visible row (the +$50 row).
2. Confirm each column.

**Expected Result:**
- **ID** column shows a numeric / `#` prefixed identifier.
- **Date** matches when the adjustment was applied.
- **Customer** shows `cust3` with `cust3@test.local` underneath.
- **Description** shows source label and the note.
- **Amount** shows `+$50.00` in green.
- **Actions** column has a delete (trash) icon.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.8 — Delete log entry warning (do NOT delete)
**Steps:**
1. Click the trash icon on a non-essential row (or just hover and observe the tooltip / modal trigger; do not confirm).
2. Read the confirmation modal.
3. Click **Cancel**.

**Expected Result:**
- A confirmation modal appears showing the transaction ID, customer name, and amount.
- The footer warning reads similar to `This log is read-only. Deleting entries only removes the log record, it does not affect actual credit balances.`
- Cancelling closes the modal without modifying anything.
- The row count is unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After all filter changes, confirm `cust3`'s actual balance from 12.02 (`$40.00`) is unchanged on **Manage Credits**.
- DevTools Network: filter changes trigger an XHR/REST call returning `200`.
- DevTools console: no JS errors when toggling filters.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
