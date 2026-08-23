---
id: 9
title: 'stage-slt-d00: SLT2 Daily Core renews unattended overnight — first cycle, spread-offset window, cron-not-CLI proof'
status: blocked
priority: critical
created: 2026-08-22T20:43:39.488561098+02:00
updated: 2026-08-22T22:35:28.875098625+02:00
started: 2026-08-22T22:35:28.875096902+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d00
    - slt-ren-01
due: "2026-08-23"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 1
    - 131
class: standard
---

## Result — BLOCKED (site date 2026-08-23)

Critical shared issue #1 / lifecycle preflight 131 and missing source checkout task 1 mean no valid subscription/action/mail gate exists yet. No renewal was forced or fabricated. Retry by completing task 1, then publish the fresh natural-renewal gate.

Lifecycle task 9 / SLT-REN-01. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/009-slt-daily-core-renews-unattended-overnight-first.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
