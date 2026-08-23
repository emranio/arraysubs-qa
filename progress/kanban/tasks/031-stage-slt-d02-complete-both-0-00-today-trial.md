---
id: 31
title: 'stage-slt-d02: Complete both $0.00-today trial checkouts: card still collected, first real charge scheduled'
status: open
priority: critical
created: 2026-08-22T20:43:41.19583292+02:00
updated: 2026-08-22T20:44:15.590406543+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d02
    - slt-chk-15
due: "2026-08-25"
estimate: 1h 30m
depends_on:
    - 37
    - 38
    - 10
    - 12
    - 11
class: standard
---

Lifecycle task 31 / SLT-CHK-15. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/031-complete-both-0-00-today-trial-checkouts-card.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
