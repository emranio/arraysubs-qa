---
id: 61
title: SLT-SYN-04 Prove global sync_to_billing_cycle=true + first_charge_mode=full, and that flex overrides it
status: todo
priority: critical
created: 2026-08-02T03:43:08.374116915+02:00
updated: 2026-08-02T03:43:18.73779038+02:00
tags:
    - renewal-sync
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 2h
depends_on:
    - 11
    - 12
    - 26
    - 21
    - 27
class: standard
---

> **SLT-SYN-04** · group `sync` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · shared-global-setting** — with `SLT-SETUP-05`, `SLT-SETUP-02`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`, `SLT-PROD-15`

- *Problem:* renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.
- *Required fix:* Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

**`unrated` · impossible-timing** — with `SLT-PROD-01`, `SLT-PROD-16`, `SLT-PROD-14`, `SLT-PROD-06`

- *Problem:* SLT-SYN-04's global-sync-ON window is not just a checkout hazard: any renewal that Action Scheduler processes while the switch is ON can pick up sync context and be re-anchored from its checkout anniversary to the site-local midnight boundary. By the time SLT-SYN-04 can realistically run (after SETUP-01/02/PROD-16/SETUP-05/SYN-03 have completed), several day/1 and day/2 subscriptions bought on D0/D1 already have renewals due, and their anniversary times are whatever clock time those checkouts happened. If a checkout was done at 09:30 site on D0, its renewal fires at 09:30 site the next day - inside a morning ON window.
- *Required fix:* Two-part rule. (1) Every SLT purchase on D0, D1 and D2 must be executed AFTER 12:00 site time, so all anniversary renewals land in the afternoon. (2) SLT-SYN-04's ON bracket is fixed at 09:00-11:00 site on D3 and no `wp action-scheduler run` of any kind may be issued during it. Record the exact UTC open/close timestamps of the bracket in the evidence root as SLT-SYN-04-bracket.txt so any anomalous renewal in that interval can be attributed.

**`unrated` · dependency-inversion** — with `SLT-SYN-03`, `SLT-SYN-01`, `SLT-SYN-02`

- *Problem:* Four tasks bind handoffs to task keys that do not exist anywhere in the plan index. SLT-SYN-04 declares 'MANDATORY ordering: this task runs FIRST among the day-0 sync purchase tasks. SLT-SYN-05 through SLT-SYN-08 depend on it'. SLT-SYN-01 declares its positional-meta finding 'binding on SLT-SYN-07'. SLT-SYN-02 says 'the contract SLT-SYN-08 buys against'. SLT-SYN-03 states SLT Sync Excl Probe 'is bought exactly ONCE, by SLT-SYN-09'. SLT-SYN-05..09 are not authored. Consequence: SLT Sync Excl Probe (created and registered by SLT-SYN-03) has no owning purchaser at all and will be created, never exercised, then deleted by SLT-SETUP-99 - a wasted artifact and a wasted creation slot.
- *Required fix:* Either author SLT-SYN-05..09 or re-point the handoffs. Minimum viable repair for this window: delete the SLT Sync Excl Probe half of SLT-SYN-03 (its exclusivity evidence is already produced identically by SLT-PROD-05 steps 7-9), keep only SLT Sync Global Daily, and rewrite SLT-SYN-04's ordering clause to reference the actual successor tasks or to say 'no successor sync purchase task may run until this task's restore is proven'.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · impossible-timing** — with `SLT-SETUP-99`, `SLT-PROD-14`, `SLT-PROD-15`, `SLT-PROD-10`

- *Problem:* SLT-SETUP-99 is scheduled on d10 (2026-08-11) and cancels + permanently deletes every SLT subscription, order, product and user, but the automated renewal watch runs to D12 (2026-08-13) and several subscriptions have renewals due after D10: SLT Flex Daily Two Seg and SLT Flex Daily Next Cycle renew 2026-08-11, the SLT Flex Variable Daily Full/Next Cycle variations renew 2026-08-12, the SLT-SYN-04 globally-synced day/3 subscription renews 2026-08-13, and SLT Box Daily renews 2026-08-11. Any dunning ladder started on D8-D10 also cancels at +3 days, i.e. 2026-08-11..08-13. Deleting on D10 destroys exactly the tail evidence D11 and D12 exist to collect. The task's own precondition notices the clash and then leaves it to the operator.
- *Required fix:* Split SLT-SETUP-99 into two tasks. SLT-SETUP-99A (D10, 2026-08-11): Part 1 settings restore + jq diff, plus cancel ONLY the subscriptions whose evidence is complete (all day/1 workhorses: SLT Daily Core, SLT Signup Fee Daily, SLT Renewal Price Step, SLT Paddle Daily, the plan-ladder rungs, SLT Free Signup Daily, SLT Trial Four Day, SLT Variable Daily tiers) so D11/D12 are not polluted by daily-renewal noise. SLT-SETUP-99B (2026-08-13, after the D12 watch check has been captured): Parts 2-4, cancel the remaining tail cohort and delete all artifacts. Settings restore is safe on D10 because it only affects NEW subscriptions.

**`unrated` · shared-global-setting** — with `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-15`, `SLT-PROD-11`, `SLT-SETUP-99`

- *Problem:* The window-wide time-travel policy tells every task to advance time with `wp action-scheduler run --hooks=<hook> --force`. A bare hook drain is site-wide: it fires EVERY due pending action for that hook, including the 13 pre-existing non-SLT active subscriptions (which the isolation contract forbids touching) and every other SLT test's pending renewal invoice / renewal / hold / cancel / expire action. This is the single largest cross-contamination risk in the plan. Tasks that will necessarily drain: any renewal of SLT Flex Month Segments (next payment 2026-09-01 / 2026-10-01, unreachable naturally), the SLT Flex Week Segments segment-3 cohort (next payment 2026-08-15), the SLT Flex Variable Daily Next Cycle tail, the SLT-PROD-11 auto-downgrade case (requires a hand-set _end_date), and SLT-SETUP-99's wind-down. One broad drain on any of those days would prematurely fire the pending renewals of SLT Daily Core, SLT Retry Daily (destroying the 1-day/3-day grace ladder timing), SLT Fixed Three Cycles (destroying its 2026-08-07 expiry contract) and the Box.
- *Required fix:* Ban bare hook drains for the whole window. Mandatory procedure for every time-travel step: (1) screenshot wp-admin -> Tools -> Scheduled Actions filtered to Pending and record EVERY action due within the next 24h, aborting if any non-SLT action is due; (2) move only the target subscription's _next_payment_date and its paired schedule meta; (3) execute the single action by id from the Scheduled Actions screen (Run action) rather than by hook, or invoke the processor for one subscription id via `wp eval` passing that id explicitly; (4) if a hook drain is truly unavoidable, first cancel/park every other pending action for that hook from the Scheduled Actions UI, run the drain, then restore them, and record before/after _next_payment_date for all 13 pre-existing active subscriptions as proof they did not move. Confine all time-travel to D8 (2026-08-09), the single authorized drain day in the calendar.

**`unrated` · same-account-collision** — with `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-15`, `SLT-SETUP-03`, `SLT-SYN-03`

- *Problem:* multiple_subscriptions.auto_migrate_on_checkout = true is a baseline the plan never changes, yet three tasks require the SAME account (slt-flex) to buy the SAME product three separate times: SLT-PROD-12 demands three purchases of SLT Flex Month Segments (segments 1/2/3), SLT-PROD-13 three purchases of SLT Flex Week Segments, and SLT-PROD-15 three purchases of three variations of one variable parent. With auto-migrate on, the second and third checkouts are liable to MIGRATE the customer's existing subscription for that product rather than create an independent one - which silently destroys the segment-1 subscription that the earlier purchase created, and makes the whole segment matrix unobservable. On top of that, slt-flex is additionally loaded with SLT Sync Global Daily (SLT-SYN-04) and SLT Sync Excl Probe (SLT-SYN-03) by explicit deviation, so one account would end up owning 9+ concurrent subscriptions and the my-account list becomes ambiguous for every later assertion.
- *Required fix:* Extend SLT-SETUP-03's matrix from 7 to 9 accounts: add A9 slt-flex2 / slt-flex2@example.test and A10 slt-flex3 / slt-flex3@example.test, same password and billing address. Bind: segment-1 purchases -> slt-flex, segment-2 purchases -> slt-flex2, segment-3 purchases -> slt-flex3, and the same 1/2/3 split for the SLT Flex Variable Daily variations. No account ever buys the same product twice. Before the first repeat purchase would have happened, run a one-line probe of auto_migrate behaviour and record it in the registry so the split is evidence-backed.

**`unrated` · same-account-collision** — with `SLT-SETUP-05`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-12`

- *Problem:* Ten tasks perform cart previews as `--session guest` and each one ends with 'empty the cart'. agent-browser sessions are keyed by name, so every one of these tasks shares ONE cart. Run on the same day (as authored, all on d0) they interleave: a leftover subscription line from SLT-PROD-04 makes SLT-PROD-09's probe-B multi-subscription refusal fire for the wrong reason; SLT-PROD-10's box add-to-cart explicitly EMPTIES the cart first, silently wiping another task's staged preview; SLT-SETUP-05's gateway accordion reading can be taken against a cart that still holds a flex product, which hides Paddle and produces a false failure of its own pass criterion.
- *Required fix:* Give every task its own browser session name: `--session guest-SLT-PROD-04`, `--session guest-SLT-SETUP-05`, etc. Each cart-touching task must additionally assert the cart is EMPTY as its first action and empty it again as its last action, capturing both in evidence. Close only its own session (`agent-browser close --session <name>`); reserve `agent-browser close --all` for the last task of the day.

**`unrated` · impossible-timing** — with `SLT-SETUP-04`, `SLT-PROD-14`, `SLT-PROD-15`

- *Problem:* SLT-SETUP-04 sets Coupon expiry date = 2026-08-12 on all six SLT coupons and justifies it as 'past the watch window'. It is not: the watch window runs to D12 = 2026-08-13, and renewals fall on 2026-08-11, 2026-08-12 and 2026-08-13 (SLT Flex Daily Two Seg / Next Cycle, the SLT Flex Variable Daily day/3 cohort, and the SLT-SYN-04 globally-synced subscription). SLTPCT20REC is a RECURRING discount whose renewal-order behaviour is exactly what those tail renewals would prove, and a coupon that has expired by then makes the tail assertion untestable or produces a false negative.
- *Required fix:* Set Coupon expiry date = 2026-08-14 (or leave it blank and rely on SLT-SETUP-99B deletion) for all six SLT coupons, and record in the registry that no SLT coupon may expire before the last watch day. Update SLT-SETUP-04 step 4 and its Description string ('delete on 2026-08-11' -> 'delete on 2026-08-13').

**`unrated` · shared-global-setting** — with `SLT-SETUP-02`, `SLT-SETUP-99`, `SLT-PROD-16`

- *Problem:* SLT-SETUP-02 flips five booleans ON for the whole window (allow_early_renew, allow_reactivation, pause_subscription.enabled, pause_subscription.customer_can_pause; plus sync OFF) and declares them frozen. Nothing in the plan republishes that baseline where a my-account or customer-action task will see it, so any later task auditing the my-account subscription screen against the site's shipped defaults will file Renew Early / Reactivate / Pause buttons as unexpected UI. The reverse trap also exists: cancellation.retention_offers_enabled has pause/skip OFF while the pause FEATURE is now ON, so the retention modal legitimately shows no pause offer even though pausing works - easy to misfile as a defect. SLT-PROD-16 already relies on the baseline being ON to assert Paddle's Renew Early button stays hidden.
- *Required fix:* SLT-SETUP-02 must append a 'WINDOW BASELINE (frozen)' table to slt-catalog-registry listing all five booleans with prior value / window value / restoring task, and every customer-facing audit task must quote that table in its preconditions instead of the shipped defaults. Add a pass criterion to SLT-SETUP-02: the registry table exists. SLT-SETUP-99A restores all five and proves it with the empty jq diff.

**`unrated` · duplicate-coverage** — with `SLT-SETUP-01`, `SLT-SETUP-05`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`

- *Problem:* SLT-SETUP-01 builds the classic cart/checkout harness pages (slt-classic-cart, slt-classic-checkout) and binds them on every task whose Scope says 'Checkout: classic' or 'both' - but not a single authored task actually visits them. SLT-SETUP-05 uses /checkout/ (block), SLT-SYN-04's Scope says 'Checkout: block' and it uses /checkout/, and every cart preview (SLT-PROD-02/04/09/12/13/14, SLT-SYN-03) uses /cart/ (block). The 'Checkout: both' scope declarations are therefore unbacked, and two published pages are created and torn down without being exercised.
- *Required fix:* Assign the classic surface explicitly rather than declaratively: route SLT-SYN-04's purchase through /slt-classic-checkout (it is a plain Stripe purchase and is the cleanest classic candidate), route SLT-PROD-04's qty-1/qty-2 signup-fee cart probes through /slt-classic-cart (fee rendering differs between block and classic), and change every remaining 'Checkout: both' to the surface actually used. Never repoint the site's real Cart/Checkout pages - the harness pages are the only permitted classic surface.

**`critical` · evidence-destruction / teardown vs watch window** — with `SLT-SETUP-99`, `SLT-CHK-14`, `SLT-CHK-13`, `SLT-EML-14`, `SLT-SYN-09`, `SLT-SYN-13`

- *Problem:* SLT-SETUP-99 is authored as a single d10 task that cancels AND permanently deletes every SLT subscription, order, product, coupon, page and user. With D10 = 2026-08-12 and the watch running to D12 = 2026-08-14, that deletes exactly the evidence D11 and D12 exist to collect. Events after D10: SUB_W1 + SUB_W (both week flex subs) renew 2026-08-14 00:00 site - the last scheduled events in the whole window and SYN-09's 'second charge full on the boundary' proof; the SLT-SYN-04 globally-synced day/3 subscription renews 08-14; SLT-SYN-13's Full and Next Cycle variations renew 08-13; SLT-CHK-13's Box Daily renews 08-12; SLT-CHK-14's lifetime negative control must be asserted on all 12 watch days including 08-13 and 08-14 (its own isolation note wrongly says '99A/99B'); SLT-EML-14 step 9 mandates a delta sweep on the morning of 08-14 and explicitly states 99B must not run before it, because a cancellation mail would contaminate the silence proof.
- *Required fix:* Split, as audit C06 directs, with the dates shifted +1. SLT-SETUP-99A on D10 (2026-08-12), after that morning's watch read and after SLT-DUN-05's recovery evidence is closed: Part 1 settings restore (five booleans, empty jq diff) plus cancellation of the COMPLETED-EVIDENCE COHORT ONLY - the day/1 workhorses (SLT Daily Core spine and its clones, Signup Fee Daily, Renewal Price Step, Paddle Daily, plan-ladder rungs, Free Signup Daily, Trial Four Day, Variable tiers, all CPN and CHK day/1 subs, IMP-03 concurrency subs, DUN-05's S2). No deletions. SLT-SETUP-99B on 2026-08-15 (Sat), strictly after the D12 watch report and SLT-EML-14's 08-14 delta are written: cancel the TAIL COHORT (both week flex subs, Sync Global Daily, SYN-13's two variation subs, SYN-12's two probes, SYN-14's qty sub, Box Daily, the lifetime controls, the flex month subs) then Parts 2-4 deletion. Correct SLT-CHK-14's and SLT-CHK-13's isolation notes to name 99B only. Publish the two cohort lists to the registry on D9 so the watcher can assert on D11/D12 that every 99A-cancelled subscription shows no renewal after its cancellation timestamp.

---
## Objective
Prove what PLAIN global renewal sync does on its own — with `renewals.sync_to_billing_cycle = true` and `renewals.sync_first_charge_mode = "full"`, a subscription's first renewal is snapped to the site-local calendar boundary instead of the checkout anniversary, and the first charge is the FULL recurring amount regardless of how far into the cycle the customer buys — and then prove that a per-product flexible segment plan OVERRIDES that global mode, so a segment-2 purchase prorates even while the global mode says `full`. This is the only task in the window permitted to turn global sync on, and it restores it in the same task.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (slt-flex)
- Plugins: both

## Preconditions
- SLT-SETUP-02 complete; the window baseline is `renewals.sync_to_billing_cycle = false` and `renewals.sync_first_charge_mode = "full"`. This task temporarily flips ONLY the first of those and restores it before finishing.
- SLT-SETUP-03 complete (account `slt-flex` / `slt-flex@example.test` / `SltQa!2026#Pass`, billing address populated).
- SLT-SETUP-05 complete (gateway capability matrix recorded).
- SLT-SYN-03 complete (`SLT Sync Global Daily`, day/3, $18.00, no flex).
- SLT-PROD-12 complete (`SLT Flex Month Segments`, month/1, $30.00, seg1_end=2 seg2_end=6).
- MANDATORY ordering: this task runs FIRST among the day-0 sync purchase tasks. SLT-SYN-05 through SLT-SYN-08 depend on it precisely so that global sync is proven back OFF before any flex purchase is made.
- Verified date facts (do NOT re-derive): site is UTC+6; site-local midnight 2026-08-01 == `2026-07-31 18:00:00` UTC. For a day/3 purchase on 2026-08-01, global sync yields `cycle_start_date = 2026-07-31 18:00:00` UTC and `next_payment_date = 2026-08-03 18:00:00` UTC (= 2026-08-04 00:00 site).
- Verified gateway fact: turning global sync ON HIDES Paddle from every sync-eligible cart (`arraysubs_is_renewal_sync_supported_gateway('arraysubs_paddle')` is hard-coded false). That is expected here and is re-confirmed as a checkpoint, not a defect.

## Test data
| Item | Value |
|---|---|
| Product | SLT Sync Global Daily (day/3, $18.00, no flex); SLT Flex Month Segments (month/1, $30.00) — probe only, not purchased here |
| Account | slt-flex / slt-flex@example.test / `SltQa!2026#Pass` |
| Coupon | N/A |
| Card | 4242 4242 4242 4242, any future expiry, any CVC, any postcode |
| Amounts | expected charge today $18.00 (FULL, not prorated); expected renewal $18.00 every 3 days on the site-local midnight boundary |

## Steps
1. Record the prior value in this task's Notes and on disk: `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public && wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SYN-04-settings-before.json`. Confirm `renewals.sync_to_billing_cycle` is currently `false` and `renewals.sync_first_charge_mode` is `"full"`.
2. `PREV=$(/usr/local/bin/mailpit-agent latest-id)`; record it.
3. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"` -> `agent-browser --session admin snapshot -i`. In the **Renewal Sync** card switch **Sync Renewals to Next Billing Cycle** ON. Confirm the **First Charge** select reappears reading `Charge the full recurring amount` and do NOT change it. Save; screenshot `SLT-SYN-04-01-global-sync-on.png`.
4. Verify from WP root: `wp option get arraysubs_settings --allow-root | grep -o 'sync_to_billing_cycle";b:[01]'` (expect `b:1`) and `wp eval 'var_dump(arraysubs_is_renewal_sync_enabled(), arraysubs_get_renewal_sync_first_charge_mode());' --allow-root` (expect `true` and `"full"`).
5. PROBE BLOCK — flex overrides the global mode, no purchase required. Run:
   `wp eval '$d=["product_id"=><SLT Flex Month Segments ID>,"period"=>"month","interval"=>1,"trial_length"=>0,"price"=>30.0,"signup_fee"=>0]; foreach(["2026-08-01 03:00:00","2026-08-04 03:00:00","2026-08-08 03:00:00"] as $s){$c=arraysubs_get_renewal_sync_context($d,1,$s,"stripe"); printf("%s applies=%s mode=%-10s seg=%s day=%s unit=%.2f line=%.2f cs=%s np=%s\n",$s,var_export($c["applies"],true),$c["mode"],$c["flex_segment"]??"-",$c["flex_day_in_cycle"]??"-",$c["initial_unit_amount"],$c["initial_line_amount"],$c["cycle_start_date"],$c["next_payment_date"]);}' --allow-root`
   Tee the output to `/home/server-manager/slt-evidence/SLT-SYN-04-flex-override-globalON.txt`.
6. Run the SAME probe against a hypothetical NON-flex month product by passing `"product_id"=>0` (which makes `filterRenewalSyncContext()` bail immediately) and tee to `/home/server-manager/slt-evidence/SLT-SYN-04-plain-global-globalON.txt`. This is the side-by-side that isolates the override.
7. Gateway checkpoint: `agent-browser --session guest open "https://mirror-help.arrayhash.com/checkout/?add-to-cart=<SLT Sync Global Daily ID>"` -> `agent-browser --session guest snapshot -i`. Record which gateways the payment accordion offers. Empty the cart afterwards via `https://mirror-help.arrayhash.com/cart/`.
8. Log in as the customer: `agent-browser --session customer open "https://mirror-help.arrayhash.com/my-account"` -> `snapshot -i` -> sign in as `slt-flex` / `SltQa!2026#Pass`.
9. Snapshot the mail id again immediately before the purchase: `PREBUY=$(/usr/local/bin/mailpit-agent latest-id)`.
10. `agent-browser --session customer open "https://mirror-help.arrayhash.com/checkout/?add-to-cart=<SLT Sync Global Daily ID>"` -> `snapshot -i`. Read and screenshot the order summary BEFORE paying: `SLT-SYN-04-02-checkout-summary-global.png`. Record the exact "total due today" string and any subscription schedule line.
11. Select **Stripe** in the payment accordion, enter card `4242 4242 4242 4242`, and place the order. Re-snapshot the order-received page and record the order number.
12. `/usr/local/bin/mailpit-agent wait-new "$PREBUY" 60 "is active"` and then `/usr/local/bin/mailpit-agent list 15` — record every message id produced by the purchase.
13. Find the subscription: `wp post list --post_type=arraysubs_data --format=csv --fields=ID,post_title,post_status --allow-root | tail -20` and identify the new subscription for `slt-flex`. Record `SUBID_GLOBAL`.
14. Dump its sync meta: `wp post meta list <SUBID_GLOBAL> --keys=_renewal_sync_enabled,_renewal_sync_first_charge_mode,_renewal_sync_cycle_start_date,_renewal_sync_first_full_renewal_date,_renewal_sync_initial_recurring_amount,_next_payment_date,_recurring_amount,_billing_period,_billing_interval,_payment_gateway --allow-root`.
15. Dump the ORDER ITEM mirror of the same data: open the order at `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=<ORDER ID>` and screenshot the line-item meta rows: `SLT-SYN-04-03-order-item-sync-meta.png`.
16. RESTORE THE BASELINE NOW, before anything else: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general"`, switch **Sync Renewals to Next Billing Cycle** back OFF, Save, and screenshot `SLT-SYN-04-04-global-sync-restored-off.png`.
17. Prove the restore: `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SYN-04-settings-after.json` then `diff <(jq -S . /home/server-manager/slt-evidence/SLT-SYN-04-settings-before.json) <(jq -S . /home/server-manager/slt-evidence/SLT-SYN-04-settings-after.json)`.
18. Re-confirm the gateway list is back to both after the restore: repeat step 7 with a guest cart and record the accordion contents, then empty the cart.
19. `agent-browser close --all`.

## Expected results
1. With global sync ON, `arraysubs_is_renewal_sync_enabled()` is `true` and `arraysubs_get_renewal_sync_first_charge_mode()` is the string `full`.
2. Step 6 (plain global, `product_id = 0`, month/1 $30.00): ALL THREE start dates produce `applies=true`, `mode=full`, `initial_unit_amount=30.00`, `cycle_start_date=2026-07-31 18:00:00`, `next_payment_date=2026-08-31 18:00:00`. Global sync alone charges the full amount whether you buy on day 1, day 4 or day 8, and always lands the first renewal on 2026-09-01 00:00 site.
3. Step 5 (same dates, but `product_id` = the flex month product): the flexible plan OVERRIDES the global mode —
   - `2026-08-01 03:00:00` -> `flex_day_in_cycle=1`, `flex_segment=1`, `mode=full`, unit `$30.00`, `next_payment_date=2026-08-31 18:00:00`.
   - `2026-08-04 03:00:00` -> `flex_day_in_cycle=4`, `flex_segment=2`, `mode=prorate`, unit `$26.13`, `next_payment_date=2026-08-31 18:00:00`.
   - `2026-08-08 03:00:00` -> `flex_day_in_cycle=8`, `flex_segment=3`, `mode=next_cycle`, unit `$30.00`, `cycle_start_date` rewritten to `2026-08-31 18:00:00` and `next_payment_date=2026-09-30 18:00:00`.
   The day-4 row is the headline: the global mode says `full` and the product still prorates to `$26.13`.
4. Step 7 gateway checkpoint with global sync ON: the payment accordion offers **Stripe** and does NOT offer **Paddle** — expected by design, recorded as a checkpoint, not filed as a defect.
5. Checkout summary for `SLT Sync Global Daily` shows a total due today of exactly `$18.00` — the FULL recurring amount, not a prorated fraction — because the global first-charge mode is `full`.
6. The parent order totals `$18.00` and reaches status `processing` or `completed`.
7. On `SUBID_GLOBAL`: `_renewal_sync_enabled=yes`, `_renewal_sync_first_charge_mode=full`, `_renewal_sync_cycle_start_date=2026-07-31 18:00:00`, `_renewal_sync_first_full_renewal_date=2026-08-03 18:00:00`, `_renewal_sync_initial_recurring_amount=18.00`, `_next_payment_date=2026-08-03 18:00:00` (= 2026-08-04 00:00 site), `_recurring_amount=18.00`, `_billing_period=day`, `_billing_interval=3`, `_payment_gateway=stripe`, post status `arraysubs-active`.
8. The renewal date is a site-local MIDNIGHT boundary, NOT the checkout anniversary. Concretely: `_next_payment_date` ends in `18:00:00` UTC regardless of what time of day the order was placed. Record the actual checkout timestamp alongside it to make the contrast explicit.
9. The same five `_renewal_sync_*` keys are mirrored onto the order line item with identical values.
10. After the restore, the jq diff between `SLT-SYN-04-settings-before.json` and `-after.json` is EMPTY — `sync_to_billing_cycle` is `false` again, `sync_first_charge_mode` is still the string `full`, and no other key moved.
11. After the restore, step 18's guest checkout for `SLT Sync Global Daily` offers BOTH Stripe and Paddle again (the product is no longer sync-eligible with the global switch off and no per-product plan).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | Subscription activated by the paid parent order (step 11) | slt-flex@example.test | `Your subscription #` … `is active` | `mailpit-agent wait-new "$PREBUY" 60 "is active"` then `mailpit-agent text latest` |
| 2 | admin_new_subscription | Same activation | site admin address | `New subscription #` … `from` | `mailpit-agent list 15` — locate by subject, record the id |
| 3 | WooCommerce order emails (customer "order is now processing"/"completed", admin "New order") | Parent order status change | customer + admin | order number | `mailpit-agent list 15` — count these as expected side effects; do not assert their body content |
| 4 | NONE EXPECTED for renewal_invoice | No renewal has been generated yet in this task | — | — | No message whose subject contains `Invoice for subscription` may appear; confirm with `mailpit-agent list 15` |
| 5 | NONE EXPECTED from the two settings saves (steps 3 and 16) | Settings save | — | — | `mailpit-agent latest-id` immediately before and after each save must be unchanged |

## Evidence to capture
- Screenshots: `SLT-SYN-04-01-global-sync-on.png`, `SLT-SYN-04-02-checkout-summary-global.png`, `SLT-SYN-04-03-order-item-sync-meta.png`, `SLT-SYN-04-04-global-sync-restored-off.png`, plus a guest-checkout gateway screenshot with sync ON and one after the restore.
- `SLT-SYN-04-settings-before.json`, `SLT-SYN-04-settings-after.json`, the jq diff (expected empty).
- `SLT-SYN-04-flex-override-globalON.txt` and `SLT-SYN-04-plain-global-globalON.txt` — the two side-by-side probe dumps.
- `SUBID_GLOBAL`, the parent order ID, the full `wp post meta list` dump, the exact checkout timestamp, the checkout total string.
- Mailpit ids for every message produced; `$PREV` and `$PREBUY`.
- Any console/network error from the block checkout or the Stripe UPE iframe.

## Pass criteria
- [ ] Global sync switched ON with first_charge_mode still "full"
- [ ] Plain-global probe: full $30.00 and 2026-09-01 boundary on all three purchase dates
- [ ] Flex-override probe: day 4 prorates to $26.13 while the global mode is "full"
- [ ] Flex-override probe: day 8 pushes next_payment to 2026-09-30 18:00:00 UTC
- [ ] Paddle hidden while global sync is ON; both gateways offered again after the restore
- [ ] Checkout charged exactly $18.00 (full, not prorated)
- [ ] Subscription carries all five `_renewal_sync_*` metas with the exact expected values
- [ ] `_next_payment_date` = 2026-08-03 18:00:00 UTC (site-local midnight boundary, not the anniversary)
- [ ] Order line item mirrors the same five metas
- [ ] jq settings diff after restore is EMPTY
- [ ] Only the listed emails appeared; no renewal_invoice mail

## Isolation / teardown
- State handoff: `SUBID_GLOBAL` is a LIVE globally-synced day/3 subscription that will renew unattended on 2026-08-04, 2026-08-07 and 2026-08-10 at 2026-08-0X 18:00:00 UTC (site-local midnight) plus its per-subscription renewal spread offset. SLT-SYN-13 and the daily renewal-watch tasks must include it and assert that it stays on the midnight boundary even though the global setting was turned back off — the subscription's own `_renewal_sync_enabled=yes` meta is what governs it from here, not the store setting.
- Compute and record the spread offset for it once: `php -r '$id=SUBID_GLOBAL;$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("offset=%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));'` — every renewal-timing assertion on this subscription uses it.
- Restores: `renewals.sync_to_billing_cycle` is returned to `false` in step 16, inside this task, and the empty jq diff is the proof. No other setting is touched. The cart is emptied. The subscription, its order and the product are left in place and are removed by SLT-SETUP-99.
- Binding on later authors: no other SLT task may turn global sync on. If a later task observes sync behaviour it did not expect, check `_renewal_sync_enabled` on the subscription rather than assuming the global switch moved.

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
