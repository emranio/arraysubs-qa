---
id: 110
title: Admin list tables overflow the mobile page in Subscriptions and Credit History
status: open
priority: low
created: 2026-10-24T13:03:29.34602+06:00
updated: 2026-10-24T13:03:29.34602+06:00
tags:
    - bug
    - admin-ui
    - mobile
class: standard
---

Active QA task ID and scheduled day: N/A (targeted shared-header fix, issue #26, 2026-10-24 Asia/Dhaka).
Plan markdown path: N/A; scoped fix plan is in issue #26. Verification report: qa/admin-header-responsive-2026-10-24/report.md.
Affected subscription ID(s) and order ID(s): N/A (list layout, not an individual record).
Affected WordPress user/customer ID(s), login/email and roles: login admin, administrator role; numeric ID/email N/A; customer N/A.
Exact routes: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions and #/store-credit/history.
Browser context: authenticated agent-browser session arraysubs-header-local, Chromium, 390 x 844 and 390 x 900.
Reproduction:
1. Open Subscriptions with a 390px-wide viewport and wait for the list to load.
2. Inspect the viewport and the subscription list/table, then repeat on Store Credit > Credit History.
Expected: list controls and tables remain inside the page, with local horizontal scrolling for wide tables.
Actual: document scrollWidth is 674-675px on Subscriptions and 598px on Credit History at innerWidth 390. The shared header remains exactly 390px wide on both routes; overflowing content elements are inside the main table/list.
Concrete proof: qa/admin-header-responsive-2026-10-24/evidence/subscriptions-content-overflow.png and evidence/store-credit-history-content-overflow.png; measurements.json and interactions.json in that directory record viewport/page/header widths and overflowing element classes. Reproduced in both the page matrix and a separate navigation pass.
Known scope/counterexamples: Settings, Member Access, Manage Members, and Store Credit > Manage Credits keep document width 390. Desktop layout is usable. This is separate from the shared header issue #26 and the already closed Plans toolbar issue #103. No records, balances, or settings were changed.
