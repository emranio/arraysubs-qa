---
id: 117
title: SLT-OWN-01 Core-only Stripe and Paddle ownership regression with Pro inactive
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - ownership
    - stripe
    - paddle
    - day-10
due: "2026-09-02"
estimate: 3h
depends_on:
    - 29
    - 42
    - 83
    - 85
    - 94
    - 114
    - 130
    - 131
class: standard
---

> **SLT-OWN-01** · group `gateway-ownership` · scheduled **D10** (2026-09-02)

## Objective
Prove the gateway migration is complete: with ArraySubsPro inactive, core alone owns ArraySubs Stripe/Paddle checkout, renewal, retry, webhook, refund, REST and customer-payment behavior. Official WooCommerce Stripe host classes may remain vendor-owned; no ArraySubs gateway service may resolve from Pro.

## Preconditions
- All ordinary two-plugin Stripe/Paddle paths listed in dependencies passed.
- Save exact plugin activation state, core/pro versions, settings, action sets, webhook/audit counts and current SLT2 fixture registry. Announce an exclusive maintenance bracket; no other card may mutate subscriptions during it.

## Steps
1. Capture a redacted runtime ownership map for gateway classes/services/hooks/routes, including file/plugin provenance. Require ArraySubs automatic-payment components under core and no duplicate hook registration.
2. Deactivate ArraySubsPro through wp-admin, verify core remains active and the site/plugin/admin/portal pages load without fatal/error. Never deactivate WooCommerce or official Stripe.
3. Re-run one fresh Stripe simple subscription checkout, one Stripe mixed subscription+regular checkout, and one SCA/signup control using dedicated SLT2 users. Verify loading/confirmation UI, order/subscription links, actions, mail and carts.
4. Re-run one Paddle simple subscription checkout through hosted overlay; verify pending→webhook-paid, remote/local IDs/date and mail.
5. Observe one natural Stripe renewal and one Paddle renewal, requiring one order/charge per cycle, correct scheduler/webhook ownership and no Pro class/file in logs or stack provenance.
6. Execute a Stripe decline/retry probe and exact webhook replay/idempotency control. Require core retry constants/locks and no duplicate actions/orders/mail.
7. From customer portal update a Stripe method and reach the Paddle hosted update path. Verify core REST ownership, capability/ownership checks, loading state and next-renewal use.
8. Refund one task-owned Stripe renewal and one task-owned Paddle transaction where sandbox supports API refund. Reconcile local/remote IDs/statuses and emails.
9. Search PHP/Woo/debug logs, registered REST routes, Action Scheduler hooks/groups and runtime hook callbacks for Pro-owned automatic-payment services. Require none. Record vendor-hosted Stripe/Paddle callbacks separately.
10. Restore ArraySubsPro to its exact prior active/inactive state, verify activation hooks do not duplicate actions/routes/callbacks, and re-run a read-only admin/portal smoke check.
11. Reconcile all new fixtures/messages/actions, close sessions, and mark done only if core-only Stripe and Paddle both pass. Any failure restores Pro first, creates/updates a critical `qa/issues/` kanban card, and leaves this task blocked.

## Pass criteria
- [ ] Core-only Stripe checkout, SCA, renewal, retry, replay, method update and refund pass
- [ ] Core-only Paddle checkout, webhook renewal, method-update path and supported refund pass
- [ ] No ArraySubs automatic-payment class/hook/route resolves from Pro; no duplicates after restore
- [ ] Pro activation state restored exactly; non-SLT2 data/actions unchanged

## Evidence / teardown
- Store redacted class/hook/route provenance, plugin states, screenshots, orders/subscriptions/actions/webhooks/refunds/mail IDs and pre/post diffs in the fresh evidence root.
- Register every new SLT2 fixture for D11 restore/D13 teardown. Automatic gateways are Stripe/Paddle only.
