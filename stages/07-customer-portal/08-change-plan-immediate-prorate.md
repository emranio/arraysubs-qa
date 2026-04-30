# Stage 07 — Task 08: Change Plan — Immediate Proration

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / Change Plan (Immediate) |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 02 — View Subscription detail, Stage 03 Task 10 (linked products configured) |

## Objective
On a Basic Monthly subscription with linked Pro upgrade and Crossgrade product, click **Change Plan**, verify the modal shows Upgrade/Downgrade and Others tabs, select the Pro plan, verify the proration preview (credit, charge, net, switch fee), confirm the switch, complete checkout, and verify the subscription is updated to the new plan with the correct order note.

## Pre-conditions
- **General Settings → Customer Actions → Allow Plan Switching:** Enabled.
- **Plan Switching → Proration mode:** **Prorate immediately** (admin setting saved).
- **Plan Switching → Switch fee for upgrades:** Set a known non-zero amount, e.g. `$5.00`, so the switch-fee row appears.
- Stage 03 catalog: Basic Plan, Pro Plan, Enterprise Plan linked as upgrades; Crossgrade Plus linked as crossgrade — all weekly so the renewal/proration cycle exercises in real time.
- `cust1@example.com` owns Subscription I — an Active Basic Plan ($9.99/wk) purchased ~3 days ago, mid-billing-cycle, with a payment method on file.

## Test Data
- Switch from: Basic Plan ($9.99/wk)
- Switch to: Pro Plan ($19.99/wk)
- Expected switch fee: `$5.00`

## Sub-Tasks

### Sub-Task 08.1 — Open Change Plan modal
**Steps:**
1. As `cust1@example.com`, open Subscription I's detail page.
2. Click **Change Plan**.

**Expected Result:**
- A modal opens and loads available plan options from the server (a brief loading state may appear).
- Tabs are visible at the top of the modal: **Upgrade/Downgrade** and **Others**.
- Under Upgrade/Downgrade, the Pro Monthly and Enterprise Monthly products are listed.
- Under Others, the Crossgrade product is listed.
- Each option shows the plan name, pricing, and a **View Details** link.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.2 — Select Pro and view proration
**Steps:**
1. On the Upgrade/Downgrade tab, click **Pro Monthly**.

**Expected Result:**
- A proration preview panel appears below the selection containing four labeled rows:
  - **Credit amount** — credit for unused time on Basic (a positive value).
  - **Charge amount** — cost of Pro for the remaining period (a positive value).
  - **Net amount** — what the customer will pay or be refunded today.
  - **Switch fee** — `$5.00` (matches the configured fee).
- A **Confirm Plan Change** button is visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.3 — Confirm and proceed to checkout
**Steps:**
1. Click **Confirm Plan Change**.

**Expected Result:**
- The browser redirects to the WooCommerce checkout page with a switch order in the cart.
- The order total on checkout matches the **Net amount + Switch fee** from the preview.
- The cart line item references the Pro Monthly product and indicates this is a switch.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.4 — Complete payment
**Steps:**
1. Use the configured test payment method (Stripe sandbox card `4242 4242 4242 4242` or equivalent).
2. Click **Place Order**.

**Expected Result:**
- The order completes successfully and the customer lands on the Order Received page.
- No payment errors.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.5 — Verify subscription updated to Pro
**Steps:**
1. Navigate back to **My Account → Subscriptions** and open Subscription I.

**Expected Result:**
- The Product row now reads **Pro Monthly**.
- The Recurring Amount row shows the Pro price (`$59.99` per month).
- The Status remains **Active**.
- The Next Payment date is adjusted per immediate-proration rules (record the displayed date).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.6 — Verify order note on the switch order
**Steps:**
1. As Administrator, open the switch order in **WooCommerce → Orders**.
2. Scroll to the Order notes section.

**Expected Result:**
- A note exists with text containing exactly: **"Existing subscription updated from checkout migration"** (the exact phrase from the user manual / system).
- A linked subscription reference back to Subscription I is visible in the order summary.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.7 — Subscription note audit
**Steps:**
1. Open Subscription I in admin → notes timeline.

**Expected Result:**
- A subscription note records the plan switch from Basic Monthly to Pro Monthly with the prorated amounts and switch fee.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm a single switch order was created (not duplicate orders).
- Confirm no pending plan switch banner is visible on Subscription I — the switch already executed.
- The My Subscriptions list shows Subscription I with the new Pro Monthly product name and `$59.99` recurring total.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
