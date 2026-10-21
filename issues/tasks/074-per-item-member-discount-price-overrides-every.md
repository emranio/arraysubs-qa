---
id: 74
title: Per-item member discount price overrides every engine-set cart price (free trial $0, checkout migration, renewal-sync and segment prorate), so members are overcharged
status: closed
priority: high
created: 2026-10-20T16:24:13.436378028+02:00
updated: 2026-10-21T02:09:42.854991014+02:00
started: 2026-10-20T19:27:41.494609425+02:00
completed: 2026-10-21T02:09:42.854990132+02:00
tags:
    - members
    - discounts
    - trials
    - checkout
    - overcharge
    - needs-repro
    - plan-switching
    - renewal-sync
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by review round 4 of the #65–#70 fixes; pre-existing, out of their scope). Fix log: qa/issues-65-69-fix-log.md (R4-12).
Affected subscription IDs and order IDs: N/A (memory-only probe; no cart saved, no order created). Staging config at risk: an enabled per-item 15% member discount rule, scope "all", for role pro_member (reviewer's read of the live rules; not changed).
Affected WordPress user/customer: N/A in the probe (user 1); on staging, any pro_member customer buying a product with a free trial.
Exact route: storefront cart/checkout. `SubscriptionProducts\Hooks::beforeCalculateTotals()` sets a free-trial line to price 0 with `set_price(0)`; `MembersAccess\DiscountHooks::filterPrice()` (arraysubs/src/Features/MembersAccess/Services/DiscountHooks.php:112, hooked on `woocommerce_product_get_price` / `woocommerce_product_variation_get_price` at priority 100) ignores the price it is passed and returns the member price computed from the regular price, so the cart charges it.
Reproduction (real code, memory-only probe):
1. Member discount rule: 50% per item, Specific products [550 "Trial 14-Day", $29.99 with a 14-day free trial].
2. Logged-in member adds 550 to the cart.
Expected: the free trial costs $0 today (Free today); the member price applies to the renewals.
Actual: line_subtotal 15.00, cart TOTAL $15.00 for a 14-day free trial.
Proof: `qa/fix-65-69/evidence/r4-member-per-item-trial-price.txt`, case "C11 ASIDE": `L0 #550 Trial 14-Day qty=1 regular=29.99 line_subtotal=15.00 line_total=15.00`, `cart: items_subtotal=15.00 ... TOTAL=15.00`. Code: filterPrice() has no trial check (DiscountEngine::getDiscountedPrice → computeDiscountedPrice from the regular price).
Scope notes: not yet reproduced in a browser — do that first (log in as a pro_member fixture customer, add a trial product, compare the cart total with a guest). Probably also affects $0 lines from renewal sync or checkout migration pricing if they set the price the same way. Fix direction: leave a line the subscription engine priced at 0 (trial) alone in the member price filter, or apply the member price as a base before the trial override.

[[2026-10-20]] Tue 18:45
Scope widened by review round 5 of the #65–#70 fixes (R5-10, confirmed by an independent skeptic; memory-only probes, all rolled back clean). DiscountHooks::filterPrice()/filterSalePrice() replace EVERY price the engine sets with set_price(), not only a trial's 0. Observed with the live staging rule (enabled, per item 15%, scope all, role pro_member) and real pro_member user 54 / products — format: engine-set get_price('edit') / charged line_total / non-member control / fix simulated:
- A trial product 550 (14-day trial): 0.00 / 25.49 / 0.00 / 0.00
- B segment-plan product 8650 in its prorate segment today (real config, no settings change): 7.29 / 17.00 / 8.57 / 7.29; variation 8654: 9.05 / 25.50 / 10.65 / 9.05
- C 495 with global renewal sync 'prorate' (set in memory): 15.08 / 42.49 / 17.74 / 15.08 ("Today's Charge: $42.49 prorated until the synced renewal date")
- D checkout migration 197→495 (customer 468, sub 27494; pro_member role + migration settings in memory): net 4.47 / 42.49 / 7.16 / 4.47; order built from the cart totals 42.49
- E plain 495: 42.49 everywhere (no conflict); G non-subscription product 12594: unchanged.
Reachable on staging today without config: 6 segment-plan products (8650, 8654, 8816, 11943, 12737, 31363) are in their prorate segment and 2 pro_members exist (users 54, 25). Global sync, plan switching and one-per-customer are off on staging, so C and D need config.
Fix direction (corrected): only make filterPrice()/filterSalePrice() return the passed price for a cart product object whose price the subscription engine set (e.g. flag it in beforeCalculateTotals() / applyCheckoutMigrationPricing()). Do NOT also discount the trial/sync/migration amount: arraysubs_get_product_subscription_data() already reads the member-filtered price of a fresh product (member recurring 42.49, sync initial 15.08, segment initial 7.29, migration net 4.47), so that would double-discount.
Evidence: qa/fix-65-69/evidence/r5-member-price-scope-skeptic.txt (cases A–G), r5-member-price-scope-lens.txt (P1–P4 with controls). Not introduced by the #65–#70 work (DiscountHooks/DiscountEngine/beforeCalculateTotals unchanged vs HEAD).

[[2026-10-21]] Wed 02:09
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (section 'Issues #71–#78' and timeline from 17:26).
Fix: MembersAccess DiscountHooks keeps a price the subscription engine set on a cart product (free trial 0, renewal-sync / segment prorated first amount, checkout-migration net); member prices still apply to plain lines and to renewals (the engine amounts already include them). Member price HTML on product pages matches the charge. Live 2026-10-20 (fixture 593 given pro_member temporarily, role and cart restored): Trial 14-Day shows 'Free today', $25.49 member price after the trial; Pro Monthly $42.49; estimated total $42.49 (screenshot r7-74-member-trial-free-today). Display/API follow-up for bundles: #83.
