---
id: 25
title: 'stage-slt-d01: SLT-SETUP-04 Create the six SLT2 coupons covering recurring, one-time, N-cycle, fee and reject paths'
status: open
priority: high
created: 2026-08-22T20:43:40.677285006+02:00
updated: 2026-08-22T20:44:14.343975901+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d01
    - slt-setup-04
due: "2026-08-24"
estimate: 1h
depends_on:
    - 10
class: standard
---

Lifecycle task 25 / SLT-SETUP-04. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/025-slt-setup-04-create-the-six-slt-coupons-covering.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
