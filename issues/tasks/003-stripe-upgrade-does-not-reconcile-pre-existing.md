---
id: 3
title: Stripe upgrade does not reconcile pre-existing secondary webhook event schema
status: closed
priority: high
created: 2026-08-23T08:58:04.080182565+02:00
updated: 2026-08-23T09:45:55.294473461+02:00
started: 2026-08-23T09:07:42.639378654+02:00
completed: 2026-08-23T09:33:23.186850063+02:00
tags:
    - stripe
    - webhook
    - upgrade
    - migration
    - regression
    - customer-risk
class: standard
---

## QA linkage

- QA progress task: `#131`, stage `stage-slt-d00`, lifecycle key `SLT-GW-00`.
- QA plan: `qa/kanban/tasks/131-slt-gw-00-stripe-paddle-preflight.md`.

## Affected records

- Subscription IDs: N/A; this is an upgrade/migration defect affecting pre-existing Stripe configurations.
- Order IDs: N/A.
- WordPress user/customer: N/A.
- Provider object: an ArraySubs secondary Stripe webhook with a stored endpoint ID and signing secret created before the required event set changed.

## Route and context

- Admin URL: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=arraysubs_stripe`.
- Webhook URL: `https://mirror-help.arrayhash.com/wp-json/arraysubs/v1/webhooks/arraysubs_stripe`.
- Browser context: administrator on the ArraySubs Stripe settings panel.
- Relevant code: `arraysubs/src/Features/AutomaticPayments/Services/StripeWebhookProvisioner.php` and `StripeSettingsTab.php`.

## Reproduction

1. Start with a stored secondary Stripe endpoint/secret created before `payment_method.attached` and `customer.updated` became required.
2. Upgrade ArraySubs without saving Stripe settings or selecting the explicit Refresh action.
3. Visit the ArraySubs Stripe settings panel.
4. Compare the stored remote endpoint events with the current required list.

## Expected

ArraySubs automatically and idempotently reconciles a pre-existing endpoint when the required event schema changes. Until reconciliation succeeds, the UI distinguishes an enabled but incomplete endpoint from a remotely disabled or disconnected endpoint.

## Actual

The normal admin bootstrap returns when a local secret and endpoint ID exist, so it never checks the remote event schema. The settings page maps every unhealthy result to `Webhook Disabled`, even when Stripe reports the endpoint itself as `enabled`. Missing `payment_method.attached` and `customer.updated` can prevent Billing Portal card/default-payment changes from reaching ArraySubs.

## Proof

- Test-site issue #1 reproduced one enabled endpoint missing exactly the two newly required events.
- Commit `e98991a` added those events after the endpoint had been created.
- The user manually repaired the test endpoint; the post-repair counterexample has 16 events, none missing.
- No subscription, order, customer, or payment record is required to reproduce this upgrade defect.

## Scope notes and counterexamples

- The official WooCommerce Stripe gateway remains enabled, so ordinary checkout is not automatically disabled by this health result.
- Current payment intent, invoice, charge, and previously subscribed events continue to arrive.
- The repaired test endpoint is healthy, but that manual repair does not protect other upgraded customer sites.

## Required resolution

- Add an idempotent, rate-limited endpoint-schema migration to the free plugin.
- Preserve the endpoint ID and signing secret by updating missing events in place.
- Persist a per-mode schema fingerprint only after remote health passes.
- Show `Enabled but missing events` while an enabled endpoint remains incomplete.
- Do not bump ArraySubs or ArraySubsPro versions.


## Fix and verification — PASS (2026-08-23)

### Implementation

- Fixed in arraysubs only. ArraySubsPro has no owner or duplicate implementation for this secondary webhook contract.
- Added a required-event schema fingerprint per Stripe mode. A pre-existing endpoint is checked on the first eligible admin request after the schema changes, without relying on a plugin version bump.
- Recoverable disabled or incomplete endpoints are updated in place. ArraySubs merges the existing event list with all required events and re-enables the endpoint, preserving the stored endpoint ID and signing secret.
- Added the temporary isolated StripeWebhookAutoHealer service. It runs only when the official Stripe gateway is enabled, uses a per-mode atomic lock, rechecks a healthy endpoint no more than hourly, and clears its incident state when Stripe is intentionally disabled.
- Retry policy is one initial attempt plus three retries: 5 minutes, 30 minutes, and 2 hours. Attempt 4 becomes exhausted and schedules no further API call until a successful manual repair, a Stripe disable/re-enable cycle, or a new required-event fingerprint starts a new incident.
- A failed or exhausted incident renders a persistent notice-error admin notice. The button navigates to wp-admin/admin.php?page=wc-settings&tab=checkout&section=arraysubs_stripe, where the existing nonce-protected Refresh action remains available.
- The settings badge now distinguishes Enabled but missing events from Disabled.

### Verification

- PHP syntax checks passed for StripeWebhookProvisioner.php, StripeWebhookAutoHealer.php, and StripeSettingsTab.php. All touched files are below 3,000 lines.
- Pure synthetic regression check reproduced an enabled endpoint missing payment_method.attached and customer.updated, classified it as repairable in place, and rendered the exact Enabled but missing events badge.
- Pure retry-policy verification returned delays/statuses 300 seconds failed, 1,800 seconds failed, 7,200 seconds failed, then attempt 4 exhausted with no next retry.
- Live lazy-migration check started with no stored schema fingerprint. The first authenticated admin visit stored the current fingerprint, left the test endpoint enabled with all 16 events and no missing events, and preserved endpoint ID hash e621caee616ae3c63d3667c04c5369769f1d8939ac5b4a9f388de521783d7ff3 before and after. Retry state is healthy with zero attempts.
- Browser evidence: /home/server-manager/slt-evidence/SLT-INV-07-stripe-auto-heal-schema-migration.png and /home/server-manager/slt-evidence/SLT-INV-08-stripe-auto-heal-exhausted-admin-notice.png. Browser errors were empty. The notice button reached the exact requested settings route.
- The exhausted-notice test changed only a temporary local retry-state option, then restored the exact healthy state and removed its QA backup. It did not disable, delete, or alter the remote Stripe endpoint and created no order, subscription, charge, or email.
- ArraySubs remains 1.8.12 and ArraySubsPro remains 1.1.3; neither version was bumped.


### Post-close lock robustness check

The per-mode option lock accepted the first caller, rejected a concurrent caller, recovered an invalid zero-valued stale lock, and left no lock option behind. This confirms the retry counter cannot be advanced by overlapping admin requests and a malformed stale lock cannot suppress healing permanently.


### Final background scheduling verification

The temporary healer now owns a self-chaining Action Scheduler job in the arraysubs-gateway group, using the centralized hook constant and indexed args. Normal requests only ensure that one lightweight action exists; Stripe API work runs in the scheduler or through the authenticated admin fast path. A healthy live handler run preserved the endpoint ID and produced exactly one successor due in 3,600 seconds. Synthetic scheduling produced an exact 300-second first retry, while an exhausted four-attempt state produced zero pending successors; the healthy chain was then restored.

A pre-handoff edge case was also corrected: the deliberately missing schema fingerprint no longer bypasses a failed incident's backoff. Synthetic proof confirms a failed not-yet-due state does not run even while schema metadata is stale, a due failure does run, a healthy stale schema runs immediately, and exhausted state never runs.

