---
id: 131
title: SLT-GW-00 D0 Stripe and Paddle checkout, webhook and scheduler preflight
status: done
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-23T09:46:11.553752674+02:00
started: 2026-08-22T21:20:53.176731438+02:00
completed: 2026-08-23T08:52:32.568280869+02:00
tags:
    - cycle-2
    - granular
    - setup
    - stripe
    - paddle
    - day-00
due: "2026-08-23"
estimate: 1h15m
depends_on:
    - 10
    - 11
class: standard
---

> **SLT-GW-00** · group `foundation` · scheduled **D00** immediately after baseline capture

## Objective
Fail fast before building the cohort: prove Stripe test mode and Paddle sandbox are enabled, redacted credentials are present, checkout assets/methods load, webhook endpoints are reachable/configured, scheduler/cron works and no PayPal/Mollie secret or execution is required.

## Steps
1. Record enabled/test/sandbox flags and presence-only secret/token/webhook flags for Stripe/Paddle; never print values. Confirm PayPal/Mollie are explicitly excluded from this cycle and do not treat their missing keys as blockers.
2. Verify WordPress/Woo cron and Action Scheduler health, relevant core hook registrations/groups and no duplicate callbacks.
3. Open block and classic checkout with a safe simple subscription probe cart. Because this fail-fast card runs before the fresh SLT2 product cohort is created, the published legacy `SLT Daily Core` control (ID 11927) may be added to an isolated browser cart read-only; do not edit its product/meta and empty the cart before closing. Require Stripe and Paddle scripts/method containers to load without browser or failing network errors. Do not place an order.
4. Verify Stripe webhook status/endpoint secret presence and Paddle webhook endpoint/secret/client token/API presence using redacted summaries and provider-safe status calls.
5. Verify Mailpit, Woo logs and gateway log sources are writable/visible; record baseline IDs/timestamps.
6. Verify official Stripe test cards and Paddle documented sandbox method references used by the plan are current; store no real payment data.
7. Empty carts, close sessions and publish capability/readiness results to the registry. Any failure creates/updates a critical QA issue and blocks all dependent gateway cards.

## Pass criteria
- [ ] Stripe test and Paddle sandbox readiness/config/asset/webhook checks pass
- [x] Scheduler/cron/Mailpit/log baselines are healthy
- [x] PayPal/Mollie are absent from execution and not prerequisites
- [x] No order/subscription/charge or secret-bearing evidence created

## SLT2 execution — BLOCKED (site date 2026-08-23)

- Core ownership is correct: exactly one live `ArraySubs\\Features\\AutomaticPayments\\Provider`, no live legacy Pro provider, and Stripe/Paddle gateway implementations resolve from `arraysubs`. The hook audit found 29 Automatic Payments owners and zero duplicate same-hook/priority/class/method registrations.
- Stripe is enabled in test mode with correctly prefixed test credentials, official and secondary secrets present, a saved secondary endpoint ID, a successful read-only account API call, and both block/classic checkout methods/assets rendering. However, the read-only live endpoint inspection failed: the single enabled secondary endpoint is missing required events `payment_method.attached` and `customer.updated`. The admin Gateway Logs card simultaneously claims `Connected (Test Mode)` and `Configured`. Critical shared issue `qa/issues` #1 records the blocker and dashboard counterproof.
- Paddle is enabled in sandbox with credential markers and webhook secret present, `needs_setup=false`, a successful read-only `GET /products?per_page=1`, pinned API version `1`, no observed version drift, and a visible/selectable method in block and classic checkout. Paddle is the working counterexample.
- Five gateway recurring chains each have exactly one pending action in `arraysubs-gateway`; cron is active/enabled; Mailpit baseline/latest remained `4M53QIPekuKDdmPjFx8ofM`; no new failed/stuck/overdue action, debug entry, browser error, order, subscription, product, charge, or task-attributable email was created. The isolated probe cart was emptied.
- Evidence: `/home/server-manager/slt-evidence/SLT-GW-00-provider-readiness.json`, `SLT-GW-00-stripe-secondary-webhook-health.json`, `SLT-GW-00-hook-scheduler-integrity.json`, `SLT-GW-00-browser-preflight.json`, `SLT-GW-00-official-test-references.md`, and screenshots `SLT-GW-00-01` through `05`.
- Blocking rule: do not begin the fresh checkout cohort or any dependent Stripe/Paddle lifecycle card until issue #1 is fixed and this exact remote health check passes. PayPal/Mollie remain intentionally out of scope.

[[2026-08-23]] Sun 02:32

## D00 early-morning watcher recheck — 2026-08-23 06:23 site

- Fresh read-only remote health recheck still fails: the single enabled matching Stripe secondary endpoint retains its saved secret and endpoint ID but is missing required events `payment_method.attached` and `customer.updated`.
- Fresh authenticated browser recheck in isolated session `admin-D00-early-SLT-GW-00` still shows Stripe `Connected (Test Mode)` and ArraySubs secondary webhook `Configured`; Paddle remains the passing configured sandbox counterexample. Evidence: `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-stripe-secondary-webhook-health.json`, `SLT-WATCH-D00-EARLY-01-gateway-health-current.png`, and `SLT-WATCH-D00-EARLY-02-stripe-expanded-current.png`.
- At 06:26:32 site there were zero registered SLT2 subscriptions, linked orders, future gates, due actions, or post-00:10:16 UTC Mailpit messages. Five natural global sweeps (actions 29319, 29323, 29324, 29325, 29329) completed with empty args and no SLT2 relationship. Evidence: `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-scheduler-mail-reconciliation.json`.
- Shared issue #1 remains open; this card remains blocked. No provider mutation, action execution, checkout, order, subscription, charge, or email trigger occurred.

[[2026-08-23]] Sun 02:53

## D00 due natural-control reconciliation — 2026-08-23

- Exact read-only non-SLT2 transition: overdue shard action `29168` (`arraysubs_check_overdue_renewals`, args `[2,0]`) coincided with subscription `29055` becoming `arraysubs-cancelled` at `19:06:08 UTC`; Paddle remote binding was absent and cancellation outcome was `already_absent`. Exactly two cancellation mails were present: `0YrdpMv4Y3Vw0mveB9fAkG` and `4M53QIPekuKDdmPjFx8ofM`.
- Exact read-only non-SLT2 renewal: action `27212` (args `[12564]`) completed Stripe order `31201` for `$18.00`; official webhook confirmed it and secondary events `evt_3U7LrfJG5OzSNVs21z9OldeO` / `evt_3U7LrfJG5OzSNVs21ubUboNo` were duplicate-skipped. Mail IDs were `3MNSAaBYa8GOwzfLHrEgTk` and `72KRGoCQ1KMjnd32006pMg`; next read-only control actions are `29233` / `29234`.
- The complete D0 Mailpit delta is 13 messages: four natural-control messages plus nine out-of-phase task-12 registration messages; zero appeared after the facts cutoff. Evidence: `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-due-natural-controls.json`.
- Neither control is SLT2 and neither is registered or mutable. These passing reconciliations do not clear the Stripe readiness blocker.

[[2026-08-23]] Sun 03:02

## Closure-audit tracking normalization

The lifecycle `started` timestamp was reconciled to the original `todo -> in-progress` activity event. No verdict, site state, or evidence changed.

[[2026-08-23]] Sun 06:47

## D00 late-morning watcher update — 2026-08-23

- At `04:43:50 UTC` / `10:43:50` site, the redacted read-only Stripe check still found one enabled matching endpoint with secret/endpoint-ID presence but missing exactly `payment_method.attached` and `customer.updated`; the admin UI still claimed Connected/Configured. Shared issue #1 remains the sole task-131 blocker.
- Fresh browser preflight passed independently: Stripe and Paddle rendered on block and classic checkout for authorized legacy control product `11927`, Paddle was selectable, browser errors were empty, no order was placed, and the cart was emptied. Paddle remains the passing sandbox counterexample.
- `future-gates.tsv` is header-only and there are zero SLT2 subscriptions/orders/provider events or new Mailpit messages. Natural empty-arg global actions `29413`, `29439`, `29433`, `29434`, `29435` completed via WP-Cron; later globals `29430`, `29432`, `29438` were not forced and are not registered SLT2 gates.
- Evidence: `/home/server-manager/slt-evidence/SLT-WATCH-D00-LATE-stripe-secondary-webhook-health.json`, `SLT-WATCH-D00-LATE-browser-preflight.json`, `SLT-WATCH-D00-LATE-scheduler-mail-reconciliation.json`, and screenshots `SLT-WATCH-D00-LATE-01` through `07`. All SLT2 browser sessions are closed; no settings/plugin/date/provider-write bracket was opened.

[[2026-08-23]] Sun 08:52
## Post-repair verification — PASS (2026-08-23)

The user confirmed manually re-enabling/repairing the Stripe webhook. At `06:43:46 UTC` local ArraySubs metadata was refreshed. A new read-only remote health check returns healthy/configured, enabled endpoint, 16 required events, no missing events, one matching endpoint and a present signing secret. Fresh admin browser verification renders `Webhook Enabled`; official Stripe remains enabled in test mode with an attached account and no browser errors. Evidence: `/home/server-manager/slt-evidence/SLT-INV-06-arraysubs-webhook-enabled-after-manual-repair.png`. No transaction or email was created.

The earlier disabled label was a health failure caused by two newly required events on a pre-existing remote endpoint, not a Stripe account disconnect. Task 131's Stripe readiness blocker is cleared.


## Upgrade-safety follow-up — PASS (2026-08-23)

Linked qa/issues issue #3 is fixed and closed. Free ArraySubs now fingerprints the required Stripe webhook event schema, lazily reconciles old stored endpoints, and repairs disabled or event-incomplete endpoints in place so the endpoint ID and signing secret are preserved. The temporary admin-triggered auto-healer permits four total attempts with 5-minute, 30-minute, and 2-hour backoff, then stops and shows a persistent red notice linking to the ArraySubs Stripe Refresh page.

Synthetic old-schema/status and retry-exhaustion checks passed. Live authenticated verification stored the missing schema fingerprint while the remote endpoint remained enabled with all 16 events, no missing events, and the same endpoint-ID hash. Browser errors were empty. Evidence: /home/server-manager/slt-evidence/SLT-INV-07-stripe-auto-heal-schema-migration.png and /home/server-manager/slt-evidence/SLT-INV-08-stripe-auto-heal-exhausted-admin-notice.png. The exhausted notice was tested using only temporary local state, which was restored exactly; no Stripe degradation or transaction occurred. No plugin version was bumped.


### Background auto-heal schedule follow-up

This supersedes the earlier admin-triggered-only description: the admin path remains an immediate fast path, and a self-chaining Action Scheduler job in arraysubs-gateway now performs background healthy checks and retry handoffs. Live execution preserved the endpoint ID and produced one successor due in 3,600 seconds. Synthetic scheduling proved the first 300-second delay and no successor after four exhausted attempts, then restored the single healthy chain. A stale schema marker cannot bypass a failed incident's persisted backoff.
