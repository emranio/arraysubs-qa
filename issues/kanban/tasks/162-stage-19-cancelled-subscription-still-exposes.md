---
id: 162
title: 'stage-19: Cancelled subscription still exposes Update payment method'
status: closed
priority: medium
created: 2026-05-23T20:33:23.750450924+02:00
updated: 2026-05-24T23:28:10.957115565+02:00
started: 2026-05-24T23:20:50.694808396+02:00
completed: 2026-05-24T23:28:02.493055928+02:00
tags:
    - qa
    - stage-19
    - customer-portal
    - refunds
    - actions
class: standard
---

Stage 19 Task 06: after full parent-order refund auto-cancelled subscription #1758, customer portal subscription detail still shows an Update payment method action/link on the cancelled subscription. Expected non-active/on-hold subscription actions hidden; task specifically verifies cancelled subscriptions cannot expose further payable/action paths. Browser: cust6@example.com, /my-account/view-subscription=1758.

[[2026-05-24]] Sun 23:27
Plan: gate the Pro gateway-specific Update payment method link and its REST update endpoint by subscription lifecycle, not only by gateway capability. Keep active/on-hold/trial subscriptions eligible when the gateway supports updates; block cancelled/expired/pending-cancel subscriptions so refunded cancelled records expose no payable path.\n\nFix: PaymentMethodCoordinator::canUpdatePaymentMethod() now first requires the subscription to be active, on-hold, or trial and not waiting cancellation before resolving gateway update capability. This hides the Pro customer portal Update payment method link and makes POST /subscriptions/{id}/payment-method/update return 403 for cancelled subscriptions.\n\nVerification: php -l passed. WP-CLI check: canUpdatePaymentMethod(1758)=false and active Stripe subscription #2591 remains true. Customer REST request as cust6 for /arraysubs/v1/subscriptions/1758/payment-method/update returned 403 with 'Payment method update is not available for this subscription.' Alumnium verified customer page Subscription #1758 shows Cancelled and no Update payment method link. Playwright screenshot: qa/artifacts/issue-162/subscription-1758-cancelled-no-update-payment-method.png.
