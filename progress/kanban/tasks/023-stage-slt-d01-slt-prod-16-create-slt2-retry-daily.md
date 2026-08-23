---
id: 23
title: 'stage-slt-d01: SLT-PROD-16 Create SLT2 Retry Daily and SLT2 Paddle Daily, the two gateway-path products'
status: open
priority: critical
created: 2026-08-22T20:43:40.552625579+02:00
updated: 2026-08-22T20:44:14.02461937+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d01
    - slt-prod-16
due: "2026-08-24"
estimate: 45m
depends_on:
    - 10
    - 11
class: standard
---

Lifecycle task 23 / SLT-PROD-16. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/023-slt-prod-16-create-slt-retry-daily-and-slt-paddle.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
