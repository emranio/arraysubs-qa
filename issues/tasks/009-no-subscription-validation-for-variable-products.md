---
id: 9
title: No subscription validation for variable products or variations
status: closed
priority: high
created: 2026-08-26T14:37:36.158739816+02:00
updated: 2026-08-30T14:53:48.830516381+02:00
started: 2026-08-30T14:53:48.83051557+02:00
completed: 2026-08-30T14:53:48.83051557+02:00
tags:
    - product-edit
    - variable
    - variations
    - validation
    - regression
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s) / order ID(s):** N/A
**Affected user(s):** WP user 1 (admin)

**Test URL:** https://mirror-help.arrayhash.com/wp-admin/post.php?post=33313&action=edit (Variations tab)

## Steps
1. Open the variable subscription product 33313 (`PEQA Variable Sub QA`, variations 33315 Silver / 33317 Gold).
2. Variations tab → expand the Gold variation (loop index 0).
3. Clear `Regular price`.
4. Set `Billing Interval` to **99** (the input's own `max` attribute is 12).
5. Click **Save changes** (the variations AJAX save).

## Expected
Same rules as a simple subscription product: price must be > 0 and billing interval must be 1–12, otherwise the save is blocked with a `WC_Admin_Meta_Boxes` error.

## Actual
Saved silently. No error, no notice. DB for variation 33317:
```
33317,_price,
33317,_is_subscription,yes
33317,_subscription_interval,99
```
(`_regular_price` was deleted entirely.)

Storefront https://mirror-help.arrayhash.com/product/peqa-variable-sub-qa/ then silently drops the priceless variation — the `Tier` dropdown offers only `Silver`, and `data-product_variations` contains one entry. The admin gives no indication that Gold is no longer sellable.

## Root cause
- `Hooks::isSubscriptionProductSaveRequest()` (arraysubs/src/Features/SubscriptionProducts/Services/Hooks.php:2050) returns `false` when `product-type === 'variable'`, so `validateProduct()` and `preserveProductStatusForInvalidSubscriptionSave()` never run.
- `Hooks::saveVariationMeta()` performs no validation of its own.

## Scope notes / counterexamples
The identical inputs on a **simple** subscription product (33252) are correctly blocked with "Subscription products must have a valid regular price greater than zero." and the product is kept out of `publish`.


---

## Fixed — 2026-08-30

`Hooks::validateVariation()` now runs on `woocommerce_admin_process_variation_object` (priority 5), applying the same rules a simple product gets: regular price > 0, sale price > 0 when set, billing interval 1–12 (skipped for lifetime), and renewal-price coherence. On failure the variation's stored pricing is restored before `$variation->save()`, the save is registered in `BlockedSaveState` so `saveVariationMeta()` and every pro variation saver stand down, and the messages are surfaced twice: as WooCommerce meta-box errors and inline inside the variation panel that re-renders after the AJAX save.

**Verified (browser, product 33313 / variation 33317):** cleared `Regular price` + set `Billing Interval` to 99 → Save changes. DB unchanged (`_regular_price=25`, `_price=25`, `_subscription_interval=1`); panel shows *"This variation was not saved: Subscription variations must have a valid regular price greater than zero. Billing interval must be between 1 and 12."*; the attempted values (empty price, interval 99) are replayed into the fields. A subsequent valid save (interval 2) wrote through, and the storefront kept both variations selectable with `$25.00 / month`.
