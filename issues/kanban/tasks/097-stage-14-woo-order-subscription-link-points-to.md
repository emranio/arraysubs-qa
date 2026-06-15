---
id: 97
title: 'stage-14: Woo order subscription link points to inaccessible legacy admin route'
status: closed
priority: high
created: 2026-05-23T11:50:32.45900908+02:00
updated: 2026-05-24T15:08:23.386900221+02:00
started: 2026-05-24T15:02:55.000888457+02:00
completed: 2026-05-24T15:08:23.386899309+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
    - woocommerce
claimed_by: shell-quartz
claimed_at: 2026-05-24T15:08:23.386900131+02:00
class: standard
---

Original task: stages/14-admin-subscriptions/06-related-orders-and-refunds.md\n\nCross-checking order #511 from subscription #508: WooCommerce order edit screen shows an ArraySubs Subscription link '#508'. Clicking it navigated to /wp-admin/admin.php?page=arraysubs#/subscriptions/edit/508 and produced an access-denied/error page. Current ArraySubs SPA route is /wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/508 (or edit/508).\n\nExpected: order subscription link returns to accessible ArraySubs subscription admin detail/edit route.

[[2026-05-24]] Sun 15:03
Fix pass started 2026-05-24 by shell-quartz. Verifying issue against Stage 14 task 06, then tracing Woo order -> ArraySubs admin link generation.

[[2026-05-24]] Sun 15:03
Plan: replace Woo order edit subscription link target with current ArraySubs admin SPA detail route, then php -l and browser-click verify order #511/subscription #508.

[[2026-05-24]] Sun 15:08
Fixed and verified. Code: Woo order edit ArraySubs Subscription link now targets admin.php?page=arraysubs-mainadmin#/subscriptions/detail/{subscription_id}. Check: php -l Refunds/Services/Hooks.php passed. Browser: agent-browser confirmed #508 href and successful click to Subscription #508 with no access denied; agent-browser proof screenshots: qa/artifacts/issue-97-order-511-subscription-link.png and qa/artifacts/issue-97-subscription-508-loaded.png.
