---
id: 65
title: 'stage-slt-d04: Buy SLT2 Box Daily through the wizard: contents selection, order lines and box meta on the subscription'
status: open
priority: high
created: 2026-08-22T20:43:46.234313735+02:00
updated: 2026-08-22T20:44:23.991969676+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d04
    - slt-chk-13
due: "2026-08-27"
estimate: 1h 30m
depends_on:
    - 59
    - 11
    - 12
class: standard
---

Lifecycle task 65 / SLT-CHK-13. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/065-buy-slt-box-daily-through-the-wizard-contents.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
