---
id: 71
title: SLT-PROD-08 Create SLT Variable Daily with four subscription variations incl. a $0 probe
status: done
priority: high
created: 2026-08-02T03:43:09.287474248+02:00
updated: 2026-08-06T20:26:50.342500752+02:00
started: 2026-08-06T20:26:50.34249964+02:00
completed: 2026-08-06T20:26:50.34249964+02:00
tags:
    - setup
    - products
    - day-04
due: "2026-08-06"
estimate: 1h 30m
depends_on:
    - 10
    - 8
    - 21
claimed_by: wild-timber
claimed_at: 2026-08-06T20:26:50.342500642+02:00
class: standard
---

> **SLT-PROD-08** · group `catalog` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the variable subscription product with four variations that differ in billing interval, price, signup fee and trial, and use it to probe the $0-recurring branch: `isSubscriptionProductSaveRequest()` returns false when `product-type == 'variable'`, and `saveVariationMeta()` performs no price validation at all, so a $0 variation can be saved even though the identical simple product cannot. Whether that $0 variation is purchasable is a genuine open question this variation exists to answer.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront variation verification only)
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
1. Record `M0=$(mailpit-agent latest-id)`.
2. `agent-browser --session admin-SLT-PROD-08 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Variable Daily`. **Description**: `SLT window product. Variable subscription, four daily tiers. Delete on 2026-08-15.`
4. Set the product type dropdown to **Variable product**; tick **Virtual**; tick the header checkbox **Subscription [ArraySubs]**.
5. **Attributes** tab: add `SLT Tier` values, tick both flags, Save, and capture `SLT-PROD-08-01-attributes.png`.
6. **Variations** tab: **Generate variations** (or add four manually) so all four `SLT Tier` values exist.
7. Configure Starter exactly and capture `SLT-PROD-08-02-variation-starter.png`.
8. Configure Plus exactly and capture `SLT-PROD-08-03-variation-plus.png`.
9. Configure Trialist exactly, prove flex hidden, and capture `SLT-PROD-08-04-variation-trialist-flex-hidden.png`.
10. Configure Zero Probe exactly and capture `SLT-PROD-08-05-variation-zero-probe.png` before Save; save variations.
11. Reload the edit screen and check whether the `0.00` price survived. If WooCommerce or ArraySubs rejected it, record the exact message and the resulting stored price — that result IS the finding; do not force it with WP-CLI.
12. Slug `slt-variable-daily`. Publish.
13. `wp post meta list <VARIATION_ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_signup_fee,_regular_price --allow-root` for each of the four variation IDs.
14. Before any storefront/cart/downstream checkout access, append only the variable parent product ID to Shop Access rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**. Do not append variation IDs. Preserve every other field and every prior exclusion; re-read the raw option and require the parent ID exactly once.
15. In `guest-SLT-PROD-08`, open the cache-busted product, switch through all four tiers, and capture each distinct rendered state as `SLT-PROD-08-06a-frontend-starter.png` through `-06d-frontend-zero-probe.png`.
16. Append parent/four variation IDs and verified Shop Access exclusion to the registry. Inspect the complete `M0` delta and require zero task-attributable mail; classify background mail. Close only `admin-SLT-PROD-08` and `guest-SLT-PROD-08`, independently review product/meta/storefront evidence, move the card through `review` to `done`, and ensure Review returns to zero. If a live publish/storefront/AJAX failure occurs, create a standalone issue with this task/plan, product/variation IDs, user `N/A`, exact route/context, reproduction, expected/actual, UI/meta/network proof and a working tier counterexample; never add a kanban bug card.

## Expected results
1. Parent published as `variable`, virtual, slug `slt-variable-daily`, with `_is_subscription=yes` on the parent and on all four variations.
2. Starter: period day, interval 1, price 6.00, no trial, no fee.
3. Plus: period day, interval 2, price 11.00, `_signup_fee=4`.
4. Trialist: period day, interval 1, price 9.00, `_trial_length=3`, `_trial_period=day`; its flex block is hidden.
5. Zero Probe: either `_regular_price=0` is stored (variation-level saves are unvalidated) — record it as an asymmetry against the simple-product rule — or the save was rejected, in which case the exact rejection text and the surviving price are recorded.
6. The variable parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access; variation IDs are not added separately.
7. The storefront updates price and subscription summary correctly for each of the four tier selections.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and variation saves | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots `SLT-PROD-08-01` through `-05` plus four distinct `-06a` through `-06d` storefront states named in the steps.
- Parent ID + four variation IDs; four meta dumps; raw Shop Access rule showing the parent ID exactly once; any validation text for the zero-price variation; console/AJAX errors during **Save changes** on the Variations tab.

## Pass criteria
- [ ] Parent variable + subscription with attribute SLT Tier used for variations
- [ ] Four variations exist with the exact price/interval/trial/fee matrix
- [ ] `_is_subscription=yes` propagated to all four variations
- [ ] Zero Probe outcome recorded either way with evidence
- [ ] Front-end summary changes per tier
- [ ] Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access
- [ ] Zero mail
- [ ] Exact sessions closed and complete product evidence reviewed to done

## Isolation / teardown
- State handoff: buy variations as `slt-core` except Trialist, which belongs to `slt-trial`. Plan-switching tasks may use variation IDs as switch targets — `getAvailableSwitchOptions()` reads `_variation_id` first, and the Linked Products search action is `woocommerce_json_search_products_and_variations`, so variations are legitimate targets.
- Restores: SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. Parent and variations are deleted by SLT-SETUP-99B.

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
Missed-window note: not started on the D4 site-local day. Downstream card 79 remains source-blocked until this product fixture is actually created and published on a valid day.

[[2026-08-06]] Thu 20:26
Failure closeout on Thursday, August 6, 2026. Live admin creation flow produced parent product 13012 and four intended child variations (13013, 13015, 13017, 13019), but all landed in post_status=trash and reopening /wp-admin/post.php?post=13012&action=edit returned a WordPress admin error. Standalone issue filed: qa/subscription-lifecycle-test/issues/SLT-PROD-08-variable-subscription-draft-is-trashed-on-save.md. Evidence: /home/server-manager/slt-evidence/SLT-PROD-08-01-attributes.png and /home/server-manager/slt-evidence/SLT-PROD-08-07-edit-error.png.
