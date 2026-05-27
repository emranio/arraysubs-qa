---
id: 75
title: 'stage-12: Store Credit History REST route fails in browser'
status: closed
priority: high
created: 2026-05-23T09:26:32.892510552+02:00
updated: 2026-05-24T09:48:25.487825954+02:00
started: 2026-05-24T09:39:48.338822157+02:00
completed: 2026-05-24T09:48:25.487824872+02:00
tags:
    - qa
    - stage-12
    - store-credit
claimed_by: shell-quartz
claimed_at: 2026-05-24T09:48:25.487825834+02:00
class: standard
---

Stage 12 Task 02 regression and likely Task 03 blocker. After creating cust3 credit transactions #1103 (+50 admin) and #1104 (-10 admin), ArraySubs > Store Credit > Credit History shows Total: 0 transactions and toast: rest_no_route / No route was found matching the URL and request method. Expected global Credit History to show both rows. This appears same pretty REST trailing-slash issue as #74, affecting /credits/history.

[[2026-05-23]] Sat 09:29
Stage 12 Task 03 confirmed blocker: selecting Source=Admin Adjustment and Type=Credit (Added) still shows Total: 0, empty state, and rest_no_route 404. WP internal rest_do_request for /arraysubs/v1/credits/history works and returns expected rows: all/admin total=2 (#1104 debit -10, #1103 credit +50), type credit total=1, type debit total=1, admin+credit total=1, refund total=0. Browser-facing route remains broken.

[[2026-05-23]] Sat 10:26
Stage 12 Task 07 Subtask 7.7 also blocked by this issue: after refund log #1186 source=refund amount=15 for cust3/order #1110, browser Store Credit History at #/store-credit/history still shows Total: 0 transactions, so Refund filter cannot verify the new row in UI. Customer portal and backend log do show the refund row.

[[2026-05-23]] Sat 20:04
Stage 19 Task 05 reproduced after credit refund log #1753. Browser /#/store-credit/history showed 'Total: 0 transactions' and 'No credit transactions found' plus route error, while DB has arraysubs_credit log #1753 for cust5@example.com source=refund amount=40 source_id=1750.

[[2026-05-24]] Sun 09:48
Fix verified: Credit History now uses buildRestUrl() for list/delete endpoints. Browser verified #/store-credit/history loads 27 transactions with table columns/rows, no rest_no_route toast. Source=Admin Adjustment refreshes to 13 transactions; combined Source=Admin Adjustment + Type=Credit (Added) refreshes to 9 positive-credit rows with no route error.
