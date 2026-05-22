---
id: 47
title: 'stage-07: Customer portal immediate cancellation fails after valid reason'
status: closed
priority: critical
created: 2026-05-20T14:43:46.84412501+02:00
updated: 2026-05-21T21:38:22.031022065+02:00
started: 2026-05-21T21:23:38.208422031+02:00
completed: 2026-05-21T21:38:22.031020813+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - cancellation
claimed_by: mold-glade
claimed_at: 2026-05-21T21:38:22.031021955+02:00
class: standard
---

Stage 07 Task 03 on cust1 active subscription #678. Settings confirmed: cancellation.cancel_immediately=true, require_reason=true, retention_offers_enabled=false. Browser flow: opened Subscription #678, clicked Cancel Subscription. Modal opened with immediate-cancel warning, Keep Subscription, Continue, and required reason selector. Clicking Continue without reason shows validation alert "Please select a reason for cancelling." Selecting "Found a better alternative" removes validation, but clicking Continue shows assertive alert "Failed to cancel subscription. Please try again". Subscription remains arraysubs-active in WP-CLI; no cancellation meta/end date written. Expected: success toast "Subscription cancelled successfully.", status Cancelled, end date set, future renewals unscheduled.

[[2026-05-21]] Thu 21:26
Plan: reproduce REST/browser cancellation on subscription #678 with Stage 07 Task 03 settings (cancel immediately on, require reason on, retention offers off); inspect canonical cancellation helper + Customer Portal REST; patch core helper/REST so immediate customer cancellation writes cancelled state, end date, reason details, unschedules renewal jobs, and returns success only on real failure; verify in browser, then close or block.

[[2026-05-21]] Thu 21:38
Fix: immediate cancellation now writes _end_date at cancellation time, uses wp_update_post(..., true) failure detection, and customer cancellation responses/localized messages match Stage 07 exact copy with punctuation. Frontend now shows REST response message on success. Built customer portal assets. Verification: browser as cust1 on #678 with Stage 07 Task 03 settings, reason validation appeared, selected Found a better alternative, Continue completed, detail reloaded as Cancelled with End Date, cancellation note reason, and no pending renewal/invoice actions. Used temporary Alumnium XHR repair for known tool XMLHttpRequest Illegal invocation. Restored #678 fixture to Active with next payment 2026-05-27 12:32:09 and renewal actions rescheduled.
