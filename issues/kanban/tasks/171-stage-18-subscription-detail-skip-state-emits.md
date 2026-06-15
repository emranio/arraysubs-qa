---
id: 171
title: 'stage-18: Subscription detail skip state emits undefined-key warnings'
status: closed
priority: medium
created: 2026-06-03T13:34:36.827554384+02:00
updated: 2026-06-03T17:01:26.595562612+02:00
started: 2026-06-03T16:55:20.425574589+02:00
completed: 2026-06-03T17:01:26.59556185+02:00
tags:
    - qa
    - stage-18
    - skip-renewal
    - debug-log
class: standard
---

QA progress task: #183 stage-18: Renewal Sync first full renewal
QA plan: qa/stages/18-renewal-followup/13-renewal-sync-first-full-renewal.md
Affected subscription ID(s) and order ID(s): subscription #4242, renewal order #4381 context; warning itself may affect any subscription detail with missing skip meta
Affected WordPress user/customer: admin browser context; customer ID 318, login sync-full, email sync-full@example.test, role customer for inspected subscription
Exact test URL/admin route: https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/4242
Browser/user context: agent-browser headless Chrome, admin user
Reproduction steps: Open subscription detail #4242 after renewal follow-up QA; tail wp-content/debug.log.
Expected result: No PHP warnings are emitted while rendering/subscription-detail REST data loads.
Actual result: debug.log contains warnings at 2026-06-03 11:29:04 UTC from arraysubs/src/Features/SkipRenewal/REST/SkipController.php lines 384-387 for undefined keys skip_remaining, skip_started_date, original_next_payment_date, skip_reason.
Concrete proof: tail of wp-content/debug.log shows the four Undefined array key warnings from SkipController.php lines 384-387.
Known scope notes/counterexamples: Renewal Sync checkout and renewal behavior passed; this appears unrelated to renewal-sync pricing/scheduling and is not a blocker for the new feature.



Fix plan: normalize SkipManager status fields in SkipController::getSkipInfo(), persist optional skip reason submitted by the admin UI, register _skip_reason meta, and clear it with skip state.

Fix applied: SkipController now maps cycles_remaining/requested_at/original_date/reason to the frontend skip_remaining/skip_started_date/original_next_payment_date/skip_reason contract with safe defaults; skipCycles sanitizes and passes reason. SkipManager now stores _skip_reason, includes it in status/history/action context, and clears it when skip state is cleared. SubscriptionCPT registers _skip_reason for REST/meta consistency.

Verification: php -l passed for SkipController.php, SkipManager.php, SubscriptionCPT.php, and skip-pause-helpers.php. Direct REST call as admin for subscription #4242 returned code 200 with skip_remaining=0, empty skip_started_date/original_next_payment_date/skip_reason, can_skip=false, and no warning. agent-browser reopened the exact admin route from the issue; /arraysubs/v1/subscriptions/4242/skip returned the normalized payload and debug.log line count stayed unchanged at 365. Screenshot: /tmp/arraysubs-issue-171-subscription-detail.png.
