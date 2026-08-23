---
id: 94
title: Update Paddle payment method and prove the next remote renewal uses it
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - portal
    - paddle
    - day-06
due: "2026-08-29"
estimate: 1h30m
depends_on:
    - 29
    - 42
class: standard
---

> **SLT-MYA-03** · group `customer-portal` · starts **D06** (2026-08-29), completes after the next Paddle renewal

## Objective
Prove a Paddle-billed customer can initiate the supported hosted payment-method update from My Account, Paddle reports the new method, the matching webhook/local audit state is reconciled, and the next remote renewal succeeds once using that method.

## Scope
- Gateway: Paddle sandbox only
- Checkout: Paddle hosted management flow
- Account: `slt2-paddle`
- Plugins: core-owned ArraySubs portal/delegation path

## Preconditions
- Canonical numeric `SUB_PAD` exists and task 42's first renewal passed. A missing source blocks this task; do not create a substitute.
- Resolve the current remote subscription and management URL without printing or storing API secrets or an unredacted management URL.

## Steps
1. Snapshot exact local/remote subscription, current safe payment-method display, next bill date, completed payments, action rows, notes and last transaction.
2. In `customer-SLT-MYA-03`, open the subscription detail and require a visible Paddle payment-method update action or supported redirect. Capture the unpopulated state.
3. Activate the action, re-snapshot after navigation/overlay load, enter a different Paddle sandbox method only in hosted fields, submit, and capture a safe success/return state.
4. Poll the exact redacted Paddle subscription/transaction until the update is reflected. Reconcile the matching webhook/audit/log/note and customer UI state; require no new order, charge or status change from the update alone.
5. Publish the next remote/local renewal gate and set a fresh Mailpit baseline inside its five-minute pre-gate window. Do not force a webhook or action.
6. After natural renewal, resolve exactly one new paid renewal order and one remote transaction for the same cycle. Require completed payments advance once, next date advances, local scheduler reconciles, and no retry/duplicate order occurs.
7. Reconcile the complete Mailpit delta and require one payment-received message plus the expected Woo mail set, no payment-failure or duplicate message.
8. Compare My Account, admin detail, local meta/actions and redacted Paddle data. Append all IDs/gates to the registry, close sessions, review, and move done only after the hosted update and subsequent renewal both pass.

## Expected results
1. The customer can reach and complete the supported Paddle-hosted update path from the portal.
2. Paddle and local audit/UI state reflect the new safe method summary without a charge or order at update time.
3. The next Paddle-driven renewal succeeds exactly once and all local/remote/order/mail state reconciles.

## Evidence / issue contract
- Never capture populated payment fields, API keys, client tokens, webhook secrets or raw management URLs.
- Any missing control, unreachable update path, absent webhook, renewal failure or mismatch creates/updates the mandatory `qa/issues/` kanban card with task/stage/plan, exact IDs/context/gates, reproduction, expected/actual and redacted proof; keep the task blocked until a real rerun passes.

## Isolation / teardown
- Do not detach or delete payment methods. Keep `SUB_PAD` alive through all Paddle parity tasks; teardown owns cancellation.

### Fresh-cycle validation contract

- No prior Paddle limitation is assumed; this build must be tested as fixed.
- Stripe and Paddle only; use `agent-browser`, Mailpit and WP-CLI `--allow-root`; update both QA boards.
