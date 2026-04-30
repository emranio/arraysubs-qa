# Stage 16 — Task 04: WooCommerce Analytics Extension *(Pro)*

| Key | Value |
|---|---|
| Stage | Analytics & Reports |
| Module | WC → Analytics → Orders / Revenue / Products / Variations / Customers (Pro additions) |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | 16-02; subscriptions and orders from earlier stages classified by Order Type |

## Objective
Verify the Pro additions to five native WooCommerce Analytics reports: **Orders** (Type column + quick filter + advanced multi-select Type Is / Type Is Not filter), **Revenue** (three subscription revenue cards), **Products** (Subscription Only filter), **Variations** (Subscription Only filter), **Customers** (Member details quick link).

## Pre-conditions
- Logged in as Administrator.
- ArraySubs Pro active.
- Earlier stages produced orders of every type:
  - Subs Purchase (initial subscription order)
  - Subs Trial (initial with trial)
  - Subs Renew (at least one renewal order)
  - Subs Upgrade (at least one plan switch order)
  - Credit Purchase (at least one Store Credit purchase order)
  - Other (at least one regular WooCommerce order)
- WC Admin caches cleared (Ctrl+Shift+R if columns or filters fail to appear).

## Test Data
- Date range: select a window covering all the orders above.
- Member to test: `customer1@arraysubs.test`.

## Sub-Tasks

### Sub-Task 4.1 — Orders report — Type column visible
**Steps:**
1. Navigate to **WooCommerce → Analytics → Orders**.
2. Locate the **Type** column in the orders table (should appear after the Status column).

**Expected Result:**
- Type column is visible.
- Each order row shows one of: **Subs Purchase**, **Subs Trial**, **Subs Renew**, **Subs Upgrade**, **Credit Purchase**, **Other**.
- No order shows a blank type cell. (If any do, run the backfill from task 16-05.)

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Quick Type filter (single-select)
**Steps:**
1. In the filter bar above the orders table, locate the **Type** dropdown.
2. Open the dropdown.
3. Select **Subs Renew**.

**Expected Result:**
- Dropdown options exist for: All Types, Subs Renew, Subs Purchase, Subs Upgrade, Credit Purchase, Subs Trial, Other.
- After selecting Subs Renew, the table only shows orders with Type = Subs Renew.
- Standard WC totals (orders count, net revenue, average order value) recalculate to reflect only the filtered set.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Advanced multi-select Type Is filter
**Steps:**
1. Reset the quick filter to **All Types**.
2. Open the advanced filters panel.
3. Locate the multi-select **Type** filter with a **Type Is** rule.
4. Select **Subs Renew** AND **Subs Upgrade**.
5. Apply.

**Expected Result:**
- The table now shows orders whose type is Subs Renew OR Subs Upgrade.
- A warning label appears in the filter bar reminding you that results are filtered.
- Standard WC totals recalculate.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Advanced multi-select Type Is Not filter
**Steps:**
1. Switch the rule from **Type Is** to **Type Is Not**.
2. Select **Other**.
3. Apply.

**Expected Result:**
- The table now shows everything EXCEPT Other-typed orders.
- All Subs-* and Credit Purchase rows remain.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Revenue report — three subscription cards
**Steps:**
1. Navigate to **WooCommerce → Analytics → Revenue**.
2. Locate the summary cards row.

**Expected Result:**
- Three additional cards appear alongside the standard WC revenue cards: **Total Subs Renew Amount**, **Total Subs Upgrade Amount**, **Total Credit Purchase Amount**.
- Each card shows the period total in store currency.
- Numerical values reconcile with the sum of order totals filtered by the corresponding type.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Revenue card click adds metric column to the table
**Steps:**
1. Click the **Total Subs Renew Amount** card.

**Expected Result:**
- The report table updates to show the corresponding metric column for each interval in the selected period.
- Trend over time is now visible inline with the other metrics.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.7 — Products & Variations: Subscription Only filter
**Steps:**
1. Navigate to **WooCommerce → Analytics → Products**.
2. Locate the **Product Type** dropdown in the filters bar.
3. Switch from **All Products** to **Subscription Only**.
4. Repeat the same flow under **WooCommerce → Analytics → Variations**.

**Expected Result:**
- Products report filtered to Subscription Only shows only products flagged with `_is_subscription` meta.
- Variations report filtered to Subscription Only shows only variations belonging to subscription products.
- Non-subscription products / variations disappear from each report.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.8 — Customers report — Member details link
**Steps:**
1. Navigate to **WooCommerce → Analytics → Customers**.
2. Locate `customer1@arraysubs.test`'s row.
3. Note the **Member details** link beneath the username.
4. Click the link.

**Expected Result:**
- The link is rendered for every customer row, beneath the username.
- Clicking navigates to **ArraySubs → Manage Members** at `#/manage-members/{user_id}` for that customer.
- The member profile loads correctly.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Combine a Type filter with a date range and a status filter — confirm AND logic applies and the standard WC totals match the visible row sum.
- Confirm "Subs Trial" filter returns trial orders only (orders with a trial period applied at checkout).
- Reload each report after clearing the browser cache. Confirm columns and filters appear without re-clearing.
- Confirm zero JavaScript console errors and that all REST analytics endpoints return 2xx.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Date range used:
- Notes:
