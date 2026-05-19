# Stage 03 — Task 16: Product Lifecycle — Trash and Restore

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Product Lifecycle |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 03.01, 03.13 (an active subscription must exist) |

## Objective
Verify the admin warning shown on subscription products with active subscriptions, the post-deletion admin notice when trashing the product, that subscriptions continue with cached product data, and that restoring the product clears the warning state.

This task trashes a **weekly** subscription product (`Standard Weekly`) so the affected subscription's renewal still falls inside this regression session if needed for follow-up.

## Pre-conditions
- Stage 03.01 complete.
- An active subscription record exists for `Standard Weekly`. If one was not created during 03.13, run a fresh checkout of `Standard Weekly` so at least one active subscription references it.
- Logged in as Administrator.

## Test Data
- Subject product: `Standard Weekly` ($19.99/week, must have ≥1 active subscription referencing it).

## Sub-Tasks

### Sub-Task 16.1 — Verify the active-subscriptions warning on edit
**Steps:**
1. Open `Standard Weekly` in the product editor.
2. Look for the yellow warning box at the top or near the Subscription tab.

**Expected Result:**
- Warning text matches:
  > `Active Subscriptions — This product has N active subscription(s). Deleting this product will NOT affect subscriptions — they will continue using cached product data.`
- `N` is replaced with the actual subscription count (≥1).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 16.2 — Capture the helper test links
**Steps:**
1. Scroll to the **General** tab of the product editor.
2. Locate the **Helper Links** section.
3. Confirm the two read-only fields:
   - `Direct add to cart` URL (format `{cart_url}?add-to-cart={product_id}`)
   - `One-click checkout` URL (format `{checkout_url}?add-to-cart={product_id}`)
4. Click the Copy button next to one of them and paste into the address bar in a new tab to confirm the URL works.

**Expected Result:**
- Both helper links present with Copy buttons.
- Clicking either URL adds `Standard Weekly` to the cart.
- Empty cart afterwards.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 16.3 — Trash the product
**Steps:**
1. From the product list (**Products → All Products**), hover `Standard Weekly` and click **Trash**.
2. After the page reloads, observe the admin notice.

**Expected Result:**
- A transient admin notice appears matching:
  > `Standard Weekly was trashed. N active subscription(s) use this product and will continue with cached product data. View Subscriptions`
- The `View Subscriptions` link navigates to the subscriptions list filtered to the affected subscriptions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 16.4 — Confirm cached display in subscription portal
**Steps:**
1. Log in as the customer that owns the subscription.
2. Open **My Account → Subscriptions** and click into the subscription detail.

**Expected Result:**
- Subscription detail still loads.
- Product name appears with a `(deleted)` suffix (e.g., `Standard Weekly (deleted)`).
- Status, next payment, and other fields are unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 16.5 — Restore the product from Trash
**Steps:**
1. As admin, go to **Products → All Products → Trash**.
2. Hover `Standard Weekly` and click **Restore**.

**Expected Result:**
- Product returns to the published list.
- No errors.
- The post-deletion admin notice does not reappear after restore.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 16.6 — Confirm `(deleted)` suffix clears
**Steps:**
1. As the customer, refresh **My Account → Subscriptions → [subscription detail]**.
2. As admin, reopen the product editor.

**Expected Result:**
- Customer view: product name no longer shows `(deleted)` (per manual: "When you restore (untrash) a product, ArraySubs clears the deletion metadata on affected subscriptions").
- Admin edit: yellow active-subscriptions warning still present (subscription is still active).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 16.7 — Cross-check: warning absent on a product with no active subscription
**Steps:**
1. Open `Lifetime Deal` (or any product with zero subscriptions tied to it).

**Expected Result:**
- No yellow active-subscriptions warning is shown — confirms the warning is conditional on subscription count > 0.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the post-deletion notice clears automatically after a page navigation or two (transient-based).
- Verify the `_subscription_id` order item meta on the parent order is preserved through trash/restore.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
