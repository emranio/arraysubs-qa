# Storefront / My Account boundary browser observations

Active QA task: #86. Scheduled day: 2026-10-23 (Asia/Dhaka). Plan: `qa/storefront-account-boundaries-2026-10-23/plan.md`.

## Environment and preservation

Real local site: `http://localhost:10013`. Isolated agent-browser sessions: `sfa-admin`, `sfa-customer`, `sfa-guest`. Customer session was authenticated using documented Local admin auto-login followed by the existing **Login as Finley Sullivan** control; no customer password was guessed or changed.

Customer: #224, **Finley Sullivan**, login `demo260927_220`, email `demo.260927.220@example.test`, roles `customer`, `demo_member`, `demo_premium`. Existing subscription: #8326, active lifetime **Demo · Lifetime Resource Library**. Existing related order: #8327, completed, USD 349. No fixture, order, subscription, payment, address, account form, cart removal, or Place Order action was submitted. A later explicit authorization allowed one credit-product add-to-cart attempt in a separate throwaway guest session; that exception and its automatic redirect are recorded below.

Original activation/modules/account menu/profile configuration and customer address/persistent-cart data are recorded in `../state/initial.json`. Both customer and guest carts were visibly empty.

WooCommerce initially gated the entire site, including authenticated customers, behind **wp-test2 is coming soon**. `woocommerce_coming_soon=yes`, `woocommerce_store_pages_only=no`, and `woocommerce_private_link=no`; no enabled existing private link was available. The lead explicitly authorized temporarily changing only the local `woocommerce_coming_soon` option from `yes` to `no` for this scoped QA, followed by exact restoration. Original visibility state and a hash of the undisclosed share key are in `../state/visibility-initial.json`. Initial gate screenshots are `pro/customer-account-dashboard.png`, `pro/customer-after-login.png`, and `pro/guest-shop.png`.

## Pro-active coverage completed

| Scenario | Browser result | Evidence |
|---|---|---|
| Customer dashboard | Logged-in customer dashboard renders; saved label **Memberships** and count 1 appear | `pro/customer-dashboard.png`, `pro/account-navigation.json` |
| Subscriptions list | Existing active subscription #8326 renders with lifetime price and features | `pro/customer-subscriptions.png` |
| Subscription details | Own subscription #8326 displays status/product/start date/lifetime amount and own order #8327 | `pro/customer-subscription-8326.png`, `pro/subscription-detail-snapshot.txt` |
| Orders / own order | Existing completed order #8327 is visible and details render | `pro/customer-orders.png`, `pro/customer-order-8327.png`, order snapshots |
| Downloads | Existing member download entitlements render with remaining counts/expiry; no download action performed | `pro/customer-downloads.png`, `pro/downloads-snapshot.txt` |
| Addresses | Original billing/shipping address displays unchanged | `pro/customer-addresses.png`, `pro/addresses-snapshot.txt` |
| Account details | Original name/display name/email render; form not submitted | `pro/customer-account-details.png`, `pro/account-details-snapshot.txt` |
| Store Credit | Existing USD 40 balance, promotional history, purchase-card navigation render; no buy action performed | `pro/customer-store-credit.png`, `pro/store-credit-snapshot.txt` |
| My Features | Expected feature/entitlement/usage table; no feature-type column, badge, or matching type-derived class/data attribute found | `pro/customer-features.png`, `pro/features-snapshot.txt`, `pro/features-dom-privacy.json` |
| Guest account / premium account URLs | Login form appears; no member #224 data exposed | `pro/guest-account.png`, `pro/guest-account-snapshot.txt`, `pro/guest-features-snapshot.txt`, `pro/guest-store-credit-snapshot.txt` |
| Guest shop and lifetime product | Catalog/product render, expected feature values visible; no cart action performed | `pro/guest-shop-open.png`, `pro/guest-product.png` |
| Empty carts | Guest and customer carts remain empty | `pro/guest-cart.png`, `pro/customer-cart.png` |

Observed premium account endpoints: `/my-account/as-store-credit/` and `/my-account/as-features/`. Core subscription endpoints: `/my-account/as-subscriptions/` and `/my-account/as-view-subscription/8326/`. Product examined: `/product/demo-%c2%b7-lifetime-resource-library/`.

## Free-only comparison completed

Only local ArraySubs Pro was temporarily deactivated; complete fresh documents were requested for every route. Core dashboard, subscriptions list/details #8326, orders, downloads, addresses, account-details form, shop, lifetime product and empty carts render. Screenshots/snapshots are in `free/`. Pro Store Credit/My Features navigation entries disappear. Visiting their former URLs directly yields a normal theme 404 page, without a PHP fatal or broken REST fetch.

With the premium Profile Builder absent, the runtime account navigation uses the default **Subscriptions** label/order instead of saved **Memberships**. The saved menu configuration itself remains byte-for-byte equivalent in the state comparison; no menu reset/save occurred.

Additional targeted checks confirmed a credit-shortcode page fatal and premium products remaining purchasable without Pro. The source audit also found missing variation feature updates. The initial variation fixture used invalid schema and cannot establish that frontend defect by itself; the final corrected fixture verifies the fix. Full fixture/routes/reproduction/proof fields and this limitation are in [findings](findings.md). Root owns their board entries and fixes.

For the explicitly authorized purchase-guard proof, guest-only `sfa-credit-throwaway` began with an empty cart and submitted **Purchase now** on existing credit product #6725. WooCommerce accepted it with an added-to-cart notice and automatically redirected to `/checkout-2/`; no checkout control, contact value, payment value, or Place Order action was used. The worker immediately navigated to cart, captured evidence, and closed that throwaway session. The cart screenshot was taken while Woo block hydration remained a skeleton. Its malformed `undefinedwc/store/v1/cart` request is a separate ownership investigation, not a completed cart-render pass. Primary customer #224 and the ordinary guest session retained empty carts.

Pro was reactivated promptly after Free comparison. `../state/post-free-comparison.json` confirms exact activation/modules/portal/profile/menu/field configuration preservation. Account/name/roles/billing/shipping data match. WooCommerce initialized a previously absent customer `_woocommerce_persistent_cart_1` as an empty cart during login; final restoration will remove only this new empty key conditionally, preserving any nonempty concurrent cart.

## Existing/coexistence observations

- The subscriptions page contains ArraySubs' correct active-subscription card followed by another empty **No subscriptions available yet** table. The separate `subscription` / `subscription-pro` products are active in this development site. Treat this as a pre-existing coexistence observation until source ownership is confirmed; no plugin was disabled to investigate it.
- Theme navigation lists the demo site's many existing pages; this was present before the checks.
- Customer browser recorded one generic WordPress **AbortError: Transition was skipped** during document navigation, with no source URL/line. Console warnings include existing JQMigrate deprecations, WordPress `data-wp-init--mark-as-hydrated` deprecation, and WooCommerce block dependency warnings. They are recorded separately from ArraySubs endpoint boundary results. Guest browser reported no page exception.
- Stored network inventories redact nonce/security query values. No customer passwords, share keys, or auth state files are stored in this evidence folder.

## Completed fix reverification

| Scenario | Browser result | Evidence |
|---|---|---|
| Variable features, valid canonical schema | Product #19371: Basic #19373 shows Basic download credits 10; Pro #19375 shows Pro download credits 20; Inherited #19377 shows Parent library credits 5. Reset hides the wrapper. No raw type fields or type-derived attributes appear; variation payload contains only escaped `arraysubs_features_html` among feature/type keys | `after/variation-{basic,pro,inherited}-valid.png/json`, `after/variation-reset.png/json` |
| Credit shortcode | Page #19379 renders the requested $25 credit form and Buy Credits; add-to-cart product ID is #6725; no fatal | `after/shortcode-product-6725.png`, `after/shortcode-form.json` |
| Pro premium product availability | Credit #6725 retains Credit Amount $25 / Buy Credits; bundle #6728 retains component review, $50.40 monthly total and Subscribe Now | `after/pro-credit-product-6725.png`, `after/pro-bundle-product-6728.png` |
| Free premium product guards | Fresh credit #6725 and bundle #6728 documents show unavailable notices and no main purchase form. Direct credit add-to-cart is rejected in a new isolated guest session, cart count stays 0 | `after/free-credit-product-6725.png`, `after/free-bundle-product-6728.png`, `after/free-credit-addcart-denial.png`, `after/free-credit-denial-dom.json` |
| Premium pending-order payment | Disposable manual order #19408/customer #224/product #6725 shows the $25 payment form under Pro. With Pro absent it shows an unavailable notice, no payment form/button, and no Pay action in the account order row. Payment was never submitted | `after/pro-order-pay-19408.png`, `after/free-order-pay-19408.png`, `after/free-orders-19408.png` |
| Avatar UI and replacement controls | Disposable customer #387 only: upload creates a working Remove button; replacement upload disables Change/Remove and displays Uploading; custom confirmation cancellation preserves the photo; confirmation displays Removing spinner/disabled controls, then success/default avatar. A subsequent upload and second removal also work. Three POST and two DELETE avatar requests all return HTTP 200 | `after/avatar-replacement-inflight.png`, `after/avatar-remove-dialog.png`, `after/avatar-remove-cancelled.png`, `after/avatar-remove-inflight.png`, `after/avatar-second-remove-final.png`, `after/avatar-requests.txt`, `after/avatar-387-flow.webm` |

The initial variable fixture used `label` instead of canonical `name`; storage normalized those rows to empty arrays. Therefore `pro/variation-*-before` and transitional `after/variation-basic-current.png` / `variation-debug.json` are invalid-schema observations, not a valid configured-feature before/after comparison. Root explicitly rewrote disposable #19371/#19373/#19375 with canonical names and values, confirmed in `../corrected-variation-fixtures.json`. The final `*-valid` evidence above is the authoritative browser result. Existing malformed product #6722 was preserved.

Saved policy disables cancellation for existing #8326, so its advisory is expected. No cancellation/lifecycle action was performed in the browser. Root's separate disposable REST regression covers required cancellation reason/timing and ownership.

The first Free product recheck still showed Purchase now on related unavailable credit cards; root subsequently added a Read more filter and verified it with real WordPress assertions in `../free-loop-buttons-after.json`. The browser was not rerun after this final related-card wording change. Main product/add-to-cart/payment guards were verified in the browser as recorded above.

## Exact restoration completed

Pro is active. All own browser sessions, including the avatar and throwaway sessions, are closed. `woocommerce_coming_soon` was restored from temporary `no` to its original `yes`; private-link/store-pages-only options and undisclosed share-key hash match the original. The only newly initialized customer #224 persistent-cart key was deleted conditionally after confirming it was an empty cart and originally absent. No nonempty cart was removed.

`../state/final-comparison.json` confirms equality for active plugins, modules, portal/profile settings, saved menu/profile fields, complete selected customer #224 account/roles/billing/shipping/meta values, page URLs and visibility. Restoration details are `../state/restoration.json`; final values are `../state/final.json` and `visibility-final.json`. Root completed disposable cleanup before this final snapshot: original absent avatar option restored by deletion; customer #387/custom avatar, pending unpaid order #19408, products/variations #19371/#19373/#19375/#19377 and shortcode page #19379 removed. All cleanup assertions pass in `../cleanup.json`. Avatar account Save changes was never submitted, and no existing-customer avatar was changed. No browser work remains pending.
