---
id: 115
title: 'stage-11: 02 Customer and Storefront Display'
status: closed
priority: medium
created: 2026-05-19T22:56:14.911399991+02:00
updated: 2026-05-23T08:39:20.854227517+02:00
started: 2026-05-23T08:06:53.424253549+02:00
completed: 2026-05-23T08:39:20.854226485+02:00
tags:
    - qa
    - stage-11
claimed_by: mold-glade
claimed_at: 2026-05-23T08:39:20.854227406+02:00
class: standard
---

Source: stages/11-feature-manager/02-customer-and-storefront-display.md

[[2026-05-23]] Sat 08:39
QA complete. Settings page browser check: Feature Manager, Product Page, My Account, Usage Count, Per Subscription all on; save success visible. Guest product page Pro Plan shows What's Included with six enabled features in saved order; Hidden Beta Flag absent in accessibility tree. Toggle-off check: used WP-CLI to flip show_on_product_page=false after Alumnium checkbox click did not change state; cache-busted guest product page removed What's Included; restored true and section returned. cust1 already had active Pro Plan subscription #271, so used existing fixture instead of duplicate checkout. Customer My Account nav includes My Features; page loads with Pro Plan / Subscription #271 section, columns Feature/Type/Your Entitlement/Usage, six enabled features with usage for number rows, hidden beta absent. Subscription #271 link opens customer subscription detail. Admin subscription detail confirms #271 ACTIVE, customer cust1, product Pro Plan. Custom title My Plan Benefits appeared on features page; restored blank and heading returned to My Features. Guest /my-account/features redirects to login/register. Non-subscription Standard Tee product has no What's Included section. Known fixture issue #68 remains: Pro Plan price 9.99/week vs expected 9.99/week.
