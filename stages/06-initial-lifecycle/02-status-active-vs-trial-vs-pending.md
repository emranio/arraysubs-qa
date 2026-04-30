# Stage 06 — Task 02: Status — Active vs Trial vs Pending

| Key | Value |
|---|---|
| Stage | Initial Subscription Lifecycle |
| Module | Lifecycle Management — Initial Statuses |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | Stage 05 Task 01 (Active), Stage 05 Task 03 (Trial), one Pending order (created here if needed) |

## Objective
Confirm that the three initial subscription statuses are produced correctly:
- A trial-product purchase yields a subscription in **Trial** status.
- A subscription order paid via a manual gateway and marked Completed yields **Active**.
- A subscription order placed but with the order still in **Pending payment** yields a subscription in **Pending** status (per manual: *"Trial — Product has a trial period... Pending — Product has no trial (transitions to Active when payment is confirmed)"*).

## Pre-conditions
- Stage 05 Task 01 finished — its subscription should be **Active**.
- Stage 05 Task 03 finished — its subscription should be **Trial**.
- A new Pending case will be created here using a fresh customer and a manual gateway, with the order **left as Pending** (not marked Completed).

## Test Data
- Active subscription: from Stage 05 Task 01.
- Trial subscription: from Stage 05 Task 03.
- Pending case: customer `customer-pending@example.test`, product `Basic Monthly`, manual gateway, order will be left in `Pending payment`.

## Sub-Tasks

### Sub-Task 2.1 — Confirm Active subscription is on the Active tab
**Steps:**
1. As Administrator, open **ArraySubs → Subscriptions**.
2. Click the **Active** tab.
3. Locate the subscription created in Stage 05 Task 01.

**Expected Result:**
- Subscription is listed under Active.
- Status badge is green and reads **Active**.
- Recurring amount: `$29.99`, next payment: Stage 05 Task 01 date + 1 month.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Confirm Trial subscription is on the Trial tab
**Steps:**
1. Click the **Trial** tab.
2. Locate the subscription created in Stage 05 Task 03 for `customer-trial@example.test`.

**Expected Result:**
- Subscription is listed under Trial.
- Status badge is cyan and reads **Trial**.
- Product: `Trial 14-Day`.
- Next payment date equals the trial end date (Stage 05 Task 03 date + 14 days).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Create a Pending case
**Steps:**
1. In Incognito, log in as `customer-pending@example.test` (create the user via wp-admin first if needed).
2. Add `Basic Monthly` to cart.
3. Open the checkout page.
4. Select a manual gateway (Direct bank transfer / Cheque / Cash on delivery).
5. Click **Place order**.
6. After the order received page renders, do NOT mark the order Completed in admin yet — leave it as the manual gateway's default status (`On hold` for Direct bank transfer or `Pending payment`).

**Expected Result:**
- A new order exists with status `Pending payment` or `On hold`.
- A subscription record was created for it (per manual subscription creation logic — *"the order payment not yet confirmed... subscriptions are created when the order is paid, not when it is placed"* — meaning the subscription may be created in Pending status, awaiting payment confirmation; verify in the next sub-task).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Confirm the new subscription is on the Pending tab
**Steps:**
1. As Administrator, open **ArraySubs → Subscriptions**.
2. Click the **Pending** tab.
3. Search for `customer-pending@example.test` or sort by Date descending to find the most recent.

**Expected Result:**
- The subscription is listed under Pending.
- Status badge is orange and reads **Pending**.
- Product: `Basic Monthly`.
- Recurring amount: `$29.99`.
- Next Payment may be blank or set to the predicted first cycle date — record what is shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Mark the Pending order as paid and re-check status
**Steps:**
1. Open the Pending order in **WooCommerce → Orders**.
2. Change order status to **Completed**.
3. Click **Update**.
4. Return to **ArraySubs → Subscriptions**.
5. Refresh the list and check the **Active** tab for the subscription.

**Expected Result:**
- The subscription's status moves from **Pending** to **Active** automatically.
- Per manual: *"Pending (transitions to Active when payment is confirmed)"*.
- Next payment date is now set to today + 1 month from the order completion timestamp.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Cross-check the three status tabs
**Steps:**
1. Click each of **Active**, **Trial**, **Pending** tabs in turn and observe counts in the parentheses (if displayed) or by visually scanning the rows.
2. Confirm:
   - Active tab contains the Stage 05 Task 01 subscription **and** the just-promoted Pending case.
   - Trial tab contains the Stage 05 Task 03 subscription.
   - Pending tab no longer contains the case from Sub-Task 2.3 (it has moved to Active).

**Expected Result:**
- Status filtering reflects each subscription's current status without stale data.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the Trial subscription's Trial Length and Trial End Date are still shown on its detail page.
- Confirm that opening the Active subscriptions in **View Details** never displays the Trial Length / Trial End Date fields (per manual: those are shown only for Trial subscriptions or subscriptions that had a trial).
- Confirm no subscription got stuck in `Pending` after its order was marked Completed (verify by re-running Sub-Task 2.5 if any old Pending entries linger).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Active subscription ID:
- Trial subscription ID:
- Pending → Active transition subscription ID:
- Notes:
