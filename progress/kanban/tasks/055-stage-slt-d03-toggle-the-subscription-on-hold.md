---
id: 55
title: 'stage-slt-d03: Toggle the Subscription On Hold customer email OFF, prove silence, restore ON, prove delivery'
status: open
priority: critical
created: 2026-08-22T20:43:44.852016833+02:00
updated: 2026-08-22T20:44:22.06218462+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d03
    - slt-eml-11
due: "2026-08-26"
estimate: 1h 30m
depends_on:
    - 10
    - 11
    - 12
    - 7
class: standard
---

Lifecycle task 55 / SLT-EML-11. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/055-toggle-the-subscription-on-hold-customer-email-off.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
