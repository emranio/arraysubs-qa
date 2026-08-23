---
id: 8
title: SLT-PROD-13 Create SLT2 Flex Week Segments, the single week-interval flexible-sync product
status: blocked
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-23T03:02:39.942542055+02:00
started: 2026-08-22T22:25:23.735628817+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-00
due: "2026-08-23"
estimate: 1h
depends_on:
    - 10
    - 11
blocked: true
block_reason: 'Shared issue #2: out-of-phase D00 mutation and missing authoritative registry publication'
class: standard
---

> **SLT-PROD-13** · group `catalog` · scheduled **D00** (2026-08-23)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Create the ONE week-interval product. It covers the week-boundary branch of `arraysubs_calculate_renewal_sync_cycle_dates()`, which snaps the cycle start to the store's start-of-week. Reconfirm the live `start_of_week` during D0; when it is **6 (Saturday)**, the active D0 cycle starts 2026-08-22 and ends 2026-08-29. D0 is Sunday 2026-08-23 (day 2), still inside segment 1, so unlike the month product this one also produces a real renewal inside the window.

## Scope
- Gateway: N/A (gateway gating is verified elsewhere)
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete (global sync off; this product syncs via the per-product pro override).
- Reconfirm with the live helper on D0: when `start_of_week=6`, purchase at `2026-08-23 09:00:00` site resolves to cycle start `2026-08-21 18:00:00` UTC (2026-08-22 00:00 site) and next payment `2026-08-28 18:00:00` UTC (2026-08-29 00:00 site).
- 2026-08-22 is Saturday; the D0 purchase date 2026-08-23 is day-in-cycle 2, still segment 1. If the live week-start differs, recompute every weekly expectation before creating the product and update the registry/calendar handoff.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Flex Week Segments / slug `slt2-flex-week-segments` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $14.00/week; segment-dependent first charge (see Expected results) |

Segment plan: nominal cycle 7 days, all three segments ACTIVE, boundaries seg1_end = `2`, seg2_end = `5`. Partition: days 1-2 = segment 1 (full), days 3-5 = segment 2 (prorate), days 6-7 = segment 3 (next cycle).

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin-SLT-PROD-13 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT2 Flex Week Segments`. **Description**: `SLT2 window product. Weekly, flexible renewal sync, 3 active segments. Delete on 2026-09-05.`
4. **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `14.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Week`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked.
7. Tick **Flexible Renewal Sync to Next Billing Cycle**; the slider appears with `data-cycle-days="7"`.
8. Leave all three legend toggles ON and drag the handles until the legend reads 1 = `1 - 2`, 2 = `3 - 5`, 3 = `6 - 7`. Screenshot.
9. Slug `slt2-flex-week-segments`. Publish. Reload and confirm the ranges redraw identically.
10. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
10a. Preserve the existing full-store Member Access rule and append only this product ID to `exclusion_product_ids` through **Member Access → Shop Access**, so the guest cart and later sync purchases are not intercepted by the unrelated members-only fixture.
11. As `--session guest-SLT-PROD-13`, add to cart, read the subscription meta rows and the first-charge amount, then empty the cart.
12. Append the ID plus the purchase-date-to-segment table to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt2-flex-week-segments`, `_subscription_period=week`, `_subscription_interval=1`, `_regular_price=14.00`.
2. `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_end=2`, `_arraysubs_flex_sync_seg2_end=5`, all three `_active` = `yes`.
3. Legend after reload: 1-2 / 3-5 / 6-7.
4. Purchase-date contract (site-local, price $14.00, active week cycle 2026-08-22..2026-08-29 when `start_of_week=6`):
   - Bought 2026-08-23 (Sun, day 2; actual D0) -> segment 1, mode `full`, charge $14.00, next payment 2026-08-29 00:00 site — a REAL renewal on D6, no time travel needed.
   - Bought 2026-08-25 (Tue, day 4) -> segment 2, mode `prorate`; cycle_days 7, days_until_next 4, remaining = max(1, 4-1) = 3, ratio 3/7, charge `round(14 * 3/7, 2)` = $6.00; next payment 2026-08-29 00:00 site.
   - Bought 2026-08-27 (Thu, day 6) -> segment 3, mode `next_cycle`, charge $14.00 in full covering the cycle starting 2026-08-29, next payment pushed to 2026-09-05 00:00 site, and the cart note names the 29 August 2026 covered-cycle boundary.
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
- [ ] D0/day-2 cart charges $14.00 and the D0 purchase is dated to renew 2026-08-29
- [ ] Purchase-date-to-segment table recorded
- [ ] Zero mail, cart left empty

## SLT2 execution — SUPERSEDED / BLOCKED (site date 2026-08-23)

- Live `start_of_week=6`; at both the current D0 time and the authored 09:00 site probe, the helper returned cycle start `2026-08-21 18:00:00` UTC and next payment `2026-08-28 18:00:00` UTC (`2026-08-29 00:00` site).
- Browser-published product `31363` as simple/virtual, week/1, price `14.00`, flex enabled. Meta and the reloaded UI agree on handles `2`/`5`, all segments `yes`, and legends `1 - 2` / `3 - 5` / `6 - 7`.
- The real D0/day-2 cart rendered `$14.00 every 1 week`, `$14.00 full first charge`, next charge `29 August, 2026 (UTC+6) ($14.00)`, and total `$14.00`. The cart was emptied after capture.
- Added only `31363` to Shop Access exclusions, now `[31340,31347,31357,31363]`; registry `31301` contains the product plus the D0/D2/D4 segment table. Mailpit baseline/latest stayed `1dKG8mscVMI2jlnj8Pzk3k`; browser errors were empty.
- Console recorded the already-observed Woo dependency-detection warning for an inline/unknown `wc.wcBlocksData` consumer, but no task error or functional failure. Evidence: `/home/server-manager/slt-evidence/SLT-PROD-13-*`.
- No checkout order, subscription, or payment occurred. Publishing did create registered Paddle sandbox catalogue product `pro_01m0njkv2n85sm0j5kxegen19t` and price `pri_01m0njkw5yy2r9bz83wc2crr6w`; shared issue #2 owns the invalid phase/registry result. The actual Stripe D0 segment-1 purchase remains lifecycle task 14 and is blocked behind shared issue #1.

## Isolation / teardown
- State handoff: buy ONLY as `slt2-flex`, three separate times — D0 (segment 1, and let the 2026-08-29 renewal fire for real), 2026-08-25 (segment 2, the ONLY place in the whole plan where a genuinely prorated first charge is observable) and 2026-08-27 (segment 3). This is the ONLY week-interval product in the plan.
- Restores: cart emptied. Keep the product through the D12 watch; `SLT-SETUP-99B` deletes it on 2026-09-05.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.

[[2026-08-23]] Sun 02:39

## D00 early-watcher phase-integrity correction — 2026-08-23

- Product 31363 was published at 02:29:21 site and auto-created Paddle sandbox objects `pro_01m0njkv2n85sm0j5kxegen19t` / `pri_01m0njkw5yy2r9bz83wc2crr6w`.
- D00 watch ownership assigns this card to afternoon at approximately 16:10 site, but its browser mutation occurred roughly 13.5-14.5 hours early. Its prior PASS therefore cannot stand under the binding phase rule.
- The authoritative TSV also omitted these identities at completion. The watcher backfilled only exact proven identity/provider rows with `cleanup_approved=no`; this containment does not waive timing or proof defects.
- Shared issue #2 owns the blocker. Do not delete, recreate, rename, or duplicate the fixture. The afternoon owner must use an approved non-duplicating revalidation protocol and rerun every mandatory assertion before unblocking this card.

[[2026-08-23]] Sun 03:02

## Closure-audit normalization

Stale PASS heading/checkmarks were reset, issue #2 linkage was made explicit, and provider-side catalogue wording was corrected where applicable. The lifecycle start timestamp now matches the original `todo -> in-progress` activity event. Status remains blocked; this note is tracking normalization, not fresh test proof.
