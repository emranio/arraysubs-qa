---
id: 20
title: SLT-PROD-05 Create SLT Renewal Price Step with a different renewal price after 2 cycles
status: done
priority: high
created: 2026-08-02T03:43:04.742050405+02:00
updated: 2026-08-03T02:47:18.608867398+02:00
started: 2026-08-03T02:47:18.608866676+02:00
completed: 2026-08-03T02:47:18.608866676+02:00
tags:
    - setup
    - products
    - day-01
due: "2026-08-03"
estimate: 40m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-05** · group `catalog` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the intro-price product (first price != renewal price) and capture, in the UI, the code-verified exclusivity: "Different Renewal Price" and "Flexible Renewal Sync" cannot coexist. `SegmentPlan::getConfig()` returns null whenever `_enable_renewal_price === 'yes'`, and the pro view sets `$arraysubs_flex_section_hidden` on the same condition, so the flex control is hidden the moment the checkbox is ticked.

## Scope
- Gateway: N/A
- Checkout: N/A (creation only)
- Account: N/A (creation only)
- Plugins: both (free feature; pro view provides the negative)

## Preconditions
- SLT-SETUP-01 complete.
- Validation contract to respect: if **Different Renewal Price** is ticked, the save is BLOCKED unless **Renewal Price** > 0 and **Apply Renewal Price After** >= 1.

## Test data
| Item | Value |
|---|---|
| Product | SLT Renewal Price Step / slug `slt-renewal-price-step` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $5.00; renewal price $20.00 applied after 2 billing periods; expected charge today $5.00 |

## Steps
1. Capture `M0=$(mailpit-agent latest-id)`. At the end, inspect every message newer than `M0`; classify unrelated/background mail by its actual owner.
2. `agent-browser --session admin-SLT-PROD-05 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Renewal Price Step`. **Description**: `SLT window product. $5 intro, $20 from cycle 3. Delete on 2026-08-15.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `5.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** empty.
7. BEFORE ticking the renewal-price box, screenshot the panel showing the **Flexible Renewal Sync to Next Billing Cycle** checkbox present.
8. Tick **Different Renewal Price**. The `show_if_renewal_price` block reveals: set **Renewal Price ($)** = `20.00` and **Apply Renewal Price After** = `2`.
9. Re-snapshot and screenshot: the **Flexible Renewal Sync** section must now be hidden. This is the exclusivity evidence required by the catalog.
10. Negative save probe: temporarily clear **Renewal Price** and click **Publish**; expect the WooCommerce error notice "If different renewal price is enabled, you must set a valid renewal price." and the post NOT going live. Restore `20.00` and publish for real.
11. Slug `slt-renewal-price-step`. Publish. Reload and re-verify.
11a. Before any downstream storefront/cart/checkout use, append this parent product ID only to the existing Shop Access rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other rule field and every prior SLT exclusion; re-read the raw option and require the new ID exactly once.
12. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_enable_renewal_price,_renewal_price,_renewal_price_after,_regular_price,_arraysubs_flex_sync_enabled --allow-root`.
13. Append the ID and verified Shop Access exclusion to the registry.
14. Close only `admin-SLT-PROD-05`.

## Expected results
1. Published simple + virtual + subscription, slug `slt-renewal-price-step`.
2. `_enable_renewal_price=yes`, `_renewal_price=20`, `_renewal_price_after=2`, `_regular_price=5.00`, `_subscription_period=day`, `_subscription_interval=1`.
3. `_arraysubs_flex_sync_enabled` is absent, and the flex UI section is hidden while the renewal-price box is ticked.
4. The negative save probe produced the exact validation error text and left the product unpublished/unchanged (`preserveProductStatusForInvalidSubscriptionSave()` keeps the prior status; `restoreProductPricingFromSavedMeta()` restores prices).
5. Expected downstream billing from the verified counter contract: charge today $5.00 (completed payment 1); renewal #1 $5.00 (cycle 2, then the counter reaches 2); renewal #2 and later $20.00 (cycle 3 onward). `SLT-LIFE-05` records the live crossover as the authoritative reading.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and the failed save probe | — | — | Complete delta after `M0`; zero message attributable to this task, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-05-01-flex-visible-before.png`, `SLT-PROD-05-02-flex-hidden-after-renewal-price.png`, `SLT-PROD-05-03-validation-error.png`, `SLT-PROD-05-04-final-subscription-tab.png`.
- Product ID; meta list output; verbatim validation error string; raw Shop Access rule showing the ID exactly once.

## Pass criteria
- [ ] Published with renewal price 20.00 after 2 and regular price 5.00
- [ ] Flex sync section visibly disappears when Different Renewal Price is ticked
- [ ] Empty-renewal-price save is blocked with the exact message
- [ ] Metas exactly as listed, flex meta absent
- [ ] Parent product ID is present exactly once in the preserved Shop Access exclusion list
- [ ] Zero mail

## Isolation / teardown
- State handoff: buy as `slt-core` on D1 after 12:00 so the $5 -> $20 crossover is observed live. This product is also the canonical "cannot be a Subscription Box child" case: `BoxConfig::isEligibleChildProduct()` excludes any product with `_enable_renewal_price=yes`.
- Restores: nothing. Cancelled by SLT-SETUP-99A and deleted by SLT-SETUP-99B.

## Execution note — 2026-08-03 early-morning

- Verdict: **PASS**.
- Published product `12087` (`SLT Renewal Price Step`, `slt-renewal-price-step`) as a simple virtual daily subscription.
- Verified `_regular_price=5.00`, `_enable_renewal_price=yes`, `_renewal_price=20`, `_renewal_price_after=2`, and `_arraysubs_flex_sync_enabled` absent.
- Captured the flex control before enabling the renewal price and its disappearance afterward.
- The negative save probe left the product unpublished and displayed the exact expected validation notice; the restored product then published normally.
- Added only parent product `12087` through Member Access -> Shop Access. Raw rule `rule_1784662676378_maa3te08s` now has exclusions `[11927,11933,11938,11943,12087]`, with `12087` exactly once and every other field preserved.
- Registry page `11847` updated through the browser editor; matching section count is one.
- Mailpit baseline/final: `42DI8ELEccd8qFsaMtyeag`; zero attributable mail.
- Consolidated evidence: `/home/server-manager/slt-evidence/SLT-PROD-05-facts.txt`.

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
