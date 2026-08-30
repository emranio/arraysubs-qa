---
id: 10
title: Blocked subscription save discards entered settings while pro meta is still written
status: open
priority: high
created: 2026-08-26T14:37:36.225240097+02:00
updated: 2026-08-26T14:37:36.225240097+02:00
tags:
    - product-edit
    - validation
    - data-loss
    - flexible-subscription
    - fixed-period-membership
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s) / order ID(s):** N/A
**Affected user(s):** WP user 1 (admin)

**Test URL:** https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product and https://mirror-help.arrayhash.com/wp-admin/post.php?post=33252&action=edit

## Steps — case A (new product)
1. Add new product, tick `Subscription [ArraySubs]`, configure the Subscription tab, leave **Regular price empty**.
2. Publish.

## Steps — case B (existing product 33252, already valid)
1. Change Billing Period `month` → `year` and Trial Length `0` → `7`.
2. Also clear Regular price.
3. Update.

## Expected
The save is blocked (correct) **and** the merchant's input is either preserved in the form or explicitly listed as discarded. Pro modules should honour the same block so meta does not go out of sync.

## Actual — case A
Product correctly stays `draft` with "Subscription products must have a valid regular price greater than zero." **but** on reload the `Subscription [ArraySubs]` checkbox is **unchecked** and every subscription field is back at its default — the whole configuration is silently gone. Meanwhile the pro savers did run:
```
_arraysubs_subscription_mode   = fixed
_arraysubs_flexible_periods    = a:1:{i:0;s:5:"month";}
_arraysubs_fixed_end_date_type = recurring_annual
_arraysubs_fixed_end_date      = 01-01
_arraysubs_fixed_end_renewal   = expire
_arraysubs_flex_sync_seg1_end  = 122
_arraysubs_flex_sync_seg2_end  = 243
```
i.e. a product carrying pro subscription meta with no `_is_subscription`.

## Actual — case B
Only the price error is shown. `_subscription_period` stays `month` and `_trial_length` stays `0` — the two unrelated edits are dropped with nothing to tell the merchant. (Old price is correctly restored by `restoreProductPricingFromSavedMeta()`.)

## Root cause
`Hooks::saveProductMeta()` returns before writing any core subscription meta when `getPostedSubscriptionProductValidationErrors()` is non-empty; the pro savers hook the same action independently and are not gated by the same check.

## Scope notes
Case B is less severe than case A because previously-saved values survive, but both share the same cause.
