---
id: 73
title: 'stage-slt-d04: Retention: accept the 20%-for-3-cycles discount and prove exactly 3 discounted renewals, plus a downgrade offer'
status: open
priority: critical
created: 2026-08-22T20:43:47.145834073+02:00
updated: 2026-08-22T20:44:24.905528008+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d04
    - slt-sw-09
due: "2026-08-27"
estimate: 2h
depends_on:
    - 11
    - 12
    - 60
class: standard
---

Lifecycle task 73 / SLT-SW-09. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/073-retention-accept-the-20-for-3-cycles-discount-and.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
