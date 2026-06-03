# Stage 18 - Task 13: Renewal Sync First Full Renewal

| Key | Value |
|---|---|
| Stage | 18 - Renewal Follow-up & Time-travel |
| Module | Billing -> Renewal Operations / Renewal Sync |
| Plugin Coverage | Free + Pro Stripe cross-check |
| Estimated Time | 45 min |
| Depends On | Task 18.01, Stage 05 Task 15 |

## Objective
Time-travel a synced-renewal subscription created in Stage 05 Task 15 to its first full renewal date. Verify the renewal invoice/order charges the full recurring amount, the completed-payments counter increments, and the next payment date advances from the synced boundary to the next aligned billing-cycle boundary rather than drifting back to the original checkout day.

## Pre-conditions
- Task 18.01 time-travel procedure is documented and available.
- Stage 05 Task 15 created at least one Active `Basic Monthly` subscription with renewal sync enabled.
- Manual test subscription from the prorate or full-mode run is available and Active.
- Stripe synced-renewal subscription is available if running the Pro automatic payment cross-check.
- Mail catcher is available.
- Action Scheduler is reachable at **WooCommerce -> Status -> Scheduled Actions**.
- Settings -> General -> Renewal Sync may be on or off; existing synced subscriptions must renew according to their stored `_next_payment_date`.

## Test Data
- Manual synced subscription ID: ____
- Manual parent order ID: ____
- Manual original checkout date: ____
- `_renewal_sync_first_charge_mode`: ____
- `_renewal_sync_initial_recurring_amount`: ____
- `_recurring_amount`: ____
- Original `_next_payment_date` / first full renewal date: ____
- Time-travel target: one hour before now
- Renewal order ID: ____
- Completed-payments counter before / after: ____ / ____
- Expected second renewal date: first full renewal date + 1 month, aligned to the same billing-cycle boundary
- Stripe synced subscription ID (optional): ____

## Sub-Tasks

### Sub-Task 13.1 - Capture synced subscription baseline
**Steps:**
1. Open the synced `Basic Monthly` subscription from Stage 05 Task 15.
2. Confirm the subscription is **Active**.
3. Record:
   - `_next_payment_date`
   - `_recurring_amount`
   - `_renewal_sync_enabled`
   - `_renewal_sync_first_charge_mode`
   - `_renewal_sync_first_full_renewal_date`
   - `_renewal_sync_initial_recurring_amount`
   - completed-payments counter
4. If the UI does not expose these values, use WP-CLI:
   ```bash
   wp post meta get SUBSCRIPTION_ID _next_payment_date --allow-root
   wp post meta get SUBSCRIPTION_ID _recurring_amount --allow-root
   wp post meta get SUBSCRIPTION_ID _renewal_sync_enabled --allow-root
   wp post meta get SUBSCRIPTION_ID _renewal_sync_first_charge_mode --allow-root
   wp post meta get SUBSCRIPTION_ID _renewal_sync_first_full_renewal_date --allow-root
   wp post meta get SUBSCRIPTION_ID _renewal_sync_initial_recurring_amount --allow-root
   ```

**Expected Result:**
- `_renewal_sync_enabled` = `yes`.
- `_next_payment_date` equals `_renewal_sync_first_full_renewal_date`.
- `_recurring_amount` equals the full product recurring amount (`29.99` for `Basic Monthly`).
- `_renewal_sync_initial_recurring_amount` reflects the first checkout charge and may differ from `_recurring_amount` in prorate mode.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.2 - Time-travel to the first full renewal date
**Steps:**
1. Use the time-travel approach from Task 18.01 to set `_next_payment_date` to one hour in the past.
2. Refresh the subscription detail page.
3. Confirm the subscription remains **Active**.

**Expected Result:**
- The synced subscription is now due for renewal.
- No status change occurs from editing the date alone.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.3 - Generate the renewal invoice/order
**Steps:**
1. Go to **WooCommerce -> Status -> Scheduled Actions**.
2. Search for `arraysubs_generate_upcoming_renewals`.
3. Click **Run** on the next pending row.
4. Wait 5 seconds.
5. Refresh the subscription detail page and inspect **Related Orders**.
6. Open the newly created renewal order.

**Expected Result:**
- A new pending renewal order is linked to the subscription.
- Renewal order total is the full recurring amount (`$29.99` for `Basic Monthly`), not the prorated first checkout amount.
- Order notes identify it as a renewal order for the subscription.
- Subscription remains **Active**.
- Scheduled-Job Logs (Pro) shows a Success row for **Generate Upcoming Renewals** if Pro is active.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.4 - Complete the manual renewal payment
**Steps:**
1. Open the renewal invoice email in the mail catcher and copy the payment link.
2. In an incognito window, open the payment link as the subscription customer.
3. Pay with **Direct bank transfer**.
4. In admin, open the renewal order and mark it **Completed** if it is On hold.
5. Refresh the subscription detail page.

**Expected Result:**
- Renewal order status is **Completed**.
- Payment Successful email is sent.
- Completed-payments counter = baseline + 1.
- `_pending_renewal_order_id` is cleared.
- Subscription status remains **Active**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.5 - Verify renewal date remains aligned
**Steps:**
1. Read the subscription's new `_next_payment_date`.
2. Compare it with the first full renewal date recorded in Sub-Task 13.1.
3. Confirm the new date is exactly one monthly interval after the synced first full renewal date.
4. Confirm the day-of-month is still the billing-cycle boundary, not the original checkout day.

**Expected Result:**
- New `_next_payment_date` = first full renewal date + 1 month.
- For monthly `Basic Monthly`, the new date is the first day of the following month in site-local display.
- The subscription does not drift back to the original purchase day.
- The recurring amount remains `29.99`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.6 - Optional Stripe automatic renewal cross-check
**Steps:**
1. Open the synced Stripe subscription from Stage 05 Task 15.
2. Record the same baseline values as Sub-Task 13.1.
3. Time-travel `_next_payment_date` to one hour in the past.
4. Run `arraysubs_generate_upcoming_renewals`.
5. Run the scheduled `arraysubs_process_renewal` action for that subscription if it does not run automatically.
6. Wait for the Stripe round-trip and refresh the renewal order.
7. Inspect the subscription's new `_next_payment_date`.

**Expected Result:**
- Stripe renewal order charges the full recurring amount.
- Stripe payment succeeds with the saved test payment method.
- Completed-payments counter increments.
- New `_next_payment_date` advances one aligned monthly cycle from the synced boundary.
- Stripe webhook/payment logs show success if Pro gateway health logging is active.

**Pass Criteria:** [ ] PASS [ ] FAIL [ ] SKIP
**Fail Notes:**

## Regression / Cross-checks
- Confirm no duplicate renewal order is created for the same due date.
- Confirm the renewal order does not reuse `_renewal_sync_initial_recurring_amount` as its total.
- Confirm the customer-facing **My Account -> Subscriptions** page shows the updated aligned next payment date.
- Confirm no Subscription On-Hold, Payment Failed, or Cancelled emails were sent during the successful manual path.
- Confirm `wp-content/debug.log` contains no renewal-sync PHP warning or fatal error during the test window.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Manual subscription ID:
- Renewal order ID:
- Counter before / after: ____ / ____
- First full renewal date:
- New next payment date:
- Stripe subscription ID (if tested):
- Notes:
