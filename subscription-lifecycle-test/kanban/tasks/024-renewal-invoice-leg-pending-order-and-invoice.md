---
id: 24
title: 'Renewal-invoice leg: pending order and invoice email at due+offset-6h, before the charge leg'
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
    - 11
    - 5
class: standard
---

> **SLT-REN-03** · group `renewal` · scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove the renewal-invoice leg is a real, separate event: a `pending` renewal order must appear at `due + k − 6h`, six hours BEFORE the charge leg, with correct cycle metadata, and `renewal_invoice` must be sent then. Stripe suppresses that email for automatic subs with auto-renew on, so this task flips `_auto_renew` to `off` on ONE SLT2 subscription — the only supported way to observe it.

## Scope
- Gateway: Stripe test (charge leg falls to the manual path)
- Checkout: block
- Account: guest->new (`slt2-invoice@example.test`)
- Plugins: both

## Preconditions
- SLT-SETUP-01/02 + SLT-PROD-01 done. This task **creates** `slt2-invoice@example.test` at checkout; it must not pre-exist and is deleted by SLT-SETUP-99B.
- `slt2-core` owns SLT2 Daily Core already; the invoice fixture must use a different customer. At `one_per_customer=false`, reusing `slt2-core` creates a second ambiguous subscription rather than migrating.
- Buy **12:30–13:00 site on D1 = 2026-08-24**; the D2 invoice leg then lands 06:30–13:00 site.
- `_auto_renew` is per-subscription SLT2 meta, not a global setting. No global changes, no drains.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core, $10.00/day |
| Account | guest `slt2-invoice@example.test` |
| Card | 4242 4242 4242 4242, 12/34, CVC 123 |
| Session | `guest-SLT-REN-03` |
| Due | 2026-08-25 ≈ purchase time |

## Steps
1. `PRE3=$(mailpit-agent latest-id)`; confirm no `slt2-invoice` user exists.
2. `agent-browser --session guest-SLT-REN-03 open ".../cart/"`; assert EMPTY.
3. `/product/slt2-daily-core/` → **Add to cart** → `/checkout/`.
4. Guest billing: `slt2-invoice@example.test`, `SLT2 Invoice`, `1 SLT2 Way`, Dhaka, BD, 1207. Because this subscription cart forces account creation, set password `SltQa!2026#Pass` if the account/password field is shown. Pay with **Stripe**, **Place Order**. Record the exact parent `ORDER` and site-time. Once the received page has loaded, open `/cart/` in the same now-authenticated session, require it and the new user's persistent-cart meta to be EMPTY, and capture `SLT-REN-03-01b-cart-empty-after.png`.
5. `mailpit-agent wait-new "$PRE3" 120 "is active"`; then inspect and classify the complete owner-filtered delta after `$PRE3`. Require the normal five-message checkout set for this guest-to-new-account purchase: customer new-account, admin WC new-order, customer WC completed-order, customer `new_subscription`, and admin `admin_new_subscription`.
6. Resolve the numeric ID from `ORDER`'s exact `_subscription_ids` linkage, require exactly one ID, and cross-check `_parent_order_id=ORDER` plus `_customer_id=<slt2-invoice user ID>`; never select by recency. Assign it to shell variable `SUBID3`, abort unless `[[ "$SUBID3" =~ ^[0-9]+$ ]]`, and record `_next_payment_date`, `_payment_gateway`, `_completed_payments`.
7. `wp post meta update "$SUBID3" _auto_renew off --allow-root`; re-read; note as an intentional per-subscription deviation.
8. Compute `k3` with the numeric `$SUBID3`. Run `wp db query "SELECT action_id,hook,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE status='pending' AND hook IN ('arraysubs_generate_renewal_invoice','arraysubs_process_renewal') AND JSON_UNQUOTE(JSON_EXTRACT(args,'\$[0]'))='$SUBID3' ORDER BY scheduled_date_gmt,action_id;" --allow-root`; record only that subscription's pending invoice and charge rows and their exact action IDs and `scheduled_date_gmt` values.
9. `PRE3B=$(mailpit-agent latest-id)`; calculate the invoice gate (`due+k3−21600s`, 06:30–13:00 site 2026-08-25), and persist `PRE3B` plus the exact invoice/charge action IDs in the registry and task evidence. If it is more than 15 minutes away, leave this card in progress with the exact gate and stop this leg; the phase runner must resume it later.
10. Once inside the 15-minute window, run `mailpit-agent wait-new "$PRE3B" 900 "Invoice for subscription #$SUBID3"`; save/show the exact match and classify every message newer than `PRE3B`; read `wp_wc_orders` for that customer and re-read `$SUBID3`.
11. Screenshot the order in **WooCommerce → Orders** with its notes.
12. After the charge leg (`due + k3`): re-read order status, notes, and subscription meta. Inspect every message newer than `PRE3B`; require no payment-success, payment-failed, or renewal-reminder subject naming `SUBID3`, while classifying unrelated mail.

## Expected results
1. Checkout creates `slt2-invoice`; parent order paid $10.00; subscription `arraysubs-active`, `_completed_payments=1`.
2. At `due + k3 − 21600s` (±90 s) a NEW order exists: `wc-pending`, $10.00, `_is_renewal_order=yes`, `_subscription_id=SUBID3`, `_renewal_cycle_number=2` (the initial payment is cycle 1), `_renewal_scheduled_date` = D2 due, `created_via` empty.
3. The gap between that order's `date_created_gmt` and the charge leg's `scheduled_date_gmt` is exactly 21600 s.
4. `_pending_renewal_order_id` = that order id; status still `arraysubs-active` — invoice creation never changes it.
5. `renewal_invoice` arrives at the invoice leg, subject `Invoice for subscription #SUBID3`, linking that order's pay URL; `_arraysubs_renewal_invoice_email_sent` set.
6. The charge leg completes but does NOT charge: order stays `wc-pending` with the note `Renewal order created. Awaiting manual payment.`; `_completed_payments` 1, retry attempts 0.
7. Both legs' logs read `via WP Cron`, not `via WP CLI`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC Customer new account | account minted at checkout | slt2-invoice@example.test | `Your account on` | Complete owner-filtered delta after `$PRE3`; save/show the exact matching id |
| 2 | WC New order | paid checkout | admin | `New order #ORDER` | Complete owner-filtered delta after `$PRE3`; save/show the exact matching id |
| 3 | WC Completed order | virtual-only order completes | slt2-invoice@example.test | `is on its way` | Complete owner-filtered delta after `$PRE3`; save/show the exact matching id |
| 4 | new_subscription + admin_new_subscription | Place Order | customer/admin | `is active` / `New subscription #` | `mailpit-agent wait-new "$PRE3" 120 "is active"`; save/show both exact ids from the complete delta |
| 5 | renewal_invoice | invoice leg `due+k3−6h` | slt2-invoice@example.test | `Invoice for subscription #SUBID3` | exact 900-second wait after `PRE3B`; exact match plus full delta |
| 6 | NONE EXPECTED: payment_successful, payment_failed, renewal_reminder | `due+k3` | — | — | absent for `SUBID3` from the complete `PRE3B` delta |

## Evidence to capture
- `SLT-REN-03-01-checkout.png`, `-01b-cart-empty-after.png`, `-02-renewal-order.png`, `-03-order-notes.png`; `SUBID3`, user id, order ids, `k3`, leg timestamps/action IDs, `PRE3B`, the five checkout-message IDs, and the exact-match/full-delta renewal Mailpit ids and text.

## Pass criteria
- [ ] Guest checkout created `slt2-invoice`; recovered sub active, with the checkout failure separately filed
- [ ] Complete five-message recovered-checkout set recorded: WC customer new-account, WC admin new-order, WC customer completed-order, and both ArraySubs signup messages
- [ ] Cart proved empty before checkout and again after checkout
- [ ] New user's persistent-cart meta empty after checkout; `SUBID3` resolved by exact parent-order linkage
- [ ] Pending renewal order at `due+k3−6h` (±90 s), renewal-cycle-2 metadata, `created_via` empty
- [ ] Invoice-to-charge gap exactly 21600 s
- [ ] `renewal_invoice` received before the charge leg
- [ ] Charge leg leaves it `wc-pending` with the manual-payment note, no task-attributable charge mail
- [ ] Legs logged `via WP Cron`; no drain

## Isolation / teardown
- Deliberate non-auto-renew case: it never renews again, goes `arraysubs-on-hold` ~1 day after due and `arraysubs-cancelled` ~3 days later. That ladder is EXPECTED, and maps here.
- Never reuse `slt2-invoice@example.test`; `_auto_renew` stays off. Empty the cart; close only `guest-SLT-REN-03`.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
