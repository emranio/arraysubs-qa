---
id: 96
title: Upgrade a Paddle-billed ladder subscription and prove remote price synchronization
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - plan-switching
    - paddle
    - day-06
due: "2026-08-29"
estimate: 2h
depends_on:
    - 26
    - 60
    - 72
class: standard
---

> **SLT-SW-05** · group `plan-switching` · starts **D06** (2026-08-29), completes after the next Paddle renewal

## Objective
Create or consume one registered Paddle-billed Basic ladder subscription, upgrade it through the real customer switch flow, and prove both local subscription state and Paddle's remote product/price/quantity are updated so the next remote renewal charges the new plan exactly once.

## Scope
- Gateway: Paddle sandbox only
- Checkout: block + hosted Paddle switch payment when required
- Account: dedicated `slt2-paddle-switch`
- Plugins: core-owned Paddle gateway plus Pro plan-switching UI/workflow

## Preconditions
- Ladder products and Paddle readiness passed; each ladder product has a current Paddle product/price ID derived in this cycle.
- Use a dedicated account/cart and a single registered source subscription. Missing catalog sync or hosted checkout blocks the card; do not manually complete orders or edit metas.

## Steps
1. If no registered Paddle Basic source exists, buy `SLT2 Plan Basic` once through Paddle using the same strict pending→webhook-paid contract as task 29. Publish its order/subscription/remote IDs as `SUB_PAD_SWITCH`.
2. Snapshot the pre-switch local subscription/order/actions/notes and redacted remote product, price, quantity, status and next-billed-at.
3. In My Account, open `SUB_PAD_SWITCH`, choose the linked Pro upgrade, capture the preview/classification, due-now amount, credits/proration, next amount/date and confirmation modal.
4. Confirm once. If payment is required, follow the real order-pay/Paddle hosted path and wait for webhook settlement; do not capture populated payment fields.
5. Resolve the exact switch order by response/relationship, and require one local subscription record (no duplicate parent/source), product/amount changed to Pro, correct completed-payment/cycle semantics, notes and rescheduled actions.
6. Fetch the same remote Paddle subscription redacted. Require its item/product/price and recurring amount to match the Pro target, quantity to remain correct, status active and next-billed-at to match the local next date contract.
7. Publish the next renewal gate and take a Mailpit baseline inside its five-minute pre-gate window. Observe natural Paddle renewal.
8. Require exactly one new remote transaction and one paid renewal order at the Pro recurring price, correct reverse links, one completed-payment increment, advanced date and no charge at the old Basic price.
9. Reconcile switch and renewal email sets, local scheduler/webhook/audit rows, customer portal and admin detail. Reject duplicate subscriptions, orders, transaction IDs or messages.
10. Append every ID/gate to the registry, empty carts, close sessions, review, and mark done only when source purchase, remote price change and new-price renewal all pass.

## Expected results
1. The customer can complete the Paddle upgrade through supported UI/hosted payment flow.
2. One existing subscription changes from Basic to Pro locally; relationship, proration and schedule remain coherent.
3. The same Paddle subscription updates to the Pro product/price before renewal.
4. The next Paddle renewal charges the Pro amount once; no old-price or duplicate charge/order occurs.

## Evidence / issue contract
- Save safe screenshots, switch response/order, local meta/actions/notes, redacted before/after Paddle responses, webhook rows and Mailpit messages.
- Any missing UI, failed hosted settlement, absent remote price update or wrong renewal amount creates/updates a mandatory `qa/issues/` kanban card with all required task/stage/plan, IDs, user context, routes/gates, reproduction, expected/actual and counterexamples. Keep this card blocked until rerun passes.

## Isolation / teardown
- Dedicated user/source prevents collision with `SUB_PAD`. Leave the final Pro subscription registered for parity/watch and teardown.

### Fresh-cycle validation contract

- Treat previous Paddle switching findings as fixed and re-test from zero.
- Stripe and Paddle only; use `agent-browser`, Mailpit and WP-CLI `--allow-root`; update both QA boards.
