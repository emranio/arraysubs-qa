---
id: 87
title: 'stage-slt-d05: Mid-cycle Basic→Pro upgrade, then prove the D6 renewal charges $15.00 on the unchanged due date'
status: open
priority: critical
created: 2026-08-22T20:43:49.215087263+02:00
updated: 2026-08-22T20:44:26.506892399+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-sw-06
due: "2026-08-28"
estimate: 2h
depends_on:
    - 11
    - 12
    - 60
class: standard
---

Lifecycle task 87 / SLT-SW-06. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/087-mid-cycle-basic-pro-upgrade-then-prove-the-d6.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
