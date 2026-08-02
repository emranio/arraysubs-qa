---
id: 75
title: 'Flexible sync exclusivity negatives: renewal-price, trial and lifetime products refuse the plan even when meta is force-set'
status: todo
priority: high
created: 2026-08-02T03:43:09.548771473+02:00
updated: 2026-08-02T03:43:20.283749971+02:00
tags:
    - renewal-sync
    - day-04
    - has-conflicts
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

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-13`, `SLT-CHK-08`, `SLT-CHK-13`, `SLT-SYN-07`, `SLT-SW-09`, `SLT-IMP-03`

- *Problem:* SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.
- *Required fix:* Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
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

## Test data
| Item | Value |
|---|---|
| A | `SLT Excl Renewal Price Probe`, day/5, $20.00, Different Renewal Price ON, $25 after 1 |
| B | `SLT Excl Trial Probe`, day/5, $20.00, Trial 2 Day |
| C | `SLT Excl Lifetime Probe`, `Lifetime`, $20.00 |

## Steps
1. `mailpit-agent latest-id` -> `M0`. Create `slt-excl` at `/wp-admin/user-new.php` (Customer).
2. Create the probes at `/wp-admin/post-new.php?post_type=product`: Simple, Virtual, tick **Subscription [ArraySubs]**, price `20.00`, description `SLT probe. Delete 2026-08-12.`, slugs `slt-excl-renewal-price-probe`/`-trial-probe`/`-lifetime-probe`.
3. On each **Subscription [ArraySubs]** tab set the schedule, then the disqualifier LAST: A **Different Renewal Price** ($25.00 after 1); B **Trial Length** 2 Day; C **Billing Period** `Lifetime`. Screenshot each panel with **Flexible Renewal Sync** absent. Publish.
4. Force the plan behind the UI on all three: `wp post meta update <ID> _arraysubs_flex_sync_enabled yes --allow-root`, plus `…seg1_end 1`, `…seg2_end 3`, all `_active` `yes`.
5. `wp eval 'use ArraySubsPro\…\SegmentPlan as S; foreach([A,B,C] as $id) var_dump(S::isEnabled($id), S::getConfig($id));' --allow-root`.
6. As `--session guest-SLT-SYN-11` log in as `slt-excl`; per probe add ONLY that product, open `/checkout/`, `snapshot -i`, screenshot the gateway list, pay, empty the cart.
7. Per subscription run `wp post meta list <SUB> --allow-root`, grep `_renewal_sync_`; same on each parent order line item.

## Expected results
1. All three panels render with NO **Flexible Renewal Sync** section — suppressed by `_enable_renewal_price=yes` (A), `_trial_length>0` (B), `lifetime` (C).
2. `isEnabled()` is **true** on all three (the meta is really set) while `getConfig()` is **null**: exclusivity lives in `getConfig()`.
3. Neither the subscriptions nor their order line items carry `_renewal_sync_enabled`, `…_first_charge_mode`, `…_cycle_start_date`, `…_first_full_renewal_date` or `…_initial_recurring_amount`. No PHP notice or 4xx/5xx.
4. A: **$20.00**, `_next_payment_date` = checkout + 5 days (anniversary ~2026-08-11), not midnight. B: **$0.00**, card still collected, `arraysubs-trial`, trial ends 2026-08-08. C: **$20.00**, `_next_payment_date` EMPTY, no action.
5. The carts are not sync-eligible, so `maybeHideUnsupportedRenewalSyncGateways()` never fires and **Paddle stays offered** at all three checkouts — the forced meta never reached gateway filtering.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription`, `admin_new_subscription`, Woo order mail (x3) | each checkout | slt-excl / admin | `is active`, `New subscription #` | `wait-new <prev> 180`, `list 30` |
| 2 | `trial_started` (B only) | B checkout | slt-excl | `free trial for SLT Excl Trial Probe has started` | `wait-new <prev> 180 "free trial"` |
| 3 | NONE from steps 2-5 | creation, surgery | — | — | `latest-id`==`M0` until step 6 |

## Evidence to capture
- `SLT-SYN-11-01/02/03-flex-absent-<probe>.png`, `-04/05/06-checkout-gateways-<probe>.png`.
- 3 product / 3 sub / 3 order IDs; `isEnabled`/`getConfig` transcript; meta dumps; Mailpit IDs.

## Pass criteria
- [ ] Flex section absent in the admin UI on all three probes
- [ ] `isEnabled()` true but `getConfig()` null on all three
- [ ] Zero `_renewal_sync_*` meta on the subscriptions and their order items
- [ ] A $20.00 anniversary; B $0.00 `arraysubs-trial`; C $20.00, empty next payment
- [ ] Paddle still offered at all three; exactly the listed mails

## Isolation / teardown
- New artifacts: 3 `SLT ` products, 1 `slt-` user, 3 subs, 3 orders — all matched by SLT-SETUP-99B's searches. Append every ID to the registry.
- Forced flex metas are LEFT in place as evidence; record them in the registry. Cart emptied between purchases; no global setting changed.
- Handoff: B converts 2026-08-08, A renews ~2026-08-11 — D6..D10 watch expects both, and must still see no `_renewal_sync_*` meta on renewal.

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
