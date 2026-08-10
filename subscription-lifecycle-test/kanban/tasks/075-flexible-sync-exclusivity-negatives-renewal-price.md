---
id: 75
title: 'Flexible sync exclusivity negatives: renewal-price, trial and lifetime products refuse the plan even when meta is force-set'
status: done
priority: high
created: 2026-08-02T03:43:09.548771473+02:00
updated: 2026-08-06T20:33:43.58245837+02:00
started: 2026-08-06T20:33:43.582457549+02:00
completed: 2026-08-06T20:33:43.582457549+02:00
tags:
    - renewal-sync
    - day-04
due: "2026-08-06"
estimate: 2h
depends_on:
    - 11
    - 12
class: standard
---

> **SLT-SYN-11** · group `sync` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the three exclusivity rules of Flexible Renewal Sync twice over: the admin UI must not offer the feature for a Different Renewal Price, free-trial or Lifetime product, and checkout must still refuse the plan when `_arraysubs_flex_sync_enabled=yes` is force-written by WP-CLI.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered `slt-excl` (CREATED HERE); card `4242 4242 4242 4242`
- Plugins: both

## Preconditions
- SLT-SETUP-02 (global sync OFF) and SLT-SETUP-03 done. Buy after 12:00 site.
- This task **creates** three probes and `slt-excl` / `slt-excl@example.test` (matrix password/billing). Fresh artifacts are required: `SLT Renewal Price Step`, `SLT Trial Four Day` and `SLT Lifetime One Time` carry live subs and must not be meta-surgered.
- A and B are **day / 5** (nominal 5 > `MIN_CYCLE_DAYS` 3) so exclusivity is the ONLY disqualifier. C is `lifetime`, which also yields nominal 0 — record both reasons.
- Sessions are `admin-SLT-SYN-11` and authenticated-customer `customer-SLT-SYN-11`; never reuse a guest-labelled session for the logged-in account. Both browser and persistent carts must be empty before, between, and after the three purchases.

## Test data
| Item | Value |
|---|---|
| A | `SLT Excl Renewal Price Probe`, day/5, $20.00, Different Renewal Price ON, $25 after 1 |
| B | `SLT Excl Trial Probe`, day/5, $20.00, Trial 2 Day |
| C | `SLT Excl Lifetime Probe`, `Lifetime`, $20.00 |

## Steps
1. Set `SUBCOUNT_BEFORE` to the exact current `arraysubs_data` count and `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-SYN-11`, create `slt-excl` at `/wp-admin/user-new.php` (Customer, **Send User Notification** unticked, billing per SLT-SETUP-03); record strict numeric `USER_ID`, login/email, and role. Classify exactly one admin-addressed `New User Registration` after `USER_PRE` and prove there is no customer account/password mail. Then set `M0=$(mailpit-agent latest-id)` as the product/surgery baseline.
2. In `admin-SLT-SYN-11`, create the probes at `/wp-admin/post-new.php?post_type=product`: Simple, Virtual, tick **Subscription [ArraySubs]**, price `20.00`, description `SLT probe. Delete 2026-08-15.`, slugs `slt-excl-renewal-price-probe`/`-trial-probe`/`-lifetime-probe`.
3. On each **Subscription [ArraySubs]** tab set the schedule, then the disqualifier LAST: A **Different Renewal Price** ($25.00 after 1); B **Trial Length** 2 Day; C **Billing Period** `Lifetime`. Capture the three panels with **Flexible Renewal Sync** absent as `SLT-SYN-11-01-flex-absent-renewal-price.png`, `-02-flex-absent-trial.png`, and `-03-flex-absent-lifetime.png`. Publish. Record exact numeric product IDs in `A_ID`, `B_ID`, and `C_ID`, and abort unless all three match `^[0-9]+$` and are distinct.
4. Force the plan behind the UI on all three: iterate `for ID in "$A_ID" "$B_ID" "$C_ID"; do ...; done`, running one `wp post meta update "$ID" ... --allow-root` per exact key: `_arraysubs_flex_sync_enabled=yes`, `_arraysubs_flex_sync_seg1_end=1`, `_arraysubs_flex_sync_seg2_end=3`, and all three `_arraysubs_flex_sync_segN_active=yes`.
5. Run `wp eval "foreach ([(int) $A_ID,(int) $B_ID,(int) $C_ID] as \$id) { var_dump(\$id, \\ArraySubsPro\\Features\\FlexibleRenewalSync\\Services\\SegmentPlan::isEnabled(\$id), \\ArraySubsPro\\Features\\FlexibleRenewalSync\\Services\\SegmentPlan::getConfig(\$id)); }" --allow-root`.
6. Before any storefront/cart/checkout access, capture the exact raw Shop Access option to `/home/server-manager/slt-evidence/SLT-SYN-11-shop-access-before.txt`, append only all three probe parent IDs to rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**, and capture the saved admin state as `SLT-SYN-11-03a-shop-access.png`. Preserve every other field and every prior exclusion; re-read to `SLT-SYN-11-shop-access-after.txt`, diff the snapshots, and require each new ID exactly once. Reconcile the complete `M0` delta through this step and require zero task-attributable mail before setting `PRE_A`.
7. In `customer-SLT-SYN-11`, log in as `slt-excl`, require both carts empty, and capture `SLT-SYN-11-03b-cart-empty-before.png`. For A, B, and C in that order, set a fresh baseline only after the prior delta and both carts are clear (`PRE_A`, `PRE_B`, `PRE_C`); add only the matching numeric product with a cache-busting request; handle any one-click checkout redirect; capture its unpopulated gateway list as `SLT-SYN-11-04-checkout-gateways-renewal-price.png`, `-05-checkout-gateways-trial.png`, or `-06-checkout-gateways-lifetime.png`; fill the hosted 4242 card without capturing it; pay; record numeric `ORDER_A/B/C`; and capture safe receipts as `-04a-receipt-renewal-price.png`, `-05a-receipt-trial.png`, and `-06a-receipt-lifetime.png`. Resolve each `SUB_A/B/C` only from its exact order's `_subscription_ids` JSON with a strict one-element numeric guard; require distinct subscriptions, reverse parent/customer/product linkage, and cumulative `SUBCOUNT_AFTER_A/B/C == SUBCOUNT_BEFORE+1/+2/+3`. A and C each require the complete four-message delta: WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup. B requires its exact trial-started message plus both task-attributable Woo order messages, with no customer/admin active-subscription pair. Save/show every ID and classify background mail before continuing. Capture final empty state as `SLT-SYN-11-06b-cart-empty-after.png`.
8. For each exact numeric pair, run `wp post meta list "$SUB_A" --allow-root` (then B/C) and select only `_renewal_sync_` rows with `rg`. Resolve the matching product line-item ID from its numeric order/product relationship via `wp eval` argv, dump that exact item's metadata the same way, and require zero `_renewal_sync_*` rows on all six objects; never use recency or a literal placeholder. Record exact status/amount/date/payment values. Query Action Scheduler by numeric subscription args: publish A's invoice/charge IDs, gates, and final-five-minute deadlines for the D9/D10 watcher; publish B's trial-conversion invoice/charge IDs, gates, and deadlines for the D6 watcher; prove C has no renewal/reminder/invoice/charge action. Append all IDs and handoffs to the registry/D04 report.
9. If any live assertion fails, create a standalone `issues/SLT-SYN-11-<concise-slug>.md` (never a kanban bug card) containing this progress task/stage and plan path; affected product/order/subscription/action IDs; `USER_ID`, login/email/role; exact URLs and browser/session context; reproduction; expected/actual; UI, option, meta, scheduler, Mailpit, and screenshot proof; and the other probe(s) as counterexamples where applicable. Continue unaffected probes. Close only `customer-SLT-SYN-11` and `admin-SLT-SYN-11`, independently review all D4 evidence and handoffs, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. All three panels render with NO **Flexible Renewal Sync** section — suppressed by `_enable_renewal_price=yes` (A), `_trial_length>0` (B), `lifetime` (C).
2. `isEnabled()` is **true** on all three (the meta is really set) while `getConfig()` is **null**: exclusivity lives in `getConfig()`.
3. Neither the subscriptions nor their order line items carry `_renewal_sync_enabled`, `…_first_charge_mode`, `…_cycle_start_date`, `…_first_full_renewal_date` or `…_initial_recurring_amount`. No PHP notice or 4xx/5xx.
4. A: **$20.00**, `_next_payment_date` = checkout + 5 days (anniversary ~2026-08-11), not midnight. B: **$0.00**, card still collected, `arraysubs-trial`, trial ends 2026-08-08. C: **$20.00**, `_next_payment_date` EMPTY, no action.
5. All three probe parent IDs are each present exactly once in the preserved Shop Access exclusion list before checkout.
6. The carts are not sync-eligible, so `maybeHideUnsupportedRenewalSyncGateways()` never fires and **Paddle stays offered** at all three checkouts — the forced meta never reached gateway filtering.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC customer paid-order + WC New order + `new_subscription` + `admin_new_subscription` for A and C | A/C checkout | slt-excl / admin | order id / `New order #` / `is active` / `New subscription #` | reconcile the two separate complete four-message deltas after `PRE_A` / `PRE_C`; save/show all eight IDs |
| 2 | `trial_started` + both task-attributable Woo order messages for B; no active-subscription pair | B checkout | slt-excl / admin | `free trial for SLT Excl Trial Probe has started` plus exact order | `mailpit-agent wait-new "$PRE_B" 180 "free trial"`, then classify the full delta and save/show exact IDs |
| 3 | NONE from steps 2-5 | creation, surgery | — | — | Complete delta after `M0` through step 5; zero task-attributable mail, while unrelated/background mail is allowed and classified |
| 4 | WP New User Registration | setup before `M0` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |

## Evidence to capture
- Safe named `SLT-SYN-11-01` through `-06b` captures; no image may contain a populated card number.
- `SUBCOUNT_BEFORE` and +1/+2/+3 progression; numeric user/product/order/subscription/action IDs and bidirectional linkage; `isEnabled`/`getConfig` transcript; exact six-object meta dumps; before/after raw Shop Access snapshots/diff; `USER_PRE`, setup-mail ID, `M0`, `PRE_A/B/C`; cart proofs, checkout Mailpit IDs, future gates/deadlines, session/review proof.

## Pass criteria
- [ ] Flex section absent in the admin UI on all three probes
- [ ] `isEnabled()` true but `getConfig()` null on all three
- [ ] Zero `_renewal_sync_*` meta on the subscriptions and their order items
- [ ] A $20.00 anniversary; B $0.00 `arraysubs-trial`; C $20.00, empty next payment
- [ ] All three probe parent IDs are each present exactly once in the preserved Shop Access exclusion list
- [ ] Paddle still offered at all three; exactly the listed mails
- [ ] Setup mail isolated before `M0`; no customer account/password mail; cart and persistent-cart meta empty between and after all purchases
- [ ] All three subscriptions are +1/+2/+3 and bidirectionally linked; A/C four-message and B trial/Woo deltas reconciled; exact future action handoffs recorded; exact sessions closed and card reviewed to done

## Isolation / teardown
- New artifacts: 3 `SLT ` products, 1 `slt-` user, 3 subs, 3 orders — all matched by SLT-SETUP-99B's searches. Append every ID to the registry.
- Forced flex metas are LEFT in place as evidence; record them in the registry. Cart emptied between purchases; SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot; no other global setting changed.
- Handoff: B converts 2026-08-08, A renews ~2026-08-11 — D6..D10 watch consumes the exact registry action IDs/deadlines and must still see no `_renewal_sync_*` meta on renewal. C has no future action. The D4 execution card closes after that handoff and evidence review.

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

[[2026-08-06]] Thu 20:15
Missed-window note: not started before the D4 site-local rollover to 2026-08-07 00:14 +06. Do not backfill this as if it were still same-day D4 execution; keep in todo until a valid reschedule/next-day decision is made.

[[2026-08-06]] Thu 20:33
UNVERIFIED closeout on 2026-08-06: this D4 same-day execution window was missed after the site-local rollover into 2026-08-07. The card is closed rather than carried forward as if its original dated setup and downstream timings were still valid.
