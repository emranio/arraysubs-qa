---
id: 58
title: SLT-PROD-04 Create SLT Signup Fee Daily with a $15.00 one-time signup fee
status: done
priority: high
created: 2026-08-02T03:43:08.066945236+02:00
updated: 2026-08-05T10:44:58.383415099+02:00
started: 2026-08-05T10:44:52.989479394+02:00
completed: 2026-08-05T10:44:52.989479394+02:00
tags:
    - setup
    - products
    - day-03
due: "2026-08-05"
estimate: 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-04** · group `catalog` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the signup-fee product and pin down exactly how the fee behaves: `addSignupFeeToCart()` adds a taxable cart fee literally named `Subscription Signup Fee`, once per subscription line and NOT multiplied by quantity, and it is skipped entirely on renewal orders (`did_action('arraysubs_creating_renewal_order')`). That makes it the anchor for the fee-vs-coupon negative and for the quantity-independence check.

## Scope
- Gateway: N/A
- Checkout: classic-cart preview only; no checkout or purchase
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Taxes are off site-wide (`woocommerce_calc_taxes=no`), so the fee's taxable flag has no visible effect and all amounts are exact.

## Test data
| Item | Value |
|---|---|
| Product | SLT Signup Fee Daily / slug `slt-signup-fee-daily` |
| Account | N/A |
| Coupon | N/A (SLTFEEPROBE is used against it later) |
| Card | N/A |
| Amounts | Regular price $9.00 + signup fee $15.00 => $24.00 due today; renewal $9.00/day with NO fee |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin-SLT-PROD-04 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Signup Fee Daily`. **Description**: `SLT window product. Daily recurring with a one-time signup fee. Delete on 2026-08-15.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `9.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `0`; **Sign-up Fee ($)** = `15.00`; **Different Renewal Price** unticked; **Flexible Renewal Sync** left unticked (1-day cycle is below the 3-day minimum anyway). Capture `SLT-PROD-04-01-subscription-tab.png`.
7. Slug `slt-signup-fee-daily`. Publish. Reload and re-verify.
8. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_signup_fee,_trial_length,_regular_price --allow-root`.
9. Before any storefront/cart/downstream checkout access, append only this parent product ID to Shop Access rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior exclusion; re-read the raw option and require the ID exactly once.
10. As `--session guest-SLT-PROD-04`: `agent-browser --session guest-SLT-PROD-04 open "https://mirror-help.arrayhash.com/slt-classic-cart/?add-to-cart=<ID>&slt-cache-bust=<timestamp>"` -> `snapshot -i`. If one-click redirects to block checkout, record the summary and explicitly reopen `/slt-classic-cart`; record the fee row/total and capture `SLT-PROD-04-02-cart-qty1-fee.png`. Set the cart quantity to 2, re-snapshot, and capture `SLT-PROD-04-03-cart-qty2-fee-unchanged.png` to prove the fee did not double.
11. Empty the cart, inspect the complete Mailpit delta after step 1 and require zero task-attributable mail, append the ID and verified Shop Access exclusion to the registry, and close only `admin-SLT-PROD-04` and `guest-SLT-PROD-04`.

## Expected results
1. Published simple + virtual + subscription, slug `slt-signup-fee-daily`.
2. `_signup_fee=15`, `_subscription_period=day`, `_subscription_interval=1`, `_trial_length=0`, `_regular_price=9.00`.
3. Guest cart at qty 1: line subtotal `$9.00`, a fee row labelled `Subscription Signup Fee` of `$15.00`, cart total `$24.00`.
4. Guest cart at qty 2: line subtotal `$18.00`, fee row still exactly `$15.00`, cart total `$33.00` — the fee is per subscription item, not per unit.
5. Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access; cart is emptied and no order is created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and cart preview | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-04-01-subscription-tab.png`, `SLT-PROD-04-02-cart-qty1-fee.png`, `SLT-PROD-04-03-cart-qty2-fee-unchanged.png`.
- Product ID; meta list output; raw Shop Access rule showing the ID exactly once; the exact fee row label and both totals.

## Pass criteria
- [x] Published with signup fee 15.00 and price 9.00
- [x] Cart shows a $15.00 `Subscription Signup Fee` row at qty 1, total $24.00
- [x] Fee stays $15.00 at qty 2, total $33.00
- [x] Metas exactly as listed
- [x] Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access
- [x] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy as `slt-core`. Downstream expectations: the parent order totals $24.00; every renewal order totals $9.00 with NO fee line. `SLTFEEPROBE` ($10.00 fixed cart discount, one-time, apply-to-subscriptions on) must be applied to this product to prove a WooCommerce coupon discounts the $9.00 line only and never the $15.00 fee — expected checkout total $14.00, not $9.00 and not $24.00.
- Restores: cart emptied; SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. Product deleted by SLT-SETUP-99B.

---

## D03 execution result (2026-08-05)

PASS. Published product `12577` with the exact slug and meta. The preserved Shop Access rule gained only `12577`, exactly once, before any storefront access. Real guest classic-cart proof was `$9.00 + $15.00 = $24.00` at quantity 1 and `$18.00 + the unchanged $15.00 = $33.00` at quantity 2. The one-click redirect to block checkout was recorded and classic cart reopened explicitly. The guest cart is empty, no order exists, Mailpit stayed at `56kcLytDylTWndyI4kEeYS`, and both browser error buffers were empty. Evidence: `/home/server-manager/slt-evidence/SLT-PROD-04-facts.txt`.

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
