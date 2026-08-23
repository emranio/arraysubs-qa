---
id: 22
title: SLT-PROD-14 Create the two daily flex-sync partition products (2-active and 1-active)
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

> **SLT-PROD-14** · group `catalog` · scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Cover the two partition shapes the calendar products cannot reach and get segment-3 `next_cycle` behaviour onto a real, unattended daily schedule. Both products use `day` with interval 3 — the smallest cycle at or above `SegmentPlan::MIN_CYCLE_DAYS = 3` that still renews twice inside the window. A crucial code-verified consequence: for the `day` period `cycle_start` is the purchase day itself, so day-in-cycle is ALWAYS 1, which means the FIRST ACTIVE segment always wins and segment selection is controlled purely by which toggles are off.

## Scope
- Gateway: N/A (gateway gating is delegated to SLT-SETUP-05)
- Checkout: block-cart preview only; no checkout or purchase
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete. Global sync is OFF, so both products sync only because of the per-product pro override — they are the proof of that override.
- Binding D1 model: a purchase on `2026-08-24` site with day/3 uses cycle start `2026-08-23 18:00:00` UTC. Product A next pays `2026-08-26 18:00:00` UTC (08-27 site); Product B rewrites its covered cycle start to that boundary and next pays `2026-08-29 18:00:00` UTC (08-30 site).

## Test data
| Item | Value |
|---|---|
| Product A | SLT2 Flex Daily Two Seg / slug `slt2-flex-daily-two-seg`, $9.00, day/3, segments 2+3 active (segment 1 OFF), boundary seg1_end = 1 |
| Product B | SLT2 Flex Daily Next Cycle / slug `slt2-flex-daily-next-cycle`, $9.00, day/3, ONLY segment 3 active |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | A: $9.00 today, renew 2026-08-27 00:00 site. B: $9.00 today, renew 2026-08-30 00:00 site |

## Steps
1. Capture `M0=$(mailpit-agent latest-id)`. At the end, inspect every message newer than `M0`; classify unrelated/background mail by its actual owner.
2. In `agent-browser --session admin-SLT-PROD-14`, create Product A: new product, title `SLT2 Flex Daily Two Seg`, description `SLT2 window product. Day/3, 2-active segment partition. Delete on 2026-09-05.`, **Simple product**, **Virtual**, tick **Subscription [ArraySubs]**, **Regular price ($)** `9.00`.
3. **Subscription [ArraySubs]** tab for A: **Billing Period** `Day`, **Billing Interval** `3`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked.
4. Tick **Flexible Renewal Sync to Next Billing Cycle** (slider `data-cycle-days="3"`). Turn the **Full amount** (segment 1) legend toggle OFF; leave **Prorate amount** and **Charge full for next billing cycle** ON. Drag the single remaining handle so the legend reads segment 2 = `1`, segment 3 = `2 - 3`. Screenshot. Turn segment 2 OFF; this is a valid one-active precondition because segment 3 remains ON. Then attempt to turn segment 3 OFF and confirm the UI refuses with the exact message "At least one segment must stay active." and leaves segment 3 ON. Restore segment 2 ON before publishing Product A and re-confirm the two-row `1` / `2 - 3` legend.
5. Slug `slt2-flex-daily-two-seg`. Publish. Reload and confirm the 2-row legend redraws.
6. Create Product B: title `SLT2 Flex Daily Next Cycle`, description `SLT2 window product. Day/3, single-active segment 3. Delete on 2026-09-05.`, same type/virtual/subscription flags, **Regular price ($)** `9.00`, **Billing Period** `Day`, **Billing Interval** `3`, length 0, trial 0, no fee.
7. Tick **Flexible Renewal Sync**; turn BOTH **Full amount** and **Prorate amount** toggles OFF, leaving only **Charge full for next billing cycle** ON. The legend must collapse to a single row covering `1 - 3` with no boundary handle. Screenshot.
8. Slug `slt2-flex-daily-next-cycle`. Publish. Reload and confirm.
8a. Before the cart preview, append both parent product IDs only to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior SLT2 exclusion; re-read the raw option and require each new ID exactly once.
9. Verify metas for both: `wp post meta list <ID> --keys=_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
10. As `--session guest-SLT-PROD-14`, add Product B to an empty cart and capture only the cart note: `Today's payment covers the full billing cycle starting 6 August, 2026`. Empty the cart. Do not repeat the gateway matrix here; cite `SLT-SETUP-05` for the Stripe-present/Paddle-hidden evidence.
11. Append both IDs to the registry.
12. Confirm the guest cart is still empty, then close only `admin-SLT-PROD-14` and `guest-SLT-PROD-14`.

## Expected results
1. Both products published, simple + virtual + subscription, `_subscription_period=day`, `_subscription_interval=3`, `_regular_price=9.00`.
2. Product A: `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_active=no`, `seg2_active=yes`, `seg3_active=yes`, `_arraysubs_flex_sync_seg1_end=1`. Legend is two rows: `1` / `2 - 3`.
3. Product B: `seg1_active=no`, `seg2_active=no`, `seg3_active=yes`; the legend is one row `1 - 3` and no boundary is used (`getConfig()` returns an empty boundaries array for a 1-active plan).
4. The UI refuses to leave zero segments active with the exact message "At least one segment must stay active."
5. Purchase contract for A (bought 2026-08-24): day-in-cycle 1 -> first active segment is 2 -> mode `prorate`. Because day-period proration resolves to ratio 1.0 here, the charge is the FULL $9.00. Cycle start is `2026-08-23 18:00:00` UTC and next payment is `2026-08-26 18:00:00` UTC (08-27 site), then 08-30 and 09-02 site. Prorate mode being indistinguishable from full on a `day` period is expected, not a bug; genuine proration lives on SLT-PROD-13.
6. Purchase contract for B (bought 2026-08-24): day-in-cycle 1 -> segment 3 -> mode `next_cycle`; charge the full $9.00 today, `flex_covered_cycle_start` = `2026-08-26 18:00:00` UTC (08-27 site), and next payment is pushed one whole cycle to **`2026-08-29 18:00:00` UTC (08-30 site)** — visibly later than A.
7. Gateway gating is owned by `SLT-SETUP-05`: cite its evidence showing Stripe present and Paddle absent for the sync-eligible product. This task does not duplicate the checkout-accordion probe.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Two publishes and the cart previews | — | — | Complete delta after `M0`; zero message attributable to this task, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-14-01-two-seg-legend.png`, `SLT-PROD-14-02-last-active-refusal.png`, `SLT-PROD-14-03-next-cycle-single-legend.png`, `SLT-PROD-14-04-cart-next-cycle-note.png`; plus the cited `SLT-SETUP-05` gateway evidence ID.
- Both product IDs; both meta dumps; raw Shop Access rule showing both IDs exactly once; slider console errors.

## Pass criteria
- [ ] Product A saved with a 2-active partition (seg1 off) and legend 1 / 2-3
- [ ] Product B saved with a 1-active partition (segment 3 only) and legend 1-3
- [ ] Last-active-segment refusal captured verbatim
- [ ] Cart on B shows the next-cycle bonus-access note naming 6 August, 2026
- [ ] Gateway gating cited from `SLT-SETUP-05`; no duplicate accordion probe
- [ ] Both parent product IDs are present exactly once in the preserved Shop Access exclusion list before the cart preview
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: `SLT-SYN-08` buys both on D1 after 12:00, A as `slt2-flex2` and B as `slt2-flex3`, one at a time. A renews 08-27 / 08-30 / 09-02 site; B renews 08-30 / 09-02 site — unattended and inside the watch window.
- Restores: cart emptied. Both deleted by SLT-SETUP-99B.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
