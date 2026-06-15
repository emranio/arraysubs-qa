---
id: 164
title: 'stage-20: Scheduled actions run during cron stoppage'
status: closed
priority: high
created: 2026-05-23T21:11:24.57877345+02:00
updated: 2026-05-24T23:45:55.380434452+02:00
started: 2026-05-24T23:37:37.107763307+02:00
completed: 2026-05-24T23:45:50.332196618+02:00
tags:
    - qa
    - stage-20
    - cron
    - action-scheduler
    - renewals
class: standard
---

Stage 20 Task 03. WP config already had DISABLE_WP_CRON=1 and the OS cron pinger /etc/cron.d/mirror-help-arrayhash-wordpress was commented/reloaded. Created overdue fixtures: Sub-A #1791 Active next=2026-05-23 18:39:17, Sub-B #1796 Trial trial_end=2026-05-23 18:59:19, with overdue Action Scheduler actions #1479/#1480/#1481 pending. Simply loading Sub-A admin detail while cron pinger was disabled caused Action Scheduler async runner to process the queue: #1479/#1480 completed, renewal invoice/order #1799 created, #1481 completed, #1796 converted Trial -> Active. Expected during cron stoppage: queue should not advance and subscriptions should remain visibly stuck until cron is re-enabled or actions are manually run. Impact: QA and operators cannot simulate/guarantee cron stoppage by disabling WP-Cron/server cron because web/admin traffic can still process due actions via Action Scheduler async runner.

[[2026-05-24]] Sun 23:45
Plan: block Action Scheduler's admin/web async queue runner when WP-Cron is intentionally disabled, so loading admin/customer pages cannot process due ArraySubs scheduled actions during a cron-stoppage test. Keep WP-Cron, WP-CLI, and manual Scheduled Actions runs available for catch-up.\n\nFix: core Boot now filters action_scheduler_allow_async_request_runner and returns false whenever DISABLE_WP_CRON is true. This prevents the shutdown-triggered async request runner from draining due actions from ordinary admin/web traffic while preserving explicit cron/manual runners.\n\nVerification: php -l passed; FPM reloaded. WP-CLI confirmed DISABLE_WP_CRON=true and apply_filters('action_scheduler_allow_async_request_runner', true) returns false. Temporarily commented /etc/cron.d/mirror-help-arrayhash-wordpress and reloaded cron, scheduled due probe action #2035, loaded the ArraySubs admin subscriptions page in agent-browser, and confirmed #2035 stayed pending with only 'action created' log entry. Deleted probe #2035, restored the cron file from backup, reloaded cron, and confirmed no arraysubs_qa_async_runner_probe actions remain.
