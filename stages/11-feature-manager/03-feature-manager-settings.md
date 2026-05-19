# Stage 11 — Task 03: Feature Manager Settings

| Key | Value |
|---|---|
| Stage | 11 — Feature Manager (Pro) |
| Module | Feature Manager — Settings |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | 11.01, 11.02 |

## Objective
Exercise every Feature Manager setting that has a visible effect: aggregation mode (Per Subscription vs Combine), customer-side and admin-side usage column toggles, custom My Account page title, and module master switch. Validate the differences for a customer holding two active subscriptions, and prove that manually bumping a number-feature's usage counter is reflected in the customer's My Features page.

## Pre-conditions
- 11.01 and 11.02 complete.
- `cust1@test.local` already has one active subscription to `Pro Plan` (from 11.02).
- A second simple subscription product is available — use the Stage 03 `Enterprise Plan` ($39.99/week) and define the features below on it if not already present.
- Logged-in admin browser session and a separate `cust1@test.local` browser session.

## Test Data
- Second product: `Enterprise Plan` ($39.99 / week, simple subscription) with the following features:
  - `Seats` — Number — `15` — Enabled
  - `Storage (GB)` — Number — `100` — Enabled
  - `API Calls` — Number — `Unlimited` — Enabled
  - `Priority Support` — Toggle — `Yes` — Enabled
  - `Plan Tier` — Text — `Enterprise` — Enabled
- Customer already on `Pro Plan` (Seats 5, Storage 10, API 10000, Priority Support Yes, Custom Domain No, Plan Tier Starter).
- Manual usage bump value for `Seats` on the `Pro Plan` subscription: `3` (out of `5`).

## Sub-Tasks

### Sub-Task 3.1 — Add a second subscription for cust1
**Steps:**
1. As admin, create or confirm `Enterprise Plan` exists (with the features in Test Data).
2. Switch to the `cust1@test.local` browser session.
3. Add `Enterprise Plan` to cart and complete checkout via Stripe test card `4242 4242 4242 4242` (or BACS / COD as a manual fallback).
4. For a manual gateway, set the resulting order to **Processing** or **Completed** so the subscription activates.
5. Confirm in **ArraySubs → Subscriptions** that `cust1@test.local` now has two **Active** subscriptions: `Pro Plan` and `Enterprise Plan`.

**Expected Result:**
- Two active subscriptions exist for `cust1@test.local`.
- No PHP notice or admin-side error during the second checkout.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Verify Per-Subscription aggregation
**Steps:**
1. As admin, **ArraySubs → Settings → Feature Manager → Feature Aggregation Mode** = **Per Subscription**.
2. Click **Save Settings**.
3. As `cust1`, hard-refresh `/my-account/features/`.

**Expected Result:**
- Two distinct sections appear, one per subscription, each with its own product-name header and `Subscription #<ID>` link.
- The `Pro Plan` table contains six rows; the `Enterprise Plan` table contains five rows.
- A feature shared by both subscriptions (e.g., `Seats`) shows independently in each table — `5` in the Pro Plan table, `15` in the Enterprise Plan table.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Switch to Combined aggregation
**Steps:**
1. As admin, change **Feature Aggregation Mode** to **Combine** and save.
2. As `cust1`, hard-refresh `/my-account/features/`.

**Expected Result:**
- A single combined table appears under one heading (e.g., `Combined Features`).
- Number features are summed:
  - `Seats` shows `20` (5 + 15).
  - `Storage (GB)` shows `110` (10 + 100).
  - `API Calls` shows `Unlimited` (any unlimited input forces unlimited; or `10000 + Unlimited` resolves to `Unlimited`).
- Toggle features are OR'd: `Priority Support` shows `Yes` (✓). `Custom Domain` shows `No` (✗) — only Pro Plan has it.
- Text features show the highest-priority value — `Plan Tier` displays one value (either `Starter` or `Enterprise`); document which one is rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Toggle "Show Usage Count in My Account"
**Steps:**
1. As admin, set **Show Usage Count in My Account** to **Off** and save.
2. As `cust1`, refresh `/my-account/features/`.
3. Re-enable **Show Usage Count in My Account** and save.
4. Refresh `/my-account/features/` again.

**Expected Result:**
- With the toggle Off, the **Usage** column is hidden — number rows display only the entitlement value.
- With the toggle On, the **Usage** column reappears for number features.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Manually bump usage for a feature
**Steps:**
1. As admin, switch back to **Feature Aggregation Mode** = **Per Subscription** and save.
2. Identify the subscription ID for `cust1`'s `Pro Plan` (e.g., `#101`).
3. Bump the `Seats` usage to 3 using ONE of these methods:
   - **Method A (REST tool, preferred):** open **WP-Admin → Tools → Site Health → Info** is irrelevant — instead use **WP-Admin → ArraySubs → Tools / REST Console** if available. Or, use the WP-CLI from server shell: `wp eval 'arraysubs_update_feature_usage(101, "seats", 3);'` (replace `101` with the actual subscription ID and adjust the feature key if different).
   - **Method B (Custom Fields):** if usage is exposed via post meta on the subscription CPT, edit the meta key for `Seats` usage to `3` from the subscription detail screen → **Custom Fields** panel.
4. Save / run the update.
5. Refresh `/my-account/features/` as `cust1`.

**Expected Result:**
- The `Seats` row in the `Pro Plan` table shows `3 / 5` in the Usage column.
- Other rows are unaffected.
- No PHP error or REST 500 response.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Toggle "Show Usage Count in Admin"
**Steps:**
1. As admin, set **Show Usage Count in Admin** to **Off** and save.
2. Open the admin Feature Log: **ArraySubs → Subscriptions → [Pro Plan subscription for cust1]** → click the **Feature Log** link (or visit `wp-admin/?page=arraysubs-features&user_id=<cust1_id>`).
3. Note whether a Usage column is visible.
4. Re-enable **Show Usage Count in Admin** and save.
5. Reload the Feature Log page.

**Expected Result:**
- With the toggle Off, no Usage column is shown in the admin Feature Log.
- With the toggle On, the Usage column returns and shows `3 / 5` for `Seats` (matching 3.5).
- The customer-side display from 3.4 is independent and unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.7 — Master switch off preserves data
**Steps:**
1. As admin, set **Enable Feature Manager** to **Off** and save.
2. As `cust1`, reload `/my-account/features/`.
3. Open the `Pro Plan` storefront page.
4. Re-enable **Enable Feature Manager** and save.
5. Re-check both pages.

**Expected Result:**
- With the master switch Off, the **My Features** menu item disappears from My Account, and `/my-account/features/` returns a 404 / WooCommerce default endpoint behaviour.
- The storefront **What's Included** section disappears as well.
- After re-enabling, both areas come back with the same data — no feature is lost.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm settings page shows zero JavaScript console errors as toggles are saved.
- Verify `wp-content/debug.log` has no warnings during the aggregation switch or usage update.
- Re-verify that `cust2@test.local` (no subscription) still sees the empty-state message on `/my-account/features/` ("You don't have any features yet…") with whichever mode is currently configured.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Time-travel / usage-update method used (A or B):
- Notes:
