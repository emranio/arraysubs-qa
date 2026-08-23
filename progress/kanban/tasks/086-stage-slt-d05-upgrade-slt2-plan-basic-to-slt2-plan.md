---
id: 86
title: 'stage-slt-d05: Upgrade SLT2 Plan Basic to SLT2 Plan Pro on Stripe with prorate_immediately arithmetic'
status: open
priority: high
created: 2026-08-22T20:43:49.049762274+02:00
updated: 2026-08-22T20:44:26.416521268+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-sw-01
due: "2026-08-28"
estimate: 1h 15m
depends_on:
    - 60
    - 11
    - 12
    - 72
class: standard
---

Lifecycle task 86 / SLT-SW-01. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/086-upgrade-slt-plan-basic-to-slt-plan-pro-on-stripe.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
