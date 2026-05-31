---
id: 81
title: 'stage-12: Checkout credit not applied to subscription order'
status: closed
priority: critical
created: 2026-05-23T09:44:36.049468248+02:00
updated: 2026-05-24T10:32:41.583208357+02:00
started: 2026-05-24T10:18:17.899766309+02:00
completed: 2026-05-24T10:32:41.583207395+02:00
tags:
    - qa
    - stage-12
    - store-credit
    - checkout
claimed_by: shell-quartz
claimed_at: 2026-05-24T10:32:41.583208226+02:00
class: standard
---

Stage 12 Task 05 Subtasks 5.1-5.3. cust3 balance $205, apply_at_checkout=true, min_order_amount=5. Standard Weekly cart/checkout showed no Store Credit/Credit Applied line and total remained $19.99. Placing order #1133 via BACS produced on-hold order total $19.99, no _arraysubs_credit_applied meta, no fee line, cust3 balance remained $205, and related subscription #1135 stayed Pending. Expected credit to cover $19.99, order total $0, balance $185.01, and Active subscription.

[[2026-05-24]] Sun 10:18
Plan: verify checkout using a clean stage-equivalent customer with existing store credit and Standard Weekly cart. If the order still lacks fee/meta or subscription remains pending, patch Store Credit checkout/order capture path. If fixed by current code, close with browser + WP-CLI proof.

[[2026-05-24]] Sun 10:25
Root cause: Store Credit captured checkout deductions only on classic checkout. Block Checkout created order with Store Credit Applied fee, but no credit meta/history/balance mutation because woocommerce_store_api_checkout_order_processed was not hooked. Fix: add Store API order hook and share capture logic with classic hook.

[[2026-05-24]] Sun 10:32
Fixed/verified. Browser Store API checkout with clean user qa-credit-order (#310): cart/checkout showed Standard Weekly 9.99, Store Credit Applied -9.99, total /bin/bash.00; order confirmation #2717 showed total /bin/bash.00 and Active. WP-CLI proof: order status completed, _arraysubs_credit_applied=19.99, _arraysubs_credit_from_customer=19.99, fee Store Credit Applied -19.99, customer credit now 5.01, credit log #2731 source order, subscription #2727 arraysubs-active with _parent_order_id=2717.
