---
id: 59
title: 'stage-04: 06 One Subscription Per Customer with Auto-Migrate'
status: closed
priority: high
created: 2026-05-19T22:56:06.573768935+02:00
updated: 2026-05-20T11:26:50.591833636+02:00
started: 2026-05-20T10:28:05.925779794+02:00
completed: 2026-05-20T11:26:50.591832624+02:00
tags:
    - qa
    - stage-04
claimed_by: mold-glade
claimed_at: 2026-05-20T11:26:50.591833546+02:00
class: standard
---

Source: stages/04-cart-rules/06-one-per-customer-with-auto-migrate.md

[[2026-05-20]] Wed 11:26
QA done. Configured cust1 (#5 / cust1@test.local) to one active Basic Plan subscription using existing subscription #271, then enabled one-per-customer + auto-migrate. Browser as cust1 added Pro Plan successfully with no one-subscription block. Cart/checkout displayed migration summary: replaces current Basic Plan, due today 0.00, credit applied .99, recurring 9.99 every week. Completed checkout with Check payments order #314; after marking payment complete, subscription #271 migrated in place to product #233 Pro Plan with recurring amount 19.99. Negative check with auto-migrate disabled: Enterprise Plan add blocked with one-subscription-per-customer message and empty cart. Defaults restored: one_per_customer=false, auto_migrate_on_checkout=false. Left cust1 active subscription #271 on Pro Plan; cancelled seeded trial #306 remains.
