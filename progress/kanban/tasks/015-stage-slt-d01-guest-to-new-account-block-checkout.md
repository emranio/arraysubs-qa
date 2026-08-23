---
id: 15
title: 'stage-slt-d01: Guest to new account: block checkout mints slt2-guest-d0 mid-flow and owns order + subscription'
status: open
priority: critical
created: 2026-08-22T20:43:39.944249286+02:00
updated: 2026-08-22T20:44:11.950132092+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d01
    - slt-chk-03
due: "2026-08-24"
estimate: 1h
depends_on:
    - 1
    - 5
    - 12
class: standard
---

Lifecycle task 15 / SLT-CHK-03. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/015-guest-to-new-account-block-checkout-mints-slt.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
