# Stage 03 — Task 06: Simple Fixed-Length Subscription (6 Cycles)

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Subscription Length |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 03.01 |

## Objective
Create a `Fixed-Length Weekly (6 cycles)` subscription priced at $24.99/week that automatically expires after 6 billing cycles. Verify the Subscription Length value persists, that the storefront product page shows the cycle count, and that the checkout summary displays `6 billing cycles` as the duration.

## Pre-conditions
- Stage 03.01 complete.
- Cart empty.

## Test Data
- Product name: `Fixed-Length Weekly (6 cycles)`
- Regular price: `24.99`
- Billing Period: `Week`, Interval: `1`
- Subscription Length: `6`
- Trial Length: `0`
- Sign-up Fee: `0`

## Sub-Tasks

### Sub-Task 06.1 — Create product with fixed length
**Steps:**
1. **Products → Add New**.
2. Name: `Fixed-Length Weekly (6 cycles)`. Regular price: `24.99`.
3. Tick **Subscription**.
4. Open **Subscription** tab.
5. Set Billing Period `Week`, Interval `1`.
6. Set **Subscription Length** to `6`.
7. Click **Publish**.

**Expected Result:**
- Save succeeds.
- Subscription Length accepts `6`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.2 — Confirm persistence
**Steps:**
1. Refresh the edit screen and reopen Subscription tab.

**Expected Result:**
- Subscription Length still `6`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.3 — Verify product page length text
**Steps:**
1. Open product page in incognito.

**Expected Result:**
- Recurring price `$24.99 / week`.
- Subscription length copy visible — equivalent to `6 billing cycles` (per manual: "Subscription length | When length > 0 | 5 billing cycles" example).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.4 — Verify checkout duration text
**Steps:**
1. Add to cart and **Proceed to checkout**.
2. Read the subscription summary.

**Expected Result:**
- Summary contains a Duration line equivalent to `6 billing cycles` (matches the manual reference text).
- Today's charge equals `$24.99` (no signup fee, no trial).
- Next charge date approximately one week away. Final renewal lands ~6 weeks from start.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.5 — Boundary check: Subscription Length range
**Steps:**
1. Return to the product edit screen.
2. Try setting Subscription Length to `366` and click **Update**.
3. After failure (or value clamp), set it back to `6`.
4. Try setting Length to `0` and **Update** — confirm save passes (0 = never expires).
5. Restore Length to `6` and **Update**.

**Expected Result:**
- `366` is rejected or clamped (range per manual: 0–365).
- `0` saves successfully and product page shows no length text.
- After restoring `6`, all references show `6 billing cycles` again.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.6 — Verify cart line metadata
**Steps:**
1. Add to cart, open the cart page.

**Expected Result:**
- Line item metadata contains the cycle-count detail.
- `$24.99 / week` price text visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Empty cart afterward.
- Confirm Length 0 product (`Standard Weekly`) shows no duration line in its checkout summary, while this product's summary explicitly mentions `6 billing cycles`.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
