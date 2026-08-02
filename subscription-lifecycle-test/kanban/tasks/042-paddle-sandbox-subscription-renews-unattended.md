---
id: 42
title: Paddle sandbox subscription renews unattended — establish Paddle-side vs site-side driver
status: todo
priority: high
created: 2026-08-02T03:43:06.468088561+02:00
updated: 2026-08-02T03:43:16.990913058+02:00
tags:
    - renewal
    - day-02
due: "2026-08-04"
estimate: 2h
depends_on:
    - 12
    - 23
    - 26
class: standard
---

> **SLT-REN-04** · group `renewal` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Buy `SLT Paddle Daily` in Paddle sandbox and establish with evidence WHO drives its unattended renewal. Code says Paddle-side: the local `arraysubs_process_renewal` leg runs but `processRenewalPayment()` is a no-op returning `pending`; money lands only on `transaction.completed`; `syncNextPaymentDate()` then overwrites `_next_payment_date` from `next_billed_at` without rescheduling. Assert that model, not AS/meta agreement.

## Scope
- Gateway: Paddle sandbox
- Checkout: block
- Account: existing (`slt-paddle`)
- Plugins: pro-required

## Preconditions
- SLT-PROD-16 (`SLT Paddle Daily`, $11.00 day/1) + SLT-SETUP-05 complete; `slt-paddle` has a billing address.
- If a checkout-group task already bought it as `slt-paddle`, do NOT buy again — start at step 5 with that subscription.
- Buy **after 12:00 site on D2 = 2026-08-04**. Paddle only, never Stripe here. No `wp action-scheduler run`.

## Test data
| Item | Value |
|---|---|
| Product | SLT Paddle Daily, $11.00/day |
| Account | slt-paddle / SltQa!2026#Pass |
| Card | Paddle sandbox 4242 4242 4242 4242 |
| Session | `customer-SLT-REN-04` |

## Steps
1. `PRE4=$(mailpit-agent latest-id)`; assert the session cart is EMPTY.
2. `agent-browser --session customer-SLT-REN-04 open ".../my-account/"` → log in as `slt-paddle`.
3. `/?p=<Paddle Daily ID>` → **Add to cart** → `/checkout/`; choose **Paddle**, complete the overlay. Screenshot the pending-order notice.
4. `mailpit-agent wait-new "$PRE4" 180 "is active"` — activation is webhook-driven; record the delay.
5. Resolve `SUBID4`; record `_next_payment_date`, `_gateway_paddle_subscription_id`, `_gateway_status`.
6. From the Paddle sandbox dashboard/API read `next_billed_at` and `billing_cycle`; **record both next to `_next_payment_date` — disagreement is itself the finding.**
7. Compute `k4`; from `wp_actionscheduler_actions WHERE args='[SUBID4]'` take both legs' `scheduled_date_gmt`.
8. On D3 = 2026-08-05 after the local charge leg (`due+k4`) and at the D4 watch (2026-08-06): re-read `SUBID4` meta and notes, the customer's orders and notes, both actions' status/logs, `list 50`.

## Expected results
1. Parent order paid $11.00, `payment_method=arraysubs_paddle`, `_gateway_paddle_subscription_id` set, subscription `arraysubs-active`, `_gateway_status=active`.
2. `arraysubs_process_renewal` `[SUBID4]` completes at `due+k4`, logged `via WP Cron`, charging nothing: private note `awaiting automatic charge from Paddle`, order still unpaid, `_payment_retry_attempts` 0/absent, no retry queued — Paddle gets NO ArraySubs retry ladder.
3. The paid renewal order comes from the webhook branch: `_is_renewal_order=yes`, `_subscription_id=SUBID4`, $11.00, paid, with `_paddle_transaction_id` + `_last_gateway_transaction_id`. Note whether it is the invoice-leg order or a retroactive one.
4. After the webhook `_next_payment_date` equals `next_billed_at` (UTC) even where that differs from the core-computed value — Paddle wins.
5. The queued legs may now disagree with `_next_payment_date` until the next hourly sweep. Record it; do NOT assert agreement.
6. Verdict written verbatim in the report: renewal is **Paddle-driven**, local legs are bookkeeping. A charge at `due+k4` with no `transaction.completed` contradicts the code and is filed as an issue.
7. If sandbox does not bill inside 24 h, results 3–4 are `UNVERIFIED` with that reason; 1, 2, 5 still stand. On a failure path, `_last_payment_failure` as a Unix timestamp (core writes a UTC string) is a bug candidate, not a test failure.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | webhook confirms checkout | customer/admin | `is active` | `wait-new $PRE4 180` |
| 2 | payment_successful | renewal `transaction.completed` | slt-paddle@example.test | `Payment received for subscription #SUBID4` | `list 50` on D3/D4 |
| 3 | NONE EXPECTED: renewal_invoice (automatic+auto-renew), payment_failed, renewal_reminder | D3 | — | — | `list 50` |

## Evidence to capture
- `SLT-REN-04-01-overlay.png`, `-02-subscription-notes.png`, `-03-renewal-order.png`; `SUBID4`, Paddle subscription id, `next_billed_at` vs `_next_payment_date` at each read, `k4`, AS rows + logs, order ids, Mailpit ids.

## Pass criteria
- [ ] Paid $11.00 parent order, active subscription with a Paddle id
- [ ] Local charge leg completed `via WP Cron`, charged nothing, left the awaiting-Paddle note
- [ ] No ArraySubs retry action or counter
- [ ] Renewal money arrived via `transaction.completed`; paid $11.00 order with Paddle ids
- [ ] `_next_payment_date` matches `next_billed_at`; AS/meta disagreement recorded, not asserted away
- [ ] Driver verdict stated; emails 1–2 present, row-3 negatives absent

## Isolation / teardown
- `SLT Paddle Daily` and `slt-paddle` stay Paddle-only all window.
- Nothing restored; cancelled at SLT-SETUP-99A. Empty the cart, close this session.

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
