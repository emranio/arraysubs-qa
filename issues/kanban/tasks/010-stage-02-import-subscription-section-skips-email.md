---
id: 10
title: 'stage-02: Import Subscription section skips email/toolkit values'
status: closed
priority: high
created: 2026-05-20T01:08:39.56144116+02:00
updated: 2026-05-22T00:25:46.6871832+02:00
started: 2026-05-22T00:21:27.600968015+02:00
completed: 2026-05-22T00:25:46.687182348+02:00
tags:
    - qa
    - stage-02
    - import-export
claimed_by: mold-glade
claimed_at: 2026-05-22T00:25:46.68718311+02:00
class: standard
---

Observed via Easy Setup import/export flow plus WP-CLI endpoint check, 2026-05-20. Exported baseline, changed Days Active After Due, Renewal Reminder, Hide Admin Bar, then imported only Subscription & Others. Expected Stage 02 task 03.5: General and Toolkit values in arraysubs_settings revert to baseline. Actual: renewals reverted, but emails.renewal_upcoming.days_before stayed 15 and toolkit.hide_admin_bar stayed changed. Import result reported Emails and other sections skipped. Need docs/task expectation or import section mapping aligned.

[[2026-05-22]] Fri 00:23
Verified against Stage 02 Task 03.4/3.5 and manual. Subscription & Others must revert General Settings values and Toolkit values stored in arraysubs_settings. Current importer treats full emails as a separate section, so selecting only Subscription & Others skips emails.renewal_upcoming.days_before even though that field is part of General Settings. Plan: keep full Emails section behavior for email notification/template settings, but when importing subscription_settings also copy the General email schedule fields (renewal_upcoming.days_before, trial_ending.days_before, expiring_soon.days_before) from imported arraysubs_settings.emails into merged settings. Toolkit stays in subscription_settings through existing unclaimed-key path. Verify with browser REST import: baseline -> mutate renewals/emails/toolkit -> import subscription_settings only -> confirm all three revert and full Emails section still reports skipped.

[[2026-05-22]] Fri 00:25
Fixed: subscription_settings import now also restores General Settings email schedule fields stored under emails (renewal_upcoming.days_before, trial_ending.days_before, expiring_soon.days_before), while leaving the full Emails section separate/skipped unless selected. Syntax check passed. Browser REST QA: exported baseline, mutated days active 3->10, allow reactivation true->false, renewal reminder 3->15, toolkit hide admin bar true->false, imported only subscription_settings; result imported subscription_settings, skipped emails, warnings [], restored all four baseline values, pass true. debug.log size 0.
