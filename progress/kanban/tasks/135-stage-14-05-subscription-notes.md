---
id: 135
title: 'stage-14: 05 Subscription Notes'
status: closed
priority: medium
created: 2026-05-19T22:56:17.993378016+02:00
updated: 2026-05-24T15:02:32.51408916+02:00
started: 2026-05-23T08:06:53.44748746+02:00
completed: 2026-05-23T11:44:54.346323129+02:00
tags:
    - qa
    - stage-14
class: standard
---

Source: stages/14-admin-subscriptions/05-subscription-notes.md

[[2026-05-23]] Sat 11:44
QA notes: Used subscription #683. Existing system notes present: Subscription created, status changed from new to Active, renewal invoice #1034 generated, plus email/system timeline entries; notes sorted newest first. Added private note 'Updated invoice email per support ticket #5432.'; appeared at top as Adminadmin/Private but no rich-text Bold toolbar or bold rendering (issue #95). Added customer note 'Thanks for renewing - we've shipped your kit today.'; became top note as Adminadmin/Customer; private note moved below. Triggered admin edit by changing billing city Austin -> San Antonio; new note inserted top with field-level changes, customer note shifted to #2; author/type mismatch logged #96. Deleted the private support-ticket note through Delete Note modal; note disappeared, no undo observed, customer note and system/admin-edit note remained. System notes expose Delete only, no Edit action. Customer portal optional visibility check not run.

[[2026-05-24]] Sun 14:46
Fix verification 2026-05-24 for issue #95: Subscription Notes editor now loads TinyMCE with Bold toolbar. Browser proof on subscription #683: editor_probe content contained <strong>support ticket #5432</strong>, POST returned 201, and rendered first note preserved the strong tag. Screenshot: qa/artifacts/issue-95-rich-text-bold-note.png. Temporary note #2893 removed after verification. Checks: php -l MainAdmin Hooks passed; npm run build passed.

[[2026-05-24]] Sun 15:02
Fix verification 2026-05-24 for issue #96: manual Admin notes now show Admin badge once, no duplicated author name; admin detail-change audit notes are System-badged private notes. Browser proof on subscription #683: Playwright created temp manual note #2898 and saw meta 'Admin ... Private'; temp edit generated note #2897 with meta 'System ... Private' and Billing City change content. Screenshots: qa/artifacts/issue-96-admin-author-clean.png and qa/artifacts/issue-96-system-edit-note.png. Cleanup done: temp notes #2896/#2897/#2898 deleted and billing city restored to San Antonio. Checks: php -l SubscriptionController passed; npm run build passed.
