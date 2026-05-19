# Stage 07 — Task 04: Cancel at End of Billing Period

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / Cancel Subscription (EOP) |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 03 — Cancel immediately |

## Objective
With **Cancel Immediately** disabled, verify end-of-period cancellation: the customer triggers cancellation, the subscription stays **Active** with a pending-cancellation indicator, future scheduled renewals are removed from Action Scheduler, and the success message is the EOP-specific copy.

## Pre-conditions
- Task 03 passed.
- **General Settings → Customer Actions → Cancel Immediately:** **Disabled**.
- All other cancellation/retention flags as in Task 03 (Allow Cancellation on, Retention Offers off, Require Reason on).
- `cust1@example.com` owns Subscription F: a fresh Active Basic Monthly with a known next payment date at least 14 days in the future.

## Test Data
- Cancellation reason: `Just need a temporary break`

## Sub-Tasks

### Sub-Task 04.1 — Confirm setting saved
**Steps:**
1. As Administrator, open **ArraySubs → Settings → General Settings → Customer Actions**.
2. Confirm **Cancel Immediately** toggle is OFF.
3. Click **Save Changes** if a change was made.

**Expected Result:**
- The toggle is unmistakably off after the save.
- A success toast confirms settings saved.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.2 — Customer initiates cancel
**Steps:**
1. As `cust1@example.com`, open Subscription F's detail page.
2. Click **Cancel Subscription**.
3. Select reason **Just need a temporary break**.
4. Click **Continue**.

**Expected Result:**
- The Cancel modal opens with the same warning message style, but the warning text reflects the EOP outcome (subscription will end on the next payment date, not immediately).
- After clicking Continue (and because retention offers are off), the success notice reads exactly: **"Subscription scheduled for cancellation at the end of your current billing period."**

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.3 — Status remains Active with pending indicator
**Steps:**
1. Refresh the subscription detail page.
2. Observe the Status row and any pending-cancellation banner / card.

**Expected Result:**
- Status badge remains **Active** (green).
- A pending-cancellation indicator is visible — either a "Scheduled for cancellation" notice near the status, or a Cancellation Details card showing the scheduled cancellation date matching the Next Payment date.
- An **Undo Cancellation** button is visible to the customer.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.4 — Cancel button no longer offered
**Steps:**
1. Look at the Subscription Actions section.

**Expected Result:**
- The **Cancel Subscription** button is hidden (a pending cancellation already exists, so the action is no longer eligible).
- The **Undo Cancellation** button is the primary action shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.5 — Future renewals unscheduled in Action Scheduler
**Steps:**
1. As Administrator, open **Tools → Scheduled Actions**.
2. Set the status filter to **Pending**.
3. Search for the subscription ID of Subscription F in the search field.
4. Inspect any rows whose hook starts with `arraysubs_` related to renewals (for example `arraysubs_subscription_renewal`).

**Expected Result:**
- No pending future renewal action is scheduled for Subscription F.
- A pending action for the EOP cancellation itself may exist (for example `arraysubs_process_waiting_cancellation`) — record the hook name and scheduled date for evidence.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.6 — Admin subscription page reflects pending state
**Steps:**
1. As Administrator, open Subscription F in **ArraySubs → Subscriptions**.

**Expected Result:**
- A **Cancellation Details** card is visible showing the scheduled cancellation date.
- A subscription note records the cancellation request, the reason **"Just need a temporary break"**, and that it is a scheduled (not immediate) cancellation.
- The status remains **Active**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- The My Subscriptions list still shows Subscription F as **Active** with the original Next Payment date.
- Confirm any existing pending plan switch on this subscription has been cleared (per spec: end-of-period cancellation clears any pending plan switch).
- Do **not** undo the cancellation in this task — Task 05 picks it up.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
