---
id: 33
title: Quick-product mobile checkbox checkmark overflows the control
status: closed
priority: low
created: 2026-09-15T16:41:58.367031+06:00
updated: 2026-09-15T16:43:23.727308+06:00
started: 2026-09-15T16:43:23.727307+06:00
completed: 2026-09-15T16:43:23.727307+06:00
tags:
    - bug
    - quick-product
    - admin-ui
class: standard
---

Active QA task: QPO-06; scheduled day: 2026-09-15; plan: qa/quick-product-options-2026-09-15/plan.md.
Affected subscriptions/orders: N/A. Product: unsaved simple product (QPO Mobile inspection only); same rendering observed on membership creation for product 6394.
Affected user: WordPress ID 1, login admin, administrator; email N/A.
URL: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/overview. Browser: isolated agent-browser quick-options-admin session at 390x844, authenticated administrator.
Reproduction: Open Subscription Product; choose Simple; enter title and price; choose Full Flexible; inspect checked Month. Also enable Use fixed end date in the membership step.
Expected: Tick fits and centers in its checkbox at mobile and desktop widths.
Actual: The checkmark extends below/right of its 16x16 control. WordPress mobile pseudo-element is 30x30 with margins -3px -5px while FormBuilder retains a 16px input.
Proof: qa/quick-product-options-2026-09-15/screenshots/billing-mobile.png and membership-mobile.png; computed before width/height 30px, box width/height 16px.
Scope: Shared FormBuilder checkboxes inside quick-product modal at mobile width. Existing radio dot centering remains correct; no product metadata issue. Repro video: N/A, static visual issue.
Fix plan: Scope checkbox tick dimensions/centering to the existing quick-product FormBuilder controls, preserving native focus and WordPress scheme. Build core; inspect screenshots and computed geometry at mobile/desktop; close only after browser verification.

Resolution: Scoped the native checkmark pseudo-element to quick-product FormBuilder checkboxes, using a centered 20px graphic with zero inherited margins. Existing keyboard focus and admin-scheme styling remain.
Verification: Core production build passed. Inspected billing-mobile-fixed.png, membership-mobile-final.png and billing-desktop-final.png at 390x844 and 1440x1000. Checkmark is fully inside the checkbox; computed 16px input, centered 20px SVG, zero margins, translate(-10px,-10px). Full Flexible period checks and membership toggle work; radio ArrowLeft navigation passes. No browser errors. Source quick-product.scss is under 3000 lines.
