---
id: 30
title: 'stage-slt-d02: Classic checkout with Stripe SCA card: 3DS at signup, then requires_action on the off-session renewal'
status: open
priority: high
created: 2026-08-22T20:43:41.117731522+02:00
updated: 2026-08-22T20:44:15.502119414+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d02
    - slt-chk-05
due: "2026-08-25"
estimate: 1h45m
depends_on:
    - 1
    - 10
    - 12
class: standard
---

Lifecycle task 30 / SLT-CHK-05. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/030-classic-checkout-with-stripe-sca-card-3ds-at.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
