---
id: 29
title: 'stage-slt-d02: Paddle sandbox purchase: SLT2 Paddle Daily overlay, webhook-paid order, remote schedule sync'
status: open
priority: critical
created: 2026-08-22T20:43:41.030887366+02:00
updated: 2026-08-22T20:44:15.238207337+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d02
    - slt-chk-04
due: "2026-08-25"
estimate: 1h30m
depends_on:
    - 23
    - 26
class: standard
---

Lifecycle task 29 / SLT-CHK-04. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/029-paddle-sandbox-purchase-slt-paddle-daily-via.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
