---
id: 27
title: 'SLT-SYN-03 Create the two sync-group control products: SLT Sync Global Daily and SLT Sync Excl Probe'
status: todo
priority: critical
created: 2026-08-02T03:43:05.218059347+02:00
updated: 2026-08-02T03:43:15.64981062+02:00
tags:
    - renewal-sync
    - day-01
    - has-conflicts
due: "2026-08-03"
estimate: 45m
depends_on:
    - 10
    - 11
    - 22
class: standard
---

> **SLT-SYN-03** · group `sync` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · dependency-inversion** — with `SLT-SYN-04`, `SLT-SYN-01`, `SLT-SYN-02`

- *Problem:* Four tasks bind handoffs to task keys that do not exist anywhere in the plan index. SLT-SYN-04 declares 'MANDATORY ordering: this task runs FIRST among the day-0 sync purchase tasks. SLT-SYN-05 through SLT-SYN-08 depend on it'. SLT-SYN-01 declares its positional-meta finding 'binding on SLT-SYN-07'. SLT-SYN-02 says 'the contract SLT-SYN-08 buys against'. SLT-SYN-03 states SLT Sync Excl Probe 'is bought exactly ONCE, by SLT-SYN-09'. SLT-SYN-05..09 are not authored. Consequence: SLT Sync Excl Probe (created and registered by SLT-SYN-03) has no owning purchaser at all and will be created, never exercised, then deleted by SLT-SETUP-99 - a wasted artifact and a wasted creation slot.
- *Required fix:* Either author SLT-SYN-05..09 or re-point the handoffs. Minimum viable repair for this window: delete the SLT Sync Excl Probe half of SLT-SYN-03 (its exclusivity evidence is already produced identically by SLT-PROD-05 steps 7-9), keep only SLT Sync Global Daily, and rewrite SLT-SYN-04's ordering clause to reference the actual successor tasks or to say 'no successor sync purchase task may run until this task's restore is proven'.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · same-account-collision** — with `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-15`, `SLT-SETUP-03`, `SLT-SYN-04`

- *Problem:* multiple_subscriptions.auto_migrate_on_checkout = true is a baseline the plan never changes, yet three tasks require the SAME account (slt-flex) to buy the SAME product three separate times: SLT-PROD-12 demands three purchases of SLT Flex Month Segments (segments 1/2/3), SLT-PROD-13 three purchases of SLT Flex Week Segments, and SLT-PROD-15 three purchases of three variations of one variable parent. With auto-migrate on, the second and third checkouts are liable to MIGRATE the customer's existing subscription for that product rather than create an independent one - which silently destroys the segment-1 subscription that the earlier purchase created, and makes the whole segment matrix unobservable. On top of that, slt-flex is additionally loaded with SLT Sync Global Daily (SLT-SYN-04) and SLT Sync Excl Probe (SLT-SYN-03) by explicit deviation, so one account would end up owning 9+ concurrent subscriptions and the my-account list becomes ambiguous for every later assertion.
- *Required fix:* Extend SLT-SETUP-03's matrix from 7 to 9 accounts: add A9 slt-flex2 / slt-flex2@example.test and A10 slt-flex3 / slt-flex3@example.test, same password and billing address. Bind: segment-1 purchases -> slt-flex, segment-2 purchases -> slt-flex2, segment-3 purchases -> slt-flex3, and the same 1/2/3 split for the SLT Flex Variable Daily variations. No account ever buys the same product twice. Before the first repeat purchase would have happened, run a one-line probe of auto_migrate behaviour and record it in the registry so the split is evidence-backed.

**`unrated` · same-account-collision** — with `SLT-SETUP-05`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-12`

- *Problem:* Ten tasks perform cart previews as `--session guest` and each one ends with 'empty the cart'. agent-browser sessions are keyed by name, so every one of these tasks shares ONE cart. Run on the same day (as authored, all on d0) they interleave: a leftover subscription line from SLT-PROD-04 makes SLT-PROD-09's probe-B multi-subscription refusal fire for the wrong reason; SLT-PROD-10's box add-to-cart explicitly EMPTIES the cart first, silently wiping another task's staged preview; SLT-SETUP-05's gateway accordion reading can be taken against a cart that still holds a flex product, which hides Paddle and produces a false failure of its own pass criterion.
- *Required fix:* Give every task its own browser session name: `--session guest-SLT-PROD-04`, `--session guest-SLT-SETUP-05`, etc. Each cart-touching task must additionally assert the cart is EMPTY as its first action and empty it again as its last action, capturing both in evidence. Close only its own session (`agent-browser close --session <name>`); reserve `agent-browser close --all` for the last task of the day.

**`unrated` · duplicate-coverage** — with `SLT-PROD-15`, `SLT-PROD-05`

- *Problem:* SLT-SYN-03 creates two products that are already covered. (a) SLT Sync Global Daily (day/3, non-flex) is functionally identical as a control to the 'No Sync' variation of SLT Flex Variable Daily (day/3, flex unticked) created by SLT-PROD-15 - both exist to show anniversary scheduling on a 3-day cycle. (b) SLT Sync Excl Probe exists only to demonstrate that Different Renewal Price hides the Flexible Renewal Sync section, which SLT-PROD-05 steps 7-9 already capture verbatim with three screenshots, and its declared purchaser SLT-SYN-09 does not exist. Two product-creation slots on the most overloaded day are spent on coverage the catalog already holds.
- *Required fix:* Keep SLT Sync Global Daily - SLT-SYN-04 needs a simple (not variation) product so the five _renewal_sync_* metas read cleanly - but drop SLT Sync Excl Probe entirely and delete its half of SLT-SYN-03 (steps 10-16, screenshots 02/03). Point SLT-SYN-03's exclusivity claim at SLT-PROD-05's evidence ids instead. Conversely, do not spend a checkout on the SLT-PROD-15 'No Sync' variation as a scheduling control; assert it by meta + SegmentPlan::getConfig()===null only, and let SLT Sync Global Daily carry the purchased control.

---
## Objective
Create the two NEW products this dimension owns, because the canonical catalog deliberately reserves its only month product and its only week product for flexible sync and therefore leaves two gaps: (a) there is no NON-flex product with a cycle of 3+ days, which is required to isolate what PLAIN global `sync_to_billing_cycle` does on its own, and (b) there is no product carrying a flex-exclusivity trigger that this group is allowed to buy, since `SLT Renewal Price Step` belongs to `slt-core`. Both products are marked NEW here and created with explicit steps; no existing catalog product is modified.

## Scope
- Gateway: Stripe test
- Checkout: N/A (creation only)
- Account: N/A
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 (conventions: `SLT <Name>` title, `slt-<name>` slug, Virtual, stock management off) and SLT-SETUP-02 (global sync OFF baseline) complete.
- SLT-PROD-14 complete — `SLT Sync Global Daily` deliberately mirrors its day/3 cycle so it is an exact non-flex control for `SLT Flex Daily Two Seg` and `SLT Flex Daily Next Cycle`.
- Both products are declared NEW by this task. No other group may buy them.
- Code facts (verified): `SegmentPlan::getNominalCycleDays('day', 3) = 3`, which is exactly `MIN_CYCLE_DAYS`, so a day/3 product IS segmentable; `SegmentPlan::getConfig()` returns `null` whenever `_enable_renewal_price === 'yes'`, which is what makes the second product a valid exclusivity canvas.

## Test data
| Item | Value |
|---|---|
| Product A | SLT Sync Global Daily / slug `slt-sync-global-daily` — Simple, Virtual, subscription, day/3, length 0, trial 0, no signup fee, NO flex sync |
| Product B | SLT Sync Excl Probe / slug `slt-sync-excl-probe` — Simple, Virtual, subscription, day/3, length 0, trial 0, no signup fee, **Different Renewal Price** ON |
| Account | admin / @GuDw(0$K7M9t8ehjqDb4Vwj |
| Coupon | N/A |
| Card | N/A |
| Amounts | A: regular price $18.00, expected first charge $18.00, renewal $18.00 every 3 days. B: regular price $16.00, renewal price $24.00 after 2 billing periods, expected first charge $16.00 |

## Steps
1. `PREV=$(/usr/local/bin/mailpit-agent latest-id)`; record it.
2. `agent-browser skills get core` if not already loaded this session, then `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `agent-browser --session admin snapshot -i`.
3. **Product title**: `SLT Sync Global Daily`. **Description**: `SLT window product. Non-flex day/3 control for global renewal sync. Owned by SLT-SYN. Delete on 2026-08-11.`
4. Product type **Simple product**; tick **Virtual**; leave **Downloadable** unticked; tick the header checkbox **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `18.00`; leave **Sale price** empty.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `3`; **Subscription Length** = `0`; **Trial Length** = `0`; **Trial Period** = `Day`; **Sign-up Fee ($)** empty; **Different Renewal Price** UNTICKED.
7. Confirm the **Flexible Renewal Sync to Next Billing Cycle** checkbox IS offered (day/3 = 3 nominal days = exactly `MIN_CYCLE_DAYS`) and leave it UNTICKED. Screenshot `SLT-SYN-03-01-global-daily-flex-offered-unticked.png` — this is the evidence that its absence of sync in later tasks is a configuration choice, not a UI limitation.
8. **Inventory** tab: leave **Manage stock?** unticked, **Stock status** = In stock.
9. Set the URL slug to `slt-sync-global-daily`. **Publish**. Reload the edit screen and re-verify every field.
10. New product: **Product title** `SLT Sync Excl Probe`. **Description**: `SLT window product. Day/3 with Different Renewal Price — flex-sync exclusivity canvas. Owned by SLT-SYN. Delete on 2026-08-11.`
11. **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**. **General** tab **Regular price ($)** = `16.00`.
12. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `3`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty.
13. BEFORE ticking the renewal-price box, screenshot the panel showing the **Flexible Renewal Sync to Next Billing Cycle** checkbox present: `SLT-SYN-03-02-excl-probe-flex-visible-before.png`.
14. Tick **Different Renewal Price**; in the revealed block set **Renewal Price ($)** = `24.00` and **Apply Renewal Price After** = `2`.
15. Re-snapshot and screenshot: the **Flexible Renewal Sync** section must now be HIDDEN. Save as `SLT-SYN-03-03-excl-probe-flex-hidden-after.png`.
16. Slug `slt-sync-excl-probe`. **Publish**. Reload and re-verify.
17. Verify metas from WP root `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public`:
   - `wp post meta list <SLT Sync Global Daily ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_arraysubs_flex_sync_enabled,_regular_price --allow-root`
   - `wp post meta list <SLT Sync Excl Probe ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_enable_renewal_price,_renewal_price,_renewal_price_after,_arraysubs_flex_sync_enabled,_regular_price --allow-root`
18. Confirm the segment-plan resolver agrees: `wp eval 'use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan; var_dump(SegmentPlan::getNominalCycleDays("day",3)); var_dump(SegmentPlan::getConfig(<SLT Sync Global Daily ID>)); var_dump(SegmentPlan::getConfig(<SLT Sync Excl Probe ID>));' --allow-root`.
19. As `--session guest`, open `https://mirror-help.arrayhash.com/slt-sync-global-daily` and `https://mirror-help.arrayhash.com/slt-sync-excl-probe`, confirm each renders a recurring "every 3 days" schedule summary, and do NOT add anything to the cart.
20. Append both product IDs to the `slt-catalog-registry` page, tagging A as `sync-group non-flex control` and B as `sync-group exclusivity canvas`.
21. `/usr/local/bin/mailpit-agent latest-id` must equal `$PREV`. `agent-browser close --all`.

## Expected results
1. `SLT Sync Global Daily` published: type `simple`, virtual, slug exactly `slt-sync-global-daily`, `_is_subscription=yes`, `_subscription_period=day`, `_subscription_interval=3`, `_subscription_length=0`, `_trial_length=0`, `_signup_fee` absent or `0`, `_enable_renewal_price` absent, `_regular_price=18.00`, and `_arraysubs_flex_sync_enabled` ABSENT.
2. The Flexible Renewal Sync checkbox is visibly OFFERED on product A and deliberately left unticked (screenshot captured).
3. `SLT Sync Excl Probe` published: slug `slt-sync-excl-probe`, `_subscription_period=day`, `_subscription_interval=3`, `_enable_renewal_price=yes`, `_renewal_price=24`, `_renewal_price_after=2`, `_regular_price=16.00`, `_arraysubs_flex_sync_enabled` ABSENT.
4. On product B the Flexible Renewal Sync section visibly disappears the moment **Different Renewal Price** is ticked.
5. `SegmentPlan::getNominalCycleDays('day', 3)` returns `3`.
6. `SegmentPlan::getConfig()` returns `NULL` for BOTH products — for A because the feature was never enabled, for B because of the renewal-price exclusivity. Both nulls are expected and are recorded with their distinct reasons.
7. Both storefront pages render an "every 3 days" recurring summary; product B additionally advertises the stepped renewal price.
8. Neither product was published with an admin error notice; both are status `publish`.
9. Baseline billing contract for later tasks, with global sync OFF (SLT-SETUP-02 baseline): product A bought at checkout time T renews at `T + 3 days` (anniversary time, NOT site-local midnight). With global sync ON, the same purchase on 2026-08-01 instead yields `_renewal_sync_cycle_start_date = 2026-07-31 18:00:00` UTC and `_next_payment_date = 2026-08-03 18:00:00` UTC (= 2026-08-04 00:00 site).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Both product publishes and the storefront views (nothing added to cart, no order placed) | — | — | `/usr/local/bin/mailpit-agent latest-id` at step 21 must equal `$PREV` from step 1 |

## Evidence to capture
- Screenshots: `SLT-SYN-03-01-global-daily-flex-offered-unticked.png`, `SLT-SYN-03-02-excl-probe-flex-visible-before.png`, `SLT-SYN-03-03-excl-probe-flex-hidden-after.png`, `SLT-SYN-03-04-frontends.png`.
- Both product IDs; both `wp post meta list` outputs; the step-18 `wp eval` output showing `3` and two `NULL`s.
- Registry page rows for both products; `$PREV`.

## Pass criteria
- [ ] SLT Sync Global Daily published day/3 at $18.00 with no flex meta and no renewal-price meta
- [ ] Flex checkbox visibly offered on product A and left unticked (evidence captured)
- [ ] SLT Sync Excl Probe published day/3 at $16.00 with renewal price 24.00 after 2
- [ ] Flex section visibly hidden by Different Renewal Price on product B
- [ ] getNominalCycleDays('day',3) === 3 and getConfig() NULL for both products
- [ ] Both front ends show "every 3 days"
- [ ] Both IDs appended to slt-catalog-registry with owner tags
- [ ] Zero mail; nothing added to cart; no existing product touched

## Isolation / teardown
- State handoff: `SLT Sync Global Daily` is bought exactly ONCE, by SLT-SYN-04, as `slt-flex`, while global sync is temporarily ON. `SLT Sync Excl Probe` is bought exactly ONCE, by SLT-SYN-09, as `slt-flex`, after its flex meta has been force-set by WP-CLI — proving the checkout pipeline refuses a plan the admin UI would never have let you create. No other task may purchase either product.
- Cross-purpose note recorded deliberately: both products are bought by `slt-flex` even though they are not flexible-sync products, because they exist solely to serve this dimension's controls and `slt-core` is reserved for the checkout group's workhorse purchases. This is the only account-purpose deviation in the sync dimension and is declared here once.
- Restores: nothing global changed. Both products are deleted by SLT-SETUP-99 (they match the `SLT ` title prefix, so the existing product-search teardown already covers them).

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
