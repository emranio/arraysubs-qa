---
id: 91
title: 'stage-14: Subscription search by email returns no results'
status: closed
priority: high
created: 2026-05-23T11:10:08.607438073+02:00
updated: 2026-05-24T14:17:53.946955918+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
class: standard
---

Original task: stages/14-admin-subscriptions/01-all-subscriptions-list-and-filters.md\n\nQA plan expects search by customer email and username. Browser test on /wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions: searched cust3@test.local and pressed Enter; result: No subscriptions found. Searched cust3; result: Subscription for Standard Weekly - cust3 with customer email cust3@test.local. Product search Standard Weekly returned no results as expected. Clearing a failed search left empty searchbox with No subscriptions found until page reload.\n\nExpected: email search matches customer email; clearing/submitting empty search resets list without hard reload.

[[2026-05-23]] Sat 21:39
Stage 20.05 retest under 301 subscriptions confirmed same defect. UI /subscriptions search field with perf010@example.test + Enter => No subscriptions found. REST with UI params search+customer_search total=0, but customer_search only total=1 (#1846 Subscription for Standard Weekly - Perf 010). Root suspect: DataList sends both WP post search and customer_search, so exact email is filtered out by post title search.

[[2026-05-24]] Sun 14:09
Fix pass started 2026-05-24 by shell-quartz. Verifying issue, source QA task, and search implementation before code changes.

[[2026-05-24]] Sun 14:17
Fix 2026-05-24: SubscriptionsList now sends only customer_search for its customer/name/email/username search field instead of combining WP title search + customer_search. This removes the AND filter that made exact emails return zero rows. Product-name search remains outside the customer search contract. Verified with npm run build. agent-browser confirmed cust3@test.local search shows the matching customer row and no empty-state text. agent-browser fallback verified search_rows=1, search_has_email=true, search_has_empty=false; clearing the search restored clear_rows=20 and clear_has_empty=false. Screenshots: qa/artifacts/issue-91-email-search-success.png and qa/artifacts/issue-91-search-clear-reset.png.
