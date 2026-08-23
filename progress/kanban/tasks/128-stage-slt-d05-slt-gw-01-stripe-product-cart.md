---
id: 128
title: 'stage-slt-d05: SLT-GW-01 Stripe product, cart, checkout, SCA and cancellation matrix'
status: open
priority: critical
created: 2026-08-22T20:44:00.282358993+02:00
updated: 2026-08-22T20:44:32.946278551+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-gw-01
due: "2026-08-28"
estimate: 4h
depends_on:
    - 5
    - 39
    - 59
    - 65
    - 71
    - 77
    - 131
class: standard
---

Lifecycle task 128 / SLT-GW-01. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/128-slt-gw-01-stripe-product-cart-lifecycle-matrix.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
