---
id: 121
title: 'stage-17: Settings audit change details missing after settings save'
status: closed
priority: high
created: 2026-05-23T14:14:36.193209849+02:00
updated: 2026-05-24T19:10:29.879713733+02:00
started: 2026-05-24T18:56:34.424372737+02:00
completed: 2026-05-24T19:10:29.879712711+02:00
tags:
    - qa
    - stage-17
    - audits
    - settings
claimed_by: shell-quartz
claimed_at: 2026-05-24T19:10:29.879713623+02:00
class: standard
---

Original task: progress #148, Stage 17 Task 01 Activity Audits, sub-task 01.5.

Expected: after an admin settings change, Activity Audits shows an Admin / Settings row with a `changes ->` link; modal lists Previous Value and Changed Value for the modified setting.
Observed: admin browser REST save changed `emails.renewal_upcoming.days_before` 3→4, Activity Audits showed a new Admin / SETTINGS row, but no `changes ->` link appeared for the row. The row summary also reported many unrelated settings changes/deletions.
Technical evidence: note #1336 has `_audit_changes` meta, but `arraysubs_get_note_audit_changes(1336, ...)` returns zero changes because the stored JSON is invalid when previous values contain raw JSON/object text. The same save dropped/projection-changed multiple settings keys (`checkout_builder.*`, cancellation form config, plan_switching.auto_migrate_checkout, pending cancellation emails, etc.).
Impact: settings audit details are not inspectable; saving settings through this payload path can record broad unintended changes.

[[2026-05-24]] Sun 19:01
Plan: store audit changes with slashed JSON; trim sanitized defaults to incoming payload shape; deep-merge partial settings into existing option; verify fresh settings save shows changes modal and preserves unrelated settings.

[[2026-05-24]] Sun 19:10
Fixed. Settings save now trims sanitized defaults to incoming payload shape, deep-merges partial saves into stored settings, stores audit-change JSON with wp_slash(), and keeps Activity Audits changes links visible/clickable. Verified php -l for touched PHP files; WP-CLI REST partial save changed only emails.renewal_upcoming.days_before and preserved unrelated settings; new note #2966 decoded one audit change 11 -> 3. agent-browser normal click opened modal with Previous Value/Changed Value. Screenshot: qa/artifacts/issue-121/settings-audit-changes-modal.png.
