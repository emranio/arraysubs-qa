---
id: 4
title: 'SLT2 Fixed Three Cycles: two renewals, short-horizon expiring-soon, final expiry'
status: blocked
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T22:35:28.033222557+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - day-00
due: "2026-08-23"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 6
    - 131
class: standard
---

## Current execution blocker — 2026-08-23 site date

Blocked by critical shared issue `qa/issues` #1 / preflight task `131`. Finite product `31347` and customer `474` are ready, but starting the timed two-renewal/expiry chain before Stripe readiness passes would invalidate the window. No checkout/order/subscription/charge was attempted; rerun immediately after task 131 passes.

> **SLT-LIFE-04** · group `renewal` · scheduled **D00** (2026-08-23)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Walk SLT2 Fixed Three Cycles (day/2, length 3, $7.00) from checkout through two natural renewals to termination. Revalidate the finite-length date calculation, the short-horizon case where the configured 7-day expiring-soon lead is already inside/past at signup, one expiring-soon notification, final expiry state/mail, and absence of any extra renewal.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt2-core`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01..03 and SLT-PROD-06 done; `_subscription_length=3`, `_subscription_interval=2`, `_subscription_period=day`, `_regular_price=7.00`. `SLT-SETUP-04` is the later coupon setup and is unrelated to this D0 purchase.
- Buy after 12:00 site time (audit C02) and only on D0, or expiry falls outside the window.
- If a SLT-CHK-* task already bought this for `slt2-core`, adopt that subscription ID and skip steps 2-3 (`slt2-core` never buys the same product twice, C08).

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Fixed Three Cycles (`slt2-fixed-three-cycles`) |
| Account | `slt2-core` / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Amounts | $7.00 today + $7.00 x2 renewals = $21.00 |
| Dates | buy 2026-08-23 at T site; due1 = 08-25 T; due2 = 08-27 T |

## Steps
1. `PREV=$(mailpit-agent latest-id)`.
2. `agent-browser --session life04-SLT-LIFE-04 open "https://mirror-help.arrayhash.com/my-account/"` -> log in as `slt2-core`, then open `/cart/` and STOP and write a QA issue card under `qa/issues/` if it is not empty. Open `/product/slt2-fixed-three-cycles/` -> `snapshot -i` -> add to cart.
3. Open `https://mirror-help.arrayhash.com/checkout/` (block checkout), pay with the saved Stripe test card or 4242, and Place order. Record O0, resolve S4 from O0's exact `_subscription_ids`, require one ID and cross-check parent order/customer/product, then assign the numeric ID to shell variable `S4`; never select by recency.
4. `wp post meta list "$S4" --keys=_next_payment_date,_end_date,_completed_payments,_subscription_length,_recurring_amount --allow-root`.
5. `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("%d\n",$h%21600);' "$S4"` -> offset k. Abort unless `S4` is numeric.
6. Screenshot the exact pending queue: require invoice/charge legs for due1 plus the current finite-lifecycle notification row(s). Record the natural `arraysubs_send_expiring_soon` action ID/gate and how the scheduler handles a lead point already before signup; do not hard-code an action ID.
7. `mailpit-agent wait-new "$PREV" 120 "is active"`, then inspect and classify the complete owner-filtered delta after `$PREV`.
8. At least five minutes before the live due1 charge action, store `LIFE04_REN1_PRE=$(mailpit-agent latest-id)` in the registry. **D2 (08-25, after due1+k):** repeat 4 and 6; read renewal order O1 by exact relationship, require one payment-success mail and reconcile the complete delta.
9. Observe the natural expiring-soon gate published in step 6. Set its Mailpit baseline only inside the five-minute pre-gate window, require the exact action to complete by WP Cron, one customer message with the correct end date/link, dedupe meta, and no duplicate on the next scheduler sweep. Never hand-schedule or manually run the hook.
10. At least five minutes before the exact D4 charge gate learned from the D2 schedule, store `LIFE04_REN2_PRE=$(mailpit-agent latest-id)` in the registry. **D4 (08-27, after due2+k):** repeat 4 and 6; read order O2 and the post status; run `mailpit-agent wait-new "$LIFE04_REN2_PRE" 900 "Payment received for subscription #$S4"`, then list all messages newer than `LIFE04_REN2_PRE` and identify the single `has expired` message by ID.

## Expected results
1. At creation `_end_date` absent, `_completed_payments=1`, `_next_payment_date` = 08-25 at the checkout clock time.
2. Renewal 1 charges $7.00 inside [due1+k, due1+k+5min]; `_completed_payments=2`; next date = 08-27 T.
3. Renewal 2 charges $7.00 inside [due2+k, due2+k+5min] on 08-27; `_completed_payments=3`; since 3 >= length, `_end_date` = the charge moment (08-27, NOT 08-29), `_next_payment_date` empty, status `arraysubs-expired`.
4. Zero pending renewal legs for S4 afterwards and no third renewal order.
5. Total charged $21.00, no tax line; exactly one natural `…is ending soon` message arrives before expiry and exactly one expired message arrives at the final charge.
6. Any date, notification, scheduling or access mismatch creates/updates the mandatory `qa/issues/` kanban card and blocks this task until rerun.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | checkout | customer + admin | `is active` / `New subscription #` | `mailpit-agent wait-new "$PREV" 120` |
| 2 | WC new order + completed order | O0 checkout | admin + customer | `New order #<O0>` / `is on its way` | Complete owner-filtered checkout delta after `$PREV`; save/show exact matching ids |
| 3 | WC new order | O1, O2 renewal orders | admin only | `New order #<order id>` | Complete owner-filtered renewal deltas after `LIFE04_REN1_PRE`/`LIFE04_REN2_PRE`; customer WC order mail is suppressed |
| 4 | payment_successful x2 | renewals 1, 2 | slt2-core@example.test | `Payment received for subscription #S4` | `mailpit-agent wait-new "$LIFE04_REN1_PRE" 900 ...` on D2 and `mailpit-agent wait-new "$LIFE04_REN2_PRE" 900 ...` on D4 |
| 5 | subscription_expired | status -> expired | slt2-core@example.test | `has expired` | identify exactly once among messages newer than `LIFE04_REN2_PRE` on D4 |
| 6 | expiring_soon x1 | natural finite-lifecycle action | slt2-core@example.test | `is ending soon` | exact pre-gate baseline, action and dedupe proof |
| 7 | NONE EXPECTED: duplicate expiring-soon, renewal reminder, renewal invoice | — | — | duplicate `is ending soon`, `renews soon`, `Invoice for subscription` | absent from complete task deltas |

## Evidence to capture
- Screenshots `SLT-LIFE-04-01-checkout.png`, `-02-pending-d0.png`, `-03-renewal1.png`, `-03a-expiring-soon-action.png`, `-03b-expiring-soon-mail.png`, `-04-expired.png`, `-05-pending-empty.png`.
- S4, O0/O1/O2, k, the three meta dumps, Mailpit IDs, checkout console errors.

## Pass criteria
- [ ] `_end_date` absent at creation, stamped at the final charge moment
- [ ] Two unattended $7.00 renewals inside [due+k, due+k+5min]
- [ ] Status `arraysubs-expired` immediately after payment 3
- [ ] Zero pending legs and no third renewal order after expiry
- [ ] Exact phase-specific mail set above, including one natural deduplicated expiring-soon notification and every negative

## Isolation / teardown
- S4 is terminal by D4. `SLT-SETUP-99A` neither cancels nor deletes the already-expired record; `SLT-SETUP-99B` deletes it on 2026-09-05 after the watch.
- Record natural notification action/dedupe state. No setting changed and no hook is force-run.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
