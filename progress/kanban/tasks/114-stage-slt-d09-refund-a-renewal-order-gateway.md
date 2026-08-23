---
id: 114
title: 'stage-slt-d09: Refund a renewal order: gateway refund, subscription effect, and emails'
status: open
priority: high
created: 2026-08-22T20:43:55.903795854+02:00
updated: 2026-08-22T20:44:30.252126122+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d09
    - slt-adm-08
due: "2026-09-01"
estimate: 1h30m
depends_on:
    - 48
    - 49
    - 58
    - 20
    - 19
class: standard
---

Lifecycle task 114 / SLT-ADM-08. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/114-refund-a-renewal-order-gateway-refund-subscription.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
