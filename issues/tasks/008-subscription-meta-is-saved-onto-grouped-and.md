---
id: 8
title: Subscription meta is saved onto Grouped and External products
status: open
priority: critical
created: 2026-08-26T14:37:07.156154984+02:00
updated: 2026-08-26T14:37:07.156154984+02:00
tags:
    - product-edit
    - subscription-products
    - save
    - regression
    - data-integrity
class: standard
---

**QA task ID / scheduled day:** N/A — no active QA plan board; ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator) unless stated; session `guest` (logged out). QA customer created: WP user 484 `peqa-cust` / peqa-cust@example.test (role customer).
**Affected subscription ID(s) / order ID(s):** N/A
**Affected user(s):** WP user 1 (admin) performed the save.

**Test URL:** https://mirror-help.arrayhash.com/wp-admin/post.php?post=33260&action=edit

## Steps
1. Products → Add new product.
2. Tick the header checkbox `Subscription [ArraySubs]`, set Regular price 20, Subscription tab → Billing Period = Week.
3. Change Product data type to **Grouped product** (the checkbox is hidden by WooCommerce CSS but stays checked).
4. Publish.

## Expected
A grouped (or external) product cannot be a subscription, so no `_is_subscription` / schedule meta should be written, and `arraysubs_is_subscription_product()` must return false.

## Actual
Form data at submit: `product-type=grouped`, `_is_subscription=on`, `_regular_price=20`, `_subscription_period=week`, `_subscription_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_trial_period=day`.

Post-save DB (product 33260):
```
33260,_is_subscription,yes
33260,_subscription_period,week
33260,_subscription_interval,1
33260,_subscription_length,0
33260,_trial_length,0
33260,_trial_period,day
33260,_signup_fee,0
33260,_arraysubs_subscription_mode,fixed
```
`wc_get_product(33260)->get_type() === 'grouped'`, `get_post_status(33260) === 'publish'`, `arraysubs_is_subscription_product(33260) === true`.

Admin products list row renders: `PEQA Grouped Leak | Grouped product | Subscription  …  – / Every week`.

## Root cause
`ArraySubs\\Features\\SubscriptionProducts\\Services\\Hooks::addSubscriptionTypeOption()` sets `wrapper_class => 'show_if_simple show_if_variable'`, which only CSS-hides the checkbox. `Hooks::saveProductMeta()` has no product-type guard — only Box/Bundle opt out via the `arraysubs_skip_subscription_product_save` filter.

## Scope notes / counterexamples
- Subscription Box and Subscription Bundle are NOT affected: their `skipCoreSubscriptionSave()` filter short-circuits the core saver.
- Store Credit posts the checkbox too but was not observed to persist `_is_subscription` in this run — needs its own check.
- External/Affiliate uses the identical wrapper class and has no skip filter; confirmed at form-data level (checkbox stays checked and posts) but the save was only executed for Grouped.
