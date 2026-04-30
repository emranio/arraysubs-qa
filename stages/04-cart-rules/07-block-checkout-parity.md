# Stage 04 — Task 07: Block Checkout Parity

| Key | Value |
|---|---|
| Stage | 04 — Cart Validation Rules |
| Module | Subscription Checkout / Block Checkout (Store API) |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | Stages 04.01 – 04.04 |

## Objective
Repeat the four cart validation rule checks (mixed cart, multiple subscriptions, one per product, different billing cycles) using the **Block Checkout** flow (Store API). Confirm that the same exact error strings surface in the block-based UI and that the rule behaviour matches Classic Checkout per the manual statement: "all cart validation errors surface as proper Store API cart errors, ensuring Block Checkout shows the same restrictions as Classic Checkout."

## Pre-conditions
- Stages 04.01 – 04.04 complete on Classic Checkout.
- A Block-based Cart and Checkout page exists on the test site (created from **Pages → Add New → Block Cart / Block Checkout** patterns, or via the WooCommerce default block templates).
- The site uses the block-based Cart and Checkout (or both classic and block pages exist and you can navigate to the block versions explicitly via `/cart-block/` and `/checkout-block/` or whichever URLs the site uses).
- All four cart settings start at their defaults.

## Test Data
- Subjects from the new catalog:
  - `Standard Weekly` ($19.99/wk) — primary subscription subject.
  - `Trial Weekly` ($19.99/wk + 7-day trial) — trial summary check.
  - `Basic Plan` ($9.99/wk) — second distinct weekly subscription for the multi-sub rule.
  - `Basic Monthly` ($29.99/mo) — used for the different-billing-cycles pairing (the only monthly product).
  - `Plain Mug` (regular product) — mixed-cart subject.
- Error strings to verify (verbatim):
  - `This order cannot contain subscription and regular products together.`
  - `Only one subscription plan can be checked out at a time.`
  - `Each subscription product can only appear once per order. Reduce the quantity to 1.`
  - `These subscription plans use different billing cycles.`

## Sub-Tasks

### Sub-Task 07.1 — Confirm Block Cart / Checkout pages exist
**Steps:**
1. As admin, go to **Pages → All Pages**.
2. Locate (or create) pages using the block Cart and block Checkout patterns.
3. In an incognito window, visit `/cart-block/` (or wherever the block page lives) and confirm it renders using block-based UI (not the shortcode legacy view).

**Expected Result:**
- Block Cart page renders. Block Checkout page renders.
- Confirm by opening browser devtools and checking for Store API requests to `/wp-json/wc/store/v1/cart`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.2 — Mixed cart rule on Block Checkout
**Steps:**
1. As admin, disable **Allow mixed cart** in General Settings.
2. In incognito, add `Standard Weekly` to the cart.
3. Navigate to `Plain Mug` and click **Add to cart**.
4. Observe the Store API error response and the on-page block error notification.

**Expected Result:**
- The block UI displays a notice with the exact text:
  > `This order cannot contain subscription and regular products together.`
- The Store API response in devtools includes the same error in `errors[].message` or equivalent.
- Re-enable the setting at end.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.3 — Multiple subscriptions rule on Block Checkout
**Steps:**
1. Disable **Allow multiple subscriptions in cart**.
2. Empty cart.
3. Add `Standard Weekly`, then add `Basic Plan` (both weekly so this is purely a multi-sub test, not a billing-cycle test).
4. Observe the block error.

**Expected Result:**
- Block UI shows:
  > `Only one subscription plan can be checked out at a time.`
- Re-enable the setting at end.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.4 — One per product rule on Block Checkout
**Steps:**
1. Enable **One subscription per product**.
2. Empty cart.
3. Add `Standard Weekly` once.
4. On the Block Cart, increase the line quantity to `2` using the +/- controls.
5. Observe the inline error.

**Expected Result:**
- Block cart shows:
  > `Each subscription product can only appear once per order. Reduce the quantity to 1.`
- Quantity reverts to 1.
- Disable the setting at end (default Disabled).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.5 — Different billing cycles rule on Block Checkout
**Steps:**
1. Disable **Allow different billing cycles** (keep multi-sub enabled).
2. Empty cart.
3. Add `Standard Weekly` (Week/1).
4. Add `Basic Monthly` (Month/1) — the canonical weekly+monthly pairing.

**Expected Result:**
- Block UI shows:
  > `These subscription plans use different billing cycles.`
- Re-enable the setting at end.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.6 — Subscription summary block on Block Checkout
**Steps:**
1. With all settings restored, empty cart.
2. Add `Trial Weekly` to cart.
3. Navigate to the block Checkout.
4. Inspect the order review area for the subscription summary block.

**Expected Result:**
- Per manual FAQ: "Subscription summary information is displayed in both [classic and block]." Confirm the recurring amount `$19.99 every week`, trial duration `7 days`, today's charge `Free (trial starts today)`, and next charge date 7 days out are all visible in the block UI.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.7 — Restore defaults
**Steps:**
1. Verify all four cart-rule settings are back to documented defaults:
   - Allow mixed cart = Enabled.
   - Allow multiple subscriptions in cart = Enabled.
   - One subscription per product = Disabled.
   - Allow different billing cycles = Enabled.
2. Empty cart.

**Expected Result:**
- Defaults restored, cart empty.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Capture screenshots of each error notice in the block UI for traceability.
- If the test site lacks block Cart/Checkout pages, file a setup blocker before proceeding to Stage 05.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
