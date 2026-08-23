---
id: 64
title: 'stage-slt-d04: Existing active subscriber buys a second, different subscription — auto_migrate_on_checkout is gated off; document what migration would do'
status: open
priority: high
created: 2026-08-22T20:43:46.129815085+02:00
updated: 2026-08-22T20:44:23.742582188+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d04
    - slt-chk-08
due: "2026-08-27"
estimate: 1h 30m
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 58
class: standard
---

Lifecycle task 64 / SLT-CHK-08. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/064-existing-active-subscriber-buys-a-second-different.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
