---
id: 24
title: 'Renewal-invoice leg: pending order and invoice email at due+offset-6h, before the charge leg'
status: todo
priority: high
created: 2026-08-02T03:43:04.97462704+02:00
updated: 2026-08-02T03:43:15.269507385+02:00
tags:
    - renewal
    - day-01
due: "2026-08-03"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 5
class: standard
---

> **SLT-REN-03** · group `renewal` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove the renewal-invoice leg is a real, separate event: a `pending` renewal order must appear at `due + k − 6h`, six hours BEFORE the charge leg, with correct cycle metadata, and `renewal_invoice` must be sent then. Stripe suppresses that email for automatic subs with auto-renew on, so this task flips `_auto_renew` to `off` on ONE SLT subscription — the only supported way to observe it.

## Scope
- Gateway: Stripe test (charge leg falls to the manual path)
- Checkout: block
- Account: guest->new (`slt-invoice@example.test`)
- Plugins: both

## Preconditions
- SLT-SETUP-01/02 + SLT-PROD-01 done. This task **creates** `slt-invoice@example.test` at checkout; it must not pre-exist and is deleted by SLT-SETUP-99B.
- `slt-core` owns SLT Daily Core already; the second buyer must be a different customer, else `auto_migrate_on_checkout` migrates instead of creating.
- Buy **12:30–13:00 site on D1 = 2026-08-03**; the D2 invoice leg then lands 06:30–12:45 site.
- `_auto_renew` is per-subscription SLT meta, not a global setting. No global changes, no drains.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, $10.00/day |
| Account | guest `slt-invoice@example.test` |
| Card | 4242 4242 4242 4242, 12/34, CVC 123 |
| Session | `guest-SLT-REN-03` |
| Due | 2026-08-04 ≈ purchase time |

## Steps
1. `PRE3=$(mailpit-agent latest-id)`; confirm no `slt-invoice` user exists.
2. `agent-browser --session guest-SLT-REN-03 open ".../cart/"`; assert EMPTY.
3. `/?p=<Daily Core ID>` → **Add to cart** → `/checkout/`.
4. Guest billing: `slt-invoice@example.test`, `SLT Invoice`, `1 SLT Way`, Dhaka, BD, 1207. Pay with **Stripe**, **Place Order**. Record site-time.
5. `mailpit-agent wait-new "$PRE3" 120 "is active"`; then `list 20`.
6. Resolve `SUBID3` (newest `arraysubs_data`); record `_next_payment_date`, `_payment_gateway`, `_completed_payments`.
7. `wp post meta update SUBID3 _auto_renew off --allow-root`; re-read; note as an intentional per-subscription deviation.
8. Compute `k3` (crc32 one-liner); from `wp_actionscheduler_actions WHERE args='[SUBID3]'` take both legs' `scheduled_date_gmt`.
9. `PRE3B=$(mailpit-agent latest-id)`; wait for the invoice leg (`due+k3−21600s`, 06:30–12:45 site 2026-08-04).
10. `wait-new "$PRE3B" 900 "Invoice for subscription"`; read `wp_wc_orders` for that customer; re-read `SUBID3`.
11. Screenshot the order in **WooCommerce → Orders** with its notes.
12. After the charge leg (`due + k3`): re-read order status, notes, subscription meta; `list 50`.

## Expected results
1. Checkout creates `slt-invoice`; parent order paid $10.00; subscription `arraysubs-active`, `_completed_payments=1`.
2. At `due + k3 − 21600s` (±90 s) a NEW order exists: `wc-pending`, $10.00, `_is_renewal_order=yes`, `_subscription_id=SUBID3`, `_renewal_cycle_number=1`, `_renewal_scheduled_date` = D2 due, `created_via` empty.
3. The gap between that order's `date_created_gmt` and the charge leg's `scheduled_date_gmt` is exactly 21600 s.
4. `_pending_renewal_order_id` = that order id; status still `arraysubs-active` — invoice creation never changes it.
5. `renewal_invoice` arrives at the invoice leg, subject `Invoice for subscription #SUBID3`, linking that order's pay URL; `_arraysubs_renewal_invoice_email_sent` set.
6. The charge leg completes but does NOT charge: order stays `wc-pending` with the note `Renewal order created. Awaiting manual payment.`; `_completed_payments` 1, retry attempts 0.
7. Both legs' logs read `via WP Cron`, not `via WP CLI`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | Place Order | customer/admin | `is active` | `wait-new $PRE3 120 "is active"` |
| 2 | renewal_invoice | invoice leg `due+k3−6h` | slt-invoice@example.test | `Invoice for subscription #SUBID3` | `wait-new $PRE3B 900 "Invoice for subscription"` |
| 3 | NONE EXPECTED (Woo account/order mail is record-only): payment_successful, payment_failed, renewal_reminder | `due+k3` | — | — | `list 50` |

## Evidence to capture
- `SLT-REN-03-01-checkout.png`, `-02-renewal-order.png`, `-03-order-notes.png`; `SUBID3`, user id, order ids, `k3`, leg timestamps, the invoice email's Mailpit id and text.

## Pass criteria
- [ ] Guest checkout created `slt-invoice`; sub active
- [ ] Pending renewal order at `due+k3−6h` (±90 s), cycle-1 metadata, `created_via` empty
- [ ] Invoice-to-charge gap exactly 21600 s
- [ ] `renewal_invoice` received before the charge leg
- [ ] Charge leg leaves it `wc-pending` with the manual-payment note, no mail
- [ ] Legs logged `via WP Cron`; no drain

## Isolation / teardown
- Deliberate non-auto-renew case: it never renews again, goes `arraysubs-on-hold` ~1 day after due and `arraysubs-cancelled` ~3 days later. That ladder is EXPECTED, and maps here.
- Never reuse `slt-invoice@example.test`; `_auto_renew` stays off. Empty the cart; close only `guest-SLT-REN-03`.

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
