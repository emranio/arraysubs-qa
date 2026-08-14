# SLT-PROD-09: grouped multi-subscription submit silently replaces the first child and reports both as added

## QA context

- QA progress task: `#39` / `SLT-PROD-09`
- Stage/day: subscription lifecycle test D03 (2026-08-05)
- Date found: 2026-08-05
- Watch day: D03
- QA plan: `kanban/tasks/039-slt-prod-09-create-slt-grouped-set-a-grouped.md`
- Severity: medium (incorrect success feedback on an enforced cart restriction)

## Affected records

- Subscription IDs: N/A — cart-only probe; no checkout or subscription was created.
- Order IDs: N/A — cart-only probe; no order was created.
- WordPress user/customer IDs: N/A.
- Login/email/role: guest browser, not logged in.
- Products: grouped parent `12586` (`SLT Grouped Set`), subscription children `11927` (`SLT Daily Core`) and `12577` (`SLT Signup Fee Daily`).

## Environment and route

- Site: `https://mirror-help.arrayhash.com`
- Exact test URL: `https://mirror-help.arrayhash.com/product/slt-grouped-set/`
- Cart route: `https://mirror-help.arrayhash.com/cart/`
- Browser context: isolated `guest-SLT-PROD-09` agent-browser session.
- Gateway: N/A — the finding occurred before checkout or payment selection.
- Checkout type: N/A — storefront/grouped-add and cart-only probe.
- Relevant settings at reproduction: `multiple_subscriptions.allow_multiple_in_cart=false`; `multiple_subscriptions.allow_mixed_cart=true`.

## Reproduction steps

1. Start with the guest cart visibly empty.
2. Open the grouped parent product.
3. Set quantity `1` for `SLT Daily Core` and quantity `1` for `SLT Signup Fee Daily`; leave `SLT Grouped Extra` empty.
4. Submit **Add to cart** once.
5. Read the product-page notice and mini-cart count, then open `/cart/` and inspect the retained line.

## Expected result

- With multiple subscription lines disabled, one child is refused.
- A WooCommerce error/info notice explains that multiple subscriptions are not allowed.
- The surviving line is identifiable so the customer can correct the choice.

## Actual result

- The cart restriction is enforced at the data level: the cart count is `1`, and `/cart/` contains only `SLT Signup Fee Daily` with its USD `24.00` first-charge total.
- No refusal/error notice is rendered.
- The product page instead renders this false success notice verbatim:

  `“SLT Daily Core” and “SLT Signup Fee Daily” have been added to your cart. View cart`

- The first requested child (`SLT Daily Core`) was silently discarded while the second child (`SLT Signup Fee Daily`) won.

## Concrete proof

- `/home/server-manager/slt-evidence/SLT-PROD-09-03-probe-b-multiple-refused.png` shows the false two-product success notice with the cart badge at one item.
- `/home/server-manager/slt-evidence/SLT-PROD-09-03b-probe-b-one-line-cart.png` shows the sole surviving `SLT Signup Fee Daily` cart line.
- Browser DOM notice read: `“SLT Daily Core” and “SLT Signup Fee Daily” have been added to your cart.\nView cart`.
- Cart DOM read showed only `SLT Signup Fee Daily`, subtotal USD `9.00`, `Subscription Signup Fee` USD `15.00`, estimated total USD `24.00`.
- Browser error buffer was empty; this is a server/UI feedback mismatch, not a browser exception.

## Scope notes and counterexamples

- Probe A on the same grouped page, adding only `SLT Daily Core`, works and produces a single USD `10.00` subscription line.
- The grouped parent itself remains non-subscription and has no visible ArraySubs subscription controls, so the mismatch is specific to the simultaneous two-subscription child submit.
- Probe C on the same grouped page is a passing counterexample: `SLT Daily Core` plus plain child `SLT Grouped Extra` produced two lines, USD `10.00` + USD `3.00`, estimated total USD `13.00`, with no error. Evidence: `/home/server-manager/slt-evidence/SLT-PROD-09-04-probe-c-mixed-cart.png`.
- No ArraySubs or ArraySubsPro source was inspected or changed while recording this issue.

## Resolution — 2026-08-14

### Investigation and corrected oracle

- Reproduced the report before editing with live products `12586`, `11927`, `12577`, and `12583` under the reported settings. The notice claimed both subscription children were added while the cart contained only `SLT Signup Fee Daily`.
- WooCommerce's grouped handler records every child whose `add_to_cart()` call succeeds and builds one aggregate notice after the loop. ArraySubs' priority-20 one-click hook then intentionally removes earlier lines after each successful one-click add, so WooCommerce's pre-removal success list was stale by notice time.
- The earlier requirement that the second subscription be refused was not the current product contract. `checkout.one_click_mode=subscription_items` explicitly clears earlier cart contents and keeps the later one-click item. The independently resolved `SLT-CHK-12` control proved that default mode still owns the multiple-subscription refusal behavior. The confirmed PROD-09 defect was therefore false aggregate feedback, not the replacement itself.

### Fix and dependency/security review

- Updated core `SubscriptionCheckout` one-click notice handling to compare multi-product success maps with the actual post-hook cart. When a requested one-click item has displaced an earlier requested line, the success notice now names only retained products and identifies the removed products with a one-click explanation.
- Single-product notices, correctly retained grouped products, the one-click cart mutation, redirects, default-mode composition validation, and Pro gateway restrictions are unchanged.
- Product IDs and quantities are normalized before use. Product titles are stripped and the complete customer-facing sentence is escaped; only the original WooCommerce action-link fragment is retained through `wp_kses_post()`. No request data is trusted as HTML and no cart, order, subscription, REST, nonce, capability, or scheduler contract changed.

### Verification

- Exact failing submit now renders: `“SLT Signup Fee Daily” has been added to your cart. One-click checkout removed “SLT Daily Core” because the last selected one-click item replaces earlier cart items.` The badge and cart both contain exactly the surviving Signup Fee line at `$24.00` first charge.
- Single-child control remained the native `“SLT Daily Core” has been added to your cart.` and produced exactly one `$10.00` line.
- Mixed grouped control remained the native two-product success notice and produced `SLT Daily Core` plus `SLT Grouped Extra`, exactly two lines and `$13.00` total.
- Browser error buffer stayed empty. Console output contained only the pre-existing WooCommerce dependency warning and JQMIGRATE messages.
- Final settings remained `allow_multiple=false`, `allow_mixed=true`, `one_click_mode=subscription_items`, and `disable_cart_page=false`; user `347`'s persistent cart was restored to an exact empty array. No checkout, order, subscription, email, or product mutation occurred.
- `git diff --check` passed. Per the QA issue-fix workflow, PHPCS and lint commands were not run.

### Evidence

- `/home/server-manager/slt-evidence/HIGH-SLT-PROD-09-before-false-notice.png`
- `/home/server-manager/slt-evidence/HIGH-SLT-PROD-09-before-one-line-cart.png`
- `/home/server-manager/slt-evidence/HIGH-SLT-PROD-09-after-truthful-notice.png`
- `/home/server-manager/slt-evidence/HIGH-SLT-PROD-09-after-one-line-cart.png`
- `/home/server-manager/slt-evidence/HIGH-SLT-PROD-09-control-single.png`
- `/home/server-manager/slt-evidence/HIGH-SLT-PROD-09-control-mixed-notice.png`
- `/home/server-manager/slt-evidence/HIGH-SLT-PROD-09-control-mixed-cart.png`
