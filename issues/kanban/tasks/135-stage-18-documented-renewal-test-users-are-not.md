---
id: 135
title: 'stage-18: documented renewal test users are not seeded'
status: closed
priority: critical
created: 2026-05-23T16:12:15.218752827+02:00
updated: 2026-05-24T13:33:05.450727439+02:00
started: 2026-05-24T13:12:12.413961046+02:00
tags:
    - qa
    - stage-18
    - prerequisites
    - test-data
class: standard
---

Stage: qa/stages/18-renewal-followup/README.md\n\nStage 18 prerequisites list these renewal test users/subscriptions:\n- member1@example.com: Active Standard Weekly manual\n- member-stripe@example.com: Active Standard Weekly Stripe success card\n- member-decline@example.com: Active Standard Weekly Stripe decline card\n- member-trial@example.com: Trial Weekly\n- member-trial-down@example.com: Trial Weekly with auto-downgrade\n- member-stepped@example.com: Stepped Weekly\n- member-limited@example.com: Limited 3-Cycle Weekly\n- member-fixed@example.com: Fixed-Period Plan\n\nObserved via WP-CLI before Task 18.01:\n- member1@example.com exists but has subscription #697 on Pro Plan, not Standard Weekly; subscription meta says billing_period=month and recurring_amount=0 despite product weekly.\n- member-stripe@example.com, member-decline@example.com, member-trial@example.com, member-trial-down@example.com, member-stepped@example.com, member-limited@example.com, and member-fixed@example.com are missing as users.\n- One alternate active Standard Weekly manual subscription exists: #357 for customer-block@example.test, payment bacs, next 2026-05-27 09:39:25.\n\nImpact: Stage 18 tasks cannot be executed against the documented users without seeding/fixing test data. Task 18.01 will use #357 as an alternate and restore it afterward.

[[2026-05-24]] Sun 13:25
Resuming after refund-status interruption. Plan: inspect current Stage 18 products/users/subscriptions, seed missing documented users with linked subscriptions, verify via WP-CLI, then close this prerequisite issue.

[[2026-05-24]] Sun 13:33
Fixed Stage 18 documented fixtures. Verified via WP-CLI: member1@example.com sub #1668 Active Standard Weekly manual/BACS; member-stripe@example.com sub #1436 Active Standard Weekly Stripe 4242; member-decline@example.com sub #1467 Active Standard Weekly Stripe 0341 decline token; member-trial@example.com sub #1558 Trial Weekly status arraysubs-trial; member-trial-down@example.com sub #1575 Trial Weekly w/ Downgrade status arraysubs-trial; member-stepped@example.com sub #1587 Active Stepped Weekly on fresh parent order #2851; member-limited@example.com sub #1673 Active Limited 3-Cycle Weekly with completed_payments=2; member-fixed@example.com sub #1687 Active Fixed-Period Plan with fixed end date 2026-06-07. Each target sub has pending renewal invoice/process actions; trial subs also have trial conversion actions. Active full-refunded consistency scan remains clean: active_full_refunded_subscriptions=0.
