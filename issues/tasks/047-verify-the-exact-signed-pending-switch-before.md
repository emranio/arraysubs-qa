---
id: 47
title: Verify the exact signed pending switch before reporting invoice freeze
status: closed
priority: high
created: 2026-10-18T17:13:13.289545+06:00
updated: 2026-10-18T17:15:51.68809+06:00
started: 2026-10-18T17:13:15.938566+06:00
completed: 2026-10-18T17:15:51.688089+06:00
tags:
    - bug
    - developer-api
    - regression
class: standard
---

Active QA task: Task 7. Scheduled day: 2026-10-18 (Asia/Dhaka).
Plan: qa/developer-support-2026-10-18/plan.md.

Affected subscription IDs: N/A, temporary signed pending-switch source fixture already cleaned; target product 14340.
Affected order IDs: renewal invoice 14342 (temporary fixture, already cleaned).
Affected customer: WordPress user 317, login subscription-order-66982ed1-a, email subscription-order-66982ed1-a@example.test, role customer (temporary fixture, already cleaned).
Actor: WordPress admin user 1, trusted PHP API via WP-CLI after wp_loaded.
Site/route: http://localhost:10013/. No REST/admin route: ArraySubs\Developer\SubscriptionSnapshot::read() through Subscriptions::data(["pending_switch"]). Browser context: N/A, server-side API regression with Pro active and HPOS enabled.

Reproduction:
1. Store a valid signed pending plan switch through arraysubs_set_pending_switch().
2. Attach a same-customer renewal invoice whose _arraysubs_pending_switch_id initially equals the signed snapshot ID. Confirm the API reports this invoice as the freeze owner.
3. Change only the invoice's _arraysubs_pending_switch_id to another-pending-switch.
4. Read the current subscription's pending_switch field group again.

Expected: frozen_by_order_id=null because that invoice no longer identifies the current signed switch.
Actual: frozen_by_order_id remains 14342; the snapshot checks only that the invoice field is nonempty.
Proof: subscription-order-api-regression-results.txt recorded FAIL: Unrelated pending-switch invoice metadata cannot freeze the current signed target. Returned target_product_id=14340 and frozen_by_order_id=14342 after the mismatching ID was stored.

Known scope: the matching signed snapshot/invoice case passes; current plan remains separate from the target and raw signatures/source data are already excluded. This defect affects freeze attribution for stale/mismatching invoice metadata.
Fix plan: validate exact current pending-switch ID plus the invoice's canonical subscription/customer membership before exposing freeze ownership. Reuse the existing pending-switch helper where appropriate. Root owns the source patch. Retest matching and mismatching cases and the adjacent snapshot matrix; no phpcs.

[[2026-10-18]] Sun 17:15
Verified fixed on 2026-10-18: freeze attribution requires exact current pending-switch ID, renewal flag, subscription membership, customer identity and an unresolved order status. Both matching and mismatching signed-target cases pass in Pro and core-only runs. Full matrices: 70/70 each, HPOS enabled, all fixtures cleaned. Evidence: qa/developer-support-2026-10-18/subscription-order-api-regression-results.txt and subscription-order-api-regression-core-results.txt.
