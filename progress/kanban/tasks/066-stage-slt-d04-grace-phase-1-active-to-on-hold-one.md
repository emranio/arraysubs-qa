---
id: 66
title: 'stage-slt-d04: Grace phase 1: active to on-hold one day after the due date, with the customer-only on-hold email'
status: open
priority: high
created: 2026-08-22T20:43:46.324637035+02:00
updated: 2026-08-22T20:44:24.166843397+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d04
    - slt-dun-03
due: "2026-08-27"
estimate: 45m
depends_on:
    - 33
class: standard
---

Lifecycle task 66 / SLT-DUN-03. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/066-grace-phase-1-active-to-on-hold-one-day-after-the.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
