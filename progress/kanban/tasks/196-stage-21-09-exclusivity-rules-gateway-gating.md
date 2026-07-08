---
id: 196
title: 'stage-21: 09 Exclusivity Rules & Gateway Gating'
status: closed
priority: high
created: 2026-07-08T02:50:39.703283+06:00
updated: 2026-07-08T01:34:38.075964117+02:00
started: 2026-07-08T01:17:13.925295591+02:00
completed: 2026-07-08T01:34:38.075963196+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 45m
depends_on:
    - 188
claimed_by: reef-maple
claimed_at: 2026-07-08T01:34:38.075964017+02:00
class: standard
---

Source: stages/21-flexible-renewal-sync/09-exclusivity-and-gateway-gating.md

Feature must be inert with Different Renewal Price / Trial / Lifetime (section hidden in admin AND anniversary behavior at checkout). Unsupported gateways (Paddle/PayPal) are hidden from checkout for flex carts and rejected on forced submit; bank transfer + Stripe remain. Cross-checks: per-product segments override the global sync setting's mode; non-flex products keep global behavior; mixed cart with a non-subscription product totals correctly.

[[2026-07-08]] Wed 01:34
QA result: EXECUTED with issue #178. Different Renewal Price exclusivity passed after enabling the actual _enable_renewal_price toggle: admin editor had the toggle checked and no Flexible Renewal Sync section; checkout showed $30.00 today, $30.00 first payment then $25.00 every 1 month, next charge 8 August 2026 UTC+6, and no sync/prorate wording. Trial exclusivity passed: trial length 7 hid flex in admin; checkout showed Free today and next charge 15 July 2026 UTC+6, no sync rows. Lifetime exclusivity passed: period=lifetime hid flex in admin; product/checkout showed one-time Lifetime Deal / No recurring charges and no sync rows. All settings were restored after each exclusivity run.

Gateway gating passed visibly. Normal Members Tee #692 checkout listed Stripe, Paddle, BACS, cheque, and COD after billing fields were available. Flex cart for FRS Monthly 30 listed only Stripe, BACS, and cheque; Paddle and COD were absent. I could set Woo Blocks internal active payment method to arraysubs_paddle, but because the method was not rendered/available the checkout did not submit or produce a server validation message, so the forced-submit backstop was not practically reachable through the rendered checkout. Global sync interplay passed: with global sync ON and mode full, FRS Monthly 30 segment-2 flex still prorated to $22.26 and next charge 1 August, while Basic Monthly #197 without flex used global full first charge $29.99 and next charge 1 August. Global sync was restored OFF.

Mixed cart subtask 9.6 failed and was logged as issue #178: attempting FRS Monthly 30 + Members Tee produced the mixed-cart-disabled notice and the checkout retained only the subscription line, despite settings split showing cart.allow_mixed_cart=true and multiple_subscriptions.allow_mixed_cart=false. Final clean-flex smoke passed after restore: FRS Monthly 30 all segments active, boundaries 10/20, no trial/different-renewal/lifetime, checkout showed $30.00 full first charge and next charge 1 August; available gateways were Stripe, BACS, and cheque. debug.log stayed at 1702 lines. Screenshots: qa/artifacts/stage-21-task-196-different-renewal-checkout-valid.png, qa/artifacts/stage-21-task-196-trial-checkout.png, qa/artifacts/stage-21-task-196-lifetime-product.png, qa/artifacts/stage-21-task-196-lifetime-checkout.png, qa/artifacts/stage-21-task-196-normal-product-gateways-filled.png, qa/artifacts/stage-21-task-196-flex-product-gateways.png, qa/artifacts/stage-21-task-196-forced-paddle-submit.png, qa/artifacts/stage-21-task-196-global-flex-overrides.png, qa/artifacts/stage-21-task-196-global-plain.png, qa/artifacts/stage-21-task-196-mixed-cart-attempt2.png, qa/artifacts/stage-21-task-196-final-clean-flex-smoke.png
