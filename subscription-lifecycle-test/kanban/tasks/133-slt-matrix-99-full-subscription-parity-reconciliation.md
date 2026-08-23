---
id: 133
title: SLT-MATRIX-99 Full subscription scenario and Stripe/Paddle parity reconciliation
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, matrix, stripe, paddle, day-10]
due: "2026-09-02"
estimate: 3h
depends_on: [73, 86, 95, 96, 97, 98, 106, 122, 123, 124, 125, 126, 128, 129, 130, 132]
class: standard
---

> **SLT-MATRIX-99** · group `audit` · scheduled **D10**, before D11 restore

## Objective
Close the coverage gap explicitly. Build a cell-level matrix for every product/cart/lifecycle/retention condition and both automatic gateways. A cell is PASS only with a fresh task/evidence pointer; missing cells are executed before this card can close.

## Matrix axes
- Product: simple, variable/each variation, free Subscription Box/bundle, grouped children, lifetime, finite, trial, signup-fee, renewal-price, sync variants, regular control.
- Cart: subscription only, regular only, subscription+regular, same-sub quantity, two distinct subscriptions, multiple variations, grouped and box composition.
- Checkout/gateway: block/classic; Stripe new/saved/SCA/decline; Paddle hosted success/abandon/failure; supported/unsupported capability negatives.
- Lifecycle: initial, invoice, automatic renewal 1/2+, remote sync, early/late/skip, retry/grace/recovery, pause/on-hold, pending/immediate cancel, expiry/reactivation.
- Money: percent/fixed/recurring/one-time/N-cycle coupons, signup fee, quantity, proration, switch fee, refund, tax-zero control.
- Switch: upgrade, downgrade, crossgrade, variable switch, customer/admin, Stripe/Paddle remote price.
- Retention: reasons, discount, pause, downgrade, support, eligibility/history, analytics, accept/decline/dismiss/all conditions.
- Integrity/UI: access, emails, notes/audits, REST/capabilities, scheduler/idempotency, loading/confirm/toast, carts, teardown ownership.

## Steps
1. Generate the matrix with one row per atomic scenario and columns for expected outcome, Stripe owner/evidence, Paddle owner/evidence, negative capability reason and issue ID.
2. Cross-check all 132 preceding cards and daily reports. A high-level card title alone is not evidence; link the exact screenshot/order/subscription/action/mail/provider IDs.
3. Execute any uncovered safe row in a dedicated session/fixture or add a new granular card before proceeding. Do not mark N/A unless task 26 proved a gateway capability exclusion and the negative refusal was itself tested.
4. Reconcile counts: every required row PASS, zero todo/review omission, every open issue keeps its cells and this card blocked.
5. Publish signed matrix and hand it to D11 restore/D12 watch/D13 teardown.

## Pass criteria
- [ ] Every requested product/cart/lifecycle/money/switch/retention/UI cell has fresh evidence
- [ ] Stripe critical path is complete; Paddle has full supported parity plus tested capability negatives
- [ ] No PayPal/Mollie execution or prerequisite exists
- [ ] No failed/missing cell is hidden by an umbrella PASS

This terminal audit remains blocked while any mandatory cell or linked issue is open.
