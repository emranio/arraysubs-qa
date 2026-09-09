---
id: 26
title: Shared admin header overflows the mobile viewport
status: open
priority: low
created: 2026-09-10T01:27:56.098987+06:00
updated: 2026-09-10T01:27:56.098987+06:00
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
