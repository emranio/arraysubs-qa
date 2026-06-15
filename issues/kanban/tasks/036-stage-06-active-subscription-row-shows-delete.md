---
id: 36
title: 'stage-06: Active subscription row shows Delete action'
status: closed
priority: medium
created: 2026-05-20T13:49:03.829882532+02:00
updated: 2026-05-22T05:03:38.095879926+02:00
started: 2026-05-22T04:57:45.800832001+02:00
completed: 2026-05-22T05:03:38.095879094+02:00
tags:
    - qa
    - stage-06
    - subscriptions
    - admin-list
    - row-actions
claimed_by: mold-glade
claimed_at: 2026-05-22T05:03:38.095879835+02:00
class: standard
---

Stage 06 Task 01 expects Delete hidden or greyed out for Active/Trial subscriptions. In ArraySubs → Subscriptions, control active subscription #357 (customer-block@example.test, Standard Weekly) showed row actions: View Details, Edit, Delete. Expected: Delete should not be available for Active subscriptions per manual/task.

[[2026-05-22]] Fri 04:58
Plan: wire DataList row actions to existing onDeleteCheck so Delete is hidden for disallowed rows before click; keep SubscriptionsList rule blocking arraysubs-active and arraysubs-trial; rebuild admin asset; verify in browser that active subscription #357 row shows View Details/Edit but no Delete.

[[2026-05-22]] Fri 05:03
Fix: DataList now passes onDeleteCheck into row actions; RowActions hides Delete when onDeleteCheck returns allowed=false. Built arraysubs admin assets with npm run build. Verification: agent-browser admin subscriptions page page 2 shows active subscription #357 customer-block row with View Details and Edit only, no Delete; active/trial rows hide Delete, on-hold/cancelled rows still show Delete.
