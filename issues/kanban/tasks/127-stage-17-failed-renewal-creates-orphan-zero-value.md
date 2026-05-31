---
id: 127
title: 'stage-17: Failed renewal creates orphan zero-value order'
status: closed
priority: high
created: 2026-05-23T15:47:25.822165772+02:00
updated: 2026-05-24T20:04:48.315763011+02:00
started: 2026-05-24T19:58:23.22067483+02:00
completed: 2026-05-24T20:04:48.315762029+02:00
tags:
    - qa
    - stage-17
    - renewals
    - orders
claimed_by: shell-quartz
claimed_at: 2026-05-24T20:04:48.31576288+02:00
class: standard
---

Task: stages/17-audits-and-logs/03-scheduled-job-logs.md\n\nDuring Sub-Task 03.6 forced failure, subscription #959 had _product_id=999999 and arraysubs_process_renewal action #1243 was run.\n\nObserved:\n- Renewal failed: #959 moved to on-hold with _last_payment_failure_reason = Failed to create renewal order.\n- A new WooCommerce order #1389 was left as wc-pending, total 0.00, billing customer-classic@example.test.\n- Order #1389 had no _subscription_id or _is_renewal_order metadata.\n- Cleanup cancelled #1389 manually.\n\nExpected: failed renewal should not leave an orphan pending zero-value order. If a placeholder order is created before validation, it should be linked and failed/cancelled/cleaned consistently.

[[2026-05-24]] Sun 19:58
Plan: move renewal product validation before wc_create_order so a missing product cannot create a placeholder order, add defensive cleanup for any partially-created order if later order construction throws, syntax-check, then reproduce the missing-product renewal and verify no new orphan zero-value order remains.

[[2026-05-24]] Sun 20:04
Fix applied: OrderCreation now validates the renewal product before wc_create_order, so a missing product cannot create a placeholder Woo order. It also force-deletes any partially-created order if a later construction error occurs. PHP syntax check passed and PHP-FPM was reloaded.

Verification: first deleted test artifact orphan order #2981 from the earlier pre-fix #126 repro. Clean rerun captured pre-test max order #2951, set #959 product to 999999, ran process-renewal action #1940, and confirmed new_orders=[] while subscription moved on-hold with failure Failed to create renewal order. Latest scheduled-job note #3007 is failed. Browser Scheduled-Job Logs shows the failed Process Renewal #959 row. Screenshot: qa/artifacts/issue-127/no-orphan-failed-renewal-row.png.

Cleanup: restored #959 to arraysubs-active, _product_id=197, _next_payment_date=2026-06-22 00:42:12, and cleared failure/retry/pending renewal meta.
