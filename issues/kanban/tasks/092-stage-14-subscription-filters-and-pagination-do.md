---
id: 92
title: 'stage-14: Subscription filters and pagination do not update URL state'
status: closed
priority: medium
created: 2026-05-23T11:10:08.629270635+02:00
updated: 2026-05-24T14:24:53.421712027+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
class: standard
---

Original task: stages/14-admin-subscriptions/01-all-subscriptions-list-and-filters.md\n\nQA plan expects status filters to update URL/query string so views are shareable/bookmarkable. Browser test: clicking Active, Pending, On Hold, Cancelled, Expired, Trial, All did not change URL. Pagination from 1 of 2 to 2 of 2 also did not add page state to URL.\n\nExpected: selected status and pagination/search state represented in URL or otherwise shareable per QA plan.

[[2026-05-24]] Sun 14:18
Fix pass started 2026-05-24 by shell-quartz. Verifying issue, source QA task, and DataList filter/pagination state handling before code changes.

[[2026-05-24]] Sun 14:24
Fix 2026-05-24: DataList now supports opt-in syncUrlState using HashRouter query params. SubscriptionsList enables it, syncing status, page, and search in the route URL while other DataList screens keep current behavior. Verified with npm run build. Alumnium confirmed Active tab updates URL to #/subscriptions?status=arraysubs-active. Playwright confirmed page 2 updates URL to #/subscriptions?status=arraysubs-active&page=2, page indicator shows 2 of 10, and loading that shared URL restores Active selected with page 2 of 10. Screenshots: qa/artifacts/issue-92-active-page2-url-state.png and qa/artifacts/issue-92-shared-url-restores-page2.png.
