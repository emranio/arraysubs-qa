---
id: 16
title: 'stage-slt-d01: SLT2PCT20REC 20% recurring coupon on block checkout - discounted first charge, discount persists to every renewal'
status: open
priority: critical
created: 2026-08-22T20:43:40.008024063+02:00
updated: 2026-08-22T20:44:12.082306921+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d01
    - slt-cpn-01
due: "2026-08-24"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 5
class: standard
---

Lifecycle task 16 / SLT-CPN-01. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/016-sltpct20rec-20-recurring-coupon-on-block-checkout.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
