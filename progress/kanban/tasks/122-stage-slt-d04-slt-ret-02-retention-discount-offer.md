---
id: 122
title: 'stage-slt-d04: SLT-RET-02 Retention discount offer eligibility, acceptance, cycle accounting and limits'
status: open
priority: critical
created: 2026-08-22T20:43:58.370678354+02:00
updated: 2026-08-22T20:44:32.060237887+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d04
    - slt-ret-02
due: "2026-08-27"
estimate: 2h30m
depends_on:
    - 60
    - 72
    - 121
class: standard
---

Lifecycle task 122 / SLT-RET-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/122-slt-ret-02-discount-offer-all-conditions.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
