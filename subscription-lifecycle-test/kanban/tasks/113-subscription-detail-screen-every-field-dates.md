---
id: 113
title: 'Subscription detail screen: every field, dates, schedule, related orders, gateway panel'
status: todo
priority: critical
created: 2026-08-02T03:43:12.380877241+02:00
updated: 2026-08-02T03:43:23.808931156+02:00
tags:
    - admin
    - portal
    - day-09
    - has-conflicts
due: "2026-08-11"
estimate: 1h15m
depends_on:
    - 47
    - 5
    - 12
class: standard
---

> **SLT-ADM-02** · group `admin` · scheduled **D09** (2026-08-11)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-01`, `SLT-CHK-14`, `SLT-LIFE-04`, `SLT-CHK-11`, `SLT-CHK-13`, `SLT-MYA-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

---
## Objective
Verify every field the detail screen renders against the underlying meta, HPOS orders and the scheduler queue, on two canvases: a live Stripe-backed subscription with several renewals, and the gateway-less admin-created SUB-A. Read-only throughout.

## Scope
- Gateway: Stripe test (canvas 1) / none (canvas 2)
- Checkout: N/A
- Account: existing (slt-core) + admin-created
- Plugins: both (pro renders Gateway, Timeline, Skip & Pause)

## Preconditions
- SLT-ADM-05 done (SUB-A exists). `SLT Daily Core` was bought by `slt-core` on D0, so by 08-07 it carries an initial order plus ~4 renewals.
- Per the frozen baseline, early-renew, reactivation and pause are ON: their buttons are expected UI, not defects.
- **Do not click** Cancel Subscription, Prorated Refund, Retry Payment, Detach/Resync Gateway, Login as Customer, Pause or Skip. Screenshot them only.

## Test data
| Item | Value |
|---|---|
| Canvas 1 | **SUB-CORE** — slt-core's SLT Daily Core sub, $10.00, Every 1 day |
| Canvas 2 | **SUB-A** — admin-created, no gateway |
| Session | `--session admin-SLT-ADM-02` |

## Steps
1. `mailpit-agent latest-id` → `M0` (zero mail expected). At `#/subscriptions` search `slt-core@example.test`, open **View Details**, record **SUB-CORE**.
2. Screenshot and transcribe every card: **Subscription** (ID, Created, Start Date, Next Payment, Last Payment, Total Paid), **Customer Information**, **Product**, **Billing Information** (Recurring Amount, Billing Schedule, Signup Fee, Completed Payments, Payment Method), **Payment Gateway**, **Addresses**, **Order History**, **Payment Timeline**, **Skip & Pause**.
3. Dump the truth: `wp post meta list SUB-CORE --keys=_customer_email,_invoice_email,_product_id,_quantity,_recurring_amount,_completed_payments,_payment_method_title,_start_date,_next_payment_date,_last_payment_date,_order_ids --allow-root`; compare the shown Start/Next/Last dates against those UTC values **+6 h**.
4. Cross-check Order History against HPOS at `admin.php?page=wc-orders` filtered to slt-core; **View Order** on the newest renewal row must open `page=wc-orders&action=edit&id=<id>`.
5. Read the **Payment Gateway** card (chip, Gateway, Card on File, Expires, Customer ID, Last Transaction) and note any external gateway link.
6. Compare the schedule against `admin.php?page=wc-status&tab=action-scheduler&status=pending&s=SUB-CORE`, with `k = crc32('arraysubs-spread-'.SUB-CORE) % 21600`.
7. Open `#/subscriptions/detail/SUB-A`, repeat steps 2-3 for the gateway-less contrast, confirm `latest-id` still equals `M0`, and close the session.

## Expected results
1. Every displayed value equals its meta counterpart; money renders `$X.XX` USD and **no tax line** appears anywhere, Order History included.
2. **Billing Schedule** reads `Every 1 day`; **Signup Fee** absent or `$0.00`; **Completed Payments** equals the paid-order count; dates render as stored UTC + 6 h, to the minute.
3. **Total Paid** = the sum of *paid* order totals (`calculateTotalPaid()`), not `recurring_amount × completed_payments`.
4. Order History lists the orders in `_order_ids` plus any carrying `_subscription_id=SUB-CORE`; the parent is typed `Initial`, the rest `Renewal`; totals match HPOS.
5. Payment Gateway shows `Connected`, Gateway `Stripe`, a 4-digit card + expiry, a `cus_…` Customer ID, a `pi_`/`ch_` Last Transaction, and **no Stripe-dashboard deep link** (neither plugin references `dashboard.stripe.com`) — observation, not defect.
6. The queue holds one invoice row at `next_payment + k − 6h` and one charge row at `next_payment + k` while the panel shows the unshifted date — that disagreement is correct, not a bug.
7. SUB-A contrast: gateway card absent or `Detached`, Payment Method empty/manual, Last Payment empty, Total Paid `$0.00`, Completed Payments `0`, only the `pending` renewal order listed.
8. Zero console errors, no 4xx/5xx on `/subscriptions/<id>/detail`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | whole task (read-only) | — | — | `latest-id` at step 7 equals `M0` |

## Evidence to capture
- Screenshots `SLT-ADM-02-01-subscription-card.png`, `-02-billing-card.png`, `-03-gateway-card.png`, `-04-order-history.png`, `-05-queue.png`, `-06-suba-detail.png`; both ids, the meta dump, the HPOS list, `k`.

## Pass criteria
- [ ] Every card field matches meta; no tax line; dates = stored UTC + 6 h; Total Paid derived from paid orders
- [ ] Order History = HPOS orders, Initial/Renewal typing correct, View Order works
- [ ] Gateway card populated for Stripe; absence of an external gateway link recorded
- [ ] Queue timestamps match `due+k−6h` / `due+k`; SUB-A contrast captured; zero mail and zero console errors

## Isolation / teardown
- Nothing mutated; the field inventory is the before-state baseline for SLT-ADM-03/04. If a mutating button is clicked by accident, STOP and file it — SUB-CORE carries another group's renewal contract.


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
