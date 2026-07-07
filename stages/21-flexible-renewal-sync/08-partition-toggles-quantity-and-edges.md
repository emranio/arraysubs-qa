# Stage 21 — Task 08: Partition Toggles, Quantity & Day-Boundary Edges

| Key | Value |
|---|---|
| Stage | Flexible Renewal Sync |
| Module | Segment partition semantics at checkout |
| Plugin Coverage | Pro |
| Estimated Time | 40 min |
| Depends On | Stage 21 Task 01 |

## Objective
Verify the partition rule with disabled segments drives real checkouts: remaining active segments expand over the whole cycle (there is **no anniversary fallback day**), a single-segment plan applies to every day, boundary days map to the correct side, and non-monthly periods (weekly) sync to the store's start-of-week. Manual gateway is sufficient here; math is gateway-independent.

## Pre-conditions
- `FRS Monthly 30` and `FRS Weekly 20` per Task 01; Direct bank transfer enabled; global sync OFF.
- Today's `D` (monthly) and weekly day-of-cycle noted (weeks start on the store's **start of week** — Settings → General; day 1 = that weekday).

## Test Data
- Run A (two segments): disable **Prorate**; actives = Full + Next-cycle; set the single boundary `b` **below** `D` (e.g. D=8 → b=5) → today must resolve to **next_cycle**.
- Run B (two segments flipped): boundary `b ≥ D` → today must resolve to **full**.
- Run C (single segment): only **Prorate** active → every day prorates, today included.
- Run D (boundary day exact): set `b1 = D` (three segments) → day `D` belongs to segment 1 (ranges are inclusive of their end day).
- Run E (weekly): `FRS Weekly 20`, three segments on the 7-day scale, boundaries per today's weekly position.

## Sub-Tasks

### Sub-Task 8.1 — Run A: disabled segment's days are covered by the next active one
**Steps:**
1. Configure Run A; update product; fresh customer → checkout.

**Expected Result:**
- Legend on the product editor shows `1 - 5 / Off / 6 - 30`.
- Checkout: Today `30.00$`, **Next charge = boundary + 1 month (B2)**, *First billing cycle* note present → today resolved to **next_cycle**, NOT to anniversary billing.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.2 — Run B: same two segments, boundary above D → full
**Steps:**
1. Move the single boundary to `b ≥ D`; refresh checkout.

**Expected Result:**
- Today `30.00$ full first charge`, Next charge = **B1** (the upcoming boundary). Mode flips purely by boundary position.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.3 — Run C: single active segment covers all days
**Steps:**
1. Enable only **Prorate amount** (picker shows no thumbs, solid blue bar); update; refresh checkout.

**Expected Result:**
- Today's charge = prorated amount regardless of `D`; next charge = B1.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.4 — Run D: inclusive boundary day
**Steps:**
1. Restore three segments; set `b1 = D` exactly; refresh checkout.
2. Then set `b1 = D − 1`; refresh again.

**Expected Result:**
- With `b1 = D`: **full** (day D is the last day of segment 1).
- With `b1 = D − 1`: **prorate** (day D is now the first day of segment 2).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.5 — Run E: weekly product syncs to start-of-week
**Steps:**
1. Configure `FRS Weekly 20` boundaries so today lands in **segment 2**; fresh customer → checkout; place the order.

**Expected Result:**
- Prorated = `round(20 × (7 − weekday_index)/7, 2)` where day 1 = store start-of-week.
- Next charge/subscription next payment = the **next start-of-week day, 00:00 site time**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.6 — Quantity 3 placed order
**Steps:**
1. `FRS Monthly 30` in segment 2; quantity **3**; place the order via Direct bank transfer.

**Expected Result:**
- Order total = `3 × unit prorated`; subscription quantity 3, Recurring Amount $30.00/unit (renewal order would bill $90.00 — verify line math on the subscription/preview).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Toggling segments in the editor never leaves the picker in a broken state after save/reload.
- `debug.log` unchanged.

## Sign-off
- Tester:
- Date:
- Browser & version:
- D (monthly) / weekly day index:
- Run-by-run expected vs actual amounts:
- Notes:
