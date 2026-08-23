---
id: 50
title: 'stage-slt-d03: Quantity 3 on a subscription line — assert order total, _quantity, unit _recurring_amount and the renewal amount'
status: open
priority: high
created: 2026-08-22T20:43:44.102295457+02:00
updated: 2026-08-22T20:44:20.354774622+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d03
    - slt-chk-09
due: "2026-08-26"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

Lifecycle task 50 / SLT-CHK-09. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/050-quantity-3-on-a-subscription-line-assert-order.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
