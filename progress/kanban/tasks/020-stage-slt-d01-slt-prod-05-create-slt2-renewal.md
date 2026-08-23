---
id: 20
title: 'stage-slt-d01: SLT-PROD-05 Create SLT2 Renewal Price Step with a different renewal price after 2 cycles'
status: open
priority: high
created: 2026-08-22T20:43:40.331894799+02:00
updated: 2026-08-22T20:44:13.580996891+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d01
    - slt-prod-05
due: "2026-08-24"
estimate: 40m
depends_on:
    - 10
class: standard
---

Lifecycle task 20 / SLT-PROD-05. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/020-slt-prod-05-create-slt-renewal-price-step-with-a.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
