---
id: 68
title: 'stage-11: Pro Plan fixture price mismatches QA plan'
status: closed
priority: medium
created: 2026-05-23T08:12:16.729887747+02:00
updated: 2026-05-24T08:39:38.316068496+02:00
started: 2026-05-24T08:31:04.604562398+02:00
completed: 2026-05-24T08:39:38.316067424+02:00
tags:
    - qa
    - stage-11
    - products
    - fixture
claimed_by: shell-quartz
claimed_at: 2026-05-24T08:39:38.316068395+02:00
class: standard
---

Stage 11 Task 01 pre-condition/regression check expects Pro Plan to be 9.99/week. Actual Pro Plan product #233 is published but regular price is 9.99 and billing period is week. This was observed before Feature Manager edits. Expected fixture from Stage 03/Stage 11 README: Pro Plan 9.99/week.

[[2026-05-24]] Sun 08:39
Fix verified 2026-05-24 by shell-quartz. Product fixture #233 normalized with WP-CLI: _regular_price/_price/_subscription_price/_arraysubs_subscription_price=19.99 and product/subscription billing period/interval meta=week/1. Browser admin check: Pro Plan subscription settings showed recurring price 9.99, billing period Week, interval 1, length 0. Storefront check: /?product=pro-plan displayed '9.99 / week'.
