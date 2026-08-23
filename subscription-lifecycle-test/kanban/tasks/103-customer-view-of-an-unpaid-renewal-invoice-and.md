---
id: 103
title: Customer view of an unpaid renewal invoice and paying it from the portal before the automatic charge leg
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-07
due: "2026-08-30"
estimate: 1.5h
depends_on:
    - 70
    - 58
    - 49
class: standard
---

> **SLT-MYA-04** · group `admin` · scheduled **D07** (2026-08-30)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Exercise the only window in which a customer can see and pay an unpaid renewal invoice: between the invoice leg at `due + k - 6h` and the charge leg at `due + k` (`RenewalScheduler.php:135-188`). Verify the portal view of that pending order, pay it from the `Pay` button, and prove the schedule math matches the unassisted path and the charge leg is a clean no-op.

## Scope
- Gateway: Stripe test
- Checkout: N/A (WooCommerce order-pay page reached from the portal)
- Account: existing (`slt2-core`)
- Plugins: both

## Preconditions
- SLT-MYA-01 done. `SLT2 Signup Fee Daily` (`S_FEE`) is `arraysubs-active`, bought by slt2-core in SLT-ADM-07 Phase A on D3.
- **This task deliberately customer-pays exactly one cycle of one subscription.** Before acting, post to `slt2-catalog-registry`: "SLT-MYA-04 customer-pays the 2026-08-30 cycle of S_FEE; that cycle is NOT an unattended charge." Without this note the watch files a false bug.
- Do NOT use `SLT2 Retry Daily` - paying its failed order would rescue another task's dunning ladder. This task runs no Action Scheduler drain.

## Test data
| Item | Value |
|---|---|
| Account | slt2-core / `SltQa!2026#Pass`, session `--session customer-MYA-04-SLT-MYA-04` |
| Product | SLT2 Signup Fee Daily, $9.00/day; signup fee charged on the initial order only |
| Renewal total | `$9.00` - no fee line, no tax line |
| Card | the saved Stripe method on the account |

## Steps
1. Resolve registry alias `S_FEE` into shell variable `S_FEE`, require exactly one registry match, and abort unless `[[ "$S_FEE" =~ ^[0-9]+$ ]]`; cross-check its recorded parent order, slt2-core customer, and exact product. Run `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("k=%ds\n",$h%21600);' "$S_FEE"`. Read `D = _next_payment_date` (UTC): invoice fires at `D+k-6h`, charge at `D+k`. Convert both to UTC+6 into Notes.
2. Record the exact pending invoice/charge action IDs and pre-invoice renewal-order set for numeric `S_FEE`. Inside `[invoice gate−300s, invoice gate)` set `MB04=$(mailpit-agent latest-id)` and persist it; never take this baseline earlier than the final five minutes.
3. Shortly after the exact invoice action completes naturally `via WP Cron`, resolve the sole new order from numeric `S_FEE` plus `_renewal_scheduled_date=D`/cycle relationship and require its reverse subscription link; never select the newest slt2-core order. Confirm it is pending, `_is_renewal_order=yes`, and that `S_FEE` has `_pending_renewal_order_id` and remains `arraysubs-active`.
4. Open `/my-account/view-subscription/<S_FEE>/` -> log in -> screenshot `Related Orders`. Record the new row (number, date, status, total) and that it offers `View` + `Pay` (`view-subscription.php:811`) while older rows offer only `View`.
5. Open the order via `View` and screenshot: line item `SLT2 Signup Fee Daily`, total `$9.00`, no fee line, no tax line.
6. Click `Pay` -> `snapshot -i`; confirm amount due `$9.00` and the Stripe box offers the saved card. `MB04b=$(mailpit-agent latest-id)`, then submit payment.
7. Poll immutable `MB04b` in repeated calls no longer than 60 seconds through the five-minute cutoff for `Payment received for subscription #$S_FEE`, save/show the exact match, and classify every message newer than `MB04b`. Require the exact WooCommerce **New order** mail to the admin and require zero customer WooCommerce processing/completed mail for this `_is_renewal_order=yes` order. Screenshot the safe order-received page. Verify order `processing`/`completed`, `_last_gateway_transaction_id` set, `_pending_renewal_order_id` deleted, `_completed_payments` +1, and `_next_payment_date` = `D + 1 day` exactly - computed from `_renewal_scheduled_date`, not payment time.
8. Reload the portal detail page: `Next Payment:` shows the new date in site-local time and the paid order offers only `View`.
9. **Same day after `D+k`:** confirm the recorded charge action completed `via WP Cron` with no new order and no second charge; compare the exact relationship/order set from step 2 and screenshot the action row. Close `customer-MYA-04-SLT-MYA-04`; do not retain it overnight.
10. **Watch day D8 = 2026-08-31 (morning check):** require exactly one relationship-exact paid renewal order for the 2026-08-30 cycle, one `payment_successful` mail, and the 2026-08-31 cycle queued normally. Independently review the registry exception, invoice/charge/order/portal/mail evidence, move the card through `review` to `done`, and require Review to return to zero. Any live defect goes only in `qa/issues/` kanban card named `SLT-MYA-04-<concise-slug>` with task/stage/plan path; subscription/order/action/message IDs; user ID/login/email/role; exact routes/session/gates; reproduction; expected/actual; and UI/meta/queue/log/order/Mailpit proof.

## Expected results
1. A pending renewal order for S_FEE exists after `D+k-6h`, total `$9.00`, `_is_renewal_order=yes`, `_renewal_scheduled_date = D`; S_FEE stays `arraysubs-active` with `_pending_renewal_order_id` until payment.
2. The portal row shows Pending payment and offers `View` + `Pay`; paid rows offer only `View`. The order view shows one $9.00 line, no fee line, no tax line.
3. The order-pay page charges `$9.00` on the saved card and succeeds; afterwards `_pending_renewal_order_id` is deleted, `_completed_payments` +1, a transaction id recorded.
4. `_next_payment_date` = `D + 1 day` to the second - identical to the unattended path.
5. The charge leg at `D+k` is a no-op: no second order, no second Stripe charge, no failure note.
6. Watch day D8 (2026-08-31): one paid renewal order and one payment-successful mail for that cycle.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Invoice at `D+k-6h` (suppressed for auto-payment subs, `EmailManager.php:504-510`) and the `D+k` charge leg | - | - | No `Invoice for subscription #<S_FEE>` between `MB04` and step 6; no `payment_failed` and no second `Payment received` after step 9 |
| 2 | `payment_successful` plus WooCommerce New order; no customer processing/completed order mail | Customer pays from the order-pay page | `slt2-core` plus `admin_email` | `Payment received for subscription #<S_FEE>`; `New order #<renewal-order-id>` | exact subject wait after `MB04b`; save/show both matches and classify the complete delta by subscription/order id |

## Evidence to capture
- Screenshots `SLT-MYA-04-01-orders-pending.png`, `-02-order-detail.png`, `-03-order-pay.png`, `-04-received.png`, `-05-action-noop.png`; `k`, `D` and both window boundaries in UTC and site-local; renewal order id; `MB04`/`MB04b`; exact-match/full-delta Mailpit ids; before/after `_next_payment_date`.

## Pass criteria
- [ ] Pending renewal order appeared inside `[D+k-6h, D+k)` with the correct total and metas
- [ ] Pay button shown only on the unpaid order; total $9.00 with no fee and no tax line
- [ ] Payment succeeded and all post-payment metas are correct
- [ ] `_next_payment_date` = `D + 1 day` exactly; charge leg at `D+k` was a no-op with no second order
- [ ] Invoice mail absent; payment-successful mail present exactly once
- [ ] Final-five-minute baseline, exact cron/order relationships, session teardown, and independent review completed with Review empty

## Isolation / teardown
- Touches only slt2-core's S_FEE and only the 2026-08-30 cycle; the registry note is mandatory so the D8 watch does not read this as a failed unattended renewal.
- Nothing force-run, no hook drained, no setting changed; the subscription is left active with its next cycle queued. Close only `--session customer-MYA-04-SLT-MYA-04`.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
