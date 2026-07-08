---
id: 191
title: 'stage-21: 04 Manual Gateway — Segment 3 Next-Cycle Checkout'
status: closed
priority: high
created: 2026-07-08T02:50:18.587487+06:00
updated: 2026-07-08T00:12:06.503702692+02:00
started: 2026-07-08T00:05:34.348095038+02:00
completed: 2026-07-08T00:12:06.50370154+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 30m
depends_on:
    - 188
claimed_by: reef-maple
claimed_at: 2026-07-08T00:12:06.503702572+02:00
class: standard
---

Source: stages/21-flexible-renewal-sync/04-manual-next-cycle-checkout.md

Complete Direct-bank-transfer checkout with purchase day in segment 3. Required conditions: full $30 today, 'First billing cycle' note (payment covers cycle starting B1), next payment = B2 = boundary+1 cycle, NO invoice/renewal jobs at B1, mode=next_cycle, customer gets bonus access days until B1.

[[2026-07-08]] Wed 00:12
QA result: PASS with known issue #172 repeated. Boundaries 3/6 put D=8 in Segment 3. Checkout summary showed 0 today, Next charge 1 September 2026 UTC+6 (0), First billing cycle note covering 1 August 2026 UTC+6, total 0. BACS order #8714 created subscription #8728 pending with next payment 2026-08-31 18:00:00 UTC, mode next_cycle, cycle start 2026-07-31 18:00:00 UTC, first full renewal 2026-08-31 18:00:00 UTC, recurring amount 30. After processing, #8728 active, completed payments 1, next payment unchanged. Scheduler rows exist only for B2: renewal invoice pending 2026-08-31 12:00:00 UTC and process renewal pending 2026-08-31 18:00:00 UTC; no B1 linked renewal rows found. Customer My Account list/detail show Active, next payment 1 September 2026 12:00 AM UTC+6, 0 Every month. Browser errors none; debug.log unchanged at 1696. Order item #542 repeats missing _renewal_sync_cycle_start_date tracked in qa/issues #172. Screenshots: qa/artifacts/stage-21-task-191-checkout-summary.png, qa/artifacts/stage-21-task-191-order-received.png, qa/artifacts/stage-21-task-191-my-account-subscriptions.png, qa/artifacts/stage-21-task-191-my-account-subscription-detail.png
