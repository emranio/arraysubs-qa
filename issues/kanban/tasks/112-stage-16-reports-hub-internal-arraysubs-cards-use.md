---
id: 112
title: 'stage-16: Reports Hub internal ArraySubs cards use inaccessible page slug'
status: closed
priority: high
created: 2026-05-23T13:02:03.492314436+02:00
updated: 2026-05-24T17:46:24.582708076+02:00
started: 2026-05-24T17:42:07.893569651+02:00
completed: 2026-05-24T17:46:24.582706984+02:00
tags:
    - qa
    - stage-16
    - bug
claimed_by: shell-quartz
claimed_at: 2026-05-24T17:46:24.582707965+02:00
class: standard
---

Original task: stage-16 task 01 Reports Hub, Sub-Task 1.3 card links.\n\nObserved: From Reports Hub as admin, clicking the Free 'All Subscriptions List' card navigates to https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs#/subscriptions and shows WordPress error 'Sorry, you are not allowed to access this page.'\n\nExpected: Card should open the ArraySubs subscriptions page. In this environment the working admin app route is page=arraysubs-mainadmin#/subscriptions.\n\nLikely affected URLs visible in accessibility tree: Subscriptions, Member Insights, Store Credit, Audit Logs cards use admin.php?page=arraysubs#... while active app is admin.php?page=arraysubs-mainadmin#....

[[2026-05-24]] Sun 17:46
Fixed Reports Hub ArraySubs card URLs by switching arraySubsUrl() from page=arraysubs to canonical page=arraysubs-mainadmin. Verified All Subscriptions List report card href and popup URL are https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions; page loaded Manage customer subscriptions with no access denied. Screenshot qa/artifacts/issue-112/subscriptions-card-mainadmin-loaded.png. npm run build passed.
