---
id: 9
title: 'stage-03: Subscription Box Free Ownership and Browser Verification'
status: closed
priority: critical
created: 2026-08-15T22:59:57.795777749+02:00
updated: 2026-08-15T23:37:49.983515783+02:00
started: 2026-08-15T23:37:12.954752931+02:00
completed: 2026-08-15T23:37:12.954752931+02:00
tags:
    - qa
    - products
    - subscription-box
claimed_by: root-vivid
claimed_at: 2026-08-15T23:37:49.983515403+02:00
class: standard
---

Verify Subscription Box is owned and booted by ArraySubs core, remains available with ArraySubsPro inactive, supports admin configuration and storefront box building, and has no duplicate Pro registration.

QA plan:
- qa/stages/03-products/17-subscription-box-free.md

QA result: PASS on the configured live installation https://mirror-help.arrayhash.com. The requested mirror-arraysubs hostname is tracked separately as blocked issue #9.

Browser: agent-browser 0.27.3 / HeadlessChrome 149.0.0.0.

Evidence:
- ArraySubsPro was deactivated through the Plugins browser screen and restored afterward.
- With Pro inactive, Subscription Box appeared exactly once, product ID 12600 loaded as an ArraySubs Core class, and the full configuration modal rendered.
- Only Core admin/frontend Subscription Box assets and Core REST routes loaded, all with HTTP 200.
- The storefront builder rendered one overlay/root with three product cards. Selecting SLT Box Sub Item calculated $5.00 every 2 days.
- Add to Cart visibly changed to disabled Adding..., then the cart showed one SLT Box Daily line for $5.00 with Box contents: SLT Box Sub Item x 1; no second child line.
- Core renewal segment plan for box product ID 11071 resolved to three active segments with boundaries 10/20 and enabled sync support while Pro was inactive.
- After Pro reactivation, the Box type, editor root, and Core lifecycle callbacks each remained registered exactly once; the separate Pro Subscription Bundle type remained available.

Screenshots:
- /tmp/arraysubs-ownership-qa/screenshots/box-admin-pro-disabled.png
- /tmp/arraysubs-ownership-qa/screenshots/box-config-modal-pro-disabled.png
- /tmp/arraysubs-ownership-qa/screenshots/box-builder-cards-pro-disabled.png
- /tmp/arraysubs-ownership-qa/screenshots/box-cart-pro-disabled.png

Affected product IDs: 12600 and 11071. Subscription/order/customer IDs: N/A.
