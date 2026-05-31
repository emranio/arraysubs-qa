---
id: 68
title: 'stage-05: 08 One-Click Checkout'
status: closed
priority: high
created: 2026-05-19T22:56:07.66458117+02:00
updated: 2026-05-20T12:21:36.531773418+02:00
started: 2026-05-20T10:28:05.943651118+02:00
completed: 2026-05-20T12:21:36.531772356+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T12:21:36.531773317+02:00
class: standard
---

Source: stages/05-checkout/08-one-click-checkout.md

[[2026-05-20]] Wed 12:21
QA done. Created missing regular product Standard Tee #447 at 5.00 for task precondition. Created/reset customer-oneclick@example.test user #14. subscription_items mode persisted with non-sub button text Buy Now and cart-page disable off. Basic Monthly product page showed Subscribe Now and 9.99 Every month. With Standard Tee preloaded, clicking Basic Monthly Subscribe Now redirected directly to checkout and checkout showed only Basic Monthly 9.99, so subscription one-click cleared unrelated cart item. In subscription_items mode, Standard Tee product page showed Add to cart, click stayed on product page and cart count increased, not direct checkout. With disable_cart_page=true, visiting Cart redirected to checkout. all_items mode changed Standard Tee button to Buy Now and redirected to checkout, but checkout showed Standard Tee total 0.00 (same product quantity not cleared to one); logged issue #29. Restored one_click_mode=default, disable_cart_page=false, non_subscription_button_text empty.
