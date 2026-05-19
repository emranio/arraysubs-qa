# Stage 19 — Task 03: Refund at End of Period

| Key | Value |
|---|---|
| Stage | 19 — Refund Management |
| Module | Refunds / End-of-Period |
| Plugin Coverage | Free |
| Estimated Time | 20 min |
| Depends On | 01 — Refund settings |

## Objective
Switch **Refund on Cancellation** to **Refund at End of Period**, then cancel an active subscription mid-cycle. Verify the subscription stays **Active** with a `Scheduled for cancellation` indicator until the next payment date, no immediate refund order is created at the moment of cancellation, and when the EOP cancellation actually fires, the documented refund handling occurs (or no refund if the unused-portion settings produce zero — record observed behaviour).

## Pre-conditions
- Task 01 passed.
- A second subscription exists for `cust3@example.com`, also $30/month, also halfway through a cycle. Use the **Edit Subscription** dates approach as in Task 02.
- The subscription's parent order is paid via a refund-capable gateway.
- **ArraySubs → Settings → Refunds:** set **Refund on Cancellation = Refund at End of Period**, leave Auto Gateway Refund **Enabled**, Allow Prorated Refunds **Enabled**, Min Refund Amount `0`.
- We will need to simulate the EOP date arriving — either by manually editing the subscription's next payment date to the past once cancellation is scheduled, or by manually triggering the **Check Overdue Renewals** action from `Tools → Scheduled Actions`.

## Test Data
- Subscription ID for the test: record at start.
- Today: `<record>`.
- Next payment date at start: `<record>` (15 days from now).

## Sub-Tasks

### Sub-Task 03.1 — Apply the EOP refund setting
**Steps:**
1. Go to **ArraySubs → Settings → Refunds**.
2. Set **Refund on Cancellation** to **Refund at End of Period**.
3. Save Changes.

**Expected Result:**
- Save success toast appears.
- Hard-reload confirms the radio is on **Refund at End of Period**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.2 — Cancel mid-cycle from admin
**Steps:**
1. Open the test subscription in **ArraySubs → Subscriptions**.
2. Click **Cancel Subscription**.
3. In the dialog, confirm the choice that performs an EOP/scheduled cancellation (the dialog's wording will reflect the EOP setting). Enter reason `QA EOP refund test`.
4. Confirm.

**Expected Result:**
- The subscription status remains **Active** (not Cancelled).
- A **Cancellation Details** card / banner now appears on the detail page showing the **scheduled cancellation date** = next payment date (the 15-days-from-now value).
- An **Undo Cancellation** button is visible.
- A subscription note records that EOP cancellation was scheduled with reason `QA EOP refund test`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.3 — Confirm no immediate refund was created
**Steps:**
1. Open the parent order in WooCommerce.
2. Look at refunds and order notes.
3. Re-open the subscription Related Orders table.

**Expected Result:**
- No refund row exists on the order.
- Order total / refund balance unchanged.
- Subscription Related Orders shows zero refunded amount.
- The customer's mailbox does **not** contain a Subscription Cancelled email yet.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.4 — Force the EOP cancellation date to arrive
**Steps:**
1. From the admin subscription edit screen, change the **Next Payment Date** (and the scheduled cancellation date if separately editable) to a date in the past — e.g. yesterday. Save.
2. Go to **Tools → Scheduled Actions** and find the pending `arraysubs_check_overdue_renewals` (or equivalent) action.
3. Click **Run** on that action manually so we don't have to wait for the hourly cron.

**Expected Result:**
- The action runs successfully (status: Complete, exit reason: success).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.5 — Verify EOP cancellation executes and refund handling
**Steps:**
1. Reload the subscription detail page.
2. Inspect status, notes, and the parent order's refund area.

**Expected Result:**
- Subscription status is now **Cancelled**.
- A note records the EOP cancellation actually executing (separate from the earlier "scheduled" note).
- Behaviour for the refund itself: the manual states EOP refund triggers a refund of the unused portion **if applicable per settings**. Record exactly what happens:
  - If a refund WAS created: amount, gateway refund ID (if any), and that the customer received the Subscription Cancelled email.
  - If no refund was created: confirm there is a documented reason in the subscription notes (e.g. "no unused portion remaining at EOP").
- The pending renewal order, if any was generated for the cycle, is set to **Cancelled** status — no new charge attempt is made.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.6 — Reset settings to documented default
**Steps:**
1. Go to **ArraySubs → Settings → Refunds**.
2. Set **Refund on Cancellation** back to **Allow Immediate Refund**.
3. Save.

**Expected Result:**
- Saved. Downstream Task 04 explicitly sets its own value, but leaving the documented default is good hygiene between tasks.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Verify the customer's portal view of this subscription, before Sub-Task 03.4 fires the EOP, shows status **Active** with the "Subscription scheduled for cancellation" notice and the **Undo Cancellation** button is also visible to them.
- After EOP fires, customer's portal status switches to **Cancelled**.
- Confirm there is no leftover scheduled-action for `arraysubs_renewal` for this subscription post-cancellation.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
