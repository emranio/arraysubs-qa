---
id: 143
title: 'stage-16: 01 Reports Hub'
status: closed
priority: medium
created: 2026-05-19T22:56:19.384730975+02:00
updated: 2026-05-24T17:46:35.128556637+02:00
started: 2026-05-23T08:06:53.455076746+02:00
completed: 2026-05-23T13:02:16.101847191+02:00
tags:
    - qa
    - stage-16
class: standard
---

Source: stages/16-analytics/01-reports-hub.md

[[2026-05-23]] Sat 13:02
QA complete. Admin: Reports Hub loaded with summary 12 report categories, 43 total reports, 9 free, 34 pro. All 12 categories present with expected counts: 7/5/6/6/3/2/1/3/3/2/2/3; card titles/descriptions/badges present. Shop manager fixture created (shop@arraysubs.test) and verified hub loads with same summary. Customer fixture password reset for QA and verified customer is blocked with WordPress access-denied page. Issues logged: #111 quick-nav scroll does not update URL hash; #112 internal ArraySubs cards use inaccessible page=arraysubs slug and show access denied.

[[2026-05-24]] Sun 17:41
Issue #111 fixed: Reports Hub quick-nav pills now update shareable hashes like #/reports#retention-analytics and direct hashes auto-scroll. Verified with Playwright after Alumnium timeout. Screenshot qa/artifacts/issue-111/reports-retention-hash.png.

[[2026-05-24]] Sun 17:46
Issue #112 fixed: Reports Hub internal ArraySubs cards now use page=arraysubs-mainadmin. Verified All Subscriptions List opens subscriptions app without access denied. Screenshot qa/artifacts/issue-112/subscriptions-card-mainadmin-loaded.png.
