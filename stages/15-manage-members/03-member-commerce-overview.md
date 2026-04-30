# Stage 15 — Task 03: Member Commerce Overview

| Key | Value |
|---|---|
| Stage | Manage Members |
| Module | Manage Members — subscriptions, purchased products, store credit, addresses |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | 15-02 (profile loads); a customer with subscriptions, one-time orders, and store credit history |

## Objective
On a customer's Manage Members page, verify the three commerce sections render correctly: the **Subscriptions** table (every subscription regardless of status with documented columns), the **Purchased Products (Non-Subscription)** table (deduplicated, only completed/processing orders), and the **Store Credit** balance shown both as a stat card and a quick link to the dedicated Store Credit page where adjustments are visible.

## Pre-conditions
- Logged in as Administrator.
- Target customer has:
  - 3 subscriptions in mixed statuses (1 Active, 1 Cancelled, 1 Trial).
  - At least 2 non-subscription product orders with status `completed` (e.g. one simple physical product purchased twice across two orders).
  - 1 refunded order so the dedup logic can be verified.
  - A non-zero store credit balance with at least 2 adjustments visible.

## Test Data
- Target customer: `customer1@arraysubs.test`.
- Expected Subscriptions count: 3 (Active 1, Cancelled 1, Trial 1).
- Expected Purchased Products row: at least 1 product with Qty Purchased > 1 (aggregated across two orders).
- Store credit balance: e.g. `$25.00`.

## Sub-Tasks

### Sub-Task 3.1 — Subscriptions section default-expanded
**Steps:**
1. Open the customer's profile.
2. Locate the **Subscriptions** section below the stats grid.
3. Confirm it is expanded by default.

**Expected Result:**
- Section header reads `Subscriptions (3)` with the count badge.
- Section is expanded — the table is visible without clicking.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Subscriptions table columns
**Steps:**
1. Read the table headers from left to right.
2. Inspect each row's data.

**Expected Result:**
- 8 columns: **ID**, **Product**, **Status**, **Total**, **Billing**, **Next Payment**, **Created**, **Actions**.
- ID column shows clickable subscription IDs like `#142`.
- Status column shows colour-coded badges per the manual:
  - Active = Green; Trial = Light blue; Pending = Blue; On Hold = Yellow; Paused = Yellow; Cancelled = Red; Expired = Gray.
- Billing shows `Every {interval} {period}` (e.g. `Every 1 month`).
- Next Payment shows a date or `—` if no future payment.
- Actions column has a **View** link.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Open a subscription from the member view
**Steps:**
1. Click the ID `#xxx` for the Active subscription in the table.
2. Wait for the navigation.

**Expected Result:**
- The browser routes to the full subscription detail page within ArraySubs.
- The detail page header shows the same subscription ID.
- Browser back button returns to the Manage Members profile (or the URL hash routing places you back on the profile).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Purchased Products (Non-Subscription) section
**Steps:**
1. Return to the customer's profile.
2. Locate the **Purchased Products (Non-Subscription)** section.
3. Click the section header to expand it (collapsed by default).

**Expected Result:**
- Section title shows a count badge of distinct non-subscription products.
- Table columns: **Product**, **Type**, **Qty Purchased**, **Total Spent**.
- Subscription products are NOT listed here.
- The product purchased twice shows `Qty Purchased = 2` and `Total Spent` summing both orders.
- Refunded orders' products are excluded.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Empty-state confirmation for non-subscription
**Steps:**
1. Search and select a customer who has only subscriptions and no other product orders.
2. Open the Purchased Products section.

**Expected Result:**
- The section displays the message `No non-subscription products purchased.`
- No table rows are rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Store Credit balance on stats grid + quick link
**Steps:**
1. Return to `customer1@arraysubs.test` profile.
2. Note the value in the **Store Credit** card (purple) on the stats grid.
3. Click the **Manage Store Credit** quick link.

**Expected Result:**
- The Store Credit card matches the customer's actual balance (e.g. `$25.00`).
- Clicking the quick link navigates to the dedicated **ArraySubs → Store Credit** page scoped to this customer.
- The page shows the same balance and a transaction history.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.7 — Store Credit adjustments tab / history
**Steps:**
1. On the Store Credit page for this customer, locate the transaction history (or "Adjustments" tab if the build has tabs).
2. Read the most recent adjustments.

**Expected Result:**
- Each adjustment shows: amount (with sign for credit vs debit), date, source (admin manual / refund-to-credit / promotional / purchase / renewal / expiration), and a note if provided.
- The running balance is consistent: previous balance + this adjustment = next balance.
- The most recent two adjustments match what was set up in pre-conditions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.8 — Addresses section (read-only)
**Steps:**
1. Return to the customer's profile.
2. Click the **Addresses** section header to expand it (collapsed by default).
3. Read both the billing address card and the shipping address card.

**Expected Result:**
- Billing address card shows: Email, Phone, Street address (lines 1 and 2), City/State/Postcode line, Country code.
- Shipping address card shows: Phone (if set), Street address, City/State/Postcode, Country code.
- Both cards are read-only — there is no edit form here.
- If either address is unset, the documented empty-state strings appear: `No billing address set.` or `No shipping address set.`

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Open a customer with no store credit feature data — confirm the balance shows zero and the quick link still works (leading to an empty state).
- Change the customer's billing address from **Edit User** in a new tab; reload the profile — the addresses section reflects the new value.
- Confirm the dedup logic on Purchased Products: if a product was purchased once with quantity 3 in one order and quantity 1 in another, the Qty Purchased value is 4.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Customer used:
- Notes:
