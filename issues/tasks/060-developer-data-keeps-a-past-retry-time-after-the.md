---
id: 60
title: Developer data keeps a past retry time after the retry ladder is exhausted
status: closed
priority: low
created: 2026-10-19T11:49:45.244172104+02:00
updated: 2026-10-20T00:35:28.107512856+02:00
started: 2026-10-19T20:24:21.28403296+02:00
completed: 2026-10-20T00:35:28.107489462+02:00
tags:
    - developer-api
    - retry
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: S04-A (board task #10 in qa/stripe-dev-support-2026-10-18/kanban), phase 2, 2026-10-19.
Plan markdown path: qa/stripe-dev-support-2026-10-18/plan.md.
Affected subscription IDs and order IDs: subscription 68512, failed renewal order 68550; retry actions 100973, 100975, 100976 (run explicitly 09:43:57Z-09:44:32Z).
Affected WordPress user/customer: ID 583, sqa-p2-ladder / sqa-p2-ladder@example.com, role customer.
Exact route: WP-CLI `\ArraySubs\Developer\Subscriptions::fromId(68512)->data()` (scripts/dev-snapshot.php) on https://mirror-help.arrayhash.com. Settings during the run: payment_retry enabled, [24,24,24], stop_on_hard_decline false (restored afterwards).
Reproduction: let a Stripe renewal fail, run all three scheduled retries; read developer data.
Expected: after "Reached the configured retry limit (3)" no retry is pending, so recovery.next_retry_at is null.
Actual: recovery.retry_attempts 3 and recovery.next_retry_at = 2026-10-20 09:44:20Z (the time of the retry that already ran); _payment_retry_next_attempt_at keeps that value; no arraysubs_process_renewal action is pending.
Proof: snapshots/sub-68512-s04-after-retry3.json.
Scope notes: retry counts, ladder timing and the exhaustion note are correct; only the reported next-retry time is stale. Integrations reading developer snapshots would show a retry that will never happen.
Repro video: N/A.

Deep re-verification 2026-10-19: CONFIRMED WITH CORRECTIONS (low, high confidence). Re-reproduced on fresh 68881. Wider than stated: also shown in Pro Audits > Renewal Failures ('Next: ...'), core REST /subscriptions/{id}/detail; also left by the retries-disabled and hard-decline exits, by overdue cancellation (live: 68525 still reports 2026-10-20 09:43:29 after cancellation) and by unschedule paths. Inverse case: #64 deletes the meta while a retry is queued. Fix by deriving next_retry_at from a pending action. Full report: qa/stripe-issues-56-61-deep-verification-2026-10-19.md

Closed 2026-10-19 (UTC) after fix + live verification. Changes are in the working trees and NOT committed (user request). Full record: qa/stripe-issues-56-64-fix-log.md.

Fix 2026-10-19 (uncommitted; log qa/stripe-issues-56-64-fix-log.md): new `arraysubs_get_payment_retry_next_attempt_at()` reports a time only while a retry `arraysubs_process_renewal` action is queued (retry counter > 0 and an open failure); `_payment_retry_next_attempt_at` is deleted at every exit that schedules nothing and on overdue cancellation. Developer data, the subscription REST detail, the grace policy, the respreader and Pro Renewal Failures use the helper. Files: core `functions/gateway-helpers.php`, `RenewalProcessor.php`, `GracePolicy.php`, `RenewalRespreader.php`, `SubscriptionController.php`; Pro `Audits/REST/RenewalFailureController.php`.
Verified live 2026-10-19: T2 (69202 `recovery.next_retry_at` = queued action 101503); T8 (Renewal Failures: "Next" only for 69202, none for exhausted 68512); probe: 68512 (exhausted) and 68525 (cancelled) report null.
Review round 2: with a schedule that fills Days Active After Due (default 24, 24, 24 / 3 days), the hold now waits for the last retry instead of removing it (GracePolicy; probe evidence fix-r2-probes-before-live.json).
Review round 3: the next-retry time reads the pending action (not a running one), and the hold waits for a queued retry by its place in the configured schedule, however late the queue runs.
