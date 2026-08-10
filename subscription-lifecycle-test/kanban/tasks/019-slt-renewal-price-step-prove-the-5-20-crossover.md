---
id: 19
title: 'SLT Renewal Price Step: prove the $5 -> $20 crossover lands on renewal #2, not renewal #3'
status: done
priority: high
created: 2026-08-02T03:43:04.667237414+02:00
updated: 2026-08-06T20:10:31.1498774+02:00
started: 2026-08-06T20:10:31.149876498+02:00
completed: 2026-08-06T20:10:31.149876498+02:00
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
1. `agent-browser --session life05-SLT-LIFE-05 open "https://mirror-help.arrayhash.com/my-account/"`, log in as `slt-core`, open `/cart/`, and require the persistent cart to be EMPTY. Then open `https://mirror-help.arrayhash.com/product/slt-renewal-price-step/` -> `snapshot -i`; screenshot the price string, expected "$5.00 every day for the first 2 payments, then $20.00 every day" (product-helpers.php:341-352).
2. `PREV=$(mailpit-agent latest-id)`.
3. Add to cart; wait for the block cart's actual product row rather than capturing its loading skeleton, then assert total $5.00 and no tax line. At `/checkout/` log in as `slt-core`, pay with 4242, and Place order. Record O0; resolve the numeric subscription ID with `get_post_meta(O0, '_subscription_ids', true)`, require one ID, and cross-check parent order/customer/product plus the count delta, then assign that exact ID to shell variable `S5`; never select by recency. Under HPOS, do not use `WC_Order::get_meta('_subscription_ids')`, which does not expose this legacy post-meta linkage.
4. `wp post meta list "$S5" --keys=_recurring_amount,_different_renewal_price,_different_renewal_price_after,_completed_payments,_next_payment_date --allow-root`.
5. `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("%d\n",$h%21600);' "$S5"` -> offset k. The ID must be numeric; never hash the literal string `S5`.
6. Load the documented admin auth state into isolated session `admin-SLT-LIFE-05`, then screenshot `wp-admin/tools.php?page=action-scheduler&s=$S5&status=pending`: invoice leg at due1+k-6h, charge leg at due1+k.
7. `mailpit-agent wait-new "$PREV" 120 "is active"`. Open `/cart/` in the same session, require it and the persistent-cart meta to be EMPTY after checkout, and capture `SLT-LIFE-05-03b-cart-empty-after.png` before closing the current-day leg.
8. At least five minutes before `due1+k`, store `O1_PRE=$(mailpit-agent latest-id)` in the registry. **D2 (08-04, after due1+k):** open `wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/$S5`; read renewal order O1 line total, `_renewal_cycle_number`, `_renewal_scheduled_date`; repeat step 4, run `mailpit-agent wait-new "$O1_PRE" 900 "Payment received for subscription #$S5"`, and reconcile every message newer than `O1_PRE`.
9. Likewise store `O2_PRE` and `O3_PRE` at least five minutes before their exact gates. **D3 (08-05, after due2+k):** perform the same reads for O2, run `mailpit-agent wait-new "$O2_PRE" 900 "Payment received for subscription #$S5"`, and reconcile every message newer than `O2_PRE`. **D4 (08-06, after due3+k):** perform the same reads for O3, run `mailpit-agent wait-new "$O3_PRE" 900 "Payment received for subscription #$S5"`, and reconcile every message newer than `O3_PRE`.
10. Publish the crossover reading (cycle number + first stepped order ID) to `slt-catalog-registry`, superseding SLT-PROD-05 ER5.

## Expected results
1. First charge exactly $5.00; `_completed_payments=1`; `_recurring_amount=5.00`; `_different_renewal_price=20`; `_different_renewal_price_after=2`.
2. O1 fires 08-04 in [due1+k, due1+k+5min], total exactly $5.00, `_renewal_cycle_number=2`; after payment `_completed_payments=2` and `checkDifferentRenewalPrice()` (OrderIntegration.php:1769-1795) rewrites `_recurring_amount` to 20.
3. O2 fires 08-05, total exactly $20.00, `_renewal_cycle_number=3` - **the crossover cycle**.
4. O3 on 08-06 is also exactly $20.00.
5. `_next_payment_date` advances exactly 24h per cycle from the logical due date; no tax or fee line; every renewal order reaches a paid status (`processing` or `completed`, recorded exactly rather than hard-coded).
6. If the first $20.00 order is O3, capture `_completed_payments` at invoice time and write a separate issue file under `issues/`; if it is O1, write a separate issue file for the initial-payment counting defect. Do not create lifecycle-board bug cards.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | checkout | customer + admin | `is active` / `New subscription #` | `mailpit-agent wait-new "$PREV" 120` |
| 2 | WC New order for O0..O3, plus the parent order's customer Completed mail | each paid order / initial virtual checkout | admin + customer for O0 only | `New order #<order id>` / `is on its way` | Complete owner-filtered checkout/renewal deltas after the recorded O0/O1/O2/O3 baselines; save/show exact ids |
| 3 | payment_successful x3 | each renewal paid | slt-core@example.test | `Payment received for subscription #S5` | `mailpit-agent wait-new "$O1_PRE" 900 ...` / `"$O2_PRE" 900 ...` / `"$O3_PRE" 900 ...` at the respective gates |
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
- No settings changed. At the end of each dated leg, close only that leg's `life05-SLT-LIFE-05` and `admin-SLT-LIFE-05` sessions. SLT-SETUP-99A cancels S5 on D10; SLT-SETUP-99B deletes it after the watch.


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

---

### D01 execution — 2026-08-03 purchase leg

**PASS for the current-day leg; D2–D4 natural renewals remain owned by this in-progress card.** Preflight at site `13:48:01` proved product `12087` published with regular price `5.00`, renewal price `20`, apply-after `2`, day/1; user `347` (`slt-core`) owned zero subscriptions for it; total subscription baseline was `363`; browser and persistent carts were empty.

The storefront displayed `$5.00 every 1 day for the first 2 payments, then $20.00 every 1 day`. Stripe checkout completed parent order `O0=12233` for USD 5.00 with zero tax and exact post-meta linkage `[12234]`; `S5=12234` is active for user `347`, product `12087`, parent `12233`, completed payments `1`, recurring amount `5`, stepped amount `20`, threshold `2`, start `2026-08-03 07:50:13Z`, and next due `2026-08-04 07:50:13Z`. `k=14003`; pending invoice `14027` is `2026-08-04 05:43:36Z` and pending charge `14028` is `2026-08-04 11:43:36Z` (D2 `17:43:36` site). Record `O1_PRE` no later than D2 `17:38:36` site and force neither action.

The complete delta after `PREV=1c1GDfyKNHlSEsmQz6pN5J` contained exactly four owned messages: completed order `1uF5MD1LSp2DkgBIxmua0f`, admin new order `5cX2baJyta8jGhS14NZFCo`, customer active subscription `4BMx3rDLIiDVeSrcRCJjGb`, and admin new subscription `2RcDTczbfEyCjc8l6jXrxL`. Browser errors were empty; only the already-filed CHK-01 dependency warning appeared. The post-checkout browser and serialized persistent carts were empty. Consolidated evidence: `/home/server-manager/slt-evidence/SLT-LIFE-05-D01-facts.txt`.

### D2 O1 / D3 pre-charge checkpoint — 2026-08-05

**O1 PASS; O2 is naturally created at the stepped price and awaits its charge gate.** Invoice action `14027` and charge action `14028` ran unattended through WP Cron. Charge began at `2026-08-04 11:44:05Z`, 29 seconds after its gate. Relationship-exact cycle-2 order `O1=12414` completed for USD `$5.00`, zero tax, zero fees/coupons, and scheduled date `2026-08-04 07:50:13Z`.

After O1, subscription `12234` is active at `_completed_payments=2`, `_recurring_amount=20`, and `_next_payment_date=2026-08-05 07:50:13Z`. The exact O1 mail pair is admin order `4qi8KRvWijDLoD7FuTCAw5` plus customer payment success `2SZxYMGHKGD9Rtr3XadnZS`; no task renewal-invoice/reminder exists. The required `O1_PRE` cursor was missed contemporaneously. Mailpit chronology proves `7Yk2i1g2X4rk5YylVEnfzb` was the immediately preceding message and only the two task-owned messages followed through the gate; this is explicitly a post-hoc reconstruction.

Natural invoice action `14478` has now created relationship-exact `O2=12539` as cycle `3`, scheduled date `2026-08-05 07:50:13Z`, pending USD `$20.00`, zero tax. This confirms the intended crossover shape before charge but is not yet the final paid reading.

Evidence: `/home/server-manager/slt-evidence/SLT-LIFE-05-O1-facts.txt` and required screenshot `SLT-LIFE-05-04-O1-5.00.png`. Browser page errors were empty.

Timed handoff: capture `O2_PRE` only in `[2026-08-05 11:38:36Z, 11:43:36Z)` (`[17:38:36, 17:43:36)` site), then observe charge action `14479` naturally. Do not force it. Keep this card in progress through paid O2/crossover publication and D4 O3.

[[2026-08-05]] Wed 09:04
Checkpoint handoff: O1 passed; pending O2=12539 already carries the expected USD 20.00 crossover shape. Capture O2_PRE only during 2026-08-05 11:38:36Z-11:43:35Z; action 14479 must run naturally after 11:43:36Z. Claim released while waiting.

[[2026-08-05]] Wed 13:50
D03 afternoon follow-up PASS: contemporaneous O2_PRE 7iVg5SzIOlA4u05cQHR1y9 captured 2026-08-05 11:39:22Z; action 14479 ran naturally via WP Cron 11:44:05Z-11:44:25Z; order 12539 completed USD 20.00 at cycle 3 with zero fees/coupons; exact mail pair 6PfIW6q5P1P98DiOWJLXSY + 5zwePzGABtsyPa1jkVfJAI; registry 11847 saved/re-read. Evidence /home/server-manager/slt-evidence/SLT-LIFE-05-O2-facts.txt and /home/server-manager/slt-evidence/SLT-LIFE-05-05-O2-20.00.png. Keep in-progress for O3: D4 2026-08-06 baseline 11:38:36Z-11:43:35Z / 17:38:36-17:43:35 site, then natural charge action 14885 after 11:43:36Z; invoice action 14884 due 05:43:36Z.

[[2026-08-05]] Wed 14:35
Board hygiene checkpoint: no actionable step is available right now; the current body already records the exact next gate/window and required natural-watch constraints. Parking this future-gated card back in todo so In Progress reflects only currently active execution.

[[2026-08-05]] Wed 14:45
D03 watcher correction: the 18:35 board-hygiene note is superseded; this future-gated card remains in-progress. Capture D4 O3_PRE only 2026-08-06 11:38:36Z-11:43:35Z, then observe natural action 14885 after 11:43:36Z.

[[2026-08-05]] Wed 15:01
Board hygiene checkpoint: parked future-gated or watch-only work returned to todo so in-progress reflects only actively worked cards.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Next gate D4: capture O3_PRE 2026-08-06 11:38:36Z-11:43:35Z (17:38:36-17:43:35 site), then observe natural action 14885 at/after 11:43:36Z.

[[2026-08-05]] Wed 16:41
D4 O3 follow-up: capture baseline 2026-08-06 11:38:36Z-11:43:35Z for natural action 14885 at 11:43:36Z.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
D4 O3 baseline 2026-08-06 11:38:36Z-11:43:35Z; action 14885 at 11:43:36Z.

[[2026-08-05]] Wed 17:44
D4 O3 follow-up: capture baseline 2026-08-06 11:38:36Z–11:43:35Z; observe natural action 14885 after 11:43:36Z.

[[2026-08-06]] Thu 20:10
Closed from completed D1/D2/D3/D4 evidence. D4 O3 proved in watch-reports/D04-2026-08-06.md: renewal order 12897 completed at 20.00 for subscription 12234, confirming the stepped price had already crossed on renewal #2 and persisted on renewal #3.
