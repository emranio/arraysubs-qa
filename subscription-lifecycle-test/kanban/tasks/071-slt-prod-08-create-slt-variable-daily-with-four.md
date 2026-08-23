---
id: 71
title: SLT-PROD-08 Create SLT2 Variable Daily with four subscription variations incl. a $0 probe
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-04
due: "2026-08-27"
estimate: 1h 30m
depends_on:
    - 10
    - 8
    - 21
claimed_by: wild-timber
class: standard
---

> **SLT-PROD-08** · group `catalog` · scheduled **D04** (2026-08-27)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the variable subscription product with four variations that differ in billing interval, price, signup fee and trial, and use it to probe the $0-recurring branch: `isSubscriptionProductSaveRequest()` returns false when `product-type == 'variable'`, and `saveVariationMeta()` performs no price validation at all, so a $0 variation can be saved even though the identical simple product cannot. Whether that $0 variation is purchasable is a genuine open question this variation exists to answer.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront variation verification only)
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- All four variations use the `day` period on purpose: the window-wide rule reserves `week` for SLT-PROD-13 and `month` for SLT-PROD-12 only, so variety here comes from interval, price, fee and trial.
- UI contract: the **Subscription [ArraySubs]** product-data TAB is registered `show_if_simple` only. For a variable product you tick the header checkbox **Subscription [ArraySubs]** (which syncs `_is_subscription=yes` onto every variation on save) and then configure each variation in its own expanded variation panel.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Variable Daily / slug `slt2-variable-daily`, attribute `SLT2 Tier` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | see the variation table |

| Variation (SLT2 Tier) | Regular price | Billing period | Interval | Length | Trial | Signup fee | Expected charge today |
|---|---|---|---|---|---|---|---|
| Starter | 6.00 | Day | 1 | 0 | 0 | — | $6.00 |
| Plus | 11.00 | Day | 2 | 0 | 0 | 4.00 | $15.00 |
| Trialist | 9.00 | Day | 1 | 0 | 3 day | — | $0.00 |
| Zero Probe | 0.00 | Day | 1 | 0 | 0 | — | $0.00 (probe) |

## Steps
1. Record `M0=$(mailpit-agent latest-id)`.
2. `agent-browser --session admin-SLT-PROD-08 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT2 Variable Daily`. **Description**: `SLT2 window product. Variable subscription, four daily tiers. Delete on 2026-09-05.`
4. Set the product type dropdown to **Variable product**; tick **Virtual**; tick the header checkbox **Subscription [ArraySubs]**.
5. **Attributes** tab: add `SLT2 Tier` values, tick both flags, Save, and capture `SLT-PROD-08-01-attributes.png`.
6. **Variations** tab: **Generate variations** (or add four manually) so all four `SLT2 Tier` values exist.
7. Configure Starter exactly and capture `SLT-PROD-08-23-variation-starter.png`.
8. Configure Plus exactly and capture `SLT-PROD-08-24-variation-plus.png`.
9. Configure Trialist exactly, prove flex hidden, and capture `SLT-PROD-08-25-variation-trialist-flex-hidden.png`.
10. Configure Zero Probe exactly and capture `SLT-PROD-08-26-variation-zero-probe.png` before Save; save variations.
11. Reload the edit screen and check whether the `0.00` price survived. If WooCommerce or ArraySubs rejected it, record the exact message and the resulting stored price — that result IS the finding; do not force it with WP-CLI.
12. Slug `slt2-variable-daily`. Publish.
13. `wp post meta list <VARIATION_ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_signup_fee,_regular_price --allow-root` for each of the four variation IDs.
14. Before any storefront/cart/downstream checkout access, append only the variable parent product ID to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**. Do not append variation IDs. Preserve every other field and every prior exclusion; re-read the raw option and require the parent ID exactly once.
15. In `guest-SLT-PROD-08`, open the cache-busted product, switch through all four tiers, and capture each distinct rendered state as `SLT-PROD-08-27a-frontend-starter.png` through `-06d-frontend-zero-probe.png`.
16. Append parent/four variation IDs and verified Shop Access exclusion to the registry. Inspect the complete `M0` delta and require zero task-attributable mail; classify background mail. Close only `admin-SLT-PROD-08` and `guest-SLT-PROD-08`, independently review product/meta/storefront evidence, move the card through `review` to `done`, and ensure Review returns to zero. If a live publish/storefront/AJAX failure occurs, create a dedicated issue with this task/plan, product/variation IDs, user `N/A`, exact route/context, reproduction, expected/actual, UI/meta/network proof and a working tier counterexample; create or update the mandatory `qa/issues/` kanban card.

## Expected results
1. Parent published as `variable`, virtual, slug `slt2-variable-daily`, with `_is_subscription=yes` on the parent and on all four variations.
2. Starter: period day, interval 1, price 6.00, no trial, no fee.
3. Plus: period day, interval 2, price 11.00, `_signup_fee=4`.
4. Trialist: period day, interval 1, price 9.00, `_trial_length=3`, `_trial_period=day`; its flex block is hidden.
5. Zero Probe: either `_regular_price=0` is stored (variation-level saves are unvalidated) — record it as an asymmetry against the simple-product rule — or the save was rejected, in which case the exact rejection text and the surviving price are recorded.
6. The variable parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access; variation IDs are not added separately.
7. The storefront updates price and subscription summary correctly for each of the four tier selections.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and variation saves | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots `SLT-PROD-08-01` through `-05` plus four distinct `-06a` through `-06d` storefront states named in the steps.
- Parent ID + four variation IDs; four meta dumps; raw Shop Access rule showing the parent ID exactly once; any validation text for the zero-price variation; console/AJAX errors during **Save changes** on the Variations tab.

## Pass criteria
- [ ] Parent variable + subscription with attribute SLT2 Tier used for variations
- [ ] Four variations exist with the exact price/interval/trial/fee matrix
- [ ] `_is_subscription=yes` propagated to all four variations
- [ ] Zero Probe outcome recorded either way with evidence
- [ ] Front-end summary changes per tier
- [ ] Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access
- [ ] Zero mail
- [ ] Exact sessions closed and complete product evidence reviewed to done

## Isolation / teardown
- State handoff: buy variations as `slt2-core` except Trialist, which belongs to `slt2-trial`. Plan-switching tasks may use variation IDs as switch targets — `getAvailableSwitchOptions()` reads `_variation_id` first, and the Linked Products search action is `woocommerce_json_search_products_and_variations`, so variations are legitimate targets.
- Restores: SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. Parent and variations are deleted by SLT-SETUP-99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
