---
id: 53
title: 'stage-slt-d03: Payment received email after real unattended renewals on Stripe and Paddle'
status: open
priority: critical
created: 2026-08-22T20:43:44.635535965+02:00
updated: 2026-08-22T20:44:21.592246778+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d03
    - slt-eml-03
due: "2026-08-26"
estimate: 1h
depends_on:
    - 5
    - 23
    - 1
    - 29
class: standard
---

Lifecycle task 53 / SLT-EML-03. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/053-payment-received-email-after-real-unattended.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
