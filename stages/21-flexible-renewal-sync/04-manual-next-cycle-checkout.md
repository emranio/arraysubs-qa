# Stage 21 — Task 04: Manual Gateway — Segment 3 (Charge Full for Next Billing Cycle)

| Key | Value |
|---|---|
| Stage | Flexible Renewal Sync |
| Module | Checkout + subscription creation (first-charge mode `next_cycle`) |
| Plugin Coverage | Free engine + Pro feature |
| Estimated Time | 30 min |
| Depends On | Stage 21 Task 01 |

## Objective
Run a complete Direct-bank-transfer checkout for a purchase day inside **segment 3** and verify the defining behavior: full charge today that **covers the next cycle** — first renewal lands one full cycle past the upcoming boundary, **no invoice/renewal exists at the first boundary**, and the customer keeps access from purchase day (bonus days until the covered cycle starts).

## Pre-conditions
- `FRS Monthly 30` boundaries set so **`b2 < D`** (e.g. D=8 → `3 / 6`); all segments active; product updated.
- Direct bank transfer enabled; global sync setting OFF.
- Fresh customer session.

## Test Data
- Product: `FRS Monthly 30` ($30.00 / month).
- Expected first charge: **$30.00**.
- Expected covered cycle start: **1st of next month** (call it `B1`).
- Expected next payment: **1st of the month after next** (`B2 = B1 + 1 month`).

## Sub-Tasks

### Sub-Task 4.1 — Checkout summary explains the covered cycle
**Steps:**
1. Add the product, open checkout, read the item rows.

**Expected Result:**
- **Today's charge:** `30.00$` (no proration wording).
- **Next charge:** `B2` with `(30.00$)` — i.e. the month **after** next, not `B1`.
- A **First billing cycle** row: *"Today's payment covers the full billing cycle starting `B1` — you get the remaining days until then as bonus access."*
- **First full renewal** row shows `B2 (30.00$)`. Order Total = **$30.00**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Place order; subscription schedule skips the first boundary
**Steps:**
1. Pay with Direct bank transfer; open the linked subscription in admin.

**Expected Result:**
- **Next Payment = `B2`** (not `B1`).
- Meta: `_renewal_sync_first_charge_mode = next_cycle`, `_renewal_sync_first_full_renewal_date = B2`, `_renewal_sync_cycle_start_date = B1` (start of the **paid** cycle).
- Recurring Amount = $30.00.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — No invoice at the skipped boundary
**Steps:**
1. Mark the order **Processing** → subscription Active.
2. Tools → Scheduled Actions → search the subscription ID.

**Expected Result:**
- The only pending renewal jobs are for **`B2`** (invoice ~6 h before, process at `B2`).
- **Nothing** is scheduled at or around `B1`; no pending renewal order exists for `B1`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Customer-facing dates
**Steps:**
1. As the customer, open **My Account → Subscriptions** and the subscription view.

**Expected Result:**
- Next payment shows `B2`; the subscription is Active immediately (access from today).
- No renewal invoice email arrives for `B1` at any point (re-verify when running Task 07).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Cart page shows the same *First billing cycle* note as checkout.
- Order item meta records `next_cycle` mode and `B2`.
- `debug.log` unchanged. Record the subscription ID for **Task 07** (it must renew at `B2`, then `B2 + 1 month`).

## Sign-off
- Tester:
- Date:
- Browser & version:
- D / boundaries used:
- B1 (covered cycle start) / B2 (first renewal):
- Order ID / Subscription ID:
- Notes:
