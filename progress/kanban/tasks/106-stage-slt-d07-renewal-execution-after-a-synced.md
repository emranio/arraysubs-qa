---
id: 106
title: 'stage-slt-d07: Renewal execution after a synced first charge: second charge full on the boundary, third on the grid'
status: open
priority: critical
created: 2026-08-22T20:43:53.402783444+02:00
updated: 2026-08-22T20:44:28.811416028+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d07
    - slt-syn-09
due: "2026-08-30"
estimate: 2h
depends_on:
    - 14
    - 45
    - 28
class: standard
---

Lifecycle task 106 / SLT-SYN-09. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/106-renewal-execution-after-a-synced-first-charge.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
