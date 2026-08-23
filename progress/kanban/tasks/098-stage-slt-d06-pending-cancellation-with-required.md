---
id: 98
title: 'stage-slt-d06: Pending cancellation with required reason, declined retention offers, natural cancel, reactivation'
status: open
priority: critical
created: 2026-08-22T20:43:51.714538504+02:00
updated: 2026-08-22T20:44:27.87580008+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d06
    - slt-sw-10
due: "2026-08-29"
estimate: 1h45m
depends_on:
    - 60
    - 72
    - 121
class: standard
---

Lifecycle task 98 / SLT-SW-10. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/098-pending-cancellation-with-a-required-reason-and.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
