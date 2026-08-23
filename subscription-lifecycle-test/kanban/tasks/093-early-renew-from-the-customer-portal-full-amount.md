---
id: 93
title: 'EARLY renew from the customer portal: full amount, next date anchored to the original due date, legs replaced'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - day-06
due: "2026-08-29"
estimate: 1h30m
depends_on:
    - 11
    - 5
    - 2
claimed_by: spur-gust
class: standard
---

> **SLT-LIFE-02** · group `renewal` · scheduled **D06** (2026-08-29)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Renew a live subscription EARLY from the customer portal and prove SLT-REF-07 Part A: the charge is the ordinary full renewal total, no discount or proration (EarlyRenewManager.php:180); `_next_payment_date` advances one full cycle from the ORIGINAL due date, not the payment moment, because the order carries `_renewal_scheduled_date = D_original` (OrderIntegration.php:1637-1643); the stale legs are replaced, not duplicated. Plus the Paddle negative - `PaddleGateway` declares `early_renewal => false`.

## Scope
- Gateway: Stripe test (Paddle read-only negative)
- Checkout: N/A (portal action)
- Account: existing (`slt2-core2`, owner of the classic-checkout control)
- Plugins: pro-required

## Preconditions
- `customer_actions.allow_early_renew = true` from the SLT-SETUP-02 baseline; quote the "WINDOW BASELINE (frozen)" registry table (audit C14) and toggle nothing here.
- Resolve `SUB_CORE2` as the exact live `arraysubs-active` SLT2 Daily Core subscription created by `SLT-CHK-02` for `slt2-core2`; quote its registry row and re-check owner/product IDs. It must be Stripe with a saved token, unsynced, with no pending skip and no open renewal order. **Never target `SUB_CORE`/`S1`; that control spine is reserved for `SLT-EML-05` on D6.**
- **Timing gate:** eligibility returns `invoice_pending` once the invoice leg has run - act BEFORE `due + k - 6h`.

## Test data
| Item | Value |
|---|---|
| Subscription | `SUB_CORE2` (SLT-CHK-02 / slt2-core2 / SLT2 Daily Core, day/1, $10.00), portal `/my-account/view-subscription/SUB_CORE2/` |
| Expected charge | $10.00 (full renewal, no proration) |
| D_original / new date | `SUB_CORE2`'s `_next_payment_date` at click time / D_original + 24h exactly |

## Steps
1. Dump `_next_payment_date,_completed_payments,_recurring_amount,_renewal_sync_enabled,_pending_renewal_order_id` for `SUB_CORE2`; record D_original; compute k and verify now < D_original + k - 6h, else defer a cycle.
2. In isolated `admin-SLT-LIFE-02`, screenshot `tools.php?page=action-scheduler&s=<numeric SUB_CORE2>&status=pending` and record the exact two action IDs/GMTs. `PREV=$(mailpit-agent latest-id)` immediately before the portal mutation.
3. `agent-browser --session customer-SLT-LIFE-02 open` the portal, log in as `slt2-core2`; require the cart and `_woocommerce_persistent_cart_1` to be empty, then screenshot the **Early Renewal:** notice naming D_original and the resulting date.
4. Click **Renew Early**; screenshot the dialog (must state $10.00, D_original and the new date); click **Renew Now**; screenshot the success state and the console/network output of `/my-subscriptions/SUB_CORE2/early-renew`.
5. Read numeric `OE` from the successful early-renew REST response and abort if it is absent/non-numeric. Cross-check the exact order's `_subscription_id`/`_subscription_renewal` against numeric `SUB_CORE2`, its customer/product, and the subscription's order relationship; never select an order by recency. Repeat the step-1 dump; read OE's `_arraysubs_early_renewal`, `_renewal_scheduled_date`, `_renewal_cycle_number` and the note naming D_original.
6. In `admin-SLT-LIFE-02`, re-screenshot the exact subscription-filtered pending actions and require both old action IDs gone/cancelled plus exactly one replacement invoice/charge pair at the calculated dates. Poll immutable `PREV` in repeated calls no longer than 60 seconds through the two-minute cutoff for `Payment received for subscription #$SUB_CORE2`, save/show that exact message, and classify every message newer than `PREV`, allowing only WooCommerce admin mail linked to `OE`; re-open the portal and record whether Renew Early is offered again or blocked, and why.
7. Paddle negative uses a separate authenticated session `customer-paddle-SLT-LIFE-02`; screenshot the exact `SUB_PAD` portal page showing no early-renewal control. A missing `SUB_PAD` leaves this card blocked with the upstream issue; do not browse another account's subscription.
8. Registry note: "SUB_CORE2 cycle N was paid early by SLT-LIFE-02 on 2026-08-29", so the watch does not read the missing unattended renewal as a failure. Empty and verify the `slt2-core2` cart plus persistent-cart meta, close `customer-SLT-LIFE-02`, `customer-paddle-SLT-LIFE-02` when opened, and `admin-SLT-LIFE-02`; independently review the exact relationship, schedule replacement, mail, cart, and Paddle-negative evidence; move the card through `review` to `done`, and require Review to return to zero. Any live defect goes only in `qa/issues/` kanban card named `SLT-LIFE-02-<concise-slug>` with task/stage/plan path; subscription/order/action/message IDs; user IDs/logins/emails/roles; exact routes/sessions/gate; reproduction; expected/actual; and UI/REST/meta/queue/Mailpit proof.

## Expected results
1. The portal shows the early-renewal notice and an enabled **Renew Early** button (baseline on, status active, Stripe `early_renewal => true`).
2. OE total is exactly $10.00 - no proration, no discount, no tax line.
3. OE carries `_arraysubs_early_renewal = yes`, anchor `_renewal_scheduled_date` = D_original and a note naming it; reaches a paid status (`processing` or `completed`, recorded exactly); `_completed_payments` +1.
4. `_next_payment_date` = D_original + 24h EXACTLY, NOT click_time + 24h - paying early does not shorten the paid-through period.
5. Old legs gone; one invoice and one charge leg remain, at (D_original+24h)+k-6h and +k, k unchanged.
6. The Paddle subscription shows no early-renewal UI at all.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | payment_successful + WC New order (admin) | early charge succeeds, OE becomes paid | slt2-core2 + admin | `Payment received for subscription #SUB_CORE2`, `New order #OE` | immutable-baseline polls ≤60 seconds through the two-minute cutoff; correlate the complete delta by `SUB_CORE2`/`OE` |
| 2 | NONE EXPECTED: customer WC processing/completed mail for OE, renewal_invoice (Stripe suppression), new_subscription, renewal_reminder (1-day cycle) | — | — | `order is now processing`, `order is complete`, `Invoice for subscription`, `is active`, `renews soon` | absent from the complete `PREV` delta |

## Evidence to capture
- Screenshots `SLT-LIFE-02-01-notice.png`, `-02-dialog.png`, `-03-success.png`, `-04-pending-before.png`, `-05-pending-after.png`, `-06-paddle-no-button.png`.
- `SUB_CORE2`, owner/product IDs, OE, D_original, k, meta dumps, order meta + note, Mailpit IDs.

## Pass criteria
- [ ] Renew Early offered; dialog states the amount and both dates
- [ ] Charge exactly $10.00, no proration or discount
- [ ] `_next_payment_date` = D_original + 1 cycle, not payment time + 1 cycle
- [ ] OE anchored to D_original; one invoice + one charge leg at the new date + same k
- [ ] Exactly the 2 email rows, negatives included
- [ ] Paddle source exists and correctly shows no early-renewal control
- [ ] Exact task sessions closed and independent review reaches `done` with Review empty

## Isolation / teardown
- `SUB_CORE2` keeps its daily grid, one cycle further on; the daily watch must be told (step 8) that this cycle was paid manually. `SUB_CORE`/`S1` is untouched.
- Nothing to restore - `allow_early_renew` is baseline, reverted by SLT-SETUP-99A.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
