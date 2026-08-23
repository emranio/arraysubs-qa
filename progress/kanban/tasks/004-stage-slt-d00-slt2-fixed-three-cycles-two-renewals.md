---
id: 4
title: 'stage-slt-d00: SLT2 Fixed Three Cycles: two renewals, short-horizon expiring-soon, final expiry'
status: blocked
priority: critical
created: 2026-08-22T20:43:38.929396502+02:00
updated: 2026-08-22T22:35:28.79864417+02:00
started: 2026-08-22T22:35:28.798643118+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d00
    - slt-life-04
due: "2026-08-23"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 6
    - 131
class: standard
---

## Result — BLOCKED (site date 2026-08-23)

Critical shared issue #1 / lifecycle preflight 131 blocks starting the finite timed Stripe chain. Product `31347` is ready; zero order/subscription/charge was attempted. Retry immediately after task 131 passes so both natural renewals remain observable.

Lifecycle task 4 / SLT-LIFE-04. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/004-slt-fixed-three-cycles-to-its-natural-end-two.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
