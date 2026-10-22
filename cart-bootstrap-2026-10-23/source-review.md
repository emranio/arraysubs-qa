# Independent Cart/Mini-Cart source review

Task 90, 2026-10-23. Reviewed both ArraySubs plugins, installed WooCommerce 11.1.2 / WordPress, and the browser owner's saved baseline artifacts. No product or site changes were made by this reviewer. The lead separately verified the WooCommerce package checksum.

## Causal proof

1. WooCommerce `src/Blocks/BlockTypes/MiniCart.php:277–279` intentionally returns a fallback on configured Cart/Checkout pages before `BlocksSharedState::load_cart_state()` at line 292. `get_markup()` at lines 616–627 returns either empty output or a hidden, disabled button. That branch has no active Mini-Cart directives and does not require interactive cart state.
2. WooCommerce `assets/client/blocks/mini-cart/block.json:10` nevertheless declares `viewScriptModule: woocommerce/mini-cart`. WordPress `wp-includes/class-wp-block.php:665` calls Woo's render callback, then lines 684–686 enqueue that metadata module regardless of the fallback. The public block-specific render filter runs afterward, at line 732.
3. The imported `@woocommerce/stores/woocommerce/cart.js` calls `refreshCartItems()` at module initialization. Its fetch concatenates `state.restUrl + wc/store/v1/cart`; without `BlocksSharedState` initialization, this produces the observed relative `/cart-2/undefinedwc/store/v1/cart` and repeated retries. The malformed fetch is owned by this Woo module, not ArraySubs' trial-price filter or checkout session guard.
4. Browser artifacts `browser/before/empty-cart-raw-response.html` and `empty-cart-runtime.json` confirm the configured Cart has a hidden fallback, an enqueued Mini-Cart module, and no interactivity JSON state even before client execution. On the alternate unconfigured `/cart/` page, `alternate-cart-raw-response.html` / `alternate-cart-runtime.json` show a visible active Mini-Cart with serialized interactivity state. This matches the two PHP branches.

## Main Cart correction

The original skeleton observation was transitional. Fresh empty Cart mounts React and renders its empty message. The populated Cart resolves after waiting for its actual product text, before any store-selector inspection; it reports quantity 1, total USD 25, and no Cart errors. The malformed Mini-Cart fetch coexists with that successful rendering. This evidence does not establish a separate main Cart hydration failure.

The fresh document includes complete `wc-settings-js-before` data and `wp-api-fetch-js-after` root/nonce middleware. Absence of a `wcSettings.storeApiRoot` or `wpApiSettings` global is not evidence of missing bootstrap in this Woo version. Earlier incomplete settings observations should not drive a global configuration or reload fix.

## Reviewed implementation

`arraysubs/src/Features/SubscriptionCheckout/Services/MiniCartCompatibility.php` (56 lines, reviewed in `.worktrees/arraysubs-cart-bootstrap`) correctly uses the public `render_block_woocommerce/mini-cart` filter after WordPress' metadata enqueue. It:

- Acts only on `is_cart()` / `is_checkout()` pages.
- Preserves output containing any `data-wp-interactive` directive, using `WP_HTML_Tag_Processor`; this also covers the JSON namespace syntax supported by WordPress.
- Dequeues only `woocommerce/mini-cart` for the inactive fallback and returns the exact markup unchanged.
- Leaves normal shop/product/alternate-page drawer behavior and unrelated script modules alone.
- Lives in the existing always-Free SubscriptionCheckout provider service loader; Pro needs no parallel implementation or asset build.

No actionable source issue was found in this implementation. Initializing Woo's consent-gated private `BlocksSharedState` API would add unused behavior to an intentionally noninteractive fallback; removing its unnecessary module follows Woo's existing rendering decision. WordPress' public dequeue API and HTML processor are available within ArraySubs' declared WordPress 6.6 minimum. Module printing follows rendering for both block themes (`wp_head`) and classic themes (`wp_footer`), so this render filter is early enough for either.

## Both-plugin coverage and limits

Core `SubscriptionProducts/Services/Hooks.php:1071–1105`, `resources/storeApiCartSubscription.js`, and its styles only register subscription display filters and scoped presentation. The enqueue declares `wc-blocks-checkout`; the entry does not replace globals, fetch middleware, or transport. `SubscriptionProducts/Services/StoreApiIntegration.php` only supplies namespaced trial data with defensive empty defaults. `SubscriptionCheckout/Services/Hooks.php:369–399` / `resources/checkoutSessionGuard.js` operate on block checkout session reconciliation and do not construct Cart REST URLs.

Pro CheckoutBuilder's hydration response filter is restricted to `/wc/store/v1/checkout`; flexible-product, bundle, and redirect integrations do not replace the Cart bootstrap. Searches across both plugins found no `wcSettings`/`wpApiSettings` assignment, API root middleware replacement, module-state manipulation, global fetch replacement, script-loader filter, or Woo script dequeue responsible for this failure.

This is a source review plus interpretation of the browser owner's actual artifacts. Parent task 90 owns post-fix empty/populated Cart, quantity/totals/removal, Checkout navigation, normal Mini-Cart drawer, Free/Pro, cleanup, and restoration acceptance. No order placement or payment is covered or required.
