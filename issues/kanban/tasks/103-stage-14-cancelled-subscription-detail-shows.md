---
id: 103
title: 'stage-14: Cancelled subscription detail shows vacation mode as active'
status: closed
priority: medium
created: 2026-05-23T12:06:44.010491201+02:00
updated: 2026-05-24T16:15:00.276394928+02:00
started: 2026-05-24T16:07:33.070030606+02:00
completed: 2026-05-24T16:15:00.276393946+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
    - detail-cards
claimed_by: shell-quartz
claimed_at: 2026-05-24T16:15:00.276394837+02:00
class: standard
---

Stage 14 task 08: cancelled subscriptions #1135 and #508 still show Skip & Pause > Vacation Mode text 'Subscription is active' on the detail page. The header/status and cancellation card correctly show Cancelled, so this card state is inconsistent after cancellation.

[[2026-05-23]] Sat 19:35
Stage 19 Task 03 reproduced on EOP-cancelled subscription #1719. Fresh admin detail showed status CANCELLED and cancellation details, but Skip & Pause > Vacation Mode still read 'Subscription is active'.

[[2026-05-23]] Sat 20:20
Stage 19 Task 06 repeat: cancelled subscription #1758 detail still shows Skip & Pause / Vacation Mode panel text 'Subscription is active'.

[[2026-05-24]] Sun 16:07
Fix pass started 2026-05-24 by shell-quartz. Verifying Stage 14 detail-card expectations and tracing Skip & Pause / vacation-mode display logic for cancelled subscriptions.

[[2026-05-24]] Sun 16:14
Fixed in arraysubs admin SkipPauseCard: vacation-mode idle copy is now status-aware, so cancelled/expired/pending subscriptions no longer show 'Subscription is active'. Verified on cancelled subscription #508 with agent-browser text extraction ('Subscription is cancelled') and agent-browser assertion activeTextCount=0. Screenshot: qa/artifacts/issue-103-cancelled-vacation-mode.png. Build passed: npm run build in arraysubs.
