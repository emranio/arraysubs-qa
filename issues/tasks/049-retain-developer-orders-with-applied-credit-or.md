---
id: 49
title: Retain developer orders with applied credit or transaction evidence
status: closed
priority: high
created: 2026-10-18T17:14:49.375548+06:00
updated: 2026-10-18T17:15:51.746245+06:00
started: 2026-10-18T17:15:51.727234+06:00
completed: 2026-10-18T17:15:51.746245+06:00
tags:
    - bug
    - developer-api
    - regression
class: standard
---

Active QA task: Task 7. Scheduled day: 2026-10-18 (Asia/Dhaka).
Plan: qa/developer-support-2026-10-18/plan.md.
Site/route: http://localhost:10013/. Trusted developer PHP APIs via WP-CLI after wp_loaded; no browser/REST route. Actor: admin user 1. Context: HPOS enabled, verified with Pro active and --skip-plugins=arraysubspro.
Affected subscription IDs: N/A (these cases exercise order API identity/filtering).
Affected WordPress customers: N/A, disposable customer fixtures were cleaned in finally; role customer, distinct generated subscription-order-* logins and example.test emails.

Affected order IDs: N/A, the failing deletion calls removed their disposable fixtures before final cleanup. One fixture contained _arraysubs_credit_applied=2 and _arraysubs_credit_from_customer=2 without a split-plan marker; another contained transaction_id=qa-failed-payment-proof with no paid date.

Reproduction:
1. Create an unreferenced pending order owned by the test customer.
2. Store positive applied-credit metadata while leaving _arraysubs_credit_plan absent; call Orders::fromId(id)->delete().
3. Repeat with a fresh pending order containing a nonempty gateway transaction ID but no paid date.
Expected: unsupported_operation and retained order in both cases because financial/recovery evidence exists.
Actual: both delete calls succeed and permanently remove the order.
Proof: subscription-order-api-regression-results.txt and subscription-order-api-regression-core-results.txt recorded FAIL: Stored applied credit remains financial evidence without a split-plan marker, and FAIL: A gateway transaction reference prevents generic order deletion. Post-call wc_get_order() returned false for both records.
Known scope: paid-date, refund, subscription-reference, _arraysubs_credit_plan and _arraysubs_credit_refund_pending guards already pass. The missing guards are positive applied-credit facts and transaction references when their other markers are absent.
Fix plan: extend the same locked/reloaded order evidence checks to these persisted financial facts; do not introduce a new deletion path. Root owns the patch. Rerun both matrices before closing.

[[2026-10-18]] Sun 17:15
Verified fixed on 2026-10-18: locked/reloaded order deletion now also retains a nonempty transaction_id and positive _arraysubs_credit_applied amount. Both missing-marker financial-evidence cases pass with Pro active and with Pro skipped; normal disposable unpaid-order deletion still passes. Full matrices 70/70 each on HPOS, fixtures cleaned. Evidence: qa/developer-support-2026-10-18/subscription-order-api-regression-results.txt and subscription-order-api-regression-core-results.txt.
