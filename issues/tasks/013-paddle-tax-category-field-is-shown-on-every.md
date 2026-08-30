---
id: 13
title: Paddle tax category field is shown on every product type including Store Credit, Box and Bundle
status: closed
priority: medium
created: 2026-08-26T14:38:33.633261254+02:00
updated: 2026-08-30T14:53:48.837098071+02:00
started: 2026-08-30T14:53:48.837097109+02:00
completed: 2026-08-30T14:53:48.837097109+02:00
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


---

## Fixed — 2026-08-30

`PaddleTaxCategoryFields::renderProductField()` now wraps the group in `show_if_simple` instead of the non-existent `show_if_arraysubs_paddle_tax_category`. Paddle's catalogue saver only ever syncs `simple` products, so that is the only type the product-level field belongs on; the per-variation field is unchanged.

**Verified (browser, product 33252, driving `#product-type` through every option):** visible on Simple only — hidden on Grouped, External, Variable, Subscription Box, Subscription Bundle and Store Credit. The per-variation Paddle tax select still renders inside the Variations tab.

**Side effect worth knowing:** the group used to be permanently visible, which kept WooCommerce's "hide a panel whose option groups are all hidden" rule from hiding the **General** tab on Variable products. With the gate in place General is hidden for Variable again, which is stock WooCommerce behaviour (pricing lives on the variations).
