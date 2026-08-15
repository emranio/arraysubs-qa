---
id: 4
title: 'stage-5: Paddle subscription and normal-item checkout'
status: closed
priority: critical
created: 2026-08-15T09:45:27.290091861+02:00
updated: 2026-08-15T10:00:05.449928667+02:00
tags:
    - stage-05
    - paddle
    - checkout
    - qa
class: standard
---

References: ../stages/05-checkout/02-block-checkout-parity.md, ../stages/05-checkout/12-paypal-and-paddle-flows.md, ../stages/05-checkout/14-mixed-cart-and-block-checkout.md

User explicitly overrides the current plan's Paddle exclusion. Run real Paddle sandbox browser purchases for subscription-only, normal-only, mixed cart, and cancelled overlay. Verify async webhook settlement, remote bindings, network, console, Mailpit, records, and schedules.

[[2026-08-15]] Sat 10:00
PASS. Paddle sandbox browser matrix completed: normal-only order #27275 -> zero subscriptions; subscription-only #27307 -> active #27309; mixed #27339 -> exactly one active #27341; overlay return #27381/#27383 stayed pending/unpaid with no remote subscription, renewal action, or mail. Exact +36 webhook delta, Mailpit, bindings, portal, network, and debug log checked. Browser ViewTransition AbortError classified gateway-agnostic/non-product. Evidence: ../artifacts/payment-migration-regression-20260815/paddle/report.md.
