# Stage 21 — Task 09: Exclusivity Rules & Gateway Gating

| Key | Value |
|---|---|
| Stage | Flexible Renewal Sync |
| Module | Feature exclusivity (Different Renewal Price / Trial / Lifetime) + supported-gateway enforcement |
| Plugin Coverage | Free engine + Pro |
| Estimated Time | 45 min |
| Depends On | Stage 21 Task 01 |

## Objective
Prove the feature is **inert** exactly when it must be — Different Renewal Price enabled, Trial configured, Lifetime period — with the section hidden in admin AND anniversary behavior at checkout; and prove **gateway gating**: unsupported gateways (Paddle / PayPal pro gateways) are hidden from checkout whenever a flex-sync product is in the cart, with a validation error as backstop. Also cross-check the interplay with the **global** renewal-sync setting.

## Pre-conditions
- `FRS Monthly 30` per Task 01, flexible sync enabled with all segments active.
- Direct bank transfer + Stripe enabled. If Paddle and/or PayPal pro gateways can be enabled on this environment, enable at least one for Sub-Task 9.4; otherwise mark 9.4 BLOCKED with a note.
- Global renewal-sync setting OFF (until Sub-Task 9.5).

## Test Data
- Expected anniversary next charge: `today + 1 month` (same day-of-month), NOT the 1st.

## Sub-Tasks

### Sub-Task 9.1 — Different Renewal Price wins over flexible sync
**Steps:**
1. Edit the product: enable **Different Renewal Price** (renewal price $25, after 1). Confirm the flex section hides. Update.
2. Fresh customer → checkout; read the rows. Do not place the order.
3. Disable Different Renewal Price again; update.

**Expected Result:**
- Checkout shows `30.00$ first payment, then 25.00$ every 1 month`; **Next charge = today + 1 month** (anniversary); **no** prorate/sync/First-billing-cycle wording; Total $30.00.
- After re-disabling, the flex section is back in admin with its previous config.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.2 — Trial wins over flexible sync
**Steps:**
1. Set **Trial Length = 7** (days) on the product (flex section hides). Update.
2. Fresh customer → checkout; observe; do not place. Reset trial to 0 after.

**Expected Result:**
- Today's charge `Free (trial starts today)`; next charge = trial end (today + 7 days), not a cycle boundary; no sync rows.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.3 — Lifetime hides and disables
**Steps:**
1. Set Billing Period = **Lifetime Deal** (section hides). Update; view the product page/cart. Restore to Month after.

**Expected Result:**
- Lifetime purchase flow unaffected: one-time charge, "Lifetime Deal — No recurring charges", no sync rows anywhere.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.4 — Unsupported gateways are hidden / blocked for flex carts
**Steps:**
1. With Paddle (or PayPal) pro gateway enabled and visible for a normal product checkout (verify with a plain non-subscription or non-flex product first).
2. Put `FRS Monthly 30` (flex enabled) in the cart; open checkout and list the offered payment methods.
3. If technically possible, force-submit with the unsupported method (e.g. re-enable the option via DevTools) and read the error.

**Expected Result:**
- Paddle/PayPal are **absent** from the payment options while the flex product is in the cart; Direct bank transfer + Stripe remain.
- A forced submit is rejected with the renewal-sync gateway validation error (checkout does not process).
- COD remains hidden (pre-existing subscription rule).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.5 — Interplay with the global sync setting
**Steps:**
1. Turn the **global** "Sync renewals to next billing cycle" ON with first-charge mode **Full**.
2. Checkout A: `FRS Monthly 30` (flex enabled, boundaries putting today in **segment 2**) → observe.
3. Checkout B: a plain monthly subscription product **without** flex config → observe.
4. Turn the global setting OFF again.

**Expected Result:**
- A: the **product's segment wins** — prorated first charge (flex overrides the global "full" mode).
- B: global behavior applies — full first charge synced to the boundary.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 9.6 — Mixed cart: flex + non-subscription product
**Steps:**
1. Cart with `FRS Monthly 30` (segment 2) + any simple non-subscription product; checkout with Direct bank transfer.

**Expected Result:**
- Only the subscription line is prorated; the simple product line is normal; totals add up; subscription record matches Task 03 expectations.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-run one Task 02-style checkout at the end to confirm the product is back to its clean flex config (no leftover trial/renewal-price/lifetime settings).
- `debug.log` unchanged.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Gateways available on this environment:
- Global-setting interplay results (A/B):
- Notes:
