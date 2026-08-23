---
id: 43
title: 'stage-slt-d02: Stripe SCA renewal observation: verify SLT-CHK-05 requires_action email and pay link'
status: open
priority: high
created: 2026-08-22T20:43:42.240297118+02:00
updated: 2026-08-22T20:44:18.263255438+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d02
    - slt-ren-05
due: "2026-08-25"
estimate: 2h
depends_on:
    - 1
    - 10
    - 12
class: standard
---

Lifecycle task 43 / SLT-REN-05. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/043-stripe-sca-at-renewal-4000-0027-6000-3184.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
