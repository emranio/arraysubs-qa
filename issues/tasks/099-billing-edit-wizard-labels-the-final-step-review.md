---
id: 99
title: Billing edit wizard labels the final step Review & create
status: closed
priority: low
created: 2026-10-24T03:41:36.969746+06:00
updated: 2026-10-24T03:46:46.029239+06:00
started: 2026-10-24T03:44:51.575912+06:00
completed: 2026-10-24T03:46:46.029238+06:00
tags:
    - bug
    - subscription-plans
    - admin-ui
class: standard
---

Active QA task ID and scheduled day: Subscription Plans progress task #5, 2026-10-24 Asia/Dhaka.
Plan markdown path: qa/subscription-plans-2026-10-24/plan.md.
Affected subscription IDs and order IDs: N/A. Affected product ID: 20072 (dedicated Plans QA 2026-10-24 Box draft). No customer subscriptions were changed.
Affected WordPress user/customer IDs, login/email and roles: WordPress ID1, login admin, administrator; email N/A. No customer involved.
Exact URL/admin route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/plans.
Browser/user context: authenticated local administrator; isolated agent-browser plans-list-final session, desktop 1280 x 577 content viewport; current freshly reloaded feature build.
Reproduction steps: Open Edit subscription billings for box20072. Keep existing product details. Configure the existing box and save the nested configuration. Continue through Recurring billing and Other settings to the last wizard step.
Expected result: The existing-plan edit flow labels the last step Review & save (or equivalent billing-edit wording).
Actual result: The modal title is Edit subscription billings and the action is Save billings, but the final heading remains Review & create.
Concrete proof: Browser accessibility snapshot shows heading Review & create and button Save billings in the same active modal. Screenshot qa/subscription-plans-2026-10-24/evidence/compound-03-edit-review-create-label.jpg.
Known scope notes/counterexamples: Static wording mismatch only; this is the shared creation wizard reused in edit mode. The actual final action is correctly Save billings. Creation-mode Review & create wording remains appropriate. Save behavior is being verified separately in the same QA task.
Repro video: N/A (static wording defect visible on the final step).

Fix plan: retain Review & create and publishing guidance for creation, pass edit context to shared ProductReview, use Review & save and saving guidance in billing-edit mode, then rebuild and reopen a real edited plan to verify both labels before closing.

Fixed shared edit context: billing wizard final heading now Review & save, with guidance Check these details before saving the plan billing settings. Creation keeps its existing publishing copy. Rebuilt successfully and verified real browser admin edit of20077 at final review; evidence/05-review-save-fixed.jpg and DOM heading/guidance assertion passed.
