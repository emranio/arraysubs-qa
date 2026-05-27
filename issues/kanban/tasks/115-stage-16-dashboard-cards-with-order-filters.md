---
id: 115
title: 'stage-16: Dashboard cards with order filters navigate to access denied'
status: closed
priority: high
created: 2026-05-23T13:08:20.734882924+02:00
updated: 2026-05-24T18:05:36.31629982+02:00
started: 2026-05-24T17:58:37.848850101+02:00
completed: 2026-05-24T18:05:36.316298718+02:00
tags:
    - qa
    - stage-16
    - bug
claimed_by: shell-quartz
claimed_at: 2026-05-24T18:05:36.31629974+02:00
class: standard
---

Original task: stage-16 task 02 Subscription Performance Dashboard, Sub-Task 2.8.\n\nObserved: Clicking the Active Subscriptions KPI card navigates to https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Forders%3Farraysubs_type_is%3Dsubs_purchase%2Csubs_trial and WooCommerce shows 'Sorry, you are not allowed to access this page.'\n\nExpected: Active Subscriptions card should open Orders analytics filtered by Subs Purchase + Subs Trial.\n\nLikely affected: Active Subscriptions, New Subscriptions, Trial Conversions card hrefs include query params inside the encoded path value (performanceCards.js), so WC Admin route is malformed.

[[2026-05-24]] Sun 18:05
Fix applied: KPI card links now build WooCommerce Admin URLs with path=/analytics/orders and filter params as top-level query args instead of embedding the query string inside the encoded path. Active/New/Trial card filters now use backend-valid Type values: Subs Purchase and Subs Trial. Verification: npm run build passed in arraysubspro. Alumnium confirmed Active Subscriptions href is /admin.php?page=wc-admin&path=%2Fanalytics%2Forders&filter=advanced&arraysubs_type_is=Subs+Purchase%2CSubs+Trial, clicking it opens WooCommerce Analytics Orders with no access denied. Playwright verified Active/New/Trial hrefs and captured qa/artifacts/issue-115/active-subscriptions-orders-filter.png.
