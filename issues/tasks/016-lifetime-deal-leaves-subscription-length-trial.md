---
id: 16
title: Lifetime Deal leaves Subscription Length, Trial, Renewal Price and Fixed Period Membership editable
status: closed
priority: medium
created: 2026-08-26T14:39:04.724843848+02:00
updated: 2026-08-30T14:53:48.841432733+02:00
started: 2026-08-30T14:53:48.841431981+02:00
completed: 2026-08-30T14:53:48.841431981+02:00
tags:
    - product-edit
    - conditional-fields
    - lifetime
    - ux
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s) / order ID(s):** N/A
**Affected user(s):** WP user 1 (admin)

**Test URL:** https://mirror-help.arrayhash.com/wp-admin/post.php?post=33252&action=edit → `Subscription [ArraySubs]` tab

## Steps
1. On a simple subscription product set `Billing Period = Lifetime Deal`.
2. Observe which fields/sections remain visible and editable.

## Expected
A lifetime deal never renews, so cycle-count, trial, renewal-price and fixed-end-date controls should be hidden or disabled — the way Flexible Renewal Sync already is.

## Actual (measured offsetParent / disabled state)
| Field | month | lifetime |
|---|---|---|
| Billing Interval | enabled | **disabled** (correct, hidden `value=1` backup injected) |
| Subscription Length | editable | **still editable** |
| Trial Settings | visible | **still visible** |
| Different Renewal Price | visible | **still visible** |
| Fixed Period Membership | visible | **still visible** |
| Flexible Renewal Sync | visible | hidden (correct) |

Worst case: a merchant can type `Subscription Length = 5`, save, and `Hooks::saveProductMeta()` silently rewrites it to `0`:
```php
if (get_post_meta($post_id, '_subscription_period', true) === 'lifetime') {
    update_post_meta($post_id, '_subscription_interval', 1);
    update_post_meta($post_id, '_subscription_length', 0);
}
```

## Scope notes
- Same gap per-variation: `productEditPage.js::applyVariationLifetimeState()` only touches the interval.
- `Lifetime Deal` also remains selectable while the Flexible Subscription mode is `flexible_length` or `full_flexible`, which is contradictory; in `full_flexible` the Billing Period select is hidden but still posts whatever it was last set to.


---

## Fixed — 2026-08-30

Both halves are covered.

**UI:** Subscription Length, Trial Settings, Different Renewal Price and the pro Fixed Period Membership section carry a shared `arraysubs-hide-if-lifetime` class that `productEditPage.js` toggles for the simple panel and per variation. Flexible Renewal Sync already hid itself.

**Data:** `Hooks::normalizeLifetimeMeta()` (product and variation) forces interval 1, length 0, trial 0 and deletes `_enable_renewal_price` / `_renewal_price` / `_renewal_price_after`. The Fixed Period Membership saver clears its own six fields for lifetime, and Flexible Renewal Sync drops `_arraysubs_flex_sync_enabled`, so a hidden-but-posted control can no longer write a contradiction.

Flexible Subscription already forced mode back to `fixed` on lifetime; the two `window.alert()` calls it used to announce that (forbidden by AGENTS.md) are replaced with an inline `.arraysubs-flexsub-notice`.

**Verified (browser, product 33252 and variation loop 0 of 33313):** switching Billing Period to Lifetime Deal disables Billing Interval and hides Subscription Length, Trial Settings, Different Renewal Price, Fixed Period Membership and Flexible Renewal Sync; Sign-up Fee stays (a one-time fee is still meaningful). Switching back to Month restores every section.
