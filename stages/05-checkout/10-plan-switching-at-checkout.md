# Stage 05 — Task 10: Plan Switching at Checkout (Checkout Migration)

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout — Plan Switching |
| Plugin Coverage | Both |
| Estimated Time | 25 min |
| Depends On | Task 01 |

## Objective
Verify the checkout-migration / plan-switching flow described in the manual: a customer with **exactly one** Active Basic subscription adds a Pro subscription product to their cart, and the checkout automatically applies prorated pricing, shows the migration UI ("Replaces your current Basic subscription" + new recurring price), and after payment the existing subscription is **modified in place** (no second subscription is created). The order note "Existing subscription updated from checkout migration" must be added.

## Pre-conditions
- Customer `customer-switch@example.test` exists and currently has **exactly one** subscription, status **Active**, on the `Basic Monthly` product (this can be the same subscription created in Task 01 if you create a new customer for that earlier task; otherwise create a fresh Basic subscription via the admin Add New flow before starting).
- ArraySubs → Settings → General Settings → Plan Switching:
  - **Plan Switching**: Enabled.
  - **Allow upgrades**: Enabled.
  - **Auto-migrate on checkout**: Enabled.
  - **Proration method**: `Prorate immediately` (so a non-zero today's charge is shown).
- ArraySubs → Settings → General Settings → Multiple Subscriptions:
  - **One subscription per customer**: Enabled.
- Product `Pro Monthly` ($49.99/month) is configured with `Basic Monthly` listed on its Linked Products tab as an allowed downgrade source AND `Pro Monthly` is listed on `Basic Monthly`'s Linked Products tab as an allowed upgrade target.
- Manual gateway (Direct bank transfer) is enabled.

## Test Data
- Existing subscription: `Basic Monthly` ($29.99 / month) — Active.
- Target plan: `Pro Monthly` ($49.99 / month).
- Customer: `customer-switch@example.test`.

## Sub-Tasks

### Sub-Task 10.1 — Confirm prerequisites are in place
**Steps:**
1. As Administrator, open **ArraySubs → Subscriptions** and filter Active.
2. Confirm `customer-switch@example.test` has exactly one Active subscription on `Basic Monthly`.
3. Open the `Basic Monthly` product → Linked Products tab → confirm `Pro Monthly` is listed under upgrades.
4. Open `Pro Monthly` → Linked Products tab → confirm `Basic Monthly` is listed under downgrades.
5. Open **General Settings → Plan Switching** and confirm all six eligibility prerequisites listed in the manual are configured.

**Expected Result:**
- All six eligibility conditions per the manual are met:
  1. Customer has exactly one active subscription.
  2. Plan Switching enabled.
  3. Upgrade direction allowed.
  4. New product listed as allowed switch target.
  5. One subscription per customer enabled.
  6. Auto-migrate on checkout enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.2 — Add Pro Monthly to cart as the existing customer
**Steps:**
1. In Incognito, log in as `customer-switch@example.test`.
2. Navigate to the `Pro Monthly` product page.
3. Click **Subscribe Now** (or `Add to cart`).
4. Open the cart page.

**Expected Result:**
- The cart contains `Pro Monthly`.
- A migration / plan-switch notice may appear on the cart page indicating an existing subscription will be replaced.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.3 — Inspect the checkout migration UI
**Steps:**
1. Click **Proceed to checkout**.
2. Read the subscription summary block and any context messages near it.

**Expected Result:**
- The summary shows:
  - The **prorated amount due today**, which is the unused portion of `Basic Monthly` credited against the new `Pro Monthly` price (a positive number less than `$49.99`).
  - A line containing the exact context phrase **"Replaces your current Basic Monthly subscription"** (as documented in the manual: *"Replaces your current [Current Product] subscription"*).
  - The **new recurring price**: `$49.99 every month`.
  - The next charge date: today + 1 month (or the recalculated date based on the new plan).
  - The authorization notice.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.4 — Place the migration order
**Steps:**
1. Fill any required billing fields (email and address are pre-filled).
2. Select Direct bank transfer.
3. Click **Place order**.
4. Note the order ID on the thank-you page.

**Expected Result:**
- Order placed successfully.
- The thank-you page does NOT show a second subscription created — the Related Subscriptions table contains only the original subscription ID, now reflecting the Pro plan.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.5 — Mark order Completed (manual gateway) and confirm switch executed
**Steps:**
1. As Administrator, change the new order's status to **Completed**.
2. Open **ArraySubs → Subscriptions**.
3. Filter by the customer email `customer-switch@example.test`.

**Expected Result:**
- There is **only one** subscription belonging to this customer (no duplicate was created).
- That subscription's product column now shows `Pro Monthly` (not `Basic Monthly`).
- Status: **Active**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.6 — Verify the subscription detail reflects the Pro plan
**Steps:**
1. Open **View Details** on the subscription.
2. Inspect the Product card and the Billing Information card.
3. Inspect the Order History card.
4. Inspect the Subscription Notes panel.

**Expected Result:**
- Product card: `Pro Monthly`.
- Billing Information card: Recurring Amount `$49.99`, Billing Schedule `Every 1 month(s)`.
- Order History: at least two orders are linked — the original `Basic Monthly` initial order AND the migration order from Sub-Task 10.4 (with `Type: Renewal` or a switch-specific label depending on UI).
- Subscription Notes panel contains a system note with the text **`Existing subscription updated from checkout migration`** (per manual exact wording).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.7 — Confirm billing schedule reset to the new plan
**Steps:**
1. Read the Subscription Info card → Next Payment value.
2. Confirm the value reflects the recalculation per the manual: *"The billing schedule resets according to the new product's configuration."*

**Expected Result:**
- Next Payment is one full Pro billing cycle from the migration order date (today + 1 month).
- The trial / different-renewal-price meta from the old plan have been cleared.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- If you start with two active subscriptions instead of one, the checkout must NOT trigger migration — instead, a new subscription is created. Verify by repeating Sub-Tasks 10.2–10.5 with a customer who already has two Active subscriptions and confirming a second subscription was created (per manual: *"Plan switching at checkout only works when the customer has exactly one live subscription"*).
- The customer should receive a relevant email (Plan Changed / Reactivated / Auto-Downgrade depending on the migration type). Record which email was received.
- If proration method = `No proration`, the today's charge equals the full new plan price; if `Apply at renewal`, today's charge is `$0` and the new price applies at next renewal. Confirm the proration method behaved as configured.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Original subscription ID:
- Migration order ID:
- Final subscription product:
- Notes:
