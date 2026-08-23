---
id: 105
title: 'stage-slt-d07: Charge a non-zero $7.50 upgrade switch fee on Pro→Enterprise and restore the fee to 0 in-task'
status: open
priority: medium
created: 2026-08-22T20:43:53.220501698+02:00
updated: 2026-08-22T20:44:28.625844214+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d07
    - slt-sw-08
due: "2026-08-30"
estimate: 1h 30m
depends_on:
    - 87
    - 104
class: standard
---

Lifecycle task 105 / SLT-SW-08. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/105-charge-a-non-zero-7-50-upgrade-switch-fee-on-pro.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
