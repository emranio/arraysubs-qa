---
id: 51
title: 'stage-slt-d03: SLT2NOSUB rejected on a subscription-only classic cart - exact message, mixed-cart partial discount, undiscounted renewal'
status: open
priority: high
created: 2026-08-22T20:43:44.355860958+02:00
updated: 2026-08-22T20:44:20.55826059+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d03
    - slt-cpn-04
due: "2026-08-26"
estimate: 1h 30m
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 5
    - 39
    - 61
class: standard
---

Lifecycle task 51 / SLT-CPN-04. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/051-sltnosub-rejected-on-a-subscription-only-classic.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
