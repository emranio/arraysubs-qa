---
id: 72
title: Initial-order charge attribution gives a subscription a negative share when a discount is larger than its line
status: closed
priority: medium
created: 2026-10-20T14:13:53.675709299+02:00
updated: 2026-10-21T02:09:42.618846677+02:00
started: 2026-10-20T19:27:41.373742383+02:00
completed: 2026-10-21T02:09:42.618845304+02:00
tags:
    - checkout
    - refunds
    - plan-switching
    - attribution
    - discounts
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by review round 3 of the #69 fix; pre-existing, out of its scope). Fix log: qa/issues-65-69-fix-log.md (R3-13).
Affected subscription IDs and order IDs: N/A (memory-only probe; no order created).
Affected WordPress user/customer: N/A.
Exact route: checkout order creation, `SubscriptionCreationTrait::buildInitialChargeAllocations()` (arraysubs/src/Features/SubscriptionCheckout/Services/Traits/SubscriptionCreationTrait.php:705) → `arraysubs_set_order_item_subscription_charge_allocations()`; read by refunds (`refund-helpers.php`, `RefundAttributionTrait`) and plan-switch credit (`ProrationCalculator`, `PlanSwitchCreditSourceTrait`).
Reproduction (memory-only probe in review round 3, session scratchpad r3/cart-math-lens):
1. Logged-in member, fixed $15 "Per cart" member discount rule targeting a 14-day trial plan (550) and Pro Monthly (495, $49.99).
2. Cart 550 + 495. DiscountEngine weights the -$15 fee on 550 by its regular price (29.99) although it charges $0 today.
3. buildInitialChargeAllocations() splits each fee by its own weights: the trial subscription is attributed -5.62 and Pro Monthly 40.61 (fee share -9.38).
Expected: no subscription is attributed a negative amount; a discount larger than a line's charge moves to the other lines it covers (what the cart's Today's charge now shows: Free today / $34.99).
Actual: trial subscription -5.62, Pro Monthly $40.61 although only $34.99 was effectively paid for it, so its refund cap and plan-switch credit are based on $40.61.
Proof: probe output in review round 3 (r3-cart notes): "the trial subscription is recorded at −5.62 and the paid one at 40.61, and refunds and plan-switch credit sources use those numbers".
Scope notes: the cart display helper `arraysubs_get_cart_initial_charge_breakdown()` already clamps and moves the excess (R2-10/R3-6); the order side should use the same rule. Root cause is also that DiscountEngine weights a $0 trial line by its regular price.

[[2026-10-21]] Wed 02:09
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (section 'Issues #71–#78' and timeline from 17:26).
Fix: initial-order charge attribution holds a subscription at 0 when its share of a discount is larger than its line and moves the excess to the other lines the discount covers, using the same rule as the cart's Today's charge (new arraysubs/src/functions/initial-charge-share-helpers.php; cart output byte-identical). Readers round per currency, so no float residue makes a 0.00 subscription a charge owner. Weighted charges whose split gives every subscription 0 (free shipping, a cent-sized discount on other lines) now finalize as all-unowned, so Pro saves subscription shipping for them; 9 stuck staging orders finalize on their next pass. Verified by fixer and verifier probes and fuzz (thousands of orders). Product decision left open: #80.
