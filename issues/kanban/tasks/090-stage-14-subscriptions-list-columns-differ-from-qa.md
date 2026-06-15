---
id: 90
title: 'stage-14: Subscriptions list columns differ from QA contract'
status: closed
priority: medium
created: 2026-05-23T11:10:08.583475854+02:00
updated: 2026-05-24T14:09:00.553042344+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
class: standard
---

Original task: stages/14-admin-subscriptions/01-all-subscriptions-list-and-filters.md\n\nQA plan expected columns in order: Status, Date, Customer, Product, Next Payment. Browser test showed table headers: Title, Status, Customer, Product, Next Payment, Date.\n\nImpact: admin list does not match documented Stage 14 column contract/order.

[[2026-05-24]] Sun 14:02
Fix pass started 2026-05-24 by shell-quartz. Verifying issue, source QA task, and stage plan before code changes.

[[2026-05-24]] Sun 14:09
Fix 2026-05-24: SubscriptionsList now opts out of the generic Title column and passes explicit columnOrder [status, date, _customer_display, _product_display, _next_payment_display]. DataList keeps legacy title-first behavior by default for other screens, and TableBody now renders row actions under the first visible column when a table intentionally hides Title. Verified with npm run build. agent-browser admin check confirmed headers Status, Date, Customer, Product, Next Payment with no Title header. agent-browser proof extracted headers=[Status,Date,Customer,Product,Next Payment] and first_row_actions='View Details|Edit|Delete'; screenshot qa/artifacts/issue-90-subscriptions-columns.png.
