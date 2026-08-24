---
id: 2
title: D00 account and catalog fixtures mutated before owning phase and omitted from registry
status: closed
priority: critical
created: 2026-08-23T02:35:07.3244436+02:00
updated: 2026-08-23T12:53:22.332229032+02:00
started: 2026-08-23T10:55:58.255227205+02:00
completed: 2026-08-23T12:53:22.33222825+02:00
tags:
    - cycle-2
    - stage-slt-d00
    - phase-order
    - registry
    - isolation
due: "2026-08-23"
class: standard
---

## QA linkage

- QA progress tasks: `#12`, `#5`, `#6`, `#7`, `#8`; stage `stage-slt-d00`; lifecycle keys `SLT-SETUP-03`, `SLT-PROD-01`, `SLT-PROD-06`, `SLT-PROD-07`, `SLT-PROD-13`.
- QA plans: `qa/kanban/tasks/012-slt-setup-03-create-the-slt-account-matrix-7-slt.md`, `005-slt-prod-01-create-slt-daily-core-the-day-1.md`, `006-slt-prod-06-create-slt-fixed-three-cycles-a-day-2.md`, `007-slt-prod-07-create-slt-lifetime-one-time-the-never.md`, and `008-slt-prod-13-create-slt-flex-week-segments-the.md`.

## Affected records

- Subscription IDs: N/A.
- Order/payment/action IDs: N/A.
- Product IDs: `31340`, `31347`, `31357`, `31363`.
- Provider objects: Paddle sandbox product/price bindings exist for products `31340`, `31347`, and `31363`; exact safe IDs are retained in product meta and must be reconciled into teardown ownership. Product `31357` has no matching gateway binding.
- WordPress users: `474 slt2-core / slt2-core@example.test`, `475 slt2-trial / slt2-trial@example.test`, `476 slt2-switch / slt2-switch@example.test`, `477 slt2-flex / slt2-flex@example.test`, `478 slt2-fail / slt2-fail@example.test`, `479 slt2-paddle / slt2-paddle@example.test`, `480 slt2-admincreated / slt2-admincreated@example.test`, `481 slt2-flex2 / slt2-flex2@example.test`, `482 slt2-flex3 / slt2-flex3@example.test`; all role `customer`.
- Reserved absent guests: `slt2-guest-d0@example.test`, `slt2-guest-d5@example.test`.

## Route and context

- Registry URL: `https://mirror-help.arrayhash.com/slt2-catalog-registry/` (private page ID `31301`).
- Browser context: isolated watcher session `admin-D00-early-SLT-SETUP-01`; creating-card evidence names `admin-SLT-SETUP-03`, `admin-SLT-PROD-01`, `admin-SLT-PROD-06`, `admin-SLT-PROD-07`, and `admin-SLT-PROD-13`.
- Gate: D00 afternoon, planned site time approximately `16:10`; this early-morning invocation owns approximately `06:10`.

## Reproduction

1. Read `watch-schedule.md`: D00 early/late morning owns tasks 10, 11, and 131; afternoon owns task 12 and tasks 5-8.
2. Resolve the exact user IDs and product IDs from lifecycle evidence and bidirectionally verify them against live WP-CLI and registry page 31301.
3. Compare live site timestamps with the assigned phase.
4. Compare the complete private registry page with authoritative `evidence/fixture-registry.tsv`.

## Expected

No account/product browser mutation occurs before the assigned D00 afternoon phase. Every exact SLT2 fixture and owned provider object is recorded in the authoritative TSV registry before a creation card is marked done, and the TSV/private-page sets agree.

## Actual

Users 474-482 were created at site `01:47:33-01:51:04`; products 31340/31347/31357/31363 were published at `02:01:58`, `02:13:46`, `02:21:23`, and `02:29:21`. These mutations occurred roughly 13.5-14.5 hours before the owning afternoon phase. The private page contains the accounts, reserved guests, products, and flex schedule, but the authoritative TSV contained only page IDs 31296/31298/31301 when the cards were marked done.

## Proof

- Current authenticated page screenshot: `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-03-catalog-registry-current.png`.
- Exact account evidence: `/home/server-manager/slt-evidence/SLT-SETUP-03-users.json`.
- Exact product evidence: `/home/server-manager/slt-evidence/SLT-PROD-01-product.json`, `SLT-PROD-06-product.json`, `SLT-PROD-07-product.json`, and `SLT-PROD-13-product.json`.
- Watcher scheduler/mail proof shows zero subscription, order, action, or later gate tied to these fixtures: `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-scheduler-mail-reconciliation.json`.

## Scope notes and counterexamples

- The relative task sequence was correct and tasks 10/11 remain phase-consistent; the failure is absolute phase ownership plus authoritative registry publication.
- The private registry page is a positive counterexample: its exact IDs match live WP-CLI and evidence. It does not cure the TSV divergence or early mutation.
- Stripe/Paddle transactions were not used for these setup results. PayPal and Mollie remain excluded.

## Current dependent impact and retry

- Park lifecycle/progress tasks 12 and 5-8 as blocked. Do not delete, recreate, rename, or duplicate the exact fixtures ad hoc.
- The early watcher may backfill exact proven identity rows solely to restore ownership/teardown safety; that does not waive the phase violation.
- At the owning afternoon phase, the cycle owner must select and document a non-duplicating repair/revalidation protocol, verify the complete TSV/private-page/provider-object set, rerun every mandatory assertion, then close this issue and pass the cards only with fresh in-phase proof.

[[2026-08-23]] Sun 02:38

## Early-watcher containment update — 2026-08-23

- Backfilled the authoritative TSV with the four exact products, nine exact users, two absent reserved guests, and all six exact Paddle sandbox objects solely for ownership/teardown safety. Every row has 14 fields and `cleanup_approved=no`; no remote deletion is authorized.
- Exact Paddle provider mappings: product 31340 → `pro_01m0nh1pxqymawg7yc6j3krmsx` / `pri_01m0nh1qw5barwpaeaa8s0jdsf`; 31347 → `pro_01m0nhqad1kxs86czedp5gmyfy` / `pri_01m0nhqbcjykweg9hv4qj9gmjy`; 31363 → `pro_01m0njkv2n85sm0j5kxegen19t` / `pri_01m0njkw5yy2r9bz83wc2crr6w`. Product 31357 is the verified no-binding control. Evidence: `/home/server-manager/slt-evidence/SLT-WATCH-D00-EARLY-paddle-product-bindings.json`.
- This reveals a second assertion conflict inside tasks 5, 6, and 8: no checkout occurred, but publishing created Paddle sandbox catalogue objects, so the notes claiming no gateway operation are not literally correct.
- Registry containment is complete, but the out-of-phase mutation and invalid PASS evidence remain unresolved. Afternoon repair/revalidation is still required; no fixture was deleted, recreated, renamed, or duplicated.

[[2026-08-23]] Sun 03:03

## Closure-audit normalization

Blocked lifecycle/progress cards 5-8 and 12 now consistently supersede stale PASS headings/checklists and link this issue. Paddle catalogue operations are stated explicitly. Reserved guests A8/A11 now use `created_at_site=N/A` because no entity exists. Lifecycle start timestamps were reconciled to original activity events. No fixture or site state changed.

[[2026-08-23]] Sun 10:58
Repair plan: perform only a non-duplicating D00-afternoon revalidation. (1) Snapshot live registry TSV/page plus reserved-guest absence and non-SLT2 controls. (2) Recheck all nine existing customer records and billing fields through the browser and WP-CLI without saving. (3) Recheck the four existing products through their admin edit UI, persisted metadata, storefront/cart behavior, Shop Access exclusions, and zero task-attributable mail without publishing/saving. (4) Reconcile the three Paddle product/price bindings and the no-binding lifetime control into the signed TSV. (5) Capture fresh evidence, update the five lifecycle/progress cards, and close this issue only if every mandatory non-mutating assertion passes.

