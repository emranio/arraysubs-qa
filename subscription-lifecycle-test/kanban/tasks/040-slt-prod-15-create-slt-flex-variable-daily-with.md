---
id: 40
title: SLT-PROD-15 Create SLT Flex Variable Daily with per-variation flexible-sync configuration
status: todo
priority: medium
created: 2026-08-02T03:43:06.322702922+02:00
updated: 2026-08-02T03:43:16.772941709+02:00
tags:
    - setup
    - products
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 1h
depends_on:
    - 10
    - 11
    - 22
class: standard
---

> **SLT-PROD-15** · group `catalog` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · shared-global-setting** — with `SLT-SYN-04`, `SLT-SETUP-05`, `SLT-SETUP-02`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`

- *Problem:* renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.
- *Required fix:* Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · impossible-timing** — with `SLT-SETUP-99`, `SLT-PROD-14`, `SLT-SYN-04`, `SLT-PROD-10`

- *Problem:* SLT-SETUP-99 is scheduled on d10 (2026-08-11) and cancels + permanently deletes every SLT subscription, order, product and user, but the automated renewal watch runs to D12 (2026-08-13) and several subscriptions have renewals due after D10: SLT Flex Daily Two Seg and SLT Flex Daily Next Cycle renew 2026-08-11, the SLT Flex Variable Daily Full/Next Cycle variations renew 2026-08-12, the SLT-SYN-04 globally-synced day/3 subscription renews 2026-08-13, and SLT Box Daily renews 2026-08-11. Any dunning ladder started on D8-D10 also cancels at +3 days, i.e. 2026-08-11..08-13. Deleting on D10 destroys exactly the tail evidence D11 and D12 exist to collect. The task's own precondition notices the clash and then leaves it to the operator.
- *Required fix:* Split SLT-SETUP-99 into two tasks. SLT-SETUP-99A (D10, 2026-08-11): Part 1 settings restore + jq diff, plus cancel ONLY the subscriptions whose evidence is complete (all day/1 workhorses: SLT Daily Core, SLT Signup Fee Daily, SLT Renewal Price Step, SLT Paddle Daily, the plan-ladder rungs, SLT Free Signup Daily, SLT Trial Four Day, SLT Variable Daily tiers) so D11/D12 are not polluted by daily-renewal noise. SLT-SETUP-99B (2026-08-13, after the D12 watch check has been captured): Parts 2-4, cancel the remaining tail cohort and delete all artifacts. Settings restore is safe on D10 because it only affects NEW subscriptions.

**`unrated` · shared-global-setting** — with `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-11`, `SLT-SETUP-99`, `SLT-SYN-04`

- *Problem:* The window-wide time-travel policy tells every task to advance time with `wp action-scheduler run --hooks=<hook> --force`. A bare hook drain is site-wide: it fires EVERY due pending action for that hook, including the 13 pre-existing non-SLT active subscriptions (which the isolation contract forbids touching) and every other SLT test's pending renewal invoice / renewal / hold / cancel / expire action. This is the single largest cross-contamination risk in the plan. Tasks that will necessarily drain: any renewal of SLT Flex Month Segments (next payment 2026-09-01 / 2026-10-01, unreachable naturally), the SLT Flex Week Segments segment-3 cohort (next payment 2026-08-15), the SLT Flex Variable Daily Next Cycle tail, the SLT-PROD-11 auto-downgrade case (requires a hand-set _end_date), and SLT-SETUP-99's wind-down. One broad drain on any of those days would prematurely fire the pending renewals of SLT Daily Core, SLT Retry Daily (destroying the 1-day/3-day grace ladder timing), SLT Fixed Three Cycles (destroying its 2026-08-07 expiry contract) and the Box.
- *Required fix:* Ban bare hook drains for the whole window. Mandatory procedure for every time-travel step: (1) screenshot wp-admin -> Tools -> Scheduled Actions filtered to Pending and record EVERY action due within the next 24h, aborting if any non-SLT action is due; (2) move only the target subscription's _next_payment_date and its paired schedule meta; (3) execute the single action by id from the Scheduled Actions screen (Run action) rather than by hook, or invoke the processor for one subscription id via `wp eval` passing that id explicitly; (4) if a hook drain is truly unavoidable, first cancel/park every other pending action for that hook from the Scheduled Actions UI, run the drain, then restore them, and record before/after _next_payment_date for all 13 pre-existing active subscriptions as proof they did not move. Confine all time-travel to D8 (2026-08-09), the single authorized drain day in the calendar.

**`unrated` · same-account-collision** — with `SLT-PROD-12`, `SLT-PROD-13`, `SLT-SETUP-03`, `SLT-SYN-03`, `SLT-SYN-04`

- *Problem:* multiple_subscriptions.auto_migrate_on_checkout = true is a baseline the plan never changes, yet three tasks require the SAME account (slt-flex) to buy the SAME product three separate times: SLT-PROD-12 demands three purchases of SLT Flex Month Segments (segments 1/2/3), SLT-PROD-13 three purchases of SLT Flex Week Segments, and SLT-PROD-15 three purchases of three variations of one variable parent. With auto-migrate on, the second and third checkouts are liable to MIGRATE the customer's existing subscription for that product rather than create an independent one - which silently destroys the segment-1 subscription that the earlier purchase created, and makes the whole segment matrix unobservable. On top of that, slt-flex is additionally loaded with SLT Sync Global Daily (SLT-SYN-04) and SLT Sync Excl Probe (SLT-SYN-03) by explicit deviation, so one account would end up owning 9+ concurrent subscriptions and the my-account list becomes ambiguous for every later assertion.
- *Required fix:* Extend SLT-SETUP-03's matrix from 7 to 9 accounts: add A9 slt-flex2 / slt-flex2@example.test and A10 slt-flex3 / slt-flex3@example.test, same password and billing address. Bind: segment-1 purchases -> slt-flex, segment-2 purchases -> slt-flex2, segment-3 purchases -> slt-flex3, and the same 1/2/3 split for the SLT Flex Variable Daily variations. No account ever buys the same product twice. Before the first repeat purchase would have happened, run a one-line probe of auto_migrate behaviour and record it in the registry so the split is evidence-backed.

**`unrated` · same-account-collision** — with `SLT-SYN-02`

- *Problem:* Same shape as the SLT-SYN-01 case at variation level: SLT-SYN-02 toggles segments off on the Full variation, reconfigures the Next Cycle variation to segment-1-only, and ticks/unticks the master checkbox on the No Sync variation, saving each time. SLT-PROD-15's isolation note has all three variations purchased on d0. Any purchase placed while a probe state is in effect resolves filterRenewalSyncContext() against the wrong plan, and SLT-SYN-02's own handoff ('if SLT-SYN-08 observes identical next-payment dates, this task's evidence proves the fault is in product_id resolution') is void if the purchases straddled the probes.
- *Required fix:* Bind SLT-SYN-02 to run on the same day as SLT-PROD-15 (D2), immediately after creation and strictly BEFORE the three variation purchases, which move to D2 after 12:00. Add an explicit gate step at the end of SLT-SYN-02: re-dump the three variations' six metas and post them to the registry as the 'purchase-authorised configuration'; the purchasing task must quote that dump in its evidence.

**`unrated` · impossible-timing** — with `SLT-SETUP-04`, `SLT-PROD-14`, `SLT-SYN-04`

- *Problem:* SLT-SETUP-04 sets Coupon expiry date = 2026-08-12 on all six SLT coupons and justifies it as 'past the watch window'. It is not: the watch window runs to D12 = 2026-08-13, and renewals fall on 2026-08-11, 2026-08-12 and 2026-08-13 (SLT Flex Daily Two Seg / Next Cycle, the SLT Flex Variable Daily day/3 cohort, and the SLT-SYN-04 globally-synced subscription). SLTPCT20REC is a RECURRING discount whose renewal-order behaviour is exactly what those tail renewals would prove, and a coupon that has expired by then makes the tail assertion untestable or produces a false negative.
- *Required fix:* Set Coupon expiry date = 2026-08-14 (or leave it blank and rely on SLT-SETUP-99B deletion) for all six SLT coupons, and record in the registry that no SLT coupon may expire before the last watch day. Update SLT-SETUP-04 step 4 and its Description string ('delete on 2026-08-11' -> 'delete on 2026-08-13').

**`unrated` · duplicate-coverage** — with `SLT-SYN-03`, `SLT-PROD-05`

- *Problem:* SLT-SYN-03 creates two products that are already covered. (a) SLT Sync Global Daily (day/3, non-flex) is functionally identical as a control to the 'No Sync' variation of SLT Flex Variable Daily (day/3, flex unticked) created by SLT-PROD-15 - both exist to show anniversary scheduling on a 3-day cycle. (b) SLT Sync Excl Probe exists only to demonstrate that Different Renewal Price hides the Flexible Renewal Sync section, which SLT-PROD-05 steps 7-9 already capture verbatim with three screenshots, and its declared purchaser SLT-SYN-09 does not exist. Two product-creation slots on the most overloaded day are spent on coverage the catalog already holds.
- *Required fix:* Keep SLT Sync Global Daily - SLT-SYN-04 needs a simple (not variation) product so the five _renewal_sync_* metas read cleanly - but drop SLT Sync Excl Probe entirely and delete its half of SLT-SYN-03 (steps 10-16, screenshots 02/03). Point SLT-SYN-03's exclusivity claim at SLT-PROD-05's evidence ids instead. Conversely, do not spend a checkout on the SLT-PROD-15 'No Sync' variation as a scheduling control; assert it by meta + SegmentPlan::getConfig()===null only, and let SLT Sync Global Daily carry the purchased control.

**`high` · dependency-inversion (product creation after first consumer)** — with `SLT-PROD-04`, `SLT-PROD-05`, `SLT-PROD-08`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-11`

- *Problem:* The corrected calendar in plan-audit places several catalog tasks later than the first new-index task that depends on them. SLT-SETUP-04 (coupons) is D3 but SLT-CPN-01/02 need it on D1 18:00-19:00. SLT-PROD-05 is D3 but SLT-LIFE-05 buys it on D1. SLT-PROD-16 is D1 but SLT-DUN-01 (corrected to D2 13:00) and SLT-CHK-04 (D2) need it, and SLT-MYA-05 needs it on D2 morning. SLT-PROD-09 is D5 but SLT-CPN-04 (D3) and SLT-CHK-12 (D5) depend on it. SLT-PROD-10 and SLT-PROD-11 are D4 but SLT-CHK-13 (D4), SLT-CHK-10 (D5) and SLT-SW-09 (D4, which explicitly says PROD-11 must be done 'before this task starts on D4') need them earlier in the day or before. SLT-PROD-08 is D5 but SLT-CHK-11 buys its variations on D5. SLT-PROD-15 is D2 and SLT-SYN-13 buys its variations on D2 - correct only if SYN-02's audit sits strictly between them.
- *Required fix:* Adopt the rebalanced calendar in this report: SETUP-04 and PROD-05 to D1 morning; PROD-16 to D1 morning (ahead of SETUP-05, which also gains PROD-14 as a dependency per audit C03); PROD-02/03/09/15 and SYN-02 to D2 morning; PROD-04/10/11 to D3 after the SYN-04 bracket closes; PROD-08 to D4 morning. Add an explicit intra-day ordering line to every day's calendar row ('creations and audits before 12:00, purchases after 12:00') and make it a pass criterion that each consuming task quotes the creating task's registry entry.

**`medium` · shared-product-meta / undeclared bracket** — with `SLT-SYN-13`, `SLT-SYN-02`, `SLT-MYA-05`

- *Problem:* SLT-SYN-13 step 2 writes a decoy segment plan onto the SLT Flex Variable Daily PARENT product and deletes it only at step 7, the same day - but between those steps two live checkouts are placed and the window is unbounded in the body. SLT-SYN-02 audits the same product family on the same day (D2). Any other cart or checkout touching that parent inside the decoy window resolves filterRenewalSyncContext() against a plan no task expects, and the decoy's own null-vs-config proof depends on nothing else having read it. Separately SLT-MYA-05 leaves two appended members_access rules and a product-level _arraysubs_features meta live from D2 morning until its step-10 teardown on D7 - a five-day global deviation during which the pre-existing 'Gold members save 15%' rule (which targets pro_member on ALL products) can alter front-end prices for slt-fail.
- *Required fix:* For SYN-13: declare the decoy a bracket - record open/close UTC in slt-evidence/SLT-SYN-13-decoy-bracket.txt, post it to the registry, keep it under 90 minutes, and assert no other SLT task carts or checks out SLT Flex Variable Daily inside it. Add a pass criterion 'decoy removed and getConfig(<PARENT>) is null before the bracket closes'. For MYA-05: shorten the deviation by moving its teardown from D7 to immediately after follow-up B (D5 morning, once the on-hold role removal is captured) and re-adding the rules only if follow-up C needs them; record the bracket in the registry either way, and add an explicit price check on SLT Retry Daily renewals proving the pro_member discount never reached a cron renewal.

---
## Objective
Cover the variation-level flexible-sync configuration path, which is a separate code path from the simple-product one: the pro feature renders through `arraysubs_subscription_variation_fields_before_shipping` and saves through `saveVariationMeta()` with `$_POST[META][$loop]` array indexing. Three variations on one identical day/3 schedule differ ONLY in their segment plan, so any difference in first charge or next-payment date is attributable to the plan alone.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01, SLT-SETUP-02 and SLT-PROD-14 complete (PROD-14 establishes the expected simple-product behaviour this task compares against).
- `filterSupportsRenewalSync()` and `filterRenewalSyncContext()` both key off `subscription_data['product_id']`; for a variation purchase that resolves to the VARIATION id, so the plan must be stored on the variation, not the parent.

## Test data
| Item | Value |
|---|---|
| Product | SLT Flex Variable Daily / slug `slt-flex-variable-daily`, attribute `SLT Sync Mode` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | all variations $12.00, day/3 |

| Variation (SLT Sync Mode) | Price | Period/Interval | Flex sync | Segments active | Boundaries | Expected next payment if bought 2026-08-01 |
|---|---|---|---|---|---|---|
| Full | 12.00 | Day / 3 | ON | 1, 2, 3 | seg1_end 1, seg2_end 2 | 2026-08-04 00:00 site, charge $12.00 |
| Next Cycle | 12.00 | Day / 3 | ON | 3 only | none | 2026-08-07 00:00 site, charge $12.00 |
| No Sync | 12.00 | Day / 3 | OFF | — | — | anniversary: checkout time + 3 days, charge $12.00 |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Flex Variable Daily`. **Description**: `SLT window product. Variation-level flexible renewal sync. Delete on 2026-08-11.`
4. Product type **Variable product**; tick **Virtual**; tick the header checkbox **Subscription [ArraySubs]**.
5. **Attributes** tab: custom attribute Name `SLT Sync Mode`, Values `Full | Next Cycle | No Sync`, tick **Visible on the product page** and **Used for variations**. Save attributes.
6. **Variations** tab: generate the three variations.
7. **Full** variation: **Regular price ($)** `12.00`; ArraySubs block: **Billing Period** `Day`, **Billing Interval** `3`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked; tick **Flexible Renewal Sync to Next Billing Cycle**; leave all three legend toggles ON; set the handles so the legend reads `1` / `2` / `3`.
8. **Next Cycle** variation: same price and schedule; tick flex sync; turn segment 1 and segment 2 toggles OFF so only **Charge full for next billing cycle** remains, legend `1 - 3`.
9. **No Sync** variation: same price and schedule; leave **Flexible Renewal Sync** UNTICKED.
10. Save variations, reload the Variations tab and expand all three to confirm each legend/toggle state survived the AJAX save.
11. Slug `slt-flex-variable-daily`. Publish.
12. For each variation id: `wp post meta list <VARIATION_ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_regular_price --allow-root`.
13. As `--session guest`, open the product page, select each `SLT Sync Mode` in turn, add to cart, read the subscription meta rows (only **Next Cycle** may show the bonus-access note), empty the cart between selections.
14. Append the parent ID and all three variation IDs to the registry.

## Expected results
1. Parent published as `variable`, virtual, `_is_subscription=yes` propagated to all three variations, slug `slt-flex-variable-daily`.
2. **Full**: `_arraysubs_flex_sync_enabled=yes`, all three `_active=yes`, `seg1_end=1`, `seg2_end=2`.
3. **Next Cycle**: `_arraysubs_flex_sync_enabled=yes`, `seg1_active=no`, `seg2_active=no`, `seg3_active=yes`.
4. **No Sync**: `_arraysubs_flex_sync_enabled` ABSENT (the saver deletes it when the box is unticked, while preserving any previously submitted boundary values).
5. All three legends survive the variation AJAX save and a full page reload.
6. In the cart, only **Next Cycle** shows the "Today's payment covers the full billing cycle starting 4 August, 2026" note; **Full** and **No Sync** do not.
7. Buying contract: **Full** renews 2026-08-04 00:00 site; **Next Cycle** renews 2026-08-07 00:00 site; **No Sync** renews at checkout time + 3 days (an anniversary time, not midnight) — the three-way divergence is the deliverable.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Publish and cart previews | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-15-01-variation-full-legend.png`, `SLT-PROD-15-02-variation-next-cycle-legend.png`, `SLT-PROD-15-03-variation-no-sync-unticked.png`, `SLT-PROD-15-04-cart-next-cycle-note.png`.
- Parent + three variation IDs; three meta dumps; any AJAX error from **Save changes** on the Variations tab.

## Pass criteria
- [ ] Three variations saved with distinct segment plans on an identical day/3 schedule
- [ ] No Sync variation has the flex meta deleted, not set to 'no'
- [ ] Legends survive AJAX save and reload
- [ ] Only Next Cycle shows the bonus-access cart note
- [ ] Divergent next-payment contract recorded in the registry
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy as `slt-flex`, one variation at a time on D0 (baseline forbids multiple subscriptions per cart). If the Full and Next Cycle variations produce identical next-payment dates, the variation-level plan is not being read — file that as a defect against `filterRenewalSyncContext()` resolving `product_id` to the parent instead of the variation.
- Restores: cart emptied. Parent and variations deleted by SLT-SETUP-99.

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
