---
id: 118
title: 'stage-16: Revenue analytics subscription amount cards are missing'
status: closed
priority: high
created: 2026-05-23T13:29:38.151024011+02:00
updated: 2026-05-24T18:37:48.085614514+02:00
started: 2026-05-24T18:25:32.909706826+02:00
completed: 2026-05-24T18:37:48.085613492+02:00
tags:
    - qa
    - stage-16
    - analytics
claimed_by: shell-quartz
claimed_at: 2026-05-24T18:37:48.085614413+02:00
class: standard
---

Original task: progress #146, Stage 16 Task 04 WooCommerce Analytics Extension, sub-task 4.5/4.6.

Expected: WooCommerce Analytics Revenue summary/cards and values menu expose `Total Subs Renew Amount`, `Total Subs Upgrade Amount`, and `Total Credit Purchase Amount`; clicking a card adds the metric column to the report table.
Observed: Revenue report for 2026-05-01..2026-05-23 only showed standard WC cards/values (`Orders`, `Gross sales`, `Returns`, `Coupons`, `Net sales`, `Taxes`, `Shipping`, `Total sales`). Custom subscription metrics were absent from summary row and the Choose values menu.
Impact: revenue extension metrics cannot be viewed or charted.
Evidence: browser session `plugins-1779533151`, Revenue values menu open; related code appears in `arraysubspro/src/resources/analyticsRevenue.js` via `woocommerce_admin_revenue_report_charts` filter.

[[2026-05-24]] Sun 18:37
Fix applied: Revenue analytics JS now registers the three ArraySubs metrics as chart cards and as Revenue report table headers/cells, with currency-formatted values. The custom headers are exposed to the values menu and selected custom card URLs keep the matching chart key. Verification: npm run build passed in arraysubspro. Alumnium confirmed Revenue report shows Total Subs Renew Amount, Total Subs Upgrade Amount, and Total Credit Purchase Amount in summary/cards/table columns. Playwright confirmed all three names visible, clicking Total Subs Renew Amount updates URL to chart=arraysubs_total_subs_renew_amount, values menu contains all three metrics, and screenshot captured qa/artifacts/issue-118/revenue-custom-cards-columns.png.
