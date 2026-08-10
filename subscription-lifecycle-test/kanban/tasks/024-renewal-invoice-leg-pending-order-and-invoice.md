---
id: 24
title: 'Renewal-invoice leg: pending order and invoice email at due+offset-6h, before the charge leg'
status: done
priority: high
created: 2026-08-02T03:43:04.97462704+02:00
updated: 2026-08-05T08:40:48.589864882+02:00
started: 2026-08-05T08:40:48.58986394+02:00
completed: 2026-08-05T08:40:48.58986394+02:00
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
- `slt-core` owns SLT Daily Core already; the invoice fixture must use a different customer. At `one_per_customer=false`, reusing `slt-core` creates a second ambiguous subscription rather than migrating.
- Buy **12:30–13:00 site on D1 = 2026-08-03**; the D2 invoice leg then lands 06:30–13:00 site.
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
3. `/product/slt-daily-core/` → **Add to cart** → `/checkout/`.
4. Guest billing: `slt-invoice@example.test`, `SLT Invoice`, `1 SLT Way`, Dhaka, BD, 1207. Because this subscription cart forces account creation, set password `SltQa!2026#Pass` if the account/password field is shown. Pay with **Stripe**, **Place Order**. Record the exact parent `ORDER` and site-time. Once the received page has loaded, open `/cart/` in the same now-authenticated session, require it and the new user's persistent-cart meta to be EMPTY, and capture `SLT-REN-03-01b-cart-empty-after.png`.
5. `mailpit-agent wait-new "$PRE3" 120 "is active"`; then inspect and classify the complete owner-filtered delta after `$PRE3`. Require the normal five-message checkout set for this guest-to-new-account purchase: customer new-account, admin WC new-order, customer WC completed-order, customer `new_subscription`, and admin `admin_new_subscription`.
6. Resolve the numeric ID from `ORDER`'s exact `_subscription_ids` linkage, require exactly one ID, and cross-check `_parent_order_id=ORDER` plus `_customer_id=<slt-invoice user ID>`; never select by recency. Assign it to shell variable `SUBID3`, abort unless `[[ "$SUBID3" =~ ^[0-9]+$ ]]`, and record `_next_payment_date`, `_payment_gateway`, `_completed_payments`.
7. `wp post meta update "$SUBID3" _auto_renew off --allow-root`; re-read; note as an intentional per-subscription deviation.
8. Compute `k3` with the numeric `$SUBID3`. Run `wp db query "SELECT action_id,hook,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE status='pending' AND hook IN ('arraysubs_generate_renewal_invoice','arraysubs_process_renewal') AND JSON_UNQUOTE(JSON_EXTRACT(args,'\$[0]'))='$SUBID3' ORDER BY scheduled_date_gmt,action_id;" --allow-root`; record only that subscription's pending invoice and charge rows and their exact action IDs and `scheduled_date_gmt` values.
9. `PRE3B=$(mailpit-agent latest-id)`; calculate the invoice gate (`due+k3−21600s`, 06:30–13:00 site 2026-08-04), and persist `PRE3B` plus the exact invoice/charge action IDs in the registry and task evidence. If it is more than 15 minutes away, leave this card in progress with the exact gate and stop this leg; the phase runner must resume it later.
10. Once inside the 15-minute window, run `mailpit-agent wait-new "$PRE3B" 900 "Invoice for subscription #$SUBID3"`; save/show the exact match and classify every message newer than `PRE3B`; read `wp_wc_orders` for that customer and re-read `$SUBID3`.
11. Screenshot the order in **WooCommerce → Orders** with its notes.
12. After the charge leg (`due + k3`): re-read order status, notes, and subscription meta. Inspect every message newer than `PRE3B`; require no payment-success, payment-failed, or renewal-reminder subject naming `SUBID3`, while classifying unrelated mail.

## Expected results
1. Checkout creates `slt-invoice`; parent order paid $10.00; subscription `arraysubs-active`, `_completed_payments=1`.
2. At `due + k3 − 21600s` (±90 s) a NEW order exists: `wc-pending`, $10.00, `_is_renewal_order=yes`, `_subscription_id=SUBID3`, `_renewal_cycle_number=2` (the initial payment is cycle 1), `_renewal_scheduled_date` = D2 due, `created_via` empty.
3. The gap between that order's `date_created_gmt` and the charge leg's `scheduled_date_gmt` is exactly 21600 s.
4. `_pending_renewal_order_id` = that order id; status still `arraysubs-active` — invoice creation never changes it.
5. `renewal_invoice` arrives at the invoice leg, subject `Invoice for subscription #SUBID3`, linking that order's pay URL; `_arraysubs_renewal_invoice_email_sent` set.
6. The charge leg completes but does NOT charge: order stays `wc-pending` with the note `Renewal order created. Awaiting manual payment.`; `_completed_payments` 1, retry attempts 0.
7. Both legs' logs read `via WP Cron`, not `via WP CLI`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC Customer new account | account minted at checkout | slt-invoice@example.test | `Your account on` | Complete owner-filtered delta after `$PRE3`; save/show the exact matching id |
| 2 | WC New order | paid checkout | admin | `New order #ORDER` | Complete owner-filtered delta after `$PRE3`; save/show the exact matching id |
| 3 | WC Completed order | virtual-only order completes | slt-invoice@example.test | `is on its way` | Complete owner-filtered delta after `$PRE3`; save/show the exact matching id |
| 4 | new_subscription + admin_new_subscription | Place Order | customer/admin | `is active` / `New subscription #` | `mailpit-agent wait-new "$PRE3" 120 "is active"`; save/show both exact ids from the complete delta |
| 5 | renewal_invoice | invoice leg `due+k3−6h` | slt-invoice@example.test | `Invoice for subscription #SUBID3` | exact 900-second wait after `PRE3B`; exact match plus full delta |
| 6 | NONE EXPECTED: payment_successful, payment_failed, renewal_reminder | `due+k3` | — | — | absent for `SUBID3` from the complete `PRE3B` delta |

## Evidence to capture
- `SLT-REN-03-01-checkout.png`, `-01b-cart-empty-after.png`, `-02-renewal-order.png`, `-03-order-notes.png`; `SUBID3`, user id, order ids, `k3`, leg timestamps/action IDs, `PRE3B`, the five checkout-message IDs, and the exact-match/full-delta renewal Mailpit ids and text.

## Pass criteria
- [x] Guest checkout created `slt-invoice`; recovered sub active, with the checkout failure separately filed
- [x] Complete five-message recovered-checkout set recorded: WC customer new-account, WC admin new-order, WC customer completed-order, and both ArraySubs signup messages
- [x] Cart proved empty before checkout and again after checkout
- [x] New user's persistent-cart meta empty after checkout; `SUBID3` resolved by exact parent-order linkage
- [x] Pending renewal order at `due+k3−6h` (±90 s), renewal-cycle-2 metadata, `created_via` empty
- [x] Invoice-to-charge gap exactly 21600 s
- [x] `renewal_invoice` received before the charge leg
- [x] Charge leg leaves it `wc-pending` with the manual-payment note, no task-attributable charge mail
- [x] Legs logged `via WP Cron`; no drain

## Isolation / teardown
- Deliberate non-auto-renew case: it never renews again, goes `arraysubs-on-hold` ~1 day after due and `arraysubs-cancelled` ~3 days later. That ladder is EXPECTED, and maps here.
- Never reuse `slt-invoice@example.test`; `_auto_renew` stays off. Empty the cart; close only `guest-SLT-REN-03`.

## D02 natural-leg completion — reviewed 2026-08-05

Verdict: **COMPLETED WITH PRODUCT FINDING**. The invoice/manual-renewal lifecycle contract passed. The original checkout exposed incompatible Alipay and remains documented only in `issues/SLT-REN-03-subscription-checkout-offers-incompatible-alipay.md`.

- Invoice action `13988` created exact renewal order `12410` 42 seconds after its natural schedule, with cycle 2, exact due-date linkage, `created_via` empty, and `wc-pending` USD `10.00` state.
- Invoice message `7Yk2i1g2X4rk5YylVEnfzb` arrived before the charge leg and links the exact order-pay route.
- Charge action `13989` ran exactly six scheduled hours later via WP Cron, left the order pending, and added `Renewal order created. Awaiting manual payment.`
- Subscription `12147` remains active at one completed payment with pending order `12410`; no retry count was created.
- Complete `PRE3B` delta: 76 messages, one task-attributable invoice, 75 classified background, and zero task-attributable payment-success/payment-failure/reminder messages.
- Browser order and notes screenshots were visually reviewed; the error buffer was empty and no card number is present.
- Primary facts: `/home/server-manager/slt-evidence/SLT-REN-03-D02-facts.txt`.

## Self-review

- Re-read both D01 recovery and D02 natural-leg evidence, the standalone issue, exact action logs, order metadata/notes, the complete Mailpit classification, and both browser captures.
- Confirmed both natural actions say `via WP Cron`, no action was forced, no duplicate fixture was created, no global setting changed, and the task-owned browser session is closed at completion.

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

### D01 execution note — 2026-08-03

**Authored checkout verdict: FAIL; lifecycle fixture recovered and armed.** The real block-checkout attempt began at 12:46 site time, inside the 12:30-13:00 gate. Stripe's Payment Element exposed and submitted Alipay, but Stripe rejected the resulting future-use intent. Order `12131` became failed while subscription `12147` was created on hold. The product finding is recorded only in `issues/SLT-REN-03-subscription-checkout-offers-incompatible-alipay.md`.

The same order was paid from its order-pay screen with the intended Card option at 13:02 site time. No duplicate order or subscription was created. Final D01 fixture state: user `358` (`slt.invoice`), order `12131` `wc-completed`, subscription `12147` `arraysubs-active`, `_completed_payments=1`, `_payment_gateway=stripe`, saved method `card`/Visa/last4 `4242`, next payment `2026-08-04 06:46:30Z`, and intentional `_auto_renew=off`. Browser and persistent carts are empty.

`k3=17271`. The exact pending natural-watch legs are invoice action `13988` at `2026-08-04 05:34:21Z` (11:34:21 site; 15-minute window opens 11:19:21) and charge action `13989` at `2026-08-04 11:34:21Z` (17:34:21 site). Canceled superseded rows are `13986`/`13987`. `PRE3B=56pmQmQl237iwUYE47i5aL`. Keep this card in progress; neither action may be forced.

The five expected account/recovered-checkout messages are `1AxAQ3yu3LsOrBXWYeweVn`, `63PlDF3h2DxHpj2DtToLbI`, `5881IxTOwr17q43qz6YXwt`, `5uB3rJkt5paJDBHMsg4gku`, and `56pmQmQl237iwUYE47i5aL`. The original failure also emitted `6FDQuJWl7DAD16ejKqWXUj`, `4HQwGlFob5BHDNNL3J7G5m`, and `0BqTmfXz4IFLKOffu6v1LT`. Full D01 evidence: `/home/server-manager/slt-evidence/SLT-REN-03-D01-facts.txt`.

[[2026-08-05]] Wed 08:40
Review outcome: invoice/manual-renewal lifecycle PASS; D01 incompatible-Alipay checkout remains a separate open product finding. Exact action timing/provenance, pending order metadata/notes, invoice content, complete 76-message classification, screenshots, registry, and D02 report all rechecked.
