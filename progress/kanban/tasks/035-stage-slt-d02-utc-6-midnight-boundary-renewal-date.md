---
id: 35
title: 'stage-slt-d02: UTC+6 midnight-boundary renewal: date correctness in admin, portal, email and order'
status: open
priority: high
created: 2026-08-22T20:43:41.451125939+02:00
updated: 2026-08-22T20:44:16.730078947+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d02
    - slt-imp-01
due: "2026-08-25"
estimate: 1.5h on D2 (late evening) + 30m follow-up on D4
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

Lifecycle task 35 / SLT-IMP-01. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/035-utc-6-midnight-boundary-renewal-date-correctness.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
