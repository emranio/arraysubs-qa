---
id: 1
title: 'stage-slt-d00: Block checkout happy path: slt2-core buys SLT2 Daily Core on Stripe 4242 (control record)'
status: blocked
priority: critical
created: 2026-08-22T20:43:38.665749136+02:00
updated: 2026-08-22T22:35:28.525057726+02:00
started: 2026-08-22T22:35:28.525055331+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d00
    - slt-chk-01
due: "2026-08-23"
estimate: 1h
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 131
class: standard
---

## Result — BLOCKED (site date 2026-08-23)

Critical shared issue #1 / lifecycle preflight 131 blocks the Stripe control checkout: required secondary-webhook events `payment_method.attached` and `customer.updated` are missing. Product `31340` and customer `474` are ready; zero order/subscription/charge was attempted. Retry after task 131 passes.

Lifecycle task 1 / SLT-CHK-01. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/001-block-checkout-happy-path-slt-core-buys-slt-daily.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
