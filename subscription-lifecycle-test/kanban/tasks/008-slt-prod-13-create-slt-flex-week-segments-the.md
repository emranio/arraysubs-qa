---
id: 8
title: SLT-PROD-13 Create SLT Flex Week Segments, the single week-interval flexible-sync product
status: done
priority: high
created: 2026-08-02T03:43:03.54302273+02:00
updated: 2026-08-02T14:20:25.181820846+02:00
started: 2026-08-02T14:20:25.181820065+02:00
completed: 2026-08-02T14:20:25.181820065+02:00
tags:
    - setup
    - products
    - day-00
due: "2026-08-02"
estimate: 1h
depends_on:
    - 10
    - 11
class: standard
---

> **SLT-PROD-13** · group `catalog` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Create the ONE week-interval product. It covers the week-boundary branch of `arraysubs_calculate_renewal_sync_cycle_dates()`, which snaps the cycle start to the store's start-of-week. This store's `start_of_week` is **6 (Saturday)**; the active cycle starts 2026-08-01 and ends 2026-08-08. D0 is Sunday 2026-08-02 (day 2), still inside segment 1, so unlike the month product this one also produces a real renewal inside the window.

## Scope
- Gateway: N/A (gateway gating is verified elsewhere)
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete (global sync off; this product syncs via the per-product pro override).
- Verified live against the site helper: purchase at `2026-08-02 09:00:00` site -> cycle_start `2026-07-31 18:00:00` UTC (2026-08-01 00:00 site), next_payment `2026-08-07 18:00:00` UTC (**2026-08-08 00:00 site**).
- 2026-08-01 is Saturday and `start_of_week=6`; the actual D0 purchase date 2026-08-02 is day-in-cycle 2, still segment 1.

## Test data
| Item | Value |
|---|---|
| Product | SLT Flex Week Segments / slug `slt-flex-week-segments` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $14.00/week; segment-dependent first charge (see Expected results) |

Segment plan: nominal cycle 7 days, all three segments ACTIVE, boundaries seg1_end = `2`, seg2_end = `5`. Partition: days 1-2 = segment 1 (full), days 3-5 = segment 2 (prorate), days 6-7 = segment 3 (next cycle).

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin-SLT-PROD-13 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Flex Week Segments`. **Description**: `SLT window product. Weekly, flexible renewal sync, 3 active segments. Delete on 2026-08-15.`
4. **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `14.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Week`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked.
7. Tick **Flexible Renewal Sync to Next Billing Cycle**; the slider appears with `data-cycle-days="7"`.
8. Leave all three legend toggles ON and drag the handles until the legend reads 1 = `1 - 2`, 2 = `3 - 5`, 3 = `6 - 7`. Screenshot.
9. Slug `slt-flex-week-segments`. Publish. Reload and confirm the ranges redraw identically.
10. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
11. As `--session guest-SLT-PROD-13`, add to cart, read the subscription meta rows and the first-charge amount, then empty the cart.
12. Append the ID plus the purchase-date-to-segment table to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-flex-week-segments`, `_subscription_period=week`, `_subscription_interval=1`, `_regular_price=14.00`.
2. `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_end=2`, `_arraysubs_flex_sync_seg2_end=5`, all three `_active` = `yes`.
3. Legend after reload: 1-2 / 3-5 / 6-7.
4. Purchase-date contract (site-local, price $14.00, week cycle 2026-08-01..2026-08-08):
   - Bought 2026-08-02 (Sun, day 2; actual D0) -> segment 1, mode `full`, charge $14.00, next payment 2026-08-08 00:00 site — a REAL renewal on D6, no time travel needed.
   - Bought 2026-08-04 (Tue, day 4) -> segment 2, mode `prorate`; cycle_days 7, days_until_next 4, remaining = max(1, 4-1) = 3, ratio 3/7, charge `round(14 * 3/7, 2)` = $6.00; next payment 2026-08-08 00:00 site.
   - Bought 2026-08-06 (Thu, day 6) -> segment 3, mode `next_cycle`, charge $14.00 in full covering the cycle starting 2026-08-08, next payment pushed to 2026-08-15 00:00 site (outside the window -> time-travel), and the cart shows the "covers the full billing cycle starting 8 August, 2026" note.
5. Guest cart on D0 (day 2 of the cycle) shows a $14.00 first charge, no next-cycle note.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and cart preview | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-13-01-subscription-tab.png`, `SLT-PROD-13-02-segment-slider-legend.png`, `SLT-PROD-13-03-after-reload.png`, `SLT-PROD-13-04-cart-day1.png`.
- Product ID; full flex meta dump; slider console errors.

## Pass criteria
- [ ] Published as week/1 at $14.00 with flex sync enabled
- [ ] seg1_end=2, seg2_end=5, all three segments active
- [ ] Legend survives reload as 1-2 / 3-5 / 6-7
- [ ] D0/day-2 cart charges $14.00 and the D0 purchase is dated to renew 2026-08-08
- [ ] Purchase-date-to-segment table recorded
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy ONLY as `slt-flex`, three separate times — D0 (segment 1, and let the 2026-08-08 renewal fire for real), 2026-08-04 (segment 2, the ONLY place in the whole plan where a genuinely prorated first charge is observable) and 2026-08-06 (segment 3). This is the ONLY week-interval product in the plan.
- Restores: cart emptied. Keep the product through the D12 watch; `SLT-SETUP-99B` deletes it on 2026-08-15.

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

[[2026-08-02]] Sun 14:20


## Execution 2026-08-02 — PASS (purchase correctly deferred behind audit gate)
- Published simple virtual subscription product ID 11943, slug `slt-flex-week-segments`, at $14.00/week.
- Flex sync saved and survived reload: seg1_end=2, seg2_end=5, all three active; legend 1-2 full / 3-5 prorate / 6-7 next cycle.
- Admin console had no slider errors.
- Date-shifted D0 is Sunday 2026-08-02 (cycle day 2). Isolated guest checkout preview confirmed $14.00 full first charge and next charge 2026-08-08 00:00 UTC+6, with no next-cycle note.
- Guest cart was proven empty before the preview and explicitly emptied afterward.
- No live purchase was placed here: the critical conflict resolution requires SLT-SYN-01A to audit, restore, and authorize the six flex metas before SLT-SYN-05 buys later on D0.
- Registry page 11847 records product ID, the three purchase-date cohorts, access exclusion, and the purchase gate.
- Mailpit latest ID stayed `1vpHEKG6i8l9ZzBoW2BqrI`.
- Inherited SLT-PROD-01 isolation: rule `rule_1784662676378_maa3te08s` exclusions now [11927, 11933, 11938, 11943]; exact pre-window restore remains assigned to SLT-SETUP-99A.
- Evidence: `/home/server-manager/slt-evidence/SLT-PROD-13-*`.
