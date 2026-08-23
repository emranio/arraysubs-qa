---
id: 78
title: 'stage-slt-d05: Anonymous guest checkout of a non-subscription cart must still work — forced registration is scoped to subscription carts only'
status: open
priority: high
created: 2026-08-22T20:43:47.955118164+02:00
updated: 2026-08-22T20:44:25.423566047+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-chk-10
due: "2026-08-28"
estimate: 1h
depends_on:
    - 10
    - 11
    - 5
    - 59
class: standard
---

Lifecycle task 78 / SLT-CHK-10. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/078-anonymous-guest-checkout-of-a-non-subscription.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
