# Stage 03 — Task 03: Simple Subscription with $4.99 Signup Fee

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Sign-up Fee |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 03.01 |

## Objective
Create a `Signup Fee Weekly` product at $19.99/week with a $4.99 one-time signup fee. Verify the signup fee appears as a separate fee line item labeled `Subscription Signup Fee` in the cart, and that today's charge totals $24.98 ($19.99 + $4.99) when no trial is configured.

## Pre-conditions
- Stage 03.01 complete.
- Cart empty in the test browser.

## Test Data
- Product name: `Signup Fee Weekly`
- Regular price: `19.99`
- Billing Period: `Week`, Interval: `1`, Length: `0`
- Trial Length: `0`
- Sign-up Fee: `4.99`

## Sub-Tasks

### Sub-Task 03.1 — Create the product with a signup fee
**Steps:**
1. **Products → Add New**.
2. Name: `Signup Fee Weekly`. Regular price: `19.99`.
3. Tick **Subscription**.
4. Open **Subscription** tab.
5. Set Billing Period `Week`, Interval `1`, Length `0`.
6. Trial Length `0`.
7. Set **Sign-up Fee** to `4.99`.
8. Click **Publish**.

**Expected Result:**
- Product saves with no validation errors.
- Sign-up Fee field accepts the value.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.2 — Confirm field persistence
**Steps:**
1. Refresh the edit screen and reopen the **Subscription** tab.

**Expected Result:**
- Sign-up Fee still shows `4.99`.
- All other fields unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.3 — Verify product page signup-fee text
**Steps:**
1. Open the storefront product page in an incognito window.

**Expected Result:**
- Recurring line shows `$19.99 / week`.
- A signup fee note is visible in the price area, equivalent to `+ $4.99 signup fee`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.4 — Verify cart fee line label
**Steps:**
1. Click **Add to cart**.
2. Open the cart page.
3. Inspect the **Cart totals** / fees section (above tax/total).

**Expected Result:**
- A separate line labeled exactly `Subscription Signup Fee` shows `$4.99`.
- Item subtotal still shows `$19.99 / week` for the line item.
- Cart total reflects $24.98 (subject to taxes if configured).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.5 — Verify checkout summary
**Steps:**
1. **Proceed to checkout**.
2. Locate the order review table and subscription summary.

**Expected Result:**
- Order review shows the recurring item plus a `Subscription Signup Fee` line of `$4.99`.
- Subscription summary block shows signup fee, e.g., `$4.99 one-time fee`.
- Today's charge text equates to `$19.99 + $4.99 signup = $24.98`.
- A next charge date roughly 1 week from today is displayed (no trial).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.6 — Verify signup fee not on renewals (documentation check)
**Steps:**
1. Inside the subscription summary, read the description of charges.
2. Cross-reference with the user manual statement: "The signup fee is only charged on the initial order. It is never applied to renewal orders."

**Expected Result:**
- Today's charge includes the signup fee.
- Next renewal amount displayed in the summary is `$19.99` (signup fee not included on the renewal line).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Empty cart afterwards.
- Verify removing the line item also removes the `Subscription Signup Fee` from the totals.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
