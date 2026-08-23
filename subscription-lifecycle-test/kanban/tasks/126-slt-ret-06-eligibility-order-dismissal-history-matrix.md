---
id: 126
title: SLT-RET-06 Retention eligibility, card order, dismissal, decline and offer-history matrix
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, retention, matrix, day-08]
due: "2026-08-31"
estimate: 2h
depends_on: [121, 122, 123, 124, 125]
class: standard
---

> **SLT-RET-06** · group `retention` · scheduled **D08**

## Objective
Exhaust the retention decision matrix across gateway, product, status, reason, customer history and UI exit path, including multiple simultaneous cards and immutable offer history.

## Matrix
- Gateways: Stripe primary, Paddle supported parity.
- Products: simple, variable variation, Subscription Box, ladder plan; regular-only control gets no cancel flow.
- Status: active, trial, paused, on-hold, pending-cancellation, cancelled, expired.
- Reasons: exact match, mismatch, empty offer reason list meaning all, disabled/custom/Other.
- History: unused, offer viewed, declined, dismissed, accepted, already used, cooldown/max reached.
- UI exits: X, Escape, Back, refresh, decline one/next card, continue cancellation, accept.

## Steps
1. Publish the full row matrix with dedicated fixture aliases and expected eligible cards/order before browser execution.
2. Enable all four offer types with overlapping eligibility and deterministic admin order. Verify customer card order matches configuration and each card appears at most once.
3. Execute every reason/status/product/history row, capturing visible cards and proving hidden cards absent from DOM/server response.
4. Verify X/Escape/Back/refresh preserve status and eligibility; declining one advances to the next; declining all reaches final cancellation; acceptance terminates the offer sequence and applies only one offer.
5. Verify offer view/decline/dismiss/accept history is append-only, actor/time/reason/offer IDs are correct, and already-used/cooldown rules survive reload/session/login.
6. Compare Stripe/Paddle presentation; unsupported Paddle mutations must not be offered while gateway-neutral support/decline behavior remains available.
7. Reconcile zero duplicate actions/orders/charges/emails and exact restoration of global settings.

## Pass criteria
- [ ] Every matrix row has screenshot/state evidence; no umbrella result substitutes for a row
- [ ] Multiple cards order/dismiss/decline/accept correctly and only one offer applies
- [ ] History/use/cooldown and gateway capability rules persist and isolate subscriptions
- [ ] All settings restored and no unintended billing/status mutation occurs

Any missing/failed row creates/updates the mandatory `qa/issues/` card and keeps this task blocked.
