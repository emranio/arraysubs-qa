---
id: 57
title: 'stage-slt-d03: Reconcile the full Mailpit set for one SLT2 Daily Core renewal — no double-send, nothing missing'
status: open
priority: critical
created: 2026-08-22T20:43:45.147532283+02:00
updated: 2026-08-22T20:44:22.704337857+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d03
    - slt-eml-15
due: "2026-08-26"
estimate: 2h
depends_on:
    - 10
    - 5
    - 1
class: standard
---

Lifecycle task 57 / SLT-EML-15. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/057-reconcile-the-full-mailpit-set-for-one-slt-daily.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
