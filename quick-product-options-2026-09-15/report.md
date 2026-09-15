# Quick product options test report

**Result: passed.** Completed 2026-09-15 on http://localhost:10013 using the local administrator and real agent-browser sessions. Scope, task IDs, and same-day schedule: [plan.md](plan.md).

## Implemented

- Simple products now have dedicated Fixed Period Membership and Subscription Shipping steps.
- Fixed, Flexible Length, and Full Flexible use the modal's shared form controls and WordPress accent color. Flexible modes show Maximum length; Full Flexible exposes selected billing periods and fixes the interval at one. Returning to Fixed/Flexible Length restores the prior interval.
- Membership supports annual month/day cutoff, an absolute calendar date, optional enrollment dates, and expire/renew behavior. Enabling it forces Fixed mode and removes the finite cycle count. Lifetime deals cannot use membership dates.
- Shipping supports recurring or first-order-only charges, initial/renewal overrides, and virtual-product exclusion. Hidden, inapplicable values cannot corrupt or block creation.
- Publishing validates all relevant step fields. Disabled Pro controls remain visible, and core-only creation works.

## Browser coverage

| Task | Result and observations |
| --- | --- |
| QPO-01 | All three types work. Flexible Length changes the label/help; 366 cycles rejected, valid limits accepted. Full Flexible removes fixed period/interval fields; empty period selection is blocked. Month, Week/Year combinations saved. Previous interval restored when leaving Full Flexible. Radio arrow-key navigation works. |
| QPO-02 | Annual cutoff saved as 02-29; February offers 29 days. Enrollment close before open rejected. Missing absolute date blocked; 2027-12-31 saved. Both expire and renew persisted. Enabling membership switches to Fixed, hides the cycle limit, disables flexible modes, and restores the earlier interval. Review editing retains required-date validation. |
| QPO-03 | Negative shipping rejected. Recurring overrides 5.50/2.25 saved; one-time initial 6.75 saved without the previously invalid renewal override. Virtual product saved without previously invalid shipping fields. Zero fallback overrides saved as 0.00. |
| QPO-04 | Six simple products published through the modal. Standard editor reopened for annual, full-flexible, and absolute products; configured fields matched. Saved settings also passed 57 exact metadata/virtual assertions. Full Flexible storefront exposed only Week/Year; choosing Year and six cycles produced a cart with those terms, one-time shipping, 6.75 initial shipping, and 45.75 total. No order or subscription was created. |
| QPO-05 | Custom sync available for eligible schedule and saved with segment boundaries 5/6. Trial and different-renewal-price configurations block sync. One-day cycle blocked; three-day cycle allowed. Lifetime removes trial, recurring-price, and membership settings; signup fee retained. Pro temporarily deactivated: flexible/membership/shipping options unavailable, simple product creation still succeeds. Pro reactivated and verified. Variable-product guide remains two steps. Container/store-credit flows retain their existing step definitions; their builders were not changed or recreated. |
| QPO-06 | Inspected desktop 1440x1000, mobile 390x844, and tablet 782x900 screenshots. Mobile body clientWidth/scrollWidth = 350/350; tablet = 742/742. Mobile footer bottom 824 within 844px viewport. No horizontal overflow, accessible footer navigation, no browser JS errors. Found and fixed the mobile checkbox checkmark issue, recorded as issue #33 and closed after retest. |

## Server and build checks

- [22 server-validation cases](server-validation.txt): all passed; no products written by these cases. Includes invalid modes/periods, missing periods, numeric ranges, negative shipping, stale inactive values, malformed dates, year zero, leap day, and reversed enrollment.
- [57 saved-product assertions](persistence-verification.txt): all passed. [Product metadata evidence](product-proof.txt) records the actual created settings.
- Core production asset build passed: `acf323baf2604e7136bc`.
- Core/Pro diff whitespace checks passed. All six modified plugin source files remain below 3000 lines (largest: 583 lines). PHPCS was not run.
- Core owns wizard/forms and shared billing validation. Pro retains premium option validation and metadata persistence. Existing unrelated local changes were preserved.

## Screenshots

- [Fixed billing](screenshots/billing-fixed.png)
- [Full Flexible desktop](screenshots/billing-desktop-final.png) and [mobile](screenshots/billing-mobile-fixed.png)
- [Annual membership](screenshots/membership-annual.png) and [mobile](screenshots/membership-mobile-final.png)
- [Absolute membership](screenshots/membership-absolute-desktop.png)
- [Recurring shipping](screenshots/shipping-recurring.png), [one-time shipping](screenshots/shipping-one-time.png), and [virtual mobile](screenshots/shipping-mobile-final.png)
- [Standard annual editor](screenshots/annual-standard-editor.png) and [absolute editor](screenshots/absolute-standard-editor.png)
- [Storefront](screenshots/full-flexible-storefront.png) and [cart](screenshots/full-flexible-cart.png)
- [Core-only billing](screenshots/core-only-billing.png), [membership](screenshots/core-only-membership.png), and [shipping](screenshots/core-only-shipping.png)

## Test products and cleanup

| ID | Configuration | Final status |
| --- | --- | --- |
| 6387 | Annual membership, recurring shipping, trial | Draft |
| 6390 | Full Flexible Week/Year, one-time shipping, custom sync | Draft |
| 6392 | Flexible Length, virtual, different renewal price, signup fee | Draft |
| 6394 | Absolute membership, expire, zero shipping overrides | Draft |
| 6396 | Lifetime virtual product with signup fee | Draft |
| 6398 | Core-only fixed simple product | Draft |

The test cart item was removed, unsaved inspection products discarded, and ArraySubs Pro restored to active. [Cleanup record](cleanup.txt). No store settings were changed. Browser sessions used for this work were closed.

## Scope limits

The site is in WooCommerce Coming soon mode, so anonymous storefront access shows that screen. Product and cart behavior were tested through the authenticated storefront; site visibility was preserved. Payment, renewal execution, downloadable-file setup, and container builders are outside this simple-product creation task.

## Issue fixed during testing

[QA issue #33](../issues/tasks/033-quick-product-mobile-checkbox-checkmark-overflows.md): WordPress's mobile 30px checkmark overflowed the modal's 16px checkbox. Scoped centering and a 20px checkmark fixed it; mobile/desktop screenshots and keyboard interactions verified before closing.
