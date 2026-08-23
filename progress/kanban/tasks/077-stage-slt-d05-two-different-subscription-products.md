---
id: 77
title: 'stage-slt-d05: Two different subscription products in one cart must be rejected — capture the exact string on every add-to-cart surface'
status: open
priority: high
created: 2026-08-22T20:43:47.599247548+02:00
updated: 2026-08-22T20:44:25.331019505+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-chk-06
due: "2026-08-28"
estimate: 1h
depends_on:
    - 10
    - 11
    - 5
    - 6
class: standard
---

Lifecycle task 77 / SLT-CHK-06. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/077-two-different-subscription-products-in-one-cart.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
