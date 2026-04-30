# Stage 11 — Task 04: Feature Log and Entitlement Review

| Key | Value |
|---|---|
| Stage | 11 — Feature Manager (Pro) |
| Module | Feature Manager — Admin Feature Log |
| Plugin Coverage | Pro |
| Estimated Time | 20 min |
| Depends On | 11.01, 11.02, 11.03 |

## Objective
Open the admin Feature Log from both available entry points (subscription detail screen and Manage Members), confirm entitlement history is visible for the customer, validate that aggregation mode and usage-in-admin settings drive the same display rules as the customer view, and finally confirm capability gating: Administrator and Shop Manager can view the log; a customer cannot reach the same admin URL.

## Pre-conditions
- 11.01–11.03 complete.
- `cust1@test.local` has two active subscriptions (`Pro Plan`, `Enterprise Plan`).
- `Seats` usage on the `Pro Plan` subscription is set to `3 / 5` (from 11.03 Sub-Task 3.5).
- `shopmgr@test.local` exists with the WordPress **Shop Manager** role.

## Test Data
- Customer being reviewed: `cust1@test.local`.
- Subscription IDs from 11.02 / 11.03 (note them in the Sign-off block).
- Expected admin Feature Log URL pattern: `wp-admin/admin.php?page=arraysubs-features&user_id=<cust1_id>` (or the equivalent the menu produces).

## Sub-Tasks

### Sub-Task 4.1 — Reach the Feature Log from the subscription detail
**Steps:**
1. Logged in as `admin@test.local`, go to **ArraySubs → Subscriptions**.
2. Click the `Pro Plan` subscription belonging to `cust1@test.local`.
3. On the subscription detail screen locate an admin tools link or button labelled **Feature Log** (or **View Features**, **Entitlement Review**).
4. Click it.

**Expected Result:**
- The Feature Log page loads with no PHP error.
- The page header shows the customer's name (`cust1`) and email (`cust1@test.local`).
- The URL contains `user_id=` and the value matches `cust1`'s WordPress user ID.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Reach the Feature Log from Manage Members
**Steps:**
1. **WP-Admin → ArraySubs → Manage Members**.
2. Search for `cust1@test.local` and click the row to open the member profile.
3. Locate the **Feature Log** shortcut link in the member profile.
4. Click it.

**Expected Result:**
- The same Feature Log page loads as in 4.1 (same `user_id` query, same content).
- Both entry points reach the same URL.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Verify Per-Subscription view in admin
**Steps:**
1. Confirm **ArraySubs → Settings → Feature Manager → Feature Aggregation Mode** is **Per Subscription**.
2. Confirm **Show Usage Count in Admin** is **On**.
3. Reload the Feature Log for `cust1`.

**Expected Result:**
- Two tables appear, one per subscription.
- Each table header shows the product name and a clickable link to the subscription edit screen.
- Columns: **Feature**, **Type**, **Entitlement**, **Usage**.
- The `Pro Plan` table shows `Seats` with usage `3 / 5`.
- The `Enterprise Plan` table shows the five Enterprise features (entitlement values exactly as defined; usage is `0 / <limit>` or empty for entries that have not been incremented).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Verify Combined view in admin
**Steps:**
1. Set **Feature Aggregation Mode** to **Combine** and save.
2. Reload the Feature Log for `cust1`.

**Expected Result:**
- A single combined table renders.
- Numeric values are summed (`Seats` shows `20`).
- `API Calls` shows `Unlimited`.
- Toggle values are OR'd; text values show the highest-priority subscription's value.
- Restore **Aggregation Mode** to **Per Subscription** before continuing if your team prefers that as the default.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Capability check: Shop Manager can view
**Steps:**
1. Log out of admin and log in as `shopmgr@test.local`.
2. Navigate to **ArraySubs → Subscriptions** (Shop Manager should have access).
3. Open `cust1`'s `Pro Plan` subscription.
4. Click the **Feature Log** link.

**Expected Result:**
- The page loads successfully for the Shop Manager.
- All sections that admin saw in 4.3 are visible.
- No "You do not have permission" error.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Capability check: Customer is blocked
**Steps:**
1. Log out and log in as `cust1@test.local`.
2. Try to open the Feature Log URL captured in 4.1 directly in the address bar (e.g., `<site>/wp-admin/admin.php?page=arraysubs-features&user_id=<cust1_id>`).
3. Observe the response.

**Expected Result:**
- The customer cannot view the admin Feature Log.
- One of these acceptable failure modes occurs:
  - WordPress returns the standard `Sorry, you are not allowed to access this page.` message.
  - The site redirects to `/my-account/` or `/wp-login.php`.
- The page does NOT render entitlement data for any user.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.7 — Cross-customer isolation
**Steps:**
1. Still as `cust1`, open `/my-account/features/`.
2. In the address bar, change any URL parameter that might suggest viewing another user's features (e.g., append `?user_id=<cust2_id>` if such param exists).
3. Confirm the page still only renders `cust1`'s entitlements.

**Expected Result:**
- The customer-facing My Features page only ever shows the logged-in user's own subscriptions, regardless of URL tampering.
- No data from `cust2@test.local` (or any other user) leaks.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After 4.4, restore **Feature Aggregation Mode** to whichever default Stage 11 expects (Per Subscription).
- DevTools console must show zero errors when the admin Feature Log loads under either aggregation mode.
- `wp-content/debug.log` must be free of new errors generated by the capability-blocked direct URL hit in 4.6.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Subscription IDs reviewed:
- Notes:
