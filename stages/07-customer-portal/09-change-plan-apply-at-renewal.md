# Stage 07 — Task 09: Change Plan — Apply at Renewal

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / Change Plan (Apply at Renewal) |
| Plugin Coverage | Free |
| Estimated Time | 20 min |
| Depends On | 08 — Change plan, immediate proration |

## Objective
With the proration mode set to **Apply at Renewal**, the same upgrade flow should NOT charge today, should display a pending-switch banner with target plan, effective date, future renewal amount, and switch fee, and should let the customer cancel the pending switch before the renewal is paid.

## Pre-conditions
- **Plan Switching → Proration mode:** **Apply at Renewal**.
- Switch fee setting unchanged (e.g. `$5.00`).
- `cust1@example.com` owns Subscription J — a fresh Active Basic Plan ($9.99/wk) with no existing pending switch.
- Original next payment date for Subscription J recorded.

## Test Data
- Switch from: Basic Plan ($9.99/wk)
- Switch to: Pro Plan ($19.99/wk)
- Original Next Payment date: __________

## Sub-Tasks

### Sub-Task 09.1 — Verify proration mode setting
**Steps:**
1. As Administrator, open **ArraySubs → Settings → Plan Switching**.
2. Confirm the proration mode is **Apply at Renewal** and click **Save Changes** if changed.

**Expected Result:**
- Setting is persisted; success toast confirms.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.2 — Initiate plan change
**Steps:**
1. As `cust1@example.com`, open Subscription J.
2. Click **Change Plan** → Upgrade/Downgrade tab → **Pro Monthly**.

**Expected Result:**
- The proration preview panel appears.
- Because the mode is Apply at Renewal, the preview emphasises **no charge today** — the customer continues paying their current plan until the next renewal, then the new plan takes effect.
- The preview shows the **Switch fee** ($5.00) and the **Future renewal amount** at the new plan's price ($59.99).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.3 — Confirm without charge
**Steps:**
1. Click **Confirm Plan Change**.

**Expected Result:**
- No checkout redirect occurs; the customer remains in My Account.
- A success notice confirms the switch is scheduled for the next renewal (record exact wording).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.4 — Pending-switch banner appears
**Steps:**
1. Refresh the Subscription J detail page.

**Expected Result:**
- A pending-switch banner is visible at the top of the page, containing:
  - Target plan: **Pro Monthly**
  - Effective date: matches the original Next Payment date recorded in pre-conditions
  - Quantity (default 1)
  - Future renewal amount: **$59.99**
  - Switch fee: **$5.00**
- The current Recurring Amount and Product rows still reflect Basic Monthly until the renewal is paid.
- A **Cancel pending switch** action / link is visible to the customer.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.5 — Replacement confirmation when picking another plan
**Steps:**
1. With the pending switch still in place, click **Change Plan** again.
2. This time pick the Crossgrade product from the **Others** tab.

**Expected Result:**
- A confirmation prompt appears asking the customer to confirm replacement of the existing pending switch (does not silently overwrite).
- If the customer cancels this prompt, the original Pro Monthly pending switch remains untouched.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.6 — Customer cancels pending switch
**Steps:**
1. Dismiss the replacement prompt without confirming.
2. On the pending-switch banner, click **Cancel pending switch** (or equivalent control).
3. Confirm any prompt.

**Expected Result:**
- The pending-switch banner disappears.
- The subscription remains on Basic Monthly with the original Next Payment date intact.
- A success notice confirms the pending switch was cancelled (record exact wording).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.7 — Admin verification
**Steps:**
1. As Administrator, open Subscription J → check meta and notes.

**Expected Result:**
- The `_pending_plan_switch` meta is cleared.
- A subscription note records both the scheduling of the pending switch and its cancellation.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- The customer was never charged a proration during this whole flow — confirm no order was created in WooCommerce → Orders for Subscription J during the test.
- Re-set proration mode to your store default after the test if needed.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
