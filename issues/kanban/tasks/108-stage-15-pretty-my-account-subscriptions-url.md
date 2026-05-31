---
id: 108
title: 'stage-15: Pretty My Account subscriptions URL renders blog page'
status: closed
priority: medium
created: 2026-05-23T12:26:28.274385249+02:00
updated: 2026-05-24T16:57:30.972554537+02:00
started: 2026-05-24T16:47:59.920525171+02:00
completed: 2026-05-24T16:57:30.972553024+02:00
tags:
    - qa
    - stage-15
    - login-as-user
    - customer-portal
    - routing
claimed_by: shell-quartz
claimed_at: 2026-05-24T16:57:30.972554437+02:00
class: standard
---

Stage 15 task 02: visiting https://mirror-help.arrayhash.com/my-account/subscriptions/ during Customer One impersonation rendered the public Blog/Hello world content, not the WooCommerce My Account Subscriptions endpoint. The query-style account URL https://mirror-help.arrayhash.com/?page_id=9&subscriptions works and shows My Subscriptions. QA plan names /my-account/subscriptions, so pretty endpoint/permalink routing is inconsistent.

[[2026-05-24]] Sun 16:48
Fix pass started 2026-05-24 by shell-quartz. Verifying My Account subscription endpoint routing after impersonation and tracing rewrite/endpoint registration.

[[2026-05-24]] Sun 16:57
Fixed My Account pretty endpoint routing for plain-permalink/no-rewrite-rule environments. CustomerPortal MyAccountHooks now maps /my-account/subscriptions/ and /my-account/view-subscription/{id}/ to the configured WooCommerce My Account page query vars when WordPress does not populate endpoint vars. Verified with Playwright during Customer One impersonation: URL remained https://mirror-help.arrayhash.com/my-account/subscriptions/, page showed My Subscriptions, and Hello world/blog content was absent. Screenshot: qa/artifacts/issue-108-pretty-subscriptions.png. php -l passed for MyAccountHooks.php.
