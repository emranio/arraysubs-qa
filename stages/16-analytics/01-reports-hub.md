# Stage 16 — Task 01: Reports Hub

| Key | Value |
|---|---|
| Stage | Analytics & Reports |
| Module | ArraySubs → Reports (hub directory) |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | Pre-flight stage 00 (admin menu visibility) |

## Objective
Open **ArraySubs → Reports** and verify the directory page renders with: a summary bar, a quick-navigation pill row, and 12 categorized sections each with their report cards. Confirm Free / Pro badges, descriptive titles, and category-to-report mappings match the manual. Verify the capability check: Shop Manager can see the page; Customer cannot.

## Pre-conditions
- Logged in as Administrator initially.
- A Shop Manager account exists (`shop@arraysubs.test`).
- A Customer account exists (`customer1@arraysubs.test`).
- ArraySubs Pro is active (so Pro-labelled cards are clickable).

## Test Data
- Expected category count: 12.
- Expected categories: Subscription Performance *(Pro)*, Leaderboards *(Pro)*, Retention Analytics *(Free)*, Orders Analytics *(Pro)*, Revenue Analytics *(Pro)*, Products & Variations Analytics *(Pro)*, Customers Analytics *(Pro)*, Order List Enhancements *(Pro)*, Subscriptions *(Free)*, Member Insights *(Pro)*, Store Credit *(Pro)*, Audit Logs *(Pro)*.

## Sub-Tasks

### Sub-Task 1.1 — Open the hub
**Steps:**
1. From wp-admin sidebar, click **ArraySubs → Reports**.
2. Wait for the page to render.

**Expected Result:**
- The page loads at the Reports Hub with no empty state or error.
- A summary bar at the top shows: total categories (`12`), total reports across categories, count of free vs Pro.
- Quick-navigation pills row appears below the summary bar with one pill per category.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Verify all 12 categories render
**Steps:**
1. Scroll the page top to bottom.
2. Note each section heading.

**Expected Result:**
- Categories appear in this order (or grouped per the build): Subscription Performance, Leaderboards, Retention Analytics, Orders Analytics, Revenue Analytics, Products & Variations Analytics, Customers Analytics, Order List Enhancements, Subscriptions, Member Insights, Store Credit, Audit Logs.
- Each category header includes its name, a brief description, and a Free or Pro label.
- Each category shows a responsive grid of report cards.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Spot-check report cards in each category
**Steps:**
1. Subscription Performance — confirm 7 report cards are listed (Performance Dashboard, MRR Trend Chart, Net Subscription Growth Chart, Churn Rate Chart, Renewal Revenue Chart, Trial Conversion Rate Chart, Active Subscriptions Chart).
2. Leaderboards — confirm 5 report cards (Top Subscription Products — Active, Top Subscription Products — Revenue, Top Subscribers — Lifetime Value, Top Cancellation Reasons, Highest Churn Products).
3. Retention Analytics — confirm 6 cards (Retention Summary, Churn Reasons Breakdown, Retention Offer Performance, Retention Trend Chart, Activity Logs, Product-Level Retention).
4. Orders Analytics — confirm 6 cards (All Orders by Type, Renewal Orders Only, Initial Purchase Orders, Trial Orders, Plan Switch Orders, Credit Purchase Orders).
5. Revenue Analytics — confirm 3 cards (Revenue Overview, Subscription Renewal Revenue, Subscription Upgrade Revenue).
6. Products & Variations Analytics — confirm 2 cards (Subscription Products Report, Subscription Variations Report).
7. Customers Analytics — confirm 1 card (Customers Report with Member Links).
8. Order List Enhancements — confirm 3 cards.
9. Subscriptions — confirm 3 cards (All Subscriptions List, Subscription Detail View, CSV Export).
10. Member Insights — confirm 2 cards.
11. Store Credit — confirm 2 cards.
12. Audit Logs — confirm 3 cards.

**Expected Result:**
- Each card has: a descriptive title, an explanation, a Free or Pro badge, and a link arrow.
- No card has a missing title or empty description.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Quick-nav pills scroll to sections
**Steps:**
1. Scroll back to the top of the page.
2. Click each quick-nav pill in turn (e.g. "Retention Analytics").

**Expected Result:**
- Clicking a pill smoothly scrolls the page to that category's section.
- The browser URL hash updates so the link is shareable / bookmarkable.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Free / Pro badge accuracy
**Steps:**
1. Inspect the badge on each report card.
2. Cross-reference with the manual's category list:
   - Free: Retention Analytics; Subscriptions.
   - Pro: everything else.

**Expected Result:**
- Every card under Free categories carries a Free badge.
- Every card under Pro categories carries a Pro badge.
- No badge is missing or mislabelled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Capability check: Shop Manager
**Steps:**
1. Log out as Administrator.
2. Log in as `shop@arraysubs.test` (Shop Manager).
3. From wp-admin sidebar, click **ArraySubs → Reports**.

**Expected Result:**
- The Reports Hub loads identically for the Shop Manager.
- Pro cards are still labelled Pro and still clickable (since Pro is active site-wide).
- No "permission denied" message.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Capability check: Customer
**Steps:**
1. Log out the Shop Manager.
2. Log in as `customer1@arraysubs.test` (Customer role).
3. Visit `wp-admin/admin.php?page=arraysubs-reports` (or the canonical Reports URL).

**Expected Result:**
- Customer is blocked: either redirected away from wp-admin entirely, or shown the "You do not have sufficient permissions to access this page." WordPress error.
- The ArraySubs sidebar menu does not render at all for the Customer role.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Deactivate ArraySubs Pro temporarily. Reload the Reports Hub as Administrator. Confirm Pro cards still render with Pro badges (per the manual, they remain visible to advertise the upgrade) but their links may no longer resolve to working reports. Reactivate Pro afterwards.
- Confirm the page does not show live data — only navigation cards.
- Confirm zero JavaScript console errors throughout.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Total reports counted:
- Notes:
