---
id: 6
title: SLT-PROD-06 Create SLT Fixed Three Cycles, a day/2 subscription that expires on 2026-08-07
status: todo
priority: high
created: 2026-08-02T03:43:03.372534457+02:00
updated: 2026-08-02T03:43:13.293779117+02:00
tags:
    - setup
    - products
    - day-00
    - has-conflicts
due: "2026-08-02"
estimate: 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-06** · group `catalog` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-SYN-04`, `SLT-PROD-01`, `SLT-PROD-16`, `SLT-PROD-14`

- *Problem:* SLT-SYN-04's global-sync-ON window is not just a checkout hazard: any renewal that Action Scheduler processes while the switch is ON can pick up sync context and be re-anchored from its checkout anniversary to the site-local midnight boundary. By the time SLT-SYN-04 can realistically run (after SETUP-01/02/PROD-16/SETUP-05/SYN-03 have completed), several day/1 and day/2 subscriptions bought on D0/D1 already have renewals due, and their anniversary times are whatever clock time those checkouts happened. If a checkout was done at 09:30 site on D0, its renewal fires at 09:30 site the next day - inside a morning ON window.
- *Required fix:* Two-part rule. (1) Every SLT purchase on D0, D1 and D2 must be executed AFTER 12:00 site time, so all anniversary renewals land in the afternoon. (2) SLT-SYN-04's ON bracket is fixed at 09:00-11:00 site on D3 and no `wp action-scheduler run` of any kind may be issued during it. Record the exact UTC open/close timestamps of the bracket in the evidence root as SLT-SYN-04-bracket.txt so any anomalous renewal in that interval can be attributed.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · same-account-collision** — with `SLT-SYN-01`

- *Problem:* SLT-SYN-01 step 16 targets SLT Fixed Three Cycles - a product SLT-PROD-06 requires to be purchased on D0 and whose subscription must expire exactly on 2026-08-07. The step is also self-contradictory: it says 'ticking it and saving yields getConfig() = null' and then 'Do NOT save the tick'. If an executing agent resolves the contradiction by saving, a live, date-critical subscription's product gains _arraysubs_flex_sync_enabled=yes mid-life, and the pass criterion 'left with _arraysubs_flex_sync_enabled ABSENT' depends on a manual untick that is not itself verified against the live subscription.
- *Required fix:* Do not use a purchased product as the sub-minimum-cycle canvas. Have SLT-SYN-01 create its own throwaway probe product `SLT Flex SubMin Probe` (simple, virtual, subscription, day/2, $7.00, never purchased by anyone), run the tick/save/getConfig()===null probe there, and leave SLT Fixed Three Cycles completely untouched. Rewrite step 16 to remove the contradictory 'do not save' clause. The probe product matches the `SLT ` prefix so SLT-SETUP-99's existing product-search teardown already removes it.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-PROD-13`

- *Problem:* Clock drift against the authored anchor. The plan is written for D0 = 2026-08-01 with hard D0 purchase deadlines (SLT-PROD-06 'MUST be purchased on D0'; SLT-PROD-13 relies on 2026-08-01 being the Saturday start-of-week). The evidence root /home/server-manager/slt-evidence is empty - no task has executed - and the host clock has already rolled past the start of the window, so a literal D0 is partly or wholly gone before SLT-SETUP-01 runs.
- *Required fix:* Two of the three D0 constraints are softer than authored and can absorb the slip without shifting the window: SLT Fixed Three Cycles ends at start + 6 days, so a 2026-08-02 purchase expires 2026-08-08 (still D7, still observable); SLT Flex Week Segments purchased 2026-08-02 is day 2 of the same Saturday-anchored week cycle, so it stays in segment 1 with the same $14.00 charge and the same 2026-08-08 renewal. Keep the D0=2026-08-01 labels in the calendar but treat them as ordinal slots: if execution actually begins on 2026-08-02, shift every date by +1 and re-verify only two things - that SLT Fixed Three Cycles still expires on or before D9, and that the watch tail still reaches the last renewal (which moves to 2026-08-14).

**`medium` · contradictory-expected-result** — with `SLT-LIFE-04`, `SLT-EML-08`, `SLT-EML-14`

- *Problem:* SLT-LIFE-04 derives from code (OrderIntegration.php:1489-1502) that SLT Fixed Three Cycles stamps _end_date at the moment of the FINAL renewal charge and flips to arraysubs-expired inside that payment - so with a D0 (2026-08-02) purchase the expiry is 2026-08-06, not the catalog's 'expires 6 days after checkout' (which LIFE-04 itself proves is unbacked - arraysubs_calculate_end_date_from_length() has zero callers). SLT-EML-08 states 'S_FIX expired 2026-08-08' and hunts for the 'has expired' message dated 08-08; SLT-EML-14 states 'Fixed Three Cycles renews 08-04 and 08-06 and expires 08-08 (_end_date)'; SLT-PROD-06's title still says 'expires on 2026-08-07' (the pre-shift anchor). Three different dates for one event; EML-08 and EML-14 will both report a missing email.
- *Required fix:* Adopt LIFE-04's code-derived model as authoritative and restate the dates everywhere for D0 = 2026-08-02: renewal #1 2026-08-04, renewal #2 2026-08-06, _end_date = the 08-06 charge moment, status arraysubs-expired 08-06, subscription_expired mail 08-06 PM (readable on watch D5, 2026-08-07). Update SLT-EML-08 step 1 to search 08-06, SLT-EML-14's dated contract, SLT-PROD-06's title and objective, and the watch schedule. LIFE-04's 'file an issue if _end_date is not the final charge moment' stays as the open question.

---
## Objective
Provide the limited-cycle product whose entire life — signup, two renewals, expiry — fits inside the window. `arraysubs_calculate_end_date_from_length()` computes end = start + (interval x length) periods, so day/2 with length 3 ends exactly 6 days after checkout: bought on D0 (2026-08-01) it renews 2026-08-03 and 2026-08-05 and expires 2026-08-07, with the `expiring_soon` reminder (7 days before) necessarily suppressed because the whole life is shorter than the lead time.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- SLT-SETUP-02 baseline (global sync off) means the renewal times are anniversary-based: checkout time + 2 days, not midnight.

## Test data
| Item | Value |
|---|---|
| Product | SLT Fixed Three Cycles / slug `slt-fixed-three-cycles` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $7.00; charge today $7.00; two renewals of $7.00; total lifetime spend $21.00 |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Fixed Three Cycles`. **Description**: `SLT window product. Bills every 2 days for 3 cycles, then expires. Delete on 2026-08-11.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `7.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `2`; **Subscription Length** = `3`; **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked; **Flexible Renewal Sync** left UNTICKED (a 2-day nominal cycle is below `MIN_CYCLE_DAYS = 3`, so the plan could never resolve).
7. Slug `slt-fixed-three-cycles`. Publish. Reload and re-verify.
8. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_regular_price --allow-root`.
9. As `--session guest`, open the product page and confirm the summary states the limited number of cycles (`getSubscriptionDurationSummary()`).
10. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-fixed-three-cycles`.
2. `_subscription_period=day`, `_subscription_interval=2`, `_subscription_length=3`, `_trial_length=0`, `_regular_price=7.00`.
3. Product page shows a bounded duration (e.g. "for 3 cycles"), not "until cancelled".
4. Date contract for the buying task, bought on 2026-08-01: `_next_payment_date` = 2026-08-03 (same clock time), second renewal 2026-08-05, `_end_date` = 2026-08-07, final status `arraysubs-expired`.
5. `emails.expiring_soon.days_before = 7` exceeds the 6-day life, so no expiring-soon mail can legitimately be sent — the buying task must assert its absence and only the `has expired` mail on 2026-08-07.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-06-01-subscription-tab.png`, `SLT-PROD-06-02-frontend-duration.png`.
- Product ID; meta list output.

## Pass criteria
- [ ] Published with day/2 and length 3, price 7.00
- [ ] Front end shows the bounded cycle count
- [ ] Metas exactly as listed
- [ ] Flex sync left off (sub-minimum cycle documented)
- [ ] Zero mail

## Isolation / teardown
- State handoff: MUST be purchased on D0 (2026-08-01) as `slt-core` — any later purchase pushes the expiry past D9 and out of the observable window. It is also the only SLT product eligible as a day/2 subscription child of the Subscription Box (matching period AND interval), though SLT-PROD-10 creates its own dedicated child instead to avoid coupling.
- Restores: nothing. Deleted by SLT-SETUP-99.

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
