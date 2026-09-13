---
id: 27
title: Quick product completion card descriptions run into titles
status: in-progress
priority: medium
created: 2026-09-14T00:13:28.794201+06:00
updated: 2026-09-14T00:13:28.810539+06:00
started: 2026-09-14T00:13:28.812175+06:00
tags:
    - bug
    - admin-ui
    - easy-setup
class: standard
---

Active QA task ID and scheduled day: N/A (user-reported regression; no active QA cycle).
Plan markdown path: N/A.
Affected subscription IDs and order IDs: N/A. Affected product ID: 27207 (local QA completion cards — September 14 fixture).
Affected WordPress user/customer: login admin, administrator role; numeric ID/email N/A. No customer involved.
Exact route: http://localhost:10003/wp-admin/admin.php?page=arraysubs-mainadmin#/easy-setup
Browser context: authenticated admin, isolated agent-browser session quick-product-cards, desktop Chromium, Modern admin color scheme.
Reproduction: Click Create subscription product, choose Simple subscription product, enter a title and price, keep defaults through all seven steps, click Create product and confirm. Inspect the three completion links.
Expected: Each card has a distinct title above a smaller description, readable spacing and compact height.
Actual: Titles run directly into the descriptions and wrap with excessive vertical line spacing.
Proof: User screenshot and /tmp/arraysubs-cards-before.png. Browser computed styles show card display:block and line-height:38px; children remain inline with title line-height:46.7692px and description line-height:35.0769px. The WordPress .wp-core-ui .button selector overrides the one-class card selector. All cards measure 114.75px tall.
Scope/counterexamples: Shared core quick-product completion UI with Pro active; no Pro-specific completion implementation. Product creation succeeds and links exist. This is unrelated to existing marked QA issues.
Fix plan: Scope the card selector beneath the completion links, explicitly stack title and description with a small gap and independent line-height, remove the padding !important, rebuild, then repeat the real product creation flow and inspect screenshots and geometry at desktop and mobile widths. Verify all three destinations and keyboard focus. Remove only the test fixtures afterward.
Repro video: N/A (static layout defect).
