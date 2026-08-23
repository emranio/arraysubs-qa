---
id: 52
title: 'stage-slt-d03: Renewal ALERT: prove the 3-day upcoming-renewal reminder fires once, on the right subscription, and never twice'
status: open
priority: high
created: 2026-08-22T20:43:44.486130714+02:00
updated: 2026-08-22T20:44:21.412902604+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d03
    - slt-eml-01
due: "2026-08-26"
estimate: 1h
depends_on:
    - 22
    - 5
    - 12
    - 28
    - 1
class: standard
---

Lifecycle task 52 / SLT-EML-01. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/052-renewal-alert-prove-the-3-day-upcoming-renewal.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
