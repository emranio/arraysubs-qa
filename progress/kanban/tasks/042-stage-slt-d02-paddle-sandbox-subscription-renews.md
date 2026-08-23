---
id: 42
title: 'stage-slt-d02: Paddle sandbox subscription renews unattended with remote/local reconciliation'
status: open
priority: critical
created: 2026-08-22T20:43:42.055285512+02:00
updated: 2026-08-22T20:44:18.123147344+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d02
    - slt-ren-04
due: "2026-08-25"
estimate: 1h30m
depends_on:
    - 26
    - 29
class: standard
---

Lifecycle task 42 / SLT-REN-04. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/042-paddle-sandbox-subscription-renews-unattended.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
