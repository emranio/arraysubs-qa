# Stage 06 — Task 05: Order Page Related Subscriptions

| Key | Value |
|---|---|
| Stage | Initial Subscription Lifecycle |
| Module | Subscription Checkout — Related Subscriptions Table |
| Plugin Coverage | Both |
| Estimated Time | 10 min |
| Depends On | Task 01 |

## Objective
Open the parent order from Stage 05 Task 01 on the customer-facing **Order Details** page (`My Account → Orders → View order`). Verify that the **Related Subscriptions** table appears with the subscription ID (linked to the customer portal), a colored status badge, the next payment date, and the recurring total with billing schedule (per the manual: *"After checkout, the order confirmation and My Account order detail pages display a Related Subscriptions table with: Subscription ID (linked to the customer portal), Status badge (color-coded), Next payment date, Recurring total with billing schedule"*).

## Pre-conditions
- Stage 05 Task 01 finished and the resulting order is Completed.
- The customer can log in.

## Test Data
- Customer: `customer-classic@example.test`.
- Parent order ID: from Stage 05 Task 01 sign-off (`__________`).
- Subscription ID: from Stage 05 Task 01 sign-off (`__________`).

## Sub-Tasks

### Sub-Task 5.1 — Log in and open the order list
**Steps:**
1. In Incognito, log in as `customer-classic@example.test`.
2. Click **My Account → Orders**.

**Expected Result:**
- The Orders tab loads with at least one row.
- The Stage 05 Task 01 order is in the list with status `Completed` and total `$29.99`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.2 — Open the order detail page
**Steps:**
1. Click **View** on the Stage 05 Task 01 order.
2. Wait for the order details page to load.

**Expected Result:**
- The order detail page renders without errors.
- The page shows order summary (line items, totals, billing/shipping addresses).
- A **Related Subscriptions** section is visible somewhere on the page (typically below the order summary).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.3 — Verify the Related Subscriptions table columns
**Steps:**
1. Read the headers of the Related Subscriptions table.
2. Read each cell in the single row.

**Expected Result:**
- The table shows the columns required by the manual: Subscription ID, Status, Next Payment, Recurring Total.
- Subscription ID cell: clickable link pointing at `/my-account/view-subscription/<ID>/`.
- Status cell: green **Active** badge (color-coded).
- Next Payment cell: Stage 05 Task 01 date + 1 month.
- Recurring Total cell: `$19.99` with `every 1 week` (billing schedule shown). Where the order is for Basic Monthly, expect `$29.99` / `every 1 month`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.4 — Click the subscription link and confirm it lands on the portal
**Steps:**
1. Click the Subscription ID link in the table.

**Expected Result:**
- The browser navigates to `/my-account/view-subscription/<ID>/`.
- The detail page loads with the same subscription data verified in Stage 06 Task 04.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.5 — Repeat on the Trial order from Stage 05 Task 03
**Steps:**
1. Log out, log in as `customer-trial@example.test`.
2. Open My Account → Orders.
3. Click View on the Stage 05 Task 03 trial order ($0.00).
4. Inspect the Related Subscriptions table.

**Expected Result:**
- Related Subscriptions table appears with one row.
- Status badge: cyan **Trial**.
- Next Payment: trial end date (Stage 05 Task 03 date + 14 days).
- Recurring Total: `$19.99 / every 1 week`.
- Subscription ID link works and lands on the trial subscription's portal page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.6 — Confirm the order received page (immediately after checkout) also shows the table
**Steps:**
1. *(Optional, only if you can re-run a checkout in this stage)* — alternatively re-open the URL of the original order received page if still cached.
2. Confirm the Related Subscriptions table is present on that page too.

**Expected Result:**
- The Related Subscriptions table appears on both the order received / thank-you page and the My Account → Orders → View order page (per manual: *"the order confirmation and My Account order detail pages display a Related Subscriptions table"*).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the same order viewed by a non-owner (different logged-in customer) does NOT expose the Related Subscriptions table or any subscription IDs.
- Confirm that mixed-cart orders from Stage 05 Task 14 show only one row in the Related Subscriptions table (the subscription, not the regular product).
- Confirm clicking the Subscription ID link does not 404 — the portal route is registered and the customer owns it.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Active order ID + subscription ID verified:
- Trial order ID + subscription ID verified:
- Notes:
