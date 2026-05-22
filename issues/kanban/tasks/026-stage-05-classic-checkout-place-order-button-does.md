---
id: 26
title: 'stage-05: Classic checkout Place order button does nothing'
status: blocked
priority: critical
created: 2026-05-20T11:34:35.658387598+02:00
updated: 2026-05-21T21:11:54.726018062+02:00
started: 2026-05-21T20:27:30.715716273+02:00
tags:
    - qa
    - stage-05
    - checkout
    - classic
    - blocker
blocked: true
block_reason: Blocked by Alumnium injected XMLHttpRequest wrapper causing WooCommerce checkout AJAX TypeError before request reaches WordPress.
claimed_by: mold-glade
claimed_at: 2026-05-21T21:11:54.726017962+02:00
class: standard
---

Stage 05 Task 01 using temporary shortcode page Classic Checkout QA (#338) with Woo checkout page option pointed to #338. Logged in as customer-classic@example.test, added Standard Weekly, filled all required billing fields, selected Direct bank transfer. Place order button was enabled, no visible validation errors, but repeated click/Enter produced no redirect, no error message, and no processing state. Checkout stayed on ?page_id=338. Also summary lacks expected Next charge date and authorization notice; related to existing checkout summary issue #14. Cart/order creation could not complete through browser.

[[2026-05-21]] Thu 20:54
Plan: verify browser-only checkout failure against Stage 05 Task 01; patch ArraySubsPro to disable Stripe expanded Optimized Checkout on classic checkout only when cart contains ArraySubs subscription checkout items; retest BACS classic checkout in browser; close issue after order + subscription created, restore checkout page option.

[[2026-05-21]] Thu 21:11
Result: Added core checkout guards to disable Woo Stripe expanded Optimized Checkout and Stripe express checkout buttons on ArraySubs subscription classic checkout. Retest: normal Alumnium click still fails before PHP with /?wc-ajax=checkout statusText 'TypeError: Illegal invocation'. Root cause isolated to Alumnium's injected XMLHttpRequest wrapper calling nativeOpen(method, url, ...rest) without binding this. After a temporary in-page XHR repair, the same Alumnium session completed BACS checkout: order #800, total 9.99, payment Direct bank transfer, related subscription #801 pending. Cleaned diagnostic artifacts #793/#794 and verification artifacts #800/#801. Restored woocommerce_checkout_page_id to #8. Blocked because default Alumnium cannot verify WooCommerce jQuery checkout AJAX until tool XHR wrapper is fixed; adding a product-side Alumnium-specific shim would be wrong.
