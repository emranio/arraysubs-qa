---
id: 81
title: 'stage-slt-d05: Retry attempts 2 and 3 reuse the same failed order, then the 4th charge hits the 3-retry cap'
status: open
priority: high
created: 2026-08-22T20:43:48.408694932+02:00
updated: 2026-08-22T20:44:25.751898253+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-dun-02
due: "2026-08-28"
estimate: 1h
depends_on:
    - 33
    - 66
class: standard
---

Lifecycle task 81 / SLT-DUN-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/081-retry-attempts-2-and-3-reuse-the-same-failed-order.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
