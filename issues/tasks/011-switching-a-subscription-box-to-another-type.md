---
id: 11
title: Switching a Subscription Box to another type leaves a published, priceless subscription product
status: closed
priority: high
created: 2026-08-26T14:38:05.253894974+02:00
updated: 2026-08-30T14:53:48.834996814+02:00
started: 2026-08-30T14:53:48.834996203+02:00
completed: 2026-08-30T14:53:48.834996203+02:00
tags:
    - subscription-box
    - product-edit
    - product-type-switch
    - validation
    - data-integrity
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s) / order ID(s):** N/A (test product had no orders)
**Affected user(s):** WP user 1 (admin)

**Test URL:** https://mirror-help.arrayhash.com/wp-admin/post.php?post=33324&action=edit
Product 33324 `PEQA Box Switch Test` is a copy of the configured box 33263.

## Steps
1. Open the configured Subscription Box product.
2. Change Product data type to **Simple product**.
3. Publish.

## Expected
Either the type change is blocked along with the subscription save, or the product ends in a coherent state (not a published, unpurchasable product still flagged as a subscription).

## Actual
Two notices appear:
> Subscription products must have a valid regular price greater than zero.
> Paddle catalogue synchronization failed for product #33324. Review the Paddle sync log, then save the product again.

but the type change and the marker deletion still land:
```
type=simple  status=publish
_arraysubs_subscription_box=''      (marker deleted)
_is_subscription='yes'              (stale, from the box saver)
_regular_price=''  _price=''
_arraysubs_box_config = 1120 bytes  (retained, so switching back works)
is_purchasable() = false
```
The product remains **published** because `preserveProductStatusForInvalidSubscriptionSave()` keeps the existing status for an existing post.

Storefront https://mirror-help.arrayhash.com/product/peqa-box-switch-test/ then renders `PEQA Box Switch Test — $0.00 / month` with no add-to-cart button (see the separate $0.00 price-html issue).

## Root cause
`ArraySubs\\Features\\SubscriptionBox\\Services\\ProductType::saveProductMeta()` deletes `BoxConfig::META_MARKER` as soon as the posted `product-type` is not the box type, and WooCommerce applies the new `product_type` term at priority 10 — both happen even though the core subscription save is blocked at the same request.

## Scope notes
The Bundle saver (`ArraySubsPro\\Features\\SubscriptionBundle\\Services\\ProductType::saveProductMeta()`) has the identical branch, so the same outcome is expected for bundle → simple; not separately executed in this run.


---

## Fixed — 2026-08-30

A product-type change **away from a subscription-engine owner type** (Subscription Box, Subscription Bundle) is now part of the same validated transaction. `Hooks::guardBlockedProductTypeChange()` runs on `woocommerce_process_product_meta` at priority 1 — before WooCommerce builds the product object at 10 — and puts `$_POST['product-type']` back when the posted subscription settings are invalid, adding an explicit second notice.

Scope is deliberately narrow: only a move to `simple` is guarded (the conversion the product-level rules apply to). Variable is validated per variation, container types validate themselves, and grouped/external hide the subscription checkbox, so guarding those would trap the merchant. Box and Bundle register their slugs through the new `arraysubs_subscription_engine_owner_product_types` filter.

**Verified (browser, product 33324):**
* box → Simple with no price → *"Subscription products must have a valid regular price greater than zero."* + *"The product type was left as \"Subscription Box [ArraySubs]\" because the subscription settings above are not valid yet. Fix them, save, then change the type."*; DB still `type=arraysubs_subscription_box`, marker `yes`, status `publish`. No Paddle notice.
* box → Simple **with** a $12 price → converts cleanly: `type=simple`, marker cleared, `_is_subscription=yes`, `_price=12`, no errors.
* box → Grouped is still allowed (not over-blocked).
