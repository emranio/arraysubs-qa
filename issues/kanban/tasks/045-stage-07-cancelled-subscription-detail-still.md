---
id: 45
title: 'stage-07: Cancelled subscription detail still renders Subscription Actions'
status: closed
priority: medium
created: 2026-05-20T14:41:08.313942911+02:00
updated: 2026-05-22T05:31:07.684712218+02:00
started: 2026-05-22T05:29:37.304089287+02:00
completed: 2026-05-22T05:31:07.684711016+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - status-actions
claimed_by: mold-glade
claimed_at: 2026-05-22T05:31:07.684712118+02:00
class: standard
---

Stage 07 Task 02 regression expects a Cancelled subscription to hide the Subscription Actions section entirely. On cust1 cancelled subscription #668, the detail page renders Subscription Actions with a Reactivate Subscription button. Expected per task/manual: no Subscription Actions section for Cancelled status, and Reactivate should not render in the free portal template.

[[2026-05-22]] Fri 05:30
Plan: verify Stage 07 Task 02 regression and current #668 state; inspect portal action gating and reactivation helper; browser-test #668 as cust1. If Subscription Actions/Reactivate are absent, close as already fixed by terminal reactivation gating; otherwise patch view/helper and retest.

[[2026-05-22]] Fri 05:30
Verified fixed by current action gating. WP-CLI #668 status=arraysubs-cancelled and arraysubs_can_customer_reactivate_by_subs_id=no. agent-browser cust1 check: #668 detail shows no Subscription Actions section and no Reactivate Subscription button. No new code change needed for this issue.
