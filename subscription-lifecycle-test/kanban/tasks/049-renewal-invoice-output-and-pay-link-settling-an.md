---
id: 49
title: Renewal invoice output and pay-link settling an unpaid invoice on Stripe and Paddle
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-03
due: "2026-08-26"
estimate: 2h
depends_on:
    - 58
    - 23
    - 29
class: standard
---

> **SLT-ADM-07** · group `admin` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Verify the customer-facing renewal invoice: what an unpaid renewal order shows in my-account and on order-pay, that totals are right with taxes off and no signup-fee line, and that the Pay link settles it on Stripe and Paddle. The unpaid window is real on both — Stripe leaves the order `pending` from `due+k-6h` to `due+k`, Paddle's `processRenewalPayment()` no-ops (SLT-REF-09 A).

## Scope
- Gateway: both
- Checkout: block (order-pay page)
- Account: existing (slt2-core, slt2-paddle)
- Plugins: both

## Preconditions
- `SLT-PROD-04` is done and its `SLT2 Signup Fee Daily` product id is in the registry. This task is the sole owner of the D3 `slt2-core` purchase; no other task may create `S_FEE`.
- For the D4 invoice checks, SLT-ADM-06 is done; `S_FEE` was created by this task's Phase A; `SLT2 Paddle Daily` ($11.00/day) was bought D2 by `slt2-paddle`; ids are in the registry.
- If CHK-04 has no numeric `SUB_PAD`, run and preserve the Stripe leg, update the upstream issue, and leave this composite card blocked until the Paddle source is corrected. Do not buy a substitute or invent an alias.
- Stripe's renewal-invoice email is suppressed for auto-renew automatic subs (`EmailManager.php:504-510`) — a negative check, not a bug.
- Sessions: `cust-adm07-stripe-SLT-ADM-07`, `cust-adm07-paddle-SLT-ADM-07`.

## Test data
| Item | Value |
|---|---|
| Stripe | S_FEE, $9.00 renewal, card `4242 4242 4242 4242` |
| Paddle | SUB_PAD, $11.00 renewal, sandbox overlay |
| Amounts | USD, no tax/fee row |

## Steps
### Phase A — D3 (2026-08-26), after 12:00 and before SLT-EML-12

0. In `cust-adm07-stripe-SLT-ADM-07`, log in as `slt2-core`. Verify both carts empty, resolve the exact `SLT2 Signup Fee Daily` product ID, record `SUBCOUNT_PRE`, and prove the account has no subscription for it. If the preflight fails, create/update the mandatory QA issue, close the session and leave the card blocked until a clean source can be created.
1. Save `M_CREATE=$(mailpit-agent latest-id 2>/dev/null || true)`. Open the block checkout with only `SLT2 Signup Fee Daily`; if one-click redirects there automatically, record it and continue. Explicitly select Stripe, require the total to be **$24.00** (`$9.00` first cycle + one `$15.00` signup fee), with no tax row, and capture `SLT-ADM-07-00-create.png` before card entry. Pay with `4242 4242 4242 4242`, `12/34`, CVC `123` without capturing the populated hosted frame.
2. Capture the safe receipt as `SLT-ADM-07-00a-created-receipt.png` and record numeric shell variable `ORDER_FEE`. Resolve that order's exact `_subscription_ids` JSON through a strict one-element numeric `jq -e` guard and assign it to `S_FEE`; abort unless both variables match `^[0-9]+$`. Cross-check numeric `$S_FEE` against reverse `_parent_order_id=$ORDER_FEE`, the recorded slt2-core customer/product IDs, and `SUBCOUNT_POST == SUBCOUNT_PRE + 1`. Require the subscription active with `_recurring_amount=9.00`, `_completed_payments=1`, and the initial order total `$24.00`. Record `_next_payment_date`; compute `k` without interpolating an alias (`php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("k=%ds\n",$h%21600);' "$S_FEE"`), and derive the D4 invoice window `[due+k-6h, due+k]` in both UTC and site time.
3. Reconcile the complete initial purchase delta after `M_CREATE` and require the exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs. Empty the cart, verify persistent-cart meta empty, then append `ORDER_FEE`, `S_FEE`, count delta, due, `k`, both exact action IDs/times, `invoice−5m`/`charge−5m`, the four mail IDs, and the future D4 manual-payment exception to `slt2-catalog-registry` and the D03 watch report. Close the Stripe session and leave this card `in-progress`.

### Phase B — D4 (2026-08-27), inside each real invoice window

4. Resolve registry alias `S_FEE` into a same-named numeric shell variable and re-check its order/customer/product relationship. Resolve `SUB_PAD` only if the CHK-04 handoff exists; otherwise record the cited `BLOCKED` Paddle branch and continue Stripe without aborting. Recompute each available target's `k` and window. No earlier than five minutes before S_FEE's exact invoice action, publish `FEE_INVOICE_PRE=$(mailpit-agent latest-id)`; after it runs, resolve numeric `R_FEE` from `_pending_renewal_order_id`, require reverse `_subscription_id=$S_FEE` plus the exact scheduled-date/cycle metadata, and inspect the bounded delta for no `renewal_invoice`. For available Paddle, use the same exact relationship/gate procedure and `PAD_INVOICE_PRE`; never select either renewal order by recency.
5. Inside the S_FEE window, reopen `cust-adm07-stripe-SLT-ADM-07`, log in at `https://mirror-help.arrayhash.com/my-account/` as `slt2-core`, open `/my-account/view-subscription/$S_FEE/`, and capture `SLT-ADM-07-01-unpaid.png` showing exact `R_FEE` as `Pending payment` with its **Pay** button.
6. Click **View** on exact `R_FEE`; record `SLT2 Signup Fee Daily x 1` at `$9.00`, total `$9.00`, confirm no `Subscription Signup Fee` or tax row, and capture `SLT-ADM-07-02-totals.png`.
7. Click **Pay**; record the order-pay URL (`/checkout/order-pay/<R_FEE>/?pay_for_order=true&key=…`), offered methods, and capture the unpopulated page as `SLT-ADM-07-03-pay.png`. Immediately before submitting payment save `STRIPE_PAY_PRE=$(mailpit-agent latest-id)`. Pay with the test card without capturing the populated frame; capture the safe receipt as `SLT-ADM-07-04-paid.png`.
8. Require `mailpit-agent wait-new "$STRIPE_PAY_PRE" 180 "Payment received for subscription #$S_FEE"`; save/show the exact match and classify every message newer than `STRIPE_PAY_PRE`, including only WooCommerce mail linked to `R_FEE`.
9. `wp db query "SELECT id,status,total_amount FROM wp_wc_orders WHERE id=$R_FEE" --allow-root`; `wp post meta list "$S_FEE" --keys=_next_payment_date,_completed_payments,_pending_renewal_order_id --allow-root`.
10. If `SUB_PAD` is available, open `/my-account/view-subscription/$SUB_PAD/` as `--session cust-adm07-paddle-SLT-ADM-07` (login `slt2-paddle`) and capture `SLT-ADM-07-05-paddle.png` showing exact `R_PAD` plus the note "awaiting automatic charge from Paddle". Otherwise skip only the Paddle assertions in steps 10-11 under the recorded fallback; still execute step 12's final review immediately after Stripe closes, without waiting for a nonexistent D5 Paddle check.
11. For available `R_PAD`, open its detail/pay flow; total must read `$11.00`, with no fee or tax. Capture the unpopulated pay state as `SLT-ADM-07-06-paddle-pay.png`. Immediately before submitting save `PADDLE_PAY_PRE=$(mailpit-agent latest-id)`, complete the Paddle sandbox overlay without capturing populated card fields, and capture only the safe paid result as `SLT-ADM-07-07-paddle-paid.png`. Require `mailpit-agent wait-new "$PADDLE_PAY_PRE" 180 "Payment received for subscription #$SUB_PAD"`; save/show the exact match and classify the complete delta, allowing only WooCommerce mail linked to `R_PAD`. Record any Paddle overlay console error.
12. Deferred watch check (24 h): reopen the Paddle session and re-check exact `R_PAD`/`SUB_PAD`. A second paid/retroactive order for the same cycle creates/updates a mandatory QA issue with full proof. Mark done only after the Stripe and Paddle invoice/pay-link branches both pass; a missing Paddle source remains a blocker.

## Expected results
1. R_FEE and R_PAD exist unpaid inside the predicted window with a **Pay** button that disappears once paid.
2. R_FEE detail: one line, qty 1, `$9.00`; total `$9.00`; no signup-fee line (that $15.00 cart fee is charged only at signup) and no tax row.
3. After paying R_FEE: status Processing/Completed, `total_amount = 9.00`; on S_FEE `_pending_renewal_order_id` deleted, `_last_payment_date` set, `_completed_payments` +1, `_next_payment_date` +1 day from `_renewal_scheduled_date`, not from the pay time.
4. The later `arraysubs_process_renewal` no-ops (`isRenewalDue()` false); no second order for the same `_renewal_cycle_number`.
5. R_PAD reaches a paid status at `$11.00` with `_paddle_transaction_id` set.
6. One `payment_successful` per leg plus WooCommerce's order mail; no renewal-invoice mail on Stripe.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` + admin counterpart | Phase A initial order paid | slt2-core + admin | `is active` / `New subscription #` | list after `M_CREATE` |
| 2 | WooCommerce initial-order pair | Phase A initial order paid | slt2-core + admin | order subject | list after `M_CREATE` |
| 3 | `payment_successful` | R_FEE paid (step 7) | slt2-core | `Payment received for subscription #<S_FEE>` | exact wait after `STRIPE_PAY_PRE`; save/show match and full delta |
| 4 | WooCommerce order mail | R_FEE paid | slt2-core | order number `R_FEE` | correlate inside the `STRIPE_PAY_PRE` delta |
| 5 | `payment_successful` | R_PAD paid (step 11) | slt2-paddle | `Payment received for subscription #<SUB_PAD>` | exact wait after `PADDLE_PAY_PRE`; save/show match and full delta |
| 6 | `renewal_invoice` NONE EXPECTED | automatic invoice creation | — | `Invoice for subscription` | No such subject in the bounded `FEE_INVOICE_PRE` or available `PAD_INVOICE_PRE` delta |

## Evidence to capture
- Screenshots `SLT-ADM-07-00-create.png`, `-00a-created-receipt.png`, `-01-unpaid.png`, `-02-totals.png`, `-03-pay.png`, `-04-paid.png`, `-05-paddle.png`, `-06-paddle-pay.png`, `-07-paddle-paid.png`; no image may contain a full card number.
- Order/subscription IDs, count delta, `k` values, `M_CREATE`, `FEE_INVOICE_PRE`, optional `PAD_INVOICE_PRE`, `STRIPE_PAY_PRE`, `PADDLE_PAY_PRE`, exact-match/full-delta Mailpit IDs, gateway transaction IDs.

## Pass criteria
- [ ] Phase A creates exactly one `S_FEE` for `slt2-core`; initial total is $24.00 and its ids/schedule are registered
- [ ] Phase A exact +1 HPOS relationship and complete four-message checkout set proved; D4 invoice/charge deadlines handed off before session closure
- [ ] Unpaid renewal order with a Pay button on both gateways
- [ ] Totals exact ($9.00, $11.00); no fee row, no tax row
- [ ] Pay link URL correct; settles the invoice on Stripe and on Paddle
- [ ] Schedule advanced from `_renewal_scheduled_date`; no duplicate order
- [ ] `payment_successful` per leg; no Stripe renewal-invoice mail

## Isolation / teardown
- Paying inside the invoice window moves each charge forward by at most 6 h; cadence unchanged. Post the paid timestamps to the registry so the watch does not misread them as early renewals.
- Touches only S_FEE, available SUB_PAD, and their orders; no setting changed. Empty both carts, verify both persistent-cart metas are empty, and close only `cust-adm07-stripe-SLT-ADM-07` and `cust-adm07-paddle-SLT-ADM-07` after each dated leg.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
