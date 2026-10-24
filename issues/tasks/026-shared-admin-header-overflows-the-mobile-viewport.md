---
id: 26
title: Shared admin header overflows the mobile viewport
status: closed
priority: low
created: 2026-09-10T01:27:56.098987+06:00
updated: 2026-10-24T13:04:32.849769+06:00
started: 2026-10-24T12:53:48.044478+06:00
completed: 2026-10-24T13:04:32.849769+06:00
tags:
    - bug
    - admin-ui
    - mobile
class: standard
---

Active QA task ID and scheduled day: N/A (discovered during Help page verification; no active QA plan).
Plan markdown path: N/A.
Affected subscription IDs and order IDs: N/A.
Affected WordPress user/customer: admin login, administrator role; numeric ID/email N/A (shared admin layout).
Exact route: http://localhost:10003/wp-admin/admin.php?page=arraysubs-mainadmin#/help
Browser context: authenticated agent-browser session help-resource-links, mobile viewport 390 x 844.

Reproduction:
1. Open Help at a 390px-wide viewport.
2. Inspect document.documentElement.scrollWidth and the bounding rectangle of .arraysubs-top-header.
Expected: The shared admin header fits the viewport without horizontal scrolling.
Actual: The header extends to x=398 at a 390px viewport and document scrollWidth is 398.
Proof: Browser measurements: viewport=390, Help card container right=363, header right=398, document scrollWidth=398. Screenshot: /tmp/arraysubs-help-contact-first-mobile.png.
Scope: The Help cards fit within the viewport and use one column on mobile. Overflow comes from the shared .arraysubs-top-header, which was not changed by the Help page work. Desktop Help layout is correct. Other routes have not been verified for this issue.

Additional verification (2026-09-14): The same existing 8px shared-header overflow is present on `http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/easy-setup`, authenticated local administrator, agent-browser session `easy-setup-admin`, viewport 390 × 844. Active QA task/day/plan: N/A (targeted verification of compact onboarding cards). Subscription/order/customer IDs: N/A; login `admin`, administrator role. Page scroll width is 398px at a 390px viewport. The three compact task cards fit inside the page panel at 288px wide, with all three actions 32px high. Screenshot: `/tmp/arraysubs-easy-setup-mobile.png`. The shared-header issue remains open; the compact cards do not introduce additional overflow.

Fix plan (2026-10-24): reproduce the shared header at phone, tablet, and desktop widths; replace the fixed-height header with a flexible layout, wrap section navigation without overflow or separators at line edges, align sticky offsets and gutters with WordPress breakpoints, and stack optional header actions when space is limited. Rebuild core assets and verify Settings, Reports, Help, and Overview navigation, focus, scrolling, and admin color scheme in the real browser. Pro uses the shared core header, so verify premium menu entries too. No active QA cycle/task/day/plan; subscription/order/customer IDs N/A.

Implemented and browser-verified 2026-10-24 on http://localhost:10013 as admin/administrator. Shared core TopHeader and layout styles now use automatic height, compact wrapping tabs, an accessible native section selector at <=782px, bounded optional actions, semantic single-page headings, and WordPress-aligned gutters/sticky offsets. Production build passed. All 65 geometry cases across 41 shared-header routes and widths 320-1920 passed without header overflow, clipping, or subtitle overlap. Browser navigation, Back, keyboard typeahead, desktop focus, external Emails link, and phone/tablet/desktop sticky offsets passed; browser errors empty. Pro sections use the same core header and were included. Full report/evidence: qa/admin-header-responsive-2026-10-24/report.md, measurements.json, interactions.json, and evidence/. Core changelog draft updated; no Pro source change. Separate existing list/table overflow logged as issue #110. No active QA plan/task/day and no subscription/order/customer fixture changes.
