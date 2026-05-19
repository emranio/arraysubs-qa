# Stage 03 — Task 05: Simple Lifetime Deal Subscription

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Lifetime Deal |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 03.01 |

## Objective
Create a `Lifetime Deal` subscription product with the Billing Period set to `Lifetime Deal`. Verify the Billing Interval is forced to `1` and disabled, that the Subscription Length is ignored, and that the checkout summary shows `Lifetime Deal — No recurring charges` instead of a next-charge date.

## Pre-conditions
- Stage 03.01 complete.
- Cart empty.

## Test Data
- Product name: `Lifetime Deal`
- Regular price: `199.00`
- Billing Period: `Lifetime Deal`
- Billing Interval: forced `1`
- Trial Length: `0`
- Sign-up Fee: `0`

## Sub-Tasks

### Sub-Task 05.1 — Create the product
**Steps:**
1. **Products → Add New**.
2. Name: `Lifetime Deal`. Regular price: `199`.
3. Tick **Subscription**.
4. Open **Subscription** tab.
5. Open **Billing Period** dropdown and select `Lifetime Deal`.
6. Click **Publish**.

**Expected Result:**
- Product saves without errors.
- Billing Period dropdown retains the `Lifetime Deal` selection after save.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.2 — Verify interval is locked to 1
**Steps:**
1. After save, look at **Billing Interval** field in the Subscription tab.
2. Try clicking inside the field and entering `3`.

**Expected Result:**
- Billing Interval value is `1`.
- The field is disabled (greyed out / readonly) and rejects input — matches the manual: "When the billing period is set to Lifetime Deal, the interval is automatically set to 1 and cannot be changed."

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.3 — Verify Subscription Length is ignored
**Steps:**
1. Set Subscription Length to `5`.
2. Click **Update**.
3. Reload and check stored value behaviour.

**Expected Result:**
- Per manual: "Subscription length is ignored" for Lifetime Deal. Saving a non-zero length should not cause an error, but the value either persists silently or is reset; either way the lifecycle treats this as a one-time purchase.
- No validation error blocks the save.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.4 — Verify storefront price text
**Steps:**
1. Open the product permalink in an incognito window.

**Expected Result:**
- Price displays as a single one-time amount `$199.00` (no `/month`, no `/year`).
- No trial / signup-fee text.
- Recurring schedule text is absent.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.5 — Verify checkout shows "Lifetime Deal — No recurring charges"
**Steps:**
1. Add to cart and **Proceed to checkout**.
2. Locate the subscription summary block.

**Expected Result:**
- Summary shows `Lifetime Deal — No recurring charges` (per the user manual exact text).
- No next charge date is displayed.
- Today's charge equals `$199.00`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.6 — Cross-check cart
**Steps:**
1. Return to the cart page.

**Expected Result:**
- Item line shows `$199.00` (no `/period` suffix).
- No fees section additions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Toggle Billing Period away from `Lifetime Deal` to `Month` and confirm Billing Interval becomes editable again. Reset back to `Lifetime Deal` before signing off.
- Empty the cart afterward.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
