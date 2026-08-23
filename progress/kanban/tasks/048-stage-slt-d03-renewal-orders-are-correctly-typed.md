---
id: 48
title: 'stage-slt-d03: Renewal orders are correctly typed and linked to the parent subscription (HPOS)'
status: open
priority: critical
created: 2026-08-22T20:43:43.744110073+02:00
updated: 2026-08-22T20:44:19.623772693+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d03
    - slt-adm-06
due: "2026-08-26"
estimate: 1h
depends_on:
    - 5
    - 1
    - 41
class: standard
---

Lifecycle task 48 / SLT-ADM-06. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/048-renewal-orders-are-correctly-typed-and-linked-to.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
