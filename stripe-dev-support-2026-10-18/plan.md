# Staging Stripe developer-support QA plan (run 2026-10-18, restart r2 2026-10-19)

Scope source: `scope.md` in this folder (owner scope, lead addendum, restart addendum). This plan supersedes nothing outside this folder. Earlier developer-support artifacts in `qa/developer-support-2026-10-18/` belong to a previous run and are read-only context (their open Stripe verification blocker is `qa/issues` #51).

## Outage and restart note

- Attempt 1 (2026-10-18) stopped in phase 1 at ~15:45 CEST (13:45 UTC) when the site database became unreachable (`Error establishing a database connection`, see `evidence/fixtures-phase1-setup.stderr`). No board tasks existed and the capture mu-plugin was never installed. Correction (verified in r2): r1 had already created customers 576/577/578 (registered 2026-10-18 13:45:35-44 UTC) before the outage; r2 reused them (no orders/subscriptions), reset their passwords (stored outside the QA folder, never printed) and registered them in `fixtures.json`.
- Restart r2 began 2026-10-19 ~07:00 UTC. Preflight was re-run into `-r2` evidence files (r1 files kept). The settings snapshot was re-taken as `snapshots/settings-original-r2.json` and proven identical to `snapshots/settings-original.json` (`snapshots/diff-original-r1-vs-r2.json`: `all_identical: true`). Either file is a valid restore source; phase 7 uses **`snapshots/settings-original-r2.json`**.
- Database resilience rule: on a WP-CLI DB connection error, retry for up to ~5 minutes; if it persists, stop, write the phase handoff, report BLOCKED.

## Environment and revisions under test

| Item | Value |
|---|---|
| Site | https://mirror-help.arrayhash.com (WP root `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public`) |
| Core (arraysubs) HEAD | `47918322ebbad435bf27749b0dc5cedc7a4a7e6b` (4791832), descends from requested `a5c9711fdc955a54b7c396827fd7b223b48408ef` via 44036ed, cfcfaac, 4791832 (PHP only) |
| Pro (arraysubspro) HEAD | `edaa4010dbc821120c3f34975a395c73dd470e2a` (edaa401), descends from requested `561ed9235bb4859c2ec8ae1d809f8240f3d071e7` (PHP only) |
| Versions | ArraySubs 2.0.9, ArraySubs Pro 1.2.8, WooCommerce 11.0.1, WooCommerce Stripe Gateway 10.9.0 |
| Built assets | unchanged since the 2026-10-18 14:06:30 (CEST) production builds; `src/resources` has no diff since the requested revisions (see `evidence/preflight-revisions-build-r2.json`) |
| Active plugins (original) | arraysubs, arraysubspro, woocommerce, woocommerce-gateway-stripe, wp-mail-smtp |
| Stripe | official gateway, test mode, Connect; currency USD; official webhook `we_1UACQaJG5OzSNVs2Q317EsnD`; ArraySubs secondary webhook `we_1U7V8AJG5OzSNVs2KHSlEsOW` (`/wp-json/arraysubs/v1/webhooks/arraysubs_stripe`) |
| Scheduler | WP-Cron disabled; OS cron runs `wp-cron.php` every minute (Action Scheduler queue runner) |
| Email sink | Mailpit via WP Mail SMTP; `mailpit-agent` |

Special attention (newer core commits): lifecycle `event_id` uniqueness per failure and per charge attempt (`_arraysubs_lifecycle_attempt_seq`), `grace_ended` carrying the overdue renewal `order_id` with an operation-scoped `event_id`, and no behavior change in Stripe renewals/retries.

## Relevant original settings (from the r2 snapshot)

- Retry: enabled, intervals 24h/24h/24h, stop on hard decline.
- Renewals: grace 3 days before on-hold, 7 days before cancel; invoice 6 hours before due; spread window 6 hours.
- Store credit: auto-apply to renewals on, apply at checkout on, min order 5.
- Plan switching: disabled (phase 5 must enable and restore).
- Auto-renew toggle allowed; members access on; feature manager per-subscription aggregation; trials require a payment method, one trial per customer; cancellation immediate allowed.

## Method (all phases)

- Browser: agent-browser with isolated sessions `--session sqa-admin`, `--session sqa-<customer>`, `--session sqa-guest`. Screenshots in `screenshots/`.
- WP-CLI always with `--allow-root`, run from the WP root.
- Fixtures only: every customer/product/page/post/subscription/order/action created by this run is recorded with `scripts/register-fixture.php` into `fixtures.json` (customers/subscriptions also into `capture/fixture-scope.json`). Due dates are brought forward only on fixtures with `\ArraySubs\Developer\Subscriptions::fromId( $id )->setNextPaymentDate( time() + N )`; only fixture actions are run (`wp action-scheduler action run <id> --allow-root`) or left to the queue runner.
- Payment proof comes from real Stripe test-mode outcomes (PaymentIntents/charges/events), never from manually marking orders paid or firing PHP payment hooks.
- Lifecycle capture: test-only mu-plugin `wp-content/mu-plugins/sqa-lifecycle-capture.php` (source `scripts/mu-plugin-sqa-lifecycle-capture.php`) appends `arraysubs_lifecycle_event` notifications for fixture subscriptions/customers to `capture/lifecycle-events.jsonl`. Compared against developer snapshots `\ArraySubs\Developer\Subscriptions::fromId( $id )->data()` taken with `scripts/dev-snapshot.php`. Notification delivery is not required to be exactly-once; business effects must be idempotent.
- Secrets never printed or stored; Stripe object IDs (pi_, ch_, evt_, pm_, cus_) are references, not secrets. Card data limited to the test card brand/last4.
- Settings: snapshot before change, restore exactly, prove with `scripts/diff-settings.php`.
- Defects go to `qa/issues/` (kanban-md from that directory) with all AGENTS.md fields; existing issues preserved.

## Phase split (one agent per phase, sequential)

| Phase | Cases | Configuration | Notes |
|---|---|---|---|
| 1 | Preflight, settings snapshot, plan/board, capture plugin, shared fixtures, **S01, S02** | core + Pro | this restart |
| 2 | **S03, S04** | core + Pro | S04 also verifies `grace_ended` order ID and stable event_id on redelivery; changes retry/grace settings (restore after) |
| 3 | **S05, S06** | core + Pro | replay with valid/invalid signatures computed from the endpoint secret (never printed) |
| 4 | **S07, S08** | core + Pro | feature allowance (Pro feature manager), trials, cancellation |
| 5 | **S09, S10** | core + Pro, plus Pro-absent / Store Credit off / auto-apply off variants of S10 | plan switching and store credit settings changed and restored |
| 6 | Core alone: ordinary checkout (S01), renewal (S02), failure/recovery (S03/S05 ordinary paths), replay (S06) | core only (Pro deactivated with WP-CLI, then reactivated) | confirm active plugin set restored |
| 7 | Cleanup, restoration proof (settings diff vs `settings-original-r2.json`, active plugins), mu-plugin removal, final report | - | fixture cleanup confined to `fixtures.json` |

## Case matrix (both configurations)

Configuration A = core + Pro (phases 1-5). Configuration B = core alone (phase 6). Pro-only behavior (feature allowance S07, Store Credit S10 auto-apply) is verified in A; B checks the core-side fallback where the scope asks for it (S10 Pro-absent deferral).

| ID | Test | Config A phase | Config B phase | Fixtures (planned) |
|---|---|---|---|---|
| S01 | Checkout and saved method: Classic + Block success, relationships, price/currency, entitlement, reusable PM, future renewal actions; initial failure grants nothing | 1 | 6 | sqa-p1-classic, sqa-p1-block, sqa-p1-initfail; SQA Daily Stripe Plan |
| S02 | Automatic due renewal: invoice + due payment action with saved PM; one collection, renewal order/history, completed payments, billing date advanced, next-cycle jobs aligned | 1 | 6 | S01 Classic + Block subscriptions |
| S03 | Decline and authentication on renewal; recoverable without duplicate collection | 2 | 6 (decline + recovery) | new phase-2 customers (0341 / 9995 / 3184 cards) |
| S04 | Retries and grace: timing/counts, retry disabled, hard decline, exhaustion, grace windows, on-hold/cancel, individual grace overrides, settlement guards, grace_ended order_id/event_id | 2 | - | phase-2 fixtures |
| S05 | Recovery and missed callback: replace PM, customer/admin retry, one settlement; delayed callback reconciled by pre-retry verification | 3 | 6 | phase-3 fixtures |
| S06 | Replay, races, late events; invalid signature no mutation | 3 | 6 | phase-3 fixtures |
| S07 | Billing-cycle feature allowance (Pro) | 4 | n/a (Pro only) | phase-4 fixtures |
| S08 | Trials and cancellation | 4 | - | phase-4 fixtures |
| S09 | Paid and deferred plan switches | 5 | - | phase-5 fixtures |
| S10 | Store Credit with Stripe; Pro absent / Store Credit off / auto-apply off deferral | 5 | 5 (Pro-absent variant) or 6 | phase-5 fixtures |

## Fixture registry

All IDs are in `fixtures.json` (append-only). Phase 1 shared fixtures and S01/S02 references are listed in `handoff-phase1.md` and in the results section below.

## Results

Per-phase results are appended in each `handoff-phaseN.md` and summarised in the final report (phase 7). Phase 1 results: see `handoff-phase1.md`.

## Environment note: Cloudflare edge cache (found 2026-10-19 in S01-A)

Front-end HTML is cached at the Cloudflare edge (`cf-cache-status: HIT`, no origin `Cache-Control`) and a cached guest copy is served to logged-in visitors. A member therefore saw the guest "SQA-DENIED-7731" copy of post 68274 at its plain URL while a cache-busted URL showed the entitled content and the developer access check returned `allowed: true`. Logged-in responses were not stored at the edge (guest fetch of a member-visited cache-busted URL was a MISS and denied), so no entitled-content leak was observed. This is a staging CDN behavior, not a plugin defect; all entitlement checks in this run use a unique `?sqa…=<ts>` query and record the developer `MemberAccess::check()` decision alongside.
