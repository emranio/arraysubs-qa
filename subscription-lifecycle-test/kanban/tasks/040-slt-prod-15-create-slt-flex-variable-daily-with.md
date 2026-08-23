---
id: 40
title: SLT-PROD-15 Create SLT2 Flex Variable Daily with per-variation flexible-sync configuration
status: todo
priority: medium
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - setup
    - products
    - day-02
due: "2026-08-25"
estimate: 1h
depends_on:
    - 10
    - 11
    - 22
class: standard
---

> **SLT-PROD-15** · group `catalog` · scheduled **D02** (2026-08-25)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Cover the variation-level flexible-sync configuration path, which is a separate code path from the simple-product one: the pro feature renders through `arraysubs_subscription_variation_fields_before_shipping` and saves through `saveVariationMeta()` with `$_POST[META][$loop]` array indexing. Three variations on one identical day/3 schedule differ ONLY in their segment plan, so any difference in first charge or next-payment date is attributable to the plan alone.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01, SLT-SETUP-02 and SLT-PROD-14 complete (PROD-14 establishes the expected simple-product behaviour this task compares against).
- `filterSupportsRenewalSync()` and `filterRenewalSyncContext()` both key off `subscription_data['product_id']`; for a variation purchase that resolves to the VARIATION id, so the plan must be stored on the variation, not the parent.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Flex Variable Daily / slug `slt2-flex-variable-daily`, attribute `SLT2 Sync Mode` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | all variations $12.00, day/3 |

| Variation (SLT2 Sync Mode) | Price | Period/Interval | Flex sync | Segments active | Boundaries | Expected next payment if bought 2026-08-25 |
|---|---|---|---|---|---|---|
| Full | 12.00 | Day / 3 | ON | 1, 2, 3 | seg1_end 1, seg2_end 2 | 2026-08-28 00:00 site, charge $12.00 |
| Next Cycle | 12.00 | Day / 3 | ON | 3 only | none | 2026-08-31 00:00 site, charge $12.00 |
| No Sync | 12.00 | Day / 3 | OFF | — | — | anniversary: checkout time + 3 days, charge $12.00 |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin-SLT-PROD-15 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT2 Flex Variable Daily`. **Description**: `SLT2 window product. Variation-level flexible renewal sync. Delete on 2026-09-05.`
4. Product type **Variable product**; tick the header checkbox **Subscription [ArraySubs]**. WooCommerce does not render a parent-level **Virtual** checkbox for variable products; virtuality is set independently on every variation in steps 7-9.
5. **Attributes** tab: custom attribute Name `SLT2 Sync Mode`, Values `Full | Next Cycle | No Sync`, tick **Visible on the product page** and **Used for variations**. Save attributes.
6. **Variations** tab: generate the three variations.
7. **Full** variation: tick **Virtual**; **Regular price ($)** `12.00`; ArraySubs block: **Billing Period** `Day`, **Billing Interval** `3`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked; tick **Flexible Renewal Sync to Next Billing Cycle**; leave all three legend toggles ON; set the handles so the legend reads `1` / `2` / `3`.
8. **Next Cycle** variation: tick **Virtual**; same price and schedule; tick flex sync; turn segment 1 and segment 2 toggles OFF so only **Charge full for next billing cycle** remains, legend `1 - 3`.
9. **No Sync** variation: tick **Virtual**; same price and schedule; leave **Flexible Renewal Sync** UNTICKED.
10. Save variations, reload the Variations tab and expand all three to confirm each legend/toggle state survived the AJAX save. Capture `SLT-PROD-15-01-variation-full-legend.png`, `-02-variation-next-cycle-legend.png`, and `-03-variation-no-sync-unticked.png` from the exact corresponding panels.
11. Slug `slt2-flex-variable-daily`. Publish.
12. For each variation id: `wp post meta list <VARIATION_ID> --keys=_virtual,_is_subscription,_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_active,_arraysubs_flex_sync_seg2_active,_arraysubs_flex_sync_seg3_active,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_regular_price --allow-root`.
13. Before any storefront/cart/downstream checkout access, append only the variable parent product ID to Shop Access rule `<D0_SHOP_ACCESS_RULE_ID>` under `exclusion_product_ids` through **Member Access → Shop Access**. Do not append the variation IDs. Preserve every other field and every prior exclusion; re-read the raw option and require the parent ID exactly once.
14. As `--session guest-SLT-PROD-15`, open the product page with `?slt2-cache-bust=<timestamp>`, select each `SLT2 Sync Mode` in turn, and add it. The frozen `checkout.one_click_mode=subscription_items` may redirect directly to block checkout; if so, record the checkout summary, then explicitly reopen `/cart/` and read that variation's subscription meta rows there. Only **Next Cycle** may show the bonus-access note; capture that exact cart as `SLT-PROD-15-04-cart-next-cycle-note.png`. Empty the cart before selecting the next variation.
15. Append the parent ID, all three variation IDs, and verified parent Shop Access exclusion to the registry.

## Expected results
1. Parent published as `variable`, with no fabricated parent-level virtual contract; all three variations are `_virtual=yes`, inherit `_is_subscription=yes`, and the parent slug is `slt2-flex-variable-daily`.
2. **Full**: `_arraysubs_flex_sync_enabled=yes`, all three `_active=yes`, `seg1_end=1`, `seg2_end=2`.
3. **Next Cycle**: `_arraysubs_flex_sync_enabled=yes`, `seg1_active=no`, `seg2_active=no`, `seg3_active=yes`.
4. **No Sync**: `_arraysubs_flex_sync_enabled` ABSENT (the saver deletes it when the box is unticked, while preserving any previously submitted boundary values).
5. All three legends survive the variation AJAX save and a full page reload.
6. In the 2026-08-25 cart, only **Next Cycle** shows the bonus-access note, naming the covered cycle starting 28 August 2026; **Full** and **No Sync** do not.
7. The variable parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access; variation IDs are not added separately.
8. Buying contract for `SLT-SYN-13`: **Full** renews 2026-08-28 00:00 site; **Next Cycle** renews 2026-08-31 00:00 site; **No Sync** is config-only and, if reasoned about, would renew at checkout time + 3 days rather than midnight.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Publish and cart previews | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-15-01-variation-full-legend.png`, `SLT-PROD-15-02-variation-next-cycle-legend.png`, `SLT-PROD-15-03-variation-no-sync-unticked.png`, `SLT-PROD-15-04-cart-next-cycle-note.png`.
- Parent + three variation IDs; three meta dumps; raw Shop Access rule showing the parent ID exactly once; any AJAX error from **Save changes** on the Variations tab.

## Pass criteria
- [ ] Three virtual variations saved with distinct segment plans on an identical day/3 schedule
- [ ] No Sync variation has the flex meta deleted, not set to 'no'
- [ ] Legends survive AJAX save and reload
- [ ] Only Next Cycle shows the bonus-access cart note
- [ ] Divergent next-payment contract recorded in the registry
- [ ] Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access
- [ ] Zero mail, cart left empty

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
