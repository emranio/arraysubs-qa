---
id: 13
title: SLT-SYN-01 Audit simple-product Flexible Renewal Sync UI, validation and meta keys
status: todo
priority: critical
created: 2026-08-02T03:43:04.080557018+02:00
updated: 2026-08-02T03:43:13.950469163+02:00
tags:
    - renewal-sync
    - day-00
    - has-conflicts
due: "2026-08-02"
estimate: 2h
depends_on:
    - 10
    - 11
    - 21
    - 8
    - 22
class: standard
---

> **SLT-SYN-01** · group `sync` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · dependency-inversion** — with `SLT-SYN-04`, `SLT-SYN-03`, `SLT-SYN-02`

- *Problem:* Four tasks bind handoffs to task keys that do not exist anywhere in the plan index. SLT-SYN-04 declares 'MANDATORY ordering: this task runs FIRST among the day-0 sync purchase tasks. SLT-SYN-05 through SLT-SYN-08 depend on it'. SLT-SYN-01 declares its positional-meta finding 'binding on SLT-SYN-07'. SLT-SYN-02 says 'the contract SLT-SYN-08 buys against'. SLT-SYN-03 states SLT Sync Excl Probe 'is bought exactly ONCE, by SLT-SYN-09'. SLT-SYN-05..09 are not authored. Consequence: SLT Sync Excl Probe (created and registered by SLT-SYN-03) has no owning purchaser at all and will be created, never exercised, then deleted by SLT-SETUP-99 - a wasted artifact and a wasted creation slot.
- *Required fix:* Either author SLT-SYN-05..09 or re-point the handoffs. Minimum viable repair for this window: delete the SLT Sync Excl Probe half of SLT-SYN-03 (its exclusivity evidence is already produced identically by SLT-PROD-05 steps 7-9), keep only SLT Sync Global Daily, and rewrite SLT-SYN-04's ordering clause to reference the actual successor tasks or to say 'no successor sync purchase task may run until this task's restore is proven'.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · same-account-collision** — with `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`

- *Problem:* SLT-SYN-01 performs destructive meta surgery on the four live flex products: steps 7-9 write inverted/out-of-range/collapsing boundary pairs to SLT Flex Month Segments and save; step 12 sets all three _active metas to 'no'; step 13 writes seg1_active=0; step 17 unticks and re-ticks the master checkbox on SLT Flex Week Segments. As authored these products are purchased on d0 by SLT-PROD-12/13/14's own isolation notes, so the surgery lands on products that already carry live subscriptions. SegmentPlan config is read from the product for checkout AND for the renewal sync context, so a subscription created between the probe and the restore, or a renewal computed inside the all-'no' window, resolves against a plan that no task expects. The empty before/after diff proves only that the END state matches - not that nothing was observed mid-probe.
- *Required fix:* Enforce audit-before-purchase: SLT-SYN-01 runs on D1 immediately after SLT-PROD-12 and SLT-PROD-14 are created and BEFORE any flex purchase is placed (all flex purchases move to D1 after 12:00). Move the SLT Flex Week Segments segment-1 purchase from D0 to D1 afternoon - it stays in segment 1 (days 1-2 of the week cycle) with the same $14.00 charge and the same 2026-08-08 renewal, so nothing in the contract changes. Additionally, no flex product may be purchased on any later day without first re-reading its six flex metas and attaching them to the purchase evidence.

**`unrated` · same-account-collision** — with `SLT-PROD-06`

- *Problem:* SLT-SYN-01 step 16 targets SLT Fixed Three Cycles - a product SLT-PROD-06 requires to be purchased on D0 and whose subscription must expire exactly on 2026-08-07. The step is also self-contradictory: it says 'ticking it and saving yields getConfig() = null' and then 'Do NOT save the tick'. If an executing agent resolves the contradiction by saving, a live, date-critical subscription's product gains _arraysubs_flex_sync_enabled=yes mid-life, and the pass criterion 'left with _arraysubs_flex_sync_enabled ABSENT' depends on a manual untick that is not itself verified against the live subscription.
- *Required fix:* Do not use a purchased product as the sub-minimum-cycle canvas. Have SLT-SYN-01 create its own throwaway probe product `SLT Flex SubMin Probe` (simple, virtual, subscription, day/2, $7.00, never purchased by anyone), run the tick/save/getConfig()===null probe there, and leave SLT Fixed Three Cycles completely untouched. Rewrite step 16 to remove the contradictory 'do not save' clause. The probe product matches the `SLT ` prefix so SLT-SETUP-99's existing product-search teardown already removes it.

**`critical` · dependency-inversion / date contradiction** — with `SLT-SYN-08`, `SLT-PROD-14`, `SLT-EML-01`, `SLT-SYN-09`

- *Problem:* SLT-SYN-08 is tagged d0 and buys SLT Flex Daily Two Seg + SLT Flex Daily Next Cycle, but SLT-PROD-14 creates those products on D1 in the corrected calendar and audit C10 forbids purchasing a flex product before SLT-SYN-01's destructive meta surgery has run and been restored. Worse, SYN-08's stated dates encode a D0 purchase (cycle_start 2026-08-01 18:00 UTC, Two Seg next payment 08-04 18:00 UTC) while SLT-EML-01 - which owns the only reachable renewal_reminder in the window - encodes a D1 purchase (SUB_2SEG due 2026-08-06 00:00 site, SUB_NC due 2026-08-09 00:00 site, reminder fires 08-06 00:00-06:00 = watch D4). Both cannot be true and neither product can be bought twice by the same account.
- *Required fix:* SLT-SYN-08 moves to D1 (2026-08-03), purchases after 12:00, strictly after SLT-PROD-14 and after SLT-SYN-01B's restore is proven. That makes EML-01's numbers correct as written (SUB_2SEG due 08-06 00:00 site, SUB_NC due 08-09 00:00 site, reminder 08-06 00:00-06:00 site, watch D4) and SYN-08's own Test data must be recomputed to cycle_start 2026-08-02 18:00 UTC, Two Seg next payment 2026-08-05 18:00 UTC, Next Cycle cycle_start rewritten to 2026-08-05 18:00 UTC and next payment 2026-08-08 18:00 UTC. Knock-on: SLT-SYN-09's SUB_A row is now wrong (it assumes #2 at 08-04 18:00 and #3 at 08-07 18:00). Move SLT-SYN-09 from D6 to D7 (2026-08-09 morning) where the week pair's 08-08 00:00 renewals AND SUB_A's #2 at 08-09 00:00 are both already visible; hand SUB_A's #3 (08-12 00:00) to watch D10 as a grid assertion.

**`critical` · impossible-timing / audit-before-purchase vs segment window** — with `SLT-SYN-05`, `SLT-PROD-13`, `SLT-PROD-12`, `SLT-PROD-14`

- *Problem:* The +1 date shift (audit C19) breaks audit C10's fix. start_of_week=6, so the week cycle is Sat 2026-08-01 -> Sat 2026-08-08 and boundaries [2,5] give seg1 = days 1-2, seg2 = days 3-5, seg3 = days 6-7. SLT-SYN-05 needs day-in-cycle 1 or 2, i.e. 08-01 or 08-02 only. D0 is now 08-02, so SYN-05 is HARD-PINNED to D0. But C10's fix moved the week seg-1 purchase to D1 ('it stays in segment 1') - which was true when D0=08-01 and D1=08-02, and is false now: D1=08-03 is day 3 = segment 2 = prorate, which would charge $6.00 instead of $14.00 and destroy SYN-05, SYN-06's 'identical boundary' proof and SYN-09's 'second charge full' headline. Meanwhile C10 still requires SLT-SYN-01's meta surgery on the week product to precede that purchase, and SYN-01 is a D1 task.
- *Required fix:* Split SLT-SYN-01 into two passes. SLT-SYN-01A runs D0 morning against SLT Flex Week Segments only (created by SLT-PROD-13, also D0), completes its restore and posts the 'purchase-authorised configuration' dump to the registry before SLT-SYN-05 buys after 12:00 the same day. SLT-SYN-01B runs D1 morning against SLT Flex Month Segments (PROD-12) and the two daily flex products (PROD-14), before SLT-SYN-08's D1 afternoon purchases and before SLT-SYN-06's D2 month purchase. Neither pass may touch a product that already carries a live subscription; add an explicit gate step to 01A/01B re-dumping the six _arraysubs_flex_sync_* metas as the purchase authorisation.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Prove that the pro Flexible Renewal Sync control block on a SIMPLE subscription product exposes every documented control, that the segment slider/legend, the two POSITIONAL boundary hidden inputs and the three per-segment active toggles round-trip through a save, that the UI refuses to leave zero segments active, that out-of-range boundaries are clamped by `SegmentPlan::sanitizeBoundaries()` / `sanitizeSingleBoundary()` instead of being stored raw, and that exactly six meta keys are written and no others.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: N/A
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 (evidence root, registry page) and SLT-SETUP-02 (window baseline, global sync OFF) complete.
- SLT-PROD-12 (`SLT Flex Month Segments`, month/1 $30.00, seg1_end=2 seg2_end=6, all three active), SLT-PROD-13 (`SLT Flex Week Segments`, week/1 $14.00, seg1_end=2 seg2_end=5), SLT-PROD-14 (`SLT Flex Daily Two Seg` day/3 $9.00 seg1 OFF seg1_end=1; `SLT Flex Daily Next Cycle` day/3 $9.00 seg3 only) all created.
- Code facts (verified, do not re-derive): the panel is rendered by `arraysubspro/src/Features/FlexibleRenewalSync/views/simple-product-fields.php`; the six meta keys are `_arraysubs_flex_sync_enabled`, `_arraysubs_flex_sync_seg1_end`, `_arraysubs_flex_sync_seg2_end`, `_arraysubs_flex_sync_seg1_active`, `_arraysubs_flex_sync_seg2_active`, `_arraysubs_flex_sync_seg3_active`; the saver is `Hooks::persistFlexSyncMeta()` on `woocommerce_process_product_meta` priority 15; `SegmentPlan::MIN_CYCLE_DAYS = 3`; `getDefaultBoundaries(30)` returns `[10, 20]`; anything other than the literal string `no` in an `_active` meta counts as ACTIVE.
- This task MUST leave SLT-PROD-12/13/14 with their catalog-declared boundaries. Every probe below is followed by an explicit restore step.

## Test data
| Item | Value |
|---|---|
| Product | SLT Flex Month Segments (month/1, $30.00), SLT Flex Week Segments (week/1, $14.00), SLT Flex Daily Two Seg (day/3, $9.00), SLT Flex Daily Next Cycle (day/3, $9.00), SLT Fixed Three Cycles (day/2, $7.00 — sub-minimum control) |
| Account | admin / @GuDw(0$K7M9t8ehjqDb4Vwj |
| Coupon | N/A |
| Card | N/A |
| Amounts | none charged — admin-only task |

## Steps
1. Capture the mail baseline: `PREV=$(/usr/local/bin/mailpit-agent latest-id)` and record the value.
2. Record the pre-task meta of all four flex products so the restores are provable. From WP root `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public` run, for each product ID: `wp post meta list <ID> --keys=_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active --format=csv --allow-root` and tee to `/home/server-manager/slt-evidence/SLT-SYN-01-flex-meta-before.csv`.
3. `agent-browser skills get core`, then `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post.php?post=<SLT Flex Month Segments ID>&action=edit"` -> `agent-browser --session admin snapshot -i`.
4. Open the **Subscription [ArraySubs]** tab. Inventory every control in the **Flexible Renewal Sync to Next Billing Cycle** block and record its exact label: the master checkbox **Flexible Renewal Sync to Next Billing Cycle**; the description line "Align renewals to the billing-cycle boundary and pick how the first payment is charged based on the day of the cycle the customer signs up."; the slider container (`data-cycle-days`); the three legend rows with toggles and range text; the three segment labels **Full amount**, **Prorate amount**, **Charge full for next billing cycle**. Screenshot as `SLT-SYN-01-01-month-panel-inventory.png`.
5. Read `data-cycle-days` off the config container and confirm it is `30` for month/1. Re-check on the week product later (must be `7`) and on the day/3 products (must be `3`).
6. Confirm the two boundary inputs are HIDDEN inputs named `_arraysubs_flex_sync_seg1_end` and `_arraysubs_flex_sync_seg2_end` (they are driven by the slider, not typed) and that their current values are `2` and `6`.
7. Boundary-ordering probe A (inverted pair): drag the slider so the legend would read seg1 = `1 - 8`, seg2 = `9 - 12`, then use the browser to set the two hidden inputs directly to an INVERTED pair — `agent-browser --session admin eval "document.querySelector('.arraysubs-flex-sync-seg1-end').value='20';document.querySelector('.arraysubs-flex-sync-seg2-end').value='5';"` — and click **Update**. Re-open and read the stored metas.
8. Boundary-ordering probe B (out of cycle): repeat step 7 with `seg1_end='0'` and `seg2_end='45'` (both outside 1..29 for a 30-day nominal cycle), click **Update**, re-open and read the stored metas.
9. Boundary-ordering probe C (collapsing pair): repeat with `seg1_end='29'` and `seg2_end='29'`, **Update**, re-read.
10. RESTORE the month product: drag/set `seg1_end=2`, `seg2_end=6`, all three toggles ON, click **Update**, and confirm the legend reads `1 - 2` / `3 - 6` / `7 - 30`. Screenshot `SLT-SYN-01-02-month-restored.png`.
11. Last-active-segment refusal: on the month product turn the **Full amount** toggle OFF, then **Prorate amount** OFF, then attempt to turn **Charge full for next billing cycle** OFF. Capture the verbatim inline notice (expected: `At least one segment must stay active.`) and screenshot `SLT-SYN-01-03-last-active-refusal.png`. Do NOT save; navigate away with **Discard**/browser back and re-open to confirm the product is still 3-active.
12. Zero-active server-side fallback probe (defensive path, no UI): on `SLT Flex Month Segments` run `wp post meta update <ID> _arraysubs_flex_sync_seg1_active no --allow-root`, same for seg2 and seg3, then `wp eval 'print_r(\ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan::getConfig(<ID>));' --allow-root`. Record the returned `actives` array. Immediately restore: `wp post meta update <ID> _arraysubs_flex_sync_seg1_active yes --allow-root` (and seg2, seg3).
13. Non-`no` string probe: `wp post meta update <ID> _arraysubs_flex_sync_seg1_active 0 --allow-root`, re-run the `getConfig()` eval, record whether segment 1 is still counted active, then restore to `yes`.
14. Two-active positional check on `SLT Flex Daily Two Seg`: open its edit screen, confirm the legend shows only TWO rows (`1` for **Prorate amount**, `2 - 3` for **Charge full for next billing cycle**) and that `_arraysubs_flex_sync_seg1_end` is `1` — i.e. the meta names the end of the FIRST ACTIVE segment, which is segment 2 here, NOT segment 1. Screenshot `SLT-SYN-01-04-two-active-positional.png`.
15. One-active check on `SLT Flex Daily Next Cycle`: confirm the legend collapses to a single row `1 - 3`, that no boundary handle is draggable, and run `wp eval 'print_r(\ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan::getConfig(<ID>));' --allow-root` to confirm `boundaries` is an EMPTY array. Screenshot `SLT-SYN-01-05-one-active.png`.
16. Sub-minimum-cycle check: open `SLT Fixed Three Cycles` (day/2 = nominal 2 days). Confirm the Flexible Renewal Sync block is present but that ticking it and saving yields `getConfig()` = `null`: `wp eval 'var_dump(\ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan::getConfig(<ID>));' --allow-root`. Do NOT save the tick — untick it and leave the product exactly as SLT-PROD-06 created it (verify `_arraysubs_flex_sync_enabled` is absent afterwards).
17. Disable-retains-boundaries probe: on `SLT Flex Week Segments` untick the master checkbox and **Update**. Read the metas. Then re-tick and **Update**, and confirm the legend redraws `1 - 2` / `3 - 5` / `6 - 7` without re-entering the boundaries. Screenshot `SLT-SYN-01-06-week-reenabled.png`.
18. Final verification dump: repeat step 2's command for all four products into `/home/server-manager/slt-evidence/SLT-SYN-01-flex-meta-after.csv` and `diff` it against the before file.
19. `/usr/local/bin/mailpit-agent latest-id` — must equal `$PREV`.
20. `agent-browser close --all`.

## Expected results
1. The month product's config container carries `data-cycle-days="30"`; the week product `7`; both day/3 products `3`.
2. Probe A (seg1_end=20, seg2_end=5, inverted): the stored pair is re-derived/clamped — `sanitizeBoundaries(20,5,30)` is entered because `seg2_end <= seg1_end`, yielding `_arraysubs_flex_sync_seg1_end=20` and `_arraysubs_flex_sync_seg2_end=21`. Neither value is stored raw as submitted, and `seg2_end > seg1_end` always holds.
3. Probe B (0 and 45): both are out of range, so `getDefaultBoundaries(30)` supplies `_arraysubs_flex_sync_seg1_end=10` and `_arraysubs_flex_sync_seg2_end=20`.
4. Probe C (29 and 29): clamped to `seg1_end=28`, `seg2_end=29` — every one of the three partitions retains at least one day and `seg2_end <= cycle_days - 1 = 29`.
5. After step 10 the month product is back to `seg1_end=2`, `seg2_end=6`, all three `_active` metas `yes`, and the legend reads `1 - 2` / `3 - 6` / `7 - 30`.
6. Turning off the last remaining active segment is refused in the UI with the verbatim string `At least one segment must stay active.` and no save occurs.
7. Step 12: with all three `_active` metas set to `no`, `SegmentPlan::getConfig()` returns `actives => [1, 2, 3]` (defensive fallback) rather than null or an empty array.
8. Step 13: `_arraysubs_flex_sync_seg1_active = 0` still counts as ACTIVE, because only the literal string `no` deactivates a segment. Record this as a documented sharp edge.
9. `SLT Flex Daily Two Seg` shows exactly two legend rows, `1` (Prorate amount) and `2 - 3` (Charge full for next billing cycle), and `_arraysubs_flex_sync_seg1_end = 1` is the end of the first ACTIVE segment (segment 2) — positional, not segment-named.
10. `SLT Flex Daily Next Cycle` shows one legend row `1 - 3` and `getConfig()['boundaries']` is `[]`.
11. `SLT Fixed Three Cycles` (nominal 2 days < MIN_CYCLE_DAYS 3) yields `getConfig() === null` even with the checkbox ticked, and is left with `_arraysubs_flex_sync_enabled` ABSENT.
12. Unticking the master checkbox DELETES `_arraysubs_flex_sync_enabled` but RETAINS `_arraysubs_flex_sync_seg1_end`/`seg2_end`; re-ticking restores the same legend with no re-entry.
13. The step-18 diff against step 2 is empty for all four products — every probe was restored.
14. Exactly the six documented meta keys exist on each flex product; no additional `_arraysubs_flex_*` key appears.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Every product save and every WP-CLI meta write in this task | — | — | `/usr/local/bin/mailpit-agent latest-id` at step 19 must equal `$PREV` captured at step 1; if it moved, `mailpit-agent show latest` and record which save leaked mail |

## Evidence to capture
- Screenshots: `SLT-SYN-01-01-month-panel-inventory.png`, `SLT-SYN-01-02-month-restored.png`, `SLT-SYN-01-03-last-active-refusal.png`, `SLT-SYN-01-04-two-active-positional.png`, `SLT-SYN-01-05-one-active.png`, `SLT-SYN-01-06-week-reenabled.png`.
- `/home/server-manager/slt-evidence/SLT-SYN-01-flex-meta-before.csv` and `-after.csv` plus the diff output (expected empty).
- The three probe meta readbacks (A/B/C) verbatim, the `getConfig()` print_r output for steps 12/13/15/16, the verbatim last-active notice string.
- Console errors from `flexibleRenewalSyncAdmin.js` while dragging the slider.
- `$PREV` value.

## Pass criteria
- [ ] All controls inventoried with exact labels and `data-cycle-days` correct per product
- [ ] Inverted boundary pair is clamped, not stored raw
- [ ] Out-of-range pair falls back to getDefaultBoundaries(30) = 10 / 20
- [ ] Collapsing pair is clamped so every partition keeps >= 1 day
- [ ] Last-active-segment refusal captured verbatim
- [ ] Zero-active meta state resolves to actives [1,2,3]
- [ ] Non-`no` value counts as active (documented)
- [ ] Two-active product proves META_SEG1_END is POSITIONAL
- [ ] One-active product has empty boundaries array
- [ ] Sub-3-day cycle yields getConfig() === null
- [ ] Disable retains boundaries; re-enable restores the legend
- [ ] Before/after meta diff is empty; zero mail

## Isolation / teardown
- State handoff: the confirmed, restored segment plans for SLT-PROD-12/13/14 are the baseline every later SLT-SYN purchase task asserts against. The positional-meta finding from step 14 is binding on SLT-SYN-07.
- Restores: all four flex products returned to their SLT-PROD-declared configuration (proved by the empty diff); `SLT Fixed Three Cycles` left with no flex meta. No global setting touched. Nothing deleted.

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
