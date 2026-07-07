# Stage 21 — Task 05: Stripe — Segment 1 (Full) & Segment 2 (Prorate) Checkout

| Key | Value |
|---|---|
| Stage | Flexible Renewal Sync |
| Module | Checkout via WooCommerce Stripe Gateway (test mode) |
| Plugin Coverage | Pro (StripeDelegate piggybacks the official Stripe plugin) |
| Estimated Time | 40 min |
| Depends On | Stage 21 Task 01; Stripe connected in test mode (Stage 05 Task 11 baseline) |

## Objective
Repeat the segment-1 and segment-2 checkouts with **Stripe** and verify the charge captured by Stripe equals the WooCommerce order total (full or prorated), the payment method is saved for off-session renewals, and the subscription record matches the manual-gateway runs exactly. Renewal sync must treat Stripe as a fully supported gateway.

## Pre-conditions
- Stripe gateway connected in **test mode**; Payment Element renders at checkout. If card submission is still blocked by the Stage 05 Task 11 environment issue (qa/issues #32 / #33), mark the affected sub-tasks BLOCKED and reference the issue numbers.
- `FRS Monthly 30` per Task 01; global sync setting OFF; fresh customer per run.
- Stripe test card `4242 4242 4242 4242`, any future expiry, any CVC.

## Test Data
- Run A (segment 1): boundaries with `b1 ≥ D` → expected charge **$30.00**.
- Run B (segment 2): boundaries with `b1 < D ≤ b2` → expected charge **round(30 × (L − D)/L, 2)** — pre-compute.

## Sub-Tasks

### Sub-Task 5.1 — Run A: full first charge via Stripe
**Steps:**
1. Set boundaries for segment 1; update product.
2. As a fresh customer, add to cart → checkout → select **Stripe / card**, pay with the test card.
3. Note order ID; open it in admin.

**Expected Result:**
- Checkout rows identical to Task 02 (full `30.00$`, next charge = boundary).
- Order paid (Processing), total **$30.00**; Stripe charge/PaymentIntent ID present in order notes/meta.
- In the Stripe test dashboard the PaymentIntent amount = **$30.00**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.2 — Run A: subscription + saved payment method
**Steps:**
1. Open the linked subscription; verify schedule + gateway context meta.

**Expected Result:**
- Active immediately (Stripe pays at checkout); Completed Payments = 1.
- Next Payment = boundary; `_renewal_sync_first_charge_mode = full`.
- Gateway context stored for off-session renewals: `_gateway_customer_id` (cus_…) and `_gateway_payment_method_id` (pm_…) present on the subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.3 — Run B: prorated first charge via Stripe
**Steps:**
1. Set boundaries for segment 2; update product.
2. Fresh customer → checkout with Stripe test card.

**Expected Result:**
- Today's charge row = pre-computed prorated value; order total and the **Stripe PaymentIntent amount both equal the prorated value to the cent**.
- Subscription: next payment = boundary, Recurring Amount = $30.00, mode `prorate`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.4 — Gateway switching refreshes totals
**Steps:**
1. With the segment-2 cart still active, on the checkout page switch between **Direct bank transfer** and **Stripe** payment options.

**Expected Result:**
- The prorated total stays identical for both (both are supported gateways) and the order review refreshes without errors.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Webhook/event handling: order status settles (no stuck "Pending" after successful card payment).
- `debug.log` unchanged; no console errors from the Payment Element page.
- Record both subscription IDs for **Task 07** (Stripe off-session renewal run).

## Sign-off
- Tester:
- Date:
- Browser & version:
- D / L / boundaries per run:
- Run A order/sub IDs + PaymentIntent:
- Run B order/sub IDs + PaymentIntent + expected prorated amount:
- Notes:
