---
id: 154
title: 'stage-17: 07 Access-Rule Conflicts'
status: blocked
priority: medium
created: 2026-05-19T22:56:21.597508133+02:00
updated: 2026-05-24T13:12:04.381867534+02:00
started: 2026-05-23T08:06:53.465713286+02:00
tags:
    - qa
    - stage-17
class: standard
---

Source: stages/17-audits-and-logs/07-access-rule-conflicts.md

[[2026-05-23]] Sat 16:09
QA blocked before creating temporary access rules. Verified target Access-Rule Conflicts UI is missing: /audits/access-rule-conflicts and /members-access/conflicts render blank; code search found no route/component/REST endpoint. Logged issue #134. Did not create overlapping rules because the expected conflict/resolution UI is absent and cleanup would be manual.

[[2026-05-24]] Sun 13:12
Issue #134 fixed. Access-Rule Conflicts UI is reachable at /wp-admin/admin.php?page=arraysubs-mainadmin#/audits/access-rule-conflicts and /#/members-access/conflicts. Verified with temporary /premium fixture: REST detected URL rule Block /premium prefix vs per-post override on /premium/article-1, UI showed priority order and per-post winner, and Disable URL Rule resolved the conflict. Screenshots: qa/artifacts/issue-134-access-rule-conflicts/01-conflict-detected.png, 02-disable-confirm.png, 03-after-disable.png. Audit note #2815 recorded the resolution. Cleanup complete: temp URL rule removed and article #2811 override meta deleted.
