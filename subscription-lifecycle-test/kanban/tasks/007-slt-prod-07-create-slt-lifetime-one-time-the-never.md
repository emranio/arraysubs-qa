---
id: 7
title: SLT-PROD-07 Create SLT Lifetime One Time, the never-renews negative control
status: todo
priority: high
created: 2026-08-02T03:43:03.472152178+02:00
updated: 2026-08-02T03:43:13.393853387+02:00
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

> **SLT-PROD-07** · group `catalog` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

---
## Objective
Provide the negative control that must NEVER produce a renewal, a renewal invoice, a renewal reminder, or a next-payment date. `_subscription_period = lifetime` forces `_subscription_interval=1` and `_subscription_length=0` on save, `arraysubs_calculate_next_payment_from_date()` returns an empty string, `arraysubs_calculate_end_date_from_length()` returns null, and both the core and pro sync paths bail on lifetime.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: both (pro view supplies the flex negative)

## Preconditions
- SLT-SETUP-01 complete.
- Validation note: the billing-interval range check is skipped for lifetime, but the regular-price > 0 rule still applies.

## Test data
| Item | Value |
|---|---|
| Product | SLT Lifetime One Time / slug `slt-lifetime-one-time` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $49.00; charge today $49.00; expected renewals: none, ever |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Lifetime One Time`. **Description**: `SLT window product. Lifetime deal, must never renew. Delete on 2026-08-11.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `49.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Lifetime Deal`. Leave **Billing Interval** and **Subscription Length** as displayed — the saver overwrites them to 1 and 0. **Trial Length** = `0`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked.
7. Screenshot the panel: the **Flexible Renewal Sync to Next Billing Cycle** section must be hidden for the lifetime period (`$arraysubs_flex_section_hidden = ... || 'lifetime' === $arraysubs_flex_period`). This is the third exclusivity negative required by the catalog.
8. Slug `slt-lifetime-one-time`. Publish. Reload and re-verify.
9. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_regular_price,_arraysubs_flex_sync_enabled --allow-root`.
10. As `--session guest`, open the product page and confirm the summary shows a one-time/lifetime purchase, not a recurring schedule.
11. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-lifetime-one-time`.
2. `_subscription_period=lifetime`, `_subscription_interval=1` (force-written), `_subscription_length=0` (force-written), `_regular_price=49.00`, `_trial_length=0`.
3. `_arraysubs_flex_sync_enabled` absent and the flex section hidden in the UI.
4. Product page shows a lifetime/one-time summary with no "every N days" phrasing.
5. Contract for the buying task: after checkout the subscription must have an EMPTY `_next_payment_date`, no `_end_date`, no scheduled `arraysubs_generate_renewal_invoice` or `arraysubs_process_renewal` action in Scheduled Actions, and no renewal-related mail for the whole 12-day watch.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-07-01-subscription-tab-lifetime.png`, `SLT-PROD-07-02-flex-hidden-by-lifetime.png`, `SLT-PROD-07-03-frontend.png`.
- Product ID; meta list output showing the forced interval/length.

## Pass criteria
- [ ] Published with period lifetime and price 49.00
- [ ] Interval forced to 1 and length forced to 0 on save
- [ ] Flex sync hidden by lifetime
- [ ] Front end shows no recurring schedule
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy as `slt-core` on D0 and then leave it alone. Every daily renewal-watch task from D1 to D12 must re-assert that this subscription still has no next-payment date, no renewal order, and no renewal mail. Because lifetime products are never sync-eligible, this is also a valid Paddle target if a second Paddle case is needed.
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
