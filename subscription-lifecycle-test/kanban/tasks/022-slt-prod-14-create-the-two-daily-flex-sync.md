---
id: 22
title: SLT-PROD-14 Create the two daily flex-sync partition products (2-active and 1-active)
status: todo
priority: high
created: 2026-08-02T03:43:04.857701037+02:00
updated: 2026-08-02T03:43:15.083659675+02:00
tags:
    - setup
    - products
    - day-01
    - has-conflicts
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

### ⚠ Conflict resolutions that apply to this task

**`unrated` · shared-global-setting** — with `SLT-SYN-04`, `SLT-SETUP-05`, `SLT-SETUP-02`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-15`

- *Problem:* renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.
- *Required fix:* Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

**`unrated` · impossible-timing** — with `SLT-SYN-04`, `SLT-PROD-01`, `SLT-PROD-16`, `SLT-PROD-06`

- *Problem:* SLT-SYN-04's global-sync-ON window is not just a checkout hazard: any renewal that Action Scheduler processes while the switch is ON can pick up sync context and be re-anchored from its checkout anniversary to the site-local midnight boundary. By the time SLT-SYN-04 can realistically run (after SETUP-01/02/PROD-16/SETUP-05/SYN-03 have completed), several day/1 and day/2 subscriptions bought on D0/D1 already have renewals due, and their anniversary times are whatever clock time those checkouts happened. If a checkout was done at 09:30 site on D0, its renewal fires at 09:30 site the next day - inside a morning ON window.
- *Required fix:* Two-part rule. (1) Every SLT purchase on D0, D1 and D2 must be executed AFTER 12:00 site time, so all anniversary renewals land in the afternoon. (2) SLT-SYN-04's ON bracket is fixed at 09:00-11:00 site on D3 and no `wp action-scheduler run` of any kind may be issued during it. Record the exact UTC open/close timestamps of the bracket in the evidence root as SLT-SYN-04-bracket.txt so any anomalous renewal in that interval can be attributed.

**`unrated` · dependency-inversion** — with `SLT-SETUP-05`

- *Problem:* SLT-SETUP-05 declares deps SLT-SETUP-02,SLT-PROD-16 but its step 7, expected result 4 and pass criterion 'Paddle hidden for SLT Flex Daily Next Cycle' all require the product SLT Flex Daily Next Cycle, which is created only by SLT-PROD-14. Run as authored (both on d0, no ordering edge) SLT-SETUP-05 can start before that product exists and its third gateway probe is unrunnable.
- *Required fix:* Add SLT-PROD-14 to SLT-SETUP-05's dependency list (deps become SLT-SETUP-02, SLT-PROD-16, SLT-PROD-14) and schedule both on D1 with SLT-PROD-14 strictly before SLT-SETUP-05.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · impossible-timing** — with `SLT-SETUP-99`, `SLT-SYN-04`, `SLT-PROD-15`, `SLT-PROD-10`

- *Problem:* SLT-SETUP-99 is scheduled on d10 (2026-08-11) and cancels + permanently deletes every SLT subscription, order, product and user, but the automated renewal watch runs to D12 (2026-08-13) and several subscriptions have renewals due after D10: SLT Flex Daily Two Seg and SLT Flex Daily Next Cycle renew 2026-08-11, the SLT Flex Variable Daily Full/Next Cycle variations renew 2026-08-12, the SLT-SYN-04 globally-synced day/3 subscription renews 2026-08-13, and SLT Box Daily renews 2026-08-11. Any dunning ladder started on D8-D10 also cancels at +3 days, i.e. 2026-08-11..08-13. Deleting on D10 destroys exactly the tail evidence D11 and D12 exist to collect. The task's own precondition notices the clash and then leaves it to the operator.
- *Required fix:* Split SLT-SETUP-99 into two tasks. SLT-SETUP-99A (D10, 2026-08-11): Part 1 settings restore + jq diff, plus cancel ONLY the subscriptions whose evidence is complete (all day/1 workhorses: SLT Daily Core, SLT Signup Fee Daily, SLT Renewal Price Step, SLT Paddle Daily, the plan-ladder rungs, SLT Free Signup Daily, SLT Trial Four Day, SLT Variable Daily tiers) so D11/D12 are not polluted by daily-renewal noise. SLT-SETUP-99B (2026-08-13, after the D12 watch check has been captured): Parts 2-4, cancel the remaining tail cohort and delete all artifacts. Settings restore is safe on D10 because it only affects NEW subscriptions.

**`unrated` · same-account-collision** — with `SLT-SETUP-05`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-12`

- *Problem:* Ten tasks perform cart previews as `--session guest` and each one ends with 'empty the cart'. agent-browser sessions are keyed by name, so every one of these tasks shares ONE cart. Run on the same day (as authored, all on d0) they interleave: a leftover subscription line from SLT-PROD-04 makes SLT-PROD-09's probe-B multi-subscription refusal fire for the wrong reason; SLT-PROD-10's box add-to-cart explicitly EMPTIES the cart first, silently wiping another task's staged preview; SLT-SETUP-05's gateway accordion reading can be taken against a cart that still holds a flex product, which hides Paddle and produces a false failure of its own pass criterion.
- *Required fix:* Give every task its own browser session name: `--session guest-SLT-PROD-04`, `--session guest-SLT-SETUP-05`, etc. Each cart-touching task must additionally assert the cart is EMPTY as its first action and empty it again as its last action, capturing both in evidence. Close only its own session (`agent-browser close --session <name>`); reserve `agent-browser close --all` for the last task of the day.

**`unrated` · same-account-collision** — with `SLT-SYN-01`, `SLT-PROD-12`, `SLT-PROD-13`

- *Problem:* SLT-SYN-01 performs destructive meta surgery on the four live flex products: steps 7-9 write inverted/out-of-range/collapsing boundary pairs to SLT Flex Month Segments and save; step 12 sets all three _active metas to 'no'; step 13 writes seg1_active=0; step 17 unticks and re-ticks the master checkbox on SLT Flex Week Segments. As authored these products are purchased on d0 by SLT-PROD-12/13/14's own isolation notes, so the surgery lands on products that already carry live subscriptions. SegmentPlan config is read from the product for checkout AND for the renewal sync context, so a subscription created between the probe and the restore, or a renewal computed inside the all-'no' window, resolves against a plan that no task expects. The empty before/after diff proves only that the END state matches - not that nothing was observed mid-probe.
- *Required fix:* Enforce audit-before-purchase: SLT-SYN-01 runs on D1 immediately after SLT-PROD-12 and SLT-PROD-14 are created and BEFORE any flex purchase is placed (all flex purchases move to D1 after 12:00). Move the SLT Flex Week Segments segment-1 purchase from D0 to D1 afternoon - it stays in segment 1 (days 1-2 of the week cycle) with the same $14.00 charge and the same 2026-08-08 renewal, so nothing in the contract changes. Additionally, no flex product may be purchased on any later day without first re-reading its six flex metas and attaching them to the purchase evidence.

**`unrated` · impossible-timing** — with `SLT-SETUP-04`, `SLT-PROD-15`, `SLT-SYN-04`

- *Problem:* SLT-SETUP-04 sets Coupon expiry date = 2026-08-12 on all six SLT coupons and justifies it as 'past the watch window'. It is not: the watch window runs to D12 = 2026-08-13, and renewals fall on 2026-08-11, 2026-08-12 and 2026-08-13 (SLT Flex Daily Two Seg / Next Cycle, the SLT Flex Variable Daily day/3 cohort, and the SLT-SYN-04 globally-synced subscription). SLTPCT20REC is a RECURRING discount whose renewal-order behaviour is exactly what those tail renewals would prove, and a coupon that has expired by then makes the tail assertion untestable or produces a false negative.
- *Required fix:* Set Coupon expiry date = 2026-08-14 (or leave it blank and rely on SLT-SETUP-99B deletion) for all six SLT coupons, and record in the registry that no SLT coupon may expire before the last watch day. Update SLT-SETUP-04 step 4 and its Description string ('delete on 2026-08-11' -> 'delete on 2026-08-13').

**`unrated` · duplicate-coverage** — with `SLT-SETUP-05`

- *Problem:* The assertion 'Paddle is hidden from checkout for SLT Flex Daily Next Cycle, Stripe is offered' is executed twice: SLT-SETUP-05 step 7 / expected result 4 / pass criterion 3, and SLT-PROD-14 step 10 / expected result 7 / pass criterion 5. Both drive a guest cart, both screenshot the accordion (SLT-SETUP-05-04-checkout-flex-nextcycle-gateways.png and SLT-PROD-14-05-paddle-absent.png). It is the same code path, the same product, the same day, and it also doubles the guest-cart collision surface described above.
- *Required fix:* Keep the probe in SLT-SETUP-05, which owns the gateway capability matrix. Reduce SLT-PROD-14 step 10 to a cart-note check only (the 'covers the full billing cycle starting 4 August, 2026' bonus-access string) and replace its gateway pass criterion with 'gateway gating verified by SLT-SETUP-05; cite that task's evidence id'. Saves one full guest-checkout cycle on the busiest day.

**`critical` · dependency-inversion / date contradiction** — with `SLT-SYN-08`, `SLT-SYN-01`, `SLT-EML-01`, `SLT-SYN-09`

- *Problem:* SLT-SYN-08 is tagged d0 and buys SLT Flex Daily Two Seg + SLT Flex Daily Next Cycle, but SLT-PROD-14 creates those products on D1 in the corrected calendar and audit C10 forbids purchasing a flex product before SLT-SYN-01's destructive meta surgery has run and been restored. Worse, SYN-08's stated dates encode a D0 purchase (cycle_start 2026-08-01 18:00 UTC, Two Seg next payment 08-04 18:00 UTC) while SLT-EML-01 - which owns the only reachable renewal_reminder in the window - encodes a D1 purchase (SUB_2SEG due 2026-08-06 00:00 site, SUB_NC due 2026-08-09 00:00 site, reminder fires 08-06 00:00-06:00 = watch D4). Both cannot be true and neither product can be bought twice by the same account.
- *Required fix:* SLT-SYN-08 moves to D1 (2026-08-03), purchases after 12:00, strictly after SLT-PROD-14 and after SLT-SYN-01B's restore is proven. That makes EML-01's numbers correct as written (SUB_2SEG due 08-06 00:00 site, SUB_NC due 08-09 00:00 site, reminder 08-06 00:00-06:00 site, watch D4) and SYN-08's own Test data must be recomputed to cycle_start 2026-08-02 18:00 UTC, Two Seg next payment 2026-08-05 18:00 UTC, Next Cycle cycle_start rewritten to 2026-08-05 18:00 UTC and next payment 2026-08-08 18:00 UTC. Knock-on: SLT-SYN-09's SUB_A row is now wrong (it assumes #2 at 08-04 18:00 and #3 at 08-07 18:00). Move SLT-SYN-09 from D6 to D7 (2026-08-09 morning) where the week pair's 08-08 00:00 renewals AND SUB_A's #2 at 08-09 00:00 are both already visible; hand SUB_A's #3 (08-12 00:00) to watch D10 as a grid assertion.

**`critical` · impossible-timing / audit-before-purchase vs segment window** — with `SLT-SYN-05`, `SLT-SYN-01`, `SLT-PROD-13`, `SLT-PROD-12`

- *Problem:* The +1 date shift (audit C19) breaks audit C10's fix. start_of_week=6, so the week cycle is Sat 2026-08-01 -> Sat 2026-08-08 and boundaries [2,5] give seg1 = days 1-2, seg2 = days 3-5, seg3 = days 6-7. SLT-SYN-05 needs day-in-cycle 1 or 2, i.e. 08-01 or 08-02 only. D0 is now 08-02, so SYN-05 is HARD-PINNED to D0. But C10's fix moved the week seg-1 purchase to D1 ('it stays in segment 1') - which was true when D0=08-01 and D1=08-02, and is false now: D1=08-03 is day 3 = segment 2 = prorate, which would charge $6.00 instead of $14.00 and destroy SYN-05, SYN-06's 'identical boundary' proof and SYN-09's 'second charge full' headline. Meanwhile C10 still requires SLT-SYN-01's meta surgery on the week product to precede that purchase, and SYN-01 is a D1 task.
- *Required fix:* Split SLT-SYN-01 into two passes. SLT-SYN-01A runs D0 morning against SLT Flex Week Segments only (created by SLT-PROD-13, also D0), completes its restore and posts the 'purchase-authorised configuration' dump to the registry before SLT-SYN-05 buys after 12:00 the same day. SLT-SYN-01B runs D1 morning against SLT Flex Month Segments (PROD-12) and the two daily flex products (PROD-14), before SLT-SYN-08's D1 afternoon purchases and before SLT-SYN-06's D2 month purchase. Neither pass may touch a product that already carries a live subscription; add an explicit gate step to 01A/01B re-dumping the six _arraysubs_flex_sync_* metas as the purchase authorisation.

---
## Objective
Cover the two partition shapes the calendar products cannot reach and get segment-3 `next_cycle` behaviour onto a real, unattended daily schedule. Both products use `day` with interval 3 — the smallest cycle at or above `SegmentPlan::MIN_CYCLE_DAYS = 3` that still renews twice inside the window. A crucial code-verified consequence: for the `day` period `cycle_start` is the purchase day itself, so day-in-cycle is ALWAYS 1, which means the FIRST ACTIVE segment always wins and segment selection is controlled purely by which toggles are off.

## Scope
- Gateway: Stripe test (Paddle hidden on sync-eligible carts — this pair is the gateway-gating negative)
- Checkout: both
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 and SLT-SETUP-02 complete. Global sync is OFF, so both products sync only because of the per-product pro override — they are the proof of that override.
- Verified live: purchase at `2026-08-01 09:00:00` site with day/3 gives cycle_start `2026-07-31 18:00:00` UTC and next_payment `2026-08-03 18:00:00` UTC (= 2026-08-04 00:00 site).

## Test data
| Item | Value |
|---|---|
| Product A | SLT Flex Daily Two Seg / slug `slt-flex-daily-two-seg`, $9.00, day/3, segments 2+3 active (segment 1 OFF), boundary seg1_end = 1 |
| Product B | SLT Flex Daily Next Cycle / slug `slt-flex-daily-next-cycle`, $9.00, day/3, ONLY segment 3 active |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | A: $9.00 today, renew 2026-08-04 00:00 site. B: $9.00 today, renew 2026-08-07 00:00 site |

## Steps
1. Capture `mailpit-agent latest-id`.
2. Create Product A: new product, title `SLT Flex Daily Two Seg`, description `SLT window product. Day/3, 2-active segment partition. Delete on 2026-08-11.`, **Simple product**, **Virtual**, tick **Subscription [ArraySubs]**, **Regular price ($)** `9.00`.
3. **Subscription [ArraySubs]** tab for A: **Billing Period** `Day`, **Billing Interval** `3`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked.
4. Tick **Flexible Renewal Sync to Next Billing Cycle** (slider `data-cycle-days="3"`). Turn the **Full amount** (segment 1) legend toggle OFF; leave **Prorate amount** and **Charge full for next billing cycle** ON. Drag the single remaining handle so the legend reads segment 2 = `1`, segment 3 = `2 - 3`. Screenshot. Attempt to also turn segment 2 off and confirm the UI refuses with "At least one segment must stay active." then restore it.
5. Slug `slt-flex-daily-two-seg`. Publish. Reload and confirm the 2-row legend redraws.
6. Create Product B: title `SLT Flex Daily Next Cycle`, description `SLT window product. Day/3, single-active segment 3. Delete on 2026-08-11.`, same type/virtual/subscription flags, **Regular price ($)** `9.00`, **Billing Period** `Day`, **Billing Interval** `3`, length 0, trial 0, no fee.
7. Tick **Flexible Renewal Sync**; turn BOTH **Full amount** and **Prorate amount** toggles OFF, leaving only **Charge full for next billing cycle** ON. The legend must collapse to a single row covering `1 - 3` with no boundary handle. Screenshot.
8. Slug `slt-flex-daily-next-cycle`. Publish. Reload and confirm.
9. Verify metas for both: `wp post meta list <ID> --keys=_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_regular_price --allow-root`.
10. As `--session guest`, add Product B to the cart and confirm the checkout gateway list omits Paddle (the gating negative) and that the cart shows the "Today's payment covers the full billing cycle starting 4 August, 2026" note. Empty the cart. Repeat the gateway check with Product A.
11. Append both IDs to the registry.

## Expected results
1. Both products published, simple + virtual + subscription, `_subscription_period=day`, `_subscription_interval=3`, `_regular_price=9.00`.
2. Product A: `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_active=no`, `seg2_active=yes`, `seg3_active=yes`, `_arraysubs_flex_sync_seg1_end=1`. Legend is two rows: `1` / `2 - 3`.
3. Product B: `seg1_active=no`, `seg2_active=no`, `seg3_active=yes`; the legend is one row `1 - 3` and no boundary is used (`getConfig()` returns an empty boundaries array for a 1-active plan).
4. The UI refuses to leave zero segments active with the exact message "At least one segment must stay active."
5. Purchase contract for A (bought 2026-08-01): day-in-cycle 1 -> first active segment is 2 -> mode `prorate`. Because `start <= cycle_start` for a day period, `arraysubs_calculate_renewal_sync_prorated_amount()` returns ratio 1.0, so the charge is the FULL $9.00. Next payment 2026-08-04 00:00 site, then 2026-08-07, then 2026-08-10 — all inside the window. Prorate mode being indistinguishable from full on a `day` period is expected, not a bug; genuine proration lives on SLT-PROD-13.
6. Purchase contract for B (bought 2026-08-01): day-in-cycle 1 -> segment 3 -> mode `next_cycle`; charge the full $9.00 today, `flex_covered_cycle_start` = 2026-08-04 00:00 site, and the next payment is PUSHED one whole cycle to **2026-08-07 00:00 site** — a visibly different date from A, which is the observable proof of segment-3 behaviour.
7. Paddle is absent from the payment options for both products; Stripe is present.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Two publishes and the cart previews | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-14-01-two-seg-legend.png`, `SLT-PROD-14-02-last-active-refusal.png`, `SLT-PROD-14-03-next-cycle-single-legend.png`, `SLT-PROD-14-04-cart-next-cycle-note.png`, `SLT-PROD-14-05-paddle-absent.png`.
- Both product IDs; both meta dumps; slider console errors.

## Pass criteria
- [ ] Product A saved with a 2-active partition (seg1 off) and legend 1 / 2-3
- [ ] Product B saved with a 1-active partition (segment 3 only) and legend 1-3
- [ ] Last-active-segment refusal captured verbatim
- [ ] Cart on B shows the next-cycle bonus-access note naming 4 August, 2026
- [ ] Paddle hidden, Stripe offered, for both
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy both on D0 as `slt-flex`, one at a time (baseline `allow_multiple_in_cart=false` forbids putting both in one cart). The pair's whole value is the diverging next-payment dates: A renews 2026-08-04 / 08-07 / 08-10, B renews 2026-08-07 / 08-10 — both unattended, both inside the watch window.
- Restores: cart emptied. Both deleted by SLT-SETUP-99.

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
