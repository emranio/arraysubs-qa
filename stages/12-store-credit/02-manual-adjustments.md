# Stage 12 — Task 02: Manual Credit Adjustments

| Key | Value |
|---|---|
| Stage | 12 — Store Credit (Pro) |
| Module | Store Credit — Manage Credits |
| Plugin Coverage | Pro |
| Estimated Time | 20 min |
| Depends On | 12.01 |

## Objective
Search for a customer in the Store Credit Management page, add $50 with a note, then deduct $10 with a different note. Confirm balance updates immediately, the transaction history records both entries with correct labels and notes, and the customer sees the same balance and history in their portal Store Credit page. Validate the over-deduct guard.

## Pre-conditions
- 12.01 complete; **Enable Store Credit System** is **On**.
- `cust3@test.local` is a registered customer (created during Stage 00).
- Logged in as `admin@test.local`.

## Test Data
- Customer: `cust3@test.local` (display name `cust3`).
- First adjustment: type **Add**, amount `50.00`, note `Goodwill — Stage 12 baseline`.
- Second adjustment: type **Deduct**, amount `10.00`, note `QA test deduction`.
- Over-deduct attempt: type **Deduct**, amount `9999.00`.

## Sub-Tasks

### Sub-Task 2.1 — Open Manage Credits and search
**Steps:**
1. **WP-Admin → ArraySubs → Store Credit → Manage Credits**.
2. The page opens with a left **customer lookup panel** and an empty right-hand panel.
3. Type `cust3` (or `cust3@test.local`) in the search field.
4. Wait for the dropdown of results.

**Expected Result:**
- Search requires at least 2 characters; a single character does not trigger a request.
- A dropdown shows `cust3 (cust3@test.local) — Balance: $0.00` (or whatever balance currently exists).
- Up to 20 results can be displayed.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Load the customer profile
**Steps:**
1. Click `cust3` in the dropdown.
2. Read the right-hand panel.

**Expected Result:**
- **Current Credit Balance** is displayed in the store currency (e.g., `$0.00`).
- A **Member Details** button appears.
- A **Credit History** table is rendered (empty — `No transactions found.` — or showing prior transactions if any).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Add $50 with a note
**Steps:**
1. In the **Adjust Credit** section enter amount `50.00`.
2. Pick type **Add**.
3. In the **Note** field type `Goodwill — Stage 12 baseline`.
4. Click **Apply Adjustment**.

**Expected Result:**
- A success message appears (e.g., `Adjustment applied.`).
- The Current Credit Balance updates to `$50.00`.
- A new Credit History row appears at the top:
  - Date: today
  - Description: `Admin Adjustment` followed by the note `Goodwill — Stage 12 baseline`.
  - Amount: `+$50.00` (green / positive).
  - Balance After: `$50.00`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Customer sees the new balance in My Account
**Steps:**
1. In a separate browser session log in as `cust3@test.local`.
2. Open **My Account → Store Credit** (or the Store Credit menu item under My Account).
3. Read the balance and the customer-side transaction history.

**Expected Result:**
- The portal page renders without error.
- The balance displays `$50.00`.
- The transaction history shows the row added in 2.3 with the same description and amount.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Customer received the Credit Added email
**Steps:**
1. Open `cust3@test.local`'s mailbox (or the equivalent Mailpit / WP Mail Log view).
2. Find the latest email.

**Expected Result:**
- An email with subject containing `Store credit added to your account` arrives.
- Body shows:
  - Credit Added: `$50.00`.
  - New Balance: `$50.00`.
  - Source: `Admin Adjustment`.
  - A link to the Store Credit page in My Account.
- Customer name placeholder rendered as `cust3` (not raw `{customer_name}`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Deduct $10 with a note
**Steps:**
1. As admin, return to **Manage Credits** and reload `cust3`.
2. In **Adjust Credit** enter amount `10.00`.
3. Pick type **Deduct**.
4. Note: `QA test deduction`.
5. Click **Apply Adjustment**.

**Expected Result:**
- Balance drops to `$40.00` immediately.
- A new history row appears with description `Admin Adjustment` + note, Amount `-$10.00` (red), Balance After `$40.00`.
- The customer's Store Credit portal page updates on refresh.
- No Credit Added email is sent for the deduction (debits do not trigger that email).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.7 — Over-deduct guard
**Steps:**
1. Try to deduct `9999.00` from `cust3` (current balance is `$40.00`).
2. Click **Apply Adjustment**.

**Expected Result:**
- An error message appears similar to `Cannot deduct more than available balance` and the adjustment is NOT applied.
- The balance remains `$40.00`.
- No new Credit History row is created.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.8 — Clear selection
**Steps:**
1. Click the **Clear** button next to the customer name to deselect.

**Expected Result:**
- The right-hand panel returns to an empty / search-prompt state.
- Re-searching `cust3` and reselecting them shows balance `$40.00` and the two history rows.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- The Stage 12 README assumes `cust3@test.local`'s baseline balance entering Task 03 is `$40.00`. Note the balance in Sign-off.
- Confirm the **Credit History** under **ArraySubs → Store Credit → Credit History** also reflects both rows.
- DevTools Network: `POST` request to apply adjustment returns `200`; no `4xx` / `5xx`.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Final cust3 balance:
- Notes:
