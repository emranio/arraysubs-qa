---
id: 124
title: 'stage-slt-d05: SLT-RET-04 Downgrade retention offer target, no-target, proration and renewal conditions'
status: open
priority: critical
created: 2026-08-22T20:43:59.03711939+02:00
updated: 2026-08-22T20:44:32.430179933+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-ret-04
due: "2026-08-28"
estimate: 2h15m
depends_on:
    - 60
    - 72
    - 121
class: standard
---

Lifecycle task 124 / SLT-RET-04. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/124-slt-ret-04-downgrade-offer-all-conditions.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
