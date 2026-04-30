# Stage 02 — Task 01: General Settings — Each Section

| Key | Value |
|---|---|
| Stage | General & Toolkit Settings |
| Module | General Settings |
| Plugin Coverage | Both (Auto-Renew section requires Pro) |
| Estimated Time | 45 min |
| Depends On | 01-setup-wizard/05-apply-and-verify-settings.md |

## Objective
Exercise every section of **ArraySubs → Settings → General**: Multiple Subscriptions, Button Text, Checkout & Trials, Grace Period, Email Reminder Schedule, Customer Actions, Cancellation Settings, and Automatic Payments (Pro). Toggle each control, click **Save Settings**, refresh the page, and confirm the value persisted. The values set here become the baseline for later stages, so leave the page in a deterministic state at the end.

## Pre-conditions
- Stages 00, 01 complete.
- ArraySubsPro is active.
- Logged in as Administrator.
- An export `pre-stage-02.json` is downloaded as a safety net.

## Test Data
- Page path: **ArraySubs → Settings → General**.
- Save action: **Save Settings**. Revert action: **Discard Changes**.
- Reference: see the `Settings Reference` table at the bottom of `general-settings.md` for default values and types.

## Sub-Tasks

### Sub-Task 1.1 — Multiple Subscriptions section
**Steps:**
1. Open **ArraySubs → Settings → General**.
2. Locate the **Multiple Subscriptions** section.
3. Toggle **Allow Multiple Subscriptions in Cart** to **Off**.
4. Toggle **One Subscription Per Customer** to **On** — confirm the **Auto Migrate to New Subscription Upon Checkout** sub-toggle now appears. Set Auto Migrate to **On**.
5. Toggle **One Subscription Per Product** to **On**.
6. Toggle **Allow Mixed Checkout** to **Off**.
7. Toggle **Allow Different Billing Cycles** to **Off**.
8. Click **Save Settings**.
9. Hard-refresh the page.

**Expected Result:**
- A success notice appears after **Save Settings** (e.g., "Settings saved").
- After reload, every value above is still set as configured.
- **Auto Migrate** is only visible while **One Subscription Per Customer** is on; turning the parent off hides the sub-toggle.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Button Text section
**Steps:**
1. In the **Button Text** section, set **Add to Cart Text** to `Subscribe Today`.
2. Click **Save Settings**.
3. Open a public subscription product page in incognito (use any test product, or skip if no subscription product exists yet — note the dependency).
4. Return to settings and clear the field, then save again.

**Expected Result:**
- The custom text persists after reload.
- On the public product page, the add-to-cart button reads `Subscribe Today`.
- Clearing the field reverts the button to the default ("Subscribe Now") on the next page reload.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Checkout & Trials section
**Steps:**
1. In **Checkout & Trials**, toggle **Auto Create Account** to **Off**, save, reload, then toggle back to **On** and save.
2. Set **One Click Checkout** to **Enabled for subscription items** — confirm **Disable Cart Page** appears as a sub-toggle. Set Disable Cart Page to **On**.
3. Set **One Click Checkout** to **Enabled for all items** — confirm **Non-Subscription Purchase Button Text** appears. Enter `Buy Now`.
4. Set **Require Payment Method for Trials** to **Off**, then back to **On**.
5. Set **One Trial Per Customer** to **Off**, then back to **On**.
6. Click **Save Settings** and hard-refresh.

**Expected Result:**
- All toggles and fields persist after reload.
- **Disable Cart Page** is hidden when One Click Checkout is **Default** and visible otherwise.
- **Non-Subscription Purchase Button Text** is hidden unless One Click Checkout is **Enabled for all items**.
- The dropdown options for One Click Checkout are exactly: **Default**, **Enabled for subscription items**, **Enabled for all items**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Grace Period section
**Steps:**
1. Set **Generate Invoice Before Due** to `2` and unit to **Days**.
2. Set **Days Active After Due** to `5`.
3. Set **Days On-Hold Before Cancel** to `14`.
4. Click **Save Settings**, hard-refresh, and re-read each field.
5. Now set Generate Invoice Before Due back to `6` Hours, Days Active to `3`, and Days On-Hold to `7` (the documented defaults).

**Expected Result:**
- Each value persists across reload.
- The unit dropdown for **Generate Invoice Before Due** offers exactly **Hours** and **Days**.
- The Days Active After Due field accepts 0 through 30.
- The Days On-Hold Before Cancel field accepts 1 through 60.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Email Reminder Schedule section
**Steps:**
1. Set **Renewal Reminder (Days Before)** to `5`.
2. Set **Trial Ending Reminder (Days Before)** to `2`.
3. Set **Expiring Soon Reminder (Days Before)** to `14`.
4. Click **Save Settings** and reload.
5. Restore defaults afterwards: 3, 3, 7.

**Expected Result:**
- Each number field persists.
- Renewal and Trial Ending fields accept 1 through 30.
- Expiring Soon accepts 1 through 60.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Customer Actions section
**Steps:**
1. Toggle **Allow Cancellation** to **Off**, save, reload.
2. Toggle **Allow Suspension (Pause)** to **On**, save, reload.
3. Toggle **Allow Reactivation** to **Off**, save, reload.
4. Restore the documented defaults: Allow Cancellation **On**, Allow Suspension **Off**, Allow Reactivation **On**.

**Expected Result:**
- Each toggle persists across reload.
- The info-box pointing to **ArraySubs → Profile Builder → My Account** for renaming the Subscriptions tab is visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Cancellation Settings section
**Steps:**
1. Toggle **Cancel Immediately** to **Off**, save, reload.
2. Toggle back to **On**, save, reload.

**Expected Result:**
- The toggle persists across reload.
- The info-box describing end-of-period (scheduled) cancellation is visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.8 — Automatic Payments (Pro) section
**Steps:**
1. Confirm the **Automatic Payments** section is visible (because ArraySubsPro is active).
2. Toggle **Allow Customers to Turn Off Auto-Renew** to **On**, save, reload.
3. Toggle back to **Off**, save, reload.
4. Deactivate ArraySubsPro from the **Plugins** screen, return to the General Settings page, and confirm the entire **Automatic Payments** section disappears.
5. Re-activate Pro.

**Expected Result:**
- The toggle persists when Pro is active.
- When Pro is deactivated, the section is no longer rendered on the page.
- After re-activating Pro, the section returns and any previously saved value is preserved.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.9 — Discard Changes button
**Steps:**
1. Toggle **Allow Cancellation** to **Off** but do **not** save.
2. Click **Discard Changes**.
3. Observe whether the toggle reverts to its previously-saved state.

**Expected Result:**
- The toggle returns to its last-saved state without a page reload.
- No success notice appears (because nothing was saved).
- After a hard refresh, the value is unchanged from before the unsaved edit.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Open DevTools Network tab while clicking **Save Settings**; confirm the request returns HTTP 200 with a success body.
- Watch DevTools Console for JS errors when toggling conditional sub-fields (Auto Migrate, Disable Cart Page, Non-Subscription Purchase Button Text).
- Tail `wp-content/debug.log` while saving — no PHP notice should reference `arraysubs/admin/settings`.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Final state of toggles (record any deviation from defaults):
- Notes:
