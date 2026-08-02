---
id: 96
title: Upgrade a Paddle-billed ladder subscription and prove the Paddle-side price is not updated
status: todo
priority: high
created: 2026-08-02T03:43:11.086851725+02:00
updated: 2026-08-02T03:43:22.250995298+02:00
tags:
    - plan-switching
    - day-06
due: "2026-08-08"
estimate: 1h 45m
depends_on:
    - 60
    - 26
    - 12
class: standard
---

> **SLT-SW-05** · group `switching` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Buy **SLT Plan Basic** ($5.00 day/1) as `slt-paddle` through the Paddle sandbox, upgrade it to **SLT Plan Pro** ($15.00 day/1) from the portal, and answer the question that matters for a remote-billed gateway: is the Paddle-side plan/price kept in step? Per SLT-REF-09 the switch path is gateway-blind — `Hooks::onPlanSwitchCompleted()` only re-captures payment context and logs, and nothing calls `PaddleApiClient::updateSubscription()`. Prove Paddle still bills $5.00 while ArraySubs believes $15.00.

## Scope
- Gateway: Paddle sandbox
- Checkout: block (page 8); order-pay for the proration
- Account: existing (`slt-paddle`)
- Plugins: both (Paddle is pro)

## Preconditions
- SLT-SETUP-05 done (Paddle selectable, global sync OFF); SLT-PROD-11 done. The rungs carry no flex sync, so Paddle is offered.
- `slt-paddle` is Paddle-only and has not bought this product before.

## Test data
| Item | Value |
|---|---|
| Purchase | SLT Plan Basic $5.00 day/1 via Paddle, card `4242…4242` |
| Switch | Basic $5.00 → Pro $15.00 (upgrade, Branch A) |
| Portal | `/my-account/view-subscription/<S-PADDLE>/`, session `cust-SW-05` |
| Paddle API | `GET https://sandbox-api.paddle.com/subscriptions/<sub>`, api_key from `wp option get woocommerce_arraysubs_paddle_settings` |

## Steps
1. `mailpit-agent latest-id` → M0. Buy SLT Plan Basic as `slt-paddle` on the block checkout choosing **Paddle**; complete the overlay.
2. The order stays **pending** until `transaction.completed` arrives; poll until the subscription is `arraysubs-active` and `_gateway_paddle_subscription_id` is set. Record it as PSUB with both date metas.
3. Record the Paddle truth: `GET /subscriptions/PSUB` → `status`, `price.id`, `unit_price.amount`, `next_billed_at`; dump the `_arraysubs_gateway_paddle_*` metas on **both** rungs.
4. Portal → **Change Plan** → **Upgrade/Downgrade** → **Select** SLT Plan Pro; record T1 and the preview rows (`credit=round(5r,2)`, `charge=round(15r,2)`, `net`); Confirm.
5. The response must be `requires_payment: true` + `checkout_url`; land on order-pay, record PRO-ORDER-P, screenshot its gateway list, pay through Paddle. **If Paddle cannot render there, that is the finding** — capture console/network evidence, then set the order `Completed` in wp-admin and flag the deviation.
6. Re-dump subscription + order metas; read the notes and the pro payment log line "Payment context updated after plan switch"; re-run the step-3 GET and diff every field; `mailpit-agent list 30`.
7. Next morning: record the amount Paddle charged, whether a renewal order was created retroactively, and `_next_payment_date`.

## Expected results
1. Local records switch exactly as on Stripe: `_product_id`=Pro, `_recurring_amount`=`15.00`, day/1 kept, title rewritten, `_arraysubs_switch_processed=yes`, one `type=upgrade` history entry, `_next_payment_date` unchanged. `_gateway_paddle_subscription_id` still equals PSUB — the switch neither cancels nor recreates the remote subscription.
2. **The Paddle side is unchanged**: same `price.id`, `unit_price.amount` ($5.00) and `next_billed_at` — no switch path calls `updateSubscription()`. This is the headline result (an expected negative, not a crash).
3. SLT Plan Pro gets Paddle metas only if the proration checkout triggered `ensureProductSynced()`; record whether `_arraysubs_gateway_paddle_product_id` appeared — a synced *product* is not a *price* change.
4. For the watch: the next `transaction.completed` bills **$5.00** while ArraySubs shows $15.00, and `syncNextPaymentDate()` overwrites `_next_payment_date` from `next_billed_at` without rescheduling the AS legs. `processRenewalPayment()` stays a no-op — no local charge, no retries.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | Purchase/activation mail for the initial order | webhook confirms | slt-paddle@example.test | `Order #` / activated | record ids after M0 |
| 2 | NONE from the switch | steps 4-5 | — | — | no `arraysubs_send_plan_switch_email` listener |
| 3 | WooCommerce order mail for PRO-ORDER-P | order paid | slt-paddle@example.test | `Order #<PRO-ORDER-P>` | `mailpit-agent list 30` |

## Evidence to capture
- `SLT-SW-05-01-checkout.png`, `-02-preview.png`, `-03-order-pay.png`, `-04-after.png`; PSUB; both Paddle JSON dumps; all metas; Mailpit ids; console errors.

## Pass criteria
- [ ] Paddle purchase reaches `arraysubs-active` with `_gateway_paddle_subscription_id` set
- [ ] Preview and proration order match the Branch-A formula, as on Stripe
- [ ] Local records switch to Pro at $15.00; `_next_payment_date` unchanged
- [ ] Paddle API diff documented; the $5.00-vs-$15.00 divergence filed as an issue
- [ ] No switch email; only the listed order mails

## Isolation / teardown
- `slt-paddle` now owns this ladder subscription as well as SLT Paddle Daily; never touch it with Stripe. SLT-SETUP-99A must cancel it on D10 so the Paddle subscription is closed, not orphaned.

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
