---
id: 33
title: 'stage-slt-d02: First renewal declines on SLT2 Retry Daily: order failed, subscription stays active, retry #1 queued +24h'
status: open
priority: critical
created: 2026-08-22T20:43:41.325212775+02:00
updated: 2026-08-22T20:44:16.44051271+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d02
    - slt-dun-01
due: "2026-08-25"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 23
class: standard
---

Lifecycle task 33 / SLT-DUN-01. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/033-first-renewal-declines-on-slt-retry-daily-order.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
