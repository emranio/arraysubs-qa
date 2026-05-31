---
id: 152
title: 'stage-17: 05 Renewal Failures Troubleshooting'
status: blocked
priority: medium
created: 2026-05-19T22:56:21.230314197+02:00
updated: 2026-05-24T12:38:50.962580251+02:00
started: 2026-05-23T08:06:53.464279372+02:00
tags:
    - qa
    - stage-17
class: standard
---

Source: stages/17-audits-and-logs/05-renewal-failures-troubleshooting.md

[[2026-05-23]] Sat 16:06
QA blocked before forcing payment/card changes. Verified target Renewal Failures troubleshooting screen is missing: only Activity Audits, Gateway Logs, Scheduled-Job Logs are visible; direct route /audits/renewal-failures renders blank; code search found no route/component/REST endpoint for Renewal Failures. Also depends on task 17.04 webhook logging, which failed in issue #130. Logged issue #132. Did not mutate member3 card/subscription because the required screen/actions cannot be tested.

[[2026-05-24]] Sun 12:38
Issue #132 fixed. Renewal Failures screen now exists at /wp-admin/admin.php?page=arraysubs-mainadmin#/audits/renewal-failures with rows, Retry, and Mark Resolved. Browser proof: qa/artifacts/issue-132-renewal-failures/01-renewal-failures-table.png, 02-mark-resolved-confirm.png, 03-after-resolve.png. Mark Resolved verified on QA fixture subscription #2727; audit note #2800 recorded as subscription / renewal_failure_resolved. Remaining stage status may still depend on other logged blockers such as issue #130.
