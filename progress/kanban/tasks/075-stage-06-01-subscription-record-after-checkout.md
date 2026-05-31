---
id: 75
title: 'stage-06: 01 Subscription Record After Checkout'
status: closed
priority: high
created: 2026-05-19T22:56:08.534705994+02:00
updated: 2026-05-22T05:03:46.109131952+02:00
started: 2026-05-20T13:41:52.823778235+02:00
completed: 2026-05-20T13:49:18.707065044+02:00
tags:
    - qa
    - stage-06
claimed_by: mold-glade
claimed_at: 2026-05-22T05:03:46.109131441+02:00
class: standard
---

Source: stages/06-initial-lifecycle/01-subscription-record-after-checkout.md

[[2026-05-20]] Wed 13:49
QA complete with fail notes. Expected Stage 05 Task 01 artifact (customer-classic@example.test Basic/Classic subscription) is absent: Stage 05 Task 01 failed at classic checkout; WP-CLI shows user #8 exists but no subscriptions/orders; admin list search for customer-classic@example.test returned no matching row. Filed qa/issues #35. Control verification used successful Stage 05 Block Checkout subscription #357 customer-block@example.test / Standard Weekly / order #340. Admin Subscriptions list loaded: tabs All(12), Active(6), Pending(1), On Hold(2), Cancelled(1), Expired(0), Trial(2); columns Title, Status, Customer, Product, Next Payment, Date; 12 rows visible. Search customer-block row: Active, customer-block, Standard Weekly, next payment 27 May 2026 (UTC+6), last modified 20 May 2026. Row actions included View Details, Edit, Delete; Delete visible for Active, filed qa/issues #36. Detail view #357 loaded. Order History: Order #340, Initial, completed, 9.99; View Order opened Woo order edit URL id=340. Billing card: Recurring Amount 9.99, Every 1 week(s), Completed Payments 1, Direct bank transfer. Export CSV succeeded with success notice '12 subscriptions exported'; downloaded CSV has UTF-8 BOM and row #357 with Active, customer-block@example.test, Standard Weekly, 19.99, USD, 1 week, next payment 2026-05-27 09:39:25.

[[2026-05-22]] Fri 02:47
Issue #35 repaired: recreated missing Stage 05 classic artifact via classic checkout BACS after Alumnium XHR repair. New order #958 completed, subscription #959 active for customer-classic@example.test, Basic Monthly, next payment 22 June 2026. Admin browser verified list row, detail billing/order history, and Woo order link.

[[2026-05-22]] Fri 05:03
Issue #36 fixed: active/trial subscription rows now hide Delete via DataList onDeleteCheck. Browser verified subscription #357 row shows View Details/Edit only; on-hold/cancelled rows still show Delete.
