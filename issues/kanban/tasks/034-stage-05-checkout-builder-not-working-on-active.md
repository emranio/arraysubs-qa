---
id: 34
title: 'stage-05: Checkout Builder not working on active checkout'
status: closed
priority: high
created: 2026-05-20T13:19:39.722235597+02:00
updated: 2026-05-22T02:35:08.210089367+02:00
started: 2026-05-22T02:14:55.335614808+02:00
completed: 2026-05-22T02:35:08.210088375+02:00
tags:
    - qa
    - stage-05
    - checkout-builder
    - block-checkout
    - conditional-fields
claimed_by: mold-glade
claimed_at: 2026-05-22T02:35:08.210089267+02:00
class: standard
---

Stage 05 Task 13. Builder editor loads and saved fields appear in editor. Active checkout page #8 (block checkout) did not render Checkout Builder fields at all: no heading, Has company toggle, Company Name, or Preferred Theme tiles. Classic Checkout QA page #338 did render the fields, but Company Name was visible while Has company was unchecked, so the conditional visibility rule did not hide the required text field. Submitting the classic form with BACS and field values hit the existing checkout failure: 'There was an error processing your order...' and only checkout-draft order #573 was created with no custom field meta and no subscription. This overlaps classic checkout blocker #26 for submission, but the block-render/conditional issues are Checkout Builder-specific.

[[2026-05-22]] Fri 02:20
Plan: reproduce current checkout-builder config, then fix two specific gaps: (1) classic conditional JS reads checkbox value even when unchecked, so Company Name stays visible; normalize checkbox/radio values and hide/disable hidden fields. (2) block checkout never receives builder fields; register supported builder inputs with Woo Blocks additional checkout fields, mirror Store API values back to _arraysubs_cf_* order meta, pass builder field metadata to frontend, and decorate block checkout for headings/image-select plus visibility rules. Verify classic + block rendering in Alumnium; skip final checkout submission if existing #26 still blocks payment.

[[2026-05-22]] Fri 02:34
Fixed: added Woo Blocks registration for Checkout Builder inputs, Store API request meta mirroring back to _arraysubs_cf_* keys, block frontend decoration for heading/image-select tiles, and corrected classic conditional JS to hide the full form row when rules are attached to the input. Built arraysubspro assets. Verified in Alumnium: block checkout #8 shows heading, Has company, no Company Name while unchecked, Blue/Red tiles; toggling Has company reveals Company Name. Classic checkout #338 now hides Company Name while unchecked and reveals it when checked. Store API meta mirror verified by WP eval: checked saves Has company=1, Company Name=Acme Corp, Preferred Theme=blue; unchecked deletes Company Name and keeps Preferred Theme. Full checkout submission still covered by existing blocker #26.
