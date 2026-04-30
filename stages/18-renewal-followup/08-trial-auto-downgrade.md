# Stage 18 — Task 08: Trial Auto-Downgrade

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Trial Management (auto-downgrade path) |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | Task 18.07 (normal trial conversion verified — auto-downgrade is the alternate path) |

## Objective
Time-travel a Trial subscription whose product has an **auto-downgrade target** configured (so `auto_downgrade_timing` includes `on_trial_expire`) past its trial-end date. Run **Process Trial Conversions**. Verify the subscription is **switched to the downgrade product** (instead of converting normally), the **Auto Downgrade** email is sent, and that **no Trial Converted email is sent** for this subscription.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active.
- Customer `member-trial-down@example.com` has a **Trial** subscription on **Trial Weekly w/ Downgrade** ($19.99/wk, 7-day free trial). The product's Linked Products tab has:
  - **Auto-downgrade target** = a free version product (use **Basic Plan** at $9.99/wk if no $0 product is available — or any explicitly free product configured for this purpose)
  - **Auto-downgrade timing** includes `on_trial_expire`
- The auto-downgrade target product exists and is published.
- Auto Downgrade email = Enabled.
- Trial Converted email = Enabled (we want to confirm it does NOT fire for this subscription).
- Time-travel approach from Task 18.01 ready.

## Test Data
- Test subscription ID: ____
- Original product (trial source): Trial Weekly w/ Downgrade (ID: ____)
- Downgrade target product: Basic Plan (or other free target) (ID: ____)
- Trial length: 7 days
- Billing period: weekly (every 1 week)
- Original `_trial_end_date`: ____
- Original `_next_payment_date` (= trial end): ____

## Sub-Tasks

### Sub-Task 08.1 — Capture starting state and verify product configuration
**Steps:**
1. Open the test subscription's admin detail page.
2. Confirm:
   - Status = **Trial**
   - Product = Trial Weekly w/ Downgrade (record product ID)
   - `_trial_end_date` and `_next_payment_date` set
3. Open the source product's edit screen.
4. On the **Linked Products** tab, confirm:
   - Auto-downgrade target = Basic Plan (or whichever free target is configured)
   - Auto-downgrade timing includes `on_trial_expire`

**Expected Result:**
- All baseline values captured. Product configuration confirmed.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.2 — Time-travel the trial end past
**Steps:**
1. Apply Task 18.01 procedure: edit BOTH `_trial_end_date` AND `_next_payment_date` to **1 hour ago**. Save.

**Expected Result:**
- Trial end date now in the past.
- Status still Trial.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.3 — Trigger Process Trial Conversions
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Run the next pending `arraysubs_process_trial_conversions` action.
3. Wait 10 seconds.
4. Refresh.

**Expected Result:**
- Batch hook completed successfully.
- Internally, the system delegated this subscription's conversion to the auto-downgrade handler instead of the normal converter.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.4 — Verify the subscription was switched to the downgrade product
**Steps:**
1. Refresh the test subscription's admin detail page.
2. Read:
   - Status (expected: **Active**)
   - Subscription product (expected: now points to the configured downgrade target — e.g. **Basic Plan**, not Trial Weekly w/ Downgrade)
   - Recurring price (expected: matches the downgrade target's price — e.g. $9.99 for Basic Plan, or $0 if a free target is configured)
   - Trial length meta (expected: cleared)
   - `_next_payment_date` (expected: one billing cycle from the switch timestamp; for a weekly downgrade target this is ~1 week from now)

**Expected Result:**
- The subscription's product reference was changed to the downgrade target.
- The recurring price reflects the downgrade target's price.
- The subscription is no longer in Trial status.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.5 — Verify the Auto Downgrade email (and absence of Trial Converted)
**Steps:**
1. Open the mail catcher.
2. Search for emails to `member-trial-down@example.com` with timestamp matching the conversion run.
3. Confirm an **Auto Downgrade** email is present with the body explaining the switch from Trial Weekly w/ Downgrade to the configured downgrade target (e.g. Basic Plan).
4. Confirm there is NO **Trial Converted** email sent for this subscription.

**Expected Result:**
- Auto Downgrade email exists.
- Trial Converted email is absent — the converter explicitly suppresses it when auto-downgrade handles the trial end.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.6 — Verify Activity Audits and Scheduled-Job Logs
**Steps:**
1. Open Activity Audits. Filter Author = System and Entity = Subscription.
2. Find the most recent row for this subscription.
3. Click `changes →` and inspect.
4. Open Scheduled-Job Logs.

**Expected Result:**
- Audit row shows the product change (e.g., `_product_id: <old_id> → <downgrade_target_id>`) and Status: Trial → Active, Trial Length: 7 → 0.
- Scheduled-Job Logs records "Batch Trial Conversions" with Success.
- Email entity audit records the Auto Downgrade send.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.7 — Verify customer portal reflects the downgrade
**Steps:**
1. Open incognito. Log in as `member-trial-down@example.com`.
2. Go to **My Account → Subscriptions**.
3. Inspect the (now-Active) subscription.

**Expected Result:**
- The subscription is shown with the downgrade target as the product (e.g. Basic Plan).
- Recurring price reflects the target's price (e.g. $9.99 / week for Basic Plan).
- Status = Active.
- The customer portal shows the new next payment date (~1 week out).
- If plan switching is enabled, the customer can upgrade back to a paid weekly plan from this view.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Activity Audits — confirm Author = System rows for both the trial conversion and the product switch.
- Confirm subscription notes describe the auto-downgrade clearly (e.g., "Auto-downgrade triggered on trial expiration; switched product from Trial Weekly w/ Downgrade to Basic Plan").
- Confirm no Trial Converted email AND no Renewal Invoice email were sent during this task — only Auto Downgrade.
- Confirm the original Trial Weekly w/ Downgrade subscription does NOT remain in any active state (it was replaced, not duplicated).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- Original / new product IDs: ____ → ____
- Downgrade timestamp:
- Notes:
