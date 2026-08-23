---
id: 63
title: 'stage-slt-d04: Admin schedule change must reschedule both renewal legs; next-payment-date is API-locked'
status: open
priority: critical
created: 2026-08-22T20:43:45.779788274+02:00
updated: 2026-08-22T20:44:23.581171142+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d04
    - slt-adm-03
due: "2026-08-27"
estimate: 1h30m
depends_on:
    - 5
    - 10
    - 11
    - 12
class: standard
---

Lifecycle task 63 / SLT-ADM-03. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/063-admin-schedule-change-must-reschedule-both-renewal.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
