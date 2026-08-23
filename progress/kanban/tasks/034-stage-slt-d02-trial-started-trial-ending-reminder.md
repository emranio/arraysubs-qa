---
id: 34
title: 'stage-slt-d02: Trial started, trial-ending reminder at 3 days, and paid trial conversion'
status: open
priority: critical
created: 2026-08-22T20:43:41.392409267+02:00
updated: 2026-08-22T20:44:16.627877482+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d02
    - slt-eml-09
due: "2026-08-25"
estimate: 1h15m
depends_on:
    - 10
    - 11
    - 12
    - 31
    - 38
class: standard
---

Lifecycle task 34 / SLT-EML-09. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/034-trial-started-trial-ending-at-days-before-3-and.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
