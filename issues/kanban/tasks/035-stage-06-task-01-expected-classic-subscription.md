---
id: 35
title: 'stage-06: Task 01 expected classic subscription artifact missing'
status: closed
priority: high
created: 2026-05-20T13:49:03.620995698+02:00
updated: 2026-05-22T02:47:19.123041428+02:00
started: 2026-05-22T02:35:14.262544206+02:00
completed: 2026-05-22T02:47:19.123040316+02:00
tags:
    - qa
    - stage-06
    - lifecycle
    - subscriptions
    - blocked-by-stage-05
claimed_by: mold-glade
claimed_at: 2026-05-22T02:47:19.123041338+02:00
class: standard
---

Stage 06 Task 01 expects Stage 05 Task 01 subscription for customer-classic@example.test, but Stage 05 Task 01 did not create an order/subscription because classic checkout Place order does nothing (existing issue #26). WP-CLI confirmed user #8 exists but no arraysubs_data subscriptions or Woo orders for customer-classic. Admin subscription search for customer-classic@example.test showed no matching row. Used Stage 05 Block Checkout subscription #357/customer-block@example.test as a control for list/detail/export behavior.

[[2026-05-22]] Fri 02:36
Plan: this is a missing QA artifact, not a code defect. Recreate the Stage 06 expected customer-classic Basic Monthly subscription through the classic checkout path: set checkout page to Classic Checkout QA #338 temporarily, ensure customer password, place BACS checkout in agent-browser (using the known XHR repair only if the agent-browser wrapper blocks Woo checkout), mark order Completed, verify customer-classic order/subscription exists and appears in admin list. Restore checkout page option to #8 after artifact creation.

[[2026-05-22]] Fri 02:47
Fixed by recreating missing Stage 05 Task 01 artifact instead of code change. Customer customer-classic@example.test placed Basic Monthly through Classic Checkout QA #338 with Direct bank transfer after applying known agent-browser XHR repair; order #958 created, marked Completed, subscription #959 promoted to Active. Browser verified order confirmation showed Related Subscriptions #959 and custom checkout builder values; admin Subscriptions active list shows #959 for customer-classic@example.test with Basic Monthly and next payment 22 June 2026; subscription detail shows 9.99, Every 1 month(s), Completed Payments 1, payment method Direct bank transfer, order history #958 Completed Initial; Woo order edit page #958 links related subscription #959. Restored Woo checkout page option to #8.
