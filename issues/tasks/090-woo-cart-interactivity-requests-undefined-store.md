---
id: 90
title: Woo Cart interactivity requests undefined Store API root
status: closed
priority: medium
created: 2026-10-23T02:35:06.349827+06:00
updated: 2026-10-23T04:16:35.231463+06:00
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

[[2026-10-23]] Fri 03:48
User explicitly requested fixing and properly testing this cart issue on2026-10-23. Fresh targeted plan qa/cart-bootstrap-2026-10-23/plan.md, immediate manual run. Reproduce actual empty and populated cart; distinguish malformed mini-cart request from main Cart hydration cause before choosing fix. Audit bothplugins/Woo bootstrap, use own isolated guest/customer fixtures and realbrowser. Temporarily change only localComingSoon yes→no andProactivation for comparison ifnecessary, with exact snapshots/restoration. No checkout payment/order submission, no existing customer cart mutation. Prefer repair in the owning code/integration with portable plugin fixes rather than direct generated/vendor patch. SkipPHPCS/Composer; focusedsource/browser checks. Earlier scope restriction in original issue is superseded by direct user authorization to fixcart.

[[2026-10-23]] Fri 03:55
Causal investigation isolates malformed requests to the inactive Woo Mini-Cart module on configured Cart/Checkout. Main Cart empty/populated React rendering succeeds after waiting for visible product content; earlier skeleton evidence was transitional. Public render-filter compatibility fix is reviewed in core worktree codex/fix-cart-bootstrap, commit8a03618. No Woo vendor edits or global API/bootstrap changes. Browser regression follows baseline quantity/removal checks.

[[2026-10-23]] Fri 04:16
Completed under active task #90, scheduled 2026-10-23 Asia/Dhaka, plan qa/cart-bootstrap-2026-10-23/plan.md. Core commit 8a03618 removes only the unused Mini-Cart module from native Cart/Checkout fallbacks through the public render filter; Woo/WordPress/theme sources unchanged. Real browser routes: http://localhost:10013/cart-2/, /checkout-2/, and disposable product pages. Seven Pro/Free guest/customer cases passed: ordinary quantities/totals 25→50→0 USD, Pro monthly12→24→0, Free monthly terms/removal12→0, and mixed customer carts37→62→12→0 in both modes. Native storefront drawer and cross-sell25+7=32 work; legitimate ProductButton state remains. No malformed URLs, page/Cart errors, checkout submissions or owned orders; draft IDs0. Supplemental render checks passed7/7 per mode. Customer438, login qa_cart_90_1792705957, fixture email@example.invalid, role customer, was positively verified before mutations and deleted afterward; products19879/19881/19887 deleted. Subscription/order IDs N/A. Both final carts empty, own sessions closed, credentials removed. Exact initial8-plugin order, Coming Soon yes, settings/modules/menu/profile/share-key hashes and Cart/Checkout configuration restored. Core draft updated. Earlier main-Cart skeleton was transitional; no persistent loading fix is claimed. Later repeated cases used reduced-motion/native keyboard after capture-tool animation freeze; no runtime/CSS patches or payment testing. Report qa/cart-bootstrap-2026-10-23/report.md plus independent/privacy reviews contain proof and limits. Unrelated changes/issues preserved. No lint/Composer, push, release or deployment.
