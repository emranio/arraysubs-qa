---
id: 90
title: 'stage-slt-d06: Mixed cart: subscription + plain product — one order, only the subscription line creates a subscription, only it renews'
status: open
priority: high
created: 2026-08-22T20:43:50.041674045+02:00
updated: 2026-08-22T20:44:26.966994553+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d06
    - slt-chk-07
due: "2026-08-29"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 39
    - 77
class: standard
---

Lifecycle task 90 / SLT-CHK-07. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/090-mixed-cart-subscription-plain-product-one-order.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
