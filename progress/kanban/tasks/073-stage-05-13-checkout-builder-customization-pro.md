---
id: 73
title: 'stage-05: 13 Checkout Builder Customization (Pro)'
status: closed
priority: high
created: 2026-05-19T22:56:08.30670897+02:00
updated: 2026-05-20T13:19:56.579641002+02:00
started: 2026-05-20T10:28:06.012195871+02:00
completed: 2026-05-20T13:19:56.57963985+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T13:19:56.579640911+02:00
class: standard
---

Source: stages/05-checkout/13-checkout-builder-customization-pro.md

[[2026-05-20]] Wed 13:19
QA complete/fail. Configured Checkout Builder with enabled/copy/show/upload toggles and fields: H3 heading 'Tell us about your company', toggle _arraysubs_cf_has_company, required text _arraysubs_cf_company_name with visibility rule has_company is 1, image select _arraysubs_cf_preferred_theme with Blue/Red, and Order Info/Payment. Admin browser: Checkout Builder page and Open Builder editor loaded; sidebar categories Standard/Advanced/Layout visible; configured fields and Save controls visible. Customer-builder browser: active block checkout #8 did not render any builder fields. Classic Checkout QA page #338 did render heading/toggle/company/image tiles, but Company Name was visible while Has company unchecked. After setting Has company, entering Acme Corp, selecting Blue, and choosing BACS, submit failed with 'There was an error processing your order...' and created only checkout-draft order #573 with no custom field meta/subscription. Filed qa/issues #34; classic submit failure overlaps #26. Disabled checkout_builder.enabled after task to restore standard checkout.
