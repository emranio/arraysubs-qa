---
id: 91
title: 'stage-slt-d06: HTML vs plain-text rendering of the renewal invoice and payment-received emails, and link resolution'
status: open
priority: medium
created: 2026-08-22T20:43:50.145563663+02:00
updated: 2026-08-22T20:44:27.067192948+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d06
    - slt-eml-05
due: "2026-08-29"
estimate: 1h30m
depends_on:
    - 67
    - 53
class: standard
---

Lifecycle task 91 / SLT-EML-05. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/091-html-vs-plain-text-rendering-of-the-renewal.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
