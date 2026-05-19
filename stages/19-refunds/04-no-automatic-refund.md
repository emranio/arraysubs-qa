# Stage 19 — Task 04: No Automatic Refund

| Key | Value |
|---|---|
| Stage | 19 — Refund Management |
| Module | Refunds / No Auto Refund |
| Plugin Coverage | Free |
| Estimated Time | 18 min |
| Depends On | 01 — Refund settings |

## Objective
Set **Refund on Cancellation** to **No Automatic Refund**. Cancel an active subscription. Verify NO refund order is created automatically and the subscription is cancelled (or scheduled, depending on the cancellation timing setting) without any refund-related side effects. Then verify the admin can still issue a manual refund from the WooCommerce order screen and that path works correctly — recording the refund into the subscription's refund history.

## Pre-conditions
- Task 01 passed.
- A fresh paid subscription for `cust4@example.com` ($30/month, halfway through cycle, paid by a refund-capable gateway).
- **ArraySubs → Settings → Refunds** = **No Automatic Refund**, Auto Gateway Refund **Enabled** (still on for the manual path test), Allow Prorated Refunds **Enabled**, Min Refund Amount `0`.
- **General Settings → Customer Actions → Cancel Immediately** = Enabled (so cancellation is immediate and we can isolate "what does the refund setting alone do").

## Test Data
- Subscription ID: record at start.
- Parent order ID: record at start.
- Parent order total: `$30.00`.

## Sub-Tasks

### Sub-Task 04.1 — Configure No Automatic Refund
**Steps:**
1. Go to **ArraySubs → Settings → Refunds**.
2. Set **Refund on Cancellation = No Automatic Refund**.
3. Save Changes and hard-reload to confirm persistence.

**Expected Result:**
- Setting persists across reload.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.2 — Cancel the subscription
**Steps:**
1. Open the test subscription in **ArraySubs → Subscriptions**.
2. Click **Cancel Subscription**, choose immediate cancellation, reason `QA no-auto-refund test`, confirm.

**Expected Result:**
- Subscription status changes to **Cancelled** with red badge.
- Subscription notes record the cancellation, who cancelled, and the reason.
- All future scheduled `arraysubs_renewal` actions for this subscription are removed.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.3 — Verify NO refund was created automatically
**Steps:**
1. Open the parent WooCommerce order.
2. Inspect the refunds area, order total, and order notes.
3. Open the gateway dashboard (e.g. Stripe test mode) and look for refunds against this charge.
4. Re-open the subscription and view **Related Orders**.

**Expected Result:**
- The order has **no refund rows**.
- Order total still shows `$30.00` paid; refundable balance is the full `$30.00`.
- Order notes do **not** mention any refund.
- Gateway dashboard shows the original charge with no associated refund.
- Subscription Related Orders table has `$0.00` in the Refunded column for this order.
- The subscription's `_total_refunded` meta is `0` (or absent).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.4 — Issue a manual refund from the WC order screen
**Steps:**
1. With the parent order open in **WooCommerce → Orders → Edit Order**, click **Refund**.
2. Enter `30.00` in the refund amount, refund reason `QA manual refund after cancellation`.
3. Click **Refund $30.00 via [Gateway]** (the button that triggers a real gateway refund).

**Expected Result:**
- Refund processes successfully — a `-$30.00` refund row appears.
- An order note documents the gateway refund with the `re_xxx` ID (Stripe) or equivalent.
- The gateway dashboard now shows a refund of $30.00.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.5 — Verify subscription state after manual full refund
**Steps:**
1. Reload the subscription detail page.
2. Inspect status, Related Orders, and notes.

**Expected Result:**
- Subscription is still **Cancelled** (it was already cancelled before the refund — the refund does not "un-cancel" or otherwise mutate it).
- **Related Orders → Refunded** column now shows `-$30.00`.
- Subscription notes contain a new entry recording that the refund was registered into refund history (the manual states refund history is captured even with the No Automatic Refund setting — verify this is the case).
- Crucially, because **No Automatic Refund** was set, no additional cancellation logic re-fires (the subscription was cancelled by admin click in 04.2, not by the refund).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.6 — Customer-side visibility
**Steps:**
1. Log in as `cust4@example.com`.
2. Open **My Account → Orders** and click into the parent order.
3. Open **My Account → Subscriptions** and click into the subscription.

**Expected Result:**
- The order shows as Refunded (or Completed with refund line) in My Account → Orders, and the refund amount is visible.
- The subscription detail page on the customer portal shows the refund in the Refund History section (if displayed) — record the exact wording.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After Task 04, restore **Refund on Cancellation** to **Allow Immediate Refund** so Tasks 05 and 06 begin from the documented default.
- Verify no admin/customer email about a refund was duplicated (one Subscription Cancelled email at the time of cancel, not two).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
