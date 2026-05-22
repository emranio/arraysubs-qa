---
id: 29
title: 'stage-05: One-click all-items mode does not clear existing same product quantity'
status: closed
priority: high
created: 2026-05-20T12:20:59.763014896+02:00
updated: 2026-05-22T01:57:16.209339662+02:00
started: 2026-05-22T01:53:31.2492329+02:00
completed: 2026-05-22T01:57:16.20933847+02:00
tags:
    - qa
    - stage-05
    - checkout
    - one-click
claimed_by: mold-glade
claimed_at: 2026-05-22T01:57:16.209339552+02:00
class: standard
---

Stage 05 Task 08 in all_items mode with Standard Tee already present in cart. Product page button changed to Buy Now and redirected directly to checkout, but checkout total was 0.00 for Standard Tee instead of a single 5.00 item. This indicates the cart was not fully cleared or same-product quantity was incremented before checkout. Expected one-click behavior: cart is cleared and only the clicked item remains.

[[2026-05-22]] Fri 01:54
Verified Stage 05 Task 08 and one-click code. Root cause: Woo merges same product into the existing cart line before the one-click cleanup hook runs; cleanup removes other lines but skips the clicked cart key, leaving merged quantity from the preloaded item. Plan: after removing all other cart items, force the clicked one-click cart item quantity back to the requested add quantity, then recalculate totals. Syntax-check PHP; set one_click_mode=all_items and disable_cart_page=false; browser-verify Standard Tee already in cart, product-page Buy Now click redirects to checkout with only one Standard Tee at 5.00; restore one_click_mode=default.

[[2026-05-22]] Fri 01:57
QA fix complete. maybeKeepOnlyOneClickItemInCart now removes other cart lines and resets the clicked line quantity to the requested add quantity, preventing Woo's same-product merge from carrying prior quantity into checkout. Syntax check passed. Browser verified in all_items mode with Standard Tee already in cart: second product-page add redirects directly to checkout and order summary shows only Standard Tee, quantity 1, total 5.00. Restored checkout.one_click_mode=default; debug.log remains 0 bytes. Note: current non_subscription_button_text setting is empty, so test site's visible label was Add to cart, but one-click redirect/cleanup behavior was active and verified.
