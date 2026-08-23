---
id: 108
title: 'stage-slt-d08: Expiring-soon at 7 days and Stripe card-expiring notification'
status: open
priority: high
created: 2026-08-22T20:43:54.091711527+02:00
updated: 2026-08-22T20:44:29.579528052+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d08
    - slt-eml-10
due: "2026-08-31"
estimate: 1h30m
depends_on:
    - 4
    - 18
class: standard
---

Lifecycle task 108 / SLT-EML-10. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/108-expiring-soon-at-days-before-7-and-card-expiring.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
