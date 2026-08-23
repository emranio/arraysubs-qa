---
id: 32
title: 'stage-slt-d02: SLT2REC3 vs SLT2REC3NOINIT on block checkout - N-cycle counting and the exact renewal where the discount stops'
status: open
priority: critical
created: 2026-08-22T20:43:41.264826902+02:00
updated: 2026-08-22T20:44:16.261829974+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d02
    - slt-cpn-03
due: "2026-08-25"
estimate: 1h 45m
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 5
    - 16
class: standard
---

Lifecycle task 32 / SLT-CPN-03. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/032-sltrec3-vs-sltrec3noinit-on-block-checkout-n-cycle.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
