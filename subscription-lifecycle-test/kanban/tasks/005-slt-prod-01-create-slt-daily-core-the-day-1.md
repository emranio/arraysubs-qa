---
id: 5
title: SLT-PROD-01 Create SLT Daily Core, the day/1 workhorse subscription product
status: todo
priority: critical
created: 2026-08-02T03:43:03.30633431+02:00
updated: 2026-08-02T03:43:13.207187376+02:00
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

> **SLT-PROD-01** · group `catalog` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · shared-global-setting** — with `SLT-SYN-04`, `SLT-SETUP-05`, `SLT-SETUP-02`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`

- *Problem:* renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.
- *Required fix:* Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

**`unrated` · impossible-timing** — with `SLT-SYN-04`, `SLT-PROD-16`, `SLT-PROD-14`, `SLT-PROD-06`

- *Problem:* SLT-SYN-04's global-sync-ON window is not just a checkout hazard: any renewal that Action Scheduler processes while the switch is ON can pick up sync context and be re-anchored from its checkout anniversary to the site-local midnight boundary. By the time SLT-SYN-04 can realistically run (after SETUP-01/02/PROD-16/SETUP-05/SYN-03 have completed), several day/1 and day/2 subscriptions bought on D0/D1 already have renewals due, and their anniversary times are whatever clock time those checkouts happened. If a checkout was done at 09:30 site on D0, its renewal fires at 09:30 site the next day - inside a morning ON window.
- *Required fix:* Two-part rule. (1) Every SLT purchase on D0, D1 and D2 must be executed AFTER 12:00 site time, so all anniversary renewals land in the afternoon. (2) SLT-SYN-04's ON bracket is fixed at 09:00-11:00 site on D3 and no `wp action-scheduler run` of any kind may be issued during it. Record the exact UTC open/close timestamps of the bracket in the evidence root as SLT-SYN-04-bracket.txt so any anomalous renewal in that interval can be attributed.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-02`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

---
## Objective
Create the plainest possible recurring product — day period, interval 1, no trial, no signup fee, no length limit, no flexible sync — so that renewals genuinely fire on their own once per day for the whole window and every other test has a known-good control to compare against.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete (conventions, evidence root, registry page).
- Billing period `day` with interval 1 is chosen deliberately: the window is 10 real days, and `arraysubs_calculate_next_payment_from_date()` gives `start + 1 day`, so this product produces up to 9 unattended renewals inside D0..D9 with zero time-travel.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core / slug `slt-daily-core` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $10.00; expected first charge $10.00; expected renewal $10.00/day |

## Steps
1. Capture `mailpit-agent latest-id` before any admin save.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `agent-browser --session admin snapshot -i`.
3. **Product title**: `SLT Daily Core`.
4. **Description**: `SLT window product. Daily recurring workhorse. Delete on 2026-08-11.`
5. In the **Product data** panel keep the type dropdown on **Simple product**; tick **Virtual**; leave **Downloadable** unticked.
6. Tick the header checkbox **Subscription [ArraySubs]** (this writes `_is_subscription=yes`; it renders next to Virtual/Downloadable and is only offered for simple and variable types).
7. **General** tab: **Regular price ($)** = `10.00`. Leave **Sale price** empty. Note: `SubscriptionProducts\Services\Hooks::getPostedSubscriptionProductValidationErrors()` blocks the save with "Subscription products must have a valid regular price greater than zero" if this is 0 or empty.
8. Open the **Subscription [ArraySubs]** tab and set: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0` (never expires); **Trial Length** = `0`; **Trial Period** = `Day`; **Sign-up Fee ($)** = empty; **Different Renewal Price** = UNTICKED.
9. Confirm the **Flexible Renewal Sync to Next Billing Cycle** checkbox is visible but leave it UNTICKED — a 1-day nominal cycle is below `SegmentPlan::MIN_CYCLE_DAYS = 3`, so even if ticked `SegmentPlan::getConfig()` would return null. Screenshot this state.
10. **Inventory** tab: leave **Manage stock?** unticked, **Stock status** = In stock.
11. Set the URL slug to `slt-daily-core` in the sidebar Permalink field. Publish.
12. Reload the edit screen and confirm every subscription field survived the save.
13. Verify meta: `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_regular_price --allow-root`.
14. Open the storefront page `https://mirror-help.arrayhash.com/?p=<ID>` as `--session guest` and confirm the subscription price/schedule summary renders under the price.
15. Append the product ID to `slt-catalog-registry`.

## Expected results
1. Product published, type `simple`, virtual, slug exactly `slt-daily-core`.
2. `_is_subscription=yes`, `_subscription_period=day`, `_subscription_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_signup_fee` absent or `0`, `_enable_renewal_price` absent, `_regular_price=10.00`.
3. `_arraysubs_flex_sync_enabled` is absent.
4. The single-product page shows the recurring schedule text "every day" (rendered by `displaySubscriptionInfo()` at `woocommerce_single_product_summary` priority 11) and the add-to-cart button uses the subscription button text.
5. No admin error notice from `WC_Admin_Meta_Boxes` on save; the post status is `publish`, not silently held back by `preserveProductStatusForInvalidSubscriptionSave()`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | `mailpit-agent latest-id` after step 13 equals the id from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-01-01-general-tab.png`, `SLT-PROD-01-02-subscription-tab.png`, `SLT-PROD-01-03-frontend.png`.
- Product ID; `wp post meta list` output; any admin notice text; console errors on the product page.

## Pass criteria
- [ ] Published as simple + virtual + subscription with slug slt-daily-core
- [ ] All eight metas exactly as listed
- [ ] Flex sync meta absent
- [ ] Front end renders the daily recurring summary
- [ ] Zero mail, zero admin errors

## Isolation / teardown
- State handoff: this is THE control product. Use it for the guest->new checkout path, the block-vs-classic comparison, the Stripe SCA card path, the cancellation/reactivation flow, and as the non-flex baseline in gateway comparisons. Buy it with `slt-core` (or a guest email) only.
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
