---
id: 160
title: stage-19 credit refund missing on customer order detail
status: closed
priority: medium
created: 2026-05-23T20:08:40.998763285+02:00
updated: 2026-05-24T23:10:49.963162946+02:00
started: 2026-05-24T23:07:35.006825949+02:00
completed: 2026-05-24T23:10:44.958535725+02:00
tags:
    - qa
    - stage-19
    - store-credit
    - customer-portal
    - refund
class: standard
---

Task: stages/19-refunds/05-refund-to-credit-pro.md

Fixture: cust5@example.com, one-time Stripe order #1750 total 0, store credit refund log #1753, _refunded_as_credit=40, order note 'Refund issued as store credit: 0.00. QA refund-to-credit full order'.

Expected: Customer My Account → Orders → order #1750 detail should indicate that a credit refund was processed, e.g. 'Refunded as store credit: 0.00'.

Observed with agent-browser: My Account Orders listed order #1750 as Completed 0.00 for 4 items. Order detail showed product Plain Mug x4, subtotal 0.00, total 0.00, payment method Stripe, billing address, but no store credit refund wording, note, or refund history. My Account → Store Credit did show balance 0.00 and the refund transaction, so backend credit exists.

Impact: customers cannot see on the order itself that the paid order was refunded as store credit.

[[2026-05-24]] Sun 23:08
Plan: inspect Store Credit refund metadata and customer My Account hooks, add a customer-visible order-detail summary for credit refunds using existing order meta, then verify as cust5/order #1750 in browser with screenshot. No gateway refund processing.

[[2026-05-24]] Sun 23:10
Fix: RefundIntegration now hooks woocommerce_order_details_after_order_table and renders a customer-visible Store Credit Refund section from order meta. It shows total _refunded_as_credit and structured refund rows when available. Verification: php -l passed; FPM reloaded; reset cust5@example.com password for QA; browser as cust5 opened /my-account/view-order/1750/ and saw 'Store Credit Refund' plus 'Refunded as store credit: 0.00'. Screenshot: qa/artifacts/issue-160/customer-order-1750-store-credit-refund-summary.png.
