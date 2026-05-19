# Stage 14 — Task 01: All Subscriptions List and Filters

| Key | Value |
|---|---|
| Stage | Admin Subscription Management |
| Module | Admin SPA — Subscriptions list |
| Plugin Coverage | Free |
| Estimated Time | 20 min |
| Depends On | Stages 00–06 (subscriptions in mixed statuses) |

## Objective
Open **ArraySubs → Subscriptions** and verify the All Subscriptions screen renders every documented column, the six status filter tabs, the customer/email/username search box, the Add New button, the Export CSV button, and that the colour-coded status badges in each row match the lifecycle manual table (Active = green, Pending = orange, On Hold = yellow, Cancelled = red, Expired = red, Trial = cyan).

## Pre-conditions
- Logged in as Administrator.
- At least one subscription exists in each of: Active, Trial, Pending, On Hold, Cancelled, Expired.
- The list contains a mix of weekly subscriptions (most rows — `Standard Weekly`, `Trial Weekly`, `Pro Plan`, `Enterprise Plan`, etc.) **and** at least one `Basic Monthly` ($29.99/month) subscription so the screen surfaces both billing intervals.
- Pagination data: at least 21 subscriptions in total (so two pages exist).
- Stripe is already configured in test mode (subscriptions backed by Stripe will appear alongside any manual-gateway rows).

## Test Data
- Customer search term: the email address of one subscriber created in stage 06 (e.g. `customer1@arraysubs.test`).
- Product search term: the slug or partial product name of one subscription product created in stage 03 (e.g. `Standard Weekly`).
- Expected default page size: 20 rows per page.

## Sub-Tasks

### Sub-Task 1.1 — Open the list and verify columns
**Steps:**
1. From wp-admin sidebar click **ArraySubs → Subscriptions**.
2. Wait for the list to finish loading (loading skeleton disappears).
3. Read the column headers from left to right.

**Expected Result:**
- Five columns are present in this order: **Status**, **Date**, **Customer**, **Product**, **Next Payment**.
- Each row shows: a coloured status badge in the Status column, a date in Date, customer name and email stacked in Customer, product name and variation stacked in Product, the next renewal date (or `—` for cancelled/expired) in Next Payment.
- The Product column surfaces a mix of weekly products (e.g., `Standard Weekly`, `Pro Plan`) and at least one monthly product (`Basic Monthly`) so both billing intervals are represented.
- An **Add New** button and an **Export CSV** button appear above the table.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Verify status badge colours match the lifecycle manual
**Steps:**
1. Find one row for each status (use the status tabs to surface them if needed — covered next).
2. Visually compare the badge colour of each status against the table below.

**Expected Result:**

| Status | Badge colour visible in row |
|---|---|
| Active | Green |
| Pending | Orange |
| Trial | Cyan |
| On Hold | Yellow |
| Cancelled | Red |
| Expired | Red |

- No status renders as plain grey or with a missing colour.
- Hovering a badge does not break the layout.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Cycle through status filter tabs
**Steps:**
1. Click the **All** tab — note the row count.
2. Click **Active** — confirm only green-badge rows appear.
3. Click **Pending** — confirm only orange-badge rows appear.
4. Click **On Hold** — confirm only yellow-badge rows appear.
5. Click **Cancelled** — confirm only red-badge rows with status text "Cancelled" appear.
6. Click **Expired** — confirm only red-badge rows with status text "Expired" appear.
7. Click **Trial** — confirm only cyan-badge rows appear.
8. Click back to **All**.

**Expected Result:**
- Each tab shows the correct subset.
- Each tab header includes a count in parentheses (or a similar indicator) reflecting that subset's row total.
- The URL or query string updates as you switch tabs (so the tab is shareable / bookmarkable).
- Switching to **All** restores the unfiltered count.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Customer search by email
**Steps:**
1. From the **All** tab, click into the search bar (placeholder mentions name / email / username).
2. Type the test customer's full email address.
3. Press Enter (or click the search icon).

**Expected Result:**
- Only rows whose customer email matches the search term remain.
- Subscription IDs returned correspond to that customer.
- Clearing the search restores the unfiltered list.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Customer search by partial username
**Steps:**
1. Clear the search field.
2. Type the first 3–4 characters of a known username.
3. Press Enter.

**Expected Result:**
- Rows for users whose username starts with or contains those characters appear.
- Search is case-insensitive.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Product search behaviour (manual reference check)
**Steps:**
1. Clear the search field.
2. Type a subscription product name (e.g. `Standard Weekly`).
3. Press Enter and observe the results.

**Expected Result:**
- Per the manual, the search bar matches customer name, email, and username only — not product name.
- If the search returns zero rows, that is the documented behaviour.
- If the build returns matches keyed on product name, log the deviation in Fail Notes (this is a deviation from the user manual and must be reported).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Pagination controls
**Steps:**
1. Clear filters and search.
2. Scroll to the bottom of the list.
3. Note the page navigation controls.
4. Click the **Next** page (or page 2).

**Expected Result:**
- Up to 20 subscriptions render per page.
- A page indicator like `1 of N` (or a numbered pager) is visible.
- Clicking page 2 loads the next 20 rows; the URL or state reflects the new page.
- Total row count matches the count shown on the **All** tab.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Repeat sub-task 1.3 logged in as **Shop Manager** — every tab should still work, every row should still render, no Pro-only badges should be missing.
- Confirm the row hover actions (View Details, Edit, Delete) reveal correctly. Delete must be hidden for Active and Trial rows per the manual.
- Confirm no JavaScript errors are thrown in DevTools → Console while switching tabs and searching.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Total subscriptions seen on All tab:
- Notes:
