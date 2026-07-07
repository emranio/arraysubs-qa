# Stage 21 — Task 10: Variable Product — Per-Variation Flexible Sync Checkout

| Key | Value |
|---|---|
| Stage | Flexible Renewal Sync |
| Module | Variation-level config end to end |
| Plugin Coverage | Free engine + Pro |
| Estimated Time | 35 min |
| Depends On | Stage 21 Task 01 (uses `FRS Variable`) |

## Objective
Prove flexible sync is a **per-variation** setting: a flex-enabled variation checks out with segment behavior while a sibling variation without flex behaves as a normal anniversary subscription — through a complete manual-gateway checkout for each, plus one Stripe spot-check.

## Pre-conditions
- `FRS Variable` per Task 01: variation **Silver** ($30/month) with flexible sync ENABLED, boundaries putting today in **segment 2**; variation **Gold** ($50/month) with flexible sync DISABLED.
- Direct bank transfer + Stripe (test) enabled; global sync OFF; fresh customer per run.

## Test Data
- Silver expected today: `round(30 × (L − D)/L, 2)`; next payment = 1st of next month.
- Gold expected today: **$50.00**; next payment = today + 1 month (anniversary).

## Sub-Tasks

### Sub-Task 10.1 — Product page variation switching
**Steps:**
1. Open the `FRS Variable` product page; switch between Silver and Gold.

**Expected Result:**
- Price/subscription strings update per variation; no console errors while switching.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.2 — Silver (flex, segment 2) full checkout — manual
**Steps:**
1. Select **Silver**, add to cart, checkout with Direct bank transfer; place order; open the subscription.

**Expected Result:**
- Today's charge = pre-computed prorated value with the "prorated until the synced renewal date" wording; order total matches.
- Subscription: next payment = boundary; `_renewal_sync_first_charge_mode = prorate`; Recurring $30.00; `_variation_id` = Silver's ID.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.3 — Gold (no flex) full checkout — manual
**Steps:**
1. Fresh session: select **Gold**, checkout with Direct bank transfer; place order; open the subscription.

**Expected Result:**
- Today's charge **$50.00**, next charge **today + 1 month** (anniversary).
- Subscription has **no** `_renewal_sync_*` meta; next payment = anniversary date.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.4 — Silver via Stripe (spot-check)
**Steps:**
1. Fresh session: Silver → Stripe test card checkout.

**Expected Result:**
- PaymentIntent amount = the prorated value; subscription identical to 10.2 (Active immediately, saved payment method present).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.5 — Variation exclusivity spot-check
**Steps:**
1. In admin, set a **Trial Length = 3** on Silver only; save; reload the product page and checkout preview for Silver. Reset to 0 after.

**Expected Result:**
- Silver behaves as a trial (free today, no sync rows); Gold unaffected. The Silver variation's flex section is hidden in admin while the trial is set.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Both subscriptions appear correctly in ArraySubs admin list with their variation names.
- `debug.log` unchanged.
- Record the Silver subscription ID if a variation renewal run is wanted in Task 07 style.

## Sign-off
- Tester:
- Date:
- Browser & version:
- D / L / Silver boundaries:
- Silver order/sub IDs (manual + Stripe), Gold order/sub ID:
- Notes:
