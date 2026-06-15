---
id: 140
title: 'stage-15: 02 Member Profile'
status: closed
priority: medium
created: 2026-05-19T22:56:18.779459198+02:00
updated: 2026-05-24T17:07:37.426690628+02:00
started: 2026-05-23T08:06:53.452563702+02:00
completed: 2026-05-23T12:26:43.976804185+02:00
tags:
    - qa
    - stage-15
class: standard
---

Source: stages/15-manage-members/02-member-profile.md

[[2026-05-23]] Sat 12:26
QA 2026-05-23: Search path blocked by #105, so opened profiles by direct URL. Customer One #32 fixture updated with older registration date and billing phone. Profile shows avatar, full name, @customer1, email text, phone, joined date, Customer role, Login as Customer/Edit User/Clear actions, six stats, and three quick links. Edit User opened WP user edit for user 32 and returned via Member Details. Clear returns to empty search state. Admin Two #38 fixture created; profile disables Login as Customer with backend reason 'Administrator accounts cannot be impersonated.' Login-as-customer works; My Account shows Customer One; subscriptions query endpoint shows no subscriptions; Go back as admin works; second impersonation also works. Issues logged: #106 email not mailto; #107 banner missing on homepage after impersonation; #108 pretty /my-account/subscriptions route renders Blog; #109 hash route change can show stale profile.

[[2026-05-24]] Sun 16:39
Issue #106 fixed: Customer One profile email is now a clickable mailto link. Verified with agent-browser and agent-browser on /manage-members/32. Screenshot qa/artifacts/issue-106-mailto-link.png.

[[2026-05-24]] Sun 16:47
Issue #107 fixed: Login-as-customer banner now renders on homepage immediately after impersonation via wp_body_open + footer fallback. Verified Customer One impersonation, homepage banner, and switch-back. Screenshots qa/artifacts/issue-107-homepage-impersonation-banner.png and qa/artifacts/issue-107-my-account-banner.png.

[[2026-05-24]] Sun 16:57
Issue #108 fixed: pretty /my-account/subscriptions/ now maps to the WooCommerce My Account subscriptions endpoint even when rewrite rules are absent. Verified during Customer One impersonation. Screenshot qa/artifacts/issue-108-pretty-subscriptions.png.

[[2026-05-24]] Sun 17:07
Issue #109 fixed: Manage Members now reloads on route-param changes and guards stale member requests. Verified same-session VIP #37 -> Customer One #32 hash route swap. Screenshot qa/artifacts/issue-109-route-swap-customer-one.png.
