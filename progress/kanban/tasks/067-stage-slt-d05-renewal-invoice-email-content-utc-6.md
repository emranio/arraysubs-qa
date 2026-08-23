---
id: 67
title: 'stage-slt-d05: Renewal invoice email: content, UTC+6 due date, and a pay-link that resolves to a real payable order'
status: open
priority: high
created: 2026-08-22T20:43:46.484487546+02:00
updated: 2026-08-22T20:44:24.322881242+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-eml-02
due: "2026-08-28"
estimate: 1h30m
depends_on:
    - 5
    - 52
    - 1
class: standard
---

Lifecycle task 67 / SLT-EML-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/067-renewal-invoice-email-content-utc-6-due-date-and-a.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
