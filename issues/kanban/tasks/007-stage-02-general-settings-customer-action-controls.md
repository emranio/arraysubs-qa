---
id: 7
title: 'stage-02: General Settings customer action controls missing'
status: closed
priority: high
created: 2026-05-20T01:08:39.497741094+02:00
updated: 2026-05-22T00:13:42.76999246+02:00
started: 2026-05-21T23:52:51.720958812+02:00
completed: 2026-05-22T00:13:42.769991498+02:00
tags:
    - qa
    - stage-02
    - settings
claimed_by: mold-glade
claimed_at: 2026-05-22T00:13:42.769992349+02:00
class: standard
---

Observed on live admin with Alumnium, 2026-05-20. Stage 02 task 01 expects Customer Actions controls: Allow Cancellation, Allow Suspension (Pause), Allow Reactivation, plus related info-box. General Settings page only renders Cancellation Settings with Cancel Immediately; requested controls are not present. Expected: documented Customer Actions section available and persistent. Actual: controls missing, cannot execute sub-task 1.6.

[[2026-05-21]] Thu 23:55
Verified against Stage 02 Task 01 and manual: General Settings must include Customer Actions with Allow Cancellation, Allow Suspension (Pause), Allow Reactivation, plus My Account info-box. Code currently has Cancellation Settings only; pause exists under pause_subscription, cancellation/reactivation are not settings-backed. Plan: add Customer Actions card to GeneralSettings, add customer_actions defaults/sanitization, map suspension to pause_subscription.customer_can_pause, enforce cancellation/reactivation toggles in portal helper/view/REST, rebuild, then verify save/reload in browser.

[[2026-05-21]] Thu 23:56
Implemented Customer Actions controls in General Settings. Added customer_actions defaults/sanitization for cancellation/reactivation, mapped Allow Suspension to pause_subscription.customer_can_pause, and wired customer cancel/reactivate portal checks to the new settings.

[[2026-05-22]] Fri 00:02
During browser verify, Alumnium clicked the switch accessibility checkbox but zero-sized hidden switch inputs did not toggle. Fixed shared FormBuilder switch input to use full-size invisible hit target over the slider.

[[2026-05-22]] Fri 00:13
Fixed: added General Settings Customer Actions card with Allow Cancellation, Allow Suspension (Pause), Allow Reactivation, plus portal info text. Added defaults/sanitization and enforced cancel/reactivate settings in customer portal helpers/REST/view. Browser QA: Customer Actions visible in live admin; saved false/true/false and confirmed wp option persisted; restored true/false/true and confirmed reload shows Allow Cancellation on, Allow Suspension off, Allow Reactivation on. Build passed. debug.log size 0.
