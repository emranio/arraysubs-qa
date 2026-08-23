---
id: 12
title: 'stage-slt-d00: SLT-SETUP-03 Create the SLT2 account matrix (9 slt2-* users) and document the guest path'
status: blocked
priority: critical
created: 2026-08-22T20:43:39.734932665+02:00
updated: 2026-08-23T03:03:06.427062874+02:00
started: 2026-08-22T21:46:04.085653922+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d00
    - slt-setup-03
due: "2026-08-23"
estimate: 1h
depends_on:
    - 10
blocked: true
block_reason: 'Shared issue #2: lifecycle card blocked for early mutation and registry omission'
class: standard
---

Lifecycle task 12 / SLT-SETUP-03. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/012-slt-setup-03-create-the-slt-account-matrix-7-slt.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.

## Result — SUPERSEDED / BLOCKED (site date 2026-08-23)

Earlier browser evidence recorded customer IDs `474`–`482`, nine admin registration notices, and absent reserved guests A8/A11, but the PASS is superseded because mutation occurred before the assigned phase and authoritative TSV ownership was missing at closure. Shared issue #2 is linked; every mandatory assertion requires approved in-phase revalidation.

[[2026-08-23]] Sun 02:39

## D00 early-watcher correction — 2026-08-23

Lifecycle task 12 / SLT-SETUP-03 is blocked on shared issue #2. Users 474-482 were created roughly 13.5-14.5 hours before the assigned D00 afternoon phase; reserved guests A8/A11 remained absent. All were missing from the authoritative TSV at closure. The watcher backfilled exact identity/reservation rows for ownership safety, but no PASS is valid until the afternoon owner completes an approved non-duplicating revalidation.

[[2026-08-23]] Sun 03:03

## Closure-audit normalization

The stale PASS result text was superseded consistently with blocked lifecycle status and shared issue #2. No fresh PASS is asserted.
