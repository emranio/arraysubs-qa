---
id: 13
title: SLT-SYN-01 Audit simple-product Flexible Renewal Sync UI, validation and meta keys
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal-sync
    - day-00
    - day-01
due: "2026-08-24"
estimate: 2h
depends_on:
    - 10
    - 11
    - 21
    - 8
    - 22
class: standard
---

> **SLT-SYN-01** · group `sync` · Pass A scheduled **D00** (2026-08-23), Pass B scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove that the pro Flexible Renewal Sync control block on a SIMPLE subscription product exposes every documented control, that the segment slider/legend, the two POSITIONAL boundary hidden inputs and the three per-segment active toggles round-trip through a save, that the UI refuses to leave zero segments active, that out-of-range boundaries are clamped by `SegmentPlan::sanitizeBoundaries()` / `sanitizeSingleBoundary()` instead of being stored raw, and that exactly six meta keys are written and no others.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: N/A
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 (evidence root, registry page) and SLT-SETUP-02 (window baseline, global sync OFF) complete.
- Pass A (D0) requires only SLT-PROD-13 (`SLT2 Flex Week Segments`, week/1 $14.00, seg1_end=2 seg2_end=5). Pass B (D1) requires SLT-PROD-12 (`SLT2 Flex Month Segments`, month/1 $30.00, seg1_end=24 seg2_end=27, all three active) and SLT-PROD-14 (`SLT2 Flex Daily Two Seg` day/3 $9.00 seg1 OFF seg1_end=1; `SLT2 Flex Daily Next Cycle` day/3 $9.00 seg3 only).
- Code facts (verified, revalidate against the current code and runtime before using): the panel is rendered by `arraysubspro/src/Features/FlexibleRenewalSync/views/simple-product-fields.php`; the six meta keys are `_arraysubs_flex_sync_enabled`, `_arraysubs_flex_sync_seg1_end`, `_arraysubs_flex_sync_seg2_end`, `_arraysubs_flex_sync_seg1_active`, `_arraysubs_flex_sync_seg2_active`, `_arraysubs_flex_sync_seg3_active`; the saver is `Hooks::persistFlexSyncMeta()` on `woocommerce_process_product_meta` priority 15; `SegmentPlan::MIN_CYCLE_DAYS = 3`; `getDefaultBoundaries(30)` returns `[10, 20]`; anything other than the literal string `no` in an `_active` meta counts as ACTIVE.
- This task MUST leave SLT-PROD-12/13/14 with their catalog-declared boundaries. Every probe below is followed by an explicit restore step.

## Binding split-execution contract
- **Pass A — D0:** audit only the week product: inventory its controls and `data-cycle-days=7`, prove the disable/re-enable retention path in step 17, take the canonical before/after diff, and publish the purchase-authorisation handoff for SLT-SYN-05. Close Pass A before that purchase.
- **Pass B — D1:** after PROD-12 and PROD-14 exist, perform steps 1–16 and 18–20 against the month product and two daily products plus the task-owned SubMin probe. **Do not open, save, or write meta on the week product in Pass B; it now has live subscription alias `SUB_W1` from the fresh registry.** Quote Pass A's week evidence instead of repeating step 17.
- The parent card remains `in-progress` after Pass A and moves to done only after Pass B's three-product canonical diffs, SubMin result, zero-mail check, and registry handoff all pass.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Flex Month Segments (month/1, $30.00), SLT2 Flex Week Segments (week/1, $14.00), SLT2 Flex Daily Two Seg (day/3, $9.00), SLT2 Flex Daily Next Cycle (day/3, $9.00), plus a task-owned `SLT2 Flex SubMin Probe` (day/2, $7.00; never purchased) |
| Account | use the current local admin credential source in `AGENTS.md` |
| Coupon | N/A |
| Card | N/A |
| Amounts | none charged — admin-only task |

## Steps
1. Capture the mail baseline: `PREV=$(/usr/local/bin/mailpit-agent latest-id)` and record the value.
2. Record the pre-task meta of the products owned by the current pass so the restores are provable. Preserve the raw CSV as
   evidence, but also create a canonical representation sorted by `meta_key` (or a keyed `jq -S` object).
   WordPress row insertion order is not state and must never be the restore assertion. From WP root run, for
   each product ID, `wp post meta list <ID> --keys=_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active --format=json --allow-root | jq -S 'map({key:.meta_key,value:.meta_value}) | sort_by(.key)'` and save the result as that product's canonical before file.
3. `agent-browser skills get core`, then `agent-browser --session admin-SLT-SYN-01 open "https://mirror-help.arrayhash.com/wp-admin/post.php?post=<SLT2 Flex Month Segments ID>&action=edit"` -> `agent-browser --session admin-SLT-SYN-01 snapshot -i`.
4. Open the **Subscription [ArraySubs]** tab. Inventory every control in the **Flexible Renewal Sync to Next Billing Cycle** block and record its exact label: the master checkbox **Flexible Renewal Sync to Next Billing Cycle**; the description line "Align renewals to the billing-cycle boundary and pick how the first payment is charged based on the day of the cycle the customer signs up."; the slider container (`data-cycle-days`); the three legend rows with toggles and range text; the three segment labels **Full amount**, **Prorate amount**, **Charge full for next billing cycle**. Screenshot as `SLT-SYN-01-01-month-panel-inventory.png`.
5. Read `data-cycle-days` off the config container and confirm it is `30` for month/1 and `3` on both day/3 products. Cite Pass A's captured `7` for the week product; do not re-open it in Pass B.
6. Confirm the two boundary inputs are HIDDEN inputs named `_arraysubs_flex_sync_seg1_end` and `_arraysubs_flex_sync_seg2_end` (they are driven by the slider, not typed) and that their current values are `24` and `27`.
7. Boundary-ordering probe A (inverted pair): drag the slider so the legend would read seg1 = `1 - 8`, seg2 = `9 - 12`, then use the browser to set the two hidden inputs directly to an INVERTED pair — `agent-browser --session admin-SLT-SYN-01 eval "document.querySelector('.arraysubs-flex-sync-seg1-end').value='20';document.querySelector('.arraysubs-flex-sync-seg2-end').value='5';"` — and click **Update**. Re-open and read the stored metas.
8. Boundary-ordering probe B (out of cycle): repeat step 7 with `seg1_end='0'` and `seg2_end='45'` (both outside 1..29 for a 30-day nominal cycle), click **Update**, re-open and read the stored metas.
9. Boundary-ordering probe C (collapsing pair): repeat with `seg1_end='29'` and `seg2_end='29'`, **Update**, re-read.
10. RESTORE the month product: drag/set `seg1_end=24`, `seg2_end=27`, all three toggles ON, click **Update**, and confirm the legend reads `1 - 24` / `25 - 27` / `28 - 30`. Screenshot `SLT-SYN-01-02-month-restored.png`.
11. Last-active-segment refusal: on the month product turn the **Full amount** toggle OFF, then **Prorate amount** OFF, then attempt to turn **Charge full for next billing cycle** OFF. Capture the verbatim inline notice (expected: `At least one segment must stay active.`) and screenshot `SLT-SYN-01-03-last-active-refusal.png`. Do NOT save; navigate away with **Discard**/browser back and re-open to confirm the product is still 3-active.
12. Zero-active server-side fallback probe (defensive path, no UI): on `SLT2 Flex Month Segments` run `wp post meta update <ID> _arraysubs_flex_sync_seg1_active no --allow-root`, same for seg2 and seg3, then `wp eval 'print_r(\ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan::getConfig(<ID>));' --allow-root`. Record the returned `actives` array. Immediately restore: `wp post meta update <ID> _arraysubs_flex_sync_seg1_active yes --allow-root` (and seg2, seg3).
13. Non-`no` string probe: `wp post meta update <ID> _arraysubs_flex_sync_seg1_active 0 --allow-root`, re-run the `getConfig()` eval, record whether segment 1 is still counted active, then restore to `yes`.
14. Two-active positional check on `SLT2 Flex Daily Two Seg`: open its edit screen, confirm the legend shows only TWO rows (`1` for **Prorate amount**, `2 - 3` for **Charge full for next billing cycle**) and that `_arraysubs_flex_sync_seg1_end` is `1` — i.e. the meta names the end of the FIRST ACTIVE segment, which is segment 2 here, NOT segment 1. Screenshot `SLT-SYN-01-04-two-active-positional.png`.
15. One-active check on `SLT2 Flex Daily Next Cycle`: confirm the legend collapses to a single row `1 - 3`, that no boundary handle is draggable, and run `wp eval 'print_r(\ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan::getConfig(<ID>));' --allow-root` to confirm `boundaries` is an EMPTY array. Screenshot `SLT-SYN-01-05-one-active.png`.
16. Sub-minimum-cycle check on a task-owned throwaway product — **do not open or mutate `SLT2 Fixed Three Cycles`**. First require `wp post list --post_type=product --name=slt2-flex-submin-probe --field=ID --allow-root` to return no ID. In `admin-SLT-SYN-01`, create `SLT2 Flex SubMin Probe` / slug `slt2-flex-submin-probe`: Simple, Virtual, Subscription, regular price `$7.00`, day/2, length 0, trial 0, no signup fee, description `SLT2 task-owned sub-minimum flex probe. Never purchase. Delete on 2026-09-05.` Confirm the Flexible Renewal Sync block is present, tick it, save, and prove `wp eval 'var_dump(\ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan::getConfig(<PROBE_ID>));' --allow-root` returns `NULL`. Immediately append only `<PROBE_ID>` to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**, preserving every other field and prior exclusion; re-read the raw option and require the ID exactly once. Record its exact product ID and verified exclusion in the registry and screenshot `SLT-SYN-01-05b-submin-probe.png`. Then untick the master control, save, and verify `_arraysubs_flex_sync_enabled` is absent. Never add this product to a cart.
17. **Pass A only:** Disable-retains-boundaries probe: on `SLT2 Flex Week Segments` untick the master checkbox and **Update**. Read the metas. Then re-tick and **Update**, and confirm the legend redraws `1 - 2` / `3 - 5` / `6 - 7` without re-entering the boundaries. Screenshot `SLT-SYN-01-06-week-reenabled.png`. Pass B cites this evidence and must not repeat it.
18. Final verification dump: preserve the raw after CSV, then repeat step 2's canonical JSON command for the
    products owned by the current pass and diff each canonical after file against its canonical before file. The canonical diffs
    must be empty. A raw CSV diff is informational only: after the required disable/re-enable probe, the
    recreated `_arraysubs_flex_sync_enabled` row may move to the end without changing product state.
19. Inspect the complete Mailpit delta after `$PREV`. No message may be attributable to this task's product saves/meta writes; classify independently scheduled/background mail by its actual owner instead of requiring the shared global latest ID to remain unchanged.
20. Close only `admin-SLT-SYN-01` by explicit name.

## Expected results
1. The month product's config container carries `data-cycle-days="30"`; the week product `7`; both day/3 products `3`.
2. Probe A (seg1_end=20, seg2_end=5, inverted): the stored pair is re-derived/clamped — `sanitizeBoundaries(20,5,30)` is entered because `seg2_end <= seg1_end`, yielding `_arraysubs_flex_sync_seg1_end=20` and `_arraysubs_flex_sync_seg2_end=21`. Neither value is stored raw as submitted, and `seg2_end > seg1_end` always holds.
3. Probe B (0 and 45): both are out of range, so `getDefaultBoundaries(30)` supplies `_arraysubs_flex_sync_seg1_end=10` and `_arraysubs_flex_sync_seg2_end=20`.
4. Probe C (29 and 29): clamped to `seg1_end=28`, `seg2_end=29` — every one of the three partitions retains at least one day and `seg2_end <= cycle_days - 1 = 29`.
5. After step 10 the month product is back to `seg1_end=24`, `seg2_end=27`, all three `_active` metas `yes`, and the legend reads `1 - 24` / `25 - 27` / `28 - 30`.
6. Turning off the last remaining active segment is refused in the UI with the verbatim string `At least one segment must stay active.` and no save occurs.
7. Step 12: with all three `_active` metas set to `no`, `SegmentPlan::getConfig()` returns `actives => [1, 2, 3]` (defensive fallback) rather than null or an empty array.
8. Step 13: `_arraysubs_flex_sync_seg1_active = 0` still counts as ACTIVE, because only the literal string `no` deactivates a segment. Record this as a documented sharp edge.
9. `SLT2 Flex Daily Two Seg` shows exactly two legend rows, `1` (Prorate amount) and `2 - 3` (Charge full for next billing cycle), and `_arraysubs_flex_sync_seg1_end = 1` is the end of the first ACTIVE segment (segment 2) — positional, not segment-named.
10. `SLT2 Flex Daily Next Cycle` shows one legend row `1 - 3` and `getConfig()['boundaries']` is `[]`.
11. Task-owned `SLT2 Flex SubMin Probe` (nominal 2 days < MIN_CYCLE_DAYS 3) yields `getConfig() === null` even with the checkbox ticked, is present exactly once in the preserved Shop Access exclusion list, and is left with `_arraysubs_flex_sync_enabled` ABSENT. `SLT2 Fixed Three Cycles` is never opened or mutated.
12. Unticking the master checkbox DELETES `_arraysubs_flex_sync_enabled` but RETAINS `_arraysubs_flex_sync_seg1_end`/`seg2_end`; re-ticking restores the same legend with no re-entry.
13. The step-18 canonical key/value diff against step 2 is empty for every product in each pass — every probe was restored. Pass A supplies the week diff; Pass B supplies the month and two daily diffs. Raw database row order is explicitly ignored.
14. Exactly the six documented meta keys exist on each flex product; no additional `_arraysubs_flex_*` key appears.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Every product save and every WP-CLI meta write in this task | — | — | complete delta after `$PREV`; zero task-attributable mail, classify unrelated mail |

## Evidence to capture
- Screenshots: `SLT-SYN-01-01-month-panel-inventory.png`, `SLT-SYN-01-02-month-restored.png`, `SLT-SYN-01-03-last-active-refusal.png`, `SLT-SYN-01-04-two-active-positional.png`, `SLT-SYN-01-05-one-active.png`, `SLT-SYN-01-05b-submin-probe.png`, `SLT-SYN-01-06-week-reenabled.png`.
- Raw before/after CSV dumps plus canonical key-sorted before/after JSON and the canonical diff output
  (expected empty). Raw CSV ordering differences remain evidence but do not fail the task.
- The three probe meta readbacks (A/B/C) verbatim, the `getConfig()` print_r output for steps 12/13/15/16, the sub-minimum probe product ID, the raw Shop Access rule showing that ID exactly once, and the verbatim last-active notice string.
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
- [ ] Sub-minimum probe parent ID is present exactly once in the preserved Shop Access exclusion list
- [ ] Disable retains boundaries; re-enable restores the legend
- [ ] Canonical key/value before/after meta diff is empty; raw row order ignored; zero mail

## Isolation / teardown
- State handoff: the freshly confirmed and restored segment plans for SLT-PROD-12/13/14 are the baseline for later purchases. Complete Pass A before Pass B; the positional-meta contract from step 14 is revalidated by SLT-SYN-07.
- Restores: all four flex products returned to their SLT-PROD-declared configuration (proved by the empty diff); `SLT2 Fixed Three Cycles` was not touched. The task-owned SubMin Probe is disabled, registered by exact ID, present in the Shop Access exclusion list for the window, never purchased, and retained only for SLT-SETUP-99B deletion; SLT-SETUP-99A restores the full rule snapshot. No other global setting touched.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
