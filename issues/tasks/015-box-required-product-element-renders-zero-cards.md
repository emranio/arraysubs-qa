---
id: 15
title: 'Box: required product element renders zero cards when its product is not purchasable, blocking add-to-cart with a misleading error'
status: open
priority: medium
created: 2026-08-26T14:39:04.657754173+02:00
updated: 2026-08-26T14:39:04.657754173+02:00
tags:
    - subscription-box
    - storefront
    - members-access
    - ux
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s) / order ID(s):** N/A (add-to-cart never succeeds)
**Affected user(s):** any non-qualifying visitor; reproduced as `guest` (logged out) and confirmed absent for WP user 1 (admin bypasses shop access rules).

**Test URL:** https://mirror-help.arrayhash.com/product/peqa-box-qa/ (product 33263)
NOTE: product pages are cached — append a cache-busting query string, otherwise the admin-rendered markup is served to the guest and vice versa.

## Steps
1. Store-wide Members Access rule `Private member store` is active (scope `all`, action `block_purchase`, exclusions: products 31340/31347/31357/31363/63 and category 19 Accessories).
2. Box 33263 is in category 19, so the box itself stays purchasable, but its child `SLT Box Item A` (#12591) is not.
3. As a logged-out guest open the box product page and click `Create Subscription Box`.
4. Fill anything else and press `Add to Cart`.

## Expected
Either the element states that its item is currently unavailable and drops the required/min constraint, or the box refuses at the launcher with an explicit message — as the Bundle does.

## Actual
The required element renders with its `*` marker, `data-min="1"` and **zero cards**:
```
[{"type":"product","id":"el_...","min":"1","max":"0","cards":0,"txt":"Base item *"}, ...]
```
Add to Cart returns only:
> Please select at least 1 item(s) in this step.

There is nothing to select. The box is permanently unbuyable for that visitor with no explanation.

## Root cause
`arraysubs/src/Features/SubscriptionBox/views/box-builder.php:161` skips each child where `!$child->is_purchasable() || !$child->is_in_stock()` (and `price <= 0`), but still renders the element wrapper, its label, its required marker and its `data-min`.

For the record, `ArraySubs\\Features\\MembersAccess\\Services\\EcommerceRestriction::filterIsPurchasable` (priority 90 on `woocommerce_is_purchasable`) is what makes the child unpurchasable here; `ArraySubsPro\\Features\\FixedPeriodMembership` filters the same hook at priority 80 and could produce the same outcome.

## Scope notes / counterexamples
The **Bundle** handles the identical situation correctly. As guest, `Subscribe Now` on product 33290 returns:
> "BUNDLE-CHILD-PLAIN" is currently unavailable, so this bundle cannot be purchased.

Out-of-stock and zero-price children hit the same code path and will behave the same way, independent of Members Access.
