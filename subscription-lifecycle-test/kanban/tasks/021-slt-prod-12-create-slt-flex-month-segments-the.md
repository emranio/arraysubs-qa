---
id: 21
title: SLT-PROD-12 Create SLT Flex Month Segments, the single month-interval flexible-sync product
status: done
priority: high
created: 2026-08-02T03:43:04.798522404+02:00
updated: 2026-08-03T03:16:16.86467179+02:00
started: 2026-08-03T03:16:16.864670989+02:00
completed: 2026-08-03T03:16:16.864670989+02:00
tags:
    - setup
    - products
    - day-01
due: "2026-08-03"
estimate: 1h
depends_on:
    - 10
    - 11
class: standard
---

> **SLT-PROD-12** · group `catalog` · scheduled **D01** (2026-08-03)

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
- Binding D1 preview model: on 2026-08-03 site the month cycle is still `2026-07-31 18:00:00` → `2026-08-31 18:00:00` UTC, but day-in-cycle is 3, so the first reachable live mode is segment 2 (`prorate`). Segment 1 is verified by the deterministic config probe only; product creation occurs after its Aug 1–2 live window.
- Segment 2 is purchased by `SLT-SYN-06` on D2; segment 3 is purchased by `SLT-SYN-10` on D6.

## Test data
| Item | Value |
|---|---|
| Product | SLT Flex Month Segments / slug `slt-flex-month-segments` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $30.00/month; segment-dependent first charge (see Expected results) |

Segment plan: nominal cycle 30 days, all three segments ACTIVE, boundaries seg1_end = `2`, seg2_end = `6`. Partition: days 1-2 = segment 1 (full), days 3-6 = segment 2 (prorate), days 7-30(+31st overflow) = segment 3 (charge full for next billing cycle).

## Steps
1. Capture `M0=$(mailpit-agent latest-id)`. At the end, inspect every message newer than `M0`; classify unrelated/background mail by its actual owner.
2. `agent-browser --session admin-SLT-PROD-12 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Flex Month Segments`. **Description**: `SLT window product. Monthly, flexible renewal sync, 3 active segments. Delete on 2026-08-15.`
4. **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `30.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Month`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** UNTICKED (ticking it would hide the flex section outright).
7. Tick **Flexible Renewal Sync to Next Billing Cycle**. The segment slider appears with `data-cycle-days="30"`.
8. Leave all three legend toggles ON (**Full amount**, **Prorate amount**, **Charge full for next billing cycle**).
9. Drag the slider handles until the legend reads segment 1 = `1 - 2`, segment 2 = `3 - 6`, segment 3 = `7 - 30`. Screenshot the legend.
10. Slug `slt-flex-month-segments`. Publish. Reload and confirm the slider redraws with the same ranges.
10a. Before the cart preview, append this parent product ID only to Shop Access rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field/prior exclusion; re-read the raw option and require the new ID exactly once.
11. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
12. As `--session guest-SLT-PROD-12`, add to cart and read the cart's subscription meta rows WITHOUT checking out, then empty the cart. On D1 the preview falls on day 3 (segment 2): total **$27.10** (`round(30 × 28/31, 2)`) and no "First billing cycle" bonus-access note.
13. Append the ID to the registry together with the purchase-date-to-segment table.
14. Confirm the guest cart is still empty, then close only `admin-SLT-PROD-12` and `guest-SLT-PROD-12`.

## Expected results
1. Published simple + virtual + subscription, slug `slt-flex-month-segments`, `_subscription_period=month`, `_subscription_interval=1`, `_regular_price=30.00`.
2. `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_end=2`, `_arraysubs_flex_sync_seg2_end=6`, and all three `_active` metas = `yes`.
3. The slider legend after reload reads 1-2 / 3-6 / 7-30.
4. Purchase-date contract for downstream tasks (site-local dates, price $30.00):
   - Synthetic/config-only 2026-08-01 (day 1) -> segment 1, mode `full`, charge $30.00, next payment 2026-09-01 00:00 site. No live checkout is claimed for this past date.
   - Previewed 2026-08-03 (day 3) -> segment 2, mode `prorate`, remaining 28/31, charge `$27.10`, next payment 2026-09-01 00:00 site.
   - Bought 2026-08-04 (day 4) -> segment 2, mode `prorate`, cycle 2026-08-01..2026-09-01 = 31 days, remaining days = 27, ratio 27/31, charge `round(30 * 27/31, 2)` = $26.13, next payment 2026-09-01 00:00 site.
   - Bought 2026-08-08 (day 8) -> segment 3, mode `next_cycle`, charge $30.00 in full, `cycle_start_date` moves to 2026-09-01 and next payment is pushed to 2026-10-01 00:00 site; the cart shows the "Today's payment covers the full billing cycle starting 1 September, 2026" note.
5. Guest cart on D1 shows a $27.10 segment-2 preview and NO bonus-access note.
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
- [ ] seg1_end=2, seg2_end=6, all three segments active
- [ ] Legend survives reload as 1-2 / 3-6 / 7-30
- [ ] D1 day-3 cart preview charges $27.10 with no next-cycle note
- [ ] Purchase-date-to-segment table recorded in the registry
- [ ] Parent product ID is present exactly once in the preserved Shop Access exclusion list before the cart preview
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: `SLT-SYN-06` buys the live segment-2 cohort as `slt-flex2` on D2; `SLT-SYN-10` buys the live segment-3 cohort as `slt-flex3` on D6. Segment 1 remains config-probe coverage because its Aug 1–2 live window predates this product's D1 creation. This is the only month-interval product in the plan.
- Restores: cart emptied. Product deleted by `SLT-SETUP-99B`.

## Execution note — 2026-08-03 early-morning

- Verdict: **PASS**.
- Published product `12093` (`SLT Flex Month Segments`, `slt-flex-month-segments`) as a simple virtual month/1 subscription at `30.00`.
- Verified flex sync enabled, `data-cycle-days=30`, boundaries `2/6`, all three active toggles, and the legend `1-2 / 3-6 / 7-30` both before publish and after reload.
- Added only parent product `12093` through Member Access -> Shop Access. Raw rule `rule_1784662676378_maa3te08s` now has exclusions `[11927,11933,11938,11943,12087,12093]`, with `12093` exactly once and every other field preserved.
- The cache-busted guest preview started from an empty cart and showed `27.10`, prorated until the synced renewal date, next charge 1 September 2026 at `30.00`, and no next-cycle bonus-access note. No checkout/order was attempted; the cart was emptied and re-proven empty.
- Registry page `11847` was updated through the browser editor with the authored purchase-date/segment table; matching section count is one.
- Mailpit baseline/final: `42DI8ELEccd8qFsaMtyeag`; zero attributable mail.
- Slider/admin errors were empty. The guest cart emitted only the previously filed global wcBlocksData dependency warning (`issues/light-plugin-SLT-CHK-01-wc-blocks-data-dependency-warning.md`).
- Consolidated evidence: `/home/server-manager/slt-evidence/SLT-PROD-12-facts.txt`.
- Independent evidence review reloaded product `12093` and recaptured `SLT-PROD-12-03-after-reload.png` with the persisted `2/6` handles, `1-2 / 3-6 / 7-30` legend, and all three active toggles visibly in frame.

---

### Verified environment facts (2026-08-01/02 — do not re-derive)

- **Nothing fires at `_next_payment_date`.** Every scheduled leg is shifted by
  `crc32('arraysubs-spread-'.$subscription_id) % 21600` (0-6 h). Charge fires at `due + offset`,
  invoice at `due + offset - 6h`. The stored date never moves. **Assert a window, not a point.**
- Currency `USD`. **Taxes are OFF** (`woocommerce_calc_taxes = no`) — never assert a tax line.
- Orders use **HPOS** (`wp_wc_orders`), not `wp_posts`.
- `woocommerce_enable_guest_checkout = yes`, but ArraySubs force-requires registration for
  **subscription** carts via `woocommerce_checkout_registration_required`
  (`SubscriptionCheckout/Services/Hooks.php:103`, `CheckoutHelpersTrait.php:93-100`).
- WooCommerce **grouped** products have zero handling in either plugin — grouped tasks are
  exploratory: document behaviour, do not assert a spec.
- WP-Cron runs every minute from `/etc/cron.d/mirror-help-arrayhash-wordpress`. Scheduled actions
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.
