---
id: 46
title: 'stage-07: Skip and pause controls missing from active subscription detail'
status: closed
priority: high
created: 2026-05-20T14:41:08.326195708+02:00
updated: 2026-05-22T03:03:45.80505719+02:00
started: 2026-05-22T03:00:34.626755011+02:00
completed: 2026-05-22T03:03:45.805056258+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - skip-pause
claimed_by: mold-glade
claimed_at: 2026-05-22T03:03:45.80505707+02:00
class: standard
---

Stage 07 Task 02.5 expects active Subscription A detail to show Manage Your Subscription with Skip Next Renewal and Pause Subscription controls when Skip/Pause settings are enabled. On cust1 subscription #633, the detail page shows overview, Change Plan, Cancel Subscription, Related Orders, and Notes, but no Manage Your Subscription section, no Skip Next Renewal button, and no Pause Subscription button. Expected: both controls visible for eligible active subscriptions.

[[2026-05-22]] Fri 03:02
Plan: verify Stage 07 Task 02 expectation, inspect portal template and Skip/Pause managers, then decide code vs fixture. Findings: template correctly renders Manage Your Subscription when skip/pause settings and eligibility allow it. Current browser now shows Manage Your Subscription + Skip Next Renewal for #633. Pause is missing because fixture/settings are not in expected state: pause_subscription.enabled=true but pause_subscription.customer_can_pause=false, and #633 has previous pause cooldown meta (_last_pause_date from earlier QA), making PauseManager::canPause return 'Cannot pause again for 30 more day(s).' Fix fixture/settings to match task precondition: enable customer pause, clear prior pause cooldown/meta on #633, ensure active status, verify both buttons in browser.

[[2026-05-22]] Fri 03:03
Fixed as QA fixture/settings issue. No code change required. Enabled pause_subscription.customer_can_pause, reset prior pause cooldown/meta on Subscription #633, reset skip state, and kept #633 Active. WP-CLI confirms SkipManager::canSkip(#633)=true and PauseManager::canPause(#633)=true. Browser QA as cust1@test.local on /my-account/view-subscription=633 now shows Manage Your Subscription, Skip Next Renewal button, Vacation Mode, and Pause Subscription button, with no active Skipping or Subscription Paused indicator.
