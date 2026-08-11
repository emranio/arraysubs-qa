# Grouped sequential subscription add silently replaces the existing child

- Severity: high
- Date found: 2026-08-07
- Watch day: D05
- Originating test task: `SLT-CHK-12`
- Plan file: `kanban/tasks/080-exploratory-slt-grouped-set-rendering-add-to-cart.md`
- Affected subscriptions: N/A; no order was placed
- Affected orders: N/A
- Affected products: `12586` (`SLT Grouped Set`), `11927` (`SLT Daily Core`), `12577` (`SLT Signup Fee Daily`); control child `12583` (`SLT Grouped Extra`)
- Affected WordPress user: `367`, `slt-grouped`, `slt-grouped@example.test`, role `customer`
- Gateway: N/A at the failing step; the later unaffected checkout used Stripe test
- Checkout type: grouped storefront form and block cart; no payment was submitted for the failing step
- Non-default settings: none. Frozen values were `multiple_subscriptions.allow_multiple_in_cart=false` and `allow_mixed_cart=true`.
- Exact URL: `https://mirror-help.arrayhash.com/product/slt-grouped-set/?slt=SLT-CHK-12-PROBEB`
- Browser context: authenticated customer session `grouped-CHK12-SLT-CHK-12`

## Reproduction steps

1. Log in as customer `slt-grouped` and verify the cart is empty.
2. Open grouped parent product `12586` at the exact URL above.
3. Set quantity `1` only for child `11927` (`SLT Daily Core`) and submit **Add to cart**.
4. Reopen `/cart/` and verify the sole line is Daily Core quantity `1`, total `$10.00`.
5. Return to the grouped parent, set quantity `1` only for child `12577` (`SLT Signup Fee Daily`), and submit **Add to cart**.
6. Read the product-page notice and cart badge, then reopen `/cart/` and inspect the retained line.

## Expected result

The second distinct subscription child is refused because multiple subscription products are disabled. Daily Core remains the sole line and the UI explains that the second subscription must be ordered separately.

## Actual result

The product page displayed the success notice `“SLT Signup Fee Daily” has been added to your cart.` and kept a one-item cart badge. Reopening the cart showed only Signup Fee Daily at `$9.00` plus its `$15.00` signup fee (`$24.00` total). Daily Core was silently removed and no refusal notice appeared.

## Concrete proof

- Initial empty cart: `/home/server-manager/slt-evidence/SLT-CHK-12-00-cart-empty-before.png`
- Probe A with Daily Core alone: `/home/server-manager/slt-evidence/SLT-CHK-12-02-probe-a.png`
- False success notice after the sequential second-child add: `/home/server-manager/slt-evidence/SLT-CHK-12-03-probe-b-refusal.png`
- Consolidated runtime/state evidence: `/home/server-manager/slt-evidence/SLT-CHK-12-D05-execution.txt`
- The DOM notice was exactly `“SLT Signup Fee Daily” has been added to your cart.`
- The following cart DOM contained only `SLT Signup Fee Daily`, recurring subtotal `$9.00`, signup fee `$15.00`, estimated total `$24.00`.
- No order or subscription existed for user `367` after the probes; Mailpit remained at checkout baseline `2WHncuVdu6LGSuQ70KXk42`.
- Browser error buffer was empty. Console output contained only JQMIGRATE and WooCommerce dependency diagnostics.
- Teardown proof: `/home/server-manager/slt-evidence/SLT-CHK-12-06-cart-empty-after.png`; persistent cart serialized to an empty `cart` array.

## Scope notes and counterexamples

- Probe A on the same grouped page correctly added Daily Core alone at `$10.00`.
- After recovering the task cart, Daily Core plus the non-subscription child Grouped Extra coexisted correctly and block checkout showed `$10.00 + $3.00 = $13.00`; evidence: `/home/server-manager/slt-evidence/SLT-CHK-12-04-probe-c-checkout.png`.
- The defect is therefore specific to adding a second subscription child, not all grouped additions or mixed subscription/plain composition.
- The simultaneous two-subscription grouped submit was previously observed under `SLT-PROD-09`; this D05 reproduction proves the same silent replacement also occurs when the second child is added sequentially to an existing subscription cart.
- A separate one-click product/archive reproduction exists under `SLT-CHK-06`; this finding establishes the grouped-form surface with an authenticated task-owned customer.
- No product source was inspected or changed.
