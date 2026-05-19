# Stage 07 — Task 05: Undo Scheduled Cancellation

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / Undo Scheduled Cancellation |
| Plugin Coverage | Free |
| Estimated Time | 10 min |
| Depends On | 04 — Cancel at end of billing period |

## Objective
On the EOP-cancelled subscription from Task 04, click **Undo Cancellation**, observe the success message, and verify all cancellation-related metadata (pending flag, scheduled date, reason) is fully cleared so the subscription resumes normal renewal behaviour.

## Pre-conditions
- Task 04 passed and Subscription F currently shows **Active** with a pending end-of-period cancellation and an **Undo Cancellation** button.
- Settings unchanged from Task 04.

## Test Data
- Subscription F (from Task 04)

## Sub-Tasks

### Sub-Task 05.1 — Click Undo Cancellation
**Steps:**
1. As `cust1@example.com`, open Subscription F's detail page.
2. Click **Undo Cancellation**.
3. If a confirmation prompt appears, accept it.

**Expected Result:**
- A success notice displays the exact text: **"Scheduled cancellation removed successfully. Your subscription will continue renewing normally."**
- No errors appear in the browser console.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.2 — Pending indicator cleared
**Steps:**
1. Refresh the subscription detail page.
2. Look for any pending-cancellation banner or Cancellation Details card.

**Expected Result:**
- No pending-cancellation banner / card is visible anywhere on the page.
- Status remains **Active** (green badge).
- The **Undo Cancellation** button is gone.
- The **Cancel Subscription** button is visible again.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.3 — Admin metadata cleanup
**Steps:**
1. As Administrator, open Subscription F in **ArraySubs → Subscriptions**.
2. Inspect the Cancellation Details card area, the metadata box, and the subscription notes timeline.

**Expected Result:**
- The Cancellation Details card is no longer present.
- The `_waiting_cancellation` flag is cleared (verify via the admin debug panel or Custom Fields metabox if exposed; otherwise infer from the absence of the card and the cancellation reason metadata).
- A subscription note records the undo action with timestamp and initiator.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.4 — Renewal scheduling restored
**Steps:**
1. As Administrator, open **Tools → Scheduled Actions**.
2. Filter by status **Pending** and search for the subscription ID.

**Expected Result:**
- A renewal action (e.g. `arraysubs_subscription_renewal`) is scheduled for the original next payment date — the same date the subscription showed before the cancellation was requested.
- No `arraysubs_process_waiting_cancellation`-style action remains for this subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.5 — Customer can cancel again
**Steps:**
1. Back in the customer browser, click **Cancel Subscription** on Subscription F.

**Expected Result:**
- The Cancel modal opens normally with the reason selector — proving the cancellation flow is fully reset.
- Click **Keep Subscription** to close the modal without cancelling. The subscription remains Active with no pending flag.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the My Subscriptions list shows Subscription F as **Active** with the original Next Payment date intact.
- Confirm there is exactly one entry in the WooCommerce → Analytics → Retention activity log for the cancellation request and one for the undo.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
