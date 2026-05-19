# Stage 18 — Task 09: Different Renewal Price Threshold

| Key | Value |
|---|---|
| Stage | 18 — Renewal Follow-up & Time-travel |
| Module | Billing → Renewal Operations (different renewal price feature) |
| Plugin Coverage | Free + Pro |
| Estimated Time | 50 min |
| Depends On | Task 18.02 (manual renewal cycle verified) |

## Objective
On a **Stepped Weekly** subscription with **Different Renewal Price** configured ($19.99/wk for the first 3 cycles, then $29.99/wk afterwards), time-travel through **6 successful weekly renewals**. Verify renewals **1–3 charge $19.99** each, renewal **4 charges $29.99** (the price switches automatically when the **completed-payments counter** reaches the threshold of 3), and renewals 5–6 also charge $29.99. Also verify that zero-total renewal orders (e.g. fully covered by store credit) do NOT increment the counter — only orders with `total > 0` advance it.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- Customer `member-stepped@example.com` has an Active subscription on **Stepped Weekly** with the following stored on the subscription:
  - Recurring price = $19.99
  - Different renewal price = $29.99
  - Apply renewal price after = 3 payments
  - Billing period = weekly (every 1 week)
  - Completed-payments counter = 0 (fresh subscription, no paid renewals yet)
- Subscription paid by Stripe with saved card `4242 4242 4242 4242`. (Manual payment also acceptable but slower — record method used: ____.)
- Store-credit feature available on this site (Pro). If store credit is not available, skip Sub-Task 09.6 and note the skip.
- Time-travel approach from Task 18.01 ready.
- Mail catcher reachable.

## Test Data
- Test subscription ID: ____
- Recurring (intro) price: $19.99 / week
- Different renewal price: $29.99 / week
- Threshold: 3 payments
- Initial completed-payments counter: 0
- Payment method used: Stripe / manual / ____
- Store-credit balance to apply for Sub-Task 09.6: $29.99 (enough to zero out one cycle at the post-threshold price)

## Sub-Tasks

### Sub-Task 09.1 — Capture starting state and verify product config
**Steps:**
1. Open the test subscription detail page.
2. Confirm:
   - Status = Active
   - Recurring price = $19.99
   - Different Renewal Price card shows $29.99 with "Applies after 3 payments"
   - Completed payments counter = 0
3. Open the **Stepped Weekly** product edit screen and confirm the same settings on the product (every 1 week, $19.99 → $29.99 after 3 cycles).

**Expected Result:**
- Subscription and product are correctly configured for an intro-pricing test.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.2 — Renewal #1 — confirm $19.99 charge
**Steps:**
1. Apply the Task 18.01 time-travel procedure: set `_next_payment_date` to one hour ago.
2. Run `arraysubs_generate_upcoming_renewals` and `arraysubs_process_renewal` from Scheduled Actions.
3. Wait 30 seconds.
4. Refresh the subscription detail page.
5. Open the new renewal order in WooCommerce.

**Expected Result:**
- Renewal order #1 created and paid.
- Order total = **$19.99**.
- Subscription completed-payments counter = **1**.
- `_next_payment_date` advanced by 1 week.
- Payment Successful email received.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.3 — Renewal #2 — confirm $19.99 charge
**Steps:**
1. Apply the time-travel procedure again: `_next_payment_date` → one hour ago.
2. Run the renewal hooks.
3. Refresh and inspect the new renewal order.

**Expected Result:**
- Renewal order #2 created and paid.
- Order total = **$19.99** (counter is 1, threshold is 3 — still under threshold).
- Counter = **2**.
- Next payment date advanced.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.4 — Renewal #3 — confirm $19.99 charge (last intro renewal)
**Steps:**
1. Time-travel and run renewal hooks again.
2. Refresh and inspect.

**Expected Result:**
- Renewal order #3 created and paid.
- Order total = **$19.99** (counter is 2 < 3 at the moment the price is selected).
- After payment, counter = **3** (now equals threshold).
- Next payment date advanced.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.5 — Renewal #4 — verify price switches to $29.99
**Steps:**
1. Time-travel and run renewal hooks again.
2. Refresh the subscription detail page.
3. Open the new renewal order.

**Expected Result:**
- Renewal order #4 created and paid.
- Order total = **$29.99** (counter was 3 ≥ threshold 3, so price selection logic uses Different Renewal Price).
- Counter = **4**.
- Next payment date advanced.
- Subscription detail page shows the recurring amount updated (or notes the switch in the activity log).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.6 — Verify a store-credit-zeroed renewal does NOT increment the counter
**Steps:**
1. Apply store credit of $29.99 to this customer (admin → ArraySubs → Members → store credit panel, or **WooCommerce → Smart Coupons / Store Credit** depending on your stack). Record the credit ID: ____.
2. Time-travel `_next_payment_date` to one hour ago.
3. Run `arraysubs_generate_upcoming_renewals` to create the next renewal invoice. The system should apply the store credit, zeroing the order total.
4. If the system does not auto-apply store credit at renewal, manually apply it to the pending renewal order via the order edit screen.
5. Once the order total is $0, complete the order (mark Completed in WooCommerce).
6. Refresh the subscription detail page.

**Expected Result:**
- Renewal order #5 paid with total = **$0** (store credit covered it).
- Subscription completed-payments counter remains at **4** (NOT incremented to 5) because total was 0.
- Next payment date still advanced by 1 week.
- This confirms the documented guard: "completed-payments counter only increments when money actually changed hands."

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.7 — Renewal #6 — verify price is still $29.99 after the zeroed cycle
**Steps:**
1. Time-travel and run renewal hooks one more time.
2. Inspect the new renewal order total.

**Expected Result:**
- Renewal order #6 created and paid at $29.99 (counter is 4 ≥ 3, still under different renewal price).
- Counter = **5** (incremented because total > 0).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.8 — Verify Action Scheduler hooks and audit
**Steps:**
1. Go to **WooCommerce → Status → Scheduled Actions**.
2. Filter Status = Complete and Hook contains `arraysubs_`.
3. Verify multiple completed `arraysubs_generate_upcoming_renewals` and `arraysubs_process_renewal` rows tied to this subscription's ID across the test.
4. Open Scheduled-Job Logs (Pro). Confirm Success rows for each of the renewals run.
5. Open Activity Audits and confirm each renewal recorded an Order entity audit row.

**Expected Result:**
- All hooks observed.
- Audit trail complete and consistent with the 6 renewal cycles tested (counter sequence: 0 → 1 → 2 → 3 → 4 → 4 → 5).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- The `_recurring_amount` meta on the subscription should now be $29.99 (after the threshold was crossed) — confirm this on the detail page.
- Confirm the customer portal shows the new recurring amount of $29.99 for any future renewals.
- Confirm the Different Renewal Price card on the subscription detail page now shows the threshold satisfied (e.g., "Applied — all future renewals at $29.99").
- Confirm that changing the Stepped Weekly product's prices in WooCommerce does NOT alter this subscription's stored values (prices are locked at checkout).
- Cancel any orphan pending renewal orders after the test if downstream stages need a clean state.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Test subscription ID:
- Counter sequence observed (record each value): 0 → ____ → ____ → ____ → ____ → ____ → ____
- Order totals observed (record each value): ____, ____, ____, ____, ____, ____
- Notes:
