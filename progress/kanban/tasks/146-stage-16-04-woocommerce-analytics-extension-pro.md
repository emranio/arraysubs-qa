---
id: 146
title: 'stage-16: 04 WooCommerce Analytics Extension *(Pro)*'
status: closed
priority: medium
created: 2026-05-19T22:56:20.01809365+02:00
updated: 2026-05-24T18:37:55.679817206+02:00
started: 2026-05-23T08:06:53.457324414+02:00
completed: 2026-05-23T13:43:38.151282474+02:00
tags:
    - qa
    - stage-16
class: standard
---

Source: stages/16-analytics/04-woocommerce-analytics-extension-pro.md

[[2026-05-23]] Sat 13:43
QA complete (2026-05-23, admin browser via Alumnium).

Orders report:
- Type column visible after Status. Observed seeded types: Subs Purchase, Subs Renew, Subs Upgrade, Subs Trial, credit purchase, Other.
- Quick Type filter options present: All Types, Subs Renew, Subs Purchase, Subs Upgrade, Credit Purchase, Subs Trial, Other. Subs Renew quick filter returned only Subs Renew rows; summary Orders 4, net sales $220.00.
- Advanced Type Is with Subs Renew + Subs Upgrade returned 5 rows and net sales $232.34.
- Advanced Type Is Not Other returned non-Other rows including subscription and credit rows.
- Issue #117 logged: Credit Purchase row label stored/rendered lowercase as `credit purchase`.

Revenue report:
- Expected subscription cards/metrics absent from summary row and Choose values menu. Issue #118 logged.

Products/Variations:
- Products Product Type filter present. All Products showed 12 products/$977.83; Subscription Products Only showed 11 products/$962.83 and removed Standard Tee.
- Seeded variation fixture for missing prereq: subscription variation order #1325 (Coffee Plan - Weekly, $14.99) and regular variation order #1327 ([QA] Analytics Non-Sub Variable - Plain, $9.99).
- Variations All Variations showed both rows, 2 variations/$24.98. Subscription Variations Only showed only Coffee Plan - Weekly, 1 variation/$14.99; non-sub variation disappeared.

Customers:
- customer1 row renders Member details link to `admin.php?page=arraysubs-mainadmin#/manage-members/32`. Direct click via pointer hit Alumnium out-of-bounds, JS click on same rendered link navigated successfully. Manage Members profile loaded for Customer One.

Browser verification complete. Issues: #117, #118.

[[2026-05-24]] Sun 18:25
Issue #117 fixed: Credit Purchase order type is now title-case in stored type values, labels, and Analytics Orders filter values. Updated existing QA HPOS meta. Verified via TypeResolver syntax, pro build, WC Analytics REST data, and WooCommerce Orders UI screenshot qa/artifacts/issue-117/wc-orders-credit-purchase-title-case.png.

[[2026-05-24]] Sun 18:37
Issue #118 fixed: Revenue report custom subscription amount cards are visible and also registered as table/value metrics. Verified build, Alumnium Revenue summary/table text, and Playwright card click + values menu; screenshot qa/artifacts/issue-118/revenue-custom-cards-columns.png.
