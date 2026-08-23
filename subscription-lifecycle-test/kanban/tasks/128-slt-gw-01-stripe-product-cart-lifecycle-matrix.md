---
id: 128
title: SLT-GW-01 Stripe product, cart, checkout, SCA and cancellation matrix
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, stripe, checkout, matrix, day-05]
due: "2026-08-28"
estimate: 4h
depends_on: [5, 39, 59, 65, 71, 77, 131]
class: standard
---

> **SLT-GW-01** · group `gateway-matrix` · scheduled **D05-D07**, Stripe critical path

## Objective
Run the Stripe checkout/lifecycle matrix across every requested product/cart shape using separate users and exact relationships. Existing cards may supply a cell only when their fresh evidence exactly matches it; uncovered cells are executed here, not waived.

## Required rows
1. Simple subscription only: block and classic checkout, saved/new 4242 method.
2. Variable subscription: each tier/variation and parent-vs-variation meta.
3. Free/core Subscription Box: configuration, box-only cart, frozen contents, renewal.
4. Grouped page: one subscription child, two-subscription refusal, subscription+plain child.
5. Plain product only negative control.
6. Mixed subscription+plain cart: one order, one subscription, renewal excludes plain line.
7. Same subscription quantity merge; two distinct subscriptions rejection/allowed behavior per setting.
8. Trial/$0, signup fee, lifetime, fixed length, coupon and renewal-price variants.
9. SCA at signup, off-session SCA, genuine decline/retry and successful recovery.
10. Immediate and end-of-period cancellation, pending state, reactivation and renewal suppression.

## Per-row assertions
- Dedicated account/cart/session; offered gateways; totals/tax/discount/fee/quantity; visible loading/errors.
- Exact order→subscription and reverse links; product/variation/box contents; payment method/transaction.
- Action/reminder/invoice/renewal rows; natural next cycle; Mailpit exact set; customer/admin views.
- Cart/persistent-cart cleanup and no mutation of other rows.

## Steps
1. Publish the row table with owner task/evidence or `execute here`, dedicated aliases and expected amounts before running.
2. Execute Stripe rows in priority order: simple/mixed/SCA first, then variable/box/grouped/edge products.
3. For each positive subscription row observe at least one natural renewal or cite the exact fresh renewal-owner card; no code-only conclusion.
4. For every rejected/negative row capture visible UI plus server/network/count proof that no order/subscription/charge was created.
5. Reconcile the matrix one cell at a time and mark done only when every required row is PASS.

## Pass criteria
- [ ] All ten row groups and every per-row assertion have fresh real-browser evidence
- [ ] Stripe simple/variable/box/grouped/mixed/edge initial and renewal amounts are correct
- [ ] SCA/decline/retry/cancel/reactivation positives and negatives pass without duplicates

Any failed/missing cell creates/updates a mandatory `qa/issues/` card and blocks this task.
