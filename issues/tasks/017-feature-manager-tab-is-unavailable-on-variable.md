---
id: 17
title: Feature Manager tab is unavailable on Variable, Subscription Box and Subscription Bundle products
status: open
priority: medium
created: 2026-08-26T14:39:51.561175634+02:00
updated: 2026-08-26T14:39:51.561175634+02:00
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
