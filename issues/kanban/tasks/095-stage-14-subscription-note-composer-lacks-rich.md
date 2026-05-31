---
id: 95
title: 'stage-14: Subscription note composer lacks rich text bold control'
status: closed
priority: medium
created: 2026-05-23T11:44:36.813978868+02:00
updated: 2026-05-24T14:46:05.601206463+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
class: standard
---

Original task: stages/14-admin-subscriptions/05-subscription-notes.md\n\nQA plan expects a rich text editor with a Bold button and bold rendering for the words 'support ticket #5432'. Browser/accessibility tree for #683 showed only a plaintext multiline textbox labeled Add note, type selector, and Add button; no toolbar or Bold control. Added private note successfully, but support ticket text rendered plain with no bold markup.\n\nExpected: rich text editor supports bold formatting for manual notes.

[[2026-05-24]] Sun 14:35
Fix pass started 2026-05-24 by shell-quartz. Verifying issue, source QA task, and notes component/controller before code changes.

[[2026-05-24]] Sun 14:46
Fix 2026-05-24: Main admin now enqueues WordPress editor assets with wp_enqueue_editor(), and AddNoteForm uses a stable editor id so TinyMCE initializes reliably. Verified php -l src/Features/MainAdmin/Services/Hooks.php and npm run build. Alumnium confirmed the Subscription Notes add-note editor exposes a Bold toolbar button. Playwright set TinyMCE content to 'Updated invoice email per <strong>support ticket #5432</strong>.', POST /subscriptions/683/notes returned 201 with note_id=2893, and the top note rendered <strong>support ticket #5432</strong>. Screenshot: qa/artifacts/issue-95-rich-text-bold-note.png. Temporary QA note #2893 was deleted after proof to keep fixture #683 clean.
