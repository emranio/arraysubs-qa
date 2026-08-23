---
id: 107
title: 'stage-slt-d08: Expired, reactivated and auto-downgrade emails, incl. the expiry-suppression negative'
status: open
priority: high
created: 2026-08-22T20:43:53.543127805+02:00
updated: 2026-08-22T20:44:29.139025753+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d08
    - slt-eml-08
due: "2026-08-31"
estimate: 1h 30m
depends_on:
    - 54
    - 6
    - 60
    - 4
    - 111
class: standard
---

Lifecycle task 107 / SLT-EML-08. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/107-expired-reactivated-and-auto-downgrade-emails-incl.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
