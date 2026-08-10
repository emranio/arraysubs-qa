---
id: 22
title: SLT-PROD-14 Create the two daily flex-sync partition products (2-active and 1-active)
status: done
priority: high
created: 2026-08-02T03:43:04.857701037+02:00
updated: 2026-08-03T04:14:15.637683762+02:00
started: 2026-08-03T04:14:15.637683091+02:00
completed: 2026-08-03T04:14:15.637683091+02:00
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

> **SLT-PROD-14** · group `catalog` · scheduled **D01** (2026-08-03)

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
- Binding D1 model: a purchase on `2026-08-03` site with day/3 uses cycle start `2026-08-02 18:00:00` UTC. Product A next pays `2026-08-05 18:00:00` UTC (08-06 site); Product B rewrites its covered cycle start to that boundary and next pays `2026-08-08 18:00:00` UTC (08-09 site).

## Test data
| Item | Value |
|---|---|
| Product A | SLT Flex Daily Two Seg / slug `slt-flex-daily-two-seg`, $9.00, day/3, segments 2+3 active (segment 1 OFF), boundary seg1_end = 1 |
| Product B | SLT Flex Daily Next Cycle / slug `slt-flex-daily-next-cycle`, $9.00, day/3, ONLY segment 3 active |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | A: $9.00 today, renew 2026-08-06 00:00 site. B: $9.00 today, renew 2026-08-09 00:00 site |

## Steps
1. Capture `M0=$(mailpit-agent latest-id)`. At the end, inspect every message newer than `M0`; classify unrelated/background mail by its actual owner.
2. In `agent-browser --session admin-SLT-PROD-14`, create Product A: new product, title `SLT Flex Daily Two Seg`, description `SLT window product. Day/3, 2-active segment partition. Delete on 2026-08-15.`, **Simple product**, **Virtual**, tick **Subscription [ArraySubs]**, **Regular price ($)** `9.00`.
3. **Subscription [ArraySubs]** tab for A: **Billing Period** `Day`, **Billing Interval** `3`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked.
4. Tick **Flexible Renewal Sync to Next Billing Cycle** (slider `data-cycle-days="3"`). Turn the **Full amount** (segment 1) legend toggle OFF; leave **Prorate amount** and **Charge full for next billing cycle** ON. Drag the single remaining handle so the legend reads segment 2 = `1`, segment 3 = `2 - 3`. Screenshot. Turn segment 2 OFF; this is a valid one-active precondition because segment 3 remains ON. Then attempt to turn segment 3 OFF and confirm the UI refuses with the exact message "At least one segment must stay active." and leaves segment 3 ON. Restore segment 2 ON before publishing Product A and re-confirm the two-row `1` / `2 - 3` legend.
5. Slug `slt-flex-daily-two-seg`. Publish. Reload and confirm the 2-row legend redraws.
6. Create Product B: title `SLT Flex Daily Next Cycle`, description `SLT window product. Day/3, single-active segment 3. Delete on 2026-08-15.`, same type/virtual/subscription flags, **Regular price ($)** `9.00`, **Billing Period** `Day`, **Billing Interval** `3`, length 0, trial 0, no fee.
7. Tick **Flexible Renewal Sync**; turn BOTH **Full amount** and **Prorate amount** toggles OFF, leaving only **Charge full for next billing cycle** ON. The legend must collapse to a single row covering `1 - 3` with no boundary handle. Screenshot.
8. Slug `slt-flex-daily-next-cycle`. Publish. Reload and confirm.
8a. Before the cart preview, append both parent product IDs only to Shop Access rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior SLT exclusion; re-read the raw option and require each new ID exactly once.
9. Verify metas for both: `wp post meta list <ID> --keys=_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
10. As `--session guest-SLT-PROD-14`, add Product B to an empty cart and capture only the cart note: `Today's payment covers the full billing cycle starting 6 August, 2026`. Empty the cart. Do not repeat the gateway matrix here; cite `SLT-SETUP-05` for the Stripe-present/Paddle-hidden evidence.
11. Append both IDs to the registry.
12. Confirm the guest cart is still empty, then close only `admin-SLT-PROD-14` and `guest-SLT-PROD-14`.

## Expected results
1. Both products published, simple + virtual + subscription, `_subscription_period=day`, `_subscription_interval=3`, `_regular_price=9.00`.
2. Product A: `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_active=no`, `seg2_active=yes`, `seg3_active=yes`, `_arraysubs_flex_sync_seg1_end=1`. Legend is two rows: `1` / `2 - 3`.
3. Product B: `seg1_active=no`, `seg2_active=no`, `seg3_active=yes`; the legend is one row `1 - 3` and no boundary is used (`getConfig()` returns an empty boundaries array for a 1-active plan).
4. The UI refuses to leave zero segments active with the exact message "At least one segment must stay active."
5. Purchase contract for A (bought 2026-08-03): day-in-cycle 1 -> first active segment is 2 -> mode `prorate`. Because day-period proration resolves to ratio 1.0 here, the charge is the FULL $9.00. Cycle start is `2026-08-02 18:00:00` UTC and next payment is `2026-08-05 18:00:00` UTC (08-06 site), then 08-09 and 08-12 site. Prorate mode being indistinguishable from full on a `day` period is expected, not a bug; genuine proration lives on SLT-PROD-13.
6. Purchase contract for B (bought 2026-08-03): day-in-cycle 1 -> segment 3 -> mode `next_cycle`; charge the full $9.00 today, `flex_covered_cycle_start` = `2026-08-05 18:00:00` UTC (08-06 site), and next payment is pushed one whole cycle to **`2026-08-08 18:00:00` UTC (08-09 site)** — visibly later than A.
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
- State handoff: `SLT-SYN-08` buys both on D1 after 12:00, A as `slt-flex2` and B as `slt-flex3`, one at a time. A renews 08-06 / 08-09 / 08-12 site; B renews 08-09 / 08-12 site — unattended and inside the watch window.
- Restores: cart emptied. Both deleted by SLT-SETUP-99B.

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

## Execution note — D01 early-morning (2026-08-03)

Verdict: **PASS**.

- Published Product A `12099` (`slt-flex-daily-two-seg`) and Product B `12102` (`slt-flex-daily-next-cycle`) with the authored simple/virtual day/3/$9 subscription configuration.
- Product A saved/reloaded with segment 1 OFF and segments 2+3 ON; legend `1` / `2 - 3`. Product B saved/reloaded with only segment 3 ON; legend `1 - 3` and no visible boundary handle.
- Captured the exact last-active refusal `At least one segment must stay active.` after first establishing the valid segment-3-only state, then restored A to its authored two-active state before publish.
- Through Member Access -> Shop Access, appended parent IDs `12099` and `12102` exactly once to rule `rule_1784662676378_maa3te08s`; raw exclusions are `[11927,11933,11938,11943,12087,12093,12099,12102]` and all other rule fields were preserved.
- Empty-cart, cache-busted Product B preview showed the exact 6 August 2026 bonus-access coverage note and next charge 9 August 2026; cart was emptied and rechecked.
- Registry page `11847` contains one task section with both IDs and the purchase/schedule handoff.
- Mailpit remained `42DI8ELEccd8qFsaMtyeag`; zero attributable messages. Both task browser sessions were closed.
- Gateway accordion evidence remains intentionally owned by dependent task `SLT-SETUP-05`; this task did not duplicate that probe.
- Evidence: `/home/server-manager/slt-evidence/SLT-PROD-14-facts.txt` and `/home/server-manager/slt-evidence/SLT-PROD-14-01-two-seg-legend.png` through `SLT-PROD-14-04-cart-next-cycle-note.png`.
