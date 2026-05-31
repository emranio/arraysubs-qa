---
id: 122
title: 'stage-12: 05 Purchase with Credit at Checkout'
status: closed
priority: medium
created: 2026-05-19T22:56:16.065463567+02:00
updated: 2026-05-24T10:38:24.445893846+02:00
started: 2026-05-23T08:06:53.432275015+02:00
completed: 2026-05-23T09:54:50.250848312+02:00
tags:
    - qa
    - stage-12
class: standard
---

Source: stages/12-store-credit/05-purchase-with-credit.md

[[2026-05-23]] Sat 09:54
QA done with failures/workarounds. 5.1 FAIL #79: Standard Weekly cart showed no store credit line and total stayed 9.99 despite balance 05/apply_at_checkout=true/min=5. Cart also lacked explicit Proceed to Checkout button (#80); used header Checkout. 5.2 FAIL #81: checkout order #1133 via BACS completed as on-hold total 9.99, no credit fee/meta, balance unchanged 05, sub #1135 pending. Workaround applied CreditManager::applyCreditsToOrder then processing: order total /bin/bash, fee Store Credit Applied -9.99, _arraysubs_credit_applied=19.99, balance 85.01, sub #1135 active. 5.3 admin Manage Credits UI blocked by #74, backend history source order_applied shows #1141 debit 19.99 balance 185.01. 5.4 email blocked by #40. 5.5/5.6 Tiny Daily .99 fixture #1146 PASS: cart/checkout showed .99, no credit line, order #1148 on-hold total .99, balance stayed 85.01. 5.7 blocked/logged #82 because cust3 now has active Standard Weekly and plan switching blocks adding Standard/Basic again; apply_at_checkout restored true. Debug log no fresh related errors; only old 2026-05-22 Action Scheduler WP-CLI fatal lines.

[[2026-05-24]] Sun 10:10
Issue #79 fix verified: checkout cart now shows Store Credit Applied fee before order placement. QA user qa-credit-cart #309 with 5 credit added Standard Weekly; cart showed 9.99 subtotal, -9.99 Store Credit Applied, estimated total /bin/bash.00.

[[2026-05-24]] Sun 10:15
Issue #80 plan: cart block already includes proceed-to-checkout block, but it renders empty for zero-total store-credit subscription cart. Fix will reuse ArraySubs block fallback for any subscription checkout cart.

[[2026-05-24]] Sun 10:17
Issue #80 fixed/verified: cart page now exposes Proceed to checkout CTA below order summary for zero-total store-credit subscription cart; link points to checkout page_id=8.

[[2026-05-24]] Sun 10:32
Issue #81 fixed/verified: Store Credit now captures Block Checkout orders via woocommerce_store_api_checkout_order_processed. Browser order #2717 for qa-credit-order #310 completed at /bin/bash.00 with Store Credit Applied -9.99; order meta _arraysubs_credit_applied=19.99, balance 5.01, subscription #2727 active.

[[2026-05-24]] Sun 10:34
Issue #82 fix: QA plan 12-store-credit/05-purchase-with-credit.md now uses a fresh credit customer for subtask 5.7 because cust3 owns Standard Weekly after 5.2 and duplicate/plan-switch validation is expected.

[[2026-05-24]] Sun 10:38
Issue #82 verified closed: fresh-user 5.7 path passed with qa-credit-off #311; apply_at_checkout=false produced full 9.99 cart total and no Store Credit Applied line; cart cleared and setting restored true.
