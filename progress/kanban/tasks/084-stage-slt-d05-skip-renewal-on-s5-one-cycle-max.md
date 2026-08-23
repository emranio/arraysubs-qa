---
id: 84
title: 'stage-slt-d05: SKIP renewal on S5: one cycle, max-three clamp, undo, notifications and shifted charge'
status: open
priority: medium
created: 2026-08-22T20:43:48.862369874+02:00
updated: 2026-08-22T20:44:26.155395285+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-life-03
due: "2026-08-28"
estimate: 2h
depends_on:
    - 19
class: standard
---

Lifecycle task 84 / SLT-LIFE-03. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/084-skip-renewal-on-s5-one-cycle-then-the-max-three.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
