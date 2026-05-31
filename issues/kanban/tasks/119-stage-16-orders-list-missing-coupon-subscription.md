---
id: 119
title: 'stage-16: Orders list missing coupon/subscription filters and summary panel'
status: closed
priority: high
created: 2026-05-23T13:54:51.280683354+02:00
updated: 2026-05-24T18:45:44.082896392+02:00
started: 2026-05-24T18:38:13.268318893+02:00
completed: 2026-05-24T18:45:44.082895531+02:00
tags:
    - qa
    - stage-16
    - analytics
    - orders
claimed_by: shell-quartz
claimed_at: 2026-05-24T18:45:44.082896292+02:00
class: standard
---

Original task: progress #147, Stage 16 Task 05 Order List Enhancements, sub-tasks 5.3/5.4/5.6.

Expected: WooCommerce → Orders shows a Coupon filter dropdown populated from used coupons, a Subscription Products Only filter/toggle, and an embedded ArraySubs summary panel above the table with Total Orders, per-type counts, and Orders with Coupon.
Observed: wide 1920px browser Orders page shows Type and Coupon(s) columns and a Type dropdown, but no Coupon dropdown, no Subscription Products Only control, and no ArraySubs summary panel. The only coupon UI available is WooCommerce native search mode `Coupons`; `arraysubs_subs_only=yes` works if placed in the URL directly, but no visible control exposes it.
Code clue: `OrderListHooks::renderFilterDropdowns()` and `renderFilterDropdownsLegacy()` only call `renderTypeFilterDropdown()`; private `renderCouponFilterDropdown()` and `renderSubsOnlyFilterDropdown()` are not rendered. Browser check also failed for `Total Orders` summary panel.
Evidence: Alumnium wide session `plugins-1779536849`; columns present: Order, Date, Status, Type, Coupon(s), Total, Origin. Missing visible controls/panel confirmed by browser checks.

[[2026-05-24]] Sun 18:45
Fix applied: WooCommerce Orders now renders Type, Coupon, and Subscription Products Only controls, and the HPOS summary panel hook accepts both WooCommerce hook args so it renders above the table. Label changed to Orders with Coupon to match QA/manual. Verification: php -l passed for OrderListHooks.php and order-list-report.php. Playwright wide browser confirmed All Types, All Coupons, Subscription Products Only, Total Orders, and Orders with Coupon are visible; coupon filter save20 submits and summary shows 1 Orders with Coupon; subscription-products-only filter submits arraysubs_subs_only=yes and summary updates. Screenshots: qa/artifacts/issue-119/orders-filters-summary.png and qa/artifacts/issue-119/orders-subs-only-filter.png.
