---
id: 131
title: 'stage-slt-d00: SLT-GW-00 D0 Stripe and Paddle checkout, webhook and scheduler preflight'
status: closed
priority: critical
created: 2026-08-22T20:44:01.560922205+02:00
updated: 2026-08-23T09:46:04.565234097+02:00
started: 2026-08-22T21:20:53.425993393+02:00
completed: 2026-08-23T08:52:32.926833908+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d00
    - slt-gw-00
due: "2026-08-23"
estimate: 1h15m
depends_on:
    - 10
    - 11
class: standard
---

Lifecycle task 131 / SLT-GW-00. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/131-slt-gw-00-stripe-paddle-preflight.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.

## Result — BLOCKED (2026-08-23 site date)

Critical shared issue #1: the enabled live Stripe test secondary webhook is missing required `payment_method.attached` and `customer.updated` subscriptions, while the admin Gateway Logs UI claims Stripe is connected/configured. Paddle sandbox API, webhook configuration, and both checkout renderers passed; core owns the gateway graph with no duplicate callbacks; cron, Action Scheduler, Mailpit, logs, browser isolation, and zero-record-mutation checks passed. Complete redacted evidence is under `/home/server-manager/slt-evidence/SLT-GW-00-*`. Do not begin dependent checkout/lifecycle tasks until issue #1 is resolved and task 131 is rerun.

[[2026-08-23]] Sun 02:33

## Early-morning watcher update — 2026-08-23 06:26 site

Lifecycle task `131` remains blocked on shared issue #1. Fresh provider and browser checks reproduce the two missing Stripe events (`payment_method.attached`, `customer.updated`) while the UI still reports Connected/Configured; Paddle remains the passing sandbox counterexample. There are zero SLT2 subscriptions, orders, future gates, due actions, or new post-cutoff Mailpit messages. Evidence: `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-stripe-secondary-webhook-health.json`, `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-scheduler-mail-reconciliation.json`, and screenshots `SLT-WATCH-D00-EARLY-01`/`02`.

[[2026-08-23]] Sun 02:53

## Exact due-control addendum — D00 early morning

Lifecycle `131 / SLT-GW-00` reconciled natural non-SLT2 cancellation `29055` via action `29168` and natural Stripe renewal `12564` / order `31201` via action `27212`, including exact provider events and four related Mailpit IDs. Nine additional D0 messages belong to out-of-phase task `12 / SLT-SETUP-03`; zero messages followed the facts cutoff. Evidence: `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-due-natural-controls.json`. Task 131 remains blocked only on shared issue #1.

[[2026-08-23]] Sun 06:47

## D00 late-morning watcher update — 2026-08-23

- At `04:43:50 UTC` / `10:43:50` site, the redacted read-only Stripe check still found one enabled matching endpoint with secret/endpoint-ID presence but missing exactly `payment_method.attached` and `customer.updated`; the admin UI still claimed Connected/Configured. Shared issue #1 remains the sole task-131 blocker.
- Fresh browser preflight passed independently: Stripe and Paddle rendered on block and classic checkout for authorized legacy control product `11927`, Paddle was selectable, browser errors were empty, no order was placed, and the cart was emptied. Paddle remains the passing sandbox counterexample.
- `future-gates.tsv` is header-only and there are zero SLT2 subscriptions/orders/provider events or new Mailpit messages. Natural empty-arg global actions `29413`, `29439`, `29433`, `29434`, `29435` completed via WP-Cron; later globals `29430`, `29432`, `29438` were not forced and are not registered SLT2 gates.
- Evidence: `/home/server-manager/slt-evidence/SLT-WATCH-D00-LATE-stripe-secondary-webhook-health.json`, `SLT-WATCH-D00-LATE-browser-preflight.json`, `SLT-WATCH-D00-LATE-scheduler-mail-reconciliation.json`, and screenshots `SLT-WATCH-D00-LATE-01` through `07`. All SLT2 browser sessions are closed; no settings/plugin/date/provider-write bracket was opened.

[[2026-08-23]] Sun 08:52
## Post-repair verification — PASS (2026-08-23)

User-confirmed manual Stripe webhook repair is verified by a fresh read-only endpoint health check: enabled, 16 events, no missing events, one endpoint match and signing-secret presence. The WooCommerce ArraySubs Stripe panel now renders `Webhook Enabled`; the official Stripe panel remains enabled in test mode with an attached account and no browser errors. Evidence: `/home/server-manager/slt-evidence/SLT-INV-06-arraysubs-webhook-enabled-after-manual-repair.png`. No order, subscription, payment, charge, or email was created. Stripe preflight no longer blocks execution.


## Upgrade-safety follow-up — PASS (2026-08-23)

Linked regression issue qa/issues #3 is fixed and closed in the free arraysubs plugin. Existing enabled Stripe configurations now receive an idempotent event-schema check; recoverable disabled or incomplete secondary endpoints are updated in place without rotating their endpoint ID or signing secret. The temporary auto-healer is capped at four total attempts with 5-minute, 30-minute, and 2-hour backoff, then stops and renders a red admin notice linking to the ArraySubs Stripe Refresh page.

Synthetic regression checks passed for the Enabled but missing events state and the complete retry sequence. Live browser migration verification kept the current endpoint enabled with 16 events, no missing events, unchanged endpoint-ID hash, healthy retry state, and no browser errors. Exhausted-notice evidence was produced with local state only and then fully restored. Evidence: /home/server-manager/slt-evidence/SLT-INV-07-stripe-auto-heal-schema-migration.png and /home/server-manager/slt-evidence/SLT-INV-08-stripe-auto-heal-exhausted-admin-notice.png. No transaction, provider degradation, or email was created; plugin versions were not changed.


### Background auto-heal schedule follow-up

The temporary healer is not dependent on an admin staying online: a self-chaining Action Scheduler job in arraysubs-gateway performs the hourly healthy check and exact retry handoffs, while admin_init remains the immediate fast path and notice surface. Live handler verification preserved the endpoint ID and left exactly one healthy successor due in 3,600 seconds. Synthetic scheduling proved attempt-1 delay 300 seconds and zero successor after attempt 4/exhausted, then restored the single healthy chain. Failed incidents now honor persisted backoff even while their schema fingerprint remains intentionally unstored.
