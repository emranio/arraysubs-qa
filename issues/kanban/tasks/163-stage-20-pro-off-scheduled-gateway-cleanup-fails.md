---
id: 163
title: 'stage-20: Pro-off scheduled gateway cleanup fails with no callback'
status: closed
priority: high
created: 2026-05-23T20:54:11.769159104+02:00
updated: 2026-05-24T23:37:22.358516488+02:00
started: 2026-05-24T23:28:22.215904602+02:00
completed: 2026-05-24T23:37:15.991708573+02:00
tags:
    - qa
    - stage-20
    - pro-deactivation
    - action-scheduler
    - gateway
class: standard
---

Stage 20 Task 01. While ArraySubsPro was deactivated, existing pending action #1039 arraysubs_cleanup_webhook_events ran via WP Cron and failed: 'Scheduled action for arraysubs_cleanup_webhook_events will not be executed as no callbacks are registered.' Expected deactivation/reactivation to leave no failed/orphaned scheduled actions; Pro-owned scheduled jobs should be unscheduled, guarded, or registered safely while Pro is inactive.

[[2026-05-23]] Sat 22:13
Stage 20.06 H3 recheck: failed arraysubs action still present: #1039 arraysubs_cleanup_webhook_events failed scheduled_date_gmt=2026-05-23 18:51:05. This predates smoke run start and remains covered by this issue.

[[2026-05-24]] Sun 23:36
Plan: remove Pro-owned gateway maintenance jobs during ArraySubsPro deactivation so Action Scheduler cannot later run them while no Pro callbacks are registered. Also clean existing failed Pro gateway maintenance rows that were already orphaned.\n\nFix: arraysubspro.php deactivation now unschedules arraysubs_cleanup_webhook_events and arraysubs_gateway_reconcile from the arraysubs-gateway group. It uses the core ActionScheduler wrapper when available, falls back to Action Scheduler functions otherwise, and deletes failed rows for those Pro gateway maintenance hooks as best-effort cleanup. Core billing/status/email actions are untouched.\n\nVerification: php -l passed. Before test: cleanup hook had pending action #1957 and failed orphan #1039. Real wp plugin deactivate arraysubspro removed pending cleanup/gateway actions and deleted failed #1039. Reactivated Pro; ArraySubsPro active again, one healthy pending cleanup action #2033 scheduled for 2026-05-25 02:00 UTC, failed cleanup hook list empty, gateway reconcile pending list empty. Browser/Playwright Scheduled Actions failed filter for arraysubs_cleanup_webhook_events shows no actions found. Screenshot: qa/artifacts/issue-163/action-scheduler-failed-cleanup-filter-empty.png.
