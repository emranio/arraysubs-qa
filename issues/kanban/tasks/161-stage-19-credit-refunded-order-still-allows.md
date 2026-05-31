---
id: 161
title: stage-19 credit-refunded order still allows gateway refund
status: closed
priority: high
created: 2026-05-23T20:09:05.725809815+02:00
updated: 2026-05-24T23:20:40.624146354+02:00
started: 2026-05-24T23:10:56.040661101+02:00
completed: 2026-05-24T23:20:34.813610559+02:00
tags:
    - qa
    - stage-19
    - store-credit
    - refund
    - double-refund
class: standard
---

Task: stages/19-refunds/05-refund-to-credit-pro.md

Fixture: cust5@example.com, order #1750 total 0, store credit refund already processed for 0 (_refunded_as_credit=40, credit log #1753, WC get_total_refunded=0 by design).

Expected: after the order is fully refunded as store credit, another refund attempt should be blocked/rejected because max refundable is /bin/bash.

Observed in WooCommerce order edit browser after fresh reload and clicking Refund: the store-credit method was no longer visible, but WooCommerce refund UI still showed Amount already refunded -/bin/bash.00, Total available to refund 0.00, and enabled gateway/manual refund controls. Backend store-credit private getRefundableAmount() returns 0 because it subtracts _refunded_as_credit, but that protection is not applied to WooCommerce's standard gateway/manual refund UI.

Impact: merchant can issue a 0 store-credit refund and then still issue a 0 Stripe/manual refund from the same order, causing a double refund/credit.

[[2026-05-24]] Sun 23:20
Plan: extend Store Credit refund integration so credit-aware refundable amount also protects WooCommerce's native gateway/manual refund path. Add a priority-0 AJAX guard for woocommerce_refund_line_items, pass credit-refund state to the order-edit asset, disable standard refund buttons when store-credit refunds consumed the order total, and verify in the real Woo order edit UI.\n\nFix: RefundIntegration now blocks native Woo gateway/manual refunds when _refunded_as_credit leaves no credit-aware refundable amount, with nonce/capability checks. The order edit script now disables Refund via Stripe and Refund manually, adds the visible message 'This order has already been fully refunded as store credit. No further gateway or manual refund is available.', and keeps native confirm/alert out of the store-credit path.\n\nVerification: php -l passed. Backend AJAX guard with admin nonce for order #1750/refund_amount=1 returned JSON error with remaining refundable /bin/bash.00. Alumnium opened Woo order #1750 and confirmed after clicking Refund that both standard refund buttons are disabled and the store-credit block message is shown. Playwright screenshot: qa/artifacts/issue-161/order-1750-standard-refund-blocked-after-credit.png.
