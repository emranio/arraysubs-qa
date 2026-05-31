---
id: 74
title: 'stage-12: Store Credit customer search shows no dropdown result'
status: closed
priority: high
created: 2026-05-23T09:17:10.512528862+02:00
updated: 2026-05-24T09:39:41.966635454+02:00
started: 2026-05-24T09:28:16.060023801+02:00
completed: 2026-05-24T09:39:41.966631958+02:00
tags:
    - qa
    - stage-12
    - store-credit
claimed_by: shell-quartz
claimed_at: 2026-05-24T09:39:41.966635324+02:00
class: standard
---

Stage 12 Task 02 Subtask 2.1. On Store Credit Management page, typing single c correctly showed no dropdown. Typing cust3@test.local in Find a Customer field did not show any dropdown result after 10s. WP-CLI get_users search confirms cust3@test.local exists as user #7 with balance 0, and the REST controller searches user_login/user_email/display_name. Expected dropdown with cust3 (cust3@test.local) balance $0.00. Used direct #/store-credit/7 route to continue QA.

[[2026-05-23]] Sat 09:21
Extra evidence: HTTP GET /wp-json/arraysubs/v1/credits/search?query=cust3 returns 301 to /credits/search/?query=cust3, then HTML front page / no REST JSON. Same for /credits/customer/7. In-app direct #/store-credit/7 showed toast "No route was found matching the URL and request method" and no customer panel. rest_do_request inside WP works, so browser-facing pretty REST route + canonical trailing slash is the failure path.

[[2026-05-23]] Sat 20:04
Stage 19 Task 05 reproduced on cust5@example.com after credit refund. Store Credit Management search field accepted 'cust5@example.com' but showed no dropdown/result/balance, despite user #52 having _arraysubs_store_credit=40 and credit log #1753.

[[2026-05-24]] Sun 09:39
Fix verified: Store Credit Management now builds REST URLs with buildRestUrl(), so the plain-permalink REST base index.php?rest_route=/ is handled correctly. Browser verified /store-credit search for cust3@test.local shows a dropdown result with balance, selecting it opens #/store-credit/7, current balance panel, Member Details button, and Credit History table.
