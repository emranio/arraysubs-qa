---
id: 22
title: Setup wizard switch focus scrolls the footer away from the modal bottom
status: closed
priority: high
created: 2026-09-09T16:57:35.470701+06:00
updated: 2026-09-09T17:08:09.697569+06:00
started: 2026-09-09T17:08:09.697569+06:00
completed: 2026-09-09T17:08:09.697569+06:00
tags:
    - bug
    - admin-ui
    - easy-setup
class: standard
---

Active QA task ID and scheduled day: N/A (user-reported regression; no active QA plan).
Plan markdown path: N/A.
Affected subscription IDs and order IDs: N/A.
Affected WordPress user/customer: authenticated local admin, login admin, role administrator; numeric ID N/A (not user-specific).
Route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/easy-setup
Browser context: isolated admin session wizard-jump-admin; desktop 1280x577.

Reproduction:
1. Launch Setup Wizard and advance to Checkout & Cart Rules.
2. Scroll the wizard content down to the three Cart Info Editor switches.
3. Click a switch.
Expected: The switch changes state and Back/Skip/Next stay at the bottom of the modal.
Actual: User observes the footer pulled upward with blank space below it. Investigation shows the hidden checkbox is positioned outside the intended scroll area: first visible switch y=195.84, hidden checkbox y=1403.84, with offsetParent arraysubs-setup-wizard. Outer modal content has scrollHeight=1592 despite height=496 and overflow:hidden, so focus can programmatically scroll it.
Initial proof: user screenshot /var/folders/cp/prfqkm1n3qv0xtq3gv05_7b40000gn/T/codex-clipboard-2c2793a2-edbb-4a22-89ba-3a67d56aa760.png and browser geometry observations above.
Scope: Core wizard layout, exposed by Pro Cart Info Editor toggles; no Pro wizard CSS overrides. Normal content scrolling leaves the footer at bottom=557 until focus scrolls an outer ancestor.

Fix plan:
Anchor the invisible checkbox within the visible toggle using a positioned label and matching bounds. Make the wizard outer content wrapper non-scrollable; only the questions area should scroll. Retain accessible keyboard focus and WordPress theme focus styling. Rebuild and test real clicks, keyboard toggle/navigation, desktop/mobile scroll behavior, and step transitions without resetting outer scroll positions during verification.

Reproduced with a real click on the visible first switch. The checkbox becomes checked, outer modal content scrollTop jumps from 0 to 1096, and footer bottom moves from 557 to -539 while the modal bottom remains 557. Screenshot: /tmp/arraysubs-wizard-footer-switch-jump.png. Full reproduction recording: /tmp/arraysubs-wizard-footer-click-repro.webm.

User additionally requested that these three Cart Info Editor controls use standard checkboxes, while explicitly retaining the reusable switch fix. Added the checkbox field renderer and changed these three question definitions only; ToggleField and the corrected switch positioning/focus styling remain. The outer wizard content keeps overflow:clip so focus cannot scroll the footer wrapper. Before changing the controls, verified real clicks on the first and second switches plus Tab/Space on the third: outer scrollTop stayed 0; footer and modal bottom both stayed 557. Keyboard focus outline was visible. Evidence: /tmp/arraysubs-wizard-footer-fixed.webm and /tmp/arraysubs-wizard-footer-fixed-desktop.png.

Reusable switch fix additionally passed on a 390x844 viewport: clicking the third switch left outer scrollTop=0 and both modal/footer bottoms at 824. Screenshot /tmp/arraysubs-wizard-switch-fixed-mobile.png. Final checkbox representation uses native visible inputs in normal flow and retains Yes/No review formatting and the same boolean answer values. Final admin build, whitespace checks, and file-size checks passed. The later final-checkbox visual recheck was interrupted by browser animation/daemon stalls; the recorded desktop/mobile switch interaction checks above completed successfully without any scroll-reset workaround.
