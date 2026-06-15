---
id: 148
title: 'stage-17: 01 Activity Audits'
status: closed
priority: medium
created: 2026-05-19T22:56:20.498066391+02:00
updated: 2026-05-24T19:43:59.367333922+02:00
started: 2026-05-23T08:06:53.461316366+02:00
completed: 2026-05-23T14:36:10.852309012+02:00
tags:
    - qa
    - stage-17
class: standard
---

Source: stages/17-audits-and-logs/01-activity-audits.md

[[2026-05-23]] Sat 14:36
QA complete. Activity Audits columns/pagination/change modal/author filters/search/date filter passed. Date filter verified by browser JS date-input events: 2026-05-22..2026-05-23 reduced total 808 -> 344. Settings audit row created but latest settings save lacked changes link/valid changes data (#121). Customer cancellation from portal failed before audit row could be generated (#122). Shop manager product edit created note #1339 and change modal showed regular price 9.99 -> 0.99, but Admin+Product filters hide that row (#123). Product price restored to 9.99; sub #697 remained active. Related entity filter bug also logged #120.

[[2026-05-24]] Sun 18:56
Issue #120 fixed: Activity Audits Entity Type filter now matches stored/displayed entity badges. Verified REST and browser Subscription filter: first 10 badges SUBSCRIPTION, total 922. Screenshot qa/artifacts/issue-120/activity-audits-subscription-filter.png.

[[2026-05-24]] Sun 19:10
Issue #121 fixed: settings audit change details now persist/decode and the Activity Audits changes link is visible/clickable. Verified REST partial save preserved unrelated settings, note #2966 decoded emails.renewal_upcoming.days_before 11 -> 3, and agent-browser opened modal with Previous Value/Changed Value. Screenshot qa/artifacts/issue-121/settings-audit-changes-modal.png.

[[2026-05-24]] Sun 19:40
Issue #122 fixed and closed. Customer cancellation modal retested as member1 on subscription #697: UI returned HTTP 200, showed Pending Cancellation, no Failed to cancel error. Activity Audits now records the scheduled cancellation as Customer/member1 with structured changes and visible changes -> modal. Fixture #697 restored to active afterward.

[[2026-05-24]] Sun 19:43
Issue #123 verified and closed. Activity Audits Author=Admin + Entity=Product now shows shop_manager_qa product update rows for Product #197, and the changes modal displays regular price $29.99 -> $30.99. No code change needed on current branch.
