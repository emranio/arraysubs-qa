---
id: 12
title: Priceless subscription product renders $0.00 / month on the storefront
status: open
priority: high
created: 2026-08-26T14:38:05.311323216+02:00
updated: 2026-08-30T14:53:48.74139182+02:00
tags:
    - storefront
    - price-html
    - subscription-products
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s) / order ID(s):** N/A
**Affected user(s):** any shopper (reproduced as `guest` and as WP user 1)

**Test URL:** https://mirror-help.arrayhash.com/product/peqa-box-switch-test/ (product 33324)

## Steps
1. Have a product with `_is_subscription = yes` and no price (`_regular_price = ''`, `_price = ''`) — e.g. the result of the box → simple type switch.
2. View it on the storefront, in the shop archive, or in Related Products.

## Expected
WooCommerce deliberately returns an empty price html for a product with no price, so nothing should be shown.

## Actual
```
_price=''   get_price()=''   is_purchasable()=false
get_price_html() = "$0.00 / month"
```
Product page shows `PEQA Box Switch Test  $0.00 / month`.

## Root cause
`ArraySubs\\Features\\SubscriptionProducts\\Services\\Hooks::subscriptionPriceHtml()`, arraysubs/src/Features/SubscriptionProducts/Services/Hooks.php:828:
```php
if (trim($price_html) === '') {
    $price_html = wc_price((float) ($data['price'] ?? 0));
}
```
An empty $data['price'] is cast to 0.0 and formatted as $0.00, then the billing suffix is appended.

## Fix direction
Only substitute when `$data['price']` is a real non-empty value; otherwise return `$price_html` untouched.

## Scope notes
Also affects shop archives, search results, related products and the product structured data, since they all use `woocommerce_get_price_html`.


---

## Deliberately not fixed — 2026-08-30

Tagged `[skip]` in `qa/product-edit-regression-qa-report.md` (F-05): *"skip it, its intentional"*. `Hooks::subscriptionPriceHtml()` still substitutes `wc_price(0)` for an empty price html.

Worth noting: the state that produced the observed `$0.00 / month` (a box switched to Simple, left published and priceless) can no longer be reached — see #11.
