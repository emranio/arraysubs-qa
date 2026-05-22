---
id: 69
title: 'stage-05: 09 Checkout with Coupon (One-Time + Recurring with Cycle Limit)'
status: closed
priority: high
created: 2026-05-19T22:56:07.765708577+02:00
updated: 2026-05-20T12:44:25.844007749+02:00
started: 2026-05-20T10:28:05.944590002+02:00
completed: 2026-05-20T12:44:25.844006617+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T12:44:25.844007649+02:00
class: standard
---

Source: stages/05-checkout/09-checkout-with-coupon.md

[[2026-05-20]] Wed 12:44
QA complete. Seeded coupons SUB10ONCE #454, RENEW20FOR3 #456, NONSUB5 #458 and customer customer-coupon@example.test. Block checkout: SUB10ONCE applied, total 9.99, renewal remained 9.99/month. RENEW20FOR3 applied, total 3.99; checkout summary showed coupon but no explicit cycle wording before order. Placed BACS order #472, subscription #482; after payment complete subscription active. Admin Coupon Discount card: recurring, renew20for3, 20%, total cycles 3, remaining cycles 3, captured 20 May 2026 4:36 PM (UTC+6). Customer portal showed Recurring Amount 9.99 Every month, Coupon Discount 20% off (renew20for3), 3 renewal cycle(s) remaining. Negative NONSUB5 failed: non-sub coupon applied and discounted total to 4.99; filed qa/issues #30.
