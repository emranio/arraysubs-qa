# Stage 07 — Task 01: My Subscriptions List

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / My Subscriptions Page |
| Plugin Coverage | Both (Free + Pro) |
| Estimated Time | 15 min |
| Depends On | Stage 03 catalog, Stages 05/06 subscriptions seeded for `cust1@example.com` |

## Objective
Log in as a customer with multiple subscriptions in mixed statuses, open **My Account → Subscriptions**, and verify the My Subscriptions list page renders the expected table columns, status badge colours, pagination, empty state, and that the recurring amount reflects any active retention discount.

## Pre-conditions
- Test customer `cust1@example.com` owns at least 12 subscriptions across these statuses (so pagination triggers): Active, Trial, On-Hold, Pending, Cancelled, Expired.
- At least one Active subscription has a retention discount applied (use admin → subscription → apply discount, or carry over from Stage 08 dry run).
- Storefront My Account page is published and accessible at `/my-account/`.

## Test Data
- URL under test: `/my-account/subscriptions/`
- Customer login: `cust1@example.com`
- Expected pagination boundary: 10 subscriptions per page

## Sub-Tasks

### Sub-Task 01.1 — Open the My Subscriptions page
**Steps:**
1. In an incognito browser window, visit the storefront and click **My Account**.
2. Log in as `cust1@example.com`.
3. In the My Account left navigation, click the **Subscriptions** tab.

**Expected Result:**
- Browser URL is `/my-account/subscriptions/`.
- A table titled with the subscriptions list renders without console errors (open DevTools → Console; no red errors).
- The page is sorted with the newest subscription at the top.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.2 — Verify table columns
**Steps:**
1. Inspect the table header row.
2. Read each column header from left to right.

**Expected Result:**
- Columns appear in this exact order: **Product**, **Status**, **Next Payment**, **Total**, **Actions**.
- The Product column shows the subscription ID as a link plus the product name underneath.
- The Total column shows the recurring amount with the billing schedule on a second line (for example `$19.99` then `every 1 week`; the Basic Monthly subscription, if listed, shows `$29.99` then `every 1 month`).
- The Actions column contains a **View** button per row.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.3 — Verify status badge colours
**Steps:**
1. Locate one row each of Active, Trial, On-Hold, Cancelled, Expired and Pending.
2. For each, observe the colour of the Status badge.

**Expected Result:**
- **Active** badge is green.
- **Trial** badge is cyan / light blue.
- **On-Hold** badge is yellow / amber.
- **Cancelled** badge is red.
- **Expired** badge is red.
- **Pending** badge renders in a neutral / grey style (record exact colour observed).
- The Next Payment column shows a date for Active/On-Hold/Trial rows and `—` for Cancelled, Expired, and Pending rows.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.4 — Verify retention discount reflected in Total
**Steps:**
1. Find the row for the Active subscription that has an active retention discount.
2. Compare the Total column amount to the product's normal recurring price.

**Expected Result:**
- The Total amount shown is the discounted recurring amount, not the base product price.
- The billing schedule line still reads correctly (e.g. `every 1 week` or `every 1 month`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.5 — Verify pagination
**Steps:**
1. Scroll to the bottom of the table.
2. Observe the pagination controls and the summary line.
3. Click **Next** (or page 2).
4. Click **Previous** (or page 1).

**Expected Result:**
- A summary line reads "Showing X–Y of Z subscriptions." with sensible numbers.
- Previous/Next links are present and the current page is visually indicated.
- Clicking Next loads the next 10 rows; clicking Previous returns to page 1 without a full WordPress reload error.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.6 — Empty state
**Steps:**
1. In a second incognito window, log in as a fresh test account that has never purchased a subscription.
2. Navigate to **My Account → Subscriptions**.

**Expected Result:**
- The page displays exactly: **"You have no subscriptions yet."**
- No table is rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.7 — Click View on a subscription
**Steps:**
1. Return to `cust1@example.com` window.
2. Click **View** on the first Active row.

**Expected Result:**
- Browser URL changes to `/my-account/view-subscription/{id}/` where `{id}` matches the row's subscription ID.
- Detail page loads (deep verification done in Task 02).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Verify the **Subscriptions** menu item is positioned per Profile Builder configuration set in Stage 02.
- Confirm the customer cannot access another customer's subscription by manipulating `{id}` in the URL — should display: **"Subscription not found or you do not have permission to view it."**

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
