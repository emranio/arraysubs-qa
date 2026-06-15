---
id: 131
title: 'stage-14: 01 All Subscriptions List and Filters'
status: closed
priority: medium
created: 2026-05-19T22:56:17.471880384+02:00
updated: 2026-05-24T14:24:53.322507972+02:00
started: 2026-05-23T08:06:53.443954376+02:00
completed: 2026-05-23T11:15:25.425163138+02:00
tags:
    - qa
    - stage-14
class: standard
---

Source: stages/14-admin-subscriptions/01-all-subscriptions-list-and-filters.md

[[2026-05-23]] Sat 11:15
QA notes: Browser-tested admin and shop manager subscription list. Status filters counts visible: All 29, Active 17, Pending 2, On Hold 3, Cancelled 3, Expired 1, Trial 3. Filters show matching status rows. All page DOM row count 20, pagination 1 of 2/2 of 2 with page 2 9 implied; accessibility undercounted but JS marker confirmed 20 rows on page 1. Active/trial rows hide Delete; non-active rows can show Delete. Add New Subscription and Export CSV visible. Product search Standard Weekly returns no results as expected. Shop manager can access list with same filters/columns. Logged issues #90 column contract mismatch, #91 email search/clear reset failure, #92 URL state not updating for filters/pagination. Debug log tail showed no new product fatal/warning during this task.

[[2026-05-24]] Sun 14:09
Fix verification 2026-05-24 for issue #90: Subscriptions admin list now renders five documented headers in order: Status, Date, Customer, Product, Next Payment. Title is removed from this screen only; row hover actions remain available under the first visible column. agent-browser extracted first_row_actions='View Details|Edit|Delete'. Screenshot: qa/artifacts/issue-90-subscriptions-columns.png. Build: npm run build passed.

[[2026-05-24]] Sun 14:17
Fix verification 2026-05-24 for issue #91: Subscriptions admin search now uses customer_search only. Browser retest on cust3@test.local returned one matching row with cust3@test.local, no empty state. Clearing the search restored 20 rows on page 1. Screenshots: qa/artifacts/issue-91-email-search-success.png, qa/artifacts/issue-91-search-clear-reset.png. Build: npm run build passed.

[[2026-05-24]] Sun 14:24
Fix verification 2026-05-24 for issue #92: Subscriptions list now syncs status/page/search to the hash route query. Active tab changed URL to #/subscriptions?status=arraysubs-active; pagination next changed URL to #/subscriptions?status=arraysubs-active&page=2 and showed 2 of 10. Reloading that shared URL restored Active selected and page 2 of 10. Screenshots: qa/artifacts/issue-92-active-page2-url-state.png, qa/artifacts/issue-92-shared-url-restores-page2.png. Build: npm run build passed.
