---
id: 29
title: 'Paddle sandbox purchase: SLT2 Paddle Daily overlay, webhook-paid order, remote schedule sync'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - paddle
    - day-02
due: "2026-08-25"
estimate: 1h30m
depends_on:
    - 23
    - 26
class: standard
---

> **SLT-CHK-04** · group `checkout` · scheduled **D02** (2026-08-25)

> Read `README.md`, `calendar.md`, `plan-audit.md`, and the fresh D0/D1 registry entries before starting.

## Objective
Complete a fresh Paddle sandbox purchase for `SLT2 Paddle Daily` through the real block checkout and hosted overlay. Prove the pending-to-paid webhook transition, exact order/subscription linkage, Paddle identifiers, local/remote next-billing synchronization, scheduler behavior, emails, and cart cleanup without carrying forward any previous Paddle finding.

## Scope
- Gateway: Paddle sandbox only
- Checkout: block
- Account: `slt2-paddle`
- Plugins: core-owned ArraySubs Paddle path; vendor-hosted Paddle UI is expected

## Preconditions
- Tasks 23 and 26 passed in this cycle. Resolve the current product ID, price/product sync IDs, gateway capability flags and account ID from the SLT2 registry; no hard-coded IDs are allowed.
- `renewals.sync_to_billing_cycle` is OFF and the product has no flexible-sync override, so Paddle should be eligible.
- Both the browser cart and persistent cart are empty. Record fresh order and subscription counts.
- If readiness, catalog sync or hosted checkout cannot pass, create a mandatory `qa/issues/` kanban card with all required IDs/contexts/proof and move this task to `blocked`. Do not close it as done, fabricate IDs, edit remote records, or substitute another subscription.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Paddle Daily, day/1, $11.00 |
| Account | slt2-paddle / `slt2-paddle@example.test` |
| Payment | Paddle documented sandbox success card, entered only inside hosted fields |
| Sessions | `customer-SLT-CHK-04`, `admin-SLT-CHK-04` |

## Steps
1. Resolve the current product/user IDs from the registry. Re-read the three Paddle catalog metas and a redacted gateway readiness summary; secrets must never be printed or saved.
2. Record `PRE=$(mailpit-agent latest-id)`, exact order/subscription counts, and an empty browser/persistent-cart state.
3. In `customer-SLT-CHK-04`, add only `SLT2 Paddle Daily`, open block checkout, re-snapshot, and prove Paddle is offered, the total is $11.00 USD, and there is no tax or unrelated line. Capture `SLT-CHK-04-01-gateways.png`.
4. Select Paddle and place the order. Re-snapshot after the hosted overlay loads, enter sandbox details without screenshots containing populated payment data, submit, and capture only a safe overlay/return state.
5. Record the order ID and its status immediately. Require the order to be pending before webhook settlement, then poll up to five minutes for the exact order to become paid. Capture the received page and the before/after order rows.
6. Resolve exactly one subscription through the order `_subscription_ids` JSON and reverse `_parent_order_id`; cross-check owner, product and count delta. Publish numeric alias `SUB_PAD` and reject recency-based selection.
7. Require active status, `_payment_method=arraysubs_paddle`, recurring amount/currency, completed-payment count, Paddle transaction/customer/subscription identifiers, and no Stripe-shaped identifier in a Paddle field.
8. Fetch only the matching Paddle sandbox subscription's redacted `id`, `status` and `next_billed_at`. Compare `next_billed_at` to local `_next_payment_date`; record the exact delta and require the integration's advertised sync contract.
9. Compute the per-subscription scheduler offset. Query exact invoice and process-renewal actions by args `[SUB_PAD]`, record their current IDs/timestamps/statuses, and verify the local charge leg cannot independently double-charge a Paddle-owned renewal.
10. Reconcile the complete Mailpit delta by exact order/subscription and recipient. Require the appropriate Woo paid-order mail plus one customer and one admin ArraySubs activation mail; require no renewal, retry, failure or SCA mail at signup.
11. In My Account, verify the subscription detail, gateway label, totals, next date, related order, and action visibility against the capability matrix. Record whether Renew Early is correctly hidden when Paddle reports it unsupported.
12. Prove browser and persistent carts empty, append all fresh IDs/timestamps/mail IDs to the registry and future-gates file, close exact sessions, review evidence, and move through review to done only when every required assertion passes.

## Expected results
1. Paddle is offered and the $11.00 sandbox checkout completes through the hosted flow.
2. The exact order is observed pending first and paid only after the matching webhook; one active subscription is linked bidirectionally.
3. Paddle identifiers and transaction metadata are present, correctly shaped and isolated from Stripe metadata.
4. Local next-payment state matches the fresh remote schedule according to the current gateway contract; exact scheduler rows are recorded without a local duplicate charge path.
5. Exactly the expected initial-order and activation email set is present; no renewal/failure/SCA mail is emitted.
6. My Account data matches order, subscription and remote state; carts are empty afterward.

## Evidence / issue contract
- Capture screenshots 01–06, redacted remote response, before/after HPOS rows, subscription meta, scheduler rows, Mailpit IDs and cart proof under `/home/server-manager/slt-evidence/`.
- Any failure creates/updates a `qa/issues/` kanban card containing the lifecycle task ID/path, affected order/subscription/product/user IDs, login/email/role, exact routes/session, steps, expected/actual, UI/network/webhook/meta/scheduler/mail proof and any Stripe counterexample.
- Missing prerequisites or a sandbox outage blocks this card and all consumers of `SUB_PAD`; it never counts as a pass.

## Isolation / teardown
- `SUB_PAD` is the only canonical Paddle Daily subscription and is reused by renewal, email, payment-method, replay and audit tasks.
- Do not cancel it before the keep-alive cohort is released. Teardown owns remote/local cancellation and fixture deletion.

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, capability, scheduler timestamp and email baseline on this run.
- Create/mutate only registered `SLT2 ` / `slt2-*` fixtures; legacy data is read-only.
- Automatic gateways are Stripe and Paddle only; Stripe is the primary path. Do not test/configure PayPal or Mollie.
- Browser assertions use `agent-browser`; every WP-CLI command includes `--allow-root`; both QA boards are updated.
