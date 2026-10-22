# My Account and storefront boundary audit — 2026-10-23

Active plan/task: [plan](plan.md), issue #86, immediate manual run on 2026-10-23 (Asia/Dhaka). This extends the completed [Free member-details fix](../member-details-free-2026-10-23/report.md). No remote deployment or gateway payment was performed.

## Result and scope

The checked core My Account pages work with Free alone. Premium account navigation is removed when Pro is inactive; direct old premium account URLs show the normal theme 404 rather than broken API calls or PHP errors. Source audit found no additional Free customer page dependent on a Pro-only REST endpoint. Customer-facing credit/feature responses use the authenticated user; subscription-specific reads enforce ownership, and customer feature payloads omit internal feature types.

Real browser coverage used local WordPress http://localhost:10013 with agent-browser and isolated admin/customer/guest sessions. Customer #224 (Finley Sullivan, demo260927_220, demo.260927.220@example.test; customer/demo_member/demo_premium) owns subscription #8326 and order #8327. Existing account, address, subscription and payment data were not changed. Full screenshots, snapshots and network observations are indexed in [browser report](browser/report.md).

| Coverage | Pro active | Free alone |
| --- | --- | --- |
| Dashboard; subscription list/details #8326 | Passed | Passed |
| Orders / own completed order #8327 | Passed | Passed |
| Downloads, addresses, edit-account | Passed; no form submissions | Passed; no form submissions |
| Store Credit and My Features | Passed; type privacy checked | Navigation removed; direct old URLs normal404 |
| Guest account/premium account routes | Login required; member data not exposed | Login/core behavior retained |
| Shop and representative lifetime product | Passed | Passed |
| Premium credit/bundle availability | Correct provider forms retained | Unavailable; direct add-to-cart refused |
| Unpaid initial premium order-pay #19408 | Normal form displayed; never submitted | Unavailable; no payment form or My Account Pay action |
| Cart block | Existing Woo interactivity problem; see limitation | Same limitation; not claimed as full hydrated-cart pass |

Source review also covered saved/custom endpoint slugs, disabled premium navigation, profile data retention, conditional portal scripts, cancellation fallback and feature-type privacy. Request-local disabled-module cases supplement browser Pro-absent checks; they do not represent changing saved module settings, which can legitimately draft products.

## Completed fixes

- **Core unavailable-product guard:** raw saved product types are checked even when WooCommerce falls back to WC_Product_Simple because Pro is absent. Purchase eligibility, add-to-cart, saved-cart validation and unpaid initial order payment refuse unavailable providers. The Store API guard runs before checkout can update the order/customer or call a gateway. Catalog links say Read more. Product data and existing subscription records are preserved. Existing renewal/plan-change invoice settlement remains governed by its existing rules.
- **Cancellation #87:** removed the unused duplicate Pro cancellation route that bypassed core cancellation policy. The canonical core customer route enforces required reasons and merchant timing/permissions. Invalid cancellation-form configuration returns its controlled error instead of a PHP return-type failure.
- **Variation features #88:** selected variations expose escaped feature-list HTML only, inherit parent lists through the existing helper, and reset correctly. Browser acceptance shows Basic19373=10, Pro19375=20, inherited19377=parent5, with no type-derived attributes or raw feature configuration.
- **Specific-product credit shortcode:** uses WooCommerce's product query context and restores surrounding globals, so the correct $25 credit product renders on an ordinary page without the earlier critical error.
- **Avatar #89:** dynamically created Remove controls receive the shared loading state; upload/removal actions serialize and async confirmation remains retryable after failure. Browser acceptance passed initial upload, replacement upload, cancel, removal and a second upload/remove cycle using disposable customer387. Upload/removal controls and the custom confirmation showed disabled/loading states; three upload and two delete requests returned200. No Account Save was submitted.

Changes are committed in the local core/Pro main checkouts. Production Pro assets rebuilt successfully. No PHPCS, Composer install, release/version change, push or deployment was performed. Source line counts and whitespace validation passed; see [source counts](source-line-counts.json).

## Validation and evidence

- [Customer REST Pro](customer-rest-pro.json): 37 checks. [Customer REST Free](customer-rest-free.json): 22 checks using only request-local --skip-plugins=arraysubspro. Read-only owner/other/guest checks, current-user identity/balance behavior, admin API restrictions and feature-type omission. The virtual lifetime subscription correctly returns its controlled no-shipping400 and no-payment-method404.
- [Cancellation regression](cancellation-rest-after.json): 22 checks with real REST requests using disposable manual-payment subscriptions/users; required/optional reasons, merchant timing, disabled cancellation, different owner and guest denial, retired-route404 and invalid admin configuration. Existing settings and active plugins unchanged; own fixtures deleted.
- [Purchase/payment Free](product-availability-rest-free.json): 47 checks; [Pro](product-availability-rest-pro.json): 49 checks with actual premium product classes. [Regression source](product-availability-regression.php) runs real WordPress/WooCommerce product/cart/Store API checks for unavailable provider purchases and payment refusal before order mutation, plus paid/renewal/plan-change exemptions. Results are stored alongside this report.
- [Browser after evidence](browser/after/): selected/inherited/reset feature lists, shortcode form product6725, Pro credit/bundle provider forms, Free product/cart refusal, and order-pay guard.
- Isolated service/DOM checks supplement real browser and real REST checks: 15PHP+10DOM variation cases and 24 avatar cases, including disabled rows, inheritance, reset/isolation, escaping/privacy and async failure/retry states.

Fixture correction: initial variable-product screenshots used malformed label-only feature rows, which canonical storage normalized to empty lists. Those screenshots do not independently reproduce a missing list for valid feature rows. The original source's explicit return for variation-level features and absence of a variation handler established the source defect. The final canonical name-keyed fixture values were read back and all three variants/reset passed in the real browser. Existing malformed demo product6722 was not modified.

## Separate environment limitation

Issue #90 records WooCommerce11.1.2's malformed /cart-2/undefinedwc/store/v1/cart request. Runtime evidence shows its cart interactivity module loaded without serialized shared state. The installed Woo module concatenates state.restUrl with wc/store/v1/cart; missing restUrl produces the observed relative path. MiniCart exits on Cart/Checkout before its shared-state initialization. No ArraySubs/Pro write to that state or API root was found. This attributes the malformed request to Woo's interactivity bootstrap; whether it fully explains the main Cart skeleton remains unverified. No WooCommerce/theme source was changed, and this report does not claim a fully hydrated Cart or completed checkout/gateway test.

A separate active WPSubscription plugin adds a second empty subscription table below the working ArraySubs cards. This is preserved as a pre-existing coexistence observation; no sibling plugin was disabled to alter it.

## Restoration

Completed. [Final equality](state/final-comparison.json) confirms exact active_plugins/modules/portal/profile/menu/field configuration, customer224 account/roles/billing/shipping/meta, URLs and Coming Soon visibility restoration. Pro is active; Coming Soon is back to its original yes. Only a newly initialized empty persistent-cart key was conditionally removed to restore the original customer metadata shape. Own browser sessions are closed. [Cleanup proof](cleanup.json) confirms deletion of hidden variable product19371, variations19373/19375/19377, shortcode page19379, unpaid pending manual order19408 and disposable avatar customer387; the originally absent avatar option is absent again. Cancellation and purchase/payment regression scripts separately delete and verify all their own fixtures in finally cleanup. No existing subscription lifecycle or payment was changed.
