---
id: 42
title: Paddle sandbox subscription renews unattended with remote/local reconciliation
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - paddle
    - day-02
due: "2026-08-25"
estimate: 1h30m
depends_on:
    - 26
    - 29
class: standard
---

> **SLT-REN-04** · group `renewals` · starts **D02** (2026-08-25), completes after the first remote renewal gate

## Objective
Prove the canonical `SUB_PAD` renews unattended through Paddle's sandbox subscription event, while ArraySubs reconciles the event exactly once and its local renewal legs never create a second charge, retry ladder or duplicate order.

## Scope
- Gateway: Paddle sandbox only
- Checkout: none; consumes task 29
- Account: `slt2-paddle`
- Plugins: core-owned ArraySubs Paddle renewal/webhook path

## Preconditions
- Task 29 passed and published numeric `SUB_PAD`, its parent order, remote subscription ID, exact local next-payment date and redacted remote `next_billed_at`.
- If task 29 is blocked, this card is blocked too and cannot be closed as done. Do not create a replacement, backdate state, force actions or hand-edit remote/local records.

## Steps
1. Revalidate exact owner/product/order/subscription relationships, Paddle IDs, active status, completed payments, local next date and remote `next_billed_at`.
2. Query scheduler rows by exact args `[SUB_PAD]`; assign fresh aliases `PAD_INVOICE_ACTION` and `PAD_RENEW_ACTION` from the live rows. Record IDs, statuses, logs, groups and GMT timestamps. No action ID or gate is hard-coded.
3. Publish the remote billing gate, local invoice/renewal gates and each `gate−5m` baseline deadline to the registry, `future-gates.tsv` and the daily watch handoff.
4. Inside the five-minute pre-gate window set `PAD_REN_PRE=$(mailpit-agent latest-id)`, snapshot the exact subscription/order/action/remote state, and close browser sessions while waiting for natural processing.
5. After the remote gate, inspect Paddle's redacted transaction/subscription state, webhook/audit log, exact action statuses/logs, subscription notes/meta and HPOS rows. Poll the sandbox for up to 24 hours without mutating it.
6. Resolve exactly one new paid renewal order by `_subscription_id=SUB_PAD`, scheduled cycle and reverse relationship. Require one new Paddle transaction, one completed-payment increment, advanced next-payment date, and continuous cycle number.
7. Prove `PAD_RENEW_ACTION` either completed as a bookkeeping/no-op or was superseded/cancelled by the earlier reconciled webhook. In either case, require zero local second charge, zero retry attempt and zero duplicate renewal order.
8. Reconcile every Mailpit message after `PAD_REN_PRE`. Require one payment-received customer email and the expected Woo order mail set; reject duplicate payment, invoice, failure or activation mail.
9. Compare My Account, admin detail, related order, local meta/action dates and remote Paddle dates. Capture the exact reconciliation and append all IDs/timestamps to the registry.
10. Close sessions and move through review to done only after the remote renewal and all reconciliation assertions pass. If the sandbox does not bill in 24 hours or any assertion fails, create/update the mandatory `qa/issues/` kanban card and leave this task blocked for a real rerun.

## Expected results
1. Paddle is the payment driver; ArraySubs receives and reconciles one matching remote event.
2. Exactly one paid renewal order/transaction is linked to `SUB_PAD`, completed payments advance once, and local/remote next dates agree with the current contract.
3. The local renewal leg does not charge or enqueue an ArraySubs retry ladder.
4. One payment-received message and the expected Woo order mail arrive; no duplicates or failure mail.
5. Admin, customer, HPOS, meta, scheduler, webhook and remote views all identify the same cycle.

## Evidence / issue contract
- Save redacted remote responses, webhook/log rows, scheduler rows, order/subscription meta, screenshots and complete Mailpit delta under `/home/server-manager/slt-evidence/`.
- Every issue card includes task/stage/plan path, gateway event/order/subscription/action IDs, user/login/email/role, exact route/session/gates, reproduction, expected/actual and all concrete proof.

## Isolation / teardown
- Observation only. Keep `SUB_PAD` alive for payment-method, replay, switch, email and parity tasks; D11 restore/D13 teardown own cancellation.

### Fresh-cycle validation contract

- Re-derive all IDs and timestamps; no previous Paddle outcome is evidence.
- Automatic gateways are Stripe and Paddle only, with Stripe primary and Paddle parity.
- Use `agent-browser`, `mailpit-agent`, and WP-CLI with `--allow-root`; update both QA boards.
