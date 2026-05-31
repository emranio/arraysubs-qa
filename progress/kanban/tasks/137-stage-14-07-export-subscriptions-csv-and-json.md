---
id: 137
title: 'stage-14: 07 Export Subscriptions (CSV and JSON)'
status: closed
priority: medium
created: 2026-05-19T22:56:18.233516957+02:00
updated: 2026-05-24T15:42:48.02108939+02:00
started: 2026-05-23T08:06:53.449761356+02:00
completed: 2026-05-23T11:57:06.790905995+02:00
tags:
    - qa
    - stage-14
class: standard
---

Source: stages/14-admin-subscriptions/07-export-subscriptions.md

[[2026-05-23]] Sat 11:57
QA notes: Browser Active(17) -> Export CSV showed toast '29 subscriptions exported', so current list filter is ignored; logged #99. Internal REST control: status=arraysubs-active CSV returned 17 rows, all Status Active, BOM present, 15 headers exactly as documented, filename pattern subscriptions-export-2026-05-23-095524.csv. All CSV returned 29 rows with status breakdown Active 17, On Hold 3, Expired 1, Cancelled 3, Trial 3, Pending 2; BOM/header correct. JSON internal REST returned 29 rows with 15 snake_case keys matching CSV fields. Documented pretty /wp-json/arraysubs/v1/subscriptions/export?format=json route redirects/renders public Blog HTML instead of JSON; logged #100. REST payload uses code/message/content wrapper and CSV is JSON-wrapped blob content, not text/csv attachment; logged #98. Unauthenticated ?rest_route endpoint returns 401 rest_forbidden. One malformed QA eval command caused a parse error/debug entry; corrected command succeeded.

[[2026-05-24]] Sun 15:28
Fix verification 2026-05-24 for issue #98: export?format=json returns a top-level JSON array with 308 rows and all documented keys; export?format=csv returns text/csv attachment with EF BB BF BOM, 15-column header, filename, and count header; UI Export CSV button downloads a CSV blob. Artifacts: qa/artifacts/issue-98-export-json.json, qa/artifacts/issue-98-export-direct.csv, qa/artifacts/issue-98-export-button.csv, qa/artifacts/issue-98-export-button-page.png. Checks: php -l ExportController passed; npm run build passed. Pretty /wp-json route remains tracked separately in issue #100.

[[2026-05-24]] Sun 15:38
Fix verification 2026-05-24 for issue #99: Active tab export now respects current status filter. Browser proof: Active tab count 181; downloaded CSV had 181 data rows and all Status values were Active, with BOM and timestamped filename. Artifacts: qa/artifacts/issue-99-active-tab-before-export.png and qa/artifacts/issue-99-active-export.csv. Check: npm run build passed.

[[2026-05-24]] Sun 15:42
Fix verification 2026-05-24 for issue #100: pretty /wp-json/arraysubs/v1/subscriptions/export?format=json no longer falls through to the public theme when rewrite rules are missing. Unauthenticated pretty URL returns 401 application/json; admin REST nonce request returns 200 application/json top-level array with 308 rows. Plain browser address bar without REST nonce returns JSON 401, matching WordPress nonce auth rather than leaking data. Screenshot: qa/artifacts/issue-100-pretty-json-browser.png. Check: php -l Boot.php passed.
