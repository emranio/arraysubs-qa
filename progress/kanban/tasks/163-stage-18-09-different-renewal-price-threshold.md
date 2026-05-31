---
id: 163
title: 'stage-18: 09 Different Renewal Price Threshold'
status: closed
priority: high
created: 2026-05-19T22:56:22.974067049+02:00
updated: 2026-05-23T17:58:03.99861755+02:00
started: 2026-05-23T08:06:53.475681055+02:00
completed: 2026-05-23T17:58:03.998616639+02:00
tags:
    - qa
    - stage-18
claimed_by: mold-glade
claimed_at: 2026-05-23T17:58:03.99861746+02:00
class: standard
---

Source: stages/18-renewal-followup/09-different-renewal-price-threshold.md

[[2026-05-23]] Sat 17:45
Starting QA with manual BACS method. Existing Stepped Weekly product #209 is configured 9.99/week with different renewal price 9.99 after 3. Created/reset user #46 member-stepped@example.com and subscription #1587 Active, completed_payments=0, recurring=19.99, different=29.99 after=3.

[[2026-05-23]] Sat 17:57
QA complete. Manual BACS method used. Fixture: user #46 member-stepped@example.com, product #209 Stepped Weekly, subscription #1587. Baseline browser verified Active, recurring 9.99/week, Different Renewal Price 9.99 after 3, completed_payments=0, product Subscription tab Week/1/renewal price 29.99 after 3. Ran per-sub scheduled actions to avoid collateral renewals: Generate Renewal Invoice + Process Renewal for each cycle, then manually completed BACS renewal orders. Observed renewal totals/counter: #1593 9.99 counter 0->1; #1600 9.99 1->2; #1607 9.99 2->3 and recurring meta switched to 29.99; #1614 9.99 3->4; #1622 /bin/bash.00 with 9.99 store credit applied, counter stayed 4; #1630 9.99 4->5. Final #1587 Active, _recurring_amount=29.99, completed_payments=5, next_payment=2026-05-30 14:51:16 UTC, no pending renewal order. Browser admin verified order history totals and billing card. Scheduled-Job Logs verified success rows for Generate Renewal Invoice and Process Renewal #1587. Activity Audits verified renewal invoice, order status, and payment email rows for all renewal orders. Customer portal verified Active Stepped Weekly at 9.99 every week. Product price lock checked by temp changing #209 price to 39.99; subscription stored recurring/different prices stayed 29.99; product restored to 19.99. Mail body verification remains blocked by existing issue #137.
