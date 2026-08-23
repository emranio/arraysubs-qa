---
id: 93
title: 'stage-slt-d06: EARLY renew from the customer portal: full amount, next date anchored to the original due date, legs replaced'
status: open
priority: high
created: 2026-08-22T20:43:50.71044302+02:00
updated: 2026-08-22T20:44:27.32362899+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d06
    - slt-life-02
due: "2026-08-29"
estimate: 1h30m
depends_on:
    - 11
    - 5
    - 2
class: standard
---

Lifecycle task 93 / SLT-LIFE-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/093-early-renew-from-the-customer-portal-full-amount.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
