---
id: 119
title: SLT-WATCH-12 D12 read-only final lifecycle tail and cross-system reconciliation
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - watch
    - stripe
    - paddle
    - day-12
due: "2026-09-04"
estimate: 2h
depends_on:
    - 117
    - 118
    - 127
    - 132
    - 133
class: standard
---

> **SLT-WATCH-12** · group `future-watch` · scheduled **D12** (2026-09-04)

## Objective
Perform the final read-only observation after the 12-day execution window. Reconcile every registered tail expectation across Stripe, Paddle, WordPress, HPOS, Action Scheduler, Mailpit, portal/admin UI and logs before teardown is permitted.

## Preconditions
- D11 restore passed and published exact cancel/keep-alive cohorts plus the D12 gate table.
- This task is strictly read-only: no status/date/meta/setting/action/webhook/remote mutation and no forced scheduler run.

## Steps
1. Verify the D12 watch report exists, is fresh, and lists each exact keep-alive subscription, expected gate, expected/forbidden state transition, order amount and email subject.
2. Capture a Mailpit baseline no earlier than each registered `gate−5m`; poll in chunks no longer than 60 seconds. Classify every message by exact task/subscription/order and recipient.
3. For each Stripe tail, query exact action status/log, subscription meta, relationship-linked order/transaction, completed payment count, next date, retry/cancel/switch/retention state and customer/admin views.
4. For each Paddle tail, compare redacted remote subscription/transaction state, webhook/audit row, local order/subscription/meta and scheduler no-op/supersession behavior.
5. Verify all expected renewals, sync-grid advances, retention cycle decrements, plan-switch prices, cancellation/expiry/reactivation results and email dedupe outcomes. Require all negative controls to remain negative.
6. Reconcile total expected vs actual new orders, transactions, actions, status changes and messages. Prove no duplicate charge/order/mail and no unrelated non-SLT2 mutation.
7. Sweep debug/Woo/PHP logs for exact SLT2 IDs plus fatal/error/lock/idempotency terms; reconcile every failed/cancelled action to an intentional test or open issue.
8. Compare the live fixture registry and future-gates table with actual tail state; sign every row PASS or link a blocking `qa/issues/` card. No row may be omitted or closed without evidence.
9. Close all browser sessions and sign `watch-reports/D12-2026-09-04.md`. Mark done only if every required row passes; otherwise leave this task blocked and forbid task 120 teardown.

## Pass criteria
- [ ] Every registered D12 Stripe/Paddle tail observed at its exact live gate
- [ ] Expected orders/charges/actions/statuses/emails match one-to-one; negatives remain absent
- [ ] Remote Paddle, Stripe logs/webhooks, local HPOS/meta/actions and UI reconcile
- [ ] Non-SLT2 state unchanged; no unexplained fatal/failed action
- [ ] Signed report exists and teardown authorization is explicit

## Isolation / handoff
- Read-only. Hand the signed registry/report and exact deletion allowlists to task 120; never infer teardown targets by prefix.
