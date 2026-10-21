---
id: 73
title: Per-cart member discount counts a free-trial line at its regular price, so a fixed discount comes off products the rule excludes
status: closed
priority: medium
created: 2026-10-20T16:24:13.342399606+02:00
updated: 2026-10-21T02:09:42.742430968+02:00
started: 2026-10-20T19:27:41.431016412+02:00
completed: 2026-10-21T02:09:42.742430276+02:00
tags:
    - members
    - discounts
    - checkout
    - revenue-leak
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by review round 4 of the #65–#70 fixes; pre-existing, out of their scope). Fix log: qa/issues-65-69-fix-log.md (R4-12).
Affected subscription IDs and order IDs: N/A (memory-only probe; no cart saved, no order created).
Affected WordPress user/customer: N/A (any logged-in member who matches a per-cart member discount rule).
Exact route: storefront cart/checkout, `woocommerce_cart_calculate_fees` → `DiscountHooks::applyCartDiscount` → `DiscountEngine::getCartDiscountData()`; cause at `arraysubs/src/Features/MembersAccess/Services/DiscountEngine.php:376-377` (`getCartItemContext()` falls back to `getBasePrice()`, the regular price, whenever `line_subtotal <= 0`, not only when it is missing). Admin route for the rule: ArraySubs → Member Access → Discount (route `#/members-access/discount-rules`, page heading "Discount Rules"; Fixed amount, Per cart, Specific products). getBasePrice() also falls back to the regular price when a sale price is set (pre-existing; check alongside).
Reproduction (real code, memory-only probe; rule injected via `arraysubs_discount_rules`):
1. Member discount rule: Fixed $30, Per cart, Specific products [550 "Trial 14-Day" (14-day free trial, $29.99), 2569 "Smoke Plan" ($10)]. The admin help says "Apply once per cart, up to the matching products' subtotal".
2. Logged-in member adds 550, 2569 and 495 "Pro Monthly" ($49.99, not in the rule) to the cart.
3. The trial line costs $0 today (Hooks::beforeCalculateTotals sets it to 0 before fees run), but DiscountEngine counts it at 29.99 for the rule's cap and weights.
Expected: the per-cart discount is capped at what the matching lines charge today ($10.00), total $49.99.
Actual: fee "Member discount" -30.00 (weights 550=29.99, 2569=10.00), cart TOTAL $29.99: $20 comes off Pro Monthly, which the rule excludes. Control without the trial line: fee -10.00, TOTAL $49.99. A member can add a free trial (and cancel it before it converts) to buy a larger discount on other products.
Proof: `qa/fix-65-69/evidence/r4-member-per-cart-trial-cap.txt` (F1: `fee 'Member discount' total=-30.00 ... weights=L0=29.99,L1=10.00`, `TOTAL=29.99`; F2 control `total=-10.00`, `TOTAL=49.99`) and the independent re-run `r4-member-per-cart-trial-cap-skeptic.txt` (C1/C2; C4 scope "all" with an exclusion leaks the same way; C6 the fee also absorbs shipping).
Scope notes: the leak per order is bounded by the rule amount minus what matching lines charge today, and by the trial's regular price × quantity. Percentage per-cart rules are API-only (the UI forces per item for percentages). Order side: buildInitialChargeAllocations() then gives the trial subscription a negative share (#72). Suggested fix (checked in memory by the reviewer): use the catalogue fallback only when the cart has not priced the line, or when the member per-item price itself took the line to 0 (keeps the per-item + per-cart combination unchanged). Separate, also pre-existing: a targeted line a coupon brought to $0 still counts its pre-coupon subtotal.

[[2026-10-21]] Wed 02:09
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (section 'Issues #71–#78' and timeline from 17:26).
Fix: per-cart member discounts count each targeted line at what the cart charges today (free trial = 0, after member prices and coupons); the catalogue price is used only for a line the cart has not priced yet. Verified by probes (F1: fee -10.00, total 49.99 instead of -30.00/29.99).
