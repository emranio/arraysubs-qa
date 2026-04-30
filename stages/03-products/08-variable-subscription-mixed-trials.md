# Stage 03 — Task 08: Variable Subscription with Mixed Cadences and Signup Fees

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Variable Subscriptions |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | 03.07 |

## Objective
Create a variable subscription product `Coffee Plan` with three variations — Daily, Weekly, Bi-weekly — each with different prices, signup-fee, and trial configurations. The goal is to prove per-variation independence across all subscription fields including signup fee, trial period unit, and length.

## Pre-conditions
- Stage 03.07 complete.
- Cart empty.
- A custom attribute `Cadence` with terms `Daily`, `Weekly`, `Bi-weekly` (will be created in this task).

## Test Data
- Product name: `Coffee Plan`
- Product type: `Variable product`
- Attribute `Cadence`: `Daily | Weekly | Bi-weekly`
- Variation Daily: Price `2.99`, Period `Day`, Interval `1`, Trial `7` Day, Sign-up Fee `0`, Length `0`
- Variation Weekly: Price `14.99`, Period `Week`, Interval `1`, Trial `0`, Sign-up Fee `4.99`, Length `0`
- Variation Bi-weekly: Price `24.99`, Period `Week`, Interval `2`, Trial `0`, Sign-up Fee `0`, Length `0`

## Sub-Tasks

### Sub-Task 08.1 — Create the parent and attribute
**Steps:**
1. **Products → Add New**. Name `Coffee Plan`. Set type to **Variable product**.
2. Tick **Subscription**.
3. Open **Attributes** tab. Add custom attribute `Cadence` with `Daily | Weekly | Bi-weekly`. Tick **Used for variations**. Save attributes.
4. Click **Save draft**.

**Expected Result:**
- Variable parent saves with Subscription enabled.
- Attribute terms saved.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.2 — Generate three variations
**Steps:**
1. **Variations** tab → **Generate variations** → confirm.
2. Verify three variations exist (Daily, Weekly, Bi-weekly).

**Expected Result:**
- All three variation rows are created.
- Each shows its own subscription field block when expanded.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.3 — Configure Daily variation
**Steps:**
1. Expand Daily.
2. Regular price `2.99`. Period `Day`, Interval `1`, Length `0`.
3. Trial Length `7`, Trial Period `Day`. Sign-up Fee `0`.
4. Click **Save changes**.

**Expected Result:**
- Daily saves; trial fields show `7 Day`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.4 — Configure Weekly variation
**Steps:**
1. Expand Weekly.
2. Regular price `14.99`. Period `Week`, Interval `1`, Length `0`.
3. Trial Length `0`. Sign-up Fee `4.99`.
4. Click **Save changes**.

**Expected Result:**
- Weekly saves with trial = 0 and signup fee `4.99`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.5 — Configure Bi-weekly variation
**Steps:**
1. Expand Bi-weekly.
2. Regular price `24.99`. Period `Week`, Interval `2`. Length `0`.
3. Trial Length `0`. Sign-up Fee `0`.
4. Click **Save changes**.
5. Click **Update** on the parent product.

**Expected Result:**
- Bi-weekly saves with interval 2 and no fee/trial.
- Parent update succeeds.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.6 — Verify per-variation independence after reload
**Steps:**
1. Refresh the edit screen.
2. Expand each variation in turn.

**Expected Result:**
- Daily: $2.99/day, 7-day trial, no fee, length 0.
- Weekly: $14.99/week, no trial, $4.99 signup, length 0.
- Bi-weekly: $24.99 every 2 weeks, no trial, no fee, length 0.
- No cross-contamination of values.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.7 — Storefront dropdown checks
**Steps:**
1. Open the product page in incognito.
2. Cycle the Cadence dropdown through Daily → Weekly → Bi-weekly and observe the price block.

**Expected Result:**
- Daily: `$2.99 / day` plus `7-day free trial`, no signup-fee text.
- Weekly: `$14.99 / week` plus `+ $4.99 signup fee`, no trial text.
- Bi-weekly: `$24.99 every 2 weeks`, no trial text, no signup-fee text.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 08.8 — Spot-check checkout for Weekly
**Steps:**
1. Select Weekly and **Add to cart**.
2. Open cart, then **Proceed to checkout**.
3. Inspect order review and subscription summary.

**Expected Result:**
- A `Subscription Signup Fee` line of `$4.99` appears in fees.
- Subscription summary shows `$14.99 every week`, no trial line, today's charge `$14.99 + $4.99 signup = $19.98`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Empty cart after Sub-Task 08.8.
- Confirm the variable Subscription checkbox at parent level cannot be unticked while variations are configured (per manual: "you cannot make individual variations non-subscription while the parent is a subscription product").

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
