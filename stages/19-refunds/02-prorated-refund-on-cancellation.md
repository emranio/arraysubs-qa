# Stage 19 — Task 02: Prorated Refund on Cancellation (Standard Weekly)

| Key | Value |
|---|---|
| Stage | 19 — Refund Management |
| Module | Refunds / Prorated |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 01 — Refund settings, Stage 06 — Initial lifecycle |

## Objective
On a **Standard Weekly $19.99/wk** subscription that is exactly halfway through its current 7-day billing cycle, with **Allow Immediate Refund** + **Allow Prorated Refunds** + **Automatic Gateway Refund** all enabled, cancel from the admin and verify the prorated refund amount is calculated as approximately **$10.00** using the formula `(Recurring Amount / Days in Cycle) * Unused Days` = `($19.99 / 7) × 3.5 ≈ $9.995 ≈ $10.00`. Verify the gateway refund is initiated on the most recent paid order via **Stripe**, the refund is visible in WooCommerce, and the refund row appears on the subscription's Related Orders section.

## Pre-conditions
- Task 01 passed — refund settings are at documented defaults.
- The **Standard Weekly** subscription product exists priced at **$19.99 / week** (`_subscription_price=19.99`, `_subscription_period=week`, `_subscription_period_interval=1`).
- A test customer (`cust2@example.com`) has purchased Standard Weekly and the parent order is **Completed** (paid via Stripe with `4242 4242 4242 4242`).
- The subscription's **Next Payment Date** has been adjusted to be exactly **3.5 days from now** and the **Last Payment Date** to **3.5 days ago** — i.e. exactly halfway through a 7-day weekly cycle. (This can be done with the **Edit Subscription** date pickers in admin, or by directly editing `_next_payment_date` and `_last_payment_date` meta.)
- **Stripe is already configured** in Test mode (no setup task in this stage). Note the original Stripe transaction ID on the parent order. PayPal and Paddle are out of scope.
- **ArraySubs → Settings → Refunds** = Allow Immediate Refund, Auto Gateway Refund **Enabled**, Allow Prorated Refunds **Enabled**, Min Refund Amount `0`.

## Test Data
- Recurring amount: $19.99 per week
- Days in cycle: 7
- Daily rate expected: `$19.99 / 7 ≈ $2.856`
- Unused days expected: `3.5`
- Prorated refund expected: **$10.00** (≈ `$2.856 × 3.5 = $9.995`, ±$0.05 rounding tolerance)
- Gateway: **Stripe** (already configured)

## Sub-Tasks

### Sub-Task 02.1 — Preview the prorated calculation
**Steps:**
1. Open **ArraySubs → Subscriptions** and click into the Standard Weekly subscription.
2. Find the **Prorated Refund** action / button (in the Subscription Actions card or a Refunds sub-tab — exact location varies; record where it lives).
3. If the UI offers a **Preview** affordance, click it. Otherwise note the displayed daily rate and unused days values shown in the modal.

**Expected Result:**
- Preview shows: `Days in Cycle: 7`, `Unused Days: 3.5`, `Daily Rate: $2.86` (rounded), `Refund Amount: $10.00` (or $9.99–$10.00 depending on rounding).
- The amount field is pre-filled with `10.00` (or the rounded calculation).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.2 — Issue the prorated refund and cancel
**Steps:**
1. In the prorated refund modal, leave amount at the calculated `$10.00`.
2. Enter reason: `QA prorated test`.
3. Confirm the `Cancel after refund` checkbox is checked (this is the documented default).
4. Click the confirm button (e.g. **Process Refund**).

**Expected Result:**
- A loading state shows briefly, followed by a success toast like `Refund of $10.00 processed`.
- The page either refreshes automatically or shows a refresh hint; either way, the subscription status now displays **Cancelled** with a red badge.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.3 — Verify the WooCommerce refund record (Stripe auto-gateway refund)
**Steps:**
1. Open the parent (or most recent paid renewal) WooCommerce order from the subscription's Related Orders section.
2. Scroll to the order items / refunds area.

**Expected Result:**
- A **Refund** entry of `-$10.00` is shown.
- The refund's reason text reads exactly `QA prorated test` (or the subscription-aware prefix the plugin adds, but `QA prorated test` must appear).
- An order note records the refund, the **Stripe** gateway response (e.g. `Stripe refund re_xxxxx`), and the linkage to the subscription.
- Open the Stripe dashboard for that transaction — a refund of $10.00 USD is visible there with the same `re_` ID.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.4 — Subscription Related Orders table reflects refund
**Steps:**
1. Return to the subscription detail page.
2. Scroll to the **Related Orders** section.

**Expected Result:**
- The order row shows a **Refunded** column with `-$10.00`.
- A summary badge or tag near the top of the section shows the cumulative refunded amount as `$10.00`.
- The refund appears as a sub-row beneath the order with date, amount, and reason.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.5 — Subscription notes and scheduled actions
**Steps:**
1. Scroll to **Subscription Notes** on the subscription detail page.
2. Open **Tools → Scheduled Actions**, filter by `arraysubs_renewal` and the subscription ID.

**Expected Result:**
- Subscription notes contain at least two entries: one recording the refund (`Refund of $10.00 issued — reason: QA prorated test`) and one recording the cancellation triggered by the refund.
- The cancellation note's `cancelled_by` is `system` (or the admin user who clicked Process Refund — record which) and `_cancellation_reason` reflects "Refund issued" or similar wording from the manual.
- No future-dated `arraysubs_renewal` actions remain for this subscription. Any matching actions are either Completed (in the past) or Cancelled.
- A `_end_date` is set on the subscription roughly equal to the time of refund.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.6 — Customer notification
**Steps:**
1. Switch to the customer mailbox / mail-trap inbox for `cust2@example.com`.

**Expected Result:**
- A **Subscription Cancelled** email arrives (from the cancellation triggered by the full refund flow). Subject and body reference the cancelled subscription.
- An email about the refund itself may or may not arrive depending on WooCommerce settings — record whichever you observe.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Verify **WooCommerce → Reports → Orders → Refunds** (or Analytics → Revenue) shows $10.00 of refund activity for today (proves the Stripe gateway refund actually flowed through WC and not just metadata).
- Verify the customer's My Account → Orders page shows the order with refund balance `$10.00`.
- If a payment retry was scheduled before cancellation, confirm it is now Cancelled in Action Scheduler — payment-retry state must be cleared on cancellation.
- Confirm the Stripe Webhook Event Log received a corresponding `charge.refunded` (or equivalent) event.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Stripe refund ID captured:
- Notes:
