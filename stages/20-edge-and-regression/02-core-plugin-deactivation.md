# Stage 20 — Task 02: Core Plugin Deactivation

| Key | Value |
|---|---|
| Stage | 20 — Edge Cases & Final Regression |
| Module | Plugin Lifecycle |
| Plugin Coverage | Both (deactivating Free) |
| Estimated Time | 25 min |
| Depends On | 01 — Pro deactivation |

## Objective
Deactivate ArraySubs (the Free core). Confirm the entire ArraySubs admin menu disappears, customer portal pages return 404 / fallback, and ArraySubsPro is automatically deactivated by WordPress as a dependent plugin (or, if it stays active, that it shows a non-fatal "core required" admin notice). Reactivate ArraySubs and verify all data — subscriptions, settings, customers, store credit, feature entitlements — is intact.

## Pre-conditions
- Task 01 passed; ArraySubs and ArraySubsPro both active and known good.
- At least 5 subscriptions exist across multiple customers in mixed statuses (Active, Trial, On-Hold, Cancelled).
- At least one Pro-only option populated (e.g. a Store Credit balance > 0).
- A note of which customer-portal URLs are ArraySubs-served so we can spot-check them: `/my-account/subscriptions/`, `/my-account/view-subscription/{id}/`, `/my-account/store-credit/` (Pro).

## Test Data
- Subscription IDs to verify post-reactivation: `<record>`.
- Customer `cust3@example.com` Store Credit balance pre-deactivation: `<record>`.

## Sub-Tasks

### Sub-Task 02.1 — Pre-deactivation snapshot
**Steps:**
1. Open **ArraySubs → Subscriptions** and record the count of subscriptions, their statuses, and IDs.
2. Open **ArraySubs → Settings** and screenshot key settings (refund settings, grace period, etc.).
3. Note the customer's Store Credit balance.

**Expected Result:**
- Baseline captured.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.2 — Deactivate ArraySubs core
**Steps:**
1. Go to **Plugins → Installed Plugins**.
2. Click **Deactivate** under **ArraySubs**.

**Expected Result:**
- Plugins page reloads with success notice.
- No fatal error / white screen.
- ArraySubsPro is also automatically deactivated (WordPress' dependency check) — record whether this happens automatically or whether Pro stays active with an admin notice. Either behaviour is acceptable as long as no fatal error occurs.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.3 — Admin menu disappears entirely
**Steps:**
1. Look at the WP admin sidebar.
2. Try direct URLs like `/wp-admin/admin.php?page=arraysubs-subscriptions` and `/wp-admin/admin.php?page=arraysubs-settings`.

**Expected Result:**
- The entire **ArraySubs** menu group is gone from the sidebar.
- Direct URLs return WordPress' "You do not have sufficient permissions to access this page." or redirect to the dashboard — they do **not** render a half-broken ArraySubs page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.4 — Customer portal pages return 404 / fallback
**Steps:**
1. Log in as `cust1@example.com`.
2. Visit `/my-account/subscriptions/`.
3. Visit `/my-account/view-subscription/{id}/` (using one of the recorded IDs).
4. Visit `/my-account/store-credit/`.

**Expected Result:**
- All ArraySubs-served pages either: return a 404, render a clean WooCommerce My Account fallback, or show "page not available" — they do **not** show PHP errors or broken half-pages.
- The standard My Account dashboard, Orders, Addresses, Account Details pages still work normally (those are pure WooCommerce, not ArraySubs).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.5 — Reactivate ArraySubs (and Pro if it deactivated)
**Steps:**
1. Go to **Plugins → Installed Plugins**.
2. Activate **ArraySubs**.
3. If Pro is also showing as inactive, activate **ArraySubs Pro**.

**Expected Result:**
- Both plugins activate without error.
- The full ArraySubs admin menu reappears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.6 — Verify data intact post-reactivation
**Steps:**
1. Open **ArraySubs → Subscriptions** and confirm the count and statuses match the pre-deactivation snapshot exactly.
2. Open **ArraySubs → Settings** and confirm the refund/grace settings match the screenshots.
3. Open `cust3@example.com`'s Store Credit balance — confirm it equals the pre-deactivation value.
4. Open one Active subscription and confirm its next payment date is unchanged.

**Expected Result:**
- All values match the baseline. No data was lost.
- Scheduled actions: any actions scheduled to run during the deactivation window would have been blocked (no PHP available to run them) — they should have rolled forward and remain queued. Verify in **Tools → Scheduled Actions** that no actions are stuck in a Failed state.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.7 — Customer portal restored
**Steps:**
1. As `cust1@example.com`, revisit `/my-account/subscriptions/`.

**Expected Result:**
- The Subscriptions list renders normally again with the same subscriptions and statuses.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm `wp_options` rows for ArraySubs are preserved across deactivation (only on **uninstall** would they be removed).
- Confirm the Action Scheduler queue did not lose any pending actions.
- Verify a deactivation/reactivation cycle does not retrigger the Setup Wizard.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
