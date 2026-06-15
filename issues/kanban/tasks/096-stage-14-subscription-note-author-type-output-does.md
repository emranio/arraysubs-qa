---
id: 96
title: 'stage-14: Subscription note author/type output does not match plan'
status: closed
priority: medium
created: 2026-05-23T11:44:36.83162201+02:00
updated: 2026-05-24T15:02:20.818814404+02:00
started: 2026-05-24T15:02:20.818813502+02:00
completed: 2026-05-24T15:02:20.818813502+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
claimed_by: shell-quartz
claimed_at: 2026-05-24T15:02:20.818814304+02:00
class: standard
---

Original task: stages/14-admin-subscriptions/05-subscription-notes.md\n\nManual notes added on #683 show author text as 'Adminadmin' instead of expected 'Admin'. Triggering an admin edit inserted a new note at the top, but it is also authored 'Adminadmin' with Private type; QA plan expects the admin-edit/system event to be System-badged and manual notes to show Admin badge cleanly.\n\nObserved order after edit: admin edit note, customer note, system email notes. Deletion confirmation worked after targeting the modal Delete button; no Edit action exists for system notes.

[[2026-05-24]] Sun 14:46
Fix pass started 2026-05-24 by shell-quartz. Verifying issue, source QA task, and note author/type formatting before code changes.

[[2026-05-24]] Sun 14:49
Plan: make detail-update audit notes system-authored/private; keep manual admin notes showing only Admin badge; then build + browser verify notes.

[[2026-05-24]] Sun 15:02
Fixed and verified. Code: manual admin notes now suppress duplicate admin display name; detail-update audit notes are system-authored private notes with event_type subscription_details_updated. Checks: php -l SubscriptionController passed; npm run build passed. Browser: agent-browser confirmed notes panel; agent-browser vision failed due base64 transport, so agent-browser screenshot/assertions used. Proof: qa/artifacts/issue-96-admin-author-clean.png shows Admin + Private without Adminadmin; qa/artifacts/issue-96-system-edit-note.png shows admin edit note as System + Private. Temp notes #2896/#2897/#2898 deleted; billing city restored to San Antonio.
