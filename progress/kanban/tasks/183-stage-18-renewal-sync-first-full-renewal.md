---
id: 183
title: 'stage-18: Renewal Sync first full renewal'
status: closed
priority: high
created: 2026-06-03T12:19:21.774117774+02:00
updated: 2026-06-03T17:01:26.464275279+02:00
started: 2026-06-03T13:18:10.845475103+02:00
completed: 2026-06-03T13:29:59.015023942+02:00
tags:
    - qa
    - stage-18
    - renewal-sync
    - renewals
class: standard
---

Time-travel/browser follow-up for synced subscription first full renewal amount and aligned next date. Plan: qa/stages/18-renewal-followup/13-renewal-sync-first-full-renewal.md.



Issue #171 fix verification (2026-06-03): after patching Skip Renewal REST status normalization, reopened https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/4242 with agent-browser. The /arraysubs/v1/subscriptions/4242/skip response returned complete default fields for inactive skip state and debug.log line count stayed unchanged at 365. Screenshot: /tmp/arraysubs-issue-171-subscription-detail.png.
