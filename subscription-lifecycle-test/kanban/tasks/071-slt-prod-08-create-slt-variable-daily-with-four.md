---
id: 71
title: SLT-PROD-08 Create SLT Variable Daily with four subscription variations incl. a $0 probe
status: todo
priority: high
created: 2026-08-02T03:43:09.287474248+02:00
updated: 2026-08-02T03:43:19.833945001+02:00
tags:
    - setup
    - products
    - day-04
    - has-conflicts
due: "2026-08-06"
estimate: 1h 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-08** · group `catalog` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`high` · dependency-inversion (product creation after first consumer)** — with `SLT-PROD-04`, `SLT-PROD-05`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-11`, `SLT-PROD-15`

- *Problem:* The corrected calendar in plan-audit places several catalog tasks later than the first new-index task that depends on them. SLT-SETUP-04 (coupons) is D3 but SLT-CPN-01/02 need it on D1 18:00-19:00. SLT-PROD-05 is D3 but SLT-LIFE-05 buys it on D1. SLT-PROD-16 is D1 but SLT-DUN-01 (corrected to D2 13:00) and SLT-CHK-04 (D2) need it, and SLT-MYA-05 needs it on D2 morning. SLT-PROD-09 is D5 but SLT-CPN-04 (D3) and SLT-CHK-12 (D5) depend on it. SLT-PROD-10 and SLT-PROD-11 are D4 but SLT-CHK-13 (D4), SLT-CHK-10 (D5) and SLT-SW-09 (D4, which explicitly says PROD-11 must be done 'before this task starts on D4') need them earlier in the day or before. SLT-PROD-08 is D5 but SLT-CHK-11 buys its variations on D5. SLT-PROD-15 is D2 and SLT-SYN-13 buys its variations on D2 - correct only if SYN-02's audit sits strictly between them.
- *Required fix:* Adopt the rebalanced calendar in this report: SETUP-04 and PROD-05 to D1 morning; PROD-16 to D1 morning (ahead of SETUP-05, which also gains PROD-14 as a dependency per audit C03); PROD-02/03/09/15 and SYN-02 to D2 morning; PROD-04/10/11 to D3 after the SYN-04 bracket closes; PROD-08 to D4 morning. Add an explicit intra-day ordering line to every day's calendar row ('creations and audits before 12:00, purchases after 12:00') and make it a pass criterion that each consuming task quotes the creating task's registry entry.

---
## Objective
Provide the variable subscription product with four variations that differ in billing interval, price, signup fee and trial, and use it to probe the $0-recurring branch: `isSubscriptionProductSaveRequest()` returns false when `product-type == 'variable'`, and `saveVariationMeta()` performs no price validation at all, so a $0 variation can be saved even though the identical simple product cannot. Whether that $0 variation is purchasable is a genuine open question this variation exists to answer.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- All four variations use the `day` period on purpose: the window-wide rule reserves `week` for SLT-PROD-13 and `month` for SLT-PROD-12 only, so variety here comes from interval, price, fee and trial.
- UI contract: the **Subscription [ArraySubs]** product-data TAB is registered `show_if_simple` only. For a variable product you tick the header checkbox **Subscription [ArraySubs]** (which syncs `_is_subscription=yes` onto every variation on save) and then configure each variation in its own expanded variation panel.

## Test data
| Item | Value |
|---|---|
| Product | SLT Variable Daily / slug `slt-variable-daily`, attribute `SLT Tier` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | see the variation table |

| Variation (SLT Tier) | Regular price | Billing period | Interval | Length | Trial | Signup fee | Expected charge today |
|---|---|---|---|---|---|---|---|
| Starter | 6.00 | Day | 1 | 0 | 0 | — | $6.00 |
| Plus | 11.00 | Day | 2 | 0 | 0 | 4.00 | $15.00 |
| Trialist | 9.00 | Day | 1 | 0 | 3 day | — | $0.00 |
| Zero Probe | 0.00 | Day | 1 | 0 | 0 | — | $0.00 (probe) |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Variable Daily`. **Description**: `SLT window product. Variable subscription, four daily tiers. Delete on 2026-08-11.`
4. Set the product type dropdown to **Variable product**; tick **Virtual**; tick the header checkbox **Subscription [ArraySubs]**.
5. **Attributes** tab: **Add new** custom attribute, Name = `SLT Tier`, Value(s) = `Starter | Plus | Trialist | Zero Probe`, tick **Visible on the product page** and **Used for variations**. Save attributes.
6. **Variations** tab: **Generate variations** (or add four manually) so all four `SLT Tier` values exist.
7. Expand the **Starter** variation and set: **Regular price ($)** `6.00`; in the ArraySubs variation block set **Billing Period** `Day`, **Billing Interval** `1`, **Subscription Length** `0`, **Trial Length** `0`, **Trial Period** `Day`, **Sign-up Fee ($)** empty, **Different Renewal Price** unticked, **Flexible Renewal Sync** unticked.
8. **Plus**: Regular price `11.00`, Billing Period `Day`, Interval `2`, Length `0`, Trial `0`, **Sign-up Fee ($)** `4.00`.
9. **Trialist**: Regular price `9.00`, Billing Period `Day`, Interval `1`, Length `0`, **Trial Length** `3`, **Trial Period** `Day`, no signup fee. Confirm the variation's Flexible Renewal Sync block is hidden by the trial.
10. **Zero Probe**: Regular price `0.00`, Billing Period `Day`, Interval `1`, Length `0`, Trial `0`, no fee. Save variations.
11. Reload the edit screen and check whether the `0.00` price survived. If WooCommerce or ArraySubs rejected it, record the exact message and the resulting stored price — that result IS the finding; do not force it with WP-CLI.
12. Slug `slt-variable-daily`. Publish.
13. `wp post meta list <VARIATION_ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_signup_fee,_regular_price --allow-root` for each of the four variation IDs.
14. As `--session guest`, open the product page, switch the `SLT Tier` dropdown across all four values and screenshot the per-variation subscription summary each time (rendered from `addVariationSubscriptionData()`).
15. Append the parent ID and all four variation IDs to the registry.

## Expected results
1. Parent published as `variable`, virtual, slug `slt-variable-daily`, with `_is_subscription=yes` on the parent and on all four variations.
2. Starter: period day, interval 1, price 6.00, no trial, no fee.
3. Plus: period day, interval 2, price 11.00, `_signup_fee=4`.
4. Trialist: period day, interval 1, price 9.00, `_trial_length=3`, `_trial_period=day`; its flex block is hidden.
5. Zero Probe: either `_regular_price=0` is stored (variation-level saves are unvalidated) — record it as an asymmetry against the simple-product rule — or the save was rejected, in which case the exact rejection text and the surviving price are recorded.
6. The storefront updates price and subscription summary correctly for each of the four tier selections.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and variation saves | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-08-01-attributes.png`, `SLT-PROD-08-02-variation-starter.png`, `SLT-PROD-08-03-variation-plus.png`, `SLT-PROD-08-04-variation-trialist-flex-hidden.png`, `SLT-PROD-08-05-variation-zero-probe.png`, `SLT-PROD-08-06-frontend-tier-switching.png`.
- Parent ID + four variation IDs; four meta dumps; any validation text for the zero-price variation; console/AJAX errors during **Save changes** on the Variations tab.

## Pass criteria
- [ ] Parent variable + subscription with attribute SLT Tier used for variations
- [ ] Four variations exist with the exact price/interval/trial/fee matrix
- [ ] `_is_subscription=yes` propagated to all four variations
- [ ] Zero Probe outcome recorded either way with evidence
- [ ] Front-end summary changes per tier
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy variations as `slt-core` except Trialist, which belongs to `slt-trial`. Plan-switching tasks may use variation IDs as switch targets — `getAvailableSwitchOptions()` reads `_variation_id` first, and the Linked Products search action is `woocommerce_json_search_products_and_variations`, so variations are legitimate targets.
- Restores: nothing. Parent and variations deleted by SLT-SETUP-99.

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
