# Stage 05 — Task 05: Different Renewal Price Summary

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout — Different Renewal Price Tiers |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | Task 01 |

## Objective
Confirm that when a subscription product has a "different renewal price" tier configured (first N payments at one price, then a second price), the checkout subscription summary shows the manual's exact wording: **"$19.99 every 1 week for the first 3 payments, then $29.99 every 1 week"** (substituting the configured prices and counts). This verifies the *Different renewal price tiers* row described in the Subscription Checkout manual.

## Pre-conditions
- Customer `customer-stepped@example.test` exists.
- Product `Stepped Weekly` is configured as: simple subscription, first **3 payments** at `$19.99/week`, then `$29.99/week` thereafter.
- ArraySubs → Settings → General Settings is in default state.
- Manual gateway (Direct bank transfer) is enabled.

## Test Data
- Product: `Stepped Weekly` (first 3 at `$19.99`, then `$29.99`, every 1 week).
- Customer: `customer-stepped@example.test`.
- Today's expected charge: `$19.99` (the introductory price).
- Expected summary line wording: **`$19.99 every 1 week for the first 3 payments, then $29.99 every 1 week`**.

## Sub-Tasks

### Sub-Task 5.1 — Verify product configuration in admin
**Steps:**
1. As Administrator, open the `Stepped Weekly` product in **Products → All Products**.
2. Open the **General** subscription tab and the **Pricing** tab.
3. Confirm:
   - Recurring price: `$19.99` per week.
   - Different renewal price toggle: **On**.
   - Different renewal price: `$29.99`.
   - After how many payments: `3`.

**Expected Result:**
- The product is saved with these exact values.
- The configuration UI accepts both prices and the count without validation errors.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.2 — Add product to cart
**Steps:**
1. Log in Incognito as `customer-stepped@example.test`.
2. Add `Stepped Weekly` to cart.
3. Go to the cart page.

**Expected Result:**
- Cart shows the product line at `$19.99` (the current introductory price).
- Cart total at the bottom shows `$19.99`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.3 — Inspect the subscription summary on checkout
**Steps:**
1. Click **Proceed to checkout**.
2. Read the subscription summary inside the order review table.

**Expected Result:**
- Product name: `Stepped Weekly` (x1).
- The summary contains a line that matches the manual's "Different renewal price tiers" row format: **`$19.99 every 1 week for the first 3 payments, then $29.99 every 1 week`**.
- Today's charge: `$19.99`.
- Next charge line: today + 7 days.
- Authorization notice present.
- No `Free trial` or `Signup fee` lines (the product has neither).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.4 — Place the order
**Steps:**
1. Fill billing details, select Direct bank transfer.
2. Click **Place order**.
3. Note the order ID.

**Expected Result:**
- Order placed at `$19.99`.
- Related Subscriptions row shows status Pending or Active and recurring `$19.99 / week` (the introductory price applies to the first three cycles).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.5 — Verify the subscription's billing information card
**Steps:**
1. As Administrator, mark the order **Completed**.
2. Open the subscription via **ArraySubs → Subscriptions → View Details**.
3. Inspect the Billing Information card.

**Expected Result:**
- Recurring Amount: `$19.99` (the current effective price).
- A **Different Renewal Price** row reads `$29.99`.
- An **Applies After** row reads `3` (payments).
- Billing Schedule: `Every 1 week(s)`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 5.6 — Confirm wording in customer portal overview
**Steps:**
1. Log back in as `customer-stepped@example.test`.
2. Go to **My Account → Subscriptions** and open the new subscription.
3. Inspect the overview table.

**Expected Result:**
- The Recurring Amount row shows `$19.99` with the billing schedule `every 1 week`.
- The detail page does not contradict the checkout wording (the rolloff to `$29.99` after 3 payments is a future event; today's view shows the current price).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-open the cart and re-trigger the AJAX update (e.g., apply and remove a coupon code) — the summary must keep the "for the first 3 payments, then" wording intact across AJAX refreshes.
- Compare the wording in the Block Checkout (if reproduced) — the same line must appear in identical phrasing per the Subscription Checkout manual ("Cart validation, subscription creation, and plan-switching logic work identically in both flows").
- Confirm the New Subscription email body (after marking Completed) shows the recurring amount at `$19.99` and the next payment date.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order ID created:
- Subscription ID created:
- Notes:
