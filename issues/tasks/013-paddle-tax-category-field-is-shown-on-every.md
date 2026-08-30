---
id: 13
title: Paddle tax category field is shown on every product type including Store Credit, Box and Bundle
status: open
priority: medium
created: 2026-08-26T14:38:33.633261254+02:00
updated: 2026-08-26T14:38:33.633261254+02:00
tags:
    - product-edit
    - paddle
    - conditional-fields
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s) / order ID(s):** N/A
**Affected user(s):** WP user 1 (admin)

**Test URL:** https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product and https://mirror-help.arrayhash.com/wp-admin/post.php?post=1106&action=edit (Store Credit product)

## Steps
1. Open any product edit screen with the ArraySubs Paddle gateway enabled.
2. Cycle `#product-type` through every option and observe the `Paddle tax category` options group on the General tab.

## Expected
The field is only meaningful for products the Paddle gateway can sell as subscriptions (simple / variable).

## Actual
It is visible for **Simple, External/Affiliate, Variable, Subscription Box, Subscription Bundle and Store Credit**. (On Grouped it is invisible only because the entire General tab is hidden.)

Measured visibility per type (offsetParent !== null):
```
simple                      -> visible
external                    -> visible
variable                    -> visible
arraysubs_subscription_box  -> visible
arraysubs_subscription_bundle -> visible
arraysubs_store_credit      -> visible
```

## Root cause
`ArraySubs\\Features\\AutomaticPayments\\Services\\PaddleTaxCategoryFields::renderProductField()`, arraysubs/src/Features/AutomaticPayments/Services/PaddleTaxCategoryFields.php:84, wraps the field in `<div class="options_group show_if_arraysubs_paddle_tax_category">`.

`arraysubs_paddle_tax_category` is **not a registered product type**. WooCommerce's `show_and_hide_panels()` hides `.show_if_<type>` only for the types present in `woocommerce_admin_meta_boxes.product_types`, which on this site is:
`simple, grouped, variable, external, arraysubs_subscription_box, arraysubs_store_credit, arraysubs_subscription_bundle`.
Since the class matches none of them, the group is never hidden and never explicitly shown — it simply always renders.

## Fix direction
Use a real gate (e.g. `show_if_simple show_if_variable`) or a JS toggle driven by the `Subscription [ArraySubs]` checkbox.
