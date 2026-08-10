---
id: 103
title: Customer view of an unpaid renewal invoice and paying it from the portal before the automatic charge leg
status: done
priority: high
created: 2026-08-02T03:43:11.573841575+02:00
updated: 2026-08-10T02:15:26.373157995+02:00
started: 2026-08-10T02:15:26.373157123+02:00
completed: 2026-08-10T02:15:26.373157123+02:00
tags:
    - admin
    - portal
    - day-07
due: "2026-08-09"
estimate: 1.5h
depends_on:
    - 70
    - 58
    - 49
class: standard
---

> **SLT-MYA-04** · group `admin` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Exercise the only window in which a customer can see and pay an unpaid renewal invoice: between the invoice leg at `due + k - 6h` and the charge leg at `due + k` (`RenewalScheduler.php:135-188`). Verify the portal view of that pending order, pay it from the `Pay` button, and prove the schedule math matches the unassisted path and the charge leg is a clean no-op.

## Scope
- Gateway: Stripe test
- Checkout: N/A (WooCommerce order-pay page reached from the portal)
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT-MYA-01 done. `SLT Signup Fee Daily` (`S_FEE`) is `arraysubs-active`, bought by slt-core in SLT-ADM-07 Phase A on D3.
- **This task deliberately customer-pays exactly one cycle of one subscription.** Before acting, post to `slt-catalog-registry`: "SLT-MYA-04 customer-pays the 2026-08-09 cycle of S_FEE; that cycle is NOT an unattended charge." Without this note the watch files a false bug.
- Do NOT use `SLT Retry Daily` - paying its failed order would rescue another task's dunning ladder. This task runs no Action Scheduler drain.

## Test data
| Item | Value |
|---|---|
| Account | slt-core / `SltQa!2026#Pass`, session `--session customer-MYA-04-SLT-MYA-04` |
| Product | SLT Signup Fee Daily, $9.00/day; signup fee charged on the initial order only |
| Renewal total | `$9.00` - no fee line, no tax line |
| Card | the saved Stripe method on the account |

## Steps
1. Resolve registry alias `S_FEE` into shell variable `S_FEE`, require exactly one registry match, and abort unless `[[ "$S_FEE" =~ ^[0-9]+$ ]]`; cross-check its recorded parent order, slt-core customer, and exact product. Run `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("k=%ds\n",$h%21600);' "$S_FEE"`. Read `D = _next_payment_date` (UTC): invoice fires at `D+k-6h`, charge at `D+k`. Convert both to UTC+6 into Notes.
2. Record the exact pending invoice/charge action IDs and pre-invoice renewal-order set for numeric `S_FEE`. Inside `[invoice gate−300s, invoice gate)` set `MB04=$(mailpit-agent latest-id)` and persist it; never take this baseline earlier than the final five minutes.
3. Shortly after the exact invoice action completes naturally `via WP Cron`, resolve the sole new order from numeric `S_FEE` plus `_renewal_scheduled_date=D`/cycle relationship and require its reverse subscription link; never select the newest slt-core order. Confirm it is pending, `_is_renewal_order=yes`, and that `S_FEE` has `_pending_renewal_order_id` and remains `arraysubs-active`.
4. Open `/my-account/view-subscription/<S_FEE>/` -> log in -> screenshot `Related Orders`. Record the new row (number, date, status, total) and that it offers `View` + `Pay` (`view-subscription.php:811`) while older rows offer only `View`.
5. Open the order via `View` and screenshot: line item `SLT Signup Fee Daily`, total `$9.00`, no fee line, no tax line.
6. Click `Pay` -> `snapshot -i`; confirm amount due `$9.00` and the Stripe box offers the saved card. `MB04b=$(mailpit-agent latest-id)`, then submit payment.
7. Poll immutable `MB04b` in repeated calls no longer than 60 seconds through the five-minute cutoff for `Payment received for subscription #$S_FEE`, save/show the exact match, and classify every message newer than `MB04b`. Require the exact WooCommerce **New order** mail to the admin and require zero customer WooCommerce processing/completed mail for this `_is_renewal_order=yes` order. Screenshot the safe order-received page. Verify order `processing`/`completed`, `_last_gateway_transaction_id` set, `_pending_renewal_order_id` deleted, `_completed_payments` +1, and `_next_payment_date` = `D + 1 day` exactly - computed from `_renewal_scheduled_date`, not payment time.
8. Reload the portal detail page: `Next Payment:` shows the new date in site-local time and the paid order offers only `View`.
9. **Same day after `D+k`:** confirm the recorded charge action completed `via WP Cron` with no new order and no second charge; compare the exact relationship/order set from step 2 and screenshot the action row. Close `customer-MYA-04-SLT-MYA-04`; do not retain it overnight.
10. **Watch day D8 = 2026-08-10 (morning check):** require exactly one relationship-exact paid renewal order for the 2026-08-09 cycle, one `payment_successful` mail, and the 2026-08-10 cycle queued normally. Independently review the registry exception, invoice/charge/order/portal/mail evidence, move the card through `review` to `done`, and require Review to return to zero. Any live defect goes only in `issues/SLT-MYA-04-<concise-slug>.md` with task/stage/plan path; subscription/order/action/message IDs; user ID/login/email/role; exact routes/session/gates; reproduction; expected/actual; and UI/meta/queue/log/order/Mailpit proof.

## Expected results
1. A pending renewal order for S_FEE exists after `D+k-6h`, total `$9.00`, `_is_renewal_order=yes`, `_renewal_scheduled_date = D`; S_FEE stays `arraysubs-active` with `_pending_renewal_order_id` until payment.
2. The portal row shows Pending payment and offers `View` + `Pay`; paid rows offer only `View`. The order view shows one $9.00 line, no fee line, no tax line.
3. The order-pay page charges `$9.00` on the saved card and succeeds; afterwards `_pending_renewal_order_id` is deleted, `_completed_payments` +1, a transaction id recorded.
4. `_next_payment_date` = `D + 1 day` to the second - identical to the unattended path.
5. The charge leg at `D+k` is a no-op: no second order, no second Stripe charge, no failure note.
6. Watch day D8 (2026-08-10): one paid renewal order and one payment-successful mail for that cycle.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Invoice at `D+k-6h` (suppressed for auto-payment subs, `EmailManager.php:504-510`) and the `D+k` charge leg | - | - | No `Invoice for subscription #<S_FEE>` between `MB04` and step 6; no `payment_failed` and no second `Payment received` after step 9 |
| 2 | `payment_successful` plus WooCommerce New order; no customer processing/completed order mail | Customer pays from the order-pay page | `slt-core` plus `admin_email` | `Payment received for subscription #<S_FEE>`; `New order #<renewal-order-id>` | exact subject wait after `MB04b`; save/show both matches and classify the complete delta by subscription/order id |

## Evidence to capture
- Screenshots `SLT-MYA-04-01-orders-pending.png`, `-02-order-detail.png`, `-03-order-pay.png`, `-04-received.png`, `-05-action-noop.png`; `k`, `D` and both window boundaries in UTC and site-local; renewal order id; `MB04`/`MB04b`; exact-match/full-delta Mailpit ids; before/after `_next_payment_date`.

## Pass criteria
- [ ] Pending renewal order appeared inside `[D+k-6h, D+k)` with the correct total and metas
- [ ] Pay button shown only on the unpaid order; total $9.00 with no fee and no tax line
- [ ] Payment succeeded and all post-payment metas are correct
- [ ] `_next_payment_date` = `D + 1 day` exactly; charge leg at `D+k` was a no-op with no second order
- [ ] Invoice mail absent; payment-successful mail present exactly once
- [ ] Final-five-minute baseline, exact cron/order relationships, session teardown, and independent review completed with Review empty

## Isolation / teardown
- Touches only slt-core's S_FEE and only the 2026-08-09 cycle; the registry note is mandatory so the D8 watch does not read this as a failed unattended renewal.
- Nothing force-run, no hook drained, no setting changed; the subscription is left active with its next cycle queued. Close only `--session customer-MYA-04-SLT-MYA-04`.

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

[[2026-08-06]] Thu 20:37
Planning correction on 2026-08-06: task 70 / SLT-MYA-01 is now closed UNVERIFIED, so the authored slt-core subscription table does not exist. This card may still run on a later valid cycle using the live S_FEE fixture and real registry cross-checks, but it must not claim task-70 signoff evidence that was never produced.

[[2026-08-09]] Sun 06:49
D07 late-morning preflight: mandatory numeric registry exception was posted and read back before the cycle. Live S_FEE=12655 validated against parent order 12654, customer 347, product 12577, status active, D=2026-08-09 10:44:10Z, k=2111s, and completed_payments=4. Exact relationship-owned order set before invoice is 12894/13047/13223; pending-renewal meta is absent. Natural actions 16024 (invoice 11:19:21 site) and 16025 (charge 17:19:21 site) are pending/unattempted. Customer session is authenticated but no order/payment action occurred. Next hard gate: capture MB04 only within 11:14:21-11:19:20 site, then observe invoice naturally and pay the relationship-exact pending USD 9.00 order. Evidence: /home/server-manager/slt-evidence/SLT-MYA-04-registry-exception.txt and /home/server-manager/slt-evidence/SLT-MYA-04-preflight.txt.

[[2026-08-09]] Sun 07:25
D07 invoice/payment leg completed. MB04=5yGiRnu4Kb079ncz43EDFT was captured exactly at 11:14:21 site. Action 16024 ran naturally via WP Cron 11:20:04-11:20:10 and created sole relationship/cycle order 13442 pending USD 9.00 with one product line, no signup fee/tax. Portal proved View+Pay only on 13442. MB04b=5yGiRnu4Kb079ncz43EDFT at 11:22:36; one saved Visa 4242 payment completed order 13442. Exact mail delta: admin order 0K2bcDTG38EPqOaEBXbRY0 and payment-success 323pZT9vBt7IyBLesD1MXk; no invoice/failure/on-hold mail. Pending meta cleared, completed_payments 4->5, transaction populated, next date exactly 2026-08-10 10:44:10Z. Original charge 16025 canceled unattempted and replacements 16336/16337 preserve k. Customer session closed. Keep in progress: after the original 17:19:21 site gate prove no second order/charge/mail, then D8 final reconciliation and review. Evidence: /home/server-manager/slt-evidence/SLT-MYA-04-D07-current-cycle.txt and task screenshots.

[[2026-08-09]] Sun 12:22
Independent-review correction: the former email row incorrectly required a customer WooCommerce processing/completed message, contradicting the established `_is_renewal_order=yes` contract in SLT-EML-15 and the same manual order-pay control in SLT-EML-02. It now correctly requires admin New order plus customer ArraySubs payment-success and zero customer Woo status mail; the exact two-message `MB04b` delta passes. The saved pending-portal screenshot accidentally captured only the subscription header, so that dedicated visual artifact is `UNVERIFIED`; the actual Pay navigation and structured pending-order facts remain recorded. The paid state was freshly recaptured at 12:22 site in `SLT-MYA-04-04a-portal-paid.png`, visibly showing next payment, completed order `13442`, `$9.00`, and View only.

[[2026-08-09]] Sun 13:26
D07 original charge-gate follow-up PASS. MYA04_GATE_PRE=3jUrzcrxsS5qMrFvzDePXI was captured at 17:14:34 site inside the final-five-minute window. After the 17:19:21 gate through 17:24:35, action 16025 remained canceled/unattempted with the identical create/cancel logs; replacements 16336/16337 remained pending; relationship/cycle orders remained exactly 12894/13047/13223/13442; four exact-subject waits returned 124 and the complete Mailpit delta was empty. Browser screenshot SLT-MYA-04-05-action-noop.png visibly proves the canceled row; errors empty and session closed. The unrelated pre-gate EML-02 cross-subscription write is separately attributed and does not change this no-op result. Evidence: /home/server-manager/slt-evidence/SLT-MYA-04-D07-original-charge-noop.txt. Keep in progress. Exact next gate: D8 final reconciliation no earlier than 2026-08-10 06:10 site; require exactly one paid D07 cycle order/mail and the normal 16336/16337 D8 queue before review to done.

[[2026-08-10]] Mon 02:15
D08 final reconciliation PASS at the 06:10 facts boundary. The immutable snapshot automation/logs/D08-2026-08-10-early-morning-facts.txt proves S_FEE=12655 remains arraysubs-active with completed_payments=5 and next_payment=2026-08-10 10:44:10Z; exact D07 relationship order 13442 is the sole paid 2026-08-09 cycle order for USD 9.00; exact customer payment-success mail remains 323pZT9vBt7IyBLesD1MXk; and normal D8 replacements 16336/16337 are pending at 05:19:21Z/11:19:21Z. Independent review re-opened SLT-MYA-04-D07-current-cycle.txt, SLT-MYA-04-D07-original-charge-noop.txt, and visually checked SLT-MYA-04-04a-portal-paid.png plus -05-action-noop.png. Lifecycle/mail/no-double-charge criteria PASS; the accidentally missed dedicated pending-row screenshot remains explicitly UNVERIFIED without weakening the recorded live Pay navigation and structured relationship proof. No mutation or new browser session in this D8 follow-up.
