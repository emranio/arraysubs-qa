# Stage 04 — Task 05: One Trial Per Customer Rule

| Key | Value |
|---|---|
| Stage | 04 — Cart Validation Rules |
| Module | Subscription Checkout / One Trial Per Customer |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | Stage 03 catalog (Trial Weekly), test customer cust1 |

## Objective
With **One trial per customer** enabled, complete a trial purchase as `cust1`, cancel/expire the resulting subscription, then attempt to start a new trial as the same customer. The second attempt must be blocked with:

> `You have already used a free trial. Free trials are limited to one per customer.`

Repeat the trial flow as a guest (not logged-in) and confirm the guest is **not** blocked, per the manual: "Guest users are not subject to the trial restriction."

## Pre-conditions
- Stage 03.02 complete (`Trial Weekly` with 7-day trial exists).
- Test customer `cust1@example.com` exists and has never bought a trial.
- A test gateway that supports zero-cost trial checkouts is enabled (Cash on Delivery, Manual, or Stripe test mode with payment-method capture).
- Cart empty.

## Test Data
- Setting path: `ArraySubs → Settings → General Settings → Multiple Subscriptions → One trial per customer`.
- Default: Enabled.
- Expected error string (verbatim): `You have already used a free trial. Free trials are limited to one per customer.`

## Sub-Tasks

### Sub-Task 05.1 — Confirm setting is enabled
**Steps:**
1. **ArraySubs → Settings → General Settings**.
2. Verify **One trial per customer** is ON (default). If OFF, enable and save.

**Expected Result:**
- Setting confirmed enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.2 — As cust1, complete a trial purchase
**Steps:**
1. Log in as `cust1@example.com`.
2. Add `Trial Weekly` to cart and proceed through checkout.
3. Use the test gateway and place the order.
4. Confirm the order page shows `Subscriptions` table with status `Trial`.

**Expected Result:**
- Order placed with `$0.00` today's charge (or signup fee only if any) and the new subscription is in `Trial` status.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.3 — Cancel or expire the subscription
**Steps:**
1. As admin, open **ArraySubs → Subscriptions**.
2. Open cust1's new subscription.
3. Cancel it immediately (admin action) or set status to `Expired`.

**Expected Result:**
- Subscription status moves to Cancelled (or Expired).
- Customer no longer has an active subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.4 — Attempt a second trial as cust1
**Steps:**
1. Log in again as `cust1@example.com`.
2. Empty cart.
3. Open `Trial Weekly` and click **Add to cart**.

**Expected Result:**
- Add-to-cart is blocked with the exact text:
  > `You have already used a free trial. Free trials are limited to one per customer.`
- Per manual, this includes cancelled and expired prior subscriptions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.5 — Confirm no-trial product is still allowed for cust1
**Steps:**
1. While logged in as cust1, add `Standard Weekly` (no trial) to cart.

**Expected Result:**
- Add succeeds (rule applies only to products with a trial period).
- Empty cart afterward.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.6 — As guest, trial is allowed
**Steps:**
1. Log out completely.
2. Open the storefront in an incognito window (no session cookies).
3. Add `Trial Weekly` to cart.
4. Proceed to checkout but do NOT complete payment yet.

**Expected Result:**
- No error message.
- Per manual: "Guest users are not subject to the trial restriction because their purchase history cannot be tracked."
- Cart and checkout proceed normally.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.7 — Negative check: disable rule and re-test as cust1
**Steps:**
1. As admin, **disable** **One trial per customer** in settings, save.
2. Log in as cust1.
3. Add `Trial Weekly` to cart.

**Expected Result:**
- No error — the rule is no longer enforced.
- Empty cart afterward.
- **Re-enable the setting** (default Enabled) before signing off.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm an admin viewing cust1's customer page sees the past subscription tied to the trial-used decision.
- Confirm the rule does not fire on `Signup Fee Weekly` (no trial), `Standard Weekly`, `Basic Monthly`, or any product with Trial Length 0.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
