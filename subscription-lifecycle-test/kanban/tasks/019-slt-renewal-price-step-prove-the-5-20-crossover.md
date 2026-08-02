---
id: 19
title: 'SLT Renewal Price Step: prove the $5 -> $20 crossover lands on renewal #2, not renewal #3'
status: todo
priority: high
created: 2026-08-02T03:43:04.667237414+02:00
updated: 2026-08-02T03:43:14.735253117+02:00
tags:
    - renewal
    - day-01
due: "2026-08-03"
estimate: 1h30m
depends_on:
    - 10
    - 12
    - 20
class: standard
---

> **SLT-LIFE-05** · group `renewal` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Establish which cycle the stepped renewal price lands on for SLT Renewal Price Step ($5.00 signup, $20.00 renewal, `_renewal_price_after=2`, day/1). `OrderCreation::createRenewalOrder()` uses the stepped price when `_completed_payments >= _different_renewal_price_after` (OrderCreation.php:104-124) and the initial payment already sets that counter to 1 (OrderIntegration.php:1071-1072), so the crossover must be renewal #2 - one cycle earlier than SLT-PROD-05 ER5 predicted. SLT-PROD-05 delegated the authoritative reading here.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-core`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01..04 and SLT-PROD-05 done; `_enable_renewal_price=yes`, `_renewal_price=20`, `_renewal_price_after=2`, `_regular_price=5.00`, day/1.
- Buy after 12:00 site time (audit C02); `slt-core` must not already own this product (C08).
- Hand-off: subscription S5 is reused by SLT-LIFE-03 (D5) and SLT-LIFE-01 (D8). Do not cancel it.

## Test data
| Item | Value |
|---|---|
| Product | SLT Renewal Price Step (`slt-renewal-price-step`) |
| Account | `slt-core` / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Amounts | today $5.00; renewal1 $5.00; renewal2 $20.00; renewal3 $20.00 |
| Dates | buy 2026-08-03 at T site; due1 08-04 T, due2 08-05 T, due3 08-06 T |

## Steps
1. `agent-browser --session life05 open "https://mirror-help.arrayhash.com/?p=<PROD05_ID>"` -> `snapshot -i`; screenshot the price string, expected "$5.00 every day for the first 2 payments, then $20.00 every day" (product-helpers.php:341-352).
2. `PREV=$(mailpit-agent latest-id)`.
3. Add to cart; at `/cart/` assert total $5.00 and no tax line; at `/checkout/` log in as `slt-core`, pay with 4242, Place order. Record O0 and S5.
4. `wp post meta list S5 --keys=_recurring_amount,_different_renewal_price,_different_renewal_price_after,_completed_payments,_next_payment_date --allow-root`.
5. `php -r '$h=(int)sprintf("%u",crc32("arraysubs-spread-S5"));printf("%d\n",$h%21600);'` -> offset k.
6. Screenshot `wp-admin/tools.php?page=action-scheduler&s=S5&status=pending`: invoice leg at due1+k-6h, charge leg at due1+k.
7. `mailpit-agent wait-new "$PREV" 120 "is active"`.
8. **D2 (08-04, after due1+k):** open `wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/S5`; read renewal order O1 line total, `_renewal_cycle_number`, `_renewal_scheduled_date`; repeat step 4.
9. **D3 (08-05, after due2+k):** same reads for O2. **D4 (08-06, after due3+k):** same reads for O3.
10. Publish the crossover reading (cycle number + first stepped order ID) to `slt-catalog-registry`, superseding SLT-PROD-05 ER5.

## Expected results
1. First charge exactly $5.00; `_completed_payments=1`; `_recurring_amount=5.00`; `_different_renewal_price=20`; `_different_renewal_price_after=2`.
2. O1 fires 08-04 in [due1+k, due1+k+5min], total exactly $5.00, `_renewal_cycle_number=2`; after payment `_completed_payments=2` and `checkDifferentRenewalPrice()` (OrderIntegration.php:1769-1795) rewrites `_recurring_amount` to 20.
3. O2 fires 08-05, total exactly $20.00, `_renewal_cycle_number=3` - **the crossover cycle**.
4. O3 on 08-06 is also exactly $20.00.
5. `_next_payment_date` advances exactly 24h per cycle from the logical due date; no tax or fee line; every renewal order reaches `processing`.
6. If the first $20.00 order is O3, capture `_completed_payments` at invoice time and file an issue; if it is O1, the initial payment is not counted - also an issue.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | checkout | customer + admin | `is active` / `New subscription #` | `wait-new $PREV 120` |
| 2 | WC new order + processing order | O0..O3 | admin + customer | `#<order id>` | `list 20` daily |
| 3 | payment_successful x3 | each renewal paid | slt-core@example.test | `Payment received for subscription #S5` | `wait-new` daily |
| 4 | NONE EXPECTED: renewal_reminder (1-day cycle), renewal_invoice (Stripe suppression) | — | — | `renews soon`, `Invoice for subscription` | absent in `list 30` |

## Evidence to capture
- Screenshots `SLT-LIFE-05-01-price-string.png`, `-02-cart-5.00.png`, `-03-pending.png`, `-04-O1-5.00.png`, `-05-O2-20.00.png`.
- S5, O0..O3, k, four meta dumps, Mailpit IDs, the registry paragraph.

## Pass criteria
- [ ] First charge $5.00 with `_completed_payments=1`
- [ ] Renewal1 $5.00, renewal2 $20.00, renewal3 $20.00
- [ ] `_recurring_amount` flips to 20 only after renewal1 is paid
- [ ] Every `_renewal_scheduled_date` equals its logical due date; no grid drift
- [ ] Exactly the 4 email rows, negatives included
- [ ] Crossover reading published to the registry

## Isolation / teardown
- S5 stays ACTIVE with a healthy daily grid for SLT-LIFE-03 and SLT-LIFE-01; no other task may mutate it.
- No settings changed. SLT-SETUP-99A cancels and deletes S5 on D10.


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
