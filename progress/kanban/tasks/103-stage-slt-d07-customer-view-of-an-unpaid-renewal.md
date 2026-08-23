---
id: 103
title: 'stage-slt-d07: Customer view of an unpaid renewal invoice and paying it from the portal before the automatic charge leg'
status: open
priority: high
created: 2026-08-22T20:43:52.679419179+02:00
updated: 2026-08-22T20:44:28.309043527+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d07
    - slt-mya-04
due: "2026-08-30"
estimate: 1.5h
depends_on:
    - 70
    - 58
    - 49
class: standard
---

Lifecycle task 103 / SLT-MYA-04. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/103-customer-view-of-an-unpaid-renewal-invoice-and.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
