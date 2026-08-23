---
id: 85
title: 'stage-slt-d05: Update the Stripe payment method from My Account and prove the next unassisted renewal charges the new card'
status: open
priority: critical
created: 2026-08-22T20:43:48.966569879+02:00
updated: 2026-08-22T20:44:26.275510425+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d05
    - slt-mya-02
due: "2026-08-28"
estimate: 1.5h
depends_on:
    - 70
    - 11
    - 5
class: standard
---

Lifecycle task 85 / SLT-MYA-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/085-update-the-stripe-payment-method-from-my-account.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.
