---
id: 109
title: 'stage-slt-d08: Negative sweep: cancelled gets no reminders, lifetime gets no renewal mail, expired gets no further mail'
status: open
priority: critical
created: 2026-08-22T20:43:54.447443538+02:00
updated: 2026-08-22T20:44:29.70478863+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d08
    - slt-eml-14
due: "2026-08-31"
estimate: 2h
depends_on:
    - 6
    - 7
    - 23
    - 68
    - 3
    - 4
    - 54
    - 101
    - 110
class: standard
---

Lifecycle task 109 / SLT-EML-14. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/109-negative-sweep-cancelled-gets-no-reminders.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
