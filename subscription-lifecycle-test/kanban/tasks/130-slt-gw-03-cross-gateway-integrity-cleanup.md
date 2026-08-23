---
id: 130
title: SLT-GW-03 Cross-gateway identity, idempotency, migration and cleanup integrity
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, stripe, paddle, integrity, day-07]
due: "2026-08-30"
estimate: 2h30m
depends_on: [83, 96, 128, 129]
class: standard
---

> **SLT-GW-03** · group `gateway-matrix` · scheduled **D07**

## Objective
Prove Stripe and Paddle records cannot contaminate each other during checkout, webhook replay, payment-method update, switching, renewal, refund, cancellation or teardown, and verify the core-migration ownership data remains internally consistent.

## Steps
1. Export all SLT2 Stripe/Paddle subscriptions, orders, provider/customer/payment/transaction IDs, actions and webhook rows into a redacted mapping.
2. Require Stripe-shaped IDs only on Stripe records and Paddle-shaped IDs only on Paddle records; gateway filters, portal labels and admin panels must match.
3. Replay one exact Stripe and Paddle event; cross-post wrong-gateway ID/signature only to the safe rejected boundary. Require zero cross-gateway state/order/note/mail mutation.
4. Update one method per gateway and prove only its exact subscription/remote record changes.
5. Execute a plan switch and refund/cancel per gateway; verify remote/local operations target the correct provider object and use idempotency/locks.
6. Test same email/customer with separate gateway subscriptions, gateway change attempt on an existing subscription, stale/duplicate event and out-of-order event. Require safe refusal/reconciliation and no method/transaction overwrite.
7. Reconcile pending actions/webhook audit/logs and delete/cancel allowlists. No provider ID may be shared by unrelated subscriptions.
8. Verify all gateway services/data remain usable with Pro inactive in task 117; hand off exact integrity map.

## Pass criteria
- [ ] No cross-shaped/misowned ID, method, transaction, action, note or webhook exists
- [ ] Duplicate/wrong/out-of-order events are idempotent or safely rejected
- [ ] Updates/switches/refunds/cancellations affect only the exact provider subscription
- [ ] Core ownership and teardown allowlists reconcile

Any mismatch creates/updates a critical `qa/issues/` card and blocks completion.
