---
id: 74
title: 'stage-slt-d04: Segment 3 next_cycle: full charge now, first renewal exactly one cycle past segment 1''s'
status: open
priority: critical
created: 2026-08-22T20:43:47.239296693+02:00
updated: 2026-08-22T20:44:24.99487088+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d04
    - slt-syn-07
due: "2026-08-27"
estimate: 1.5h
depends_on:
    - 14
    - 8
    - 13
class: standard
---

Lifecycle task 74 / SLT-SYN-07. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/074-segment-3-next-cycle-full-charge-now-first-renewal.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
