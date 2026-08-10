---
id: 44
title: SLT-SYN-02 Audit variation-level Flexible Renewal Sync UI, [$loop] meta and per-variation independence
status: done
priority: critical
created: 2026-08-02T03:43:06.596459182+02:00
updated: 2026-08-05T08:33:43.132592545+02:00
started: 2026-08-05T08:33:43.132591793+02:00
completed: 2026-08-05T08:33:43.132591793+02:00
tags:
    - renewal-sync
    - day-02
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
| Account | use the current local admin credential source in `AGENTS.md` |
| Coupon | N/A |
| Card | N/A |
| Amounts | none charged — admin-only task |

## Steps
1. `PREV=$(/usr/local/bin/mailpit-agent latest-id)`; record it.
2. From WP root `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public` resolve the exact parent and three variation IDs, then capture the before-state for all four: `wp post meta list <ID> --keys=_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_subscription_period,_subscription_interval --format=csv --allow-root`, tee all four into `/home/server-manager/slt-evidence/SLT-SYN-02-variation-meta-before.csv`. Also save the ID-to-attribute-to-`menu_order` mapping as `/home/server-manager/slt-evidence/SLT-SYN-02-variation-order-before.csv`; later probes must identify variations by ID/attribute, never by their current loop position.
   - Precondition guard: if the fresh variable parent already contains any `_arraysubs_flex_sync_*` key, preserve that exact first read as `SLT-SYN-02-parent-precondition-leak.csv` and write a standalone product issue under `issues/`. Delete only those six parent flex keys as QA-fixture containment, verify they are absent, and then recapture the authoritative `variation-meta-before.csv`. Do not click the parent-level **Update** action during this task because its hidden unindexed fields can rematerialize the leak. The task verdict must retain the product finding even if the subsequent variation audit passes.
3. `agent-browser --session admin-SLT-SYN-02 open "https://mirror-help.arrayhash.com/wp-admin/post.php?post=<SLT Flex Variable Daily PARENT ID>&action=edit"` -> `agent-browser --session admin-SLT-SYN-02 snapshot -i` -> open the **Variations** tab and expand all three variations.
4. Confirm the Flexible Renewal Sync block appears INSIDE each variation panel, positioned after that variation's **Different Renewal Price** section, and that it does NOT appear anywhere in the parent-level **Subscription [ArraySubs]** area. Screenshot `SLT-SYN-02-01-three-variations-expanded.png`.
5. Read the rendered field names scoped to the variation panels with `agent-browser --session admin-SLT-SYN-02 eval "Array.from(document.querySelectorAll('#variable_product_options [name*=arraysubs_flex_sync]')).map(e=>e.name+'='+e.value).join('\n')"` and record them. Confirm every variation name carries a `[<loop>]` index and that the three variations use three DISTINCT loop indices. Separately list unindexed flex fields outside `#variable_product_options`; if the hidden parent tab contributes any, attach that proof to the step-2 standalone product issue rather than falsely failing the variation index assertion.
6. Confirm each variation's config container carries `data-cycle-days="3"` (day/3 nominal), and that the **Full** legend reads `1` / `2` / `3`, the **Next Cycle** legend reads `1 - 3` with a single row, and the **No Sync** variation shows the master checkbox UNTICKED with the config block hidden. Screenshot `SLT-SYN-02-02-legends.png`.
7. Independence probe: on the **Full** variation ONLY, turn the **Prorate amount** toggle OFF (leaving segments 1 and 3 active) and click **Save changes** on the Variations tab. Wait for the AJAX save to settle, then reload the edit screen and re-expand all three.
8. Read all three variations' metas again via the step-2 command. Confirm ONLY the **Full** variation changed and that `Next Cycle` and `No Sync` are byte-identical to the before file; capture `SLT-SYN-02-03-full-seg2-off-independent.png`.
9. RESTORE: turn the **Prorate amount** toggle on the **Full** variation back ON, set the boundaries so the legend reads `1` / `2` / `3`, click **Save changes**, reload, and confirm.
10. Cross-write probe: on **Next Cycle**, turn segment 1 ON first and only then turn segment 3 OFF (the UI correctly prevents disabling the sole active segment), so it becomes segment-1-only. **Save changes**, reload, and read the metas of all three. RESTORE the visible plan by turning segment 3 ON first and segment 1 OFF, leaving segment 2 OFF, then **Save changes** and reload. Because the browser control normalizes dormant boundary inputs while moving between one-segment plans, compare the two raw boundary keys with the exact step-2 values; if they drifted, preserve the drift read and restore only those two dormant keys with ID-keyed `wp post meta update` commands. Require actives `no/no/yes`, boundaries `1/2`, and byte-identical Next Cycle rows before continuing.
11. Reorder probe: record the current UI order, drag **Full** below **Next Cycle**, click **Save changes**, reload, and resolve each panel again by exact variation ID/attribute. Require every plan to remain attached to its original variation ID and capture `SLT-SYN-02-04-reorder-independent.png`. Restore the exact step-2 order, save/reload, and abort unless the final ID/attribute/`menu_order` mapping is byte-identical to `SLT-SYN-02-variation-order-before.csv`.
12. No-Sync deletion semantics: on the **No Sync** variation tick the master checkbox, **Save changes**, reload and read `_arraysubs_flex_sync_enabled` (expect `yes`). Then untick it, **Save changes**, reload and read again. Require the master key to be DELETED, not set to `no`, and record all dormant active/boundary keys left by the submitted enabled state. Capture `SLT-SYN-02-05-nosync-key-deleted.png`. For byte-exact isolation, compare key existence with step 2 and delete only probe-created dormant active keys that were absent from the authoritative baseline; retain the pre-existing boundary values and abort unless the final No Sync rows are byte-identical.
13. Parent-leakage check: `wp post meta list <PARENT ID> --allow-root | rg '_arraysubs_flex_sync_'` — expect no output. Do not use the broader `arraysubs_flex` pattern because it also matches the unrelated `_arraysubs_flexible_periods` subscription-setting key.
14. Variation-id resolution proof (no purchase): for each of the three variation IDs run `wp eval 'use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan; $id=<VARIATION ID>; var_dump(SegmentPlan::isEnabled($id)); print_r(SegmentPlan::getConfig($id)); print_r(SegmentPlan::getPartition(SegmentPlan::getConfig($id) ?: ["cycle_days"=>3,"actives"=>[],"boundaries"=>[]]));' --allow-root` and record the output.
15. Segment resolution matrix per variation: `wp eval 'use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan; $c=SegmentPlan::getConfig(<VARIATION ID>); if(!$c){echo "null\n"; return;} foreach([1,2,3] as $d) echo "day $d -> segment ".SegmentPlan::resolveSegment($d,$c)." mode ".SegmentPlan::getSegmentMode(SegmentPlan::resolveSegment($d,$c))."\n";' --allow-root` for all three variations.
16. Final dump into `/home/server-manager/slt-evidence/SLT-SYN-02-variation-meta-after.csv` and `diff` against the before file; also re-dump the final variation order and require an empty diff against the step-2 mapping.
17. Inspect every Mailpit message newer than `$PREV`; require zero message attributable to this task and classify unrelated/background mail by its actual owner. Append the exact parent/variation IDs, ID-keyed final meta dump, `getConfig()` results, and restored order proof to the registry and D02 watch report as the purchase-authorisation handoff for `SLT-SYN-13`. Close only `admin-SLT-SYN-02` by explicit name.

## Expected results
1. The Flexible Renewal Sync block renders once per variation, inside the variation panel, and no parent flex block is visible. Any unindexed fields retained in the hidden parent tab are evidence for the guarded product finding.
2. Every variation-panel field name is loop-indexed: `_arraysubs_flex_sync_enabled[0]`, `_arraysubs_flex_sync_seg1_end[0]`, `_arraysubs_flex_sync_seg2_end[0]`, `_arraysubs_flex_sync_seg1_active[0]`, `_arraysubs_flex_sync_seg2_active[0]`, `_arraysubs_flex_sync_seg3_active[0]` (and the same with indices 1 and 2 for the other two variations). Three distinct indices are present.
3. All three config containers report `data-cycle-days="3"`.
4. Legends: **Full** = `1` / `2` / `3` (three rows); **Next Cycle** = `1 - 3` (one row); **No Sync** = master checkbox unticked, config block hidden.
5. Step 7-8: turning off segment 2 on **Full** changes ONLY that variation's metas — `Next Cycle` and `No Sync` rows are byte-identical to the before file. This proves `$_POST[META][$loop]` indexing does not bleed across variations.
6. Step 10: reconfiguring **Next Cycle** to segment-1-only likewise leaves **Full** and **No Sync** untouched.
7. Step 11: after a real reorder, every plan remains attached to the same variation ID; the original order is restored and its mapping diff is empty.
8. Step 12: after unticking, `_arraysubs_flex_sync_enabled` is DELETED from the **No Sync** variation (the key is absent from `wp post meta list`), not stored as `no`; submitted dormant active/boundary values may remain and are recorded before the probe-created active keys are removed to restore the exact baseline.
9. Step 13: after the guarded precondition normalization, the PARENT product carries no `_arraysubs_flex_*` meta and no variation-only AJAX save rematerializes it. Any flex keys present on the fresh parent before normalization are a product finding, not an acceptable final state.
10. Step 14: `SegmentPlan::getConfig()` returns a non-null config for **Full** (`actives [1,2,3]`, `boundaries [1,2]`, `cycle_days 3`) and for **Next Cycle** (`actives [3]`, `boundaries []`, `cycle_days 3`), and returns `null` for **No Sync**.
11. Step 15 matrix — **Full**: day 1 -> segment 1 mode `full`; day 2 -> segment 2 mode `prorate`; day 3 -> segment 3 mode `next_cycle`. **Next Cycle**: days 1, 2 and 3 all -> segment 3 mode `next_cycle`. **No Sync**: `null`.
12. The step-16 meta and order diffs against the before files are EMPTY — every probe was restored.
13. No AJAX/console error appears during any **Save changes** click.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Every variation AJAX save and every WP-CLI read in this task | — | — | Complete delta after `$PREV`; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-SYN-02-01-three-variations-expanded.png`, `SLT-SYN-02-02-legends.png`, `SLT-SYN-02-03-full-seg2-off-independent.png`, `SLT-SYN-02-04-reorder-independent.png`, `SLT-SYN-02-05-nosync-key-deleted.png`.
- The step-5 field-name dump verbatim (proving the `[<loop>]` indexing and three distinct indices).
- `SLT-SYN-02-variation-meta-before.csv`, `-after.csv`, and the diff (expected empty).
- The `getConfig()` / `getPartition()` / `resolveSegment()` output for all three variations.
- Parent ID, three variation IDs, `$PREV`, and any AJAX errors from the Variations tab network log.

## Pass criteria
- [x] Flex block renders per variation and never visibly on the parent
- [x] All variation-panel field names are `[<loop>]`-indexed with three distinct indices; hidden unindexed parent fields are separately documented
- [x] data-cycle-days is 3 on all three variations
- [x] Editing one variation leaves the other two byte-identical (both directions probed)
- [x] Reordering variations leaves every plan attached to its exact ID, then the original order is restored with an empty diff
- [x] Unticking deletes `_arraysubs_flex_sync_enabled`; dormant values are recorded and the exact No Sync baseline is restored
- [x] Parent remains free of flex meta after guarded normalization; the initial leak is preserved as a standalone issue
- [x] getConfig() non-null for Full and Next Cycle, null for No Sync
- [x] Segment/mode matrix matches Full 1/2/3 and Next Cycle all-3
- [x] Before/after diffs empty; zero task-attributable mail; zero task-attributable AJAX/console errors

## Isolation / teardown
- State handoff: the three verified variation configs are the contract `SLT-SYN-13` buys against. If it later observes identical next-payment dates for **Full** and **Next Cycle**, this task's evidence is what proves the fault is in variation resolution and not in the stored configuration.
- Restores: all three variations returned to SLT-PROD-15's declared configuration (proved by the empty diff). No global setting touched. Nothing purchased, nothing deleted.

## Execution — 2026-08-05 (late completion of D02)

Verdict: **COMPLETED WITH PRODUCT FINDING**. The variation-level independence contract passed after the guarded parent cleanup. The fresh parent leak remains an open standalone finding at `issues/SLT-SYN-02-variable-parent-hidden-flex-meta.md`.

- Parent `12385`; Full `12386`; Next Cycle `12388`; No Sync `12390`.
- All indexed loops, day/3 containers, legends, two cross-write directions, real reorder, deletion semantics, parent isolation, runtime config/partition/matrix, and exact restoration were verified.
- Both final diffs are empty. Parent exact-prefix read is empty.
- Mailpit baseline `6fzJg6YALlBNfbNPe6f79F`, final `45OTdiHe9PfgKqXpz0uE51`: 34 background messages fully classified, zero task-attributable messages.
- The opaque browser `Object` page-error reproduced on an ordinary Dashboard control reload; no Variations Save changes AJAX failure or task-attributable console error occurred.
- Plan corrections C186-C191 were applied before closure.
- Purchase authorization for `SLT-SYN-13` is limited to the clean ID-keyed Full/Next Cycle configuration recorded here.
- Primary facts: `/home/server-manager/slt-evidence/SLT-SYN-02-facts.txt`.

## Self-review

- Re-read the originating plan, its corrected C186-C191 isolation clauses, the standalone issue, all five screenshots, field-name/runtime dumps, exact before/after files, Mailpit delta, browser control comparison, and final live UI.
- Confirmed no product source was inspected or changed, no global setting was changed, no purchase occurred, no card data was captured, and only the task session is eligible for closure.

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

[[2026-08-05]] Wed 08:33
Review outcome: PASS for the variation-independence contract; the separate parent hidden-meta product finding remains open. Evidence inventory, exact restoration diffs, Mailpit classification, registry/watch handoff, issue completeness, and browser control comparison all rechecked.
