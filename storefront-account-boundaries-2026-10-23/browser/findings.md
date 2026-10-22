# Confirmed browser findings / investigation evidence

All findings belong to active QA task #86, scheduled day 2026-10-23 (Asia/Dhaka), plan `qa/storefront-account-boundaries-2026-10-23/plan.md`. Browser automation used actual local WordPress with agent-browser. Product source/configuration was not modified by the browser worker. Root owns issue-board entries and isolated fixture cleanup.

## Variation-specific storefront feature updates — source finding, browser fixture limitation

- Subscription/order IDs: N/A; no purchase performed.
- User/customer: guest (N/A user ID/email/role).
- Product: isolated variable fixture #19371; Basic variation #19373, Pro #19375, Inherited #19377. Existing product #6722 was not changed: its Tier dropdown is malformed and has no selectable values despite two available variation records.
- URL: `http://localhost:10013/product/qa-boundary-variation-features-20261023/`, Pro active, guest session `sfa-guest`.
- Reproduction: load product; select Plan Basic, Pro, Inherited in turn; read hidden `variation_id` and visible feature area.
- Expected: Basic download credits 10, Pro download credits 20, or inherited Parent library credits 5 shown for the selected variation.
- Initial observation: each selection set the correct variation ID but no **What's Included** heading/list appeared. Saved DOM observation has `includedHeadings: []` for each. Root later found that the initial disposable fixture used invalid `label` keys instead of canonical `name`, so storage normalized its rows to empty arrays. These initial screenshots do not establish the defect for valid configured data; root's source audit established that the baseline lacked variable-product feature updates.
- Initial limited evidence: `pro/variation-basic-before.png/json`, `pro/variation-pro-before.png/json`, `pro/variation-inherited-before.png/json`, `pro/variation-inherited-before-snapshot.txt`. Treat transitional `after/variation-basic-current.png` / `variation-debug.json` the same way.
- After fix with corrected canonical fixture: Basic #19373 displays Basic download credits 10; Pro #19375 displays Pro download credits 20; Inherited #19377 displays Parent library credits 5. Reset hides the wrapper. Feature/type-related payload keys contain only escaped `arraysubs_features_html`; no raw types or type-derived attributes are exposed. Proof: `after/variation-{basic,pro,inherited}-valid.png/json`, `after/variation-reset.png/json`, `../corrected-variation-fixtures.json`.
- Counterexample: simple lifetime product `/product/demo-%c2%b7-lifetime-resource-library/` displays its configured feature list with Pro active (`pro/guest-product.png`).

## Buy-credit shortcode fatals on an ordinary page

- Subscription/order IDs: N/A; no purchase or payment performed.
- Customer: #224, login `demo260927_220`, email `demo.260927.220@example.test`, roles `customer`, `demo_member`, `demo_premium`.
- Page #19379 contains `[arraysubs_buy_credits product_id="6725"]`; requested product #6725 is Demo · Store Credit $25.
- URL: `http://localhost:10013/qa-credit-shortcode-20261023/`, Pro active, authenticated customer session `sfa-customer`.
- Reproduction: visit the page directly.
- Expected: correct $25 credit-product form renders in the page context.
- Actual: WordPress critical-error page. Browser displays **Call to a member function is_on_sale() on null**, `woocommerce/templates/single-product/sale-flash.php:25`, called through `CreditPurchase.php:598` -> `renderBuyCreditsShortcode()` -> `wc_get_template_part('content', 'single-product')`.
- Proof: `pro/shortcode-before.png`, `pro/shortcode-before-snapshot.txt`.
- Counterexample: native product #6725 renders Credit Amount $25 / Buy Credits under Pro (`pro/credit-product-6725.png`).
- After fix: the ordinary page renders the correct $25 form, Buy Credits and add-to-cart ID #6725 without a fatal (`after/shortcode-product-6725.png`, `after/shortcode-form.json`).

## Premium product purchase guards absent without Pro

- Subscription/order IDs: N/A. No order/payment submission occurred.
- Users: unauthenticated guest. Main customer #224 was not used for the cart reproduction; its cart remained empty. Throwaway session `sfa-credit-throwaway` was separate and closed after evidence.
- Products: credit #6725 and bundle #6728.
- URLs: `http://localhost:10013/product/demo-%c2%b7-store-credit-25/` and `/product/demo-%c2%b7-coffee-and-tea-membership-bundle/`, Free alone.
- Reproduction: deactivate only local Pro, reload complete documents, inspect purchase controls. In the explicitly authorized throwaway guest cart only, click credit #6725's **Purchase now**.
- Expected: unsupported premium products cannot be purchased as ordinary products while their implementation is absent.
- Actual: credit #6725 becomes a generic quantity/Purchase now product, with no credit amount explanation. Bundle #6728 loses its component review and displays Subscribe Now at a different ordinary price. Credit Purchase now is accepted, with WooCommerce **“Demo · Store Credit $25” has been added to your cart.**
- Proof: `free/credit-product-6725.png`, `free/bundle-product-6728.png`, `free/credit-throwaway-product-before.png`, `free/credit-throwaway-added.png`, `free/credit-purchase-guard-before.webm`, `free/credit-throwaway-requests.txt`.
- Important observation: the site's Purchase now action automatically redirected the accepted add-to-cart POST to `/checkout-2/`; the worker did not choose checkout, enter any contact/payment value, or click Place Order, and immediately navigated back to cart. Recorded requests contain no Store API checkout/payment request. Do not describe this as an intentionally completed checkout test.
- The first cart screenshot was captured during block hydration (`free/credit-throwaway-cart.png`); it is transitional evidence, not proof of a fully rendered cart. The accepted-addcart notice and product POST are the concrete guard-failure proof.
- Counterexamples: with Pro active, credit shows its credit explanation and Buy Credits form; bundle shows its composition and $50.40 monthly total (`pro/credit-product-6725.png`, `pro/bundle-product-6728.png`).
- After fix: fresh Free documents show unavailable notices/no main purchase form; direct add-to-cart #6725 is rejected with cart count 0 in an isolated guest session (`after/free-credit-addcart-denial.png`, `after/free-credit-denial-dom.json`). Disposable pending order #19408/customer #224/product #6725 has no Pay form/button or account Pay action while Pro is absent, but shows the expected payment form under Pro (`after/free-order-pay-19408.png`, `after/free-orders-19408.png`, `after/pro-order-pay-19408.png`). No payment was submitted. A later related-card Read more wording fix has real-WP assertion proof from root, without another browser run.

## Cart block malformed API URL — ownership investigation required

- Subscription/order IDs: N/A; no checkout/payment submitted.
- User: guest, `sfa-credit-throwaway`; the initial empty-cart visit already records this request before the authorized add-to-cart action.
- URL: `http://localhost:10013/cart-2/`.
- Observation: block hydration requests `/cart-2/undefinedwc/store/v1/cart`, which redirects to `/cart/` with HTTP 200. The nonempty cart view remained a skeleton during its captured state.
- Expected: the cart block requests its actual `/wp-json/wc/store/v1/cart` endpoint and renders product totals.
- Proof: `free/credit-throwaway-requests.txt`, `free/credit-throwaway-cart.png`, `free/credit-throwaway-cart-snapshot.txt`. The same inventory also contains a correct `/wp-json/wc/store/v1/cart` 200 request on a product page.
- Scope: source ownership not established by browser testing; distinguish from the confirmed ArraySubs premium-product boundary defect. Existing Woo/WordPress prerelease script warnings are recorded separately.
- Additional runtime proof: `cart-runtime-pro.json`, `cart-interactivity-state.json`, `cart-json-script-inventory.json`. The cart module is loaded, but no interactivity JSON data script/`state.woocommerce.restUrl` is emitted in this document. The integration audit traced the malformed fetch to WooCommerce's cart interactivity module consuming that missing state. This remains a separate Woo/WordPress bootstrap observation; it is not claimed fixed by the ArraySubs changes.
