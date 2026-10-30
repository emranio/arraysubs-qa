---
id: 127
title: Escape freezes cancellation editor before discard confirmation
status: closed
priority: high
created: 2026-10-28T02:05:54.743233+06:00
updated: 2026-10-31T02:20:58.244132+06:00
started: 2026-10-28T02:06:00.769172+06:00
completed: 2026-10-28T02:08:52.417473+06:00
tags:
    - bug
    - admin-ui
    - cancellation-flow
class: standard
---

Active QA task ID and scheduled day: N/A (user-requested cancellation editor verification, 2026-10-28 Asia/Dhaka). Plan markdown path: N/A; no active QA cycle.
Affected subscription IDs and order IDs: N/A.
Affected WordPress user/customer: administrator login admin, role administrator; numeric ID/email N/A. No customer involved.
Exact route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/cancellation-flow. Browser contexts: isolated cancellation-close-admin and cancellation-close-check Chromium sessions.
Reproduction: Open Edit cancellation flow; change Require Cancellation Reason or expand the first reason and edit its label; press Escape; request a new snapshot or interact with the page.
Expected: The shared Discard your changes confirmation opens; Keep editing preserves values, and Discard changes restores saved values and closes the editor.
Actual: Browser renderer remains busy and subsequent snapshot/evaluation/close requests do not complete. Reproduced in two isolated sessions.
Proof: Before Escape, the footer reads Unsaved changes. The renderer consumes sustained CPU after Escape; CLI snapshot and evaluation requests remain pending. Mouse X and footer Close successfully open the existing confirmation, shown in /tmp/cancellation-close-existing-confirm.png. Clean-editor Escape closes normally. No settings were saved.
Scope notes: The confirmation is already implemented in FlowWizard. Its mouse paths work; the shared modal Escape event handling is under investigation. Pro maintains a parallel modal implementation.
Fix plan: Consume the topmost modal Escape event before invoking onClose, preventing the same dismissal event from entering dialogs opened by that callback; align the core and Pro modal implementations. Rebuild affected assets, then verify Escape, X, footer Close, Keep editing, confirmed discard and clean-editor closure through the real browser. Preserve existing QA issues and settings.

[[2026-10-28]] Wed 02:08
Implementation: Core and Pro topmost modal keydown handlers now prevent the Escape default and stop immediate propagation before onClose, then return. Existing cancellation confirmation and saved-state restoration are reused.
Verification: Both production builds passed. Real browser cancellation-close-fixed confirms edited-switch Escape opens the confirmation without freezing; Keep editing preserves edits; repeated Escape reopens the confirmation; Escape on the confirmation dismisses only that dialog; footer Close opens the confirmation; confirmed discard closes both dialogs. Reopening restores Require Cancellation Reason=true and All changes saved. Editing the first reason label and pressing Escape also opens the confirmation; discarding and reopening restores Too expensive and removes Temporary unsaved reason. Clean-editor X closes directly. No settings were saved.
Automated regression: arraysubs/src/resources/libs/modal/__tests__/escape.test.jsx tests actual core and Pro modal components. Escape opens a child confirmation, another Escape closes only the child, and the parent can open another confirmation. Both tests passed (1.206 s).
Saved evidence: qa/issues/evidence/cancellation-unsaved-confirmation/mouse-confirmation-before.png; escape-confirmation-after.png; reason-edit-confirmation-after.png. Logs: /tmp/arraysubs-cancellation-close-build.log, /tmp/arraysubspro-cancellation-close-build.log, /tmp/arraysubs-modal-escape-tests.log. Source sizes and diff whitespace passed. Correct existing changelog drafts updated.


Release QA2026-10-31 additional browser evidence (prior status preserved):
Suspected deployed recurrence of issue127: Cancellation wizard Escape freezes renderer

- Active QA task: #2/#4; scheduled day2026-10-31 Asia/Dhaka; plan qa/release-2026-10-31-0208/plan.md.
- Subscriptions/orders: N/A, admin configuration only. Customer: N/A. WordPress administrator admin, user1.
- URL: https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/cancellation-flow
- Browser: rqa0208-premium-admin, desktop1280x720, baseline profileA with editor-only unsaved toggles.
- Steps: Open Edit cancellation flow; Continue to Retention Offers; turn retention on in local draft; reveal/enable each offer while inspecting; invalid discount101 rejected, restore20 and advance to Contact Support; Escape opens shared Discard your changes modal; choose Keep editing; press Escape again. No Save click.
- Expected: Keep editing preserves draft, repeated Escape opens shared discard prompt; confirmed discard closes and resets draft.
- Actual: Initial shared prompt renders; after Keep editing and immediate repeated Escape, browser snapshot waits indefinitely/renderer unresponsive. CLI doctor reports Chrome+daemon healthy. Pending wait/snapshot processes interrupted after budget bound; no further repro.
- Proof: evidence/premium-cancel-shared-confirm.png (initial prompt), premium-cancel-contact.png (unsaved draft); browser tool pending process observations in chat. No console error before freeze; cannot query frozen renderer afterwards.
- Counterexample/scope: first Escape did open prompt. New isolated recovery session loaded saved overview with retention disabled and all4offers disabled, proving no draft saved. Initial1280 fullpage screenshots contain fixed-position capture artifacts, so these are not by themselves visual-layout bugs.
- Assessment: Suspected recurrence matching previously closed#127; live deployment is older/misaligned, so cannot claim current local fix regressed. Preserve existing closed status, append run evidence for investigation.
