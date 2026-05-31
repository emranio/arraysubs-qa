---
id: 23
title: 'stage-04: Multiple subscription error text does not match QA spec'
status: closed
priority: high
created: 2026-05-20T10:47:22.93075043+02:00
updated: 2026-05-22T01:21:02.589614468+02:00
started: 2026-05-22T01:16:42.877651742+02:00
completed: 2026-05-22T01:21:02.589613346+02:00
tags:
    - qa
    - stage-04
    - cart-validation
    - multiple-subscriptions
claimed_by: mold-glade
claimed_at: 2026-05-22T01:21:02.589614367+02:00
class: standard
---

Stage 04 Task 02 expected exact error: Only one subscription plan can be checked out at a time. Observed on live site with Allow multiple subscriptions in cart disabled: 'Only one subscription plan can be checked out at a time. Remove the other subscription from your cart before adding this one.' Blocking behavior works in both Standard Weekly -> Basic Plan and Basic Plan -> Standard Weekly paths, but exact error contract fails due appended sentence.

[[2026-05-22]] Fri 01:17
Verified Stage 04 Task 02/07 and code. Blocking works, but add-to-cart and cart/block validation append extra remediation copy. Plan: replace core multiple-subscription customer-facing errors with exact 'Only one subscription plan can be checked out at a time.' in add-to-cart plus classic/block cart validation; then browser-verify both add order paths with Allow multiple subscriptions disabled and restore setting.

[[2026-05-22]] Fri 01:20
Fix applied. Multiple-subscription add-to-cart validation and classic/block cart validation now use exact Stage 04 contract text. Browser QA with Allow multiple subscriptions in cart disabled: Standard Weekly then Basic Plan shows 'Only one subscription plan can be checked out at a time.' and cart contains only Standard Weekly; Basic Plan then Standard Weekly shows same exact text and cart contains only Basic Plan. Restored Allow multiple subscriptions in cart to enabled. debug.log remains 0 bytes.
