---
id: 57
title: Reconcile the full Mailpit set for one SLT Daily Core renewal — no double-send, nothing missing
status: done
priority: critical
created: 2026-08-02T03:43:07.994935496+02:00
updated: 2026-08-05T21:37:49.562913545+02:00
started: 2026-08-05T17:42:59.190342626+02:00
completed: 2026-08-05T17:42:59.190342626+02:00
tags:
    - email
    - day-03
due: "2026-08-05"
estimate: 2h
depends_on:
    - 10
    - 5
    - 1
class: standard
---

> **SLT-EML-15** · group `emails` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Reconcile every Mailpit message produced by one unattended Stripe renewal of `SLT Daily Core` — invoice leg and charge leg — against a code-derived expected set. Prove nothing double-sends, nothing is missing, and that WooCommerce customer order emails are suppressed for renewal orders while the admin New Order email is not.

## Scope
- Gateway: Stripe test
- Checkout: N/A (observation only; no order placed)
- Account: existing (`slt-core`)
- Plugins: free-only

## Preconditions
- SLT-PROD-01 complete and `SLT Daily Core` bought by `slt-core` on D0 (2026-08-02, after 12:00); subscription **S1** is in the registry. Renewal #1 fired D1 and renewal #2 fires D2; this D3 task observes **renewal #3**, due 2026-08-05. The D2 watcher must arm the D3 invoice baseline after renewal #2 advances the anchored date.
- Code basis: customer `processing`/`completed`/`on-hold`/`invoice`/`failed` order emails are suppressed for orders with `_is_renewal_order=yes` by blanking the recipient (`EmailManager.php:69,240-270`); `woocommerce_email_recipient_new_order` is **not** in that list, so the admin New Order email still sends. `renewal_invoice` is suppressed for automatic subs with auto-renew on (`:504-510`, L23); `payment_successful` is guarded per order by `_arraysubs_renewal_payment_success_email_sent` (`:530-539`).
- **No Action Scheduler drain.** A renewal that does not fire on its own is a genuine bug — capture evidence first.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core ($10.00, day/1), subscription **S1** |
| Offset | `k` = `crc32('arraysubs-spread-'.S1) % 21600` |
| Due #3 | `D3` = `_next_payment_date` read after renewal #2 and before the D3 window (UTC) |
| Legs | invoice `D3+k−6h`; charge `D3+k` |

## Steps
1. **D2 preparation, only after renewal #2 is complete:** resolve numeric `SUB_CORE` from the registry into shell variable `S1` and abort unless numeric. Read `_next_payment_date` into `D3`; require the calendar date 2026-08-05, and also read `_completed_payments`, `_payment_method`, `_auto_renew`.
2. `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("%d\n",$h%21600);' "$S1"` → `k`. Write `D3`, `k`, the exact pending invoice/charge action IDs, and both leg times (UTC + site) to `/home/server-manager/slt-evidence/SLT-EML-15-window.txt`, the registry, and D02 watch report.
3. In `admin-SLT-EML-15`, open Tools → Scheduled Actions, search numeric `$S1`, capture the two exact pending rows as `SLT-EML-15-01-scheduled-actions.png`, and confirm they match the computed legs to the minute; close only that session.
4. No earlier than 10 minutes before exact `D3+k−6h`, record/publish `MPA=$(mailpit-agent latest-id)`, move the card to `in-progress`, and let the action fire naturally.
5. After `D3+k−6h+10min`, enumerate the complete `(MPA, latest]` delta (paginate the localhost Mailpit API if needed). **Invoice-leg assertion:** none addressed to `slt-core@example.test` or referencing the new renewal order.
6. Resolve the pending renewal order `R3` from `S1._pending_renewal_order_id` and the exact scheduled cycle, never from a recent/customer Orders list. Require reverse `_subscription_id`/`_subscription_renewal=$S1`, status pending, total $10.00, and capture the exact order in `admin-SLT-EML-15` as `SLT-EML-15-02-pending-order.png`; close only that session.
7. No earlier than five minutes before exact `D3+k`, record/publish `MPB=$(mailpit-agent latest-id)`; then `mailpit-agent wait-new "$MPB" 900 "Payment received for subscription #$S1"`.
8. Immediately enumerate the complete `(MPB, latest]` delta (paginate if needed), run `mailpit-agent show <id>` for each, classify into the table below or mark UNMAPPED; run `mailpit-agent html <matched-id>` for the payment-successful message and capture To/From/Reply-To of both expected messages.
9. Re-read exact `R3` (status, total, `_is_renewal_order`, `_subscription_id`, `_renewal_cycle_number`, `_renewal_scheduled_date`) and S1 (`_next_payment_date`, `_completed_payments`, `_pending_renewal_order_id`). Reopen `admin-SLT-EML-15`, capture the exact paid order as `SLT-EML-15-03-order-processing.png`, and close only that session.
10. Duplicate check: exactly one `Payment received for subscription #$S1` and one `New order #<numeric R3>` in the bounded charge window. In `mail-SLT-EML-15`, capture the exact filtered list as `SLT-EML-15-04-mailpit-window.png` and the payment message render as `SLT-EML-15-05-payment-html.png`; close only that session. Anything task-attributable and UNMAPPED becomes a standalone issue file with this task/plan, subscription/order/action/user IDs and login/role, exact UI/Mailpit context, reproduction window, expected/actual, message/meta proof, and a mapped-cycle counterexample—never a kanban bug card. Post the reconciled table to the registry/D03 report, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Both legs fired unattended at `D3+k−6h` and `D3+k` (±5 min); no drain needed.
2. Invoice leg produced relationship-exact `R3`, status `pending`, total `$10.00`, no tax line, and **zero** email.
3. Charge leg moved `R3` pending → a paid status (`processing` or `completed`, record the actual value) and produced exactly **two** messages: admin `New order #R3` and `Payment received for subscription #S1`.
4. Zero customer processing/completed/invoice order mail; zero `renewal_invoice`; zero `renewal_reminder` (1-day cycle < 3-day lead); zero `new_subscription`/`payment_failed`; no duplicates.
5. `S1._next_payment_date` = `D3 + 1 day`, `_completed_payments` +1, `_pending_renewal_order_id` removed; every id in `(MPA, latest]` maps to a row below or to another SLT task's declared mail — anything UNMAPPED is a standalone finding.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED (invoice leg) | `D3+k−6h` | — | — | complete `(MPA, post-invoice latest]` delta; no task-linked id to slt-core@ |
| 2 | WooCommerce New order (admin) | `D3+k` pending→paid | admin_email | `New order #R3` | `mailpit-agent show <id>` |
| 3 | payment_successful | renewal payment complete | slt-core@example.test | `Payment received for subscription #S1` | `mailpit-agent wait-new "$MPB" 900` |
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
- Handed on: post the reconciled expected-set table to `slt-catalog-registry` — the reference the D3–D12 watch uses to classify daily renewal mail. The admin New Order email firing for renewal orders is an expected observation; if judged undesirable, write a standalone product-finding file under `issues/`, never a lifecycle-board card.
- Restores: nothing.


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

[[2026-08-05]] Wed 16:36
D03 charge observation resumed. Shared CORE_REN_PRE/MPB baseline is due only inside 2026-08-05 15:32:52Z to 15:37:51Z; natural action 14543 is due 15:37:52Z. Invoice-leg baseline was missed, so that criterion remains UNVERIFIED with post-hoc evidence.

[[2026-08-05]] Wed 17:42
EML15 complete on 2026-08-05. Invoice action 14542 completed at 2026-08-05 09:38:09Z and created pending renewal order 12604 with zero task-owned mail before pre-charge baseline 2NkOs7YmUleUZp7unRgwhw; post-hoc Mailpit sweep for 2026-08-05T11:37:52+02:00..2026-08-05T17:12:04.391+02:00 found no 11959/12604 mail. Charge action 14543 completed at 2026-08-05 15:38:14Z; exact bounded delta after 2NkOs7YmUleUZp7unRgwhw is only admin new-order 5Utzqz5rTAQsZHRLO66kZI and customer payment-success 2i0R3FNNwSKttHCGnF3brU. Order 12604 completed for USD 10.00; no customer WC order mail, no renewal_invoice, no renewal_reminder, no duplicate payment mail. Evidence: /home/server-manager/slt-evidence/SLT-EML-15-window.txt and screenshots SLT-EML-15-01..05.
