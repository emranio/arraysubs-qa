---
id: 14
title: 'stage-slt-d00: Segment 1 full: prove the full recurring charge now and the exact next-cycle boundary date'
status: blocked
priority: critical
created: 2026-08-22T20:43:39.881302637+02:00
updated: 2026-08-22T22:35:29.001118113+02:00
started: 2026-08-22T22:35:29.001117041+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d00
    - slt-syn-05
due: "2026-08-23"
estimate: 1.5h
depends_on:
    - 10
    - 11
    - 12
    - 8
    - 131
class: standard
---

## Result — BLOCKED (site date 2026-08-23)

Critical shared issue #1 / lifecycle preflight 131 blocks the Stripe segment-1 purchase. Product `31363`, customer `477`, live week boundary and D0 `$14.00` cart proof are ready; zero order/subscription/charge was attempted. Retry after task 131 passes.

Lifecycle task 14 / SLT-SYN-05. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/014-segment-1-full-prove-the-full-recurring-charge-now.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
