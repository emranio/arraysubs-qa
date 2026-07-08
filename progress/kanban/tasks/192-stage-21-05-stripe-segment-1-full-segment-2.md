---
id: 192
title: 'stage-21: 05 Stripe — Segment 1 Full & Segment 2 Prorate Checkout'
status: closed
priority: high
created: 2026-07-08T02:50:18.614642+06:00
updated: 2026-07-08T00:32:39.023967568+02:00
started: 2026-07-08T00:12:17.965649124+02:00
completed: 2026-07-08T00:32:39.023966626+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 40m
depends_on:
    - 188
claimed_by: reef-maple
claimed_at: 2026-07-08T00:32:39.023967448+02:00
class: standard
---

Source: stages/21-flexible-renewal-sync/05-stripe-full-and-prorate-checkout.md

Repeat full + prorate checkouts on Stripe test mode. Required conditions: PaymentIntent amount equals the WC order total (full $30 / exact prorated value), subscription Active immediately with saved off-session payment method (_gateway_customer_id/_gateway_payment_method_id), schedule identical to manual runs, gateway switching refreshes totals. Reference qa/issues #32/#33 if Payment Element still blocked.

[[2026-07-08]] Wed 00:32
QA result: PASS with issues #172 and #173. Run A Segment 1 boundaries 10/20: Stripe checkout order #8737 completed for 0, subscription #8751 active, completed payments 1, next payment 2026-07-31 18:00:00 UTC, mode full, Stripe gateway customer cus_UqOAIbfNmjl5FJ and payment method pm_1TqhNHJG5OzSNVs2QYdqYef5 stored. PaymentIntent pi_3TqhNkJG5OzSNVs208VQk5R0 succeeded for 3000 cents. Run B Segment 2 boundaries 5/20: Stripe checkout order #8762 completed for 2.26, subscription #8776 active, completed payments 1, next payment 2026-07-31 18:00:00 UTC, mode prorate, recurring amount 30, Stripe gateway customer cus_UqOG3dmB2mwvCC and payment method pm_1TqhTsJG5OzSNVs2FLcKvW3s stored. PaymentIntent pi_3TqhU1JG5OzSNVs20Ufkcxne succeeded for 2226 cents. Gateway switching on Segment 2 kept the same 2.26 total for BACS and Stripe. Browser errors none. Issue #172: order items #543/#544 lack _renewal_sync_cycle_start_date. Issue #173: debug.log grew from 1696 to 1698 with duplicate arraysubs_stripe charge.succeeded webhook-event DB errors. Screenshots: qa/artifacts/stage-21-task-192-stripe-full-checkout-summary.png, qa/artifacts/stage-21-task-192-stripe-full-submit-cleared-link.png, qa/artifacts/stage-21-task-192-stripe-prorate-checkout-summary.png, qa/artifacts/stage-21-task-192-gateway-switch-prorate.png, qa/artifacts/stage-21-task-192-stripe-prorate-order-received.png
