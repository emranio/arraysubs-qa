# Stage 03 — Task 13: Product Experience and Display

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Product Experience |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | 03.01, 03.02, 03.03, 03.04, 03.07 |

## Objective
Audit subscription pricing display end-to-end across the storefront for every previously-created product type. Confirms recurring price text is consistent on the product page, cart, mini-cart, checkout (classic), order confirmation page, and admin order item view.

## Pre-conditions
- Stages 03.01 – 03.04 and 03.07 complete.
- Test browser is using the **Classic Checkout** shortcode page (Block checkout parity is covered in Stage 04).
- Cart starts empty.

## Test Data
- Products under audit (covers a simple weekly, weekly+trial, weekly+signup, stepped weekly, and variable):
  - `Standard Weekly` ($19.99/wk, no trial, no fee)
  - `Trial Weekly` ($19.99/wk, 7-day trial)
  - `Signup Fee Weekly` ($19.99/wk + $4.99 signup fee)
  - `Stepped Weekly` ($19.99/wk for 3 cycles, then $29.99/wk)
  - `PM Tool` variable (Weekly $9.99 / Bi-weekly $14.99)
  - `Basic Monthly` ($29.99/mo, used for the order-confirmation + admin order-item label check because it is the catalog's monthly anchor)

## Sub-Tasks

### Sub-Task 13.1 — Product page display matrix
**Steps:**
1. Open each product's permalink in incognito tabs.
2. For each, confirm the price area renders these elements per the manual table:
   - Recurring price + schedule (always).
   - Trial copy if Trial Length > 0.
   - Signup fee text if Sign-up Fee > 0.
   - Different renewal price tier text if enabled.
3. For `PM Tool`, confirm the subscription info area is empty until a variation is selected, then populates dynamically.

**Expected Result:**
| Product | Expected price-area text |
|---|---|
| Standard Weekly | `$19.99 / week` |
| Trial Weekly | `$19.99 / week` + `7-day free trial` |
| Signup Fee Weekly | `$19.99 / week` + `+ $4.99 signup fee` |
| Stepped Weekly | `$19.99 / week for the first 3 payments, then $29.99 / week` |
| PM Tool (Weekly) | `$9.99 / week` |
| PM Tool (Bi-weekly) | `$14.99 every 2 weeks` |
| Basic Monthly | `$29.99 / month` |

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.2 — Cart display matrix
**Steps:**
1. For each product, add to cart, open the cart, and capture the line price column, line subtotal, and any fees.
2. Empty cart between products.
3. For variable `PM Tool`, run once with Weekly, once with Bi-weekly.

**Expected Result:**
- Item price column shows the recurring price + billing schedule.
- Item subtotal area echoes detailed subscription info.
- For `Signup Fee Weekly`, fees section contains `Subscription Signup Fee` `$4.99`.
- For `Trial Weekly`, item metadata mentions trial.
- For `Stepped Weekly`, line metadata mentions the cycle threshold.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.3 — Mini-cart widget
**Steps:**
1. Add `Standard Weekly` to cart.
2. Open the mini-cart widget (header cart icon or hover dropdown depending on theme).
3. Inspect the line item HTML in the browser inspector.

**Expected Result:**
- Mini-cart shows the recurring price `$19.99 / week` next to quantity.
- The line item element carries the CSS class `arraysubs-mini-cart-item` (per manual).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.4 — Checkout order review + summary block
**Steps:**
1. Empty cart, then add one of each: `Trial Weekly`, `Signup Fee Weekly`, `Stepped Weekly`. (If multiple-subscriptions in cart is currently disallowed by settings, test them one at a time and reset cart between each.)
2. Proceed to checkout.
3. Inspect the order review table and the subscription summary block beneath it.

**Expected Result:**
- Each line shows recurring price + schedule.
- Subscription summary block shows for each subscription: recurring amount, trial duration (if any), signup fee (if any), tiered renewal pricing (if any), today's charge calculation, next charge date, and the authorization notice.
- The authorization notice begins with `By completing this purchase, you authorize us to charge`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.5 — Order confirmation (Thank You) page
**Steps:**
1. Use a test gateway (Cash on delivery / Manual / Stripe test) to actually complete an order with `Basic Monthly`.
2. Land on the Thank You page.

**Expected Result:**
- A `Subscriptions` table is rendered with columns Subscription ID, Status, Next payment, Total.
- Subscription ID is a clickable link to the customer portal subscription detail page.
- Total shows recurring amount + billing schedule (`$29.99 / month`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.6 — Admin order item label
**Steps:**
1. As admin, open the order created in 13.5 in **WooCommerce → Orders**.
2. Inspect the line item name.

**Expected Result:**
- Line item name reads `Basic Monthly (Every month)` (per manual: "the subscription billing schedule is appended to the item name in parentheses").

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.7 — Currency formatting check
**Steps:**
1. In **WooCommerce → Settings → General**, briefly switch currency position from `Left ($19.99)` to `Right with space (19.99 $)`.
2. Reload `Standard Weekly` storefront page.
3. Restore to original currency position.

**Expected Result:**
- Price formatting honours the WooCommerce currency settings (currency symbol, position, decimals) — confirms the price format follows WooCommerce, not a hardcoded format (per FAQ).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Empty cart at the end of the audit.
- If any product page is missing the expected text, capture the rendered HTML for the price area and file a defect referencing the user manual table row.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
