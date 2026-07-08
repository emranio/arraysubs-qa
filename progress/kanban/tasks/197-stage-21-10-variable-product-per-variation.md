---
id: 197
title: 'stage-21: 10 Variable Product — Per-Variation Flexible Sync Checkout'
status: closed
priority: high
created: 2026-07-08T02:50:39.732477+06:00
updated: 2026-07-08T01:57:21.413252955+02:00
started: 2026-07-08T01:34:54.556210723+02:00
completed: 2026-07-08T01:57:21.413251963+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 35m
depends_on:
    - 188
claimed_by: reef-maple
claimed_at: 2026-07-08T01:57:21.413252835+02:00
class: standard
---

Source: stages/21-flexible-renewal-sync/10-variation-checkout.md

Per-variation independence through complete checkouts: flex-enabled Silver variation prorates and syncs to the boundary (manual + one Stripe spot-check) while sibling Gold without flex bills full $50 on its anniversary with no _renewal_sync_* meta. Variation-level trial hides/disables only that variation's flex config.

[[2026-07-08]] Wed 01:57
Stage 21 task #197 completed on 2026-07-08 by reef-maple.

Site context: site time 2026-07-08 UTC+6, monthly D=8, July L=31. Silver #8654 baseline restored to $30/month, _trial_length=0, flex enabled yes, boundaries 5/20. Gold #8656 baseline restored to $50/month, _trial_length=0, flex disabled.

Subtask 10.1 product variation switching: PASS. Product page switched Silver and Gold without browser errors. Silver showed $30 Every month, Gold showed $50 Every month. Artifacts: qa/artifacts/stage-21-task-197-product-silver.png, qa/artifacts/stage-21-task-197-product-gold.png.

Additional issue found before using the intended product form: direct URL https://mirror-help.arrayhash.com/checkout/?add-to-cart=8654 rendered a WordPress critical error. Filed issue #179 with screenshot qa/artifacts/stage-21-task-197-direct-variation-fatal.png and debug.log lines 1703-1718.

Subtask 10.2 Silver manual BACS checkout: PASS with recurring issue #172. Product form checkout order #8913, order item #554, subscription #8927, customer #339 sync.var.silver.manual.197@example.test. Checkout/order total $22.26, BACS, subscription active after processing, next payment 2026-07-31 18:00:00 UTC, recurring amount 30, variation_id 8654, _renewal_sync_first_charge_mode=prorate, _renewal_sync_initial_recurring_amount=22.26. Order item #554 still lacks _renewal_sync_cycle_start_date; appended to issue #172. Artifacts: qa/artifacts/stage-21-task-197-silver-manual-checkout.png, qa/artifacts/stage-21-task-197-silver-manual-bacs-selected.png.

Subtask 10.3 Gold BACS checkout: PASS. Exact BACS run order #8955, order item #556, subscription #8969, customer #341 sync.var.gold.bacs.197@example.test. Checkout/order total $50.00, BACS, subscription active after processing, next payment 2026-08-07 23:46:56 UTC (8 August, 2026 UTC+6), recurring amount 50, variation_id 8656, no _renewal_sync_* order item or subscription meta. A supplemental manual run order #8936/sub #8950 used cheque due a shifted ref but also showed the same non-flex behavior. Artifact: qa/artifacts/stage-21-task-197-gold-bacs-checkout.png.

Subtask 10.4 Silver Stripe spot-check: PASS with recurring issues #172 and #173. Order #8978, order item #557, subscription #8992, customer #342 sync.var.silver.stripe.197@example.test. Browser completed checkout with Stripe, order completed, subscription active immediately, next payment 2026-07-31 18:00:00 UTC, recurring amount 30, variation_id 8654, sync mode prorate. Stripe PaymentIntent pi_3TqillJG5OzSNVs20qMPf88L succeeded for amount=2226 usd, latest charge ch_3TqillJG5OzSNVs20D4h4DUX, payment method pm_1TqilbJG5OzSNVs24tqCLzRa. Order item #557 lacks _renewal_sync_cycle_start_date; appended to issue #172. debug.log line 1719 added duplicate webhook event DB error for charge.succeeded evt_3TqillJG5OzSNVs20j7XRJmU; appended to issue #173. Artifacts: qa/artifacts/stage-21-task-197-silver-stripe-checkout-before-card.png, qa/artifacts/stage-21-task-197-silver-stripe-card-filled.png.

Subtask 10.5 variation exclusivity: PASS. Set Silver #8654 _trial_length=3 with _trial_period=day. Silver checkout became trial-only: $0.00 today, Trial: 3 days free trial, next charge 11 July, 2026 UTC+6, no synced-prorate wording. Gold checkout while Silver trial was active remained $50.00 today, next charge 8 August, 2026 UTC+6, no sync/prorate behavior. Admin product editor Silver variation showed Trial Settings and did not show Flexible Renewal Sync controls while trial was active. Reset Silver #8654 _trial_length back to 0 and verified flex meta still yes with boundaries 5/20. Artifacts: qa/artifacts/stage-21-task-197-silver-trial-checkout.png, qa/artifacts/stage-21-task-197-gold-unaffected-by-silver-trial.png, qa/artifacts/stage-21-task-197-admin-silver-trial-variation.png.

Final debug.log baseline for this task: started at 1718 after the direct variation fatal was captured; ended at 1719 after the Stripe duplicate webhook issue. Open issues touched/created: #172, #173, #179.
