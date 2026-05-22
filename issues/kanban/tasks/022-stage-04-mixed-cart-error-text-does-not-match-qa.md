---
id: 22
title: 'stage-04: Mixed cart error text does not match QA spec'
status: closed
priority: high
created: 2026-05-20T10:33:54.056534104+02:00
updated: 2026-05-22T01:16:36.623724291+02:00
started: 2026-05-22T01:12:06.366286773+02:00
completed: 2026-05-22T01:16:36.623723068+02:00
tags:
    - qa
    - stage-04
    - cart-validation
    - mixed-cart
claimed_by: mold-glade
claimed_at: 2026-05-22T01:16:36.62372419+02:00
class: standard
---

Stage 04 Task 01 expected exact error: This order cannot contain subscription and regular products together. Observed on live site with Allow mixed cart disabled: subscription then regular shows 'This cart already contains a subscription. Remove the subscription first or enable mixed checkout before adding regular products to the same order.' Reverse order shows 'This cart already contains a regular product. Remove it first or enable mixed checkout before adding a subscription to the same order.' Blocking behavior works and second product is not added, but exact error contract fails.

[[2026-05-22]] Fri 01:12
Verified Stage 04 Task 01/07 and code. Blocking works, but add-to-cart validation uses directional mixed-cart messages and cart validation uses longer remediation copy. Plan: replace all core mixed-cart customer-facing errors with the exact contract string 'This order cannot contain subscription and regular products together.' in both add-to-cart paths and classic/block cart validation; then browser-verify subscription→regular and regular→subscription flows with Allow mixed cart disabled.

[[2026-05-22]] Fri 01:16
Fix applied. Mixed-cart add-to-cart validation and classic/block cart validation now use the exact Stage 04 contract string. Browser QA with Allow mixed cart disabled: Standard Weekly then Plain Mug shows 'This order cannot contain subscription and regular products together.' and cart contains only Standard Weekly; Plain Mug then Standard Weekly shows the same exact text and cart contains only Plain Mug. Restored Allow mixed cart to enabled. debug.log remains 0 bytes.
