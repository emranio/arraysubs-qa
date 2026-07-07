# Stage 21 — Task 06: Stripe — Segment 3 (Next Cycle) & Minimum-Charge Edge

| Key | Value |
|---|---|
| Stage | Flexible Renewal Sync |
| Module | Checkout via Stripe (test mode) — next_cycle mode + gateway minimum safeguard |
| Plugin Coverage | Pro |
| Estimated Time | 35 min |
| Depends On | Stage 21 Tasks 01, 05 |

## Objective
Verify segment 3 end to end on Stripe (full charge today, first renewal pushed one cycle past the boundary, no invoice at the skipped boundary), and verify the **gateway minimum-charge safeguard**: a prorated first charge that would fall below Stripe's minimum (~$0.50 USD) is bumped up to the minimum when the full price is at or above it, and left as-is when even the full price is below the minimum.

## Pre-conditions
- Stripe test mode working (Task 05 baseline). If card submission is blocked, mark BLOCKED with issue reference.
- `FRS Monthly 30` per Task 01. Create one extra product for the edge case: `FRS Tiny 0.60` — Simple, Virtual, subscription, **$0.60 / month**, flexible sync enabled, boundaries forcing **segment 2** for today.
- Global sync setting OFF; fresh customer per run.

## Test Data
- Run A: `FRS Monthly 30`, boundaries with `b2 < D` (segment 3) → charge **$30.00**, next payment `B2` (boundary + 1 month).
- Run B: `FRS Tiny 0.60`, segment 2 → raw prorated = `round(0.60 × (L − D)/L, 2)`; if that is **< $0.50**, expected charge = **$0.50** (bumped to Stripe minimum).

## Sub-Tasks

### Sub-Task 6.1 — Run A: next_cycle via Stripe
**Steps:**
1. Set `FRS Monthly 30` boundaries for segment 3; update.
2. Fresh customer → Stripe checkout with the test card; open order + subscription in admin.

**Expected Result:**
- Checkout shows Today `30.00$`, Next charge `B2 (30.00$)`, and the **First billing cycle** note for `B1`.
- Stripe PaymentIntent amount = $30.00; order Processing.
- Subscription Active; **Next Payment = `B2`**; mode `next_cycle`; Scheduled Actions show renewal jobs only for `B2` (nothing at `B1`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.2 — Run B: prorated below the Stripe minimum gets bumped
**Steps:**
1. Confirm the raw prorated value for `FRS Tiny 0.60` today is below $0.50 (if the month/day makes it ≥ $0.50, temporarily set the price to $0.60 and boundaries so the remaining ratio is small — e.g. late-month day with `b1 = 1`).
2. Checkout with **Stripe selected** and read Today's charge.
3. Pay and verify the PaymentIntent amount.

**Expected Result:**
- Today's charge = **$0.50** (not the raw prorated value, not $0.00); Stripe accepts the charge (no "amount too small" API error).
- Subscription recurring amount stays **$0.60**; next payment = boundary.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.3 — Run B control: manual gateway does not bump
**Steps:**
1. Same cart, switch the payment method to **Direct bank transfer** and observe Today's charge.

**Expected Result:**
- With a manual gateway selected the raw prorated value (no Stripe minimum) is shown — the bump is gateway-aware.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.4 — Even-full-below-minimum control (display-level)
**Steps:**
1. Temporarily set `FRS Tiny 0.60` price to **$0.20**, keep segment 2, select Stripe at checkout and observe (do **not** place the order).
2. Restore the price to $0.60 afterwards.

**Expected Result:**
- Today's charge shows the raw prorated value (e.g. $0.10) — no bump, because even the full price is below the minimum. (Placing such an order is expected to fail at Stripe; do not submit.)

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- `debug.log` unchanged; no console errors.
- Record Run A subscription ID for **Task 07**.

## Sign-off
- Tester:
- Date:
- Browser & version:
- D / L / boundaries per run:
- Run A order/sub IDs, B1/B2 dates:
- Run B raw prorated vs charged amount:
- Notes:
