---
id: 101
title: 'stage-slt-d07: Grace phase 2: terminal cancellation three days after the hold, with customer and admin cancel emails'
status: open
priority: high
created: 2026-08-22T20:43:52.083830301+02:00
updated: 2026-08-22T20:44:28.130432492+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d07
    - slt-dun-04
due: "2026-08-30"
estimate: 1h
depends_on:
    - 81
    - 66
class: standard
---

Lifecycle task 101 / SLT-DUN-04. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/101-grace-phase-2-terminal-cancellation-three-days.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
