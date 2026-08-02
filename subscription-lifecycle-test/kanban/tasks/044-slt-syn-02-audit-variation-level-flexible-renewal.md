---
id: 44
title: SLT-SYN-02 Audit variation-level Flexible Renewal Sync UI, [$loop] meta and per-variation independence
status: todo
priority: critical
created: 2026-08-02T03:43:06.596459182+02:00
updated: 2026-08-02T03:43:17.15507673+02:00
tags:
    - renewal-sync
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 1h 30m
depends_on:
    - 10
    - 11
    - 40
    - 13
class: standard
---

> **SLT-SYN-02** · group `sync` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · dependency-inversion** — with `SLT-SYN-04`, `SLT-SYN-03`, `SLT-SYN-01`

- *Problem:* Four tasks bind handoffs to task keys that do not exist anywhere in the plan index. SLT-SYN-04 declares 'MANDATORY ordering: this task runs FIRST among the day-0 sync purchase tasks. SLT-SYN-05 through SLT-SYN-08 depend on it'. SLT-SYN-01 declares its positional-meta finding 'binding on SLT-SYN-07'. SLT-SYN-02 says 'the contract SLT-SYN-08 buys against'. SLT-SYN-03 states SLT Sync Excl Probe 'is bought exactly ONCE, by SLT-SYN-09'. SLT-SYN-05..09 are not authored. Consequence: SLT Sync Excl Probe (created and registered by SLT-SYN-03) has no owning purchaser at all and will be created, never exercised, then deleted by SLT-SETUP-99 - a wasted artifact and a wasted creation slot.
- *Required fix:* Either author SLT-SYN-05..09 or re-point the handoffs. Minimum viable repair for this window: delete the SLT Sync Excl Probe half of SLT-SYN-03 (its exclusivity evidence is already produced identically by SLT-PROD-05 steps 7-9), keep only SLT Sync Global Daily, and rewrite SLT-SYN-04's ordering clause to reference the actual successor tasks or to say 'no successor sync purchase task may run until this task's restore is proven'.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · same-account-collision** — with `SLT-PROD-15`

- *Problem:* Same shape as the SLT-SYN-01 case at variation level: SLT-SYN-02 toggles segments off on the Full variation, reconfigures the Next Cycle variation to segment-1-only, and ticks/unticks the master checkbox on the No Sync variation, saving each time. SLT-PROD-15's isolation note has all three variations purchased on d0. Any purchase placed while a probe state is in effect resolves filterRenewalSyncContext() against the wrong plan, and SLT-SYN-02's own handoff ('if SLT-SYN-08 observes identical next-payment dates, this task's evidence proves the fault is in product_id resolution') is void if the purchases straddled the probes.
- *Required fix:* Bind SLT-SYN-02 to run on the same day as SLT-PROD-15 (D2), immediately after creation and strictly BEFORE the three variation purchases, which move to D2 after 12:00. Add an explicit gate step at the end of SLT-SYN-02: re-dump the three variations' six metas and post them to the registry as the 'purchase-authorised configuration'; the purchasing task must quote that dump in its evidence.

**`medium` · shared-product-meta / undeclared bracket** — with `SLT-SYN-13`, `SLT-PROD-15`, `SLT-MYA-05`

- *Problem:* SLT-SYN-13 step 2 writes a decoy segment plan onto the SLT Flex Variable Daily PARENT product and deletes it only at step 7, the same day - but between those steps two live checkouts are placed and the window is unbounded in the body. SLT-SYN-02 audits the same product family on the same day (D2). Any other cart or checkout touching that parent inside the decoy window resolves filterRenewalSyncContext() against a plan no task expects, and the decoy's own null-vs-config proof depends on nothing else having read it. Separately SLT-MYA-05 leaves two appended members_access rules and a product-level _arraysubs_features meta live from D2 morning until its step-10 teardown on D7 - a five-day global deviation during which the pre-existing 'Gold members save 15%' rule (which targets pro_member on ALL products) can alter front-end prices for slt-fail.
- *Required fix:* For SYN-13: declare the decoy a bracket - record open/close UTC in slt-evidence/SLT-SYN-13-decoy-bracket.txt, post it to the registry, keep it under 90 minutes, and assert no other SLT task carts or checks out SLT Flex Variable Daily inside it. Add a pass criterion 'decoy removed and getConfig(<PARENT>) is null before the bracket closes'. For MYA-05: shorten the deviation by moving its teardown from D7 to immediately after follow-up B (D5 morning, once the on-hold role removal is captured) and re-adding the rules only if follow-up C needs them; record the bracket in the registry either way, and add an explicit price check on SLT Retry Daily renewals proving the pro_member discount never reached a cron renewal.

---
## Objective
Prove that the variation-level Flexible Renewal Sync block is a genuinely separate code path from the simple-product one — rendered on `arraysubs_subscription_variation_fields_before_shipping`, submitted as `META[$loop]` arrays, saved on `woocommerce_save_product_variation` priority 15 — and that three variations of ONE variable product hold three INDEPENDENT segment plans that survive the variation AJAX save, a page reload, and a reorder of the variation list. Also prove the parent product carries no flex meta of its own, so nothing can silently fall back to a parent plan.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: N/A
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01, SLT-SETUP-02 and SLT-PROD-15 (`SLT Flex Variable Daily`, attribute `SLT Sync Mode`, variations **Full** / **Next Cycle** / **No Sync**, all day/3 at $12.00) complete.
- SLT-SYN-01 complete — its findings on positional metas and the `no`-only deactivation rule apply identically here.
- Code facts (verified): the view is `arraysubspro/src/Features/FlexibleRenewalSync/views/variation-fields.php`; field names are `_arraysubs_flex_sync_enabled[<loop>]`, `_arraysubs_flex_sync_seg1_end[<loop>]`, `_arraysubs_flex_sync_seg2_end[<loop>]`, `_arraysubs_flex_sync_seg{1,2,3}_active[<loop>]`; unticking the master box DELETES `_arraysubs_flex_sync_enabled` on that variation rather than writing `no`; `filterSupportsRenewalSync()` and `filterRenewalSyncContext()` both resolve `subscription_data['product_id']` to the VARIATION id for a variation purchase.
- SLT-PROD-15 declared: **Full** = all three active, seg1_end 1, seg2_end 2; **Next Cycle** = segment 3 only; **No Sync** = flex unticked. This task must leave exactly that state.

## Test data
| Item | Value |
|---|---|
| Product | SLT Flex Variable Daily (parent) + variations Full / Next Cycle / No Sync, all day/3 $12.00 |
| Account | admin / @GuDw(0$K7M9t8ehjqDb4Vwj |
| Coupon | N/A |
| Card | N/A |
| Amounts | none charged — admin-only task |

## Steps
1. `PREV=$(/usr/local/bin/mailpit-agent latest-id)`; record it.
2. From WP root `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public` capture the before-state for the parent and all three variations: `wp post meta list <ID> --keys=_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_subscription_period,_subscription_interval --format=csv --allow-root`, tee all four into `/home/server-manager/slt-evidence/SLT-SYN-02-variation-meta-before.csv`.
3. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post.php?post=<SLT Flex Variable Daily PARENT ID>&action=edit"` -> `agent-browser --session admin snapshot -i` -> open the **Variations** tab and expand all three variations.
4. Confirm the Flexible Renewal Sync block appears INSIDE each variation panel, positioned after that variation's **Different Renewal Price** section, and that it does NOT appear anywhere in the parent-level **Subscription [ArraySubs]** area. Screenshot `SLT-SYN-02-01-three-variations-expanded.png`.
5. Read the rendered field names for the **Full** variation with `agent-browser --session admin eval "Array.from(document.querySelectorAll('[name*=arraysubs_flex_sync]')).map(e=>e.name+'='+e.value).join('\n')"` and record them. Confirm every name carries a `[<loop>]` index and that the three variations use three DISTINCT loop indices.
6. Confirm each variation's config container carries `data-cycle-days="3"` (day/3 nominal), and that the **Full** legend reads `1` / `2` / `3`, the **Next Cycle** legend reads `1 - 3` with a single row, and the **No Sync** variation shows the master checkbox UNTICKED with the config block hidden. Screenshot `SLT-SYN-02-02-legends.png`.
7. Independence probe: on the **Full** variation ONLY, turn the **Prorate amount** toggle OFF (leaving segments 1 and 3 active) and click **Save changes** on the Variations tab. Wait for the AJAX save to settle, then reload the edit screen and re-expand all three.
8. Read all three variations' metas again via the step-2 command. Confirm ONLY the **Full** variation changed and that `Next Cycle` and `No Sync` are byte-identical to the before file.
9. RESTORE: turn the **Prorate amount** toggle on the **Full** variation back ON, set the boundaries so the legend reads `1` / `2` / `3`, click **Save changes**, reload, and confirm.
10. Cross-write probe: set the **Next Cycle** variation's segment-3 toggle OFF and its segment-1 toggle ON (so it becomes 1-active on segment 1), **Save changes**, reload, and read the metas of all three. Then RESTORE **Next Cycle** to segment-3-only (seg1 OFF, seg2 OFF, seg3 ON), **Save changes**, reload, verify.
11. No-Sync deletion semantics: on the **No Sync** variation tick the master checkbox, **Save changes**, reload and read `_arraysubs_flex_sync_enabled` (expect `yes`). Then untick it, **Save changes**, reload and read again. Record whether the key is DELETED or set to a value.
12. Parent-leakage check: `wp post meta list <PARENT ID> --allow-root | grep arraysubs_flex` — expect no output.
13. Variation-id resolution proof (no purchase): for each of the three variation IDs run `wp eval 'use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan; $id=<VARIATION ID>; var_dump(SegmentPlan::isEnabled($id)); print_r(SegmentPlan::getConfig($id)); print_r(SegmentPlan::getPartition(SegmentPlan::getConfig($id) ?: ["cycle_days"=>3,"actives"=>[],"boundaries"=>[]]));' --allow-root` and record the output.
14. Segment resolution matrix per variation: `wp eval 'use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan; $c=SegmentPlan::getConfig(<VARIATION ID>); if(!$c){echo "null\n"; return;} foreach([1,2,3] as $d) echo "day $d -> segment ".SegmentPlan::resolveSegment($d,$c)." mode ".SegmentPlan::getSegmentMode(SegmentPlan::resolveSegment($d,$c))."\n";' --allow-root` for all three variations.
15. Final dump into `/home/server-manager/slt-evidence/SLT-SYN-02-variation-meta-after.csv` and `diff` against the before file.
16. `/usr/local/bin/mailpit-agent latest-id` must equal `$PREV`. `agent-browser close --all`.

## Expected results
1. The Flexible Renewal Sync block renders once per variation, inside the variation panel, and is absent from the parent Subscription tab.
2. Every submitted field name is loop-indexed: `_arraysubs_flex_sync_enabled[0]`, `_arraysubs_flex_sync_seg1_end[0]`, `_arraysubs_flex_sync_seg2_end[0]`, `_arraysubs_flex_sync_seg1_active[0]`, `_arraysubs_flex_sync_seg2_active[0]`, `_arraysubs_flex_sync_seg3_active[0]` (and the same with indices 1 and 2 for the other two variations). Three distinct indices are present.
3. All three config containers report `data-cycle-days="3"`.
4. Legends: **Full** = `1` / `2` / `3` (three rows); **Next Cycle** = `1 - 3` (one row); **No Sync** = master checkbox unticked, config block hidden.
5. Step 7-8: turning off segment 2 on **Full** changes ONLY that variation's metas — `Next Cycle` and `No Sync` rows are byte-identical to the before file. This proves `$_POST[META][$loop]` indexing does not bleed across variations.
6. Step 10: reconfiguring **Next Cycle** to segment-1-only likewise leaves **Full** and **No Sync** untouched.
7. Step 11: after unticking, `_arraysubs_flex_sync_enabled` is DELETED from the **No Sync** variation (the key is absent from `wp post meta list`), not stored as `no`; any previously submitted `seg1_end`/`seg2_end` values are retained.
8. Step 12: the PARENT product carries no `_arraysubs_flex_*` meta at all.
9. Step 13: `SegmentPlan::getConfig()` returns a non-null config for **Full** (`actives [1,2,3]`, `boundaries [1,2]`, `cycle_days 3`) and for **Next Cycle** (`actives [3]`, `boundaries []`, `cycle_days 3`), and returns `null` for **No Sync**.
10. Step 14 matrix — **Full**: day 1 -> segment 1 mode `full`; day 2 -> segment 2 mode `prorate`; day 3 -> segment 3 mode `next_cycle`. **Next Cycle**: days 1, 2 and 3 all -> segment 3 mode `next_cycle`. **No Sync**: `null`.
11. The step-15 diff against the before file is EMPTY — every probe was restored.
12. No AJAX/console error appears during any **Save changes** click.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Every variation AJAX save and every WP-CLI read in this task | — | — | `/usr/local/bin/mailpit-agent latest-id` at step 16 must equal `$PREV` from step 1 |

## Evidence to capture
- Screenshots: `SLT-SYN-02-01-three-variations-expanded.png`, `SLT-SYN-02-02-legends.png`, `SLT-SYN-02-03-full-seg2-off-independent.png`, `SLT-SYN-02-04-nosync-key-deleted.png`.
- The step-5 field-name dump verbatim (proving the `[<loop>]` indexing and three distinct indices).
- `SLT-SYN-02-variation-meta-before.csv`, `-after.csv`, and the diff (expected empty).
- The `getConfig()` / `getPartition()` / `resolveSegment()` output for all three variations.
- Parent ID, three variation IDs, `$PREV`, and any AJAX errors from the Variations tab network log.

## Pass criteria
- [ ] Flex block renders per variation and never on the parent
- [ ] All field names are `[<loop>]`-indexed with three distinct indices
- [ ] data-cycle-days is 3 on all three variations
- [ ] Editing one variation leaves the other two byte-identical (both directions probed)
- [ ] Unticking deletes `_arraysubs_flex_sync_enabled` and retains the boundaries
- [ ] Parent carries no flex meta
- [ ] getConfig() non-null for Full and Next Cycle, null for No Sync
- [ ] Segment/mode matrix matches Full 1/2/3 and Next Cycle all-3
- [ ] Before/after diff empty; zero mail; zero AJAX errors

## Isolation / teardown
- State handoff: the three verified variation configs are the contract SLT-SYN-08 buys against. If SLT-SYN-08 later observes identical next-payment dates for **Full** and **Next Cycle**, this task's evidence is what proves the fault is in `filterRenewalSyncContext()` product_id resolution and not in the stored configuration.
- Restores: all three variations returned to SLT-PROD-15's declared configuration (proved by the empty diff). No global setting touched. Nothing purchased, nothing deleted.

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
