---
id: 16
title: Lifetime Deal leaves Subscription Length, Trial, Renewal Price and Fixed Period Membership editable
status: open
priority: medium
created: 2026-08-26T14:39:04.724843848+02:00
updated: 2026-08-26T14:39:04.724843848+02:00
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
