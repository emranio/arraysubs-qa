---
id: 78
title: Pro subscription bundle get_price() ignores the cart's set_price(), so a bundle in its renewal-sync prorate segment is charged the full bundle price
status: closed
priority: medium
created: 2026-10-20T18:46:01.430047943+02:00
updated: 2026-10-21T02:09:43.388746257+02:00
started: 2026-10-20T19:27:41.757748302+02:00
completed: 2026-10-21T02:09:43.388744814+02:00
tags:
    - pro
    - bundles
    - renewal-sync
    - checkout
    - overcharge
    - needs-repro
class: standard
---

Active QA task ID and scheduled day: N/A (side observation of review round 5 of the #65–#70 fixes, 2026-10-20; pre-existing, out of their scope). Fix log: qa/issues-65-69-fix-log.md (R5-10).
Affected subscription IDs and order IDs: N/A (memory-only probe, rolled back; no cart saved, no order created). Product: Pro bundle 11095 (segment plan, in its prorate segment on 2026-10-20).
Affected WordPress user/customer: user 54 (customer, pro_member) used in the probe; affects any customer buying such a bundle.
Exact route: storefront cart/checkout. SubscriptionProducts `Hooks::beforeCalculateTotals()` sets the line's first-charge price with `set_price()` (renewal-sync / segment prorate initial amount; also 0 for a free trial). `ArraySubsPro\Features\SubscriptionBundle\Services\ProductType::get_price()` (arraysubspro/src/Features/SubscriptionBundle/Services/ProductType.php:100) always returns the recomputed bundle total (resolvePricing()), so the price the engine set is never charged.
Reproduction (memory-only probe, real code):
1. User 54, no qualifying member rule (control H-c), adds bundle 11095 to the cart.
2. Subscription data for the line: sync applies=true, mode=prorate, segment 2, initial_unit=16.59, full_unit=46.74, next 2026-10-31 18:00.
Expected: the line charges the prorated first amount (16.59) today, as non-bundle segment-plan products do.
Actual: engine-set get_price('edit')=46.74, charged 46.74; cart TOTAL 46.72 after a 0.02 store-credit fee. With the live 15% pro_member rule (case H) the same: 39.73 charged vs initial_unit 14.10.
Proof: qa/fix-65-69/evidence/r5-bundle-get-price-ignores-set-price.txt (cases H, H-c, H-f). Code: ProductType::get_price() returns wc_format_decimal($pricing['total']) whenever resolvePricing() is not null, ignoring the product object's set price.
Scope notes: needs a browser repro first (add 11095 as a guest on staging and compare Today's charge with the cart total). (Corrected by review round 6: subscription bundles cannot have a free trial, so only the renewal-sync / segment prorate first charge is affected.) Pro Bundle CartHooks may already price some cases differently; check before fixing. Fix direction: when the product object carries a price set by the cart ($this->changes['price'] or a cart flag), return it from get_price() in view context; keep the recomputed total for catalogue and product pages. Related: #74 (member price also replaces engine-set cart prices).

[[2026-10-21]] Wed 02:09
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (section 'Issues #71–#78' and timeline from 17:26).
Fix: Pro SubscriptionBundleProduct::get_price() returns the price the cart set (renewal-sync / segment prorated first amount) instead of the recomputed bundle total; catalogue and product pages are unchanged, and it relies on core's member-price guard defensively. Verified by probes (non-member 16.59, member 14.10 instead of 46.74 / 39.73). Live: on 2026-10-21 bundle 11095 is in its full-charge segment, so the prorate case could not be reproduced live that day (Today's charge = total = $46.74, consistent). Issue text corrected: bundles cannot have a free trial.
