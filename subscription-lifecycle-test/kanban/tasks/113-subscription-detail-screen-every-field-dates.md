---
id: 113
title: 'Subscription detail screen: every field, dates, schedule, related orders, gateway panel'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-09
due: "2026-09-01"
estimate: 1h15m
depends_on:
    - 47
    - 5
    - 1
    - 12
class: standard
---

> **SLT-ADM-02** · group `admin` · scheduled **D09** (2026-09-01)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Verify every field the detail screen renders against the underlying meta, HPOS orders and the scheduler queue, on two canvases: a live Stripe-backed subscription with several renewals, and the gateway-less admin-created SUB-A. Read-only throughout.

## Scope
- Gateway: Stripe test (canvas 1) / none (canvas 2)
- Checkout: N/A
- Account: existing (slt2-core) + admin-created
- Plugins: both (pro renders Gateway, Timeline, Skip & Pause)

## Preconditions
- SLT-ADM-05 done (SUB-A exists). `SLT2 Daily Core` was bought by `slt2-core` on D0, so by this D9 task it carries its initial order plus several renewals; derive the exact count live from `_completed_payments` and HPOS rather than using a stale estimate.
- Per the frozen baseline, early-renew, reactivation and pause are ON: their buttons are expected UI, not defects.
- **Do not click** Cancel Subscription, Prorated Refund, Retry Payment, Detach/Resync Gateway, Login as Customer, Pause or Skip. Screenshot them only.

## Test data
| Item | Value |
|---|---|
| Canvas 1 | **SUB_CORE** — slt2-core's canonical SLT2 Daily Core sub, $10.00, Every 1 day |
| Canvas 2 | **SUB-A** — admin-created, no gateway |
| Session | `--session admin-SLT-ADM-02` |

## Steps
1. `M0=$(mailpit-agent latest-id)` (zero mail expected). Resolve canonical `SUB_CORE` and `SUB-A` from the registry into numeric shell variables, require each exactly once/distinct, and cross-check owner/product/parent relationships. At `#/subscriptions` search exact numeric SUB_CORE and open its View Details action.
2. Screenshot and transcribe every card: **Subscription** (ID, Created, Start Date, Next Payment, Last Payment, Total Paid), **Customer Information**, **Product**, **Billing Information** (Recurring Amount, Billing Schedule, Signup Fee, Completed Payments, Payment Method), **Payment Gateway**, **Addresses**, **Order History**, **Payment Timeline**, **Skip & Pause**.
3. Dump the truth: `wp post meta list "$SUB_CORE" --keys=_customer_email,_invoice_email,_product_id,_quantity,_recurring_amount,_completed_payments,_payment_method_title,_start_date,_next_payment_date,_last_payment_date,_order_ids --allow-root`; compare the shown Start/Next/Last dates against those UTC values **+6 h**.
4. Build the exact relationship-owned HPOS order set from parent/order IDs and subscription metas; compare it to Order History. View Order on the highest-cycle exact renewal must open its numeric HPOS route; never use customer recency as truth.
5. Read the **Payment Gateway** card (chip, Gateway, Card on File, Expires, Customer ID, Last Transaction) and note any external gateway link.
6. Compare the schedule against `tools.php?page=action-scheduler&status=pending&s=<numeric SUB_CORE>`, computing k from numeric SUB_CORE and recording exact action IDs/GMTs.
7. Open `#/subscriptions/detail/<numeric SUB-A>`, repeat steps 2-3 for the gateway-less contrast, inspect complete M0 delta and require zero task mail, capture console/network errors, close `admin-SLT-ADM-02`, independently review both canvases, then move through `review` to `done` with Review empty. Any live defect goes only in `qa/issues/` kanban card named `SLT-ADM-02-<concise-slug>` with task/stage/plan path; subscription/customer/product/order/action IDs; user login/email/role; exact routes/session; reproduction; expected/actual; and UI/meta/HPOS/queue/console/network proof.

## Expected results
1. Every displayed value equals its meta counterpart; money renders `$X.XX` USD and **no tax line** appears anywhere, Order History included.
2. **Billing Schedule** reads `Every 1 day`; **Signup Fee** absent or `$0.00`; **Completed Payments** equals the paid-order count; dates render as stored UTC + 6 h, to the minute.
3. **Total Paid** = the sum of *paid* order totals (`calculateTotalPaid()`), not `recurring_amount × completed_payments`.
4. Order History lists the orders in `_order_ids` plus any carrying `_subscription_id=SUB_CORE`; the parent is typed `Initial`, the rest `Renewal`; totals match HPOS.
5. Payment Gateway shows `Connected`, Gateway `Stripe`, a 4-digit card + expiry, a `cus_…` Customer ID, a `pi_`/`ch_` Last Transaction, and **no Stripe-dashboard deep link** (neither plugin references `dashboard.stripe.com`) — observation, not defect.
6. The queue holds one invoice row at `next_payment + k − 6h` and one charge row at `next_payment + k` while the panel shows the unshifted date — that disagreement is correct, not a bug.
7. SUB-A contrast: gateway card absent or `Detached`, Payment Method empty/manual, Last Payment empty, Total Paid `$0.00`, Completed Payments `0`, only the `pending` renewal order listed.
8. Zero console errors, no 4xx/5xx on `/subscriptions/<id>/detail`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | whole task (read-only) | — | — | Complete delta after `M0`; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots `SLT-ADM-02-01-subscription-card.png`, `-02-billing-card.png`, `-03-gateway-card.png`, `-04-order-history.png`, `-05-queue.png`, `-06-suba-detail.png`; both ids, the meta dump, the HPOS list, `k`.

## Pass criteria
- [ ] Every card field matches meta; no tax line; dates = stored UTC + 6 h; Total Paid derived from paid orders
- [ ] Order History = HPOS orders, Initial/Renewal typing correct, View Order works
- [ ] Gateway card populated for Stripe; absence of an external gateway link recorded
- [ ] Queue timestamps match `due+k−6h` / `due+k`; SUB-A contrast captured; zero mail and zero console errors
- [ ] Numeric aliases/routes and exact order set reviewed to `done` with Review empty

## Isolation / teardown
- Nothing mutated; the field inventory is the before-state baseline for SLT-ADM-03/04. If a mutating button is clicked by accident, STOP and file it — `SUB_CORE` carries another group's renewal contract.
- Close only `admin-SLT-ADM-02`; preserve unrelated sessions.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
