---
id: 17
title: 'stage-slt-d01: SLT2FIX5FIRST $5 fixed one-time coupon on classic checkout - first order discounted, first renewal at full price'
status: open
priority: critical
created: 2026-08-22T20:43:40.086373761+02:00
updated: 2026-08-22T20:44:12.73328262+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d01
    - slt-cpn-02
due: "2026-08-24"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 5
class: standard
---

Lifecycle task 17 / SLT-CPN-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/017-sltfix5first-5-fixed-one-time-coupon-on-classic.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
