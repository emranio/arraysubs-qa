---
id: 53
title: Payment received email after real unattended renewals on Stripe and Paddle
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
estimate: 1h
depends_on:
    - 5
    - 23
    - 1
    - 29
class: standard
---

> **SLT-EML-03** · group `emails` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove `payment_successful` is sent exactly once per paid renewal order, right after a real unattended renewal, with correct amount, payment method and next-payment date — on Stripe (local charge) and Paddle (webhook-driven; the local charge leg is a no-op). Capture the Paddle ordering hazard: the email renders before `syncNextPaymentDate()` overwrites `_next_payment_date` with Paddle's `next_billed_at`.

## Scope
- Gateway: both (Stripe test, Paddle sandbox)
- Checkout: N/A — both subscriptions already exist
- Account: existing (`slt2-core`, `slt2-paddle`)
- Plugins: both

## Preconditions
- SLT2 Daily Core bought by `slt2-core` D0 (2026-08-23 PM, card 4242) → `SUB_CORE`, $10.00/day. SLT2 Paddle Daily bought by `slt2-paddle` D2 (2026-08-25 PM, Paddle sandbox card) → `SUB_PAD`, $11.00/day; never touch it with Stripe.
- If CHK-04 has no numeric `SUB_PAD`, execute and preserve the Stripe evidence, update the upstream issue, and leave this composite card blocked until Paddle is corrected and rerun. Never create a substitute Paddle purchase.
- `SUB_CORE` renewal #3 is due on D3 (2026-08-26). The late D3 Paddle purchase has `next_billed_at=2026-08-27T10:20:38.143985Z`, so `SUB_PAD` renewal #1 is a D4/D5 follow-up owned by the exact `SLT-REN-04` handoff; it is not due on D3.
- Guard `_arraysubs_renewal_payment_success_email_sent` = once per order (`EmailManager.php:524-546`).
- SLT-REF-09: Paddle `processRenewalPayment()` returns `pending` and charges nothing; paid state and email arrive only when `transaction.completed` hits `POST /wp-json/arraysubs/v1/webhooks/arraysubs_paddle`.
- **Force-run nothing.** A renewal that misses its window is a bug — capture evidence first.

## Test data
| Item | Value |
|---|---|
| Stripe | `SUB_CORE`, SLT2 Daily Core, $10.00 |
| Paddle | `SUB_PAD`, SLT2 Paddle Daily, $11.00 |
| Charge moment | `_next_payment_date + k`, k = `crc32('arraysubs-spread-'.$id) % 21600` |
| Subject | `[<site title>] Payment received for subscription #<id>` |

## Steps
1. Resolve registry alias `SUB_CORE` into the same-named shell variable and abort unless it matches `^[0-9]+$`. Resolve `SUB_PAD` independently: if the registry contains the authored CHK-04 no-source marker, record that exact marker and issue path and mark only the Paddle leg `BLOCKED`; otherwise require numeric `SUB_PAD`. For every available source, read `_next_payment_date`, compute k with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("%ds\n",$h%21600);' "<numeric ID>"`, and write its exact (`D+k` … `D+k+5min`) window/action ID to evidence. Publish the D3 Stripe window and the D4 Paddle handoff to the D03 report before either gate; append the resolved Paddle outcome to D04/D05 as applicable.
2. `agent-browser --session admin-SLT-EML-03 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=$SUB_CORE"`; capture the exact pending `arraysubs_process_renewal` row as `SLT-EML-03-01-stripe-pending.png`. If `SUB_PAD` is numeric, repeat with `s=$SUB_PAD` and capture `SLT-EML-03-01a-paddle-pending.png`; otherwise do not manufacture a substitute row or purchase.
3. At the final scheduled phase before `SUB_CORE`'s exact `D+k` charge action, and at least five minutes before it, save `CORE_REN_PRE=$(mailpit-agent latest-id)` plus the exact pending action ID to the registry and task evidence. After the gate require `mailpit-agent wait-new "$CORE_REN_PRE" 900 "Payment received for subscription #$SUB_CORE"`; save/show the exact match and classify every message newer than `CORE_REN_PRE`. If it exits 124 after the action's five-minute grace window, screenshot the AS row and subscription notes before anything else.
4. `mailpit-agent show <stripe-mail-id>`; `mailpit-agent text <stripe-mail-id>`. In exact session `mail-SLT-EML-03`, open that matched message in the local Mailpit UI and capture `SLT-EML-03-02-stripe-email.png`, then close only that session.
5. Resolve the Stripe renewal order from the recorded invoice/charge handoff and exact subscription/scheduled-cycle relationship, never from the recent Orders list; require reverse `_subscription_id`/`_subscription_renewal` linkage to numeric `SUB_CORE`. Open that exact order in wp-admin, capture status, total, `_is_renewal_order`, and `_renewal_cycle_number` as `SLT-EML-03-03-stripe-renewal-order.png`. Then `wp post meta list "$SUB_CORE" --keys=_next_payment_date,_last_payment_date,_completed_payments,_payment_retry_attempts --allow-root`.
6. Require numeric `SUB_PAD` and consume the fresh immutable `PAD_REN_PRE` plus exact timestamps published by `SLT-REN-04`; do not replace them with a later baseline. After the gateway event require the exact payment-received subject and classify every message newer than the baseline. The local charge action may no-op or be superseded/cancelled, but may not charge or start a retry. If the webhook/mail is absent through the freshly published 24-hour deadline, update the QA issue and leave this card blocked. If mail arrives, capture it and close the exact session.
7. For numeric `SUB_PAD` only, open its exact ArraySubs detail route in `admin-SLT-EML-03`, capture its notes as `SLT-EML-03-05-paddle-notes.png`, run `wp post meta get "$SUB_PAD" _next_payment_date --allow-root`, and compare it with the `Next Payment Date` printed in the Paddle email. If the live values diverge, create `qa/issues/` kanban card named `SLT-EML-03-paddle-next-date-in-email` with this task/plan path, subscription/order/user IDs and login/role, exact admin/Mailpit contexts, reproduction timeline, expected/actual dates, message/meta/action proof, and the Stripe counterexample; create or update the mandatory `qa/issues/` kanban card.
8. Reconcile the D3 Stripe delta plus the dated D4/D5 Paddle delta, or the Stripe delta plus the documented Paddle no-source branch: exactly one `Payment received` per available paid target subscription this cycle. Classify unrelated messages without attributing them to this task. Close the D3 session after the Stripe leg and keep the card unclaimed `in-progress` only for the registered Paddle gate. After the available Paddle result or D5 no-bill deadline, close only `admin-SLT-EML-03` and `mail-SLT-EML-03`, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. `SUB_CORE`: order created `pending` at `D+k−6h`, paid at `D+k` ±5 min, status `processing`/`completed`, total `$10.00`, no tax line.
2. One mail to `slt2-core@example.test`: "We have received your payment for subscription #SUB_CORE. Your subscription has been renewed.", Product `SLT2 Daily Core`, Amount Paid `$10.00`, a Payment Method row naming the Stripe card, `Next Payment Date` in UTC+6.
3. `_completed_payments` +1, `_last_payment_date` set, `_payment_retry_attempts` absent/0, `_next_payment_date` advanced exactly 1 day from `_renewal_scheduled_date`.
4. Order carries `_arraysubs_renewal_payment_success_email_sent = yes`; no duplicate mail.
5. `SUB_PAD`: `arraysubs_process_renewal` either completes without charging (where emitted, leaving an awaiting-Paddle note) or is superseded/canceled by the earlier Paddle webhook; both outcomes have zero local charge and zero retry.
6. One mail to `slt2-paddle@example.test`, Amount Paid `$11.00`, Product `SLT2 Paddle Daily`.
7. If step 7's `_next_payment_date` differs from the date in that email, file the fully evidenced dedicated issue named in step 7; do not add it to this kanban board.
8. No Paddle webhook in the window ⇒ Paddle leg is `BLOCKED`, never rounded up to PASS.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_successful` Stripe | `arraysubs_renewal_payment_complete` at `D+k` | slt2-core@example.test | `Payment received for subscription #SUB_CORE` | exact 900-second wait after `CORE_REN_PRE`; exact match plus full delta |
| 2 | `payment_successful` Paddle | `transaction.completed` webhook | slt2-paddle@example.test | `Payment received for subscription #SUB_PAD` | exact 900-second wait after `PAD_REN_PRE`; exact match plus full delta |
| 3 | NONE EXPECTED | — | — | `Invoice for subscription` | suppressed: automatic + auto-renew on |
| 4 | NONE EXPECTED | — | — | `Payment failed` (either sub) | absent from both complete task-owned deltas |

## Evidence to capture
- `SLT-EML-03-01-stripe-pending.png`, conditional `-01a-paddle-pending.png`, `-02-stripe-email.png`, `-03-stripe-renewal-order.png`, conditional `-04-paddle-email.png` and `-05-paddle-notes.png`; every available k/window; `CORE_REN_PRE` and the `PAD_REN_PRE` consumed from `SLT-REN-04`, plus exact action/gateway times; exact-match/full-delta Mailpit IDs; exact relationship-linked order IDs; meta dumps; send times vs predicted windows; or the exact Paddle no-source handoff.

## Pass criteria
- [ ] Every available renewal fired unattended inside its dated D3/D4 gateway window, nothing force-run; Paddle local no-op or earlier-webhook supersession reconciled
- [ ] Exactly one `Payment received` per available subscription, correct recipient
- [ ] Stripe is $10.00; available Paddle is $11.00; no tax line; exact product names
- [ ] Next Payment Date in UTC+6; any available Paddle divergence recorded in a dedicated issue
- [ ] Guard meta and complete payment-received contract pass on both Stripe and Paddle; a missing Paddle source keeps the card blocked
- [ ] Exact sessions closed and card reviewed to done with Review empty

## Isolation / teardown
- Read-only; nothing written; the schedule was allowed to run. Close the D3 Stripe leg promptly and leave the card unclaimed until the registered D4/D5 Paddle result.
- Hands the Stripe `payment_successful` mailpit id to SLT-EML-05 as the HTML baseline.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
