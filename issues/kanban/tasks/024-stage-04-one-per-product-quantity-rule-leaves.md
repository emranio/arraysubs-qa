---
id: 24
title: 'stage-04: One-per-product quantity rule leaves invalid quantity and wrong text'
status: closed
priority: high
created: 2026-05-20T10:51:45.239636646+02:00
updated: 2026-05-22T01:40:46.252904453+02:00
started: 2026-05-22T01:21:08.144677246+02:00
completed: 2026-05-22T01:40:46.252457329+02:00
tags:
    - qa
    - stage-04
    - cart-validation
    - one-per-product
claimed_by: mold-glade
claimed_at: 2026-05-22T01:40:46.252904323+02:00
class: standard
---

Stage 04 Task 03 expected exact error: Each subscription product can only appear once per order. Reduce the quantity to 1. Observed cart quantity update with One subscription per product enabled: 'Each subscription product can only appear once per order. Reduce the quantity to 1 before continuing.' The cart line remained quantity 2 with total 9.98 instead of reverting/remain quantity 1. Direct add-to-cart with quantity=2 left cart empty with no visible validation notice on cart page.

[[2026-05-22]] Fri 01:22
Verified Stage 04 Task 03 and code. String mismatch appears in add-to-cart, cart update, and cart/block validation. Block Cart Store API updates quantity before cart errors run, so it can leave qty 2 while showing an error. Plan: centralize exact message string, replace all one-per-product quantity messages, add Store API user-request quantity hook to reset subscription item quantity back to 1 and store a one-time cart error flag for the Store API response; then browser-verify cart update and direct add-to-cart with One subscription per product enabled, and restore default disabled.

[[2026-05-22]] Fri 01:40
QA fix complete. Code now uses exact one-per-product quantity message, Store API cart update resets subscription quantity back to 1, direct add-to-cart quantity=2 reaches clamp path instead of failing empty. Browser verified: cart-page quantity increase shows exact error and keeps qty 1/9.99; direct add-to-cart quantity=2 shows exact error and cart qty 1/9.99; distinct Standard Weekly + Trial Weekly allowed qty 1 each; setting disabled restores qty 2 allowed with no error/9.98. Restored multiple_subscriptions.one_per_product=0. debug.log remains 0 bytes.
