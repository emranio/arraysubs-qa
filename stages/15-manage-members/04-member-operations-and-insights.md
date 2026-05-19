# Stage 15 — Task 04: Member Operations and Insights

| Key | Value |
|---|---|
| Stage | Manage Members |
| Module | Manage Members — operations + insight metrics |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | 15-02, 15-03 |

## Objective
Verify the operational glue that links Manage Members to the rest of the admin: open a subscription's full detail screen from the member view, manage store credit (add and deduct credit) from the member operations link, exercise the five admin-shortcut integrations across WordPress and WooCommerce, and validate that the six **Member Insight** metrics on the stats grid are arithmetically correct against live data.

## Pre-conditions
- Logged in as Administrator.
- Pro plugin active with **Member Insight** feature enabled.
- Target customer `customer1@arraysubs.test` has the data set up in 15-03 (3 subscriptions, multiple orders, refund history, store credit history).
- WC Analytics is enabled (so the Customers report row is reachable for the analytics shortcut test).

## Test Data
- Manual store credit adjustment: `+$10.00` with note `QA test top-up`.
- Manual store credit deduction: `-$5.00` with note `QA test deduction`.

## Sub-Tasks

### Sub-Task 4.1 — Open subscription detail from member view (both routes)
**Steps:**
1. From the customer's profile, in the Subscriptions table click the ID link `#xxx` for one subscription.
2. Note where it lands; use the browser back button.
3. Now click the **View** action in the Actions column for the same row.
4. Note where this lands.

**Expected Result:**
- Both routes open the same full Subscription Detail page within ArraySubs.
- The detail page reflects the subscription identified by `#xxx`.
- Both routes preserve the customer scope (no error or "subscription not found" condition).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Manage Store Credit — add credit
**Steps:**
1. Return to the customer's profile.
2. Click the **Manage Store Credit** quick link.
3. On the Store Credit page, locate the credit-adjustment form (an "Add Credit" / "Adjust Balance" control).
4. Add `+10.00` with a note `QA test top-up`.
5. Submit.

**Expected Result:**
- A success toast or inline confirmation appears.
- The current balance increases by `$10.00`.
- A new entry appears at the top of the transaction history with amount `+$10.00`, the note, today's date, and the actor (admin name).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Manage Store Credit — deduct credit
**Steps:**
1. From the same Store Credit page, deduct `5.00` with note `QA test deduction`.
2. Submit.

**Expected Result:**
- Balance decreases by `$5.00` (net change from baseline +$5).
- A new transaction history row records the deduction with the note and admin actor.
- The running balance column is consistent.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Member Insight: Total Spent metric verification
**Steps:**
1. Return to the customer's Manage Members profile.
2. Note the **Total Spent** stat card value.
3. Open **WooCommerce → Customers** (or **Analytics → Customers** if available) and find the same customer's lifetime total spent.

**Expected Result:**
- Total Spent on the stats grid equals WooCommerce's reported lifetime customer total.
- If they disagree by less than 1 cent, that's float rounding — note it but pass.
- If they disagree materially, fail and note the discrepancy.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Member Insight: Total Orders, Active Subscriptions, Total Subscriptions
**Steps:**
1. Read the **Total Orders** card.
2. Read the **Active Subscriptions** card.
3. Read the **Total Subscriptions** card.
4. Cross-check with **WooCommerce → Orders** (filtered by customer) and **ArraySubs → Subscriptions** (search by customer).

**Expected Result:**
- Total Orders = number of WooCommerce orders for this customer in any status that WooCommerce counts toward customer totals.
- Active Subscriptions = number of subscriptions in `arraysubs-active` status (Trial subs are NOT counted here).
- Total Subscriptions = total count in any status.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Member Insight: Store Credit and Total Refunds
**Steps:**
1. Read the **Store Credit** card.
2. Read the **Total Refunds** card.
3. Cross-check Store Credit with the dedicated Store Credit page balance.
4. Manually sum the refunded amounts from the customer's WooCommerce orders.

**Expected Result:**
- Store Credit value matches the dedicated page exactly.
- Total Refunds equals the sum of all refunded amounts on the customer's orders (full + partial).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.7 — Admin shortcuts across WP / WC (member-details links)
**Steps:**
1. Open **ArraySubs → Subscriptions**. In the customer column, click the customer's name on a subscription row owned by `customer1@arraysubs.test`.
2. Confirm where it routes.
3. Open a WooCommerce order belonging to this customer (**WooCommerce → Orders → click the order**). Locate the **Open Member Details** button below the order details section.
4. From the WooCommerce orders list, hover the same order row to invoke the quick-view modal. Look for a **Member details** action link.
5. Open the WordPress user edit page (`wp-admin/user-edit.php?user_id={id}`). Look for a **Member Details** button near the page heading.
6. Open **WooCommerce → Analytics → Customers**, find the customer's row. Look for a **Member details** link beneath the username.
7. Click each shortcut and confirm it opens `#/manage-members/{user_id}` for this customer.

**Expected Result:**
- Subscription customer link redirects to the member profile (not the WP user editor).
- Order edit screen shows **Open Member Details** button.
- Order quick-view modal shows **Member details** action.
- WP user edit screen shows **Member Details** button.
- WC Analytics Customers report shows **Member details** link beneath the username.
- Every shortcut opens the same member profile.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.8 — Live recalculation after adding new data
**Steps:**
1. Without leaving the member profile, open another tab and place a new WooCommerce order for this customer (or refund part of an existing order).
2. Reload the Manage Members profile in the original tab.

**Expected Result:**
- Total Orders, Total Spent, and Total Refunds update on next page load (no stale cache).
- This proves the metrics are fetched live per the manual ("There is no caching layer — the values reflect the current database state").

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Deactivate ArraySubs Pro, reload the WooCommerce Orders edit screen — the **Open Member Details** button must disappear (it is registered by the Pro Member Insight feature). Reactivate Pro after the check.
- Confirm the impersonation actions (`arraysubs_login_as_user_started` and `arraysubs_login_as_user_ended`) appear in the Activity Audits log if the Audits feature is enabled (optional Pro check).
- Confirm the customer's Subscriptions count badge in the section header updates if a new subscription is created and the page is reloaded.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Customer used:
- Notes:
