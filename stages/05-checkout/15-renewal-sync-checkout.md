# Stage 05 - Task 15: Renewal Sync Checkout

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout / Renewal Sync |
| Plugin Coverage | Free + Pro gateway cross-check |
| Estimated Time | 45 min |
| Depends On | Stage 02 Task 01, Stage 03 product setup |

## Objective
Verify that **Sync Renewals to Next Billing Cycle** changes only the first checkout charge and the first full renewal date. A monthly subscription bought mid-cycle should charge either a prorated first amount or the full recurring amount today, set the first full renewal to the next billing-cycle boundary, and keep the stored recurring amount at the full product recurring price. Confirm the feature works with manual gateways and Stripe, and that unsupported automatic gateways are not offered for synced-renewal checkout.

## Pre-conditions
- Logged in as Administrator in one browser tab.
- A clean customer exists for each variant:
  - `sync-prorate@example.test`
  - `sync-full@example.test`
  - `sync-stripe@example.test`
- Product `Basic Monthly` exists, is published, costs **$29.99 / month**, has no trial, no signup fee, and is not lifetime.
- Manual payment gateway **Direct bank transfer** is enabled.
- Stripe is configured in test mode if running the Stripe variant.
- If ArraySubsPro gateways such as PayPal or Paddle are active, leave them enabled so gateway filtering can be observed.
- WooCommerce uses the Classic Checkout page for the primary run. Block Checkout parity can be recorded in Regression / Cross-checks if the environment is configured for blocks.

## Test Data
- Product: `Basic Monthly` ($29.99 / month).
- Renewal sync boundary for monthly products: first day of the next calendar month in the site timezone.
- Prorated expected amount: `29.99 * remaining full site-local billing days / days in current monthly cycle`, rounded to store price decimals.
- Full first-charge expected amount: `$29.99`.
- Site timezone: ____
- Purchase date/time for prorate run: ____
- Expected first full renewal date: ____
- Prorated checkout amount observed: ____
- Full-mode checkout amount observed: ____
- Manual order/subscription IDs: ____ / ____
- Stripe order/subscription IDs: ____ / ____

## Sub-Tasks

### Sub-Task 15.1 - Enable renewal sync in prorate mode
**Steps:**
1. Go to **ArraySubs -> Settings -> General**.
2. In **Renewal Sync**, turn **Sync Renewals to Next Billing Cycle** on.
3. Set **First Charge Mode** to **Prorate until the synced renewal date**.
4. Click **Save Settings**.
5. Hard-refresh and confirm both values persisted.

**Expected Result:**
- Renewal sync is enabled.
- First charge mode is `Prorate until the synced renewal date`.
- The section text states manual gateways and Stripe are supported, and trials/lifetime subscriptions are excluded.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 15.2 - Verify prorated manual checkout summary
**Steps:**
1. Open a clean incognito window and log in as `sync-prorate@example.test`.
2. Add `Basic Monthly` to the cart.
3. Open checkout.
4. Select **Direct bank transfer**.
5. Inspect the order review subscription summary and order total.

**Expected Result:**
- The line item/order total is less than `$29.99` unless the test is being run exactly at the billing-cycle boundary.
- The summary says today's charge is prorated until the synced renewal date.
- The summary shows the first full renewal date as the first day of the next calendar month in the site timezone.
- The recurring amount remains the full `$29.99 every 1 month`.
- No trial or lifetime messaging appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 15.3 - Place the prorated manual order and inspect subscription meta
**Steps:**
1. Complete the checkout with **Direct bank transfer**.
2. Record the order ID and subscription ID.
3. In admin, open the WooCommerce order. Mark it **Completed** if the manual gateway leaves it On hold.
4. Open the created subscription in **ArraySubs -> Subscriptions**.
5. Inspect the next payment date, recurring amount, and renewal sync meta. If the UI does not expose the meta, use WP-CLI:
   ```bash
   wp post meta get SUBSCRIPTION_ID _next_payment_date --allow-root
   wp post meta get SUBSCRIPTION_ID _recurring_amount --allow-root
   wp post meta get SUBSCRIPTION_ID _renewal_sync_enabled --allow-root
   wp post meta get SUBSCRIPTION_ID _renewal_sync_first_charge_mode --allow-root
   wp post meta get SUBSCRIPTION_ID _renewal_sync_first_full_renewal_date --allow-root
   wp post meta get SUBSCRIPTION_ID _renewal_sync_initial_recurring_amount --allow-root
   ```

**Expected Result:**
- Order total equals the prorated first charge observed at checkout.
- Subscription status becomes **Active** after the order is completed.
- `_next_payment_date` equals the first day of the next calendar month in UTC storage.
- `_recurring_amount` remains `29.99`.
- `_renewal_sync_enabled` = `yes`.
- `_renewal_sync_first_charge_mode` = `prorate`.
- `_renewal_sync_first_full_renewal_date` equals `_next_payment_date`.
- `_renewal_sync_initial_recurring_amount` equals the prorated first charge.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 15.4 - Verify full first-charge mode
**Steps:**
1. Return to **ArraySubs -> Settings -> General**.
2. Keep renewal sync enabled and change **First Charge Mode** to **Charge the full recurring amount**.
3. Save and hard-refresh.
4. Open a clean incognito window and log in as `sync-full@example.test`.
5. Add `Basic Monthly` to cart, open checkout, and select **Direct bank transfer**.
6. Inspect the order review summary and then place the order.
7. Complete the manual order if needed and open the created subscription.

**Expected Result:**
- Checkout total is exactly `$29.99`.
- The summary says today's charge is the full first charge, not a prorated charge.
- The first full renewal date is still the first day of the next calendar month in the site timezone.
- Subscription `_next_payment_date` equals that synced boundary.
- `_recurring_amount` remains `29.99`.
- `_renewal_sync_first_charge_mode` = `full`.
- `_renewal_sync_initial_recurring_amount` = `29.99`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 15.5 - Verify Stripe support and unsupported gateway filtering
**Steps:**
1. Set **First Charge Mode** back to **Prorate until the synced renewal date**.
2. Save and hard-refresh.
3. Open a clean incognito window and log in as `sync-stripe@example.test`.
4. Add `Basic Monthly` to cart and open checkout.
5. Inspect the payment methods list.
6. Confirm **Stripe** is available.
7. If PayPal, Paddle, or other automatic ArraySubs gateways are active, confirm they are not listed for this checkout.
8. Complete the Stripe checkout with test card `4242 4242 4242 4242`.
9. Record the order ID and subscription ID.
10. Open the subscription in admin and inspect its next payment date and gateway.

**Expected Result:**
- Stripe remains available for synced-renewal checkout.
- Unsupported automatic gateways are hidden before payment.
- If an unsupported gateway is forced by browser manipulation or stale checkout state, checkout validation blocks payment with: `Renewal sync is available for manual payment gateways and Stripe only. Choose a supported payment method to continue.`
- Stripe order total equals the prorated first charge, unless the prorated recurring line is below Stripe's minimum charge for the store currency. In that case the first recurring line is raised only enough to satisfy Stripe's minimum.
- Subscription gateway/payment method is Stripe.
- `_next_payment_date` equals the synced first full renewal date.
- `_recurring_amount` remains `29.99`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 15.6 - Trial and lifetime counterexamples
**Steps:**
1. Add `Trial Weekly` to cart with renewal sync still enabled.
2. Confirm the checkout summary follows the normal trial behavior and does not mention synced renewal proration.
3. If `Lifetime Deal` exists, add it to cart in a clean session and confirm no renewal sync messaging appears.

**Expected Result:**
- Trial products keep the normal trial checkout behavior.
- Lifetime products do not show renewal sync messaging.
- No unsupported gateway filtering occurs unless an eligible synced-renewal subscription product is in the cart.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Repeat one prorated run through Block Checkout if the environment is configured for the checkout block.
- Confirm cart and checkout totals match each other before placing the order.
- Confirm order item meta records `_renewal_sync_enabled`, `_renewal_sync_first_charge_mode`, `_renewal_sync_first_full_renewal_date`, and `_renewal_sync_initial_recurring_amount`.
- Confirm no PHP notices or warnings appeared in `wp-content/debug.log` during checkout.
- Confirm the customer's **My Account -> Subscriptions** page shows the synced next payment date and full recurring amount.
- Leave renewal sync **Off** after this task unless Stage 18 Task 13 will run immediately.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Site timezone:
- Prorate order/subscription ID:
- Full-mode order/subscription ID:
- Stripe order/subscription ID:
- Expected first full renewal date:
- Notes:
