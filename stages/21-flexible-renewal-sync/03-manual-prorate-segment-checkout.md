# Stage 21 — Task 03: Manual Gateway — Segment 2 (Prorate) Checkout

| Key | Value |
|---|---|
| Stage | Flexible Renewal Sync |
| Module | Checkout + subscription creation (first-charge mode `prorate`) |
| Plugin Coverage | Free engine + Pro feature |
| Estimated Time | 30 min |
| Depends On | Stage 21 Task 01 |

## Objective
Run a complete Direct-bank-transfer checkout for a purchase day inside **segment 2** and verify the prorated first charge **to the cent**, the synced summary rows, subscription meta (`mode = prorate`), and that the recurring amount stays at the full price for renewals.

## Pre-conditions
- `FRS Monthly 30` boundaries set so **`b1 < D ≤ b2`** (e.g. D=8 → `5 / 20`); all segments active; product updated.
- Direct bank transfer enabled; global sync setting OFF.
- Fresh customer session (no live subscription for this product).

## Test Data
- Product: `FRS Monthly 30` ($30.00 / month).
- `L` = days in the current month (site timezone), `D` = today's day of month.
- **Expected prorated charge = round(30 × (L − D) / L, 2)** — compute and write it down before checkout (e.g. D=8, July L=31 → 30 × 23⁄31 = **$22.26**).

## Sub-Tasks

### Sub-Task 3.1 — Checkout summary shows the prorated amount
**Steps:**
1. Add `FRS Monthly 30` to cart, open checkout.

**Expected Result:**
- **Today's charge:** `<prorated>$ prorated until the synced renewal date` — matching the pre-computed value exactly.
- **Next charge:** 1st of next month `(30.00$)`.
- **Recurring** row shows `30.00$ Every month` and **First full renewal** shows the boundary date `(30.00$)`.
- Order **Total = the prorated amount** (virtual product, no tax/shipping deltas).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Place order and verify order record
**Steps:**
1. Pay with **Direct bank transfer**; note order ID.
2. Admin → order: check line item total and item meta.

**Expected Result:**
- Order line total and order total = prorated amount, to the cent.
- Item meta: `_renewal_sync_enabled = yes`, `_renewal_sync_first_charge_mode = prorate`, `_renewal_sync_initial_recurring_amount` = prorated amount, `_renewal_sync_first_full_renewal_date` = boundary.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Subscription record
**Steps:**
1. Open the linked subscription; verify via detail page + WP-CLI.

**Expected Result:**
- **Next Payment = 1st of next month**; **Recurring Amount = $30.00 (full, NOT the prorated value)**.
- `_renewal_sync_first_charge_mode = prorate`; `_renewal_sync_cycle_start_date` = 1st of current month.
- Mark the order **Processing** → subscription **Active**, next payment unchanged, Completed Payments = 1.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Quantity scaling spot-check
**Steps:**
1. New session/customer: add `FRS Monthly 30` with **quantity 3**; open checkout (do not place the order unless recording IDs for Task 07).

**Expected Result:**
- Today's charge = `3 × unit prorated` (unit rounded to 2 decimals first, then × 3).
- Next charge shows `(90.00$)`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Change the cart quantity back and forth on the cart page — totals recompute correctly.
- My Account → Subscriptions shows full $30.00 recurring (not prorated).
- `debug.log` unchanged. Record the subscription ID for **Task 07**.

## Sign-off
- Tester:
- Date:
- Browser & version:
- D / L / boundaries used:
- Pre-computed prorated amount:
- Order ID / Subscription ID:
- Notes:
