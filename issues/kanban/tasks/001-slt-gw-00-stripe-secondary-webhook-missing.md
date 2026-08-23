---
id: 1
title: SLT-GW-00 Stripe secondary webhook missing required events
status: closed
priority: critical
created: 2026-08-22T21:27:57.80713441+02:00
updated: 2026-08-23T08:52:32.173678794+02:00
started: 2026-08-23T08:52:32.040644937+02:00
completed: 2026-08-23T08:52:32.173677432+02:00
tags:
    - cycle-2
    - stage-slt-d00
    - slt-gw-00
    - stripe
    - webhook
    - regression
due: "2026-08-23"
class: expedite
---

## QA linkage

- QA progress task: `#131`, stage `stage-slt-d00`, lifecycle key `SLT-GW-00`.
- QA plan: `qa/subscription-lifecycle-test/kanban/tasks/131-slt-gw-00-stripe-paddle-preflight.md`.

## Affected records

- Subscription IDs: N/A.
- Order IDs: N/A.
- Product IDs: N/A; failure occurs before fixture checkout.
- Provider object: one enabled Stripe test-mode ArraySubs secondary webhook endpoint; endpoint ID is intentionally redacted from QA evidence.
- WordPress user/customer: user ID `1`, login `admin`, role `administrator`; no customer account involved.

## Route and context

- Admin URL: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/gateways`.
- Webhook URL: `https://mirror-help.arrayhash.com/wp-json/arraysubs/v1/webhooks/arraysubs_stripe`.
- Browser context: isolated `admin-SLT-GW-00` session.
- Provider check context: read-only WP-CLI call to `ArraySubs\\Features\\AutomaticPayments\\Services\\StripeWebhookProvisioner::checkEndpointHealth()` with `--allow-root`; no create, update, or delete request.

## Reproduction

1. Confirm Woo Stripe is enabled in test mode and both official and ArraySubs secondary webhook secrets are present without printing either value.
2. Run the read-only secondary-endpoint health check.
3. Open the ArraySubs Gateway Logs dashboard and expand Stripe.
4. Compare the remote event subscription to `StripeWebhookProvisioner::getRequiredEvents()`.

## Expected

The enabled secondary Stripe webhook matches the ArraySubs URL, has its saved signing secret, and subscribes to every current required event. Gateway readiness must fail closed or visibly warn when any required event is absent.

## Actual

The remote endpoint exists, is enabled, has a saved secret, and is the single URL match, but the health check returns failure because `payment_method.attached` and `customer.updated` are missing. The admin card still displays `Connected (Test Mode)` and `ArraySubs secondary webhook: Configured`, so the dashboard does not expose this remote event drift.

## Proof

- `/home/server-manager/slt-evidence/SLT-GW-00-stripe-secondary-webhook-health.json` records the redacted provider result and complete required/missing event lists.
- `/home/server-manager/slt-evidence/SLT-GW-00-01-stripe-health-ui.png` shows the browser dashboard claiming connected/configured.
- No order, subscription, payment, charge, or secret-bearing artifact was created.

## Scope notes and counterexamples

- Stripe is the main-priority gateway for this cycle, so this blocks Stripe checkout/lifecycle dependents even though recent legacy Stripe webhook rows exist.
- Paddle is a counterexample: its enabled sandbox configuration is complete and the read-only `GET /products?per_page=1` API probe succeeded with pinned API version `1` and no observed version drift.
- PayPal and Mollie are intentionally excluded by user instruction and their missing credentials are not part of this failure.

## Current dependent impact

- D0 lifecycle/progress cards `1`, `2`, `3`, `4`, `9`, and `14` are explicitly blocked and now depend on preflight card `131` in the board graph.
- Safe independent setup passed while this issue remained open: account matrix task `12` (users `474`–`482`) and catalog tasks `5`/`6`/`7`/`8` (products `31340`, `31347`, `31357`, `31363`). No automatic-gateway transaction was used to obtain those results.
- Retry sequence: repair the Stripe secondary endpoint event set, rerun task `131`'s read-only remote health check and browser readiness assertions, close this issue only on PASS, then move cards `1`/`3`/`4`/`14` back to executable state; tasks `2` and `9` follow their source dependencies.

[[2026-08-23]] Sun 02:33

## Fresh D00 early-morning reproduction — 2026-08-23

- At 00:23:36 UTC / 06:23:36 site, the read-only endpoint health check again returned `success=false`, `endpoint_enabled=true`, `matching_endpoint_count=1`, `secret_present=true`, and `endpoint_id_present=true`; the missing event set remains exactly `payment_method.attached` and `customer.updated`.
- In isolated browser session `admin-D00-early-SLT-GW-00`, the current gateway page still displayed Stripe `Connected (Test Mode)` and the expanded secondary webhook as `Configured`, preserving the dashboard counterexample. Paddle remained connected/configured in sandbox.
- Fresh proof: `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-stripe-secondary-webhook-health.json`, `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-01-gateway-health-current.png`, and `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-02-stripe-expanded-current.png`.
- Retry condition is unchanged: repair the remote required-event set, rerun the same read-only health check and browser assertion, then unblock lifecycle/progress task 131 and dependents only on a fresh PASS.

[[2026-08-23]] Sun 02:44

## Dependent-state correction — D00 early watcher

The earlier `safe independent setup passed` statement is superseded for lifecycle/progress tasks 12 and 5-8: shared issue #2 proves their browser mutations occurred before the assigned afternoon phase and their exact entities/provider objects were absent from the authoritative TSV at closure. Those cards are now blocked pending in-phase non-duplicating revalidation. This correction does not alter the Stripe endpoint failure or its retry condition.

[[2026-08-23]] Sun 06:46
## Fresh D00 late-morning reproduction — 2026-08-23

- At 04:43:50 UTC / 10:43:50 site, the read-only remote check again returned `success=false`, `healthy=false`, `configured=false`, one enabled matching endpoint, and presence-only secret/endpoint-ID flags. The missing event set is still exactly `payment_method.attached` and `customer.updated`.
- The fresh gateway dashboard still displayed Stripe `Connected (Test Mode)` and both official and ArraySubs secondary webhooks as `Configured`; Paddle remained the passing connected sandbox counterexample. Fresh checkout control product `11927` rendered Stripe and Paddle in block and classic checkout, and Paddle was selectable, so method rendering does not mask the webhook defect.
- Fresh proof: `/home/server-manager/slt-evidence/SLT-WATCH-D00-LATE-stripe-secondary-webhook-health.json`, `SLT-WATCH-D00-LATE-browser-preflight.json`, and screenshots `SLT-WATCH-D00-LATE-01` through `07`. Browser errors were empty; no order, subscription, provider event, charge, or email was created; the cart was emptied and all watcher sessions were closed.
- Retry condition remains unchanged: repair the required remote event set, rerun the same read-only provider/browser assertions, and unblock lifecycle/progress task 131 and dependents only after fresh PASS evidence.

[[2026-08-23]] Sun 08:52
## Verified manual repair and root-cause analysis — 2026-08-23

- User confirmed the provider-side/manual re-enable. Local ArraySubs webhook metadata was updated at `2026-08-23 06:43:46 UTC`.
- Fresh read-only health check: `healthy=true`, `configured=true`, remote endpoint `enabled`, `event_count=16`, `missing_events=[]`, one matching endpoint, and signing-secret presence true.
- Fresh browser check at `wp-admin/admin.php?page=wc-settings&tab=checkout&section=arraysubs_stripe` renders `Webhook Enabled`; the official Stripe panel has Stripe and test mode enabled with an attached account. Browser errors were empty. Proof: `/home/server-manager/slt-evidence/SLT-INV-06-arraysubs-webhook-enabled-after-manual-repair.png`. No order, subscription, payment, charge, or email was created.
- Root cause of the former `Webhook Disabled` label: it was a calculated health badge, not proof that Stripe had disabled/disconnected the endpoint. The remote endpoint status was `enabled`; the health check failed only because it lacked `payment_method.attached` and `customer.updated`. Commit `e98991a` (2026-08-17) added those two required events for the Billing Portal return path.
- Why it did not self-heal: the normal admin bootstrap exits when a local secret and endpoint ID already exist. It does not compare the existing remote event list to newly added required events. Existing endpoints are repaired only through the explicit Refresh/save path or when local metadata is absent.
- Result: issue reproduced before repair and passes after the user's confirmed manual re-enable. The long-term migration gap is recorded for follow-up; the active gateway readiness blocker is cleared.
