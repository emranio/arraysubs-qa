---
id: 12
title: 'stage-03: Basic Monthly recurring price format mismatch'
status: closed
priority: medium
created: 2026-05-20T01:14:56.482010419+02:00
updated: 2026-05-22T04:21:34.656292866+02:00
started: 2026-05-22T04:14:09.052230486+02:00
completed: 2026-05-22T04:21:34.656292114+02:00
tags:
    - qa
    - stage-03
    - products
    - display
claimed_by: mold-glade
claimed_at: 2026-05-22T04:21:34.656292775+02:00
class: standard
---

Observed with Alumnium on 2026-05-20 for product #197 Basic Monthly. Product detail displays '9.99 Every month' and extra 'Shipping charged on every renewal'; shop catalog card displays only '9.99'. Expected Stage 03 task 01: '9.99 / month' on product page and catalog, with no extra trial/signup/first-payment text. Add-to-cart/Subscribe button is visible.

[[2026-05-20]] Wed 01:18
Also observed for Standard Weekly (#200): detail page extraction showed '9.99' with recurring context in page diff but not exact '9.99 / week'; extra 'Shipping charged on every renewal' text present.

[[2026-05-22]] Fri 04:16
Plan: make subscription storefront price HTML carry the billing suffix for simple products, suppress the duplicate simple-product subscription info block when there are no extra terms, keep richer trial/signup/different-renewal messaging intact, and hide Pro product-page shipping notice unless custom subscription shipping amounts are configured. Then verify Basic Monthly and Standard Weekly product/catalog output in browser.

[[2026-05-22]] Fri 04:21
Fixed: simple subscription price HTML now uses compact suffixes (, ) via ; simple products with no extra subscription terms no longer render duplicate  info blocks; Pro product-page shipping notice is hidden unless custom subscription shipping amounts are configured. Verified WP-CLI price HTML for #197/#200 and Alumnium: Basic Monthly page , Subscribe Now, no /shipping text; shop listing Basic Monthly ; Standard Weekly , no .

[[2026-05-22]] Fri 04:21
Fixed details corrected: simple subscription price HTML now uses compact suffixes (/ month, / week) through WooCommerce price HTML filtering; simple products with no extra subscription terms no longer render duplicate Every-month/Every-week info blocks; Pro product-page shipping notice is hidden unless custom subscription shipping amounts are configured. Verified WP-CLI price HTML for product 197 and 200. Verified with Alumnium: Basic Monthly page shows $29.99 / month, Subscribe Now, no Every month and no Shipping charged on every renewal; shop listing Basic Monthly shows $29.99 / month; Standard Weekly shows $19.99 / week and no Every week.
