---
id: 116
title: 'stage-16: Subscription charts have no manual interval selector'
status: closed
priority: high
created: 2026-05-23T13:14:41.584276256+02:00
updated: 2026-05-24T18:11:53.026383685+02:00
started: 2026-05-24T18:05:57.998920238+02:00
completed: 2026-05-24T18:11:53.026382783+02:00
tags:
    - qa
    - stage-16
    - bug
claimed_by: shell-quartz
claimed_at: 2026-05-24T18:11:53.026383585+02:00
class: standard
---

Original task: stage-16 task 03 Performance Charts, Sub-Task 3.7.\n\nObserved: Subscription Charts section shows six chart tabs and auto-selects interval from date range, but there is no Day / Week / Month / Quarter / Year interval selector in the subscription chart UI. Browser extraction found no manual interval selector; only WooCommerce's standard chart period control with By day / By week exists for the standard WC charts, not for subscription charts.\n\nExpected: All five interval options should be manually selectable and each selection should redraw the subscription chart.\n\nLikely cause: arraysubspro/src/resources/pages/AnalyticsOverview/chartConfigs.js always calls getAutoInterval(after, before) and does not render interval state/control.

[[2026-05-24]] Sun 18:11
Fix applied: Subscription Charts now render Day/Week/Month/Quarter/Year interval buttons, auto-select the interval from the current date range, and refetch chart data when chart tab, interval, or WC Admin date URL changes. Verification: npm run build passed in arraysubspro. Alumnium confirmed all five interval controls, Week auto-selected for Month to date, Day and Year selections activated and chart still had bars. Playwright verified chart REST calls returned 200 for intervals week, day, month, quarter, year and captured qa/artifacts/issue-116/chart-interval-year-selected.png.
