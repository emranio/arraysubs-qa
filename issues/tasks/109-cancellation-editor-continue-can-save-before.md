---
id: 109
title: Cancellation editor Continue can save before review
status: closed
priority: high
created: 2026-10-24T12:56:28.963293+06:00
updated: 2026-10-24T13:12:36.227716+06:00
started: 2026-10-24T12:56:28.976369+06:00
completed: 2026-10-24T13:12:36.227716+06:00
tags:
    - bug
    - cancellation-flow
class: standard
---

Active QA task ID and scheduled day: N/A (feature validation on 2026-10-24 Asia/Dhaka). Plan: qa/cancellation-flow-wizard-2026-10-24/plan.md.
Affected subscriptions and orders: N/A; admin settings only.
Affected WordPress user: administrator ID 1, login admin. Disposable customer ID 460, cancellation-wizard-qa-20261024@example.invalid, role customer, observed the premature reason update.
Route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/cancellation-flow. Isolated cancellation-admin browser.
Reproduction: Open editor; change a reason and discount; continue through Contact Support Offer; click Continue to reach Overview and Save.
Expected: Review opens with unsaved changes and no settings/save request until Save is activated.
Actual: Continue was reconciled into a submit button during the same click; a save ran automatically and closed the modal. The changed reason then appeared in customer My Account before the explicit Save step.
Proof: qa/cancellation-flow-wizard-2026-10-24/evidence/admin-checks.json initial run stopped at reason removal because review Edit opened a fresh editor after the premature save; real customer reason options included the temporary QA reason.
Scope: Only the final Continue-to-Save transition; earlier steps retained changes.
Fix plan: Use separate keyed Continue and Save controls; make Save explicitly call form.submit from a button; prevent the Continue default action. Rerun full browser checks proving review opens without persistence, then explicit save/reload and customer behavior.

Verified fixed: final review remains unsaved until explicit Save, saved values survive reload, retry retains edits after an intercepted failed response, and real My Account receives the saved values. Full results: qa/cancellation-flow-wizard-2026-10-24/report.md; 49 checks passed and production build passed. Original settings restored and disposable fixtures removed.
