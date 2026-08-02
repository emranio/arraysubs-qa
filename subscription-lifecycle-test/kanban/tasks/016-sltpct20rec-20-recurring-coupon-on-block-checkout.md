---
id: 16
title: SLTPCT20REC 20% recurring coupon on block checkout - discounted first charge, discount persists to every renewal
status: todo
priority: critical
created: 2026-08-02T03:43:04.433753843+02:00
updated: 2026-08-02T03:43:14.334007392+02:00
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

> **SLT-CPN-01** · group `checkout` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove `SLTPCT20REC` (percent 20, apply-to-subs yes, `recurring`, cycles 0 = unlimited) discounts the block-checkout first charge AND every unattended renewal, the renewal discount arriving as a negative FEE named `Recurring Discount: sltpct20rec` (`CouponTracking\Services\Hooks::applyRecurringCoupons` on `arraysubs_renewal_invoice_created`), not a coupon line.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered (this task CREATES `slt-cpnrec`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-SETUP-04 (`SLTPCT20REC`), SLT-PROD-01 (`SLT Daily Core` $10.00 day/1) done.
- Coupon expiry must be `2026-08-14`, not `2026-08-12` (C13): `applyRecurringCoupons()` returns silently once `time() > date_expires`. Fix and record if wrong.
- CREATES `slt-cpnrec` / `slt-cpnrec@example.test`, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4. Fresh account mandatory: `auto_migrate_on_checkout=true` migrates an existing SLT Daily Core subscription (C08).
- Check out **18:00-19:00 site (UTC+6)**: satisfies C02 and keeps the invoice leg (`due+k-6h` => 12:00-19:00 site) clear of SLT-SYN-04's D3 09:00-11:00 bracket.
- Sessions `cpn01-admin` / `cpn01-cust` exclusive to this task (C09).

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, $10.00, day/1 |
| Account | slt-cpnrec (created here) |
| Coupon | SLTPCT20REC - percent 20, recurring, cycles 0 |
| Card | 4242 4242 4242 4242 |
| Amounts | first charge $8.00; every renewal $8.00 ($10.00 - $2.00 fee) |

## Steps
1. `cpn01-admin`: `user-new.php` -> create `slt-cpnrec`, role Customer, **Send User Notification** unticked; fill billing on `user-edit.php`. Open the coupon, screenshot the **ArraySubs Subscription Settings** group + expiry, record coupon ID.
2. `mailpit-agent latest-id` -> `M0`.
3. `cpn01-cust`: log in as `slt-cpnrec`; open `/cart/`, confirm EMPTY, screenshot; add SLT Daily Core from its product page; subtotal $10.00.
4. Open `https://mirror-help.arrayhash.com/checkout/` (page 8, block); in **Order summary** expand **Add a coupon**, enter `SLTPCT20REC`, **Apply**; re-snapshot and screenshot totals.
5. Pay with Stripe; **Place order**. Record parent order ID and SUB_ID.
6. `wp post meta list <SUB_ID> --allow-root | grep -E '_coupon|_next_payment_date'`; copy the capture note verbatim.
7. Offset `k = crc32('arraysubs-spread-'.SUB_ID) % 21600` via `php -r`; record invoice window `due+k-6h` and charge `due+k`.
8. `mailpit-agent wait-new $M0 120 "is active"`, then `mailpit-agent list 20`.
9. **2026-08-05 after 09:00 site** (renewal #1 charged the evening of 08-04): open it via `admin.php?page=wc-orders&s=slt-cpnrec`, screenshot the item table, dump fees with `wp eval '$o=wc_get_order(<REN1>);foreach($o->get_fees() as $f){echo $f->get_name()."|".$f->get_total();}echo "|".$o->get_total();' --allow-root`.
10. Repeat 2026-08-06 for renewal #2 - one discounted renewal is not persistence. Append IDs to `slt-catalog-registry`.

## Expected results
1. Checkout total exactly **$8.00**; no tax line.
2. Metas: `_coupon_code=sltpct20rec` (lowercased by `wc_strtolower`), `_coupon_discount_type=recurring`, `_coupon_original_cycles=0`, `_coupon_remaining_cycles=0`, `_coupon_discount_percent=20`, `_coupon_discount_amount=0`, `_coupon_wc_discount_type=percent`, `_coupon_count_initial=no`, `_applied_coupon_id` set, `_coupon_initial_cycle_pending` ABSENT.
3. Note: `Coupon "sltpct20rec" captured from checkout order. Duration: recurring (unlimited). Discount: 20% off.`
4. `_next_payment_date` = checkout +1 day, same clock time; status `arraysubs-active`.
5. Renewal #1 (08-04): subtotal $10.00, one fee **`Recurring Discount: sltpct20rec`** at **-2.00** (meta `_subscription_coupon=yes`), total **$8.00**, `_is_renewal_order=yes`.
6. Renewal #2 (08-05): identical, total **$8.00** - no decay.
7. `_coupon_remaining_cycles` still 0 after both (`decrementCouponCycles()` exits when original cycles <= 0); no exhaustion note.
8. `get_coupon_codes()` empty on both renewal orders - fee, not coupon.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | parent paid | slt-cpnrec@ | `is active` | `wait-new $M0 120 "is active"` |
| 2 | admin_new_subscription | parent paid | admin_email | `New subscription #` | `mailpit-agent list 20` |
| 3 | payment_successful | each renewal paid | slt-cpnrec@ | `Payment received for subscription #<SUB_ID>` | `wait-new <prev> 900 "Payment received"` |
| 4 | renewal_invoice **NONE EXPECTED** | invoice leg | - | - | suppressed for auto-payment auto-renew subs (SLT-REF-04) |

## Evidence to capture
- Screenshots `SLT-CPN-01-01-coupon-settings.png`, `-02-block-totals-8.00.png`, `-03-order-received.png`, `-04-renewal1-items.png`, `-05-renewal2-items.png`.
- Coupon ID, SUB_ID, parent + 2 renewal order IDs, `k`, meta/`wp eval` dumps, Mailpit ids, checkout console errors.

## Pass criteria
- [ ] Checkout total exactly $8.00
- [ ] All ten metas as listed; `_coupon_initial_cycle_pending` absent
- [ ] Capture note verbatim
- [ ] Renewal #1 $8.00 with the named fee at -2.00
- [ ] Renewal #2 $8.00 - persistence past one cycle
- [ ] `_coupon_remaining_cycles` still 0; no exhaustion note; no coupon line on either renewal
- [ ] payment_successful per renewal; zero renewal_invoice mail

## Isolation / teardown
- Watch assertion D3..D9: every `slt-cpnrec` renewal totals $8.00 with the named fee; a $10.00 renewal is a defect.
- Leaves `slt-cpnrec` + one active daily subscription; add both to SLT-SETUP-99A's cancel list. Only global change is the coupon expiry fix. Close `cpn01-*` sessions only.


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
