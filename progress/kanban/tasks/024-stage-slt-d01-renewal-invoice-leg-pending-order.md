---
id: 24
title: 'stage-slt-d01: Renewal-invoice leg: pending order and invoice email at due+offset-6h, before the charge leg'
status: open
priority: high
created: 2026-08-22T20:43:40.611508491+02:00
updated: 2026-08-22T20:44:14.153076353+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d01
    - slt-ren-03
due: "2026-08-24"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 5
class: standard
---

Lifecycle task 24 / SLT-REN-03. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/024-renewal-invoice-leg-pending-order-and-invoice.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
