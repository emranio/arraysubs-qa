---
id: 38
title: SLT-PROD-03 Create SLT Trial Four Day, the trial product with a live trial-ending reminder
status: todo
priority: high
created: 2026-08-02T03:43:06.193016054+02:00
updated: 2026-08-02T03:43:16.617376644+02:00
tags:
    - setup
    - products
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-03** · group `catalog` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

---
## Objective
Provide the trial product whose trial is long enough for the trial-ending reminder to actually fire inside the window. With `emails.trial_ending.days_before = 3`, a 4-day trial started on D0 puts the reminder on D1 (2026-08-02) and the conversion on D4 (2026-08-05) — both observable without touching the clock. It also carries `trials.require_payment_method = true`, so the card must still be collected on a $0.00 order.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Deliberate deviation from "e.g. 2 days": 4 days is the shortest trial that leaves a valid 3-days-before reminder window. SLT-PROD-02 keeps the 2-day case as the suppressed-reminder negative.
- Baseline `trials.require_payment_method=true` and `trials.one_trial_per_customer=false` are unchanged.

## Test data
| Item | Value |
|---|---|
| Product | SLT Trial Four Day / slug `slt-trial-four-day` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $12.00; charge today $0.00; first paid charge $12.00 on 2026-08-05; renewal $12.00/day |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Trial Four Day`. **Description**: `SLT window product. 4-day free trial, card required. Delete on 2026-08-11.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `12.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `4`; **Trial Period** = `Day`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked.
7. Confirm again that the **Flexible Renewal Sync** section is hidden while a trial is set; screenshot.
8. Slug `slt-trial-four-day`. Publish. Reload and re-verify.
9. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_trial_period,_regular_price,_signup_fee --allow-root`.
10. As `--session guest`, open the product page and confirm the trial is advertised in the price/schedule summary. Do not add to cart.
11. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-trial-four-day`.
2. `_trial_length=4`, `_trial_period=day`, `_subscription_period=day`, `_subscription_interval=1`, `_regular_price=12.00`.
3. Flex sync section hidden; `_arraysubs_flex_sync_enabled` absent.
4. Product page shows the 4-day free trial in the subscription summary.
5. Date arithmetic to be used by the buying task: trial start = checkout timestamp; `_trial_end_date` = start + 4 days = 2026-08-05; trial-ending reminder due 3 days before = 2026-08-02 (D1); first paid renewal invoice generated 6 hours before the due time on 2026-08-05.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-03-01-subscription-tab.png`, `SLT-PROD-03-02-flex-hidden-by-trial.png`, `SLT-PROD-03-03-frontend-trial-summary.png`.
- Product ID; meta list output.

## Pass criteria
- [ ] Published with trial 4 day and price 12.00
- [ ] Flex sync hidden by trial
- [ ] Front end advertises the trial
- [ ] Metas exactly as listed
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy this ONLY as `slt-trial`, and only AFTER the `SLT Free Signup Daily` purchase has been captured, so the two trial subscriptions on that account are distinguishable by product. The subscription is expected to sit in status `arraysubs-trial` until 2026-08-05, then become `arraysubs-active`. Emails downstream tasks must look for: `Your free trial for SLT Trial Four Day has started`, `Your trial for SLT Trial Four Day ends soon`, `Your trial for SLT Trial Four Day has converted to a paid subscription`.
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
