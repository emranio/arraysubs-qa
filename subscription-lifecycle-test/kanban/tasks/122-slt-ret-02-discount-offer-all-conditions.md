---
id: 122
title: SLT-RET-02 Retention discount offer eligibility, acceptance, cycle accounting and limits
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, retention, discount, stripe, day-04]
due: "2026-08-27"
estimate: 2h30m
depends_on: [60, 72, 121]
class: standard
---

> **SLT-RET-02** · group `retention` · starts **D04**, completes after four natural renewals

## Objective
Exercise every discount-offer condition and prove a 20%-for-3-cycles acceptance changes exactly three paid renewals, restores full price on the fourth, cannot be consumed twice, and does not leak into taxes, signup fees, other subscriptions or declined flows.

## Steps
1. Snapshot retention settings; configure discount offer 20%, three cycles, Too expensive reason, eligible active Stripe plans, clear copy/CTA and one-use limit. Save/reload and verify list card/editor values.
2. Verify negative inputs/bounds: blank/zero/negative/over-100 percent, zero cycles, nonnumeric values and missing eligible products are rejected visibly without partial save.
3. With reason mismatch require no discount card; with matching reason require one card with exact amount/cycle copy. X/Escape/decline must not apply/consume it.
4. Accept once with loading/toast. Require active status, cancellation cleared, effective recurring amount, base/amount/remaining/history/note/audit fields and one acceptance email.
5. Observe three natural Stripe renewals. Each must charge $4.00 from $5.00 with one −$1 retention line; decrement remaining only after paid settlement, not invoice creation/failure/retry.
6. Fourth renewal must charge full $5.00, contain no retention line and clear active discount metas while preserving immutable history.
7. Re-enter cancel flow: the used discount must not be offered again. A second subscription remains independently eligible; another subscription/customer is unchanged.
8. Repeat supported presentation/state parity on Paddle. If the capability matrix declares remote amount update unsupported, require the offer hidden/rejected with clear reason and no remote/local mutation.
9. Test fixed-amount variant on a disposable fixture, amount greater than recurring total, quantity >1, coupon coexistence and proration interaction; require bounded nonnegative totals and deterministic line ordering.
10. Restore settings exactly, reconcile all actions/orders/mail and mark done only after every positive/negative/cycle cell passes.

## Pass criteria
- [ ] Eligibility and all invalid-boundary cases behave correctly
- [ ] Exactly three paid renewals discounted; fourth full; failed/invoice attempts do not decrement
- [ ] One-use limit, history, notes/audit/email and isolation pass
- [ ] Coupon/quantity/proration/fixed-amount and Paddle capability cells pass

Any failure creates/updates a mandatory `qa/issues/` card and leaves this task blocked; include all settings, product/subscription/order/action/user/mail IDs and counterexamples.
