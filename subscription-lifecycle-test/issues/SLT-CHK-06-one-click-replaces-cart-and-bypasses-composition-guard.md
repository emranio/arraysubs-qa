# One-click subscription adds replace the current cart instead of enforcing the composition guard

- Severity: high
- Date found: 2026-08-07
- Watch day: D05
- Originating task: `SLT-CHK-06`
- Plan/task file: `kanban/tasks/077-two-different-subscription-products-in-one-cart.md`
- Affected subscriptions: N/A (no order was placed)
- Affected orders: N/A
- Affected products: 11927 (`SLT Daily Core`) and 11933 (`SLT Fixed Three Cycles`)
- Affected WordPress users: N/A; anonymous guest session
- Gateway: N/A
- Checkout type: classic cart plus block cart/block checkout observation; no checkout submission
- Browser context: anonymous `guest-SLT-CHK-06`
- Non-default settings in play: none relative to the frozen suite baseline. Relevant values were `multiple_subscriptions.allow_multiple_in_cart=false`, `allow_mixed_cart=true`, `one_per_customer=false`, `one_per_product=false`, `allow_different_cycles=true`, and `one_click_mode=subscription_items`.

## Exact routes

- `https://mirror-help.arrayhash.com/product/slt-daily-core/`
- `https://mirror-help.arrayhash.com/product/slt-fixed-three-cycles/`
- `https://mirror-help.arrayhash.com/page/2/?post_type=product&s=SLT&slt_cb=202608072001&add-to-cart=11933`
- `https://mirror-help.arrayhash.com/slt-classic-cart/`
- `https://mirror-help.arrayhash.com/cart/`
- `https://mirror-help.arrayhash.com/checkout/`

## Reproduction

1. In a fresh anonymous browser session, verify the classic cart is empty.
2. With `allow_multiple_in_cart=false` and `one_click_mode=subscription_items`, open product 11927 and click **Subscribe Now** once.
3. After the one-click redirect, reopen the classic cart and verify product 11927 is present at quantity 1 and subtotal `$10.00`.
4. Open product 11933 and click **Subscribe Now** once.
5. Observe the redirect to `/checkout/`, the success notice, and the checkout summary.
6. Reopen the classic cart and inspect its sole line.
7. Repeat from archive search page 2 by starting with 11927 in the cart and activating the exact archive add link for product 11933.
8. As a discriminator, empty the cart, add product 11927 twice from its standalone product page, and inspect the classic cart.

## Expected result

At steps 4-6 and 7, the second distinct subscription must be refused, product 11927 must remain the sole cart line, and the exact notice must be:

`Multiple subscription plans are disabled for one checkout. Keep only one subscription plan in the cart, then place a separate order for any other plan.`

At step 8, adding the same product twice must retain one product line at quantity 2 and subtotal `$20.00` because the rule counts distinct product IDs.

## Actual result

Both the standalone product path and archive path accepted product 11933, displayed `“SLT Fixed Three Cycles” has been added to your cart.`, and silently removed the pre-existing product 11927. The resulting cart contained only product 11933 at quantity 1 and subtotal `$7.00`. The authored rejection notice never appeared.

Adding product 11927 twice likewise left quantity 1 and subtotal `$10.00`; the second one-click add reset/replaced the cart rather than merging quantity.

## Concrete proof

- Initial empty cart: `/home/server-manager/slt-evidence/SLT-CHK-06-00-cart-empty-before.png`
- Product 11927 alone at quantity 1 / `$10.00`: `/home/server-manager/slt-evidence/SLT-CHK-06-01-classic-cart-one-sub.png`
- Product-page add success at checkout instead of rejection: `/home/server-manager/slt-evidence/SLT-CHK-06-02-rejection-notice.png`
- Classic cart after the distinct add, showing only product 11933 / `$7.00`: `/home/server-manager/slt-evidence/SLT-CHK-06-03-classic-cart-still-one.png`
- Clean single-product block cart and checkout counterchecks: `/home/server-manager/slt-evidence/SLT-CHK-06-04-block-cart-one.png`, `/home/server-manager/slt-evidence/SLT-CHK-06-05-block-checkout-clean.png`
- Archive add result at checkout: `/home/server-manager/slt-evidence/SLT-CHK-06-06-archive-ajax-result.png`
- Archive request: `GET /page/2/?post_type=product&s=SLT&slt_cb=202608072001&add-to-cart=11933`, followed by `GET /checkout/` status 200.
- Same-product discriminator showing quantity 1 / `$10.00` after two adds: `/home/server-manager/slt-evidence/SLT-CHK-06-07-same-product-qty2.png`
- Teardown proof: `/home/server-manager/slt-evidence/SLT-CHK-06-08-cart-empty-after.png`
- Exact text comparison: `/home/server-manager/slt-evidence/SLT-CHK-06-expected.txt`, `/home/server-manager/slt-evidence/SLT-CHK-06-rejection.txt`, `/home/server-manager/slt-evidence/SLT-CHK-06-rejection-cmp.txt`
- Consolidated browser/runtime facts: `/home/server-manager/slt-evidence/SLT-CHK-06-facts.txt`
- Mailpit baseline and final newest ID were both `5hm1LQfe2IKo0vIamcv9kd`; zero task-attributable mail was emitted.
- Browser errors were empty. Console output contained only WooCommerce dependency diagnostics and JQMIGRATE logs.

## Scope notes and counterexamples

- The defect reproduced on two add surfaces: standalone product and archive link. It is not limited to one template.
- A sole product 11927 rendered correctly in both block cart and block checkout at `$10.00` with no `arraysubs_cart_error`; the problem is the one-click add transition, not basic single-subscription rendering.
- The cart never contained both distinct subscriptions simultaneously. The failure is that the required refusal is bypassed by destructive replacement, which can silently discard a shopper's existing cart choice.
- The same-product quantity discriminator also failed, consistent with the one-click path clearing/replacing before validation rather than merging.
- No order was submitted, no mail was sent, and no browser error explained the behavior.
