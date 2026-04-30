# Stage 05 — Task 12: Stripe Webhook Verification & Payment-Method Lifecycle

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Automatic Payments — Stripe Gateway (deep verification) |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | Task 01, Task 11 |

> **Scope note:** PayPal and Paddle are **out of scope** for this regression cycle. The original Task 12 covered all three gateways; it has been replaced with a deeper Stripe-only verification because Stripe is the only automatic-payment gateway exercised in this run. Stripe is **already configured** by the developer (test-mode keys + webhook secret saved). Do not perform any Stripe setup steps in this task — only verify behaviour.

## Objective

Confirm that Stripe webhooks land correctly in ArraySubs, that customer-side payment-method updates rotate the saved Stripe token on the subscription, and that the **Card Expiring Soon** scheduled email fires when a saved test card is set to expire next month. Also verify the Pro **Gateway Health Dashboard** webhook event log records each test event.

## Pre-conditions

- ArraySubsPro is active.
- Stripe is connected (verify under **WooCommerce → Settings → Payments → ArraySubs Stripe** that the status reads **Connected (Test Mode)**).
- The webhook secret is saved (Stripe CLI or Stripe Dashboard test-mode webhook endpoint pointing at `/wp-json/arraysubs/v1/stripe/webhook`).
- A customer `cust1@test.local` exists.
- The catalog from Stage 03 is available — specifically **Standard Weekly $19.99/wk** and **Pro Plan $19.99/wk**.
- BACS / COD remain enabled as a manual fallback.
- DevTools console open and `wp-content/debug.log` reachable.

## Test Data

- Stripe test cards:
  - `4242 4242 4242 4242` — generic success
  - `4000 0027 6000 3184` — SCA / 3-D Secure challenge
  - `4000 0000 0000 0341` — declines on every renewal (use to force a `payment_intent.payment_failed` event)
  - `5555 5555 5555 4444` — Mastercard test card (used for payment-method rotation)
- Stripe Dashboard test-mode URL bookmarked

## Sub-Tasks

### Sub-Task 12.1 — Confirm Stripe is connected (no setup)

**Steps:**
1. As Administrator, open **WooCommerce → Settings → Payments**.
2. Locate the **ArraySubs Stripe** row.
3. Click **Manage**.
4. Read the connection status block.

**Expected Result:**
- The status block reads **Connected (Test Mode)** with the developer-supplied account name visible.
- Webhook URL is shown and points at `/wp-json/arraysubs/v1/stripe/webhook`.
- Webhook secret status reads **Saved**.
- No "Connect Stripe" CTA button is shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.2 — Successful purchase generates `payment_intent.succeeded`

**Steps:**
1. In Incognito, log in as `cust1@test.local`.
2. Buy **Standard Weekly $19.99/wk**.
3. Pay with `4242 4242 4242 4242` (any future expiry, any CVC).
4. Complete checkout.
5. As Administrator, open the Stripe Dashboard test-mode **Developers → Events** view.
6. Open **ArraySubs → Audits → Gateway Health Dashboard**.

**Expected Result:**
- Stripe test-mode dashboard shows a `payment_intent.succeeded` event for the purchase amount.
- ArraySubs Gateway Health Dashboard webhook event log shows the same event with status **Processed** and a link to the matching subscription.
- The new subscription's **Payment Gateway Card** shows the saved Stripe token (last4 `4242`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.3 — Forced renewal failure generates `payment_intent.payment_failed`

**Steps:**
1. As Administrator, open one of the customer's existing subscriptions.
2. Detach the current Stripe payment method.
3. Attach a Stripe payment method created from `4000 0000 0000 0341` (use the customer portal **Update Payment Method** flow if needed).
4. Edit the subscription's `_next_payment_date` to one minute in the past.
5. Trigger the Action Scheduler queue (run **Tools → Scheduled Actions → Run** on the next due `arraysubs_process_renewal` action).
6. Watch the Stripe test-mode events view.
7. Refresh the Gateway Health Dashboard webhook log.

**Expected Result:**
- Stripe test-mode dashboard records a `payment_intent.payment_failed` event for the renewal amount.
- ArraySubs Gateway Health Dashboard log shows the same event with status **Processed** and the failure reason category captured on the subscription.
- The customer receives a **Payment Failed** email triggered by the Stripe failure event.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.4 — Card Expiring Soon notice fires for a near-expiry test card

**Steps:**
1. Identify (or create) a customer subscription paid via Stripe whose saved card expiry is set to the **last day of next month**. (Use the Stripe Customer dashboard to update the card's expiry on the test PaymentMethod, OR re-tokenise with a card whose expiry month is next month.)
2. As Administrator, run the action scheduler hook `arraysubs_check_expiring_cards` (or wait for its scheduled cron tick).
3. Open the customer's inbox (or the WP mail logger).
4. Open **ArraySubs → Audits → Gateway Health Dashboard** → **Card Health** (or the corresponding admin notice surface).

**Expected Result:**
- A **Card Expiring Soon** email is delivered to the customer with the correct card last4 and expiry month.
- Admin sees a corresponding card-health notice (count of subscriptions with a card expiring within 30 days).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.5 — Customer updates payment method from the portal — token rotates

**Steps:**
1. As `cust1@test.local`, go to **My Account → Subscriptions → View** for an Active Stripe-paid subscription.
2. Click **Update Payment Method** (the link that goes to the WC payment-methods endpoint or the gateway-managed flow, depending on settings).
3. Add a new card: `5555 5555 5555 4444` (Mastercard test card).
4. Set it as default for the subscription.
5. As Administrator, refresh the subscription's Payment Gateway card.
6. Refresh the Stripe test-mode dashboard for the customer.

**Expected Result:**
- The saved Stripe token on the subscription rotates to the new PaymentMethod ID.
- The Payment Gateway card shows last4 `4444` (Mastercard).
- The previous PaymentMethod is detached or marked as not-default in the Stripe customer.
- A `customer.updated` or `payment_method.attached` event lands in Stripe events; the Gateway Health Dashboard log records it.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.6 — Detach card from admin and confirm subscription falls back to manual

**Steps:**
1. As Administrator, open the same subscription used in 12.5.
2. In the Payment Gateway card click **Detach Card**.
3. Confirm the action.
4. Reload and look at the subscription's payment-method state.
5. Look at any auto-renew / manual-fallback indicators.

**Expected Result:**
- The Stripe token meta is cleared.
- The subscription is moved into **manual fallback** mode (next renewal will generate a pending invoice with a payment link instead of attempting an automatic charge).
- A subscription note records: *"Stripe payment method detached by admin."*

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 12.7 — Webhook event log pagination + filtering

**Steps:**
1. After the previous sub-tasks, open **ArraySubs → Audits → Gateway Health Dashboard → Webhook Event Log**.
2. Filter by event type `payment_intent.succeeded`.
3. Reset filter and paginate to page 2 (if the log has > 1 page).
4. Click an event row and inspect the JSON payload preview.

**Expected Result:**
- Filtering returns only the chosen event type.
- Pagination loads the next page without an error.
- The JSON preview matches what Stripe Dashboard shows for the same event.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks

- DevTools console must remain clear of JavaScript errors throughout each Stripe interaction.
- `wp-content/debug.log` must not show PHP notices triggered by webhook handlers.
- The customer must NOT be double-charged in any sub-task — verify totals on the Stripe customer page after each.
- All references to PayPal and Paddle are intentionally absent from this run; if you encounter any setting that says "PayPal" or "Paddle" while testing, leave it untouched and flag it in Fail Notes for awareness only.

## Sign-off

- Tester:
- Date:
- Browser & version:
- Stripe events observed (IDs):
- Notes:
