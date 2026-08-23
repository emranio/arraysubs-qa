---
id: 129
title: SLT-GW-02 Paddle product, cart, hosted checkout and lifecycle parity matrix
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, paddle, checkout, matrix, day-06]
due: "2026-08-29"
estimate: 4h
depends_on: [26, 29, 42, 59, 65, 71, 128, 131]
class: standard
---

> **SLT-GW-02** · group `gateway-matrix` · scheduled **D06-D08**, Paddle parity

## Objective
Run Paddle parity across simple, variable, Subscription Box, grouped and mixed carts, including hosted checkout/webhook settlement, renewals, coupons, trials, switching, cancellation/refund and explicit capability negatives. Unsupported cases must be visibly hidden/refused with no mutation; they are tested, not skipped.

## Required rows
- Simple subscription only; regular-only control; subscription+regular mixed cart.
- Variable tier, Subscription Box and grouped single-child/mixed-child flows.
- Trial/$0, signup fee, finite/lifetime and recurring coupon where Paddle supports them.
- Hosted pending→paid checkout, abandoned/failed overlay cleanup and idempotent webhook replay.
- Remote renewal, local no-double-charge, payment-method update, upgrade/downgrade/crossgrade, cancellation and refund.
- Capability negatives: flexible renewal sync, early renew, SCA and differing billing-cycle combinations when declared unsupported.

## Steps
1. Publish a Paddle row table with current capability result, product catalog IDs and expected supported/negative outcome. Derive it from task 26; no stale matrix.
2. Execute every supported row with dedicated users/sessions. Require safe hosted-field handling, pending order before webhook, one active subscription, redacted remote IDs/date, actions and exact mail.
3. Observe at least one natural remote renewal for each distinct product implementation or cite the exact fresh owner; reconcile remote transaction, webhook, one local order and new next date.
4. Execute every unsupported row to the safe refusal boundary: gateway absent or server rejects before order/transaction; capture DOM/network/count/remote proof.
5. Verify abandoned overlay/cart retry creates no phantom subscription/order and a later clean attempt succeeds once.
6. Reconcile all rows and mark done only when supported positives and unsupported negatives pass.

## Pass criteria
- [ ] Paddle simple/variable/box/grouped/mixed supported rows settle and renew exactly once
- [ ] Catalog/remote/local/order/action/mail data reconcile for every positive row
- [ ] All capability negatives are hidden/refused without side effects
- [ ] Abandon/retry/replay/cancel/refund/switch rows have fresh proof

Any failed/missing row creates/updates a mandatory `qa/issues/` card and blocks this task.
