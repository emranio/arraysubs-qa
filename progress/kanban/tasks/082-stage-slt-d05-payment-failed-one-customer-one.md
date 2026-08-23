---
id: 82
title: 'stage-slt-d05: Payment failed: one customer + one admin email per retry attempt, and whether the attempt number is visible'
status: open
priority: critical
created: 2026-08-22T20:43:48.534593461+02:00
updated: 2026-08-22T20:44:25.833632291+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-eml-04
due: "2026-08-28"
estimate: 1h30m
depends_on:
    - 23
    - 12
    - 33
class: standard
---

Lifecycle task 82 / SLT-EML-04. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/082-payment-failed-one-customer-one-admin-email-per.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
