---
id: 30
title: Quick-product unlock guidance is misaligned and visually oversized
status: closed
priority: low
created: 2026-09-14T03:55:05.933045+06:00
updated: 2026-09-14T03:58:22.916975+06:00
tags:
    - bug
    - admin-ui
    - quick-product
class: standard
---

Active QA task ID and scheduled day: N/A (targeted user-reported design fix). Plan markdown path: N/A.
Affected subscription/order/product IDs: N/A (product-type selection only).
Affected WordPress user/customer: admin, administrator; numeric ID/email N/A.
Exact URL: http://localhost:10003/wp-admin/admin.php?page=arraysubs-mainadmin#/overview
Browser context: isolated agent-browser product-card-polish session; local authenticated administrator; desktop and mobile.
Reproduction: Open + Subscription Product with Pro inactive. Inspect the locked Bundle and Store Credit cards.
Expected: Compact unlock guidance aligns with the title and description, with clear selectable/locked states.
Actual: Guidance starts far left of the title in a separate full-width panel, with a heavy divider and excessive repeated heading/bullet spacing.
Proof: User screenshot codex-clipboard-4059af20-da5c-423c-a14a-0dc2267c16b9.png and /tmp/product-card-polish-before.png.
Scope/counterexamples: Product-type cards only. Availability logic and prerequisite links already function; preserve those and the centered radio-dot fix in issue 29.
Fix plan: Share fixed grid-column dimensions between main content and guidance, replace the separated panel with compact inline guidance, add a small locked status badge, rebuild core and check alignment, disabled interaction, links, keyboard navigation, and wrapping in the real browser at desktop/mobile widths.
Repro video: N/A (static visual issue).


Resolution: Replaced the separated full-width unlock panel with compact inline guidance, removed its visible repeated heading/bullets/divider, and added a small Pro/Setup needed badge next to the title. Shared card-column variables align guidance with the title and description, including mobile. Disabled icons are neutral; admin-scheme colors remain on selected controls and action links. All existing unlock conditions and actions are unchanged.
Verification: Core production build passed (b1e066e15bfe38e74693). Real browser screenshots inspected at 1334x886, 782x900, and 390x844. Title/guidance left-edge differences are exactly 0px at every tested width. Desktop locked card height reduced from 185.69px to 120.69px. Mobile and tablet wizard bodies have zero horizontal overflow. Clicking the locked bundle leaves Simple selected. Box selection works; ArrowRight skips both disabled premium types and returns to Simple with exactly one checked input. Pro link opens the correct #/you-may-need page in a new tab. Radio dots remain centered. Browser errors: none. No site settings, products, or plugin activation state were changed during this task.
After screenshots: /tmp/product-card-polish-desktop.png; /tmp/product-card-polish-mobile.png; /tmp/product-card-polish-tablet.png. Clean before screenshot: /tmp/product-card-polish-before-clean.png.
Core/Pro scope: Two shared core UI files only; Pro uses the same picker. No availability/backend contract changes or Pro source edits in this design pass. Source lengths: ProductTypePicker.jsx 139 lines; quick-product.scss 376 lines. Git diff whitespace check passed.
