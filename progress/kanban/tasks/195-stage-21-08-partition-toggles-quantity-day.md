---
id: 195
title: 'stage-21: 08 Partition Toggles, Quantity & Day-Boundary Edges'
status: closed
priority: high
created: 2026-07-08T02:50:39.674496+06:00
updated: 2026-07-08T01:16:52.552219073+02:00
started: 2026-07-08T01:03:40.315366989+02:00
completed: 2026-07-08T01:16:52.55221757+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 40m
depends_on:
    - 188
claimed_by: reef-maple
claimed_at: 2026-07-08T01:16:52.552218962+02:00
class: standard
---

Source: stages/21-flexible-renewal-sync/08-partition-toggles-quantity-and-edges.md

Checkout-level proof of the partition rule: two-segment plans resolve disabled days to the covering active segment (no anniversary fallback), single-segment plans apply to every day, boundary day D is inclusive (b1=D -> full, b1=D-1 -> prorate), weekly products sync to the store start-of-week with round(20*(7-d)/7,2) proration, and quantity 3 orders bill 3x the unit prorated amount.

[[2026-07-08]] Wed 01:16
QA result: PASS with known issue #172 repeated. Site-local date was 2026-07-08, monthly D=8. Store start_of_week=6 (Saturday), so weekly day index was 5 and expected weekly prorate was round(20 * (7 - 5) / 7, 2) = $5.71. Run A: FRS Monthly 30 with active Full + Next-cycle and prorate disabled, boundary 5 below D. Checkout resolved to next_cycle: $30.00 today, next charge 1 September 2026 UTC+6, First billing cycle note for 1 August present. Run B: same active segments, boundary 10 >= D. Checkout flipped to full: $30.00 full first charge, next charge 1 August 2026 UTC+6. Run C: only Prorate active. Checkout showed $22.26 today, next 1 August 2026 UTC+6. Run D: all segments, b1=8 showed full first charge; b1=7 showed $22.26 prorate, proving inclusive segment-end behavior.

Run E weekly: FRS Weekly 20 with boundaries 2/6 put weekly index 5 in segment 2. BACS checkout showed $5.71 today and next charge 11 July 2026 UTC+6. Order #8862 total $5.71 created subscription #8876, which became active after processing; stored next payment 2026-07-10 18:00:00 UTC, recurring amount 20, mode prorate, first_full 2026-07-10 18:00:00, cycle_start 2026-07-03 18:00:00. Run F quantity 3: FRS Monthly 30 segment 2 with quantity=3 showed $66.78 today, $90.00/month renewal, next 1 August 2026 UTC+6. Order #8885 total $66.78 created subscription #8899, active after processing, quantity 3, recurring amount 30 per unit, initial amount 66.78, next payment 2026-07-31 18:00:00 UTC, and pending renewal actions #9162/#9163 at 2026-07-31 12:00/18:00 UTC.

Debug.log line count stayed at 1702 during #195. Issue #172 repeated for order items #551 and #552 missing _renewal_sync_cycle_start_date while their subscriptions contain the cycle-start meta. Screenshots: qa/artifacts/stage-21-task-195-run-a-next-cycle.png, qa/artifacts/stage-21-task-195-run-b-full.png, qa/artifacts/stage-21-task-195-run-c-single-prorate.png, qa/artifacts/stage-21-task-195-run-d1-inclusive-full.png, qa/artifacts/stage-21-task-195-run-d2-prorate.png, qa/artifacts/stage-21-task-195-run-e-weekly-before-order.png, qa/artifacts/stage-21-task-195-run-e-weekly-order.png, qa/artifacts/stage-21-task-195-run-f-qty3-before-order.png, qa/artifacts/stage-21-task-195-run-f-qty3-order.png
