---
id: 144
title: 'stage-16: 02 Subscription Performance Dashboard *(Pro)*'
status: closed
priority: medium
created: 2026-05-19T22:56:19.566255054+02:00
updated: 2026-05-24T18:05:42.767250874+02:00
started: 2026-05-23T08:06:53.45580531+02:00
completed: 2026-05-23T13:09:03.810669641+02:00
tags:
    - qa
    - stage-16
class: standard
---

Source: stages/16-analytics/02-subscription-performance-dashboard-pro.md

[[2026-05-23]] Sat 13:08
QA complete. Browser verified Subscription Performance KPI grid on WC Analytics Overview. Month-to-date cards: Active 17, MRR ,301.55, New 33, Churned 3, Churn Rate 9.1%, Trial Conversions 0.0%, Renewal Revenue 20, Revenue at Risk ,943.35, ARPU 00.12, Retention Saves 1. Active count reconciled after fixing QA fixture #1306 missing _start_date. Backend/manual checks: active tab/status count 17; revenue at risk rows #115, #653, #683 sum ,943.35; renewal revenue 20; week period changed values (New 27, Renewal 0, Churn 9.7%), so date range updates. Issues logged: #113 Revenue at Risk shows +100% delta; #114 MRR weekly multiplier uses 4.35 vs QA/manual 4.333; #115 filtered KPI card links navigate to access denied.

[[2026-05-24]] Sun 17:50
Issue #113 fixed: Revenue at Risk is now rendered as a snapshot KPI with no previous-period delta. Verified on Woo Analytics Overview. Screenshot qa/artifacts/issue-113/revenue-at-risk-no-delta.png.

[[2026-05-24]] Sun 17:57
Issue #114 fixed: weekly MRR normalization now uses 4.333 in pro analytics. Verified REST MRR for May 1-23 returned 9301.31 and browser MRR for Month to date May 1-24 showed 0,189.15, matching manual active/trial subscription sum. Screenshot qa/artifacts/issue-114/mrr-weekly-4333.png.

[[2026-05-24]] Sun 18:05
Issue #115 fixed: Dashboard KPI links now open WC Analytics Orders with filter params outside the encoded path and valid ArraySubs Type values. Alumnium/Playwright verified Active Subscriptions opens Orders without access denied; screenshot qa/artifacts/issue-115/active-subscriptions-orders-filter.png.
