# Stage 20 — Task 01: Pro Deactivation While Subscriptions Are Running

| Key | Value |
|---|---|
| Stage | 20 — Edge Cases & Final Regression |
| Module | Plugin Lifecycle |
| Plugin Coverage | Both (deactivating Pro) |
| Estimated Time | 30 min |
| Depends On | Stages 06, 11, 14, 19 |

## Objective
With ArraySubsPro active and real Pro state in flight (a Stripe auto-renewing subscription, a Feature Manager entitlement, a non-zero Store Credit balance), deactivate Pro from `Plugins → Installed Plugins`. Confirm: (a) the Free core plugin keeps working — Subscriptions list, manual cancellation, manual renewal payment all still function; (b) Pro-only admin menu items disappear; (c) Pro-only settings remain stored in the DB but are dormant; (d) the customer portal hides Pro-only actions (auto-renew toggle, store credit page, plan switch if Pro-gated); (e) renewals continue to process via the Free manual gateway path (customer pays the invoice). Reactivate Pro and verify all Pro features restore exactly as before, with no data loss.

## Pre-conditions
- Both ArraySubs (core) and ArraySubsPro active.
- One subscription on Stripe auto-renew with a successful first payment (`cust1@example.com`).
- One subscription with a Feature Manager entitlement assigned (e.g. a "VIP" feature flag) (`cust2@example.com`).
- Customer `cust3@example.com` has a Store Credit balance of `$25.00`.
- Snapshot the database options table — record the Pro option keys that exist (e.g. `arraysubspro_*`).

## Test Data
- Stripe subscription's next payment date: `<record>`.
- Feature entitlement key: `<record>`.
- Pre-deactivation Pro menu items list: `<record>`.

## Sub-Tasks

### Sub-Task 01.1 — Capture pre-deactivation baseline
**Steps:**
1. Take screenshots of the WP admin sidebar with the full ArraySubs menu expanded.
2. Take a screenshot of `cust1@example.com`'s subscription detail in the customer portal showing the Auto-Renew toggle and any other Pro-only buttons.
3. Note the exact list of Pro-related options in `wp_options` (or use a DB tool to dump rows where `option_name LIKE 'arraysubspro%'`).

**Expected Result:**
- Baseline captured for direct before/after comparison.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.2 — Deactivate ArraySubsPro
**Steps:**
1. Go to **Plugins → Installed Plugins**.
2. Click **Deactivate** under **ArraySubs Pro**.

**Expected Result:**
- Plugins page reloads with success notice "Plugin deactivated."
- No fatal error / white screen on any admin page after the deactivation.
- Dashboard, Posts, WooCommerce → Orders, ArraySubs → Subscriptions all open normally.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.3 — Pro-only admin menus disappear
**Steps:**
1. Look at the WP admin sidebar.
2. Compare against the screenshot from 01.1.

**Expected Result:**
- Pro-only menu items (e.g. **Store Credit**, **Feature Manager**, **Analytics** if Pro-gated, **Scheduled-Job Logs** if Pro-gated) are gone.
- Free menu items (e.g. **Subscriptions**, **Settings**, **Retention Flow**, **Member Access**) are still present.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.4 — Pro-only settings persist in DB but are dormant
**Steps:**
1. Run the same DB query / inspect `wp_options` for the `arraysubspro_*` keys.

**Expected Result:**
- All Pro option rows still exist with their values intact.
- They are simply not being read because Pro's PHP is not loaded.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.5 — Customer portal hides Pro-only actions
**Steps:**
1. Log in as `cust1@example.com`.
2. Visit `/my-account/subscriptions/` and click into the Stripe subscription.
3. Try to access `/my-account/store-credit/` directly (or wherever Pro's customer page lives).

**Expected Result:**
- The Auto-Renew toggle is no longer present (or is disabled with a tooltip saying not available).
- Plan Switch / Pause / Skip buttons that are Pro-gated are hidden, but Free actions (Cancel) remain.
- Direct navigation to `/my-account/store-credit/` returns a 404 / "page not found" / WooCommerce account fallback — record exact behaviour.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.6 — Subscriptions still process renewals via the manual gateway path
**Steps:**
1. As admin, find a subscription whose next payment is due soon (or shift its date to be due within the hour).
2. Run **Tools → Scheduled Actions → Generate Upcoming Renewals** manually.
3. Reload the subscription's parent order list.
4. The customer should receive a Renewal Invoice email with a Pay-Now link.
5. Click the Pay-Now link from the customer's mailbox; complete payment (use a non-Stripe gateway like Cheque if Stripe was Pro-gated, or use Stripe's manual checkout flow if available without Pro).

**Expected Result:**
- A pending renewal order is created.
- The Renewal Invoice email is sent.
- Manual payment of that invoice succeeds, the subscription advances to the next cycle, and a Payment Successful email is sent.
- This proves Free's manual renewal path still works without Pro.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.7 — Reactivate Pro and verify state restored
**Steps:**
1. Go to **Plugins → Installed Plugins**.
2. Click **Activate** under **ArraySubs Pro**.
3. Reload the WP admin sidebar; navigate through Pro pages.
4. Visit `cust3@example.com` and confirm the Store Credit balance still shows `$25.00`.
5. Visit the Stripe-paying subscription and confirm the Auto-Renew toggle is back.
6. Verify the previous Feature Manager entitlement is still assigned.

**Expected Result:**
- All Pro menu items are back.
- All Pro state (store credit balance, feature entitlements, auto-renew status) is exactly what it was before deactivation — nothing was lost.
- No "first run" wizard re-appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm activation/deactivation events were logged in `wp-content/debug.log` (if `WP_DEBUG_LOG` is on) without any PHP fatal/notice output.
- Confirm no orphaned scheduled actions were created or destroyed by activation/deactivation — counts before and after should be the same after Pro restarts.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
