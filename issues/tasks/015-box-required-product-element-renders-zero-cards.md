---
id: 15
title: 'Box: required product element renders zero cards when its product is not purchasable, blocking add-to-cart with a misleading error'
status: closed
priority: medium
created: 2026-08-26T14:39:04.657754173+02:00
updated: 2026-08-30T14:53:48.840808852+02:00
started: 2026-08-30T14:53:48.840808181+02:00
completed: 2026-08-30T14:53:48.840808181+02:00
tags:
    - subscription-box
    - storefront
    - members-access
    - ux
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s) / order ID(s):** N/A (add-to-cart never succeeds)
**Affected user(s):** any non-qualifying visitor; reproduced as `guest` (logged out) and confirmed absent for WP user 1 (admin bypasses shop access rules).

**Test URL:** https://mirror-help.arrayhash.com/product/peqa-box-qa/ (product 33263)
NOTE: product pages are cached — append a cache-busting query string, otherwise the admin-rendered markup is served to the guest and vice versa.

## Steps
1. Store-wide Members Access rule `Private member store` is active (scope `all`, action `block_purchase`, exclusions: products 31340/31347/31357/31363/63 and category 19 Accessories).
2. Box 33263 is in category 19, so the box itself stays purchasable, but its child `SLT Box Item A` (#12591) is not.
3. As a logged-out guest open the box product page and click `Create Subscription Box`.
4. Fill anything else and press `Add to Cart`.

## Expected
Either the element states that its item is currently unavailable and drops the required/min constraint, or the box refuses at the launcher with an explicit message — as the Bundle does.

## Actual
The required element renders with its `*` marker, `data-min="1"` and **zero cards**:
```
[{"type":"product","id":"el_...","min":"1","max":"0","cards":0,"txt":"Base item *"}, ...]
```
Add to Cart returns only:
> Please select at least 1 item(s) in this step.

There is nothing to select. The box is permanently unbuyable for that visitor with no explanation.

## Root cause
`arraysubs/src/Features/SubscriptionBox/views/box-builder.php:161` skips each child where `!$child->is_purchasable() || !$child->is_in_stock()` (and `price <= 0`), but still renders the element wrapper, its label, its required marker and its `data-min`.

For the record, `ArraySubs\\Features\\MembersAccess\\Services\\EcommerceRestriction::filterIsPurchasable` (priority 90 on `woocommerce_is_purchasable`) is what makes the child unpurchasable here; `ArraySubsPro\\Features\\FixedPeriodMembership` filters the same hook at priority 80 and could produce the same outcome.

## Scope notes / counterexamples
The **Bundle** handles the identical situation correctly. As guest, `Subscribe Now` on product 33290 returns:
> "BUNDLE-CHILD-PLAIN" is currently unavailable, so this bundle cannot be purchased.

Out-of-stock and zero-price children hit the same code path and will behave the same way, independent of Members Access.


---

## Fixed — 2026-08-30

New `BoxConfig::getSelectableChildIds()` (the children that will really render as cards) and `BoxConfig::validateAvailability()` (mirrors the bundle's `validateContents()`). The builder view calls it: when a **required** element resolves to zero selectable children the launcher and the whole wizard are replaced with a bundle-style refusal; an **optional** element that resolves to zero renders an explicit empty state, drops its `*` marker and its `data-min` goes to 0. `validateSelection()` runs the same check first, so the server-side add-to-cart answers the real reason instead of "Please select at least 1 item(s) in this step."

**Verified (browser):**
* box 33263 as **guest** (Members Access rule active) → no launcher, no overlay, message *"\"Base item\" has nothing available right now, so this subscription box cannot be built."*
* box 33263 as **admin** → unchanged: launcher present, Base item 1 card, Pick extras 8 cards.
* copy 33324 with its product/category elements made optional, as guest → launcher present, empty element renders *"Nothing is available in this step right now."*, `data-min=0`, no required marker, and the other element still offers 3 cards.
