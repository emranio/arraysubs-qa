# Stage 09 — Task 04: Discount Rule — Members Get 15% Off

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access → Discount |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 01-role-mapping.md |

## Objective
Configure a Discount Rule that gives active **Pro Plan** subscribers a 15% discount on all products. Verify the discount is automatically applied on product pages and in the cart for active subscribers, while guests, non-members, and paused subscribers see the regular price.

## Pre-conditions
- Pro Plan subscription product and **Members Tee** ($20.00 non-subscription product) exist and are published.
- Customers ready: `member1@example.com` (Active Pro Plan), `member2@example.com` (Paused Pro Plan), `nonmember@example.com` (no subscription), and a guest browser (incognito, never logged in).
- WooCommerce currency is USD (or whatever the test store uses — record actual currency in Sign-off).
- "Subscription Plans" category exists and the **Pro Plan** product is assigned to it (so the rule can exclude subscription products themselves from the discount).

## Test Data
- Discount target: `All products`
- Exclude categories: `Subscription Plans`
- Discount type: `Percentage`
- Discount value: `15`
- Apply To: not shown (only relevant for fixed-amount discounts)
- Test product: **Members Tee** $20.00 → expected member price $17.00 (15% off)

## Sub-Tasks

### Sub-Task 04.1 — Create the Discount rule
**Steps:**
1. Go to **ArraySubs → Member Access → Discount**.
2. Click **Add Rule**.
3. Name the rule `Pro Plan members — 15% off all products`.
4. **IF conditions:** Has Active Subscription to **Pro Plan**.
5. **TARGET:**
   - Apply discount to: `All products`.
   - Exclude products: leave empty.
   - Exclude categories: select `Subscription Plans`.
6. **THEN:**
   - Discount Type: `Percentage`.
   - Discount Value: `15`.
7. Schedule: leave disabled.
8. Save.

**Expected Result:**
- Rule appears in the list with discount value `15%` and target `All products`. Toggle is enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.2 — Guest sees regular price
**Steps:**
1. Open an incognito browser. Navigate to the **Members Tee** product page.
2. Inspect the price displayed.
3. Add Members Tee to cart and view the cart page.

**Expected Result:**
- Product page shows `$20.00` only — no member price strikethrough, no member discount notice.
- Cart subtotal = `$20.00`. No discount fee line is present.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.3 — Non-member (logged-in) sees regular price
**Steps:**
1. Log in as `nonmember@example.com`.
2. Navigate to **Members Tee** and view the cart with one Members Tee in it.

**Expected Result:**
- Product page shows `$20.00`.
- Cart subtotal `$20.00`. No member discount.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.4 — Active Pro Plan subscriber sees discounted price
**Steps:**
1. Log in as `member1@example.com` (Active Pro Plan).
2. Navigate to the **Members Tee** product page.
3. Inspect the price block.
4. Add Members Tee to cart and view the cart page.

**Expected Result:**
- Product page shows BOTH the original `$20.00` (struck through or labelled "Regular") AND the member price `$17.00`.
- Cart line item shows `$17.00`. Cart subtotal reflects the discounted price.
- Cart shows the saving relative to the regular price (theme-dependent).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.5 — Subscription product is excluded
**Steps:**
1. Still as `member1@example.com`, navigate to the **Pro Plan** product page.
2. Inspect the price block.

**Expected Result:**
- Pro Plan price still reads `$29.99 / month` (regular subscription price, no member discount applied because Pro Plan is in the excluded `Subscription Plans` category).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.6 — Paused subscriber loses discount
**Steps:**
1. Log out, log in as `member2@example.com` (Paused Pro Plan).
2. Navigate to the **Members Tee** product page.

**Expected Result:**
- Product page shows `$20.00` regular price only.
- No member price displayed (paused subscriptions are not `active` so the rule does not match).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.7 — Verify discount survives cart refresh and login transition
**Steps:**
1. As `member1@example.com`, leave Members Tee in cart at `$17.00`. Hard-refresh the cart page (Ctrl/Cmd + Shift + R).
2. Log out.
3. Reload the cart page (cart now belongs to a guest session via cookies).
4. Log in again as `member1@example.com` and reload cart.

**Expected Result:**
- After refresh while logged in: cart line stays at `$17.00`.
- After logout: cart line reverts to `$20.00` (no member discount for guest).
- After re-login: cart line is `$17.00` again.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Add a percentage coupon (e.g., `WELCOME10` = 10% off) and confirm the member discount AND the coupon both apply (default `arraysubs_member_discount_stack_with_coupons` behavior).
- Confirm the original price is shown on the cart/product page so the customer can see the value of membership.
- Confirm Stage 04 cart rules and Stage 06 lifecycle behavior are not broken.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Currency used:
- Notes:
