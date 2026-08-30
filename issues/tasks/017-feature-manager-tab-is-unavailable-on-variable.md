---
id: 17
title: Feature Manager tab is unavailable on Variable, Subscription Box and Subscription Bundle products
status: closed
priority: medium
created: 2026-08-26T14:39:51.561175634+02:00
updated: 2026-08-30T14:53:48.842062814+02:00
started: 2026-08-30T14:53:48.842061802+02:00
completed: 2026-08-30T14:53:48.842061802+02:00
tags:
    - product-edit
    - feature-manager
    - conditional-fields
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s) / order ID(s):** N/A
**Affected user(s):** WP user 1 (admin)

**Test URL:** https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product

## Steps
Cycle `#product-type` through every option and record which product-data tabs are visible.

## Expected
Boxes and bundles are full subscription products (both set `_is_subscription = yes`), so membership features should be attachable to them — or the UI should say why they cannot be.

## Actual
`Feature Manager [AS]` is visible for **simple only**. Its tab classes are:
```
arraysubs_features_options arraysubs_features_tab show_if_simple hide_if_grouped hide_if_external
```
Because the list contains only `show_if_simple`, WooCommerce hides the tab for variable, arraysubs_subscription_box, arraysubs_subscription_bundle and arraysubs_store_credit.

Observed visible tabs per type:
```
simple  -> General, Inventory, Shipping, Linked, Attributes, Feature Manager [AS], Product Redirect [AS], Advanced
variable-> General, Inventory, Shipping, Linked, Attributes, Variations, Product Redirect [AS], Advanced
box     -> General, Inventory, Shipping, Linked, Attributes, Product Redirect [AS], Advanced
bundle  -> General, Inventory, Shipping, Linked, Attributes, Product Redirect [AS], Advanced
```

## Scope notes
Please confirm intent. If features genuinely do not apply to boxes/bundles/variable, the configurator should say so; if they do, the tab needs the extra `show_if_*` classes.


---

## Fixed — 2026-08-30

Two defects, not one.

1. The tab's class list was `show_if_simple` only. It is now built from simple + variable + every type registered through `arraysubs_subscription_engine_owner_product_types` (Subscription Box, Subscription Bundle), still hidden for grouped/external.
2. **The real blocker:** `subscriptionBoxAdmin.js` and `subscriptionBundleAdmin.js` ran `$('.show_if_arraysubs_subscription_box').toggle(isBox)` over *every* matching element, so adding the box/bundle classes to the tab made it disappear on Simple. Both now skip elements the current product type also claims and leave them to WooCommerce's own show/hide pass.

Variations already had their own Feature Manager UI but no parent-level defaults; `arraysubs_get_product_features()` now falls back to the parent product when a variation defines no features of its own (an empty `"[]"` counts as "not set"), and the panel says so on variable products. The storefront block still stays quiet on a variable product whose variations override the parent.

**Verified (browser):** tab visible on Simple, Variable, Subscription Box and Subscription Bundle; hidden on Grouped, External and Store Credit. Feature added on bundle 33290 and box 33263, saved, and both appear in **My account → My Features** under *PEQA Bundle QA #33298* and *PEQA Box QA #35313*. A parent-level feature on variable 33313 resolves for both variations 33315/33317. Box and Store Credit editors still open on their General tab with core pricing hidden.
