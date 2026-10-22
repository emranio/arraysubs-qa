---
id: 86
title: Verify My Account and storefront Free/Pro endpoint boundaries
status: closed
priority: medium
created: 2026-10-23T02:17:12.51958+06:00
updated: 2026-10-23T02:46:25.990391+06:00
started: 2026-10-23T02:18:04.887051+06:00
completed: 2026-10-23T02:46:25.99039+06:00
tags:
    - qa
    - customer-portal
    - storefront
    - free-pro
class: standard
---

Active QA task ID / scheduled day / plan: N/A before creation; targeted extension of member-details fix QA requested by user on 2026-10-23. This does not reuse a removed full lifecycle QA plan.
Affected subscriptions/orders/users: N/A at creation; choose existing local QA customers during browser checks and record exact IDs before judging any defect.
Test site/admin route: http://localhost:10013, real local WordPress; documented Local auto-login for isolated admin context. Customer context will use the existing Login as Customer feature in its own isolated browser session. No remote deployment.
Scope/plan: audit both plugins for customer-facing Free/Pro boundaries, then browser-check My Account dashboard, subscriptions/list/details, account details/addresses/orders/downloads, and storefront/cart/product pages. Verify saved Pro menu/profile/credit/feature settings degrade safely when Pro is absent and optional premium actions do not call missing endpoints. Keep customer account ownership checks and internal feature-type privacy. Use only read-only views and reversible QA configuration changes with exact restoration; do not submit payments, change subscription lifecycle state, or place orders.
Expected: core account/storefront pages work in Free; premium pages/actions stay gated or degrade to core flows without 404/fatal/API failures; customer endpoints enforce ownership and permissions.
Actual/proof: pending; log concrete failures as separate issues with all required fields. Preserve closed #85 and earlier issues.
Environment restore: record activation/module/menu/profile settings before any change and restore exactly. Follow-up findings and evidence will be saved under qa/storefront-account-boundaries-2026-10-23/.

[[2026-10-23]] Fri 02:26
Confirmed additional storefront findings (2026-10-23): (1) With Pro skipped only for a real WP request, published credit product #6725 and bundle #6728 load as WC_Product_Simple and report purchasable=true; taxonomy still records arraysubs_store_credit / arraysubs_subscription_bundle. Expected: unavailable product providers must block new purchases while preserving product data and existing subscriptions. Evidence qa/storefront-account-boundaries-2026-10-23/premium-product-free-probe-before.json; pending browser Free product/cart confirmation. Customer/subscription/order IDs N/A for guest purchase eligibility (no order placed). (2) Customer #224 Finley Sullivan (demo260927_220, demo.260927.220@example.test, customer/demo_member/demo_premium) opening local QA page #19379 http://localhost:10013/qa-credit-shortcode-20261023/ containing [arraysubs_buy_credits product_id="6725"] sees a fatal: Call to a member function is_on_sale() on null, Woo sale-flash.php25 via CreditPurchase.php598. Expected: specific credit product25 purchase form in correct product context. Screenshot browser/pro/shortcode-before.png under this QA directory. Subscriptions/orders N/A for shortcode rendering. Root created isolated hidden variable product#19371 (variants19373/19375/19377) and QA shortcode page19379 for reproduction; existing malformed demo6722 remains untouched, and fixture cleanup is planned after verification. (3) Source-only avatar issue pending runtime check: profileFields.js caches initial Remove button, then replaces it; pending-state updates disable stale element. Plan: use dynamic current button, processing guard and shared portal loading/async confirmation helpers. All fixes stay in central existing feature systems, with no payment or real lifecycle mutations.

[[2026-10-23]] Fri 02:37
Customer read-only REST checks passed in both real Pro and request-local Free requests (customer-rest-pro.json / customer-rest-free.json), including owner vs other vs guest, controlled virtual/no-payment responses, current-user credit/feature APIs, internal feature-type omission and admin-only API restrictions. Disposable pending manual order19408/customer224/product6725 created solely for unpaid order-pay guard display verification; no payment or checkout submission. Corrected QA variation fixture schema from label to canonical name after finding initial malformed test rows; initial absent-list screenshots do not prove behavior for valid rows. Code ownership confirms original missing variation-render path, while valid-schema browser acceptance is pending. Cart malformed API root source belongs to installed Woo interactivity module, logged separately as90.

[[2026-10-23]] Fri 02:46
Completed local source fixes and real browser acceptance. #87 cancellation, #88 selected-variation features, #89 avatar controls are closed. Specific-product credit shortcode now renders correct product without fatal. Core ProductAvailability blocks unavailable premium products for classic/StoreAPI cart and unpaid initial-order payment before changes/gateways, with paid/renewal/plan-change exemptions and Read more catalog labels. Regression passes:47Free+49actual-Pro purchase/payment,22cancellation,37Pro+22Free customer reads; browser Pro/Free account routes, product guards/order19408, canonical feature selections/reset/privacy and disposable-avatar387 flows pass. Pro build/whitespace/line-count checks passed. Canonical draft entries appended for each affected plugin without releasing versions. Exact config/customer224/visibility restored (state/final-comparison.json); comingsoon=yes,Proactive; own product/page/order/avatar fixtures and browser sessions removed (cleanup.json). Existing lifecycle/payments untouched. Separate Woo cart interactivity undefined API-root issue90 remains open; no full cart hydration/checkout/gateway pass claimed. Final report qa/storefront-account-boundaries-2026-10-23/report.md.
