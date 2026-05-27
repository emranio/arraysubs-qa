---
id: 145
title: 'stage-16: 03 Performance Charts *(Pro)*'
status: closed
priority: medium
created: 2026-05-19T22:56:19.777748364+02:00
updated: 2026-05-24T18:11:59.260220284+02:00
started: 2026-05-23T08:06:53.456575942+02:00
completed: 2026-05-23T13:14:53.514542555+02:00
tags:
    - qa
    - stage-16
class: standard
---

Source: stages/16-analytics/03-performance-charts.md

[[2026-05-23]] Sat 13:14
QA complete. Browser verified subscription chart section on WC Analytics Overview: six tabs visible and all redraw (MRR, Net Subscription Growth, Renewal Revenue, Churn Rate, Trial Conversion Rate, Active Subscriptions). Week range auto daily labels 05-16..05-22; 30-day custom range auto weekly labels 04-23/04-30/05-07/05-14/05-21; 90-day custom range auto monthly labels 02-22/03-22/04-22/05-22; 400-day custom range auto quarterly-ish 3-month labels 04-18/07-18/10-18/01-18/04-18. Empty Jan 2024 range shows zero values/no errors. Issue logged: #116 no manual Day/Week/Month/Quarter/Year interval selector for subscription charts. Known MRR formula defect covered by #114.

[[2026-05-24]] Sun 18:11
Issue #116 fixed: Subscription Charts now have manual Day/Week/Month/Quarter/Year controls. Verified build, Alumnium interval state, and Playwright REST calls for all intervals; screenshot qa/artifacts/issue-116/chart-interval-year-selected.png.
