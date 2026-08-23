---
id: 37
title: SLT-PROD-02 Create SLT2 Free Signup Daily, the $0.00-today free-signup-then-paid product
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-02
due: "2026-08-25"
estimate: 40m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-02** · group `catalog` · scheduled **D02** (2026-08-25)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the "signup free" product. A true $0 recurring SIMPLE subscription is impossible on this build — `getPostedSubscriptionProductValidationErrors()` rejects any simple subscription save whose regular price is empty or <= 0, and `validateProduct()` additionally restores the old prices — so free signup is implemented the supported way: a priced product with a short trial, which `beforeCalculateTotals()` zeroes at checkout, giving $0.00 due today and a first real charge when the trial converts. The $0-recurring branch is probed separately on a VARIATION in SLT-PROD-08, where no such validation runs.

## Scope
- Gateway: N/A
- Checkout: block-cart preview only; no checkout or purchase
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Baseline `trials.require_payment_method = true` is NOT changed by this window, so checkout still requires a card even though the total is $0.00 (`TrialCheckoutTrait::maybeSkipPaymentForTrial()` returns the setting value).
- Trial length 2 days is deliberate: `emails.trial_ending.days_before = 3` is LONGER than the trial, so the trial-ending reminder has no valid send window — that negative is the point of this product and is contrasted with SLT-PROD-03.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Free Signup Daily / slug `slt2-free-signup-daily` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $8.00; expected charge today $0.00; expected first paid charge $8.00 at the recorded `trial_end+k` charge gate; renewal $8.00/day |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin-SLT-PROD-02 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT2 Free Signup Daily`. **Description**: `SLT2 window product. Free signup via 2-day trial, then paid. Delete on 2026-09-05.`
4. Type **Simple product**; tick **Virtual**.
5. Tick **Subscription [ArraySubs]**.
6. **General** tab: **Regular price ($)** = `8.00`, Sale price empty.
7. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `2`; **Trial Period** = `Day`; **Sign-up Fee ($)** = EMPTY (a signup fee would force payment and break the $0.00-today premise — `maybeSkipPaymentForTrial()` returns true unconditionally when `cartHasSignupFee()`); **Different Renewal Price** = unticked. Capture `SLT-PROD-02-01-subscription-tab.png`.
8. Confirm the **Flexible Renewal Sync to Next Billing Cycle** section is HIDDEN/disabled because a trial is configured (`$arraysubs_flex_section_hidden = ... || $arraysubs_flex_trial_length > 0`). Capture it as `SLT-PROD-02-02-flex-hidden-by-trial.png`.
9. Slug `slt2-free-signup-daily`. Publish. Reload and re-verify the fields.
10. Verify meta: `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_trial_period,_signup_fee,_regular_price,_arraysubs_flex_sync_enabled --allow-root`.
11. Before any storefront/cart/downstream checkout access, append only this parent product ID to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior exclusion; re-read the raw option and require the ID exactly once.
12. As `--session guest-SLT-PROD-02`, open `https://mirror-help.arrayhash.com/product/slt2-free-signup-daily/?slt2-cache-bust=<timestamp>` and then `https://mirror-help.arrayhash.com/cart/?add-to-cart=<ID>&slt2-cache-bust=<timestamp>`. The frozen `checkout.one_click_mode=subscription_items` must redirect the add action to block `/checkout/`; record the zero-due-today summary there without entering payment data, then explicitly reopen `/cart/`, record the product row plus `$0.00` totals, and capture `SLT-PROD-02-03-cart-zero-total.png`. Empty the cart afterward.
13. Append the ID and verified Shop Access exclusion to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt2-free-signup-daily`.
2. `_trial_length=2`, `_trial_period=day`, `_subscription_period=day`, `_subscription_interval=1`, `_regular_price=8.00`, `_signup_fee` absent or `0`.
3. `_arraysubs_flex_sync_enabled` absent AND the flex section is not offered in the UI while the trial is set.
4. In the guest cart the line total for this item is `$0.00` and the cart total is `$0.00`; the item shows the trial/first-payment summary produced by `getSubscriptionTodayChargeSummary()`.
5. No `Subscription Signup Fee` fee row appears in the cart.
6. Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access; cart is emptied at the end and no order is created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and cart preview | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-02-01-subscription-tab.png`, `SLT-PROD-02-02-flex-hidden-by-trial.png`, `SLT-PROD-02-03-cart-zero-total.png`.
- Product ID; meta list output; raw Shop Access rule showing the ID exactly once; the exact cart total string.

## Pass criteria
- [ ] Published with trial 2 day and price 8.00
- [ ] Flex sync section hidden by the trial (exclusivity negative captured)
- [ ] Guest cart total is exactly $0.00 with no signup-fee row
- [ ] Metas exactly as listed
- [ ] Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: `SLT-CHK-15` buys this exactly once as `slt2-trial` after 12:00 site on 2026-08-25. It is the free-signup / $0-due-today path and the negative case for the trial-ending reminder (2-day trial vs 3-day reminder lead time). Its logical trial end is the 2026-08-27 checkout anniversary; the charge executes at `trial_end+k`, which can fall late on 08-27 or shortly after local midnight on 08-28. The D5 morning watch therefore owns the settled activation/charge evidence without time travel.
- Restores: cart emptied; close only `admin-SLT-PROD-02` and `guest-SLT-PROD-02`; SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. Product deleted by SLT-SETUP-99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
