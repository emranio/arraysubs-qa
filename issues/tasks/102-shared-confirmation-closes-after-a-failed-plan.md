---
id: 102
title: Shared confirmation closes after a failed plan save
status: closed
priority: medium
created: 2026-10-24T03:47:57.387821+06:00
updated: 2026-10-24T03:53:12.080213+06:00
started: 2026-10-24T03:48:30.175751+06:00
completed: 2026-10-24T03:53:12.080213+06:00
tags:
    - bug
    - subscription-plans
    - confirm-dialog
class: standard
---

Active QA task ID and scheduled day: Subscription Plans progress task #7, 2026-10-24 Asia/Dhaka.
Plan markdown path: qa/subscription-plans-2026-10-24/plan.md.
Affected subscription/order IDs: N/A. Product: dedicated fixture20077.
User/customer: ID1 admin, administrator; customer N/A.
Exact route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/plans?search=Plans%20QA%202026-10-24%20Simple.
Browser context: authenticated administrator, agent-browser plans-primary, desktop1280x577.
Reproduction: Open billing editor, change price36 to40; change product metadata in another context; continue to review and Save billings; confirm.
Expected:409 stale-version message, entered values retained, shared confirmation remains open with retry/cancel and visible error, as required by AGENTS.
Actual:409 correctly prevents the write and retains values, but shared useConfirm closes its confirmation after rejection. Both core and Pro helpers set isOpen:false in catch despite the documented contract.
Proof: evidence/06-stale-save-retained.jpg; DOM alert This product changed after you opened it; product regular price remains36. Source arraysubs/src/resources/libs/confirm-dialog/index.jsx and matching Pro helper.
Scope: helper error handling only; successful saves and ordinary cancel flows work. Existing subscriptions unaffected.
Fix plan: keep confirmation open after failed async action; show escaped inline error on every failure, including retries after the original promise rejection; retain caller toast/catch semantics, clear error on retry/new confirmation, close on success/cancel. Align core and Pro. Add focused retry/cancel tests and repeat real browser stale-save failure.

Aligned core/Pro helpers: failed async confirmations remain open and show an escaped inline error on every attempt; clear error on retry/new dialog; successful retry/cancel still close. Four React tests pass for both implementations, covering two failures then success and cancel/new-dialog error reset. Built both plugins. Real stale409 browser test repeated twice with confirmation remaining open and price37 retained; screenshot evidence/07-stale-retry-stays-open.jpg. Closed via Keep reviewing, then discarded/reopened and saved successfully.
