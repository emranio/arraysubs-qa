---
id: 102
title: 'stage-slt-d07: Mid-grace recovery: new card in My Account, pay the failed renewal, and prove the next-payment anchor'
status: open
priority: high
created: 2026-08-22T20:43:52.408886473+02:00
updated: 2026-08-22T20:44:28.225613057+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d07
    - slt-dun-05
due: "2026-08-30"
estimate: 2h
depends_on:
    - 101
    - 36
class: standard
---

Lifecycle task 102 / SLT-DUN-05. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/102-mid-grace-recovery-new-card-in-my-account-pay-the.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
