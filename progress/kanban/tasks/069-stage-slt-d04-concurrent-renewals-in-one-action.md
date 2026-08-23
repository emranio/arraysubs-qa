---
id: 69
title: 'stage-slt-d04: Concurrent renewals in one Action Scheduler window: no skips, no double charges, offsets stagger'
status: open
priority: high
created: 2026-08-22T20:43:46.741134129+02:00
updated: 2026-08-22T20:44:24.50354011+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d04
    - slt-imp-03
due: "2026-08-27"
estimate: 2h on D4 + 45m follow-up on D5
depends_on:
    - 11
    - 12
    - 5
class: standard
---

Lifecycle task 69 / SLT-IMP-03. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/069-concurrent-renewals-in-one-action-scheduler-window.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
