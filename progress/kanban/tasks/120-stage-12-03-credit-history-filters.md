---
id: 120
title: 'stage-12: 03 Credit History Filters'
status: closed
priority: medium
created: 2026-05-19T22:56:15.74252989+02:00
updated: 2026-05-24T09:48:18.805585479+02:00
started: 2026-05-23T08:06:53.430700167+02:00
completed: 2026-05-23T09:29:48.001861073+02:00
tags:
    - qa
    - stage-12
class: standard
---

Source: stages/12-store-credit/03-credit-history-filters.md

[[2026-05-23]] Sat 09:29
QA done. Browser Credit History page FAIL/blocker logged #75. Page opens as All Credit Transactions but shows Total: 0, no rows, empty state, and rest_no_route 404 even though cust3 has #1103 +50 and #1104 -10. Source=Admin Adjustment and Type=Credit (Added) controls change state but still show rest_no_route/0 rows. Because table never loads, subtasks 3.1-3.8 row/filter/modal checks cannot pass in browser. Backend cross-check via rest_do_request works: all/admin total=2, credit total=1 (#1103), debit total=1 (#1104), admin+credit total=1, refund total=0. cust3 balance unchanged at 0.

[[2026-05-24]] Sun 09:48
Issue #75 fix verified: global Credit History browser REST calls now load rows and filters. All Sources loaded 27 transactions; Source=Admin Adjustment loaded 13; Admin Adjustment + Credit (Added) loaded 9 positive rows; no rest_no_route toast.
