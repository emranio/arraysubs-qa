---
id: 60
title: SLT-PROD-11 Create the four-product plan ladder and wire upgrade/downgrade/crossgrade links
status: todo
priority: high
created: 2026-08-02T03:43:08.230625359+02:00
updated: 2026-08-02T03:43:18.625824279+02:00
tags:
    - setup
    - products
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-11** · group `catalog` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · shared-global-setting** — with `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-15`, `SLT-SETUP-99`, `SLT-SYN-04`

- *Problem:* The window-wide time-travel policy tells every task to advance time with `wp action-scheduler run --hooks=<hook> --force`. A bare hook drain is site-wide: it fires EVERY due pending action for that hook, including the 13 pre-existing non-SLT active subscriptions (which the isolation contract forbids touching) and every other SLT test's pending renewal invoice / renewal / hold / cancel / expire action. This is the single largest cross-contamination risk in the plan. Tasks that will necessarily drain: any renewal of SLT Flex Month Segments (next payment 2026-09-01 / 2026-10-01, unreachable naturally), the SLT Flex Week Segments segment-3 cohort (next payment 2026-08-15), the SLT Flex Variable Daily Next Cycle tail, the SLT-PROD-11 auto-downgrade case (requires a hand-set _end_date), and SLT-SETUP-99's wind-down. One broad drain on any of those days would prematurely fire the pending renewals of SLT Daily Core, SLT Retry Daily (destroying the 1-day/3-day grace ladder timing), SLT Fixed Three Cycles (destroying its 2026-08-07 expiry contract) and the Box.
- *Required fix:* Ban bare hook drains for the whole window. Mandatory procedure for every time-travel step: (1) screenshot wp-admin -> Tools -> Scheduled Actions filtered to Pending and record EVERY action due within the next 24h, aborting if any non-SLT action is due; (2) move only the target subscription's _next_payment_date and its paired schedule meta; (3) execute the single action by id from the Scheduled Actions screen (Run action) rather than by hook, or invoke the processor for one subscription id via `wp eval` passing that id explicitly; (4) if a hook drain is truly unavoidable, first cancel/park every other pending action for that hook from the Scheduled Actions UI, run the drain, then restore them, and record before/after _next_payment_date for all 13 pre-existing active subscriptions as proof they did not move. Confine all time-travel to D8 (2026-08-09), the single authorized drain day in the calendar.

**`high` · same-subscription collision / duplicate coverage** — with `SLT-EML-08`, `SLT-SW-02`, `SLT-SW-03`, `SLT-SW-01`

- *Problem:* Both tasks run on D8 and both drive the on_expire auto-downgrade of a slt-switch plan-ladder subscription. SLT-EML-08 step 5 sets _end_date on 'S_PRO - slt-switch's active Pro subscription' and fires arraysubs_expire_subscription to capture the auto_downgrade email and the expired-suppression negative. SLT-SW-02 Leg B does exactly the same on 'S-BASIC (on Pro since SLT-SW-01)'. There are only two slt-switch ladder subscriptions and SLT-SW-03 (d6) already crossgraded the other one (S-PRO) off Pro onto SLT Plan Peer - at which point Pro's _arraysubs_auto_downgrade_product no longer applies to it and EML-08's leg is unrunnable as written. Whichever task expires the remaining Pro subscription first consumes the other's canvas.
- *Required fix:* Single owner: SLT-SW-02 Leg B owns the hand-set _end_date and the expiry of S-BASIC (which SLT-SW-01 left on SLT Plan Pro). SLT-EML-08 becomes observation-only for that leg - it reads the auto_downgrade mail ('has been changed to SLT Plan Basic'), proves the subscription_expired suppression negative (EmailManager.php:317-322) and confirms S-BASIC re-activated on Basic at $5.00 - and runs strictly after SW-02 in the D8 order. Delete EML-08 steps 4-5 (queue screenshot + _end_date write) and replace with 'quote SLT-SW-02's pre-flight queue screenshot and _end_date timestamp'. Update EML-08's Test data to name S-BASIC, not S_PRO.

**`high` · dependency-inversion (product creation after first consumer)** — with `SLT-PROD-04`, `SLT-PROD-05`, `SLT-PROD-08`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-15`

- *Problem:* The corrected calendar in plan-audit places several catalog tasks later than the first new-index task that depends on them. SLT-SETUP-04 (coupons) is D3 but SLT-CPN-01/02 need it on D1 18:00-19:00. SLT-PROD-05 is D3 but SLT-LIFE-05 buys it on D1. SLT-PROD-16 is D1 but SLT-DUN-01 (corrected to D2 13:00) and SLT-CHK-04 (D2) need it, and SLT-MYA-05 needs it on D2 morning. SLT-PROD-09 is D5 but SLT-CPN-04 (D3) and SLT-CHK-12 (D5) depend on it. SLT-PROD-10 and SLT-PROD-11 are D4 but SLT-CHK-13 (D4), SLT-CHK-10 (D5) and SLT-SW-09 (D4, which explicitly says PROD-11 must be done 'before this task starts on D4') need them earlier in the day or before. SLT-PROD-08 is D5 but SLT-CHK-11 buys its variations on D5. SLT-PROD-15 is D2 and SLT-SYN-13 buys its variations on D2 - correct only if SYN-02's audit sits strictly between them.
- *Required fix:* Adopt the rebalanced calendar in this report: SETUP-04 and PROD-05 to D1 morning; PROD-16 to D1 morning (ahead of SETUP-05, which also gains PROD-14 as a dependency per audit C03); PROD-02/03/09/15 and SYN-02 to D2 morning; PROD-04/10/11 to D3 after the SYN-04 bracket closes; PROD-08 to D4 morning. Add an explicit intra-day ordering line to every day's calendar row ('creations and audits before 12:00, purchases after 12:00') and make it a pass criterion that each consuming task quotes the creating task's registry entry.

---
## Objective
Build the switching ladder as four daily subscription products and link them through the WooCommerce **Linked Products** tab, which is where `PlanSwitching\Services\Hooks::addSwitchingFields()` renders **Upgrade to**, **Downgrade to**, **Crossgrade to** and **Auto-downgrade to**. Targets are stored as ID arrays in `_arraysubs_upgrade_products`, `_arraysubs_downgrade_products`, `_arraysubs_crossgrade_products` and `_arraysubs_auto_downgrade_product`, and `ProrationCalculator::getAvailableSwitchOptions()` reads them from the SOURCE product only — so the links must be set on every rung, in both directions.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Baseline `plan_switching`: enabled, upgrades/downgrades/crossgrades all allowed, `proration_type = prorate_immediately`, `allow_customer_switch = true`, `auto_downgrade_timing = on_expire` — all unchanged by this window.
- All four rungs are day/1 so a switch and its prorated order are observable the same day, and so proration maths uses a 1-day cycle (credit/charge is dominated by the price delta, not by elapsed time).

## Test data
| Item | Value |
|---|---|
| Products | SLT Plan Basic `slt-plan-basic` $5.00; SLT Plan Pro `slt-plan-pro` $15.00; SLT Plan Enterprise `slt-plan-enterprise` $30.00; SLT Plan Peer `slt-plan-peer` $15.00 |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | all day/1, no trial, no fee, no length limit |

Link matrix (set on each source product's Linked Products tab):

| Source | Upgrade to | Downgrade to | Crossgrade to | Auto-downgrade to |
|---|---|---|---|---|
| SLT Plan Basic | SLT Plan Pro, SLT Plan Enterprise | (none) | (none) | (none) |
| SLT Plan Pro | SLT Plan Enterprise | SLT Plan Basic | SLT Plan Peer | SLT Plan Basic |
| SLT Plan Enterprise | (none) | SLT Plan Pro, SLT Plan Basic | (none) | SLT Plan Basic |
| SLT Plan Peer | SLT Plan Enterprise | SLT Plan Basic | SLT Plan Pro | (none) |

## Steps
1. Capture `mailpit-agent latest-id`.
2. For each of the four products: `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"`; set the title; **Description** `SLT window product. Plan-switching ladder rung. Delete on 2026-08-11.`; **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**; **General** tab **Regular price ($)** per the table; **Subscription [ArraySubs]** tab: **Billing Period** `Day`, **Billing Interval** `1`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked, **Flexible Renewal Sync** unticked; set the slug; Publish.
3. Record all four product IDs before wiring links.
4. Re-open each product and go to the **Linked Products** tab. Confirm the **Subscription Plan Switching** block is visible (it is display:none unless `_is_subscription=yes`).
5. Fill **Upgrade to**, **Downgrade to**, **Crossgrade to** and **Auto-downgrade to** exactly per the link matrix using the product-search selects (`data-action=woocommerce_json_search_products_and_variations`). Update.
6. Reload each product and confirm the selects still show the chosen products (proves `saveSwitchingFields()` persisted arrays, not strings).
7. Verify: `wp post meta get <ID> _arraysubs_upgrade_products --format=json --allow-root` (and the downgrade/crossgrade/auto keys) for all four.
8. Append the four IDs to the registry along with the link matrix.

## Expected results
1. Four products published, all simple + virtual + subscription, day/1, no trial, no fee, prices $5.00 / $15.00 / $30.00 / $15.00.
2. `_arraysubs_upgrade_products` on Basic is a 2-element array containing the Pro and Enterprise IDs.
3. Pro carries all four keys: upgrade `[Enterprise]`, downgrade `[Basic]`, crossgrade `[Peer]`, auto-downgrade `Basic`.
4. Enterprise carries downgrade `[Pro, Basic]` and auto-downgrade `Basic`, with an empty upgrade array.
5. Peer carries upgrade `[Enterprise]`, downgrade `[Basic]`, crossgrade `[Pro]`.
6. Pro <-> Peer is a genuine crossgrade (identical $15.00 price) so `ProrationCalculator` classifies it laterally and applies no proration or credit.
7. All link selects survive a page reload.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Four publishes and four link saves | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-11-01-basic-subscription-tab.png`, `SLT-PROD-11-02-pro-linked-products.png`, `SLT-PROD-11-03-enterprise-linked-products.png`, `SLT-PROD-11-04-peer-linked-products.png`.
- Four product IDs; the four meta JSON dumps; any select2 AJAX errors.

## Pass criteria
- [ ] Four rungs published at the exact prices, all day/1
- [ ] Link matrix stored as ID arrays on all four sources
- [ ] Links survive reload
- [ ] Pro and Peer are equal-priced (true crossgrade)
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy and switch ONLY as `slt-switch`. Switching requires the subscription to be in `arraysubs-active` or `arraysubs-trial` — `SwitchController` rejects any other status with "Plan switching is only available for active subscriptions". Auto-downgrade fires on expiry (`auto_downgrade_timing = on_expire`), which needs a length-limited or time-travelled subscription; the ladder rungs are length 0, so an auto-downgrade test must set `_end_date` by hand rather than expect it naturally.
- Restores: nothing global. All four deleted by SLT-SETUP-99.

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
