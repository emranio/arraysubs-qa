---
id: 77
title: 'stage-06: 03 Subscription Detail Screen — Cards Verification'
status: closed
priority: high
created: 2026-05-19T22:56:08.784564276+02:00
updated: 2026-05-22T05:10:39.207999702+02:00
started: 2026-05-20T13:41:52.826492719+02:00
completed: 2026-05-20T14:00:45.60870037+02:00
tags:
    - qa
    - stage-06
class: standard
---

Source: stages/06-initial-lifecycle/03-subscription-detail-screen.md

[[2026-05-20]] Wed 14:00
QA complete using active control subscription #618 (Basic Monthly/customer-pending) because Stage 05 Task 01 classic artifact is missing (#35). Header: Subscription for Basic Monthly - customer-pending, ACTIVE, actions Cancel Subscription/Edit Subscription/Login as Customer, no end-of-period cancel notice observed. Subscription Info: ID #618, created/start 20 May 2026 5:53 PM UTC+6, next payment 20 June 2026 5:53 PM UTC+6, last payment 20 May 2026 5:55 PM UTC+6, total paid 9.99, no trial/end date shown. Customer Info: customer-pending, customer-pending@example.test, invoice email same, registered 20 May 2026 5:50 PM. Product: Basic Monthly, SKU -, quantity 1. Billing: recurring 9.99, Every 1 month(s), completed payments 1, Direct bank transfer. Billing/shipping address both show Pending Customer, 76 Pending St, Austin TX 78701, US, email present; phone blank. Order History: #610, completed, 9.99, refunded -, type Initial, View Order. Payment Timeline and Notes contain initial payment/status/email/created notes, but Payment Received appears duplicated; filed issue #38. Skip & Pause card visible with disabled message: features not enabled. Trial regression checked #384: Trial Weekly status Trial, Trial Length 7 day(s), Trial Ends 27 May 2026, total paid /bin/bash, completed payments 0, order #368 /bin/bash.00; task expected 14-day Trial 14-Day mismatch already filed #37.

[[2026-05-22]] Fri 05:10
Issue #38 fixed: payment-success note source now idempotent by subscription/order/event; duplicate note #628 removed from #618 fixture; browser recheck confirms one timeline event and one subscription note for Order #610.
