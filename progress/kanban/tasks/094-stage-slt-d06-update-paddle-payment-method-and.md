---
id: 94
title: 'stage-slt-d06: Update Paddle payment method and prove the next remote renewal uses it'
status: open
priority: high
created: 2026-08-22T20:43:50.819768256+02:00
updated: 2026-08-22T20:44:27.421370823+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d06
    - slt-mya-03
due: "2026-08-29"
estimate: 1h30m
depends_on:
    - 29
    - 42
class: standard
---

Lifecycle task 94 / SLT-MYA-03. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/094-update-the-paddle-payment-method-and-prove-the.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
