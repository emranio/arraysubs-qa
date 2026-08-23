---
id: 98
title: Pending cancellation with required reason, declined retention offers, natural cancel, reactivation
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - cancellation
    - retention
    - day-06
due: "2026-08-29"
estimate: 1h45m
depends_on:
    - 60
    - 72
    - 121
class: standard
---

> **SLT-SW-10** · group `plan-switching` · starts **D06** (2026-08-29), completes at the registered cancellation/reactivation gates

## Objective
Prove the complete cancel-at-period-end lifecycle when a reason is required and all eligible retention offers are declined: validation, reason storage, pending-cancellation state, scheduler replacement, renewal suppression, natural cancellation, email sets and customer reactivation with future renewal legs rearmed.

## Scope
- Gateway: Stripe primary; repeat state parity on Paddle where the capability matrix supports reactivation
- Checkout: none
- Account: dedicated registered cancellation fixture
- Plugins: both

## Preconditions
- Cancellation-reason setup task 121 passed and the source subscription is active with exact next date/actions recorded.
- Use only current-cycle retention configuration and IDs. The source must not have consumed an offer previously.

## Steps
1. Snapshot active subscription status, `_cancelled_date`, next date, exact invoice/renewal actions, customer actions, notes and Mailpit baseline.
2. Start cancellation from My Account. Require the shared modal, visible reason list and disabled/blocked continuation when reason is required but unset.
3. Select a configured reason. Traverse every retention card eligible for this source and choose decline/continue-cancelling each time; record presentation order and ensure dismissal by X/Escape does not cancel or consume an offer.
4. Confirm end-of-period cancellation once. Require a visible loading state and success toast, then refresh after the documented delay.
5. Require status `arraysubs-pending-cancellation`; store selected reason and optional Other text exactly once. `_cancelled_date` must remain unset until actual cancellation. Require one exact cancel action at the current end date and no duplicate.
6. Require renewal invoice/charge legs for the cancelled period to be removed/suppressed, with no new renewal order or charge while pending cancellation. Verify customer/admin portal dates and status.
7. Reconcile the pending-cancellation email contract and require no offer-acceptance email or discount/pause/downgrade mutation because every offer was declined.
8. At the natural cancellation gate, require the exact action to complete once, status cancelled, cancellation date set to the real event time, waiting/scheduled/next-payment state cleared and no renewal order/charge.
9. Reconcile exactly one customer and one admin cancellation email. Reject duplicate status changes, actions, notes or messages.
10. From the customer portal, use the visible Reactivate action. Require confirmation/loading/toast, active status, one reactivation email, future invoice/renewal legs rearmed from the documented anchor, no immediate double charge and no stale cancel action.
11. Recheck all relationships, carts and session storage; append IDs/actions/gates/messages to the registry, close sessions, review, and mark done only when every leg passes.

## Expected results
1. Required-reason validation is visible and the selected reason is persisted accurately.
2. Declining all offers applies no offer and consumes none prematurely.
3. Pending cancellation has one cancel action, no early `_cancelled_date`, and no renewal charge/order.
4. Natural cancellation occurs once with correct cleanup and email pair.
5. Reactivation is visible, succeeds once, emits one mail and restores future renewal legs without stale cancellation state.

## Evidence / issue contract
- Capture before/after modal, portal, admin and queue screenshots; exact meta/action/order/note rows and full Mailpit deltas.
- Any failed assertion creates/updates a mandatory `qa/issues/` kanban card with lifecycle task/stage/plan path, all IDs and user context, exact routes/sessions/gates, reproduction, expected/actual, proof and a passing gateway/status counterexample when available. The task remains blocked until fixed and rerun.

## Isolation / teardown
- Leave the reactivated fixture registered for the next natural watch if required; D11/D13 own final cancellation/deletion. Restore any task-owned retention setting in-task.

### Fresh-cycle validation contract

- No historical cancellation ID or result is reused.
- Stripe and Paddle only; Stripe is primary. Browser/WP/Mailpit and both QA-board rules are mandatory.
