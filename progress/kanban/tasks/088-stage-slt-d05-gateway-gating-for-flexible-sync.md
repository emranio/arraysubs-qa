---
id: 88
title: 'stage-slt-d05: Gateway gating for flexible sync: Paddle hidden from the DOM and blocked at submit, Stripe syncs to the midnight boundary'
status: open
priority: critical
created: 2026-08-22T20:43:49.344019937+02:00
updated: 2026-08-22T20:44:26.777312485+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-syn-12
due: "2026-08-28"
estimate: 2h
depends_on:
    - 10
    - 11
    - 26
    - 23
class: standard
---

Lifecycle task 88 / SLT-SYN-12. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/088-gateway-gating-for-flexible-sync-paddle-hidden.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
