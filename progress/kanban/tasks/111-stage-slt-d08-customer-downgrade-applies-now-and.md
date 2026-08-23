---
id: 111
title: 'stage-slt-d08: Customer downgrade applies now, and targeted D8 expiry auto-downgrade with its email'
status: open
priority: high
created: 2026-08-22T20:43:55.062501189+02:00
updated: 2026-08-22T20:44:29.94207951+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d08
    - slt-sw-02
due: "2026-08-31"
estimate: 1h 45m
depends_on:
    - 86
    - 95
    - 60
    - 99
class: standard
---

Lifecycle task 111 / SLT-SW-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/111-customer-downgrade-applies-now-and-expiry-auto.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
