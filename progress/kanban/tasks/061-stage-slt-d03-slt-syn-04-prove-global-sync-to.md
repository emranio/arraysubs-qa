---
id: 61
title: 'stage-slt-d03: SLT-SYN-04 Prove global sync_to_billing_cycle=true + first_charge_mode=full, and that flex overrides it'
status: open
priority: critical
created: 2026-08-22T20:43:45.59140748+02:00
updated: 2026-08-22T20:44:23.298802993+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d03
    - slt-syn-04
due: "2026-08-26"
estimate: 2h
depends_on:
    - 11
    - 12
    - 26
    - 21
    - 27
class: standard
---

Lifecycle task 61 / SLT-SYN-04. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/061-slt-syn-04-prove-global-sync-to-billing-cycle-true.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
