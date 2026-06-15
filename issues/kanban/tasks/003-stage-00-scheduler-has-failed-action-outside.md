---
id: 3
title: 'Stage 00: Scheduler has failed action outside ArraySubs'
status: closed
priority: medium
created: 2026-05-19T22:38:45.105839643+02:00
updated: 2026-05-22T04:09:00.725264722+02:00
started: 2026-05-22T04:06:22.320063624+02:00
completed: 2026-05-22T04:09:00.72526358+02:00
tags:
    - qa
    - env
    - stage-00
claimed_by: mold-glade
claimed_at: 2026-05-22T04:09:00.725264612+02:00
class: standard
---

Found in qa/stages/00-preflight/03-cron-and-action-scheduler.md, Sub-Task 3.5. Global Action Scheduler status shows 1 failed action (`woocommerce_admin/stored_state_setup_for_products/async/run_remote_notifications`). ArraySubs hooks show no failed actions.

[[2026-05-22]] Fri 04:07
Plan: confirm failed queue rows are outside ArraySubs, inspect logs/details, delete stale no-callback failed actions from Action Scheduler queue, then verify Failed tab/CLI show zero failed actions and ArraySubs search remains zero. No ArraySubs code change expected because hooks are WooCommerce Admin / Action Scheduler migration rows.

[[2026-05-22]] Fri 04:08
Fixed: stale failed Action Scheduler rows #81 (WooCommerce Admin remote notifications) and #855 (Action Scheduler migration hook) had no registered callbacks and were outside ArraySubs. Deleted both failed rows. Verified WP-CLI global failed list empty, ArraySubs failed search empty, Action Scheduler status has no failed count, and agent-browser Scheduled Actions failed view says No items found.
