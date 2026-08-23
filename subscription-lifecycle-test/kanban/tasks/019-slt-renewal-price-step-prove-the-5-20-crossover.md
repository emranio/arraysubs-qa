---
id: 19
title: 'SLT2 Renewal Price Step: prove the $5 -> $20 crossover lands on renewal #2, not renewal #3'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - day-01
due: "2026-08-24"
estimate: 1h30m
depends_on:
    - 10
    - 12
    - 20
class: standard
---

> **SLT-LIFE-05** · group `renewal` · scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Establish which cycle the stepped renewal price lands on for SLT2 Renewal Price Step ($5.00 signup, $20.00 renewal, `_renewal_price_after=2`, day/1). `OrderCreation::createRenewalOrder()` uses the stepped price when `_completed_payments >= _different_renewal_price_after` (OrderCreation.php:104-124) and the initial payment already sets that counter to 1 (OrderIntegration.php:1071-1072), so the crossover must be renewal #2 - one cycle earlier than SLT-PROD-05 ER5 predicted. SLT-PROD-05 delegated the authoritative reading here.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt2-core`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01..04 and SLT-PROD-05 done; `_enable_renewal_price=yes`, `_renewal_price=20`, `_renewal_price_after=2`, `_regular_price=5.00`, day/1.
- Buy after 12:00 site time (audit C02); `slt2-core` must not already own this product (C08).
- Hand-off: subscription S5 is reused by SLT-LIFE-03 (D5) and SLT-LIFE-01 (D8). Do not cancel it.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Renewal Price Step (`slt2-renewal-price-step`) |
| Account | `slt2-core` / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Amounts | today $5.00; renewal1 $5.00; renewal2 $20.00; renewal3 $20.00 |
| Dates | buy 2026-08-24 at T site; due1 08-25 T, due2 08-26 T, due3 08-27 T |

## Steps
1. `agent-browser --session life05-SLT-LIFE-05 open "https://mirror-help.arrayhash.com/my-account/"`, log in as `slt2-core`, open `/cart/`, and require the persistent cart to be EMPTY. Then open `https://mirror-help.arrayhash.com/product/slt2-renewal-price-step/` -> `snapshot -i`; screenshot the price string, expected "$5.00 every day for the first 2 payments, then $20.00 every day" (product-helpers.php:341-352).
2. `PREV=$(mailpit-agent latest-id)`.
3. Add to cart; wait for the block cart's actual product row rather than capturing its loading skeleton, then assert total $5.00 and no tax line. At `/checkout/` log in as `slt2-core`, pay with 4242, and Place order. Record O0; resolve the numeric subscription ID with `get_post_meta(O0, '_subscription_ids', true)`, require one ID, and cross-check parent order/customer/product plus the count delta, then assign that exact ID to shell variable `S5`; never select by recency. Under HPOS, do not use `WC_Order::get_meta('_subscription_ids')`, which does not expose this legacy post-meta linkage.
4. `wp post meta list "$S5" --keys=_recurring_amount,_different_renewal_price,_different_renewal_price_after,_completed_payments,_next_payment_date --allow-root`.
5. `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("%d\n",$h%21600);' "$S5"` -> offset k. The ID must be numeric; never hash the literal string `S5`.
6. Load the documented admin auth state into isolated session `admin-SLT-LIFE-05`, then screenshot `wp-admin/tools.php?page=action-scheduler&s=$S5&status=pending`: invoice leg at due1+k-6h, charge leg at due1+k.
7. `mailpit-agent wait-new "$PREV" 120 "is active"`. Open `/cart/` in the same session, require it and the persistent-cart meta to be EMPTY after checkout, and capture `SLT-LIFE-05-03b-cart-empty-after.png` before closing the current-day leg.
8. At least five minutes before `due1+k`, store `O1_PRE=$(mailpit-agent latest-id)` in the registry. **D2 (08-25, after due1+k):** open `wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/$S5`; read renewal order O1 line total, `_renewal_cycle_number`, `_renewal_scheduled_date`; repeat step 4, run `mailpit-agent wait-new "$O1_PRE" 900 "Payment received for subscription #$S5"`, and reconcile every message newer than `O1_PRE`.
9. Likewise store `O2_PRE` and `O3_PRE` at least five minutes before their exact gates. **D3 (08-26, after due2+k):** perform the same reads for O2, run `mailpit-agent wait-new "$O2_PRE" 900 "Payment received for subscription #$S5"`, and reconcile every message newer than `O2_PRE`. **D4 (08-27, after due3+k):** perform the same reads for O3, run `mailpit-agent wait-new "$O3_PRE" 900 "Payment received for subscription #$S5"`, and reconcile every message newer than `O3_PRE`.
10. Publish the crossover reading (cycle number + first stepped order ID) to `slt2-catalog-registry`, superseding SLT-PROD-05 ER5.

## Expected results
1. First charge exactly $5.00; `_completed_payments=1`; `_recurring_amount=5.00`; `_different_renewal_price=20`; `_different_renewal_price_after=2`.
2. O1 fires 08-25 in [due1+k, due1+k+5min], total exactly $5.00, `_renewal_cycle_number=2`; after payment `_completed_payments=2` and `checkDifferentRenewalPrice()` (OrderIntegration.php:1769-1795) rewrites `_recurring_amount` to 20.
3. O2 fires 08-26, total exactly $20.00, `_renewal_cycle_number=3` - **the crossover cycle**.
4. O3 on 08-27 is also exactly $20.00.
5. `_next_payment_date` advances exactly 24h per cycle from the logical due date; no tax or fee line; every renewal order reaches a paid status (`processing` or `completed`, recorded exactly rather than hard-coded).
6. If the first $20.00 order is O3, capture `_completed_payments` at invoice time and create/update the mandatory `qa/issues/` kanban card for the late crossover. If it is O1, create/update a separate mandatory issue card for the initial-payment counting defect.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | checkout | customer + admin | `is active` / `New subscription #` | `mailpit-agent wait-new "$PREV" 120` |
| 2 | WC New order for O0..O3, plus the parent order's customer Completed mail | each paid order / initial virtual checkout | admin + customer for O0 only | `New order #<order id>` / `is on its way` | Complete owner-filtered checkout/renewal deltas after the recorded O0/O1/O2/O3 baselines; save/show exact ids |
| 3 | payment_successful x3 | each renewal paid | slt2-core@example.test | `Payment received for subscription #S5` | `mailpit-agent wait-new "$O1_PRE" 900 ...` / `"$O2_PRE" 900 ...` / `"$O3_PRE" 900 ...` at the respective gates |
| 4 | NONE EXPECTED: customer WC status mail for O1..O3, renewal_reminder (1-day cycle), renewal_invoice (Stripe suppression) | — | — | `order is now processing`, `order is complete`, `renews soon`, `Invoice for subscription` | absent from each complete `O1_PRE` / `O2_PRE` / `O3_PRE` owner delta |

## Evidence to capture
- Screenshots `SLT-LIFE-05-01-price-string.png`, `-02-cart-5.00.png`, `-03-pending.png`, `-03b-cart-empty-after.png`, `-04-O1-5.00.png`, `-05-O2-20.00.png`.
- S5, O0..O3, k, four meta dumps, `O1_PRE`/`O2_PRE`/`O3_PRE` and resulting Mailpit IDs, the registry paragraph.

## Pass criteria
- [ ] First charge $5.00 with `_completed_payments=1`
- [ ] Cart and persistent-cart meta proved empty before checkout and again after checkout
- [ ] Renewal1 $5.00, renewal2 $20.00, renewal3 $20.00
- [ ] `_recurring_amount` flips to 20 only after renewal1 is paid
- [ ] Every `_renewal_scheduled_date` equals its logical due date; no grid drift
- [ ] Exactly the 4 email rows, negatives included
- [ ] Crossover reading published to the registry

## Isolation / teardown
- S5 stays ACTIVE with a healthy daily grid for SLT-LIFE-03 and SLT-LIFE-01; no other task may mutate it.
- No settings changed. At the end of each dated leg, close only that leg's `life05-SLT-LIFE-05` and `admin-SLT-LIFE-05` sessions. SLT-SETUP-99A cancels S5 on D11; SLT-SETUP-99B deletes it after the watch.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
