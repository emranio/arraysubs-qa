---
id: 27
title: 'SLT-SYN-03 Create the sync-group control product: SLT Sync Global Daily'
status: done
priority: critical
created: 2026-08-02T03:43:05.218059347+02:00
updated: 2026-08-05T21:37:49.349258739+02:00
started: 2026-08-03T07:16:10.174708801+02:00
completed: 2026-08-03T07:16:10.174708801+02:00
tags:
    - renewal-sync
    - day-01
due: "2026-08-03"
estimate: 45m
depends_on:
    - 10
    - 11
    - 22
    - 20
class: standard
---

> **SLT-SYN-03** · group `sync` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Create the one NEW product this dimension owns. The catalog has no non-flex product with a cycle of 3+ days, so `SLT Sync Global Daily` is required to isolate what plain global `sync_to_billing_cycle` does on its own. The former `SLT Sync Excl Probe` is deliberately not created: `SLT-PROD-05` already owns the identical Different Renewal Price versus Flexible Renewal Sync exclusivity proof.

## Scope
- Gateway: N/A (creation and storefront verification only)
- Checkout: N/A (creation only)
- Account: N/A
- Plugins: pro-required (the Flexible Renewal Sync control is supplied by ArraySubsPro)

## Preconditions
- SLT-SETUP-01 (conventions: `SLT <Name>` title, `slt-<name>` slug, Virtual, stock management off) and SLT-SETUP-02 (global sync OFF baseline) complete.
- SLT-PROD-14 complete — `SLT Sync Global Daily` deliberately mirrors its day/3 cycle so it is an exact non-flex control for `SLT Flex Daily Two Seg` and `SLT Flex Daily Next Cycle`.
- `SLT Sync Global Daily` is declared NEW by this task. No other group may buy it.
- `SLT-PROD-05` is complete and its screenshots for the renewal-price exclusivity proof are available to quote; this task must not recreate that canvas.
- Code fact (verified): `SegmentPlan::getNominalCycleDays('day', 3) = 3`, which is exactly `MIN_CYCLE_DAYS`, so a day/3 product is segmentable.

## Test data
| Item | Value |
|---|---|
| Product | SLT Sync Global Daily / slug `slt-sync-global-daily` — Simple, Virtual, subscription, day/3, length 0, trial 0, no signup fee, NO flex sync |
| Account | use the current local admin credential source in `AGENTS.md` |
| Coupon | N/A |
| Card | N/A |
| Amount | Regular price $18.00, expected first charge $18.00, renewal $18.00 every 3 days |

## Steps
1. `PREV=$(/usr/local/bin/mailpit-agent latest-id)`; record it.
2. `agent-browser skills get core` if not already loaded this session, then `agent-browser --session admin-SLT-SYN-03 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `agent-browser --session admin-SLT-SYN-03 snapshot -i`.
3. **Product title**: `SLT Sync Global Daily`. **Description**: `SLT window product. Non-flex day/3 control for global renewal sync. Owned by SLT-SYN. Delete on 2026-08-15.`
4. Product type **Simple product**; tick **Virtual**; leave **Downloadable** unticked; tick the header checkbox **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `18.00`; leave **Sale price** empty.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `3`; **Subscription Length** = `0`; **Trial Length** = `0`; **Trial Period** = `Day`; **Sign-up Fee ($)** empty; **Different Renewal Price** UNTICKED.
7. Confirm the **Flexible Renewal Sync to Next Billing Cycle** checkbox IS offered (day/3 = 3 nominal days = exactly `MIN_CYCLE_DAYS`) and leave it UNTICKED. Screenshot `SLT-SYN-03-01-global-daily-flex-offered-unticked.png` — this is the evidence that its absence of sync in later tasks is a configuration choice, not a UI limitation.
8. **Inventory** tab: leave **Manage stock?** unticked, **Stock status** = In stock.
9. Set the URL slug to `slt-sync-global-daily`. **Publish**. Reload the edit screen and re-verify every field.
10. Verify metas from WP root `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public`:
    `wp post meta list <SLT Sync Global Daily ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_enable_renewal_price,_arraysubs_flex_sync_enabled,_regular_price --allow-root`.
11. Confirm the segment-plan resolver agrees: `wp eval 'use ArraySubsPro\Features\FlexibleRenewalSync\Services\SegmentPlan; var_dump(SegmentPlan::getNominalCycleDays("day",3)); var_dump(SegmentPlan::getConfig(<SLT Sync Global Daily ID>));' --allow-root`.
12. Before any storefront or downstream checkout access, append only this parent product ID to Shop Access rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior exclusion; re-read the raw option and require the new ID exactly once.
13. As `--session guest-SLT-SYN-03`, open `https://mirror-help.arrayhash.com/product/slt-sync-global-daily/?slt-cache-bust=<timestamp>`, confirm it renders a recurring "every 3 days" schedule summary, capture `SLT-SYN-03-02-global-daily-frontend.png`, and do NOT add anything to the cart.
14. Append the product ID and verified Shop Access exclusion to the `slt-catalog-registry` page as `sync-group non-flex control`. In the same registry entry, link the `SLT-PROD-05` screenshot IDs as the authoritative exclusivity evidence and state that no `SLT Sync Excl Probe` product was created.
15. Inspect every Mailpit message newer than `$PREV`; require zero message attributable to this task and classify unrelated/background mail by its actual owner. Close only this task's named sessions by explicit name.

## Expected results
1. `SLT Sync Global Daily` published: type `simple`, virtual, slug exactly `slt-sync-global-daily`, `_is_subscription=yes`, `_subscription_period=day`, `_subscription_interval=3`, `_subscription_length=0`, `_trial_length=0`, `_signup_fee` absent or `0`, `_enable_renewal_price` absent, `_regular_price=18.00`, and `_arraysubs_flex_sync_enabled` ABSENT.
2. The Flexible Renewal Sync checkbox is visibly OFFERED on product A and deliberately left unticked (screenshot captured).
3. `SegmentPlan::getNominalCycleDays('day', 3)` returns `3`; `SegmentPlan::getConfig()` returns `NULL` because flex sync was deliberately left disabled.
4. The parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access, and the cache-busted storefront page renders an "every 3 days" recurring summary.
5. The product publishes without an admin error notice and has status `publish`.
6. The registry points to `SLT-PROD-05` for the already-covered exclusivity proof; no product with slug `slt-sync-excl-probe` exists.
7. Baseline billing contract for later tasks, with global sync OFF (SLT-SETUP-02 baseline): this product bought at checkout time T renews at `T + 3 days` (anniversary time, NOT site-local midnight). With global sync ON, the D3 purchase yields a site-midnight boundary according to the current cycle; `SLT-SYN-04` owns the exact live dates.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and storefront view (nothing added to cart, no order placed) | — | — | Complete delta after `$PREV`; zero message attributable to this task, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-SYN-03-01-global-daily-flex-offered-unticked.png`, `SLT-SYN-03-02-global-daily-frontend.png`.
- Product ID; `wp post meta list` output; the step-11 `wp eval` output showing `3` and `NULL`; raw Shop Access rule showing the ID exactly once.
- Registry row for the product plus the `SLT-PROD-05` evidence links; `$PREV`.

## Pass criteria
- [ ] SLT Sync Global Daily published day/3 at $18.00 with no flex meta and no renewal-price meta
- [ ] Flex checkbox visibly offered on product A and left unticked (evidence captured)
- [ ] `getNominalCycleDays('day',3) === 3` and `getConfig()` is `NULL`
- [ ] Front end shows "every 3 days"
- [ ] Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access
- [ ] Product ID appended to the registry and `SLT-PROD-05` cited for exclusivity
- [ ] No `slt-sync-excl-probe` product created
- [ ] Zero mail; nothing added to cart; no existing product touched

## Isolation / teardown
- State handoff: `SLT Sync Global Daily` is bought exactly once by `SLT-SYN-04` as `slt-flex`, while global sync is temporarily ON. No other task may purchase it.
- Cross-purpose note recorded deliberately: this product is bought by `slt-flex` even though it is not a flexible-sync product, because it exists solely as this dimension's control and `slt-core` is reserved for checkout workhorses.
- Restores: the product is deleted by `SLT-SETUP-99B` (it matches the `SLT ` title prefix); SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot.

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

[[2026-08-03]] Mon 07:16
## D01 late-morning execution — PASS

Published product 12125 (SLT Sync Global Daily / slt-sync-global-daily) as the authored simple virtual day/3 USD 18.00 non-flex control. Flex was visibly offered and deliberately left unticked; renewal-price and flex-enabled metas are absent; SegmentPlan returned nominal cycle 3 and getConfig NULL. Through Member Access -> Shop Access, appended only 12125 exactly once before the cache-busted guest view. Storefront rendered $18.00 / 3 days and the cart remained empty. Registry page 11847 contains one CATALOG (SLT-SYN-03) handoff citing the two SLT-PROD-05 exclusivity screenshots; slt-sync-excl-probe remains absent. Mailpit baseline/final 42DI8ELEccd8qFsaMtyeag; zero mail. Evidence: /home/server-manager/slt-evidence/SLT-SYN-03-facts.txt and screenshots SLT-SYN-03-01/-02. Both named browser sessions closed; no errors.
