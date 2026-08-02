---
id: 8
title: SLT-PROD-13 Create SLT Flex Week Segments, the single week-interval flexible-sync product
status: todo
priority: high
created: 2026-08-02T03:43:03.54302273+02:00
updated: 2026-08-02T03:43:13.539145811+02:00
tags:
    - setup
    - products
    - day-00
    - has-conflicts
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

### ⚠ Conflict resolutions that apply to this task

**`unrated` · shared-global-setting** — with `SLT-SYN-04`, `SLT-SETUP-05`, `SLT-SETUP-02`, `SLT-PROD-12`, `SLT-PROD-14`, `SLT-PROD-15`

- *Problem:* renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.
- *Required fix:* Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · shared-global-setting** — with `SLT-PROD-12`, `SLT-PROD-15`, `SLT-PROD-11`, `SLT-SETUP-99`, `SLT-SYN-04`

- *Problem:* The window-wide time-travel policy tells every task to advance time with `wp action-scheduler run --hooks=<hook> --force`. A bare hook drain is site-wide: it fires EVERY due pending action for that hook, including the 13 pre-existing non-SLT active subscriptions (which the isolation contract forbids touching) and every other SLT test's pending renewal invoice / renewal / hold / cancel / expire action. This is the single largest cross-contamination risk in the plan. Tasks that will necessarily drain: any renewal of SLT Flex Month Segments (next payment 2026-09-01 / 2026-10-01, unreachable naturally), the SLT Flex Week Segments segment-3 cohort (next payment 2026-08-15), the SLT Flex Variable Daily Next Cycle tail, the SLT-PROD-11 auto-downgrade case (requires a hand-set _end_date), and SLT-SETUP-99's wind-down. One broad drain on any of those days would prematurely fire the pending renewals of SLT Daily Core, SLT Retry Daily (destroying the 1-day/3-day grace ladder timing), SLT Fixed Three Cycles (destroying its 2026-08-07 expiry contract) and the Box.
- *Required fix:* Ban bare hook drains for the whole window. Mandatory procedure for every time-travel step: (1) screenshot wp-admin -> Tools -> Scheduled Actions filtered to Pending and record EVERY action due within the next 24h, aborting if any non-SLT action is due; (2) move only the target subscription's _next_payment_date and its paired schedule meta; (3) execute the single action by id from the Scheduled Actions screen (Run action) rather than by hook, or invoke the processor for one subscription id via `wp eval` passing that id explicitly; (4) if a hook drain is truly unavoidable, first cancel/park every other pending action for that hook from the Scheduled Actions UI, run the drain, then restore them, and record before/after _next_payment_date for all 13 pre-existing active subscriptions as proof they did not move. Confine all time-travel to D8 (2026-08-09), the single authorized drain day in the calendar.

**`unrated` · same-account-collision** — with `SLT-PROD-12`, `SLT-PROD-15`, `SLT-SETUP-03`, `SLT-SYN-03`, `SLT-SYN-04`

- *Problem:* multiple_subscriptions.auto_migrate_on_checkout = true is a baseline the plan never changes, yet three tasks require the SAME account (slt-flex) to buy the SAME product three separate times: SLT-PROD-12 demands three purchases of SLT Flex Month Segments (segments 1/2/3), SLT-PROD-13 three purchases of SLT Flex Week Segments, and SLT-PROD-15 three purchases of three variations of one variable parent. With auto-migrate on, the second and third checkouts are liable to MIGRATE the customer's existing subscription for that product rather than create an independent one - which silently destroys the segment-1 subscription that the earlier purchase created, and makes the whole segment matrix unobservable. On top of that, slt-flex is additionally loaded with SLT Sync Global Daily (SLT-SYN-04) and SLT Sync Excl Probe (SLT-SYN-03) by explicit deviation, so one account would end up owning 9+ concurrent subscriptions and the my-account list becomes ambiguous for every later assertion.
- *Required fix:* Extend SLT-SETUP-03's matrix from 7 to 9 accounts: add A9 slt-flex2 / slt-flex2@example.test and A10 slt-flex3 / slt-flex3@example.test, same password and billing address. Bind: segment-1 purchases -> slt-flex, segment-2 purchases -> slt-flex2, segment-3 purchases -> slt-flex3, and the same 1/2/3 split for the SLT Flex Variable Daily variations. No account ever buys the same product twice. Before the first repeat purchase would have happened, run a one-line probe of auto_migrate behaviour and record it in the registry so the split is evidence-backed.

**`unrated` · same-account-collision** — with `SLT-SETUP-05`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-12`

- *Problem:* Ten tasks perform cart previews as `--session guest` and each one ends with 'empty the cart'. agent-browser sessions are keyed by name, so every one of these tasks shares ONE cart. Run on the same day (as authored, all on d0) they interleave: a leftover subscription line from SLT-PROD-04 makes SLT-PROD-09's probe-B multi-subscription refusal fire for the wrong reason; SLT-PROD-10's box add-to-cart explicitly EMPTIES the cart first, silently wiping another task's staged preview; SLT-SETUP-05's gateway accordion reading can be taken against a cart that still holds a flex product, which hides Paddle and produces a false failure of its own pass criterion.
- *Required fix:* Give every task its own browser session name: `--session guest-SLT-PROD-04`, `--session guest-SLT-SETUP-05`, etc. Each cart-touching task must additionally assert the cart is EMPTY as its first action and empty it again as its last action, capturing both in evidence. Close only its own session (`agent-browser close --session <name>`); reserve `agent-browser close --all` for the last task of the day.

**`unrated` · same-account-collision** — with `SLT-SYN-01`, `SLT-PROD-12`, `SLT-PROD-14`

- *Problem:* SLT-SYN-01 performs destructive meta surgery on the four live flex products: steps 7-9 write inverted/out-of-range/collapsing boundary pairs to SLT Flex Month Segments and save; step 12 sets all three _active metas to 'no'; step 13 writes seg1_active=0; step 17 unticks and re-ticks the master checkbox on SLT Flex Week Segments. As authored these products are purchased on d0 by SLT-PROD-12/13/14's own isolation notes, so the surgery lands on products that already carry live subscriptions. SegmentPlan config is read from the product for checkout AND for the renewal sync context, so a subscription created between the probe and the restore, or a renewal computed inside the all-'no' window, resolves against a plan that no task expects. The empty before/after diff proves only that the END state matches - not that nothing was observed mid-probe.
- *Required fix:* Enforce audit-before-purchase: SLT-SYN-01 runs on D1 immediately after SLT-PROD-12 and SLT-PROD-14 are created and BEFORE any flex purchase is placed (all flex purchases move to D1 after 12:00). Move the SLT Flex Week Segments segment-1 purchase from D0 to D1 afternoon - it stays in segment 1 (days 1-2 of the week cycle) with the same $14.00 charge and the same 2026-08-08 renewal, so nothing in the contract changes. Additionally, no flex product may be purchased on any later day without first re-reading its six flex metas and attaching them to the purchase evidence.

**`unrated` · impossible-timing** — with `SLT-PROD-12`

- *Problem:* Neither calendar-interval product can produce a natural renewal for its later cohorts, and the plan does not schedule the time travel. SLT Flex Month Segments: every purchase (segment 1, 2 and 3) lands its next payment on 2026-09-01 or 2026-10-01, i.e. 21 to 61 days past the window - the product yields zero renewal evidence unless a dedicated time-travel task exists before D10. SLT Flex Week Segments segment-3 cohort (purchased 2026-08-06) pushes next payment to 2026-08-15, also outside the window. Only the week segment-1/segment-2 cohorts renew naturally, on 2026-08-08. Combined with the drain hazard above, all four of these forced time-travel events would otherwise be improvised late in the window by whichever task notices first.
- *Required fix:* Create one dedicated, explicitly scheduled task on D8 (2026-08-09) that owns ALL time travel for the window: the month segment-1/2/3 renewals, the week segment-3 renewal, the flex-variable Next Cycle tail, and any hand-set _end_date needed for the SLT-PROD-11 auto-downgrade case. It must use the targeted single-action procedure (never a bare hook drain), record the pending-queue screenshot before and after, and prove the 13 pre-existing active subscriptions' _next_payment_date values are unchanged. D8 is the only authorized drain day in the corrected calendar.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-PROD-06`

- *Problem:* Clock drift against the authored anchor. The plan is written for D0 = 2026-08-01 with hard D0 purchase deadlines (SLT-PROD-06 'MUST be purchased on D0'; SLT-PROD-13 relies on 2026-08-01 being the Saturday start-of-week). The evidence root /home/server-manager/slt-evidence is empty - no task has executed - and the host clock has already rolled past the start of the window, so a literal D0 is partly or wholly gone before SLT-SETUP-01 runs.
- *Required fix:* Two of the three D0 constraints are softer than authored and can absorb the slip without shifting the window: SLT Fixed Three Cycles ends at start + 6 days, so a 2026-08-02 purchase expires 2026-08-08 (still D7, still observable); SLT Flex Week Segments purchased 2026-08-02 is day 2 of the same Saturday-anchored week cycle, so it stays in segment 1 with the same $14.00 charge and the same 2026-08-08 renewal. Keep the D0=2026-08-01 labels in the calendar but treat them as ordinal slots: if execution actually begins on 2026-08-02, shift every date by +1 and re-verify only two things - that SLT Fixed Three Cycles still expires on or before D9, and that the watch tail still reaches the last renewal (which moves to 2026-08-14).

**`critical` · impossible-timing / audit-before-purchase vs segment window** — with `SLT-SYN-05`, `SLT-SYN-01`, `SLT-PROD-12`, `SLT-PROD-14`

- *Problem:* The +1 date shift (audit C19) breaks audit C10's fix. start_of_week=6, so the week cycle is Sat 2026-08-01 -> Sat 2026-08-08 and boundaries [2,5] give seg1 = days 1-2, seg2 = days 3-5, seg3 = days 6-7. SLT-SYN-05 needs day-in-cycle 1 or 2, i.e. 08-01 or 08-02 only. D0 is now 08-02, so SYN-05 is HARD-PINNED to D0. But C10's fix moved the week seg-1 purchase to D1 ('it stays in segment 1') - which was true when D0=08-01 and D1=08-02, and is false now: D1=08-03 is day 3 = segment 2 = prorate, which would charge $6.00 instead of $14.00 and destroy SYN-05, SYN-06's 'identical boundary' proof and SYN-09's 'second charge full' headline. Meanwhile C10 still requires SLT-SYN-01's meta surgery on the week product to precede that purchase, and SYN-01 is a D1 task.
- *Required fix:* Split SLT-SYN-01 into two passes. SLT-SYN-01A runs D0 morning against SLT Flex Week Segments only (created by SLT-PROD-13, also D0), completes its restore and posts the 'purchase-authorised configuration' dump to the registry before SLT-SYN-05 buys after 12:00 the same day. SLT-SYN-01B runs D1 morning against SLT Flex Month Segments (PROD-12) and the two daily flex products (PROD-14), before SLT-SYN-08's D1 afternoon purchases and before SLT-SYN-06's D2 month purchase. Neither pass may touch a product that already carries a live subscription; add an explicit gate step to 01A/01B re-dumping the six _arraysubs_flex_sync_* metas as the purchase authorisation.

---
## Objective
Create the ONE week-interval product. It covers the week-boundary branch of `arraysubs_calculate_renewal_sync_cycle_dates()`, which snaps the cycle start to the store's start-of-week — and this store's `start_of_week` is **6 (Saturday)**, which happens to be D0 itself. That makes the week cycle start on 2026-08-01 and end on 2026-08-08, so unlike the month product this one also produces a REAL renewal inside the window.

## Scope
- Gateway: Stripe test (Paddle hidden on sync-eligible carts)
- Checkout: both
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete (global sync off; this product syncs via the per-product pro override).
- Verified live against the site helper: purchase at `2026-08-01 09:00:00` site -> cycle_start `2026-07-31 18:00:00` UTC (2026-08-01 00:00 site), next_payment `2026-08-07 18:00:00` UTC (**2026-08-08 00:00 site**).
- 2026-08-01 is a Saturday and `start_of_week=6`, so day-in-cycle on D0 is 1.

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
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Flex Week Segments`. **Description**: `SLT window product. Weekly, flexible renewal sync, 3 active segments. Delete on 2026-08-11.`
4. **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `14.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Week`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked.
7. Tick **Flexible Renewal Sync to Next Billing Cycle**; the slider appears with `data-cycle-days="7"`.
8. Leave all three legend toggles ON and drag the handles until the legend reads 1 = `1 - 2`, 2 = `3 - 5`, 3 = `6 - 7`. Screenshot.
9. Slug `slt-flex-week-segments`. Publish. Reload and confirm the ranges redraw identically.
10. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
11. As `--session guest`, add to cart, read the subscription meta rows and the first-charge amount, then empty the cart.
12. Append the ID plus the purchase-date-to-segment table to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-flex-week-segments`, `_subscription_period=week`, `_subscription_interval=1`, `_regular_price=14.00`.
2. `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_end=2`, `_arraysubs_flex_sync_seg2_end=5`, all three `_active` = `yes`.
3. Legend after reload: 1-2 / 3-5 / 6-7.
4. Purchase-date contract (site-local, price $14.00, week cycle 2026-08-01..2026-08-08):
   - Bought 2026-08-01 (Sat, day 1) -> segment 1, mode `full`, charge $14.00, next payment 2026-08-08 00:00 site — a REAL renewal on D7, no time travel needed.
   - Bought 2026-08-04 (Tue, day 4) -> segment 2, mode `prorate`; cycle_days 7, days_until_next 4, remaining = max(1, 4-1) = 3, ratio 3/7, charge `round(14 * 3/7, 2)` = $6.00; next payment 2026-08-08 00:00 site.
   - Bought 2026-08-06 (Thu, day 6) -> segment 3, mode `next_cycle`, charge $14.00 in full covering the cycle starting 2026-08-08, next payment pushed to 2026-08-15 00:00 site (outside the window -> time-travel), and the cart shows the "covers the full billing cycle starting 8 August, 2026" note.
5. Guest cart on D0 shows a $14.00 first charge, no next-cycle note.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and cart preview | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-13-01-subscription-tab.png`, `SLT-PROD-13-02-segment-slider-legend.png`, `SLT-PROD-13-03-after-reload.png`, `SLT-PROD-13-04-cart-day1.png`.
- Product ID; full flex meta dump; slider console errors.

## Pass criteria
- [ ] Published as week/1 at $14.00 with flex sync enabled
- [ ] seg1_end=2, seg2_end=5, all three segments active
- [ ] Legend survives reload as 1-2 / 3-5 / 6-7
- [ ] Day-1 cart charges $14.00 and the D0 purchase is dated to renew 2026-08-08
- [ ] Purchase-date-to-segment table recorded
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy ONLY as `slt-flex`, three separate times — D0 (segment 1, and let the 2026-08-08 renewal fire for real), 2026-08-04 (segment 2, the ONLY place in the whole plan where a genuinely prorated first charge is observable) and 2026-08-06 (segment 3). This is the ONLY week-interval product in the plan.
- Restores: cart emptied. Product deleted by SLT-SETUP-99.

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
