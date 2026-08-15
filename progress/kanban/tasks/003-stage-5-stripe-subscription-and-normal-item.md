---
id: 3
title: 'stage-5: Stripe subscription and normal-item checkout'
status: closed
priority: critical
created: 2026-08-15T09:45:27.056450185+02:00
updated: 2026-08-15T10:00:04.679187215+02:00
tags:
    - stage-05
    - stripe
    - checkout
    - qa
class: standard
---

References: ../stages/05-checkout/01-classic-checkout-basic-subscription.md, ../stages/05-checkout/02-block-checkout-parity.md, ../stages/05-checkout/11-stripe-test-card-flow.md, ../stages/05-checkout/14-mixed-cart-and-block-checkout.md

Run real Stripe test-mode browser purchases for subscription-only, normal-only, mixed cart, SCA, and cancelled checkout. Verify network, console, webhooks, Mailpit, orders, subscriptions, payment bindings, and schedules.

[[2026-08-15]] Sat 10:00
PASS. Stripe browser matrix completed: subscription-only order #27280 -> active #27296; normal-only #27315 -> zero subscriptions; mixed #27346 -> exactly one active #27362; SCA #27387 -> active #27403 after one challenge; cancel/return customer #467 -> zero records and cart retained. Webhooks, Mailpit, payment bindings, actions, browser/network, and debug log checked. Longstanding duplicate Stripe confirmation notes are tracked in qa/issues task #1 and predate this migration. Evidence: ../artifacts/payment-migration-regression-20260815/stripe/report.md.
