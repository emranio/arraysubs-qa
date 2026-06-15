---
id: 98
title: 'stage-14: Export REST payload shape differs from CSV/JSON contract'
status: closed
priority: medium
created: 2026-05-23T11:56:42.25931467+02:00
updated: 2026-05-24T15:28:13.622884376+02:00
started: 2026-05-24T15:08:55.863633759+02:00
completed: 2026-05-24T15:28:13.622883484+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
    - export
    - rest
claimed_by: shell-quartz
claimed_at: 2026-05-24T15:28:13.622884276+02:00
class: standard
---

Original task: stages/14-admin-subscriptions/07-export-subscriptions.md\n\nQA plan expects CSV endpoint/network response to be text/csv attachment and JSON endpoint body to be a JSON array. Internal REST call shows both formats use the standard wrapper keys code/message/content. CSV response content is JSON with content.csv, content.filename, content.count; frontend then creates a Blob download. JSON response content is the array, not the top-level body.\n\nExpected per plan: CSV REST response content-type/disposition suitable for direct download, and JSON endpoint top-level array or updated documentation/QA contract.

[[2026-05-24]] Sun 15:09
Fix pass started 2026-05-24 by shell-quartz. Verifying Stage 14 task 07 export contract and tracing admin export REST implementation.

[[2026-05-24]] Sun 15:17
Plan: export?format=json returns raw array; export?format=csv streams text/csv attachment with BOM/filename; SubscriptionsList handles response as blob instead of wrapper JSON.

[[2026-05-24]] Sun 15:28
Fixed and verified. Code: export?format=json now returns top-level JSON array; export?format=csv streams raw text/csv attachment with BOM, filename, and count header; SubscriptionsList downloads CSV blob directly. Checks: php -l ExportController passed; npm run build passed. Browser: agent-browser confirmed Export CSV button; agent-browser verified internal REST base index.php?rest_route=/ returned JSON 200 application/json top-level array with 308 rows, CSV 200 text/csv attachment with EF BB BF BOM and 15-column header, plus UI button download subscriptions-export-2026-05-24-132718.csv. Artifacts: qa/artifacts/issue-98-export-json.json, qa/artifacts/issue-98-export-direct.csv, qa/artifacts/issue-98-export-button.csv, qa/artifacts/issue-98-export-button-page.png. Note: pretty /wp-json route still returns HTML; that remains separate issue #100.
