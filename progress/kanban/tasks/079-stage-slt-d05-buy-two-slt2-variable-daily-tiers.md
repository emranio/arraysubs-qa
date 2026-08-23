---
id: 79
title: 'stage-slt-d05: Buy two SLT2 Variable Daily tiers and prove per-variation config lands on the subscription'
status: open
priority: high
created: 2026-08-22T20:43:48.077631264+02:00
updated: 2026-08-22T20:44:25.558146987+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-chk-11
due: "2026-08-28"
estimate: 2h
depends_on:
    - 71
    - 10
    - 11
    - 12
class: standard
---

Lifecycle task 79 / SLT-CHK-11. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/079-buy-two-slt-variable-daily-tiers-and-prove-per.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
