---
id: 192
title: 'stage-21: 05 Stripe — Segment 1 Full & Segment 2 Prorate Checkout'
status: open
priority: high
created: 2026-07-08T02:50:18.614642+06:00
updated: 2026-07-08T02:51:11.863525+06:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 40m
depends_on:
    - 188
class: standard
---

Source: stages/21-flexible-renewal-sync/05-stripe-full-and-prorate-checkout.md

Repeat full + prorate checkouts on Stripe test mode. Required conditions: PaymentIntent amount equals the WC order total (full $30 / exact prorated value), subscription Active immediately with saved off-session payment method (_gateway_customer_id/_gateway_payment_method_id), schedule identical to manual runs, gateway switching refreshes totals. Reference qa/issues #32/#33 if Payment Element still blocked.
