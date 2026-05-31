---
id: 59
title: 'stage-08: Retention reasons default list missing temporary_pause'
status: closed
priority: high
created: 2026-05-20T15:07:47.421529166+02:00
updated: 2026-05-22T03:40:08.148018157+02:00
started: 2026-05-22T03:34:46.545785653+02:00
completed: 2026-05-22T03:40:08.148016984+02:00
tags:
    - qa
    - stage-08
    - retention
    - settings
claimed_by: mold-glade
claimed_at: 2026-05-22T03:40:08.148018066+02:00
class: standard
---

Task 01 expected seven default cancellation reasons including temporary_pause / Just need a temporary break. Actual Retention Flow page and arraysubs_settings.cancellation.reasons show only six defaults: too_expensive, not_using, found_alternative, missing_features, technical_issues, other. temporary_pause is absent before custom edits.

[[2026-05-22]] Fri 03:36
Plan: verify Stage 08 Task 01 and current arraysubs_settings.cancellation.reasons; add backend normalization for legacy six default reason rows so temporary_pause is inserted before other only when the saved list is still the old uncustomized default; update SettingsController fallback to include temporary_pause; persist the normalized current option; browser-verify Retention Flow shows temporary_pause / Just need a temporary break.

[[2026-05-22]] Fri 03:40
Fix: added backend normalization for legacy six-row cancellation default lists so temporary_pause / Just need a temporary break is inserted before other only when the saved list is still the old untouched defaults. SettingsController fallback now includes temporary_pause too. Persisted current arraysubs_settings.cancellation.reasons to seven rows. Verification: raw option and arraysubs_get_setting show too_expensive, not_using, found_alternative, missing_features, technical_issues, temporary_pause, other; Retention Flow browser page shows Reason 6 - temporary_pause and seven reason rows.
