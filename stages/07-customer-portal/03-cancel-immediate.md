# Stage 07 — Task 03: Cancel Subscription Immediately

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / Cancel Subscription |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 02 — View Subscription detail |

## Objective
With **General Settings → Customer Actions → Cancel Immediately** enabled, walk through the customer-facing immediate cancellation flow on an Active subscription: open the Cancel modal, choose a reason, confirm cancellation, observe the success message, and verify the subscription status changes to Cancelled in real time both in the portal and admin.

## Pre-conditions
- 02 — View Subscription detail passed.
- **General Settings → Customer Actions → Allow Cancellation:** Enabled.
- **General Settings → Customer Actions → Cancel Immediately:** **Enabled**.
- **Retention Flow → Enable Retention Offers:** Disabled (so the retention modal does not appear; retention flow is covered in Stage 08).
- **Retention Flow → Require Cancellation Reason:** Enabled.
- Test customer `cust1@example.com` owns at least one Active subscription that is **not** Subscription A from Task 02 (use a fresh Active Basic Monthly — call it Subscription E).
- Browser admin tab is also logged in as Administrator on a separate window for verification.

## Test Data
- Cancellation reason to select: `Found a better alternative`

## Sub-Tasks

### Sub-Task 03.1 — Open the Cancel modal
**Steps:**
1. As `cust1@example.com`, open `/my-account/subscriptions/`.
2. Click **View** on Subscription E.
3. In the Subscription Actions section, click **Cancel Subscription**.

**Expected Result:**
- A modal dialog opens centered on screen.
- The modal contains a warning message describing what will happen on cancellation.
- A reason selector (dropdown or radio list) is visible.
- A **Keep Subscription** button is visible.
- A **Continue** button is visible (and is disabled because no reason is selected yet, since Require Reason is on).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.2 — Reason required validation
**Steps:**
1. Without selecting a reason, attempt to click **Continue**.

**Expected Result:**
- The Continue button remains disabled (cannot be clicked) OR a validation message appears stating a reason is required — record which behaviour is observed.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.3 — Select a reason and confirm
**Steps:**
1. Select **Found a better alternative** from the reason selector.
2. Click **Continue**.

**Expected Result:**
- The Continue button becomes enabled after selecting a reason.
- Because retention offers are disabled, the modal proceeds directly to the cancellation outcome (no "Before You Go..." modal appears).
- A success toast / inline notice displays the exact text: **"Subscription cancelled successfully."**

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.4 — Status changes to Cancelled in real time
**Steps:**
1. Without manually refreshing, observe the subscription detail page.
2. After the success toast appears, refresh the page once.

**Expected Result:**
- The Status row updates from **Active** to **Cancelled** with a red badge (no full page reload required for the toast, but the badge will reflect after the page refresh).
- The Next Payment row no longer shows a date (replaced with `—` or hidden).
- The **Cancel Subscription**, **Change Plan**, **Skip Next Renewal**, and **Pause Subscription** buttons are all hidden because the subscription is no longer Active or On-Hold.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.5 — Admin-side verification
**Steps:**
1. Switch to the admin browser window.
2. Navigate to **ArraySubs → Subscriptions** and open Subscription E.

**Expected Result:**
- Status shows **Cancelled**.
- A subscription note records the cancellation with: who cancelled (the customer), the reason **"Found a better alternative"**, and the timestamp.
- The `_end_date` field reflects roughly the time of cancellation (within a minute).
- Future scheduled renewal actions for this subscription are removed (verify in **Tools → Scheduled Actions**: filter by `arraysubs_renewal` hook + this subscription ID — no future entries should remain).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.6 — Re-attempting cancel
**Steps:**
1. Back in the customer browser, refresh `/my-account/view-subscription/{id}/`.

**Expected Result:**
- No **Cancel Subscription** button is visible (a Cancelled subscription is not in an eligible status).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the My Subscriptions list now shows Subscription E with a red **Cancelled** badge and `—` in the Next Payment column.
- Verify the cancellation event is logged in **WooCommerce → Analytics → Retention** activity logs (Stage 08 — Task 07 covers the dashboard fully; here we just confirm an entry exists).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
