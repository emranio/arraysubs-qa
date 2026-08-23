---
id: 57
title: Reconcile the full Mailpit set for one SLT2 Daily Core renewal — no double-send, nothing missing
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - day-03
due: "2026-08-26"
estimate: 2h
depends_on:
    - 10
    - 5
    - 1
class: standard
---

> **SLT-EML-15** · group `emails` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Reconcile every Mailpit message produced by one unattended Stripe renewal of `SLT2 Daily Core` — invoice leg and charge leg — against a code-derived expected set. Prove nothing double-sends, nothing is missing, and that WooCommerce customer order emails are suppressed for renewal orders while the admin New Order email is not.

## Scope
- Gateway: Stripe test
- Checkout: N/A (observation only; no order placed)
- Account: existing (`slt2-core`)
- Plugins: free-only

## Preconditions
- SLT-PROD-01 complete and `SLT2 Daily Core` bought by `slt2-core` on D0 (2026-08-23, after 12:00); subscription **S1** is in the registry. Renewal #1 fired D1 and renewal #2 fires D2; this D3 task observes **renewal #3**, due 2026-08-26. The D2 watcher must arm the D3 invoice baseline after renewal #2 advances the anchored date.
- Code basis: customer `processing`/`completed`/`on-hold`/`invoice`/`failed` order emails are suppressed for orders with `_is_renewal_order=yes` by blanking the recipient (`EmailManager.php:69,240-270`); `woocommerce_email_recipient_new_order` is **not** in that list, so the admin New Order email still sends. `renewal_invoice` is suppressed for automatic subs with auto-renew on (`:504-510`, L23); `payment_successful` is guarded per order by `_arraysubs_renewal_payment_success_email_sent` (`:530-539`).
- **No Action Scheduler drain.** A renewal that does not fire on its own is a genuine bug — capture evidence first.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core ($10.00, day/1), subscription **S1** |
| Offset | `k` = `crc32('arraysubs-spread-'.S1) % 21600` |
| Due #3 | `D3` = `_next_payment_date` read after renewal #2 and before the D3 window (UTC) |
| Legs | invoice `D3+k−6h`; charge `D3+k` |

## Steps
1. **D2 preparation, only after renewal #2 is complete:** resolve numeric `SUB_CORE` from the registry into shell variable `S1` and abort unless numeric. Read `_next_payment_date` into `D3`; require the calendar date 2026-08-26, and also read `_completed_payments`, `_payment_method`, `_auto_renew`.
2. `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("%d\n",$h%21600);' "$S1"` → `k`. Write `D3`, `k`, the exact pending invoice/charge action IDs, and both leg times (UTC + site) to `/home/server-manager/slt-evidence/SLT-EML-15-window.txt`, the registry, and D02 watch report.
3. In `admin-SLT-EML-15`, open Tools → Scheduled Actions, search numeric `$S1`, capture the two exact pending rows as `SLT-EML-15-01-scheduled-actions.png`, and confirm they match the computed legs to the minute; close only that session.
4. No earlier than 10 minutes before exact `D3+k−6h`, record/publish `MPA=$(mailpit-agent latest-id)`, move the card to `in-progress`, and let the action fire naturally.
5. After `D3+k−6h+10min`, enumerate the complete `(MPA, latest]` delta (paginate the localhost Mailpit API if needed). **Invoice-leg assertion:** none addressed to `slt2-core@example.test` or referencing the new renewal order.
6. Resolve the pending renewal order `R3` from `S1._pending_renewal_order_id` and the exact scheduled cycle, never from a recent/customer Orders list. Require reverse `_subscription_id`/`_subscription_renewal=$S1`, status pending, total $10.00, and capture the exact order in `admin-SLT-EML-15` as `SLT-EML-15-02-pending-order.png`; close only that session.
7. No earlier than five minutes before exact `D3+k`, record/publish `MPB=$(mailpit-agent latest-id)`; then `mailpit-agent wait-new "$MPB" 900 "Payment received for subscription #$S1"`.
8. Immediately enumerate the complete `(MPB, latest]` delta (paginate if needed), run `mailpit-agent show <id>` for each, classify into the table below or mark UNMAPPED; run `mailpit-agent html <matched-id>` for the payment-successful message and capture To/From/Reply-To of both expected messages.
9. Re-read exact `R3` (status, total, `_is_renewal_order`, `_subscription_id`, `_renewal_cycle_number`, `_renewal_scheduled_date`) and S1 (`_next_payment_date`, `_completed_payments`, `_pending_renewal_order_id`). Reopen `admin-SLT-EML-15`, capture the exact paid order as `SLT-EML-15-03-order-processing.png`, and close only that session.
10. Duplicate check: exactly one `Payment received for subscription #$S1` and one `New order #<numeric R3>` in the bounded charge window. In `mail-SLT-EML-15`, capture the exact filtered list as `SLT-EML-15-04-mailpit-window.png` and the payment message render as `SLT-EML-15-05-payment-html.png`; close only that session. Anything task-attributable and UNMAPPED becomes a QA issue card with this task/plan, subscription/order/action/user IDs and login/role, exact UI/Mailpit context, reproduction window, expected/actual, message/meta proof, and a mapped-cycle counterexample—create the required QA issue card. Post the reconciled table to the registry/D03 report, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Both legs fired unattended at `D3+k−6h` and `D3+k` (±5 min); no drain needed.
2. Invoice leg produced relationship-exact `R3`, status `pending`, total `$10.00`, no tax line, and **zero** email.
3. Charge leg moved `R3` pending → a paid status (`processing` or `completed`, record the actual value) and produced exactly **two** messages: admin `New order #R3` and `Payment received for subscription #S1`.
4. Zero customer processing/completed/invoice order mail; zero `renewal_invoice`; zero `renewal_reminder` (1-day cycle < 3-day lead); zero `new_subscription`/`payment_failed`; no duplicates.
5. `S1._next_payment_date` = `D3 + 1 day`, `_completed_payments` +1, `_pending_renewal_order_id` removed; every id in `(MPA, latest]` maps to a row below or to another SLT2 task's declared mail — anything UNMAPPED is a QA issue card.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED (invoice leg) | `D3+k−6h` | — | — | complete `(MPA, post-invoice latest]` delta; no task-linked id to slt2-core@ |
| 2 | WooCommerce New order (admin) | `D3+k` pending→paid | admin_email | `New order #R3` | `mailpit-agent show <id>` |
| 3 | payment_successful | renewal payment complete | slt2-core@example.test | `Payment received for subscription #S1` | `mailpit-agent wait-new "$MPB" 900` |
| 4 | NONE EXPECTED — customer order mail, renewal_invoice, renewal_reminder | whole window | — | — | zero subjects containing `order is now processing`, `order is complete`, `Invoice for order`, `Invoice for subscription #S1`, `renews soon` |

## Evidence to capture
- `SLT-EML-15-window.txt`; screenshots `-01-scheduled-actions.png`, `-02-pending-order.png`, `-03-order-processing.png`, `-04-mailpit-window.png`, `-05-payment-html.png`.
- IDs S1, relationship-exact R3 and both actions, `MPA`, `MPB`; every window message ID with its classification; `show` output for messages 2 and 3; cross-day handoff/session/review proof.

## Pass criteria
- [ ] Both legs fired unattended at the computed offsets, no drain
- [ ] Invoice leg: pending order created, zero mail; charge leg: exactly two messages
- [ ] Zero customer WooCommerce order emails for the renewal order
- [ ] Zero renewal_invoice, renewal_reminder, new_subscription; no duplicates
- [ ] Schedule and counters advanced correctly; every window message classified, zero UNMAPPED
- [ ] D2 publishes both D3 gates/baseline deadlines; exact sessions close and card moves through review to done

## Isolation / teardown
- Read-only: no setting changed, no order placed, no status edited, no drain.
- Handed on: post the reconciled expected-set table to `slt2-catalog-registry` — the reference the D3–D12 watch uses to classify daily renewal mail. The admin New Order email firing for renewal orders is an expected observation; if judged undesirable, write a dedicated product-finding file under `qa/issues/`, and track it on the mandatory `qa/issues/` kanban board.
- Restores: nothing.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
