# Stage 04 — Task 06: One Subscription Per Customer with Auto-Migrate

| Key | Value |
|---|---|
| Stage | 04 — Cart Validation Rules |
| Module | Subscription Checkout / Plan Switching |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | Stage 03.10 (Basic Plan / Pro Plan / Enterprise Plan products) |

## Objective
With **One subscription per customer** and **Auto-migrate on checkout** both enabled, a customer who already holds an active subscription and buys a different switchable subscription product must be migrated in place — no second subscription is created. Verify the eligibility checks, the prorated today's-charge, and the post-payment subscription state.

The plan ladder used here is `Basic Plan` ($9.99/wk) → `Pro Plan` ($19.99/wk). Both are weekly so a real renewal can fire inside the regression session if the tester wants to chain the test through Stage 06 / 07.

> The end-to-end proration math is covered in Stage 05. This task confirms the cart/checkout side: that the customer is not blocked from buying a second eligible product, and that the second order does not produce a second subscription.

## Pre-conditions
- Stage 03.10 complete: `Basic Plan`, `Pro Plan`, `Enterprise Plan` exist; `Pro Plan` lists `Enterprise Plan` as upgrade target / `Basic Plan` as downgrade target.
- `cust1@example.com` exists.
- Plan switching is enabled at **ArraySubs → Settings → Plan Switching → Enabled = On**.
- Cart empty.

## Test Data
- Settings to enable for this test (note defaults so we can restore):
  - `Multiple Subscriptions → One subscription per customer` — Enable.
  - `Multiple Subscriptions → Auto-migrate on checkout` — Enable.
  - `Plan Switching → Allow upgrades` — On (default).
  - `Plan Switching → Proration type` — `Prorate immediately` (default).
- Subject products: cust1's existing subscription on `Basic Plan` ($9.99/wk); checkout upgrade target `Pro Plan` ($19.99/wk).

## Sub-Tasks

### Sub-Task 06.1 — Configure settings
**Steps:**
1. **ArraySubs → Settings → General Settings**.
2. Enable **One subscription per customer**.
3. Enable **Auto-migrate on checkout**.
4. **ArraySubs → Settings → Plan Switching**: confirm **Enabled = On**, **Allow upgrades = On**, **Proration type = Prorate immediately**.
5. Save all changes.

**Expected Result:**
- All four settings persist.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.2 — Seed cust1 with an active `Basic Plan` subscription
**Steps:**
1. Log in as cust1.
2. Buy `Basic Plan` ($9.99/wk). Use a test gateway (Stripe test or Cash on Delivery) and complete checkout.
3. Confirm the resulting subscription is in status `Active` (or `Pending → Active` after payment).

**Expected Result:**
- cust1 has exactly one active subscription on `Basic Plan`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.3 — Add `Pro Plan` to cart as cust1 (eligible upgrade)
**Steps:**
1. Still logged in as cust1, open `Pro Plan` storefront page.
2. Click **Add to cart**.
3. Open the cart.

**Expected Result:**
- Add-to-cart succeeds (no "Only one subscription plan" error) because the system detects an eligible plan switch.
- Cart shows `Pro Plan` as a single line item.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.4 — Verify checkout migration messaging
**Steps:**
1. Proceed to checkout.
2. Read the order review and subscription summary.

**Expected Result:**
- Checkout summary indicates a plan switch / replacement, including text like `Replaces your current Basic Plan subscription` (per manual).
- Today's charge equals the **prorated amount due today**, NOT the full `$19.99`.
- The new recurring price `$19.99 every week` is shown for after the switch.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.5 — Complete payment and confirm in-place migration
**Steps:**
1. Place the order with the test gateway.
2. After completion, open **ArraySubs → Subscriptions** as admin.
3. Filter to cust1's subscriptions.

**Expected Result:**
- Customer still has **one** subscription record (the originally-Basic Plan subscription updated to Pro Plan), not two.
- The subscription's product is now `Pro Plan`, recurring amount `$19.99 every week`.
- An order note appears on the new order: `Existing subscription updated from checkout migration` (per manual).
- Subscription has the related plan-switch order linked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.6 — Negative check: disable Auto-migrate, re-attempt
**Steps:**
1. As admin, disable **Auto-migrate on checkout**. Save.
2. As cust1 (now on `Pro Plan` from 06.5), try to add `Enterprise Plan` to cart.

**Expected Result:**
- With Auto-migrate disabled, the eligibility chain breaks. Per manual eligibility list (#6 = Auto-migrate on checkout), one of these now happens:
  - The cart blocks with `Only one subscription plan can be checked out at a time.` (because **One subscription per customer** is still on), OR
  - The product is added but checkout creates a second subscription.
- Document which path occurs in the test record.
- Re-enable Auto-migrate on checkout afterward.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.7 — Restore defaults / cleanup
**Steps:**
1. Restore default values: **One subscription per customer = Off** (or whichever is the documented default for your env), **Auto-migrate on checkout = Off** (default), Plan Switching settings unchanged.
2. Empty cart.
3. Note in the test record which subscriptions were left active for cust1 so Stage 05 can pick up cleanly.

**Expected Result:**
- Settings restored to defaults.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Stage 05 will exercise the proration math and Apply-at-Renewal path. This task confirms cart/checkout migration only.
- Per manual: "Plan switching at checkout only works when the customer has exactly one live subscription." If cust1 ends up with multiple active subscriptions during testing, reset by cancelling extras before continuing.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
