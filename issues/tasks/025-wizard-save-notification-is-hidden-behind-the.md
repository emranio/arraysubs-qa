---
id: 25
title: Wizard save notification is hidden behind the modal backdrop
status: closed
priority: medium
created: 2026-09-09T18:25:04.224107+06:00
updated: 2026-09-09T18:38:29.598855+06:00
started: 2026-09-09T18:25:28.576237+06:00
completed: 2026-09-09T18:38:15.347201+06:00
tags:
    - bug
    - admin-ui
    - easy-setup
class: standard
---

Active QA task ID and scheduled day: N/A (user-reported issue; no active QA cycle).
Plan markdown path: N/A.
Subscription IDs and order IDs: N/A.
WordPress user/customer ID: N/A; login: admin; role: administrator.
Route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/easy-setup (local admin browser).
Steps: Open Setup Wizard, go to Review & Apply, and successfully apply settings.
Expected: The success notification appears clearly above the wizard and can be dismissed.
Actual: The toast appears behind the modal backdrop. The prior completion screenshot /tmp/arraysubs-wizard-completion-desktop.png shows the green notification blurred behind the overlay.
Code evidence: core toast container and modal backdrop both use z-index 99999; the modal is portaled to document.body while the toast stays inside the page tree.
Scope: Core shared admin notifications, including the wizard with Pro active. Pro has an unused separate toast library; no Pro toast consumers were found. Other existing QA issues remain unchanged.
Fix plan: Portal core toast containers to document.body, put notifications above admin overlays, and verify successful Apply, notification dismissal, modal stability, and responsive completion styling in the local browser.

[[2026-09-09]] Wed 18:38
Implemented: core ToastContainer now portals to document.body and uses z-index 100100, above the wizard backdrop at 99999 and admin builder overlays. Core and Pro wizard pages continue using the existing core notification API; no separate notification system was introduced.
Verification: A real local admin Apply succeeded with message "Setup wizard applied successfully. 0 settings configured." The settings hash was unchanged. Browser measurements confirmed bodyPortal=true, toastZ=100100, modalZ=99999, and elementFromPoint at the toast center returned the toast rather than the backdrop. Both completion text blocks computed text-align:center. The product card used background rgb(56, 88, 233), matching --wp-admin-theme-color #3858e9; icon, heading, paragraph, and link were white. Production admin build and whitespace checks passed; all modified source files remain below 3000 lines.
Capture limitation: viewport, full-page, and element screenshots stalled in this browser session, including after restarting it. The above proof is from the actual browser DOM, hit testing, and computed styles; no screenshot success is claimed.

[[2026-09-09]] Wed 18:38
Additional browser check: temporarily setting the card’s --wp-admin-theme-color to #008a20 changed its computed background to rgb(0, 138, 32). The override was removed immediately. The success notification also auto-dismissed while the completion view remained open.
