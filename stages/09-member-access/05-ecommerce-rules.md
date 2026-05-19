# Stage 09 — Task 05: Ecommerce Rule — Members-Only Purchase

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access → Ecommerce |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 01-role-mapping.md |

## Objective
Configure an Ecommerce Rule restricting purchase of the **Premium Widget** product to active **Pro Plan** subscribers. Verify guests and non-members see a "Block purchase" message and cannot add the product to cart, while active subscribers can purchase normally.

## Pre-conditions
- Premium Widget product exists, $25.00, simple non-subscription, published.
- Pro Plan and the Stage 06 customers are in place.
- Customers ready: `member1@example.com` (Active Pro Plan), `nonmember@example.com`, guest.

## Test Data
- Restriction scope: `Specific products` → **Premium Widget**
- Action: `Block purchase`
- Block message: `This product is exclusive to Pro Plan members. Subscribe to unlock.`
- Expected Block API behavior: Add-to-cart button disabled or replaced; Store API quantity max = 0.

## Sub-Tasks

### Sub-Task 05.1 — Create the Ecommerce rule
**Steps:**
1. Go to **ArraySubs → Member Access → Ecommerce**.
2. Click **Add Rule**.
3. Name the rule `Premium Widget — members only`.
4. **IF conditions:** Has Active Subscription to **Pro Plan**.
5. **TARGET:**
   - Restrict access to: `Specific products`.
   - In the AJAX search, type `Premium Widget` and select it.
   - Exclude products / categories: leave empty.
6. **THEN:** Action = `Block purchase`.
7. Message: enter `This product is exclusive to Pro Plan members. Subscribe to unlock.`
8. Schedule: disabled.
9. Save.

**Expected Result:**
- Rule listed with target `Premium Widget`, action `Block purchase`. Toggle is enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.2 — Guest sees the block message
**Steps:**
1. In an incognito browser, navigate to the **Premium Widget** product page.
2. Look at where the **Add to cart** button normally renders.
3. Look at the body of the product summary.
4. Try to bypass: directly hit `?add-to-cart=<product_id>` in the URL bar.

**Expected Result:**
- Product page is reachable (200 OK).
- Add-to-cart button is replaced or disabled.
- The block message "This product is exclusive to Pro Plan members. Subscribe to unlock." is displayed.
- Direct add-to-cart URL is rejected — cart shows an error notice and the item is NOT added.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.3 — Non-member (logged-in) sees the block message
**Steps:**
1. Log in as `nonmember@example.com`.
2. Navigate to **Premium Widget**.

**Expected Result:**
- Same as 05.2: button blocked, message displayed.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.4 — Active subscriber can purchase
**Steps:**
1. Log in as `member1@example.com`.
2. Navigate to **Premium Widget**.
3. Click **Add to cart**.
4. View the cart and proceed to checkout.

**Expected Result:**
- Add-to-cart button works normally.
- Premium Widget appears in cart at `$25.00` (with the 15% member discount applied from Task 04 if that rule is still enabled — i.e., line shows `$21.25`).
- Checkout proceeds without an "items removed from your cart" notice for Premium Widget.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.5 — Block-purchase via Block Checkout (Store API)
**Steps:**
1. Confirm the store has a Block-based cart/checkout (set up in Stage 05). If not, skip and note in Sign-off.
2. As `nonmember@example.com`, view the Block-cart page after attempting to add Premium Widget via the product page.
3. Inspect the Block Checkout cart line for any quantity controls.

**Expected Result:**
- Premium Widget cannot be added via the product page Block-styled add-to-cart.
- Quantity max in Store API responses is `0` for this product (visible by inspecting the network request to `/wp-json/wc/store/v1/cart/items` if added to a member's cart while signed-in as a non-member).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.6 — Test the "404 Not Found" action variant
**Steps:**
1. Edit the Ecommerce rule. Change Action to `404 Not Found`. Save.
2. As `nonmember@example.com`, navigate to the **Premium Widget** product page.
3. Check the HTTP status (DevTools → Network → top-level document).
4. Navigate to the shop archive (`/shop/`) and search for "Premium Widget".

**Expected Result:**
- Single product page returns HTTP `404`.
- Premium Widget does NOT appear in the shop archive listing or in the search results for non-members.
- WordPress XML sitemap (`/sitemap.xml` or Yoast/Rank Math equivalent) does not list this product when fetched as the non-member (or when fetched logged out).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.7 — Restore Block purchase action
**Steps:**
1. Edit the rule, change action back to `Block purchase`. Save.
2. Confirm Premium Widget is reachable for everyone (just blocked from purchase by non-members).

**Expected Result:**
- Premium Widget single page is HTTP `200` again. Non-members see the block message; active subscribers can buy.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm WP admin can still see Premium Widget in **Products → All Products** (admin/wp-admin not gated by frontend ecommerce rules).
- Confirm cart validation: as `nonmember@example.com`, manually add `?add-to-cart=<premium-widget-id>` — confirm the cart contents remain empty and an error notice appears: "items removed from your cart" or similar restriction message.
- Confirm related-products / upsell widgets on other product pages do NOT show Premium Widget to non-members when action was `404`.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Block Checkout used (yes/no):
- Notes:
