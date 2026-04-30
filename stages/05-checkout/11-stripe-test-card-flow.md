# Stage 05 — Task 11: Stripe Test Card Flow (Pro)

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Automatic Payments — Stripe Gateway |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | Task 01, Task 03 (trial flow) |

## Objective
With the Stripe gateway in test mode, confirm:
1. A subscription purchase using `4242 4242 4242 4242` completes via Stripe Checkout Session and saves the payment method on the subscription (PaymentIntent flow for paid orders).
2. A trial subscription with $0 today flows through SetupIntent rather than PaymentIntent (per manual: *"Stripe cannot charge $0, so... ArraySubs creates a SetupIntent instead of a PaymentIntent"*).
3. The SCA test card `4000 0027 6000 3184` triggers a 3D Secure challenge inside the Stripe Checkout Session and the customer must complete authentication before the order can finish.

## Pre-conditions
- ArraySubsPro plugin is active.
- **WooCommerce → Settings → Payments → ArraySubs Stripe** has:
  - **Enable/Disable**: Enabled.
  - **Test Mode**: Enabled.
  - Test publishable key and test secret key entered.
  - Webhook secret entered (the test mode webhook secret from Stripe Dashboard).
  - Webhook endpoint configured in the Stripe Dashboard at `https://<this-site>/wp-json/arraysubs/v1/webhooks/arraysubs_stripe` with at least these events: `checkout.session.completed`, `payment_intent.succeeded`, `setup_intent.succeeded`, `payment_intent.requires_action`.
- Stripe Dashboard test mode is open in another tab to inspect Customer / PaymentIntent / SetupIntent records.
- Customer `customer-stripe@example.test` exists (separate test user with no prior trials).
- Customer `customer-stripe-sca@example.test` exists.
- Customer `customer-stripe-trial@example.test` exists, no prior trials.
- Products `Basic Monthly` and `Trial 14-Day` are published.

## Test Data
- Successful card: `4242 4242 4242 4242`, any future expiry (e.g., `12/34`), CVC `123`, ZIP `42424`.
- SCA-required card: `4000 0027 6000 3184`, future expiry, CVC `123`.

## Sub-Tasks

### Sub-Task 11.1 — Confirm Stripe is enabled in test mode and webhooks are healthy
**Steps:**
1. As Administrator, open **ArraySubs → Automatic Payments → Gateway Health Dashboard** (or equivalent).
2. Confirm Stripe shows **Connected** with **Test mode** indicator and the webhook endpoint shows recent successful events (or "Awaiting first event").
3. Cross-check the webhook URL in Stripe Dashboard → Developers → Webhooks matches the URL displayed on the Gateway Health Dashboard.

**Expected Result:**
- Stripe is Active and in Test mode.
- Webhook endpoint URL matches.
- No "Webhook secret missing" or "Connection error" warnings.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.2 — Successful paid checkout with `4242 4242 4242 4242`
**Steps:**
1. In Incognito, log in as `customer-stripe@example.test`.
2. Add `Basic Monthly` to cart.
3. Open the checkout page.
4. Select **ArraySubs Stripe** (or whatever the title is configured as) as payment method.
5. Click **Place order**.
6. The customer is redirected to a Stripe-hosted Checkout Session page.
7. Enter card `4242 4242 4242 4242`, expiry `12/34`, CVC `123`, ZIP `42424`.
8. Click **Pay** (or Stripe's Subscribe button).

**Expected Result:**
- Stripe Checkout Session loads successfully (no blank page).
- Payment is accepted instantly.
- Customer is redirected back to the order received page on the store.
- Order total is `$29.99` and order status is **Processing** or **Completed**.
- Related Subscriptions row shows status **Active** with the next payment date today + 1 month.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.3 — Verify Stripe customer + payment method saved on subscription
**Steps:**
1. As Administrator, open the new subscription's **View Details** page.
2. Locate the **Payment Gateway** card.
3. Cross-check the Stripe Dashboard for the same Customer ID.

**Expected Result:**
- Payment Gateway card shows:
  - Gateway: `Stripe`.
  - Connection Status: `Connected` (green).
  - Card on File: `Visa ending in 4242`.
  - Expiry: `12/34` (or the configured value).
  - Customer ID: format `cus_...` matching the Stripe Dashboard.
  - Last Transaction ID: format `pi_...` (PaymentIntent), not `seti_...` (SetupIntent).
- Stripe Dashboard shows the same Customer ID and a PaymentIntent of `$29.99` succeeded.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.4 — Trial checkout uses SetupIntent (not PaymentIntent)
**Steps:**
1. In a fresh Incognito session, log in as `customer-stripe-trial@example.test`.
2. Add `Trial 14-Day` to cart (today's total = `$0.00`).
3. Proceed to checkout, select Stripe, click **Place order**.
4. On the Stripe Checkout Session page, enter card `4242 4242 4242 4242`.
5. Confirm the page wording says something equivalent to "Save card for future" / "$0 today" — Stripe shows a setup-only charge.
6. Submit.

**Expected Result:**
- The order completes with `$0.00` total and the customer is redirected back.
- The new subscription's status is **Trial**.
- The Payment Gateway card on the subscription shows a Last Transaction ID with prefix `seti_` (SetupIntent), not `pi_`.
- In Stripe Dashboard, you see a SetupIntent (succeeded) for this customer with no associated charge.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.5 — SCA / 3D Secure challenge using `4000 0027 6000 3184`
**Steps:**
1. In a fresh Incognito session, log in as `customer-stripe-sca@example.test`.
2. Add `Basic Monthly` to cart.
3. Proceed to checkout, select Stripe, click **Place order**.
4. On the Stripe Checkout Session page, enter card `4000 0027 6000 3184`, expiry `12/34`, CVC `123`.
5. Submit the form.

**Expected Result:**
- Stripe presents an in-page 3D Secure modal (e.g., "Complete authentication" step) before the payment can succeed.
- Click **Complete authentication** in the Stripe modal.
- The payment now succeeds and the customer is redirected back to the store.
- The order is marked Processing/Completed and a subscription is created with status Active.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.6 — Verify SCA-related metadata
**Steps:**
1. Open the SCA subscription's detail page.
2. Inspect the Payment Gateway card and Subscription Notes.
3. Cross-check Stripe Dashboard: PaymentIntent should have `requires_action` then `succeeded` events.

**Expected Result:**
- Payment Gateway card: Card on File `Visa ending in 3184`, Connection Status `Connected`.
- A subscription note (system) records that an SCA / authentication step was required and was satisfied.
- No "payment_intent.requires_action" event is left unresolved on the order.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.7 — Negative test: cancel the Stripe Checkout Session
**Steps:**
1. In a fresh Incognito session, place a fourth checkout but click the back / cancel arrow on the Stripe Checkout Session page (do not submit a card).
2. Watch where you land back on the store.

**Expected Result:**
- The customer is returned to the cart or checkout page (per Stripe's `cancel_url`).
- No subscription is created.
- The order remains **Pending payment** (or is cancelled by WooCommerce based on configured cleanup).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the webhook events table (Gateway Health Dashboard) shows `checkout.session.completed`, `payment_intent.succeeded` (or `setup_intent.succeeded` for trial), and (for SCA) at least one `payment_intent.requires_action` followed by `payment_intent.succeeded`.
- Confirm no PHP fatal errors appear in `wp-content/debug.log` during any of the Stripe flows.
- Re-open the customer's `My Account → Subscriptions` and confirm the Payment Method row shows the Stripe gateway with the matching last 4 digits.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Paid order ID + subscription ID:
- Trial order ID + subscription ID:
- SCA order ID + subscription ID:
- Notes:
