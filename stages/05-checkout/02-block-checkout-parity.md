# Stage 05 — Task 02: Block Checkout Parity

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Subscription Checkout (Block Checkout / Store API) |
| Plugin Coverage | Both |
| Estimated Time | 20 min |
| Depends On | Task 01 (Classic Checkout) |

## Objective
Verify that Block Checkout (the WooCommerce Store API checkout that uses the `Checkout` block) produces the same subscription summary, surfaces the same cart validation errors, and creates the same subscription record as Classic Checkout. Cart validation, subscription creation, and plan-switching logic must work identically in both flows per the manual.

## Pre-conditions
- A WooCommerce **Checkout (block)** page exists and is set as the active checkout page in **WooCommerce → Settings → Advanced → Page setup**. If the block checkout page does not exist, create one with the **Checkout** block before starting.
- A separate copy of the Classic Checkout page is still reachable at its shortcode URL for the visual comparison in Sub-Task 2.5 (or use Task 01 screenshots).
- Customer `customer-block@example.test` exists and has no existing subscriptions.
- ArraySubs → Settings → General Settings: same as Task 01 (mixed cart on, multiple subscriptions on, auto-create account on, one-click default).
- Manual gateway (Direct bank transfer) is enabled.

## Test Data
- Product: `Standard Weekly` ($19.99 / week).
- Customer: `customer-block@example.test`.
- Payment method: Direct bank transfer (manual).

## Sub-Tasks

### Sub-Task 2.1 — Verify Block Checkout is the active checkout
**Steps:**
1. Log in as Administrator in one window.
2. Go to **WooCommerce → Settings → Advanced → Page setup** and confirm the Checkout page is the one containing the Checkout block.
3. Open the page in the editor briefly to confirm it contains the Checkout block (not the `[woocommerce_checkout]` shortcode).

**Expected Result:**
- The Checkout page uses the Checkout block.
- The Cart page is set up with the Cart block (parity).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Trigger and observe a cart validation error in Block Checkout
**Steps:**
1. As Administrator, in **ArraySubs → Settings → General Settings**, temporarily disable **Allow multiple subscriptions in cart**. Save.
2. In an Incognito window, log in as `customer-block@example.test`.
3. Add `Standard Weekly` to cart.
4. Add `Pro Plan` to cart (a second distinct subscription product, also weekly).
5. Open the cart and then proceed to the Block Checkout page.
6. Look for the cart-level error message above the order review.

**Expected Result:**
- A cart-level error appears with the exact wording: *"Only one subscription plan can be checked out at a time."*
- The error is rendered as a Store API cart error (highlighted Block Checkout error banner, not a WooCommerce shortcode notice).
- The Place Order button is disabled or returns the same error if clicked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Reset cart rules and clear cart
**Steps:**
1. As Administrator, re-enable **Allow multiple subscriptions in cart**. Save.
2. As the customer, remove `Pro Plan` from cart (keep only `Standard Weekly`).
3. Refresh the Block Checkout page.

**Expected Result:**
- The cart now contains only `Standard Weekly`.
- The error banner is gone.
- The Place Order button is enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Inspect the subscription summary inside the Block Checkout order review
**Steps:**
1. With only `Standard Weekly` in cart, scroll to the order review section of Block Checkout.
2. Read each line of the subscription summary inside the order review table.

**Expected Result:**
- Product name `Standard Weekly` (x1) is shown.
- Recurring text `$19.99 every 1 week` is shown.
- A **Today's charge** line shows `$19.99`.
- A **Next charge** line shows today + 7 days.
- The authorization notice appears verbatim: *"By completing this purchase, you authorize us to charge..."*
- The summary updates via AJAX without a full page reload when quantity, coupon, or shipping changes (test by changing nothing, just observe no missing pieces).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Visual parity comparison with Classic Checkout (Task 01)
**Steps:**
1. Open the Task 01 sign-off file or screenshots side-by-side with the current Block Checkout page.
2. Compare the subscription summary block line by line.

**Expected Result:**
- Recurring price text matches Task 01.
- Today's charge value matches.
- Next charge date matches (allow up to 1-day difference if the test is run on a different calendar day).
- Authorization notice text matches verbatim.
- No additional fields appear in Block Checkout that did not appear in Classic Checkout (no extra "Subscription details" header, no different label text).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Place the order via Block Checkout
**Steps:**
1. Fill the billing fields (email, first name, last name, country, address, city, postcode, phone).
2. Select Direct bank transfer.
3. Click **Place Order**.
4. Wait for the redirect to the order received page.
5. Record the new order ID.

**Expected Result:**
- Redirect succeeds.
- Order received page shows the order ID and a **Related Subscriptions** table with one row.
- Status badge is **Pending** or **Active** depending on the manual gateway behavior.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.7 — Confirm subscription record matches Task 01's behavior
**Steps:**
1. As Administrator, change the new order's status to **Completed** (if not already).
2. Open **ArraySubs → Subscriptions**.
3. Locate the subscription created for `customer-block@example.test`.
4. Open **View Details**.

**Expected Result:**
- Status: **Active**.
- Recurring amount: `$19.99`.
- Billing schedule: `Every 1 week(s)`.
- Next Payment: today + 7 days (matching Task 01's calculation method).
- Parent order link points to the order from Sub-Task 2.6.
- Only one subscription was created (no duplicates from Classic / Block dual hooks — verify by counting entries on the All Subscriptions list filtered by this customer's email).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-run Sub-Task 2.2 with **Allow mixed cart** disabled and confirm: *"This order cannot contain subscription and regular products together."*
- Confirm no JavaScript console errors appear on the Block Checkout page (DevTools → Console).
- Confirm the Block Checkout page does not render a duplicate subscription summary if the page is reloaded mid-checkout.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order ID created:
- Subscription ID created:
- Notes:
