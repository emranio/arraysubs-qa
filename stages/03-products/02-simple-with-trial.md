# Stage 03 — Task 02: Simple Subscription with 7-Day Trial

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Trial Handling |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 03.01 |

## Objective
Create a `Trial Weekly` simple subscription priced at $19.99/week with a 7-day free trial. Verify the trial fields persist, that the storefront product page shows trial copy, and that the cart and checkout reflect "Free (trial starts today)" today's-charge text.

## Pre-conditions
- Stage 03.01 complete (`Basic Monthly` exists).
- Cart is empty in the test browser session.
- A test customer that has never purchased a subscription is available (use guest checkout if customer not yet seeded).

## Test Data
- Product name: `Trial Weekly`
- Regular price: `19.99`
- Billing Period: `Week`, Interval: `1`, Length: `0`
- Trial Length: `7`
- Trial Period: `Day`
- Sign-up Fee: `0`

## Sub-Tasks

### Sub-Task 02.1 — Create the product
**Steps:**
1. **Products → Add New**.
2. Name: `Trial Weekly`.
3. Regular price: `19.99`.
4. Tick **Subscription**.
5. Open **Subscription** tab.
6. Set Billing Period `Week`, Interval `1`, Length `0`.
7. Set Trial Length `7` and Trial Period `Day`.
8. Click **Publish**.

**Expected Result:**
- Product publishes; no validation errors.
- Trial Length field accepts `7` and Trial Period dropdown stays on `Day`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.2 — Verify trial field persistence
**Steps:**
1. Refresh the edit screen.
2. Open **Subscription** tab.

**Expected Result:**
- Trial Length is still `7`, Trial Period still `Day`.
- Sign-up Fee remains `0`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.3 — Verify product page trial display
**Steps:**
1. Open the product permalink in an incognito window.
2. Read the subscription info area below the title.

**Expected Result:**
- Recurring price shows `$19.99 / week`.
- Trial copy is visible — equivalent to `7-day free trial`.
- No signup-fee text appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.4 — Verify cart trial display
**Steps:**
1. Click **Add to cart** on the product page.
2. Open the cart page.
3. Inspect the line item area and order subtotal.

**Expected Result:**
- Item line shows price with billing schedule (`$19.99 / week`).
- Trial detail (e.g., `Free trial: 7 days`) appears in the item metadata or subtotal area.
- No `Subscription Signup Fee` line in the fees section.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.5 — Verify checkout trial summary
**Steps:**
1. Click **Proceed to checkout**.
2. Locate the order review table and the subscription summary block beneath it.

**Expected Result:**
- Order review item shows recurring price + schedule.
- Subscription summary shows:
  - Recurring amount `$19.99 every week`.
  - Free trial duration `7 days`.
  - Today's charge equivalent to `Free (trial starts today)`.
  - A next charge date 7 days in the future.
- Authorization notice text is visible (begins with `By completing this purchase, you authorize us to charge`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.6 — Negative check: trial fields hidden when length 0
**Steps:**
1. Open the `Basic Monthly` product (from 03.01) in another tab.
2. Open the Subscription tab and confirm Trial Length is `0`.
3. View the storefront page for `Basic Monthly`.

**Expected Result:**
- No trial copy on `Basic Monthly` product page.
- Confirms trial display is gated on Trial Length > 0.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Empty the cart after Sub-Task 02.5 so later tasks start clean.
- Confirm the WooCommerce default currency symbol is used throughout.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
