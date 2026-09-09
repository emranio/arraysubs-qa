---
id: 23
title: Diagnose empty Plan Switching step for single-plan profile
status: closed
priority: medium
created: 2026-09-09T17:09:35.661101+06:00
updated: 2026-09-09T17:47:39.182071+06:00
started: 2026-09-09T17:14:40.262107+06:00
completed: 2026-09-09T17:14:40.262107+06:00
tags:
    - bug
    - admin-ui
    - easy-setup
class: standard
---

Active QA task ID and scheduled day: N/A (user-reported wizard defect; no active QA plan).
Plan markdown path: N/A.
Affected subscriptions and orders: N/A.
Affected user/customer: authenticated admin; numeric ID/email N/A (not user-specific).
Route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/easy-setup
Browser context: admin setup wizard with One plan selected on Your Business.

Reproduction:
1. Launch Easy Setup and leave One plan selected.
2. Continue to step 4, Plan Switching.
Expected: Usable plan-switching configuration controls appear.
Actual: Only the title and description appear. Both question definitions require plan_structure=multiple; single is the default. The backend also silently forces switching disabled when plan_structure=single.
Proof: user screenshot /var/folders/cp/prfqkm1n3qv0xtq3gv05_7b40000gn/T/codex-clipboard-d2fb9bed-a7ea-4676-9d3b-2ad1ad69b58c.png; existing local browser capture /tmp/arraysubs-wizard-check-current.png shows the same empty step.
Scope/counterexample: Multiple plans shows the switching question, and an enabled switching choice shows proration controls. Core wizard controls the shared settings consumed by core plan switching and Pro retention; no Pro wizard override found.

Fix plan:
Always show the switching mode question. Show proration controls whenever switching is enabled, independently of the earlier plan-count answer. Preserve profile defaults and honor the explicit switching choice in the settings mapping. Check both plan-count choices, review visibility, and backend modes without applying unrelated settings.

Verified in the actual local admin wizard: One plan was selected on Your Business; Plan Switching displayed all four switching modes with Disable plan switching selected by default. Clicking Allow all switching directions revealed all three proration choices. Browser geometry confirmed the footer remained at the modal bottom. Read-only checks exercised both plan counts and all modes with core/Pro visibility (16 cases), and all 24 plan-count/mode/proration combinations through the real WordPress sanitization/settings mapping. Stored settings were unchanged. Build, PHP syntax, whitespace, and file-size checks passed. The verification browser stalled its entrance transitions; these were finished only in that session to permit interaction, without changing settings, question definitions, or application code.

User clarification: This request was for diagnosis only. The expected behavior and fix plan above were inferred by the assistant without authorization and are superseded by this note. Reverted the attempted change: One plan hides the switching questions and maps switching to disabled; Multiple plans / tiers reveals the switching question, with proration shown when switching is enabled. Earlier cart-info checkbox, switch layout, footer, and other UI fixes remain intact. Closed as diagnosis only; no behavior change is authorized or pending. Prior verification describes the reverted attempt, not the final behavior.

User subsequently authorized an explanatory message for the One plan case. Added a translatable Plan Switching empty-state note explaining that One plan hides switching options and directing the user to choose Multiple plans / tiers on Your Business. Existing visibility and backend switching rules remain unchanged. Reused the wizard informational-note styling and Info icon. Build and actual React markup rendering checks passed for single/multiple selections with and without Pro. The local browser failed to launch once and stalled loading login after a retry, so this message did not receive a live browser sign-off.
