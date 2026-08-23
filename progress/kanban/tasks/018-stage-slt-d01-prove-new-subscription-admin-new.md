---
id: 18
title: 'stage-slt-d01: Prove new_subscription + admin_new_subscription at a real Stripe block checkout'
status: open
priority: high
created: 2026-08-22T20:43:40.167273336+02:00
updated: 2026-08-22T20:44:13.247030403+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d01
    - slt-eml-06
due: "2026-08-24"
estimate: 1h
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

Lifecycle task 18 / SLT-EML-06. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/018-prove-new-subscription-admin-new-subscription-at-a.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
