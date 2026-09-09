---
id: 24
title: Setup wizard modal height changes between steps
status: closed
priority: medium
created: 2026-09-09T17:40:13.789882+06:00
updated: 2026-09-09T17:41:43.956147+06:00
started: 2026-09-09T17:41:43.956146+06:00
completed: 2026-09-09T17:41:43.956146+06:00
tags:
    - bug
    - admin-ui
    - easy-setup
class: standard
---

Active QA task ID and scheduled day: N/A (user-reported issue; no active QA cycle).
Plan markdown path: N/A.
Affected subscription/order IDs: N/A.
Affected WordPress user/customer: local admin login admin; numeric ID/email N/A; administrator role.
Route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/easy-setup
Browser context: authenticated administrator, desktop and mobile viewport checks.

Reproduction: Open Setup Wizard and navigate between a long step and a short step such as Plan Switching or Access Control.
Expected: Modal fills the available viewport height consistently; question content scrolls within it and footer stays at the bottom.
Actual: User reports modal height fluctuates between steps. Source uses min-height:60vh with content-driven height and only a shared max-height, allowing shorter steps to shrink the panel.
Proof: User report in this task; arraysubs/src/resources/scss/main/pages/_setup-wizard.scss former min-height:60vh rule.
Scope/counterexamples: This concerns panel resizing, separate from the hidden-switch focus/outer-scroll bug tracked in #22. The footer scroll isolation remains in place. Shared core wizard serves free and Pro; no Pro override found.

Fix plan: Set the wizard panel height and max-height to 100% of the backdrop content area, retain existing outer spacing, and let only the inner question area scroll. Rebuild and compare panel/footer geometry on long and short steps plus a narrow viewport. Do not apply wizard settings.

Verified on the actual local admin wizard after rebuilding: desktop viewport 1440x1000 kept modal top=20, bottom=980, height=960 and footer bottom=980 on Your Business, Billing & Renewal Rules, and Plan Switching. Selecting Allow all switching directions revealed proration fields without changing those dimensions. At 390x844 the modal remained top=20, bottom=824, height=804 and footer bottom=824. Scrolling the questions moved inner scrollTop to 183 while outer scrollTop stayed 0 and panel/footer positions remained unchanged. Screenshots inspected: /tmp/arraysubs-wizard-fixed-height-desktop.png and /tmp/arraysubs-wizard-fixed-height-mobile.png. Build and whitespace checks passed; touched SCSS is 711 lines. No wizard settings were applied.
