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
