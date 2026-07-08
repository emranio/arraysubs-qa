---
id: 189
title: 'stage-21: 02 Manual Gateway — Segment 1 Full-Amount Checkout'
status: closed
priority: high
created: 2026-07-08T02:50:18.534475+06:00
updated: 2026-07-07T23:50:44.713043503+02:00
started: 2026-07-07T23:41:44.844570034+02:00
completed: 2026-07-07T23:50:44.713042601+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 25m
depends_on:
    - 188
claimed_by: reef-maple
claimed_at: 2026-07-07T23:50:44.713043402+02:00
class: standard
---

Source: stages/21-flexible-renewal-sync/02-manual-full-segment-checkout.md

Complete Direct-bank-transfer checkout with purchase day in segment 1. Required conditions: Today's charge = full $30 ('full first charge' row), next charge = next cycle boundary, subscription meta _renewal_sync_enabled=yes / mode=full / first_full_renewal=boundary, activation after payment keeps the synced date, renewal jobs scheduled at boundary (invoice ~6h before).

[[2026-07-07]] Tue 23:50
QA result: EXECUTED with issue. Segment 1 BACS checkout passed browser summary/order/activation/scheduler checks: order #8668 total 0, subscription #8682 created pending then active after order moved to processing, completed payments 1, next payment 2026-07-31 18:00:00 UTC / 1 August 2026 00:00 UTC+6, recurring amount 0, renewal invoice action at 2026-07-31 12:00:00 UTC and process renewal at 2026-07-31 18:00:00 UTC. Browser errors none; debug.log unchanged at 1696. Customer My Account shows Active #8682, next payment 1 August 2026 12:00 AM UTC+6, 0 Every month. Logged qa/issues #172 because order item #540 is missing _renewal_sync_cycle_start_date while subscription meta has it. Screenshots: qa/artifacts/stage-21-task-189-checkout-summary.png, qa/artifacts/stage-21-task-189-order-received-8668.png, qa/artifacts/stage-21-task-189-my-account-subscriptions.png
