---
id: 17
title: SLTFIX5FIRST $5 fixed one-time coupon on classic checkout - first order discounted, first renewal at full price
status: todo
priority: critical
created: 2026-08-02T03:43:04.505709602+02:00
updated: 2026-08-02T03:43:14.489921817+02:00
tags:
    - checkout
    - day-01
due: "2026-08-03"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 5
class: standard
---

> **SLT-CPN-02** · group `checkout` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove `SLTFIX5FIRST` (fixed cart $5.00, apply-to-subs yes, duration `one-time`) discounts only the classic-checkout parent order and that the FIRST scheduled renewal is charged at full list price: `applyRecurringCoupons()` exits at its `'recurring' !== $discount_type` gate, so no `Recurring Discount:` fee may ever appear on a renewal here.

## Scope
- Gateway: Stripe test
- Checkout: classic (`/slt-classic-cart` + `/slt-classic-checkout`)
- Account: new registered (this task CREATES `slt-cpnfirst`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-SETUP-04 (`SLTFIX5FIRST`), SLT-PROD-01 (`SLT Daily Core` $10.00 day/1) done.
- CREATES `slt-cpnfirst` / `slt-cpnfirst@example.test`, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4. Fresh account mandatory (`auto_migrate_on_checkout=true`, C08).
- Check out **18:00-19:00 site (UTC+6)**: satisfies C02 and keeps the invoice leg (`due+k-6h` => 12:00-19:00 site) clear of SLT-SYN-04's D3 09:00-11:00 bracket.
- $10.00 product vs $5.00 coupon is deliberate: the order must stay above $0.00 so the real Stripe charge runs, not `PaymentProcessor`'s zero-total short-circuit.
- Sessions `cpn02-admin` / `cpn02-cust` exclusive to this task (C09).

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, $10.00, day/1 |
| Account | slt-cpnfirst (created here) |
| Coupon | SLTFIX5FIRST - fixed cart 5.00, one-time |
| Card | 4242 4242 4242 4242 |
| Amounts | first order $5.00; renewal #1 and later $10.00 |

## Steps
1. `cpn02-admin`: create `slt-cpnfirst` (**Send User Notification** unticked), fill billing. Open the coupon; confirm **Discount duration** = `One-time (initial order only)` and expiry `2026-08-14`; screenshot; record coupon ID.
2. `mailpit-agent latest-id` -> `M0`.
3. `cpn02-cust`: log in as `slt-cpnfirst`; open `/slt-classic-cart`, confirm the classic empty-cart notice, screenshot; add SLT Daily Core; Subtotal $10.00.
4. Type `SLTFIX5FIRST` in **Coupon code** -> **Apply coupon**; re-snapshot: `Coupon: sltfix5first` row **-$5.00**, Total **$5.00**; screenshot.
5. Open `/slt-classic-checkout`; confirm the review table repeats Total **$5.00** with the coupon row.
6. Pay with Stripe; **Place order**. Record parent order ID and SUB_ID.
7. `wp post meta list <SUB_ID> --allow-root | grep -E '_coupon|_next_payment_date'`; copy the capture note verbatim.
8. Offset `k = crc32('arraysubs-spread-'.SUB_ID) % 21600` via `php -r`; record invoice window `due+k-6h`, charge `due+k`.
9. `mailpit-agent wait-new $M0 120 "is active"`; record all checkout mail.
10. **2026-08-05 after 09:00 site** (renewal #1 charged the evening of 08-04): open the renewal order, screenshot the item table, run `wp eval '$o=wc_get_order(<REN1>);echo count($o->get_fees())."|".implode(",",$o->get_coupon_codes())."|".$o->get_total();' --allow-root`.
11. Repeat 2026-08-06 for renewal #2. Append IDs to `slt-catalog-registry`.

## Expected results
1. Classic cart and classic checkout both show Total **$5.00**, discount row **-$5.00**, no tax line.
2. Parent order total **$5.00**, status processing/completed, coupon `sltfix5first` on the order.
3. Metas: `_coupon_code=sltfix5first`, `_coupon_discount_type=one-time`, `_coupon_discount_amount=5.00`, `_coupon_discount_percent=0`, `_coupon_wc_discount_type=fixed_cart`, `_coupon_original_cycles=0`, `_coupon_remaining_cycles=0`, `_coupon_count_initial=no`, `_coupon_initial_cycle_pending` ABSENT.
4. Capture note: `Coupon "sltfix5first" captured from checkout order. Duration: one-time (initial order only). Discount: $5.00 off per eligible renewal.` The trailing **"per eligible renewal" is wrong for a one-time coupon** - record verbatim and file a `bug`-tagged issue; behaviour is still correct.
5. Renewal #1 (08-04): subtotal $10.00, total **$10.00**, **zero** fee items, **zero** coupon codes.
6. Renewal #2 (08-05): total **$10.00**, same shape.
7. No recurring-discount or cycle note ever added to the subscription.
8. Status stays `arraysubs-active`; `_next_payment_date` advances exactly 1 day per renewal, anchored on `_renewal_scheduled_date`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | parent paid | slt-cpnfirst@ | `is active` | `wait-new $M0 120 "is active"` |
| 2 | admin_new_subscription | parent paid | admin_email | `New subscription #` | `mailpit-agent list 20` |
| 3 | payment_successful | each renewal paid | slt-cpnfirst@ | `Payment received for subscription #<SUB_ID>` | `wait-new <prev> 900 "Payment received"` |
| 4 | renewal_invoice **NONE EXPECTED** | invoice leg | - | - | suppressed for auto-payment auto-renew subs (SLT-REF-04) |

## Evidence to capture
- Screenshots `SLT-CPN-02-01-coupon-settings.png`, `-02-classic-cart-5.00.png`, `-03-checkout-review.png`, `-04-order-received.png`, `-05-renewal1-full-price.png`.
- Coupon ID, SUB_ID, parent + 2 renewal order IDs, `k`, verbatim note, meta/`wp eval` dumps, Mailpit ids.

## Pass criteria
- [ ] Classic cart and classic checkout both total $5.00
- [ ] `_coupon_discount_type=one-time`, `_coupon_discount_amount=5.00`, `_coupon_discount_percent=0`
- [ ] Capture note verbatim and the misleading wording filed as an issue
- [ ] Renewal #1 exactly $10.00 with zero fees and zero coupons
- [ ] Renewal #2 exactly $10.00; no recurring-discount note ever added
- [ ] payment_successful per renewal; zero renewal_invoice mail

## Isolation / teardown
- Watch assertion D3..D9: every `slt-cpnfirst` renewal is $10.00 with no fee line - the direct counterexample to SLT-CPN-01. A discounted renewal here is a defect.
- Leaves `slt-cpnfirst` + one active daily subscription; add both to SLT-SETUP-99A's cancel list. Nothing global changed. Close `cpn02-*` sessions only.


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
