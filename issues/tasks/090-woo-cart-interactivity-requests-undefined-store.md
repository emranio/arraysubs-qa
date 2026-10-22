---
id: 90
title: Woo Cart interactivity requests undefined Store API root
status: open
priority: medium
created: 2026-10-23T02:35:06.349827+06:00
updated: 2026-10-23T02:35:06.349827+06:00
tags:
    - qa
    - woocommerce
    - storefront
parent: 86
class: standard
---

Active QA task ID / scheduled day / plan: #86 / 2026-10-23 Asia/Dhaka / qa/storefront-account-boundaries-2026-10-23/plan.md.
Affected subscription IDs / order IDs: N/A; no checkout/payment submission.
Affected user/customer IDs, login/email, roles: N/A, guest in isolated sfa-credit-throwaway session; also occurs on initial empty cart.
Exact URL/browser context: http://localhost:10013/cart-2/, local real WordPress, WooCommerce 11.1.2, agent-browser guest. Seen with Free alone and existing Woo block scripts.
Reproduction: open Cart page with an empty cart; inspect network/interactivity state. After a separate authorized credit add-to-cart reproduction the Cart block also remains a loading skeleton; no Place Order action or payment performed.
Expected: Woo cart uses /wp-json/wc/store/v1/cart and renders normally.
Actual/proof: browser network requests /cart-2/undefinedwc/store/v1/cart, redirects to /cart/ HTTP200. No wp-interactivity-data or JSON/module data state present although Woo cart interactivity module is loaded. Evidence qa/storefront-account-boundaries-2026-10-23/browser/free/credit-throwaway-requests.txt and credit-throwaway-cart.png; runtime cart-interactivity-state.json and cart-json-script-inventory.json in browser/.
Scope/source attribution: installed woocommerce/assets/client/blocks/@woocommerce/stores/woocommerce/cart.js refreshCartItems concatenates state.restUrl with wc/store/v1/cart. Missing restUrl produces exact observed relative URL. Woo MiniCart.php returns early on is_cart()/is_checkout() before initializing BlocksSharedState load_cart_state. No ArraySubs/Pro writes to that interactivity state, wp.apiFetch root or wcSettings found. This identifies the malformed request owner; whether it fully explains the main cart skeleton remains unverified. Correct Store API cart200 request exists on product page. Not an ArraySubs endpoint boundary fix; no Woo/theme source changed.
Plan: preserve evidence and investigate Woo/theme bootstrap separately; do not expand product boundary fixes into third-party Woo internals.
