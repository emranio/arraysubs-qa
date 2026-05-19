# Stage 01 — Task 04: Pro vs Free Options

| Key | Value |
|---|---|
| Stage | Easy Setup Wizard |
| Module | Setup Wizard — Pro Gating |
| Plugin Coverage | Both |
| Estimated Time | 20 min |
| Depends On | 01-setup-wizard/01-launch-and-navigation.md |

## Objective
Confirm Pro-only options inside the wizard (Store Credit, Feature Manager, Audit Logging, Multi-Login Prevention) are completely hidden when ArraySubsPro is inactive and that the documented "ArraySubs Pro is not active" notice appears at the top of the wizard. Then re-activate Pro and confirm those options reappear and their follow-up questions work.

## Pre-conditions
- Stage 01 Tasks 01–03 passed.
- ArraySubs core is active.
- ArraySubsPro is currently active and known to work.
- Logged in as Administrator.

## Test Data
- Notice text (from manual): "ArraySubs Pro is not active, so Pro-only wizard options are hidden for now."
- Pro-only features expected on Step 8: **Store Credit System**, **Feature Manager**, **Activity Audit Log**, **Multi-Login Prevention**.
- Free-only features expected on Step 8: **Custom Profile Fields**, **My Account Page Editor**, **Hide Admin Bar for Customers**, **Restrict WP Dashboard Access**.

## Sub-Tasks

### Sub-Task 4.1 — Deactivate ArraySubsPro
**Steps:**
1. Go to **Plugins → Installed Plugins**.
2. Click **Deactivate** under **ArraySubsPro**.
3. Confirm the row now shows the **Activate** action instead of **Deactivate**.
4. Reload the dashboard once to clear any cached menu state.

**Expected Result:**
- ArraySubsPro is deactivated cleanly.
- No "core dependency" or "fatal error" notice is shown.
- The ArraySubs core menu is still present.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Confirm the "Pro is not active" notice in the wizard
**Steps:**
1. Go to **ArraySubs → Easy Setup**.
2. Click **Launch Setup Wizard**.
3. Read the top of the wizard modal.

**Expected Result:**
- A notice / banner is rendered at the top of the wizard with the text: "ArraySubs Pro is not active, so Pro-only wizard options are hidden for now."
- The notice is dismissable or persistent per design — record which.
- The wizard still functions on every step.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Confirm Pro-only options are hidden on Step 8
**Steps:**
1. From the running wizard, click through to Step 8 (Additional Features & Tools).
2. Look at the available feature checkboxes.
3. Compare against the documented free vs Pro feature lists.

**Expected Result:**
- The four Pro-only options are **completely absent** from the checkbox group: **Store Credit System**, **Feature Manager**, **Activity Audit Log**, **Multi-Login Prevention**.
- The four free-only options are visible and toggleable: **Custom Profile Fields**, **My Account Page Editor**, **Hide Admin Bar for Customers**, **Restrict WP Dashboard Access**.
- Follow-up questions tied to Pro features (auto-apply store credit, store-credit expiration, max concurrent sessions, feature-manager highlights) are also hidden.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Confirm Pro-implied profile defaults still work on free
**Steps:**
1. Go back to Step 1 and pick **SaaS / Digital Software** (which would normally pre-check Feature Manager and Audit Log).
2. Skip with defaults to Step 8.
3. Inspect the checkbox state.

**Expected Result:**
- Step 8 shows free-only options pre-checked according to the SaaS profile (none of the Pro options can be pre-checked because they are hidden).
- No JavaScript console error from referencing a missing Pro field.
- The wizard still allows you to advance to Step 9 and shows a clean review screen with no missing references.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Re-activate ArraySubsPro
**Steps:**
1. **Discard wizard** to close the current run.
2. Go to **Plugins → Installed Plugins**.
3. Click **Activate** under **ArraySubsPro**.
4. Reload the dashboard.

**Expected Result:**
- ArraySubsPro re-activates with no error.
- ArraySubs Pro sub-menus return.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Confirm Pro-only options reappear in the wizard
**Steps:**
1. Open **ArraySubs → Easy Setup → Launch Setup Wizard**.
2. Skip with defaults to Step 8.
3. Look at the feature checkbox group.

**Expected Result:**
- The "ArraySubs Pro is not active" notice is no longer shown at the top of the wizard.
- Step 8 now lists all 8 features (4 Pro + 4 free).
- Each Pro feature is labelled with a **Pro** badge per the manual table.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.7 — Confirm Pro follow-up questions work
**Steps:**
1. On Step 8, tick **Store Credit System**, **Feature Manager**, and **Multi-Login Prevention**.
2. Observe the follow-up questions that appear.
3. Set values: store credit auto-apply = **Yes**, store credit expires = **After 365 days**, feature manager highlights = **Yes**, max sessions = **3 sessions**.

**Expected Result:**
- Ticking **Store Credit System** reveals **Should store credit automatically apply to renewal payments?** and **When should store credits expire?** with options **Never expire** / **After 90 days** / **After 180 days** / **After 365 days**.
- Ticking **Feature Manager** reveals **Show Feature Manager highlights on product pages?**.
- Ticking **Multi-Login Prevention** reveals **Maximum concurrent login sessions per customer** with options **1**, **2**, **3**, **5 sessions**.
- All values save to the review screen on Step 9.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- DevTools Console must stay clean during the deactivation, wizard launch on free-only, re-activation, and wizard launch on Pro-active flows.
- Confirm `wp-content/debug.log` shows no PHP notices from `arraysubs/wizard` paths during Sub-Tasks 4.1 through 4.4.
- Leave Pro **active** at the end of this task — Stage 01 Task 05 expects Pro to be active.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Pro state at end of run: Active / Inactive
- Notes:
