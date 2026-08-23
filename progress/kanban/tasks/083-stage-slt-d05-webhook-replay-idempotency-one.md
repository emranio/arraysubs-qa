---
id: 83
title: 'stage-slt-d05: Webhook replay idempotency: one Stripe and one Paddle renewal event, no duplicates'
status: open
priority: high
created: 2026-08-22T20:43:48.650826777+02:00
updated: 2026-08-22T20:44:25.972979193+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-imp-02
due: "2026-08-28"
estimate: 3h
depends_on:
    - 5
    - 23
    - 26
    - 9
    - 42
class: standard
---

Lifecycle task 83 / SLT-IMP-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/083-webhook-replay-idempotency-one-stripe-and-one.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
