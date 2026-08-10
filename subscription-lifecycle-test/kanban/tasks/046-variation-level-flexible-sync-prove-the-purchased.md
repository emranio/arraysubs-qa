---
id: 46
title: 'Variation-level flexible sync: prove the purchased variation''s segment plan wins over a parent decoy'
status: done
priority: high
created: 2026-08-02T03:43:06.771934046+02:00
updated: 2026-08-05T21:37:49.543500135+02:00
started: 2026-08-05T21:05:26.203266365+02:00
completed: 2026-08-05T21:05:26.203266365+02:00
tags:
    - renewal-sync
    - day-02
due: "2026-08-04"
estimate: 1h30m
depends_on:
    - 40
    - 44
    - 11
    - 12
class: standard
---

> **SLT-SYN-13** · group `sync` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove `filterRenewalSyncContext()` resolves the segment plan from the PURCHASED VARIATION, not the parent. Two variations of `SLT Flex Variable Daily` share one day/3 $12.00 schedule and differ only in segment plan; a decoy plan force-written onto the parent would collapse both to `next_cycle` if the parent won.

## Scope
- Gateway: Stripe test
- Checkout: block (`/checkout/`)
- Account: existing `slt-flex`, `slt-flex3`
- Plugins: pro-required

## Preconditions
- SLT-PROD-15 created parent `<PARENT>` and variations `<V_FULL>` (3 active, seg1_end 1, seg2_end 2), `<V_NEXT>` (segment 3 only), `<V_NOSYNC>` (flex off). Quote SLT-SYN-02's authorised dump in evidence.
- This task OWNS both `SLT Flex Variable Daily` purchases; no other may buy this parent. Both run **after 12:00 site on 2026-08-04**; the decoy must be gone before day end.
- Per plan-audit fix `<V_NOSYNC>` is NOT purchased — asserted by `getConfig() === null`.
- The parent-decoy deviation is one declared bracket of at most 90 minutes. Record open/close UTC timestamps in `/home/server-manager/slt-evidence/SLT-SYN-13-decoy-bracket.txt` and the registry; no other task may cart, checkout, save, or inspect this parent inside the bracket.

## Test data
| Item | Value |
|---|---|
| Product | `SLT Flex Variable Daily`, attribute **SLT Sync Mode**, variations day/3 $12.00 |
| Parent decoy | `_arraysubs_flex_sync_enabled=yes`, seg1/seg2 `_active` `no`, seg3 `yes`, seg1_end 1, seg2_end 2 |
| Buy date / card | 2026-08-04 (cycle_start 08-04 00:00 site, day-in-cycle 1 for both) / `4242…4242` |

## Steps
1. `M0=$(mailpit-agent latest-id)`. Resolve the parent and three variation IDs from the `SLT-SYN-02` registry handoff into shell variables `PARENT_ID`, `V_FULL_ID`, `V_NEXT_ID`, and `V_NOSYNC_ID`; abort unless all four are numeric and distinct. Re-dump the six flex metas on all three variations and check against SLT-SYN-02's dump.
2. Record bracket-open UTC in `/home/server-manager/slt-evidence/SLT-SYN-13-decoy-bracket.txt` and the registry. Write exactly these six decoy keys on numeric `$PARENT_ID` only, using one `wp post meta update "$PARENT_ID" ... --allow-root` command per key: `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_active=no`, `_arraysubs_flex_sync_seg2_active=no`, `_arraysubs_flex_sync_seg3_active=yes`, `_arraysubs_flex_sync_seg1_end=1`, `_arraysubs_flex_sync_seg2_end=2`. Touch no variation.
3. Run `wp eval "foreach ([(int) $PARENT_ID,(int) $V_FULL_ID,(int) $V_NEXT_ID,(int) $V_NOSYNC_ID] as \$id) { var_dump(\$id, \\ArraySubsPro\\Features\\FlexibleRenewalSync\\Services\\SegmentPlan::getConfig(\$id)); }" --allow-root`. Inspect the complete delta after `M0`, require zero task-attributable mail while classifying unrelated/background mail, then set `MA=$(mailpit-agent latest-id)` for the first checkout.
4. In `--session customer-a-SLT-SYN-13`, log in as `slt-flex`, open `/cart/` and require the browser and persistent cart EMPTY, pick **SLT Sync Mode = Full**, and add it. If one-click redirects to block checkout, record its summary, then explicitly reopen `/cart/` and capture `SLT-SYN-13-01-cart-full.png`. Open `/checkout/`, pay, record numeric `ORDER_FULL`, and resolve exact numeric `SUB_FULL` through `wp post meta get "$ORDER_FULL" _subscription_ids --format=json --allow-root` plus a strict one-element `jq -e` guard. Cross-check parent/customer/variation and the count delta; never use the WooCommerce order meta accessor or recency. `mailpit-agent wait-new "$MA" 180 "is active"`; classify the complete delta and save the exact four WC/ArraySubs checkout IDs. Reopen `/cart/` and require both cart representations EMPTY after checkout.
5. Set `MB=$(mailpit-agent latest-id)` only after Full's delta is classified. In `--session customer-b-SLT-SYN-13`, log in as `slt-flex3`, require both carts EMPTY, repeat with **Next Cycle**, and handle any one-click redirect by explicitly reopening `/cart/` for `SLT-SYN-13-02-cart-next-cycle-note.png`. Pay, record numeric `ORDER_NEXT`, resolve exact numeric `SUB_NEXT` through the same post-meta JSON path and strict guard, and cross-check parent/customer/variation plus the second count delta. `mailpit-agent wait-new "$MB" 180 "is active"`; require and save the second exact four-message checkout set. Reopen `/cart/` and require both cart representations EMPTY. Record `M7=$(mailpit-agent latest-id)` before teardown.
6. Dump both exact subs: `_renewal_sync_enabled`, `…_first_charge_mode`, `…_cycle_start_date`, `_next_payment_date`, `_variation_id`.
7. Restoration takes priority over every remaining positive assertion. Start it no later than bracket minute 75, and run it immediately on any checkout/evidence failure. Delete all six parent decoy keys named in step 2 from numeric `$PARENT_ID`, one exact `wp post meta delete "$PARENT_ID" ... --allow-root` command per key. Re-run step 3 and `wp post meta list "$PARENT_ID" --allow-root | rg _arraysubs_flex_sync`; require `getConfig($PARENT_ID)` null and zero matching parent keys. Inspect the complete delta after `M7`, require zero message attributable to the cleanup, and classify unrelated/background mail. Record bracket-close UTC in the bracket file and registry, prove elapsed time is at most 90 minutes, and only then release the parent to other tasks.
8. Append both exact orders/subscriptions, offsets, action IDs/times, and the three future `charge−5m` deadlines to the registry and D02 watch report; close only `customer-a-SLT-SYN-13` and `customer-b-SLT-SYN-13`. Keep the card `in-progress`. For `SUB_FULL`, store `FULL_REN1_PRE` and `FULL_REN2_PRE` at least five minutes before its exact 08-07 and 08-10 charge gates. For `SUB_NEXT`, store `NEXT_REN1_PRE` at least five minutes before its exact 08-10 charge gate. Follow up after each gate: require the renewal in `[due+offset, due+offset+10min]`, run `mailpit-agent wait-new "<that exact baseline ID>" 900 "Payment received for subscription #<exact numeric subscription ID>"`, and reconcile every message newer than that baseline. The offset is `crc32('arraysubs-spread-'.$id) % 21600`.

## Expected results
1. `getConfig()`: `<V_FULL>` actives `[1,2,3]`, boundaries `[1,2]`, cycle_days 3; `<V_NEXT>` actives `[3]`, boundaries `[]`; `<V_NOSYNC>` **null**; `<PARENT>` the decoy — present, never used.
2. **Full**: **$12.00**, mode `full`, `_next_payment_date` **`2026-08-06 18:00:00` UTC** = 2026-08-07 00:00 site; cart shows no bonus-access note.
3. **Next Cycle**: **$12.00**, mode `next_cycle`, `_renewal_sync_cycle_start_date` rewritten to `2026-08-06 18:00:00` UTC, `_next_payment_date` **`2026-08-09 18:00:00` UTC** = 2026-08-10 00:00 site; cart reads `Today's payment covers the full billing cycle starting 7 August, 2026`.
4. The dates differ by exactly one 3-day cycle. **If both read 2026-08-10 the decoy won and `product_id` resolves to the parent — write a standalone markdown file under `issues/`; never create a lifecycle-board bug card.**
5. Each sub's `_variation_id` matches the variation bought; `<V_NOSYNC>` is never purchased.
6. After step 7 the parent has none of the six decoy keys and `getConfig(<PARENT>)` is null; the bracket closed within 90 minutes. Both renewals then fire unattended at midnight + their own offset, each $12.00.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | Customer + admin ArraySubs signup ×2 each | steps 4-5 | slt-flex / slt-flex3 / admin | `is active`, `New subscription #` | complete separate MA/MB deltas; save/show exact ids |
| 1a | WC New order + Completed order ×2 each | steps 4-5 | admin / each buyer | `New order #`, `is on its way` | same complete separate deltas; save/show exact ids |
| 2 | `payment_successful` | 08-07, 08-10 renewals | each buyer | `Payment received for subscription #<ID>` | `FULL_REN1_PRE`, `FULL_REN2_PRE`, or `NEXT_REN1_PRE`, each with timeout 900 and exact numeric subject |
| 3 | NONE, steps 1-3 and 6-7 | meta work | — | — | latest remains `M0` through step 3 and `M7` through step 7 |

## Evidence to capture
- `SLT-SYN-13-01-cart-full.png`, `-02-cart-next-cycle-note.png`, `-03-next-payment-dates.png`.
- Step 3 and 7 `getConfig` transcripts; exact sub and order ids; both offsets; `M0`/`MA`/`MB`/`M7`, all eight checkout mail ids, all three renewal baselines, and resulting Mailpit ids; `/home/server-manager/slt-evidence/SLT-SYN-13-decoy-bracket.txt`.

## Pass criteria
- [ ] `getConfig()` differs per variation, null for No Sync
- [ ] Full -> `2026-08-06 18:00:00` UTC, mode `full`, $12.00
- [ ] Next Cycle -> `2026-08-09 18:00:00` UTC, mode `next_cycle`, $12.00, note shown
- [ ] The dates diverge, so variation resolution beat the parent decoy; decoy then removed and `getConfig(<PARENT>)` null; both renewals fire in window
- [ ] Both persistent carts empty before and after; all six parent decoy keys removed; bracket closed in ≤90 minutes; meta-only legs sent zero mail
- [ ] Both exact order relationships and complete four-message checkout sets recorded; future renewal gates handed off before D2 sessions close

## Isolation / teardown
- The decoy is written in step 2 and MUST be deleted in step 7 within the declared ≤90-minute bracket; record both dumps and timestamps. No variation meta touched.
- New artifacts: 2 subs, 2 orders — ids to the registry for 99B. Separate customer sessions so the two authenticated users and carts cannot collide.
- Close only `customer-a-SLT-SYN-13` and `customer-b-SLT-SYN-13` after the D2 leg; later renewal reads may reopen the same exact names if browser evidence is needed.
- Handoff: Full renews 08-07 then 08-10, Next Cycle 08-10; the watch must expect that cadence.

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

[[2026-08-05]] Wed 21:05
UNVERIFIED (missed D02 purchase window) on 2026-08-05.

This task depended on the two D02 variation purchases under `SLT-SYN-13`. Live verification on 2026-08-05 found no ArraySubs subscriptions owned by `slt-flex` or `slt-flex3` for parent product `SLT Flex Variable Daily` (`12385`). The D03 suite report and evening automation log explicitly state that `SYN-13` remained unexercised at the overnight boundary and that missing D2 flex fixtures are execution gaps unless a later authored recovery path permits creation. No such recovery path exists here, so this card closes without opening a late decoy bracket or placing replacement variation checkouts.
