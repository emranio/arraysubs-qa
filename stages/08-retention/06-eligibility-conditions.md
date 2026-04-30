# Stage 08 — Task 06: Eligibility Conditions

| Key | Value |
|---|---|
| Stage | 08 — Retention Flow |
| Module | Retention Flow / Eligibility Logic |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 02 — Discount offer, 03 — Pause offer, 05 — Contact Support offer |

## Objective
Validate the three core eligibility rules at the customer-facing modal:
1. The **already-used** check — a subscription that already has an accepted retention discount must not see the discount card again.
2. The **reason targeting** check — offers only appear when the customer's selected reason is in the offer's trigger reasons.
3. The **leave reasons empty = show for all** rule — clearing all trigger reasons makes the offer appear for every reason.

## Pre-conditions
- Tasks 02, 03, 05 PASSED.
- Subscription R5 — Active Basic Monthly that already has an accepted retention discount in its history (Subscription R1 from Task 02 qualifies; reuse it).
- Subscription R6 — A clean Active Basic Monthly with no offer history.
- Discount Offer enabled (20% / 3 cycles, Trigger reasons: `Too expensive`).
- Pause Offer enabled (30 days, Trigger reasons: `Just need a temporary break`).
- Contact Support Offer enabled (Trigger reasons: `Technical issues`, `Missing features I need`).

## Test Data
- Subscription R5 (already used discount): Subscription R1 from Task 02.
- Subscription R6: clean Active Basic Monthly.

## Sub-Tasks

### Sub-Task 06.1 — Already-used discount is hidden
**Steps:**
1. As `cust1@example.com`, open Subscription R5 (the one that already accepted a discount in Task 02).
2. Click **Cancel Subscription**.
3. Select reason **Too expensive**.
4. Click **Continue**.

**Expected Result:**
- The **"Before You Go..."** modal opens.
- The Discount card is **NOT** shown (already-used check filters it out).
- Other configured offers that match Too expensive (e.g. Downgrade if enabled and applicable) may still appear; if no offers remain, the modal may show only the dismissal buttons or skip directly to cancellation — record the actual behaviour.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.2 — Click Keep Subscription to abort
**Steps:**
1. Click **Keep Subscription** to close the retention modal without cancelling.

**Expected Result:**
- The modal closes.
- Subscription R5 remains Active with the existing retention discount untouched.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.3 — Reason targeting: matching reason shows discount
**Steps:**
1. Open Subscription R6.
2. Click **Cancel Subscription**.
3. Select **Too expensive**.
4. Click **Continue**.

**Expected Result:**
- The Discount card appears.
- The Pause card and Contact Support card do NOT appear (their trigger reasons don't include Too expensive).
- Click **Keep Subscription** to abort and leave R6 untouched.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.4 — Reason targeting: non-matching reason hides discount
**Steps:**
1. Reopen Subscription R6 → click **Cancel Subscription**.
2. Select **Found a better alternative** (no offer is configured for this reason yet).
3. Click **Continue**.

**Expected Result:**
- No offer cards appear for Found a better alternative — the retention modal either skips entirely or shows only the dismissal buttons.
- Click **Keep Subscription** to abort.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.5 — Empty reasons = show for all
**Steps:**
1. As Administrator, open **ArraySubs → Retention Flow**.
2. Edit the Discount Offer settings: clear all selected reasons in **Show for these reasons** (leave the multi-select empty).
3. Save.
4. As `cust1@example.com`, open Subscription R6 → click **Cancel Subscription**.
5. Select **Found a better alternative** (a reason that previously did not trigger the discount).
6. Click **Continue**.

**Expected Result:**
- The Discount card now appears for **Found a better alternative**, proving that an empty trigger reasons list = show for all reasons.
- Click **Keep Subscription** to abort.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.6 — Multiple matching offers shown together
**Steps:**
1. As Administrator, edit the Discount Offer to target only `Just need a temporary break` (which is also the Pause Offer's trigger).
2. Save.
3. As `cust1@example.com`, on Subscription R6, click **Cancel Subscription** → select **Just need a temporary break** → **Continue**.

**Expected Result:**
- The retention modal shows **both** the Discount card and the Pause card simultaneously.
- The customer can pick only one (clicking accept on one closes the modal and applies that offer).
- Click **Keep Subscription** to abort without accepting.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.7 — Restore default offer configuration
**Steps:**
1. Restore Discount Offer to: trigger reasons `Too expensive` only.
2. Save.

**Expected Result:**
- Configuration is back to the Task 02 baseline ready for the analytics task.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm closing the retention modal via the X / Escape key does **not** cancel the subscription — only "No thanks, continue to cancel" actually proceeds with cancellation. Test once on Subscription R6.
- Confirm declining all offers and proceeding with the cancellation logs an "offer declined" event in Retention activity logs (Task 07 will verify the dashboard side).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
