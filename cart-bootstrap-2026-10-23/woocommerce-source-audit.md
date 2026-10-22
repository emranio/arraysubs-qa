# WooCommerce Cart and Mini-Cart source audit

Task #90, active plan `qa/cart-bootstrap-2026-10-23/plan.md`, 2026-10-23. Read-only source audit by endpoint_audit; lead owns implementation, browser acceptance and state restoration.

Installed versions recorded in `browser/state-initial.json`: WordPress 7.1.3, WooCommerce 11.1.2, Twenty Twenty-Five. The lead verified the installed WooCommerce package against official checksums; see `woocommerce-checksums.txt`. This finding does not depend on an edited WooCommerce source file.

## Confirmed cause

The default Mini-Cart on the configured Cart/Checkout pages deliberately renders a noninteractive fallback, but WordPress still enqueues its metadata-declared interactive module. That module starts a cart request without the shared WooCommerce interactivity state initialized by the normal Mini-Cart path.

The installed source establishes the full chain:

1. `woocommerce/assets/client/blocks/mini-cart/block.json:10` declares `viewScriptModule: woocommerce/mini-cart`.
2. `woocommerce/src/Blocks/BlockTypes/MiniCart.php:152–155` skips Mini-Cart asset data on `is_cart()` or `is_checkout()`.
3. `MiniCart.php:277–278` returns the fallback before the explicit module enqueue at line 281 and before shared state/config initialization at lines 289–292.
4. `MiniCart.php:615–623` makes the intended fallback explicit: it returns empty output for the removed style, or a wrapper with `visibility:hidden`, `aria-hidden=true` and a disabled button. This markup has no `data-wp-interactive` directive and needs no interactive hydration.
5. WordPress invokes the block's render callback in `wp-includes/class-wp-block.php:665`, then independently enqueues its `view_script_module_ids` at lines 684–686. Returning early from WooCommerce's render method therefore does not prevent the metadata enqueue.
6. `woocommerce/assets/client/blocks/interactivity-blocks-frontend-assets.php` registers `woocommerce/mini-cart` as depending on `@woocommerce/stores/woocommerce/cart`.
7. The installed `@woocommerce/stores/woocommerce/cart.js` calls `refreshCartItems()` at module evaluation. Its fetch URL is the concatenation of the WooCommerce store's `state.restUrl` and `wc/store/v1/cart`; it retries after failures.
8. `woocommerce/src/Blocks/Utils/BlocksSharedState.php:99–129` is the normal initialization path for this state. In particular, lines 121–128 define `woocommerce.cart`, `noticeId` and `restUrl = get_rest_url()`. The Mini-Cart fallback skips this path.

The concrete browser request is recorded in `browser/before/empty-cart-requests.txt` as:

`GET http://localhost:10013/cart-2/undefinedwc/store/v1/cart`

`browser/before/empty-cart-runtime.json` independently records the Mini-Cart module present, the hidden Mini-Cart fallback, and no serialized interactivity data. Other blocks which initialize shared cart state could mask the malformed URL on some pages; their presence does not make the fallback's module necessary.

## Main Cart is a separate flow

The settled empty and populated baseline browser evidence does not support a persistent main Cart skeleton failure:

- `browser/before/empty-cart-runtime.json` records a React container on the main Cart, no `is-loading` class, and the actual “Your cart is currently empty!” view.
- Its ordered script inventory contains `wp-api-fetch-js-after` with both root-URL and nonce middleware at order 35, followed by `wc-settings-js-before` at order 36. The latter contains the WooCommerce settings and preloading bootstrap.
- Sanitized inline scripts are preserved as `browser/before/wp-api-fetch-js-after-sanitized.js` and `browser/before/wc-settings-js-before-sanitized.js`; raw-response/script inventory artifacts are alongside them.
- The populated ordinary-product Cart also settled before the compatibility change, as recorded in `browser/before/ordinary-main-cart-stable.png`. The initial loading observation therefore did not reproduce as a persistent failure after settling.

The source matches that separation:

- `woocommerce/src/Blocks/BlockTypes/Cart.php:120–126` uses the classic `wc-cart-block-frontend` React script rather than the Mini-Cart script module.
- `Cart.php:239–281` registers its normal data and cart API hydration.
- `woocommerce/src/Blocks/Assets/AssetDataRegistry.php:71–74,396–415` emits settings/preloading before the `wc-settings` script during footer script printing.
- WordPress configures `wp.apiFetch` directly through `createRootURLMiddleware(get_rest_url())` in `wp-includes/script-loader.php:351–360` and nonce middleware at lines 362–379. A missing `window.wpApiSettings` does not establish missing API bootstrap.
- `woocommerce/src/Blocks/BlockTypes/AbstractBlock.php:515–547` defines `wcBlocksConfig` without an API-root field. Its absence there is expected.

The Mini-Cart request defect is confirmed independently. Its correction eliminates the malformed background request; the baseline does not establish that it fixes a persistent main Cart loading failure. The compatibility service is not a replacement for main Cart hydration or Store API initialization.

## Public-API correction and ownership

The lead's candidate is `arraysubs/src/Features/SubscriptionCheckout/Services/MiniCartCompatibility.php`, initially developed in `.worktrees/arraysubs-cart-bootstrap/`. It belongs to the always-available core checkout integration; Pro and premium module settings are not prerequisites. The SubscriptionCheckout provider already loads service classes from its `Services` directory.

The reviewed service uses the public `render_block_woocommerce/mini-cart` filter. On WooCommerce's native Cart/Checkout conditionals, it checks the returned markup with `WP_HTML_Tag_Processor`. If any `data-wp-interactive` attribute exists, it preserves the module. Otherwise it calls public `wp_dequeue_script_module('woocommerce/mini-cart')` and returns the original markup.

This hook timing is suitable: `wp-includes/class-wp-block.php:732` invokes the per-block render filter after the metadata enqueue at lines 684–686. WordPress prints block-theme head modules through `WP_Script_Modules::print_head_enqueued_script_modules()` and other queued modules in the footer; the render filter removes the unnecessary queue entry before that output. The dequeue implementation only removes the named queue entry (`wp-includes/class-wp-script-modules.php:326–328`). It does not deregister modules or remove other blocks' independently needed imports.

The interactivity-presence guard was adjusted during peer review. WordPress accepts both a literal namespace and a JSON namespace object in `data-wp-interactive` (`wp-includes/interactivity-api/class-wp-interactivity-api.php:1027–1045`). Checking presence preserves both formats and custom/future interactive markup; matching only the literal `woocommerce/mini-cart` string would miss valid JSON markup.

The reviewed scope is narrow:

- Uses configured WooCommerce conditionals, with no hardcoded page IDs or URL slugs.
- Preserves hidden/removed fallback markup and accessibility semantics.
- Leaves shop/product-page Mini-Cart behavior and any interactive Mini-Cart markup unchanged.
- Does not alter the main Cart script, cart store module, WooCommerce settings, API routes, request middleware, cart contents or checkout submission.
- Uses no private `BlocksSharedState` API, copied cart state, generated bundle changes, WooCommerce edits or saved configuration changes.

An upstream WooCommerce source correction could instead remove the metadata declaration and rely on its existing explicit enqueue in the interactive render path. Doing that through a plugin-wide metadata override would affect all Mini-Cart registrations and assume that explicit enqueue remains present in future versions. The fallback-aware render filter is the more bounded compatibility correction here.

## Other blocks importing the cart store

The installed `interactivity-blocks-frontend-assets.php` dependency map declares six direct block-module importers of `@woocommerce/stores/woocommerce/cart`:

| Block module | Source of cart state | Effect of the candidate |
| --- | --- | --- |
| `woocommerce/mini-cart` | Normal render calls `MiniCart.php:290`; native Cart/Checkout fallback skips it. | Dequeued only for the noninteractive fallback. |
| `woocommerce/product-button` | `ProductButton.php:83–101` resolves a real product and calls `BlocksSharedState::load_cart_state()`. This also happens for collection/cross-sell buttons which link to a product instead of adding it directly. | Preserved, together with its legitimate cart-store import. |
| `woocommerce/add-to-cart-with-options` | The form renders its type template through `do_blocks()` at `AddToCartWithOptions/AddToCartWithOptions.php:539–543`; all four shipped simple/variable/grouped/external templates contain a ProductButton child, which supplies the cart state. | Preserved; not part of the stored Cart content in the initial state snapshot. |
| `woocommerce/add-to-cart-with-options-grouped-product-selector` | Nested under the grouped add-to-cart form, with the same ProductButton state initialization in the shipped template. | Preserved; not part of the stored Cart content. |
| `woocommerce/saved-for-later` | Authenticated render explicitly loads cart state at `SavedForLater.php:169–176`; guests return empty output at lines 149–151. | Preserved, including its independently required cart-store import. |
| `woocommerce/wishlist` | Authenticated render explicitly loads cart state at `Wishlist.php:66–73`; guests return empty output at lines 48–54. | Preserved, including its independently required cart-store import. |

Consequently a real native ProductButton rendered inside the Cart's product-collection/cross-sells can initialize the shared cart state independently of Mini-Cart. In that case the cart-store module may correctly remain in the import map after the Mini-Cart module is removed. Verification should assert that the unused **Mini-Cart module** and malformed URL disappear, rather than asserting that every cart-store import/request disappears.

A useful browser regression is to link an ordinary fixture product as a cross-sell, confirm the actual server-rendered ProductButton and serialized WooCommerce `restUrl` exist, then add that cross-sell and confirm Cart totals update. Test the subscription recommendation separately when applicable. This covers the positive dependency path which the compatibility filter must preserve.

Custom add-to-cart form templates which omit the ProductButton, or suppress it through the nonpurchasable-form guard at `ProductButton.php:93–98`, can remove that form's ordinary source of shared cart state. That is a separate source-only edge requiring its own reproduction; it is not present in the recorded Cart content and is not evidence that task #90 should dequeue additional module roots.

## Counterexamples and verification boundary

On WordPress 7.1, a Mini-Cart configured to return empty output can already have newly added assets automatically removed by `WP_Block`'s empty-content cleanup at lines 756–780. The default hidden fallback is nonempty, so that cleanup does not run. The candidate's dequeue is idempotent for removed output.

Source review of the adjusted candidate found no remaining actionable defect. It proves the enqueue/state mismatch and correction scope; browser acceptance still belongs to task #90. Required acceptance includes configured Cart and Checkout, empty/populated cart actions, intact interactive Mini-Cart on another page, ordinary/subscription products and Free/Pro coverage. Keep malformed-request elimination separate from any claim about a persistent main Cart skeleton.

This audit did not edit product, WooCommerce, WordPress or theme files, mutate cart/site configuration, navigate a browser session, send mail or invoke payment/lifecycle actions.
