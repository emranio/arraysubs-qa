---
id: 103
title: Plans toolbar overflows the mobile viewport
status: closed
priority: medium
created: 2026-10-24T03:53:54.232357+06:00
updated: 2026-10-24T03:55:37.03717+06:00
started: 2026-10-24T03:54:25.421397+06:00
completed: 2026-10-24T03:55:37.03717+06:00
tags:
    - bug
    - subscription-plans
    - mobile
class: standard
---

Active QA task ID/day: Subscription Plans progress #8, 2026-10-24 Asia/Dhaka.
Plan: qa/subscription-plans-2026-10-24/plan.md.
Subscription/order IDs: N/A. Product20077 (dedicated fixture); user ID1/admin/administrator, customer N/A.
Exact route: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/plans?search=Plans%20QA%202026-10-24%20Simple.
Browser: plans-primary, authenticated local administrator,390x844.
Steps: resize to390x844, reload Plans, wait for product list, inspect status tabs and toolbar.
Expected: all status filters/search/type/Add new plan controls fit within the viewport; only the wide product table scrolls horizontally.
Actual: page scrollWidth554 at innerWidth390; toolbar/search/type and status tabs run beyond right edge. Shared top header fits388, so this is separate from pre-existing issue26.
Proof: evidence/08-mobile-plans.jpg, evidence/09-mobile-overflow-loaded.jpg; DOM wrapper width338/scrollWidth1120/overflow auto, pagewidth554.
Scope: desktop controls work; table already has intended local scrolling.
Fix plan: constrain Plans list header/toolbar to available width, wrap status tabs, stack search/type/Add controls on small screens; keep table local scrolling. Rebuild, inspect390x844 screenshot and assert pagewidth <= viewport, then verify modal/focus.

Scoped Plans mobile CSS constrains the toolbar/header, wraps status filters, and stacks search/type/Add controls. Rebuilt and verified real390x844 browser screenshot evidence/10-mobile-plans-fixed.jpg. DOM now viewport390/pageWidth390, table localwrapper338/scrollWidth1120, Add button x25..363. All controls visible. Existing unrelated issue26 preserved.

Further screenshot inspection shows WordPress applies its mobile product-table stacking rules to the shared table, hiding some headers and expanding each product into stacked cells inside the wide scroll area. Refine the same scoped fix to retain table-row/table-cell display for Plans so the horizontal scroll reveals coherent Woo-style columns.

Final refinement verified: all9 headers and cells retain table-cell display on mobile; screenshot12-mobile-table-fixed.jpg inspected. Horizontal scroll moved wrapper to700 while page remained390wide; screenshot13-mobile-table-scrolled.jpg. Mobile modal screenshot11 fits350x804 within390x844, footer visible, focus stays inside, Tab/Shift-Tab and Escape close work.
