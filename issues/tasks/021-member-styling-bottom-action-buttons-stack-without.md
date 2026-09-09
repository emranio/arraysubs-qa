---
id: 21
title: Member Styling bottom action buttons stack without spacing
status: closed
priority: medium
created: 2026-09-09T16:41:54.153152+06:00
updated: 2026-09-09T16:43:06.827163+06:00
started: 2026-09-09T16:43:06.827162+06:00
completed: 2026-09-09T16:43:06.827162+06:00
tags:
    - bug
    - admin-ui
    - member-styling
class: standard
---

Active QA task ID and scheduled day: N/A (user-reported UI fix; no active QA plan).
Plan markdown path: N/A.
Affected subscription IDs and order IDs: N/A.
Affected WordPress user/customer: local admin login admin, role administrator; numeric ID N/A (layout applies to any authorized admin).
Route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/member-styling
Browser context: isolated authenticated admin session member-style-admin; desktop 1280 x 577.

Reproduction:
1. Open Member Styling after logging in.
2. Inspect Save Rules and Discard Changes in the bottom bar.
Expected: Both buttons share the horizontal action layout used by other settings pages.
Actual: The buttons stack with no gap. The fixed inner container has display:block; both button elements have display:flex.
Proof: /tmp/arraysubs-member-styling-buttons-before.png. Save Rules x=198,y=487,w=101.30,h=40; Discard Changes x=198,y=527,w=139.20,h=40.
Scope: Member Styling uses arraysubs-members-access__actions. Working settings pages use arraysubs-settings-actions, which puts display:flex and a 12px gap on the inner container. Core UI only; no premium overrides found.

Fix plan:
Use the existing shared settings-actions class on Member Styling, retain the shared fixed-bar positioning, and use SpinnerButton for Save Rules. Rebuild the admin assets and check desktop/mobile alignment, discard behavior, and an unchanged save in the actual local browser.

Verified fixed in the actual local admin browser. Desktop: both buttons y=527,h=40 with a 12px gap; fixed inner container display:flex. Mobile 390x844: bar x=25,w=338,bottom=844; both buttons y=790,h=40 and fully inside the bar. Discard Changes restored the unsaved master toggle to true and showed Changes discarded. Saving the unchanged restored settings succeeded with Member Styling rules saved successfully. Screenshots: /tmp/arraysubs-member-styling-buttons-after.png and /tmp/arraysubs-member-styling-buttons-mobile.png. Targeted admin build and diff whitespace checks passed. MemberStyling/index.jsx remains 379 lines. Shared CSS and Pro code unchanged.
