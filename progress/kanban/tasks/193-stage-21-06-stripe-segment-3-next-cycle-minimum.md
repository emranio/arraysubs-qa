---
id: 193
title: 'stage-21: 06 Stripe — Segment 3 Next-Cycle & Minimum-Charge Edge'
status: closed
priority: high
created: 2026-07-08T02:50:39.596203+06:00
updated: 2026-07-08T00:50:13.476449787+02:00
started: 2026-07-08T00:32:59.570619494+02:00
completed: 2026-07-08T00:49:42.135706188+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 35m
depends_on:
    - 188
    - 192
claimed_by: reef-maple
claimed_at: 2026-07-08T00:50:13.475695634+02:00
class: standard
---

Source: stages/21-flexible-renewal-sync/06-stripe-next-cycle-and-minimum-charge.md

Segment 3 on Stripe (full $30 today, next payment = boundary+1 cycle, nothing scheduled at the skipped boundary) plus the gateway minimum safeguard: prorated charge below ~$0.50 bumps to the minimum when full price >= minimum (verify PaymentIntent accepts), manual gateway shows the raw un-bumped value, and no bump when even the full price is below the minimum.

[[2026-07-08]] Wed 00:50
QA result: EXECUTED with failures #174/#175 and known issues #172/#173. Run A Stripe next_cycle passed: boundaries 3/6 put D=8 in Segment 3; checkout showed $30.00 today, next charge 1 September 2026 UTC+6, and first-billing-cycle note for 1 August 2026 UTC+6. Order #8791 completed through Stripe, subscription #8805 active, completed payments 1, next payment 2026-08-31 18:00:00 UTC, mode next_cycle, cycle start 2026-07-31 18:00:00 UTC, first full renewal 2026-08-31 18:00:00 UTC, recurring amount 30, saved gateway customer cus_UqOPM0b5g9LW7Q and payment method pm_1Tqhc3JG5OzSNVs23Pmrt4WA. PaymentIntent pi_3TqhcDJG5OzSNVs20HZ1t83R succeeded for 3000 cents; scheduler rows exist only for B2 at 2026-08-31 12:00:00 and 18:00:00 UTC. Issue #172 repeated for order item #545 missing _renewal_sync_cycle_start_date; issue #173 repeated with duplicate Stripe charge.succeeded webhook-event DB error, debug.log now 1699 lines.

Run B tiny product #8816: D=8, L=31, raw prorate for $0.60 is $0.45. Stripe checkout displayed the expected $0.50 bump, but fresh checkout showed "Amount must be at least $0.50 USD" and no Stripe Payment Element iframe, blocking the required 50-cent PaymentIntent proof; filed issue #175. Manual gateway control failed: switching to Direct bank transfer still showed the bumped $0.50 instead of raw $0.45; filed issue #174. Below-minimum full-price display control passed: temporarily setting #8816 to $0.20 showed raw $0.15 with no bump; price restored to $0.60 after the check. Screenshots: qa/artifacts/stage-21-task-193-stripe-nextcycle-summary.png, qa/artifacts/stage-21-task-193-stripe-nextcycle-order-received-final.png, qa/artifacts/stage-21-task-193-tiny-stripe-summary.png, qa/artifacts/stage-21-task-193-tiny-gateway-switch.png, qa/artifacts/stage-21-task-193-tiny-stripe-fresh-error.png, qa/artifacts/stage-21-task-193-tiny-below-min-summary.png
