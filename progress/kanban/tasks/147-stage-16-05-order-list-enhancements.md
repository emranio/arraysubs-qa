---
id: 147
title: 'stage-16: 05 Order List Enhancements'
status: closed
priority: medium
created: 2026-05-19T22:56:20.210235949+02:00
updated: 2026-05-24T18:45:55.491980766+02:00
started: 2026-05-23T08:06:53.459772386+02:00
completed: 2026-05-23T13:58:19.571198896+02:00
tags:
    - qa
    - stage-16
class: standard
---

Source: stages/16-analytics/05-order-list-enhancements.md

[[2026-05-23]] Sat 13:58
QA complete (2026-05-23, HPOS enabled, wide Alumnium browser).

Setup:
- Seeded SAVE20/saves-as `save20` coupon fixture order #1332: Customer One, completed, Type `Subs Renew`, Coupon(s) `save20`, total $19.99.
- Fixed trial fixture #1317 by linking `_subscription_ids` to trial subscription #1293 so TypeResolver returns `Subs Trial`.

Results:
- Columns visible in WC → Orders: Order, Date, Status, Type, Coupon(s), Total, Origin. Type and Coupon(s) columns render on HPOS list.
- Type filter dropdown visible with options: All Types, credit purchase, Subs Trial, Subs Renew, Subs Upgrade, Subs Purchase, Other. Subs Renew filter returned only Subs Renew rows; Subs Trial filter returned #1317 only.
- Coupon filtering works through WooCommerce native search mode `Coupons`: search `save20` returned #1332 only. Combined `Type=Subs Renew` + Coupons search `save20` returned #1332 only.
- Subscription-only backend/list filter works by direct URL `arraysubs_subs_only=yes`; visible rows were subscription types only and regular Other rows were hidden. No visible UI control exposes this filter.
- Missing expected UI: Coupon dropdown, Subscription Products Only control, and ArraySubs summary panel. Logged issue #119. Existing lowercase credit type label covered by issue #117.
- Backfill exercised: direct-cleared `_arraysubs_computed_type` and `_arraysubs_has_subscription_product` for #511/#494. Notice appeared: `2 orders need type classification for analytics.` Button `Compute Order Types` completed with `Successfully computed types for 2 orders. All orders are now classified.` Reload removed notice.
- Non-destructive check: #511 stayed completed/$20.00/customer 16; #494 stayed checkout-draft/$24.99/customer 15. Backfill restored types/sub flags only.

Browser verification complete. Issue: #119.

[[2026-05-24]] Sun 18:45
Issue #119 fixed: Orders list now exposes Coupon and Subscription Products Only controls and HPOS summary panel renders correctly with Total Orders/type counts/Orders with Coupon. Verified syntax plus Playwright filter submissions and screenshots qa/artifacts/issue-119/orders-filters-summary.png, qa/artifacts/issue-119/orders-subs-only-filter.png.
