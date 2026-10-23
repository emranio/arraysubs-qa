---
id: 101
title: Elementor conversion draft shows stale custom-message switch state
status: closed
priority: low
created: 2026-10-24T03:46:30.037156+06:00
updated: 2026-10-24T03:52:57.267096+06:00
started: 2026-10-24T03:46:46.660261+06:00
completed: 2026-10-24T03:49:27.735315+06:00
tags:
    - bug
    - qa
    - elementor
class: standard
---

Active QA: task 2, scheduled 2026-10-24 (Asia/Dhaka); plan qa/restriction-legacy-compatibility-2026-10-24/plan.md.
Subscriptions and orders: N/A. User: administrator ID 1, admin, administrator role.
Fixture: disposable Elementor authoring page 20084, QA legacy author_elementor 20261024.
URL/context: http://localhost:10013/wp-admin/post.php?post=20084&action=elementor in isolated admin session legacy-elementor.
Steps: Open the original login-only restriction. Choose Convert to current rules while an implicit old custom message is stored.
Expected: The local draft keeps custom messaging enabled and the Use custom message switch visibly on.
Actual: The model becomes custom=yes and its rich-text editor is shown, but native switch still visually shows off until rerender. No save or runtime access corruption observed.
Proof: qa/restriction-legacy-compatibility-2026-10-24/evidence/elementor-converted-draft-before-refresh.json records customModel=yes, customChecked=false, editorVisible=true.
Scope: programmatic local conversion only; direct native switch clicks work.
Fix plan: Notify Elementor native controls of external settings changes after the history-tracked draft conversion, then repeat the real button interaction and verify toggle/model/editor agree.

[[2026-10-24]] Sat 03:49
Implemented: after the history-tracked draft conversion, invoke Elementor native setExternalChange so standard control views refresh their displayed values. Real fresh-session Convert to current rules now produces custom model=yes, checkbox checked=true, and editor visible=true. The conversion remains a local draft until Publish; savedRaw still version1 before that click. Publish/reload confirms version2, checked custom switch, retained rich HTML, empty dormant roles/products, login on, and legacy notice hidden. Evidence elementor-converted-draft.png/.json and elementor-converted-reloaded.png/.json in the active legacy QA evidence directory. Native browser errors log empty.

QA tracking correction: this editor-authoring defect belongs to active task3 (Legacy editor saves and conversion) in the same 2026-10-24 plan; the original task2 reference was a tracking typo.
