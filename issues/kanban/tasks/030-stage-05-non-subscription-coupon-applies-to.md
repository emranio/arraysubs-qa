---
id: 30
title: 'stage-05: Non-subscription coupon applies to subscription product'
status: closed
priority: high
created: 2026-05-20T12:44:16.398943317+02:00
updated: 2026-05-22T02:04:32.916780932+02:00
started: 2026-05-22T01:57:20.066088615+02:00
completed: 2026-05-22T02:04:32.916780161+02:00
tags:
    - qa
    - stage-05
    - checkout
    - coupons
    - subscription
claimed_by: mold-glade
claimed_at: 2026-05-22T02:04:32.916780842+02:00
class: standard
---

Stage 05 Task 09 negative test failed. Coupon NONSUB5 was configured as a normal WooCommerce fixed-cart coupon with _arraysubs_apply_to_subscriptions empty. In block checkout with Basic Monthly #197, applying NONSUB5 showed success notice, added Coupon: nonsub5, Discount -.00, and changed total from 9.99 to 4.99. Expected: reject coupon or leave subscription total undiscounted.

[[2026-05-22]] Fri 01:58
Verified Stage 05 Task 09 negative test and coupon code. CouponTracking stores _arraysubs_apply_to_subscriptions but does not participate in Woo coupon validation/discount item selection, so fixed-cart normal coupons can discount subscription cart lines. Plan: add Woo coupon validation and item-selection filters. For coupons not marked Apply to subscriptions, reject if there are no eligible non-subscription items, and always remove subscription items from the coupon's discounted item list. This keeps normal coupons usable for regular products in mixed carts but prevents discounts against subscription-only carts. Syntax-check PHP; browser-verify NONSUB5 on Basic Monthly checkout is rejected/no discount; verify SUB10ONCE still discounts Basic Monthly; check debug log.

[[2026-05-22]] Fri 02:04
QA fix complete. Added coupon validation/item filters: coupons without _arraysubs_apply_to_subscriptions=yes now reject subscription-only carts and subscription line items are removed from discount application, while regular products remain eligible in mixed carts. Syntax check passed. Browser verified Basic Monthly + NONSUB5: 'Coupon is not valid.', no coupon row, no discount, total remains 9.99. Browser verified Basic Monthly + SUB10ONCE still applies: coupon row sub10once, -0.00, total 9.99, recurring line remains 9.99. Mixed Basic Monthly + Standard Tee + NONSUB5 still applies to regular item: -.00, total 9.99 from 4.99. debug.log remains 0 bytes.
