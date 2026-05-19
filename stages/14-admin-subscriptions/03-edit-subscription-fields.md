# Stage 14 — Task 03: Edit Subscription Fields

| Key | Value |
|---|---|
| Stage | Admin Subscription Management |
| Module | Admin SPA — Edit Subscription |
| Plugin Coverage | Free |
| Estimated Time | 20 min |
| Depends On | 14-02 (a subscription exists) |

## Objective
Open the **Edit Subscription** form for an existing subscription and confirm exactly which fields are editable: invoice email, billing address (10 fields), shipping address (9 fields, no phone), and the inline status change control. Verify that product, recurring amount, customer, and next payment date are read-only on the edit screen. Trigger a status change and confirm the correct confirmation modal copy appears for **Cancelled**, **On Hold**, and **Active** transitions.

## Pre-conditions
- Logged in as Administrator.
- One **Active** subscription is available to edit (use one created in stage 06 or set the subscription from task 14-02 to Active first if needed).
- DevTools Network tab open to capture the PUT/POST update request.

## Test Data
- Updated invoice email: `invoice-updated@arraysubs.test`.
- Updated billing City: `Dallas`.
- Updated shipping City: `Houston`.
- Status change to test: Active → On Hold → Active → (eventually Cancelled in 14-08).

## Sub-Tasks

### Sub-Task 3.1 — Open the edit form
**Steps:**
1. From **ArraySubs → Subscriptions**, hover the target row and click **Edit**.
2. Wait for the focused edit form to render.

**Expected Result:**
- Page renders with a read-only **Subscription Summary** card at the top showing Product, Recurring Amount (formatted as e.g. `$50.00 / 1 month(s)`), Customer, and Status badge with the inline status change control.
- Below the summary, only three editable cards are visible: **Contact** (Invoice Email), **Billing Address**, **Shipping Address**.
- **Update Subscription** button is at the bottom.
- No editable input exists for Product, Variation, Recurring Amount, Billing Interval / Period, Trial settings, Quantity, or Next Payment Date.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Confirm read-only summary
**Steps:**
1. Try to click into the Product, Recurring Amount, Customer, and any next-payment-date display in the summary card.
2. Note any tooltip or info text shown when you hover the next payment date.

**Expected Result:**
- None of these summary fields accept focus or open an editor.
- A note (per the manual info-box) explains the next payment date is calculated automatically and is no longer editable.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Edit invoice email and addresses
**Steps:**
1. Change **Invoice Email** to `invoice-updated@arraysubs.test`.
2. In **Billing Address**, change the City to `Dallas`. Leave other fields unchanged.
3. In **Shipping Address**, change the City to `Houston`.
4. Click **Update Subscription**.
5. Watch the network request; wait for the redirect to the subscription detail page.

**Expected Result:**
- The save returns 2xx.
- After redirect, the detail page **Customer Information** card shows the new Invoice Email.
- The **Billing Address** card shows the new City `Dallas`.
- The **Shipping Address** card shows the new City `Houston`.
- The Subscription Notes panel logs an admin-edit entry referencing changed fields.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Status change confirmation modal: Active → On Hold
**Steps:**
1. Re-open the edit screen for the same subscription.
2. In the summary card, open the status dropdown and select **On Hold**.
3. Click **Change Status**.
4. Read the confirmation dialog text.

**Expected Result:**
- Modal appears with the message: "This will put the subscription on hold. Scheduled renewals will be paused until the subscription is reactivated. Continue?"
- Two buttons: a confirm action and a cancel action.
- Click **Cancel** in this step — confirm the status does not change and the modal closes.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Status change confirmation modal: actually transition to On Hold
**Steps:**
1. Repeat the status dropdown action and pick **On Hold** again.
2. Click **Change Status**.
3. This time click the confirm action in the modal.
4. Wait for the page to reload.

**Expected Result:**
- Modal closes after confirming.
- Status badge in the summary becomes **On Hold** (yellow).
- A system note in the notes panel records the status change.
- DevTools Network tab shows a 2xx PATCH/POST.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Status change confirmation modal: On Hold → Active
**Steps:**
1. From the edit screen of the now-on-hold subscription, open the status dropdown and pick **Active**.
2. Click **Change Status**.
3. Read the dialog text and click confirm.

**Expected Result:**
- Modal text reads: "This will activate the subscription and schedule renewal payments. Continue?"
- After confirming, status badge becomes **Active** (green).
- Notes panel records a "Subscription Reactivated" entry (per lifecycle manual).
- A subscription-reactivated email is queued (verify in `wp_mail` log if available — optional).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.7 — Status change confirmation modal: preview Cancelled wording (do NOT confirm)
**Steps:**
1. Open the edit screen again.
2. Open the status dropdown and pick **Cancelled**.
3. Click **Change Status**.
4. Read the warning text in the confirmation modal.
5. Click **Cancel** — do not actually cancel the subscription. (The actual cancellation flow is exercised in task 14-08.)

**Expected Result:**
- Modal text reads: "This will cancel the subscription immediately. All scheduled renewals will be removed. Continue?"
- The warning copy makes the immediate-cancellation behaviour explicit.
- Closing the modal leaves the subscription's status unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Reload the edit page after each change and confirm the values persisted.
- Confirm DevTools Console shows zero errors during dropdown interaction and modal transitions.
- Confirm the Edit page does not expose a Delete action (deletion is a row action on the list, not on the edit screen).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Subscription ID edited:
- Notes:
