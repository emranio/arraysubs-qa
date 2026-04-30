# Stage 16 — Task 05: Order List Enhancements

| Key | Value |
|---|---|
| Stage | Analytics & Reports |
| Module | WooCommerce → Orders (Pro additions) |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | 16-04 (orders are typed) |

## Objective
Verify the Pro additions to **WooCommerce → Orders**: a **Type** column with colour-coded badges, a **Coupons** column, three filter dropdowns (Type, Coupon, Subscription Products Only), the embedded summary panel above the table, and the historical **Compute Order Types** backfill admin notice that classifies pre-Pro orders.

## Pre-conditions
- Logged in as Administrator.
- ArraySubs Pro active.
- Earlier stages produced orders of every type (same data as task 16-04).
- At least one order has a coupon applied (e.g. `SAVE20`) so the Coupons column has visible data.
- If your test site has historical orders predating Pro activation, the backfill admin notice should be displayable; if not, you can simulate by deleting the `_arraysubs_computed_type` meta on a couple of orders via SQL or the database tool.

## Test Data
- Test order with coupon: an Initial subscription order using `SAVE20`.
- Subscription product: `Standard Weekly` ($19.99/wk) — or any other product from the canonical catalog. PayPal and Paddle are out of scope; only **Stripe** and **manual** payment gateways are exercised in this regression cycle.

## Sub-Tasks

### Sub-Task 5.1 — Open WC → Orders and verify Type and Coupons columns
**Steps:**
1. Navigate to **WooCommerce → Orders** (legacy or HPOS list, whichever is active on the test site).
2. Inspect the column headers.

**Expected Result:**
- A **Type** column is present with colour-coded badges per the manual table:
  - Subs Purchase, Subs Trial, Subs Renew, Subs Upgrade, Credit Purchase, Other.
- A **Coupon(s)** column is present. Orders without coupons show an empty cell; orders with coupons show comma-separated codes (e.g. `SAVE20`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.2 — Type filter dropdown
**Steps:**
1. Above the table, locate the **Type** filter dropdown.
2. Cycle through every option: All Types, Subs Renew, Subs Purchase, Subs Upgrade, Credit Purchase, Subs Trial, Other.

**Expected Result:**
- Each option filters the table to orders matching the chosen type only.
- Selecting **All Types** restores the full list (subject to the active status tab and date range).
- The visible row count and the summary panel update with each selection.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.3 — Coupon filter dropdown
**Steps:**
1. Locate the **Coupon** filter dropdown.
2. Open the dropdown — confirm only coupons that have been used on at least one order appear.
3. Select `SAVE20`.

**Expected Result:**
- Dropdown is populated dynamically from used coupons (cached server-side).
- Selecting `SAVE20` filters the list to only orders that used that coupon.
- The summary panel's totals update.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.4 — Subscription Products Only filter
**Steps:**
1. Reset Type and Coupon filters.
2. Locate the **Subscription Products Only** checkbox / toggle.
3. Enable it.

**Expected Result:**
- The table shows only orders containing at least one subscription product.
- Other-type orders that contain no subscription product are hidden.
- This filter combines correctly with the other two filters (AND logic).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.5 — Combined filters
**Steps:**
1. Set Type = `Subs Renew` AND Coupon = `SAVE20`.
2. Apply.

**Expected Result:**
- The table only shows renewal orders that used `SAVE20`.
- The summary panel reflects this filter combination.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.6 — Embedded summary panel
**Steps:**
1. Reset all filters (so the summary represents the entire current view).
2. Locate the summary panel above the orders table.

**Expected Result:**
- Panel shows: **Total Orders**, per-type counts (Subs Purchase, Subs Renew, Subs Trial, Subs Upgrade, Credit Purchase, Other), and **Orders with Coupon**.
- Counts add up: sum of per-type counts equals Total Orders.
- Apply a Type filter — the summary panel recalculates and reflects the filtered subset.
- Switch to a different status tab (e.g. **Processing** instead of All) — the summary panel reflects only that status.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.7 — Backfill admin notice (Compute Order Types)
**Steps:**
1. Identify whether the **Compute Order Types** admin notice is currently shown at the top of the WC → Orders page.
2. If it is shown, click the **Compute Order Types** button.
3. Watch the progress indicator (batches of 200).
4. Wait for completion message.
5. Reload the Orders page.

**Expected Result:**
- Notice appears only when unclassified orders exist.
- Clicking the button starts a batched job; progress text updates.
- A completion message confirms the total number of orders processed.
- After completion, all order rows have a Type badge (no blank cells).
- The admin notice no longer appears on reload.
- If no unclassified orders exist on your test site, simulate by clearing `_arraysubs_computed_type` meta on a couple of orders, then reload — the notice must reappear.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.8 — Backfill is non-destructive (verify)
**Steps:**
1. Note the order total, status, and customer for a sample order both before and after the backfill.

**Expected Result:**
- The backfill only adds `_arraysubs_computed_type` and `_arraysubs_has_subscription_product` meta.
- No order's status, total, or any other operational data is modified.
- The Type column now reflects the computed classification.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm filters and columns work in both **HPOS** mode and **legacy post-type** mode (toggle in WC → Settings → Advanced → Features for a regression run on a separate test environment).
- Trigger a renewal order creation manually (or wait for the scheduler) and confirm the new order is automatically classified as **Subs Renew** without running the backfill again.
- Confirm zero JavaScript console errors throughout filter interactions.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order storage mode (HPOS / legacy):
- Backfill exercised: [ ] yes [ ] no — N/A because no unclassified orders
- Notes:
