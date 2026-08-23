---
id: 60
title: SLT-PROD-11 Create the four-product plan ladder and wire upgrade/downgrade/crossgrade links
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-03
due: "2026-08-26"
estimate: 1h 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-11** · group `catalog` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Build the switching ladder as four daily subscription products and link them through the WooCommerce **Linked Products** tab, which is where `PlanSwitching\Services\Hooks::addSwitchingFields()` renders **Upgrade to**, **Downgrade to**, **Crossgrade to** and **Auto-downgrade to**. The upgrade, downgrade and crossgrade targets are stored as ID arrays in `_arraysubs_upgrade_products`, `_arraysubs_downgrade_products` and `_arraysubs_crossgrade_products`; the single-select auto-downgrade target is stored as one product ID in `_arraysubs_auto_downgrade_product`. `ProrationCalculator::getAvailableSwitchOptions()` reads them from the SOURCE product only — so the links must be set on every rung, in both directions.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Baseline `plan_switching`: enabled, upgrades/downgrades/crossgrades all allowed, `proration_type = prorate_immediately`, `allow_customer_switch = true`, `auto_downgrade_timing = on_expire` — all unchanged by this window.
- All four rungs are day/1 so a switch and its prorated order are observable the same day, and so proration maths uses a 1-day cycle (credit/charge is dominated by the price delta, not by elapsed time).
- Session `admin-SLT-PROD-11` is exclusive to this task.

## Test data
| Item | Value |
|---|---|
| Products | SLT2 Plan Basic `slt2-plan-basic` $5.00; SLT2 Plan Pro `slt2-plan-pro` $15.00; SLT2 Plan Enterprise `slt2-plan-enterprise` $30.00; SLT2 Plan Peer `slt2-plan-peer` $15.00 |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | all day/1, no trial, no fee, no length limit |

Link matrix (set on each source product's Linked Products tab):

| Source | Upgrade to | Downgrade to | Crossgrade to | Auto-downgrade to |
|---|---|---|---|---|
| SLT2 Plan Basic | SLT2 Plan Pro, SLT2 Plan Enterprise | (none) | (none) | (none) |
| SLT2 Plan Pro | SLT2 Plan Enterprise | SLT2 Plan Basic | SLT2 Plan Peer | SLT2 Plan Basic |
| SLT2 Plan Enterprise | (none) | SLT2 Plan Pro, SLT2 Plan Basic | (none) | SLT2 Plan Basic |
| SLT2 Plan Peer | SLT2 Plan Enterprise | SLT2 Plan Basic | SLT2 Plan Pro | (none) |

## Steps
1. Capture `mailpit-agent latest-id`.
2. For each of the four products: `agent-browser --session admin-SLT-PROD-11 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; set the title; **Description** `SLT2 window product. Plan-switching ladder rung. Delete on 2026-09-05.`; **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**; **General** tab **Regular price ($)** per the table; **Subscription [ArraySubs]** tab: **Billing Period** `Day`, **Billing Interval** `1`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked, **Flexible Renewal Sync** unticked; for Basic capture `SLT-PROD-11-01-basic-subscription-tab.png`; set the slug; Publish.
3. Record all four product IDs before wiring links.
4. Re-open each product and go to the **Linked Products** tab. Confirm the **Subscription Plan Switching** block is visible (it is display:none unless `_is_subscription=yes`).
5. Fill **Upgrade to**, **Downgrade to**, **Crossgrade to** and **Auto-downgrade to** exactly per the link matrix using the product-search selects (`data-action=woocommerce_json_search_products_and_variations`). Update.
6. Reload each product and confirm the selects still show the chosen products (proves `saveSwitchingFields()` persisted the three multi-select fields as arrays and the auto-downgrade single-select as one product ID). Capture the exact persisted blocks as `SLT-PROD-11-02-pro-linked-products.png`, `-03-enterprise-linked-products.png`, and `-04-peer-linked-products.png`.
7. Verify: `wp post meta get <ID> _arraysubs_upgrade_products --format=json --allow-root` (and the downgrade/crossgrade/auto keys) for all four.
8. Before any downstream storefront/cart/checkout use, append only all four parent product IDs to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior exclusion; re-read the raw option and require each new ID exactly once.
9. Inspect the complete Mailpit delta after step 1 and require zero task-attributable mail, append the four IDs, verified Shop Access exclusions, and link matrix to the registry, and close only `admin-SLT-PROD-11`.

## Expected results
1. Four products published, all simple + virtual + subscription, day/1, no trial, no fee, prices $5.00 / $15.00 / $30.00 / $15.00.
2. `_arraysubs_upgrade_products` on Basic is a 2-element array containing the Pro and Enterprise IDs.
3. Pro carries all four keys: upgrade `[Enterprise]`, downgrade `[Basic]`, crossgrade `[Peer]`, auto-downgrade `Basic`.
4. Enterprise carries downgrade `[Pro, Basic]` and auto-downgrade `Basic`, with an empty upgrade array.
5. Peer carries upgrade `[Enterprise]`, downgrade `[Basic]`, crossgrade `[Pro]`.
6. Pro <-> Peer is a genuine crossgrade (identical $15.00 price) so `ProrationCalculator` classifies it laterally and applies no proration or credit.
7. All link selects survive a page reload.
8. All four parent product IDs are each present exactly once in the preserved Shop Access exclusion list before any downstream purchase.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Four publishes and four link saves | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-11-01-basic-subscription-tab.png`, `SLT-PROD-11-02-pro-linked-products.png`, `SLT-PROD-11-03-enterprise-linked-products.png`, `SLT-PROD-11-04-peer-linked-products.png`.
- Four product IDs; the four meta JSON dumps; raw Shop Access rule showing all four IDs exactly once; any select2 AJAX errors.

## Pass criteria
- [ ] Four rungs published at the exact prices, all day/1
- [ ] Multi-target link keys stored as ID arrays and auto-downgrade stored as one product ID
- [ ] Links survive reload
- [ ] Pro and Peer are equal-priced (true crossgrade)
- [ ] All four parent product IDs are each present exactly once in the preserved Shop Access exclusion list
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy and switch ONLY as `slt2-switch`. Switching requires the subscription to be in `arraysubs-active` or `arraysubs-trial` — `SwitchController` rejects any other status with "Plan switching is only available for active subscriptions". Auto-downgrade fires on expiry (`auto_downgrade_timing = on_expire`), which needs a length-limited or time-travelled subscription; the ladder rungs are length 0, so an auto-downgrade test must set `_end_date` by hand rather than expect it naturally.
- Restores: SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. All four products are deleted by SLT-SETUP-99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
