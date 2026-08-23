---
id: 2
title: 'stage-slt-d00: Classic checkout parity: same SLT2 Daily Core purchase, meta diffed field-by-field against CHK-01'
status: blocked
priority: critical
created: 2026-08-22T20:43:38.739381924+02:00
updated: 2026-08-23T10:57:54.843214696+02:00
started: 2026-08-22T22:35:28.602883754+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d00
    - slt-chk-02
due: "2026-08-23"
estimate: 1h15m
depends_on:
    - 1
    - 10
    - 131
claimed_by: flora-pulse
claimed_at: 2026-08-23T10:57:54.842120843+02:00
class: standard
---

## Result — BLOCKED (site date 2026-08-23)

Critical shared issue #1 / lifecycle preflight 131 and source checkout task 1 block classic parity. Zero order/subscription/charge was attempted. Retry after the live Stripe endpoint health check and task 1 both pass.

Lifecycle task 2 / SLT-CHK-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/002-classic-checkout-parity-same-slt-daily-core.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
