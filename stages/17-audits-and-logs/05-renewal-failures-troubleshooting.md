# Stage 17 — Task 05: Renewal Failures Troubleshooting

| Key | Value |
|---|---|
| Stage | 17 — Audits, Logs & Troubleshooting |
| Module | Audits → Renewal Failures (troubleshooting screen) |
| Plugin Coverage | Free + Pro |
| Estimated Time | 35 min |
| Depends On | Task 17.04 (Stripe gateway connected, webhook event log working) |

## Objective
Force a renewal payment failure using Stripe test card `4000 0000 0000 0341` (decline-on-renewal), then open the **Renewal Failures** troubleshooting screen and verify the failed renewal is listed with subscription, reason category, and the **Retry** and **Mark as resolved** actions. Confirm that the failure category surfaces in the customer-facing **Renewal Payment Failed** email reason callout and in the subscription notes.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- Stripe gateway in Test mode, webhook URL configured.
- Customer `member3@example.com` exists with an Active subscription paid by saved Stripe card.
- Ability to swap the saved card on this subscription to the declining test card `4000 0000 0000 0341`.
- Settings → General Settings: **Active Grace Days = 3**, **On-Hold Grace Days = 7** (defaults).

## Test Data
- Test customer: `member3@example.com`
- Subscription ID: ____
- Original saved card: `4242 4242 4242 4242`
- Declining card to set: `4000 0000 0000 0341` (Stripe decline simulation)
- Original `_next_payment_date`: ____

## Sub-Tasks

### Sub-Task 05.1 — Swap the saved card to the declining card
**Steps:**
1. Log in as `member3@example.com` in incognito.
2. Go to **My Account → Payment Methods**.
3. Add a new payment method with card `4000 0000 0000 0341`, exp `12/34`, CVC `123`, ZIP per locale.
4. Set this card as default.
5. Delete or unset the original `4242` card so the subscription will use the new declining card on its next charge attempt.
6. Confirm in the customer portal that the subscription's payment method now shows the new card last four `0341`.
7. Log out the customer.

**Expected Result:**
- New payment method added; default updated; old card removed.
- Subscription detail page in admin shows the new payment method token attached.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.2 — Trigger the failed renewal
**Steps:**
1. As admin, open the test subscription detail page.
2. Edit `_next_payment_date` to one hour ago.
3. Go to **WooCommerce → Status → Scheduled Actions**.
4. Find the next pending `arraysubs_generate_upcoming_renewals` action and click **Run**.
5. Then find the new `arraysubs_process_renewal` action scoped to this subscription ID and click **Run**.
6. Wait 30 seconds for the gateway round-trip.
7. Navigate to the subscription detail page → **Related Orders** card.

**Expected Result:**
- A renewal order was created with status **Failed** (red) — open the order, confirm it shows the gateway decline note.
- The subscription's notes include a customer-readable failure note such as "Renewal payment failed: ..." with the gateway message.
- Subscription status remains **Active** (still inside the 3-day active grace period).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.3 — Verify the failure category meta and customer email
**Steps:**
1. On the subscription detail page, find the meta `_last_payment_failure_category` (Custom Fields panel).
2. Record the value: ____ (expected: `card_declined` or `generic_decline` for `4000...0341`).
3. Open the customer's inbox for `member3@example.com` (or use a mail catcher / WP Mail logger).
4. Find the **Renewal Payment Failed** email.
5. Confirm the email body includes a one-line reason callout above the order details (e.g., "Reason: the card was declined.").

**Expected Result:**
- `_last_payment_failure_category` is one of the documented categories: `card_declined`, `generic_decline`, `insufficient_funds`, `expired_card`, `incorrect_cvc`, `invalid_card`, `authentication_required`, `processing_error`, `unknown`.
- Customer email shows the reason callout in human-readable form.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.4 — Open the Renewal Failures troubleshooting screen
**Steps:**
1. Navigate to **ArraySubs → Audits [beta] → Renewal Failures** (or the closest equivalent — record exact menu path: ____).
2. Verify the screen lists at least one failed renewal row.
3. Inspect the columns shown for each failure row.

**Expected Result:**
- The failure row contains:
  - Subscription number (clickable link to the subscription)
  - Customer name / email
  - Failure reason category (e.g., `card_declined`)
  - Failed timestamp
  - Action buttons: **Retry** and **Mark as resolved**

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.5 — Test the Retry action with the same failing card
**Steps:**
1. On the failure row, click **Retry**.
2. Confirm any modal/dialog.
3. Wait 30 seconds.
4. Refresh the screen.

**Expected Result:**
- A new payment attempt fires.
- Because the card is still the declining `0341`, the retry fails again — the row remains in the list with an updated "last attempted" timestamp and the same reason.
- The subscription notes record an additional retry attempt.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.6 — Replace the card and Retry — succeed
**Steps:**
1. As admin (or as customer), set the subscription's payment method back to the working test card `4242 4242 4242 4242`.
2. Return to the Renewal Failures screen.
3. Click **Retry** on the failure row.
4. Wait 30 seconds, refresh.

**Expected Result:**
- The retry succeeds.
- The renewal order moves to **Processing** or **Completed**.
- The failure row is automatically removed (or marked resolved).
- Subscription `_next_payment_date` advances by one billing cycle.
- Customer receives **Payment Successful** email.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.7 — Test Mark as resolved on a separate forced failure
**Steps:**
1. Trigger a second failure: swap the card back to `0341`, edit `_next_payment_date` to one hour ago, run the renewal again as in Sub-Task 05.2.
2. Open Renewal Failures and locate the new row.
3. Click **Mark as resolved**.
4. Confirm the action.

**Expected Result:**
- The row disappears from the active failures list (or is greyed out and labelled Resolved).
- The subscription remains in its current state (Active, with the unpaid invoice still pending) — Mark as resolved is administrative dismissal only and does NOT pay the order.
- An audit row in Activity Audits (Author = Admin, Entity = Subscription) records the resolution.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After cleanup, swap the saved card back to the working `4242` card. Pay any outstanding renewal order from the customer side (or cancel it as admin) so the subscription returns to a healthy state for Stage 18.
- Confirm Scheduled-Job Logs (Task 17.03) shows two **Process Renewal** Failed rows for this subscription.
- Confirm Activity Audits (Task 17.01) shows two Subscription/Order entries reflecting the failure events.
- Restore the original `_next_payment_date` value. Record final state: ____

## Sign-off
- Tester:
- Date:
- Browser & version:
- Subscription ID:
- Failure category recorded:
- Notes:
