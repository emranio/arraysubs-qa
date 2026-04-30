# Stage 06 — Task 01: Subscription Record After Checkout

| Key | Value |
|---|---|
| Stage | Initial Subscription Lifecycle |
| Module | Subscription Operations — All Subscriptions list |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | Stage 05 Task 01 (or any completed Stage 05 task) |

## Objective
Open **WP Admin → ArraySubs → Subscriptions** and verify that the subscription created during Stage 05 Task 01 (the `Basic Monthly` subscription for `customer-classic@example.test`) appears in the All Subscriptions list with all five expected columns rendered correctly: Status, Date, Customer (name + email), Product, Next Payment. Confirm the parent order link works and the Export CSV row matches the visible data.

## Pre-conditions
- Stage 05 Task 01 finished and the resulting order was marked **Completed**.
- Administrator login.
- Browser DevTools available (used in 1.4 to confirm no JS errors when interacting with the list).

## Test Data
- Subscription ID: from Stage 05 Task 01 sign-off (`__________`).
- Customer email: `customer-classic@example.test`.
- Product: `Basic Monthly`.
- Recurring amount: `$29.99`.
- Expected status: `Active`.
- Expected next payment: today (the date Stage 05 Task 01 was run) + 1 month.

## Sub-Tasks

### Sub-Task 1.1 — Open the All Subscriptions list
**Steps:**
1. Log in as Administrator.
2. Click **ArraySubs → Subscriptions** in the wp-admin sidebar.
3. Wait for the list to load.

**Expected Result:**
- The list page renders without a fatal error.
- A status tab bar is visible at the top with at least: All, Active, Pending, On Hold, Cancelled, Expired, Trial.
- The list table shows columns: Status, Date, Customer, Product, Next Payment.
- A row count or pagination control is visible at the bottom.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Locate the Stage 05 Task 01 subscription
**Steps:**
1. Click the **Active** status tab.
2. Search the search bar for `customer-classic@example.test` (or the subscription ID).
3. Identify the row created in Stage 05 Task 01.

**Expected Result:**
- The row appears in the filtered results.
- Status badge: **Active** (green).
- Date column: today's date (or the date Stage 05 Task 01 was run).
- Customer column: the customer's display name on top, `customer-classic@example.test` below.
- Product column: `Basic Monthly` (no variation suffix).
- Next Payment column: today + 1 month, formatted with the store's date format.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Verify each column matches the recorded sign-off data
**Steps:**
1. Cross-check each cell in the row against the Sign-off block of Stage 05 Task 01:
   - Subscription ID matches.
   - Customer email matches.
   - Product matches.
   - Next payment date matches the predicted today + 1 month.
2. Hover the Customer column to confirm the email is visible in the sub-line.
3. Hover the row to reveal Row Actions (View Details / Edit / Delete).

**Expected Result:**
- All four data points line up with sign-off.
- Row actions show **View Details** and **Edit**, but **Delete** is greyed out / hidden because the subscription is Active (per manual: *"You cannot delete Active or Trial subscriptions"*).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Open View Details and confirm parent-order link
**Steps:**
1. Click **View Details** on the row.
2. Wait for the detail screen to load.
3. Scroll to the **Order History** card.
4. Click the order number link in the row marked `Initial`.

**Expected Result:**
- The detail screen renders without errors.
- The Order History card lists the parent order with `Type: Initial`, total `$29.99`, status `Completed`.
- Clicking the order number opens the WooCommerce order edit screen for the same order ID recorded in Stage 05 Task 01 sign-off.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Verify recurring amount in the detail screen
**Steps:**
1. Return to the subscription detail.
2. Locate the **Billing Information** card.
3. Read the Recurring Amount, Billing Schedule, Completed Payments, and Payment Method rows.

**Expected Result:**
- Recurring Amount: `$29.99`.
- Billing Schedule: `Every 1 month(s)`.
- Completed Payments: `1` (the initial paid order counts as the first completed payment).
- Payment Method: `Direct bank transfer` (or whatever manual gateway was used in Stage 05 Task 01).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Export CSV and verify the row is present
**Steps:**
1. Return to the All Subscriptions list (Active tab still selected).
2. Click **Export CSV** at the top of the list.
3. Open the downloaded file in a spreadsheet or text editor.
4. Find the row matching the Stage 05 Task 01 subscription ID.

**Expected Result:**
- A CSV file downloads named like `subscriptions-export-2026-XX-XX-HHMMSS.csv`.
- The CSV is UTF-8 encoded with a BOM (Excel compatibility).
- The matching row contains: Subscription ID, Status `Active`, Customer Name, Customer Email `customer-classic@example.test`, Product Name `Basic Monthly`, Recurring Amount `29.99`, Currency, Billing Cycle, Start Date, Next Payment Date matching the UI value, Created Date.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Switch to the **All** tab and confirm the same row appears.
- Search by customer name (not email) and confirm the row is also returned.
- Confirm pagination works if more than 20 subscriptions exist (per manual: 20 per page).
- Open browser DevTools → Console — confirm no JavaScript errors are thrown when filtering tabs or running the search.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Subscription ID verified:
- Notes:
