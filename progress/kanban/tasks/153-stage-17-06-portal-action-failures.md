---
id: 153
title: 'stage-17: 06 Portal Action Failures'
status: closed
priority: medium
created: 2026-05-19T22:56:21.408953271+02:00
updated: 2026-07-08T02:18:27.484375+06:00
started: 2026-05-23T08:06:53.46491936+02:00
completed: 2026-07-08T02:18:27.49248+06:00
tags:
    - qa
    - stage-17
class: standard
---

Source: stages/17-audits-and-logs/06-portal-action-failures.md

[[2026-05-23]] Sat 16:08
QA blocked before changing customer cancellation settings. Verified target Portal Action Failures troubleshooting screen is missing: only Activity Audits, Gateway Logs, Scheduled-Job Logs are visible; direct route /audits/portal-action-failures renders blank; code search found no route/component/REST endpoint. Logged issue #133. Did not disable cancellation or force endpoint failure because the required screen/actions cannot be tested.

[[2026-05-24]] Sun 12:57
Issue #133 fixed. Portal Action Failures screen now exists at /wp-admin/admin.php?page=arraysubs-mainadmin#/audits/portal-action-failures. Forced disabled-cancel failure for member1@example.com subscription #1668 returned HTTP 403 cancellation_disabled; screen showed View and Resolve; screenshots: qa/artifacts/issue-133-portal-action-failures/01-portal-failures-table.png through 04-after-resolve.png. Resolve verified: failure #2805 status=resolved; activity note #2807 recorded as subscription / portal_action_failure_resolved. Customer cancellation setting restored ON and subscription #1668 remained active.
