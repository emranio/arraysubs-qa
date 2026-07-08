---
id: 190
title: 'stage-21: 03 Manual Gateway — Segment 2 Prorate Checkout'
status: closed
priority: high
created: 2026-07-08T02:50:18.560759+06:00
updated: 2026-07-08T00:05:23.444505396+02:00
started: 2026-07-07T23:50:56.019731171+02:00
completed: 2026-07-08T00:05:23.444504495+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 30m
depends_on:
    - 188
claimed_by: reef-maple
claimed_at: 2026-07-08T00:05:23.444505286+02:00
class: standard
---

Source: stages/21-flexible-renewal-sync/03-manual-prorate-segment-checkout.md

Complete Direct-bank-transfer checkout with purchase day in segment 2. Required conditions: Today's charge = round(price*(L-D)/L, 2) to the cent (L = actual month length, D = day of month, site tz), order/line totals match, subscription Recurring Amount stays full $30, mode=prorate, next payment = boundary. Includes quantity x3 scaling spot-check.

[[2026-07-08]] Wed 00:05
QA result: EXECUTED with known issue #172. D=8, L=31, boundaries 5/20, computed unit prorate 2.26. Checkout summary showed 2.26 today, 0.00 Every month, next charge 1 August 2026 UTC+6 (0), total 2.26. BACS order #8691 created subscription #8705 pending, line total 2.26. Subscription meta passed: mode prorate, initial amount 22.26, recurring amount 30, cycle start 2026-06-30 18:00:00 UTC, next/first-full renewal 2026-07-31 18:00:00 UTC. After order processing, #8705 active, completed payments 1, next payment unchanged, scheduler rows pending at 2026-07-31 12:00:00 and 18:00:00 UTC. Quantity-3 checkout spot-check showed today 6.78 and next charge 0.00. My Account for customer #332 showed Active #8705, next payment 1 August 2026 12:00 AM UTC+6, 0 Every month. Browser errors none; debug.log unchanged at 1696. Cart-page quantity edit could not be exercised because add-to-cart redirected subscription product to checkout. Screenshots: qa/artifacts/stage-21-task-190-checkout-summary.png, qa/artifacts/stage-21-task-190-order-received.png, qa/artifacts/stage-21-task-190-quantity-3-summary.png, qa/artifacts/stage-21-task-190-my-account-subscriptions-logged-in.png
