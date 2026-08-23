---
id: 108
title: Expiring-soon at 7 days and Stripe card-expiring notification
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - stripe
    - day-08
due: "2026-08-31"
estimate: 1h30m
depends_on:
    - 4
    - 18
class: standard
---

> **SLT-EML-10** · group `emails` · scheduled **D08** (2026-08-31)

## Objective
Prove the natural expiring-soon scheduler/email and the Stripe card-expiring webhook/email are both wired, deduplicated and correctly linked, while ineligible cancelled/lifetime controls receive neither message.

## Scope
- Gateway: Stripe test for card-expiring; gateway-neutral finite subscription for expiring-soon
- Checkout: none
- Account: current-cycle registered targets
- Plugins: core-owned email/scheduler/gateway path

## Preconditions
- Resolve one finite active subscription whose end date puts the configured 7-day lead inside the window, one Stripe active subscription with a safe test payment method, and cancelled/lifetime negative controls.
- Reconfirm current email enablement, lead-day settings and exact target IDs; no previous action history is treated as the expected result.

## Steps
1. Snapshot target/control statuses, dates, payment-method safe summaries, dedupe metas, current exact actions and a Mailpit baseline.
2. Query the finite target's naturally scheduled expiring-soon action by exact subscription args. Require one pending row at `end_date−7d` plus deterministic offset according to the current contract; publish the gate and baseline deadline.
3. Inside five minutes before the gate, set `EXPIRING_PRE`. After natural WP Cron, require that exact action to complete and exactly one expiring-soon customer email with correct subscription/product/end date/timezone and working portal link.
4. Confirm the dedupe key/sent timestamp is written. Allow another natural scheduler scan and require no second action/message for the unchanged end-date/lead key.
5. Trigger the documented Stripe test `payment_method`/card-expiring event for the exact active Stripe fixture using provider-supported test tooling or an exact signed webhook payload. Never invent a production event or expose secrets.
6. Require one accepted/idempotent webhook/audit row, one card-expiring customer email with correct subscription and payment-method update link, and no order, charge, status or schedule mutation.
7. Replay the exact event once and require no duplicate mail/note/state change.
8. Inspect cancelled and lifetime controls for zero expiring-soon/card-expiring actions and messages; record exact counterexample queries.
9. Compare portal/admin link targets, save action/webhook/meta/mail evidence, close sessions, review, and mark done only when both positive paths and both negative controls pass.

## Expected results
1. One natural expiring-soon action and email fire at the configured seven-day lead; dedupe prevents a repeat.
2. One Stripe card-expiring event produces one linked email and usable update path without billing/state changes; replay is idempotent.
3. Cancelled and lifetime controls receive neither notification.

## Evidence / issue contract
- Capture queue/history, admin/portal screens, exact meta/webhook rows and complete Mailpit deltas.
- Missing natural scheduling, missing/duplicate mail, broken link or webhook/state mismatch creates/updates the mandatory `qa/issues/` kanban card with task/stage/plan, all IDs/user contexts/routes/gates, reproduction, expected/actual and proof. The card stays blocked until rerun passes.

## Isolation / teardown
- Only target-specific dedupe/audit state is expected. Do not drain hooks or alter non-SLT2 records; teardown removes current-cycle fixtures.

### Fresh-cycle validation contract

- Revalidate this build as fixed; no previous missing-scheduler finding is assumed.
- Stripe and Paddle only; use `agent-browser`, Mailpit and WP-CLI `--allow-root`; update both QA boards.
