---
id: 41
title: 'stage-slt-d02: Second unassisted renewal of the same subscription — schedule re-arms at the same offset, no drift'
status: open
priority: critical
created: 2026-08-22T20:43:41.962436381+02:00
updated: 2026-08-22T20:44:18.027354462+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d02
    - slt-ren-02
due: "2026-08-25"
estimate: 1h
depends_on:
    - 9
class: standard
---

Lifecycle task 41 / SLT-REN-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/041-second-unassisted-renewal-of-the-same-subscription.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
