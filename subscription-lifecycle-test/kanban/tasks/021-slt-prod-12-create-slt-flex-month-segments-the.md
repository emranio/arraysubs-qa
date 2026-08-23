---
id: 21
title: SLT-PROD-12 Create SLT2 Flex Month Segments, the single month-interval flexible-sync product
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-01
due: "2026-08-24"
estimate: 1h
depends_on:
    - 10
    - 11
class: standard
---

> **SLT-PROD-12** · group `catalog` · scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Create the ONE month-interval product in the whole plan. It is the only artifact where calendar date math and all three flexible-sync segment modes are simultaneously observable, because for a month cycle `arraysubs_calculate_renewal_sync_cycle_dates()` anchors `cycle_start` to the first of the month — so the day-in-cycle varies with the purchase date, which it never does for a `day` period (where cycle_start is always the purchase day itself and day-in-cycle is permanently 1).

## Scope
- Gateway: N/A (gateway gating is verified by SLT-SETUP-05)
- Checkout: block-cart preview only; no checkout or purchase
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete. Global sync is OFF, so this product syncs ONLY because `FlexibleRenewalSync\Services\Hooks::filterSupportsRenewalSync()` grants it per-product — that is a deliberate part of what this product proves.
- Binding D1 preview model: on 2026-08-24 site the month cycle is `2026-07-31 18:00:00` → `2026-08-31 18:00:00` UTC and day-in-cycle is 24. The cycle-2 boundaries deliberately place D1 in segment 1, D2 in segment 2, and D6 in segment 3 so every mode is exercised by a real checkout.
- Segment 2 is purchased by `SLT-SYN-06` on D2; segment 3 is purchased by `SLT-SYN-10` on D6.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Flex Month Segments / slug `slt2-flex-month-segments` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $30.00/month; segment-dependent first charge (see Expected results) |

Segment plan: nominal cycle 30 days, all three segments ACTIVE, boundaries seg1_end = `24`, seg2_end = `27`. Partition: days 1-24 = segment 1 (full), days 25-27 = segment 2 (prorate), days 28-30(+31st overflow) = segment 3 (charge full for next billing cycle).

## Steps
1. Capture `M0=$(mailpit-agent latest-id)`. At the end, inspect every message newer than `M0`; classify unrelated/background mail by its actual owner.
2. `agent-browser --session admin-SLT-PROD-12 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT2 Flex Month Segments`. **Description**: `SLT2 window product. Monthly, flexible renewal sync, 3 active segments. Delete on 2026-09-05.`
4. **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `30.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Month`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** UNTICKED (ticking it would hide the flex section outright).
7. Tick **Flexible Renewal Sync to Next Billing Cycle**. The segment slider appears with `data-cycle-days="30"`.
8. Leave all three legend toggles ON (**Full amount**, **Prorate amount**, **Charge full for next billing cycle**).
9. Drag the slider handles until the legend reads segment 1 = `1 - 24`, segment 2 = `25 - 27`, segment 3 = `28 - 30`. Screenshot the legend.
10. Slug `slt2-flex-month-segments`. Publish. Reload and confirm the slider redraws with the same ranges.
10a. Before the cart preview, append this parent product ID only to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field/prior exclusion; re-read the raw option and require the new ID exactly once.
11. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
12. As `--session guest-SLT-PROD-12`, add to cart and read the cart's subscription meta rows WITHOUT checking out, then empty the cart. On D1 the preview falls on day 24 (segment 1): total **$30.00**, mode `full`, and no next-cycle bonus-access note.
13. Append the ID to the registry together with the purchase-date-to-segment table.
14. Confirm the guest cart is still empty, then close only `admin-SLT-PROD-12` and `guest-SLT-PROD-12`.

## Expected results
1. Published simple + virtual + subscription, slug `slt2-flex-month-segments`, `_subscription_period=month`, `_subscription_interval=1`, `_regular_price=30.00`.
2. `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_end=24`, `_arraysubs_flex_sync_seg2_end=27`, and all three `_active` metas = `yes`.
3. The slider legend after reload reads 1-24 / 25-27 / 28-30.
4. Purchase-date contract for downstream tasks (site-local dates, price $30.00):
   - Previewed 2026-08-24 (day 24) -> segment 1, mode `full`, charge $30.00, next payment 2026-09-01 00:00 site.
   - Bought 2026-08-25 (day 25) -> segment 2, mode `prorate`, cycle 2026-08-01..2026-09-01 = 31 days, remaining days = 6 after the deliberate minus-one rule, ratio 6/31, charge `round(30 * 6/31, 2)` = $5.81, next payment 2026-09-01 00:00 site.
   - Bought 2026-08-29 (day 29) -> segment 3, mode `next_cycle`, charge $30.00 in full, `cycle_start_date` moves to 2026-09-01 and next payment is pushed to 2026-10-01 00:00 site; the cart shows the next-cycle coverage note.
5. Guest cart on D1 shows a $30.00 segment-1 preview and no next-cycle note.
6. Because the next payment is outside the window, the two real cohorts are advanced only by `SLT-TT-00` on D8, one known action ID at a time after its queue pre-flight; hook-wide drains remain forbidden.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and cart preview | — | — | Complete delta after `M0`; zero message attributable to this task, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-12-01-subscription-tab.png`, `SLT-PROD-12-02-segment-slider-legend.png`, `SLT-PROD-12-03-after-reload.png`, `SLT-PROD-12-04-cart-D1-day3.png`.
- Product ID; full flex meta dump; raw Shop Access rule showing the ID exactly once; console errors from the slider bundle `flexible-renewal-sync.js`.

## Pass criteria
- [ ] Published as month/1 at $30.00 with flex sync enabled
- [ ] seg1_end=24, seg2_end=27, all three segments active
- [ ] Legend survives reload as 1-24 / 25-27 / 28-30
- [ ] D1 day-24 cart preview charges $30.00 with no next-cycle note
- [ ] Purchase-date-to-segment table recorded in the registry
- [ ] Parent product ID is present exactly once in the preserved Shop Access exclusion list before the cart preview
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: D1 supplies the live segment-1 preview, `SLT-SYN-06` buys segment 2 as `slt2-flex2` on D2, and `SLT-SYN-10` buys segment 3 as `slt2-flex3` on D6. This is the only month-interval product in the plan.
- Restores: cart emptied. Product deleted by `SLT-SETUP-99B`.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
