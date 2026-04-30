# Stage 01 — Task 02: Business Type Defaults

| Key | Value |
|---|---|
| Stage | Easy Setup Wizard |
| Module | Setup Wizard — Business Profiles |
| Plugin Coverage | Both |
| Estimated Time | 35 min |
| Depends On | 01-setup-wizard/01-launch-and-navigation.md |

## Objective
Walk through each of the 7 business types on Step 1 — **SaaS / Digital Software**, **Physical Subscription Box**, **Membership / Community**, **Digital Content**, **Professional Services**, **Nonprofit / Donations**, **Other / Custom** — and verify the wizard preloads the documented profile defaults on subsequent steps. This proves the wizard's core value: smart defaults per industry.

## Pre-conditions
- Stage 01 Task 01 passed.
- Both plugins active.
- Logged in as Administrator.
- Use **Discard wizard** between each business-type run so there is no carry-over from the previous run.

## Test Data
Profile defaults (from `easy-setup-wizard.md`):

| Profile | Key Defaults |
|---|---|
| SaaS / Digital Software | Multiple plans, trials with payment required, strict grace (1 active / 3 hold days), one subscription per customer, all-direction plan switching, immediate proration, access control enabled, Feature Manager and Audit Logging on |
| Physical Subscription Box | Lenient grace (5/14), skip and pause enabled, contact-support retention offer, Store Credit enabled |
| Membership / Community | Multiple plans, pause flexibility, one per customer, access control, Feature Manager, Audit Logging, Multi-Login prevention |
| Digital Content | Multiple plans, trials, upgrade-only plan switching, end-of-period cancellation and refund, access control |
| Professional Services | Pause enabled, contact-support retention, immediate prorated refund, custom profile fields |
| Nonprofit / Donations | Lenient grace, hide admin bar, minimal defaults |
| Other / Custom | Monthly billing, single plan, standard grace, manual renewals, minimal configuration |

## Sub-Tasks

### Sub-Task 2.1 — SaaS / Digital Software profile
**Steps:**
1. Launch the wizard from **ArraySubs → Easy Setup → Launch Setup Wizard**.
2. On Step 1, choose **SaaS / Digital Software**, billing cycle **Weekly** (matches the catalog's predominantly weekly products), plan count **Multiple plans / tiers**, trials **Yes**.
3. Click **Next** to advance through every step. On each step, do **not** modify any answer — just observe what is preselected.
4. On Step 9, expand each step section in the review.

**Expected Result:**
- Step 2: grace is **Strict (1 active / 3 hold days)**.
- Step 3: **Only one subscription per customer** is selected.
- Step 4: plan switching is set to **Allow all switching directions**; proration is **Prorate immediately**.
- Step 6: access control is enabled (the "Do you need content restriction?" question is skipped per the info-box note in the manual).
- Step 8: **Feature Manager** and **Activity Audit Log** are pre-checked.
- Step 1: trial questions show "payment method required = Yes".

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Physical Subscription Box profile
**Steps:**
1. Discard the previous wizard run.
2. Launch wizard again. On Step 1 choose **Physical Subscription Box**.
3. Walk through every step using **Skip with defaults**.
4. Open the Step 9 review and inspect the recorded answers.

**Expected Result:**
- Step 2: grace is **Lenient (5 / 14)**; skip and pause checkboxes are both ticked.
- Step 5: retention offer **Contact support** is checked.
- Step 8: **Store Credit System** is pre-checked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Membership / Community profile
**Steps:**
1. Discard, relaunch. On Step 1 choose **Membership / Community**, plan count **Multiple plans / tiers**.
2. Skip with defaults through every step.
3. Inspect the Step 9 review.

**Expected Result:**
- Step 2: pause is enabled (max pause duration question becomes visible).
- Step 3: **Only one subscription per customer** is selected.
- Step 6: access control is enabled (content-restriction question skipped per manual).
- Step 8: **Feature Manager**, **Activity Audit Log**, and **Multi-Login Prevention** are pre-checked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Digital Content profile
**Steps:**
1. Discard, relaunch. On Step 1 choose **Digital Content**, plan count **Multiple plans / tiers**, trials **Yes**.
2. Skip with defaults through every step.
3. Inspect the Step 9 review.

**Expected Result:**
- Step 4: plan switching is set to **Only upgrades**.
- Step 5: cancellation timing is **At the end of the billing period**; refund behavior is **Refund at end of period**.
- Step 6: access control is enabled (content-restriction question skipped per manual).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Professional Services profile
**Steps:**
1. Discard, relaunch. On Step 1 choose **Professional Services**.
2. Skip with defaults through every step.
3. Inspect the Step 9 review.

**Expected Result:**
- Step 2: pause is enabled.
- Step 5: retention offer **Contact support** is checked; refund behavior is **Immediate prorated refund**.
- Step 8: **Custom Profile Fields** is pre-checked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Nonprofit / Donations profile
**Steps:**
1. Discard, relaunch. On Step 1 choose **Nonprofit / Donations**.
2. Skip with defaults through every step.
3. Inspect the Step 9 review.

**Expected Result:**
- Step 2: grace is **Lenient (5 / 14)**.
- Step 8: **Hide Admin Bar for Customers** is pre-checked.
- The overall set of preselected options is minimal — most optional features are off.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.7 — Other / Custom profile
**Steps:**
1. Discard, relaunch. On Step 1 choose **Other / Custom**.
2. Skip with defaults through every step.
3. Inspect the Step 9 review.

**Expected Result:**
- Step 1: billing cycle is **Monthly**, plan count is **One plan**.
- Step 2: grace is **Standard (3 / 7)**.
- Step 8: virtually no optional features are preselected (minimal configuration).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.8 — Switching profiles re-applies defaults
**Steps:**
1. Without discarding, on Step 1 change the business type from **Other / Custom** back to **SaaS / Digital Software**.
2. Click **Next** through to Step 2.
3. Inspect the grace selection and skip/pause checkboxes.

**Expected Result:**
- Changing the business type on Step 1 re-applies the defaults of the new profile to subsequent steps.
- Step 2 now shows **Strict (1 active / 3 hold days)** instead of the **Other / Custom** default.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.9 — Verify Monthly billing cycle still applies cleanly
**Steps:**
1. Discard, relaunch. On Step 1 choose **SaaS / Digital Software**, billing cycle **Monthly**, plan count **Multiple plans / tiers**, trials **Yes**.
2. Skip with defaults through every step.
3. Inspect the Step 9 review.

**Expected Result:**
- Monthly remains a valid wizard answer (it matches the **Basic Monthly $29.99** product downstream).
- Step 9 review shows the chosen billing cycle as **Monthly**.
- All other SaaS profile defaults from Sub-Task 2.1 still apply.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After each profile run, click **Discard wizard** to ensure no leakage to saved settings.
- Re-open the wizard after discarding to verify Step 1 starts fresh (no business type pre-selected).
- Confirm the DevTools Console stays clean across all 7 profile selections.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Profiles tested OK:
- Notes:
