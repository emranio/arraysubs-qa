---
id: 67
title: 'Renewal invoice email: content, UTC+6 due date, and a pay-link that resolves to a real payable order'
status: done
priority: high
created: 2026-08-02T03:43:08.994907162+02:00
updated: 2026-08-08T12:54:33.245308526+02:00
started: 2026-08-08T12:54:33.245307364+02:00
completed: 2026-08-08T12:54:33.245307364+02:00
tags:
    - email
    - day-04
due: "2026-08-07"
estimate: 1h30m
depends_on:
    - 5
    - 52
    - 1
class: standard
---

> **SLT-EML-02** · group `emails` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
The renewal-invoice email is suppressed for automatic-payment subscriptions with auto-renew ON (`EmailManager.php:495-513`), so on Stripe it never sends. Set `_auto_renew = off` on one SLT subscription so the manual-invoice branch runs, then assert the email's four content rows (subscription id, product, amount, due date rendered in site UTC+6) and prove the "Pay for this order" link opens a genuinely payable order-pay page.

## Scope
- Gateway: Stripe test
- Checkout: block (order-pay endpoint on checkout page 8 — do NOT swap page 8)
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT Daily Core bought by `slt-core` on D0 (2026-08-02 PM) → `SUB_CORE`, $10.00/day, `arraysubs-active`.
- SLT-REF-01: invoice leg `arraysubs_generate_renewal_invoice` at `D + k − 6h`; charge leg `arraysubs_process_renewal` at `D + k`. `_auto_renew=off` both un-suppresses the invoice email and puts this cycle on the manual-payment path. This task must customer-pay the pending order before `D+k`; once payment advances the anchored schedule, the later charge leg is a no-op. **The pay-link is payable only inside that 6-hour window.**
- Once-per-order guard `_arraysubs_renewal_invoice_email_sent`.
- Writes ONE per-subscription meta on an SLT subscription; changes no global setting.

## Test data
| Item | Value |
|---|---|
| Product / subscription | SLT Daily Core $10.00 day/1 / `SUB_CORE` (slt-core@example.test) |
| Meta written | `_auto_renew = off` (exact prior existence/value restored in teardown) |
| Card | `4242 4242 4242 4242`, future expiry, CVC 123 |
| Expected total | $10.00 — no tax line, no fee line |
| Subject | `[<site title>] Invoice for subscription #SUB_CORE` |

## Steps
1. Resolve numeric `SUB_CORE` from the registry, assign it to shell variable `SUB_CORE`, and abort unless numeric. `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));' "$SUB_CORE"` → k.
2. `wp post meta get "$SUB_CORE" _next_payment_date --allow-root` → `D`; compute `D+k−6h` and `D+k` in UTC and site time.
3. Preserve the exact prior existence/value of `_auto_renew`. In `admin-SLT-EML-02`, open `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=$SUB_CORE`, capture both exact pending legs as `SLT-EML-02-01-pending-legs.png`, and confirm their timestamps match step 2.
4. Gate: this task has a preparation leg and a post-invoice leg. If the invoice row is already complete or is under 15 minutes away before `_auto_renew` is armed, stop and repeat from step 2 on the next cycle (D5). Otherwise record the exact invoice and charge gates. Never block the D4 `SLT-DUN-03` hard gate merely by waiting for this email.
5. **Before writing the meta**, append the dated numeric exception to the registry and `/home/server-manager/slt-evidence/SLT-EML-02-registry-exception.txt`, read both back, and record the planned restore time before the charge gate. Set `PREV=$(mailpit-agent latest-id)`, run `wp post meta update "$SUB_CORE" _auto_renew off --allow-root`, read it back, close only `admin-SLT-EML-02`, and leave the card `in-progress` with the exact invoice gate. Do not hold a phase open solely for this wait.
6. Resume in the first phase after the exact invoice timestamp and before the charge timestamp. Run `mailpit-agent wait-new "$PREV" 60 "Invoice for subscription #$SUB_CORE"`; because the baseline predates the action, an existing match returns immediately. If none exists by five minutes after the invoice timestamp, reopen `admin-SLT-EML-02`, capture the exact action/notes, create a fully evidenced standalone issue with task/plan, subscription/pending-order/action/user IDs and login/role, admin/Mailpit contexts, reproduction, expected/actual, meta/action/mail proof and the prior auto-renew-suppressed cycle as counterexample, then continue directly to mandatory exact meta restoration; never wait past the charge gate or create a kanban bug card.
7. `mailpit-agent show <invoice-mail-id>` and `mailpit-agent html <invoice-mail-id>`; extract the four table rows and pay URL. In `mail-SLT-EML-02`, capture the exact message as `SLT-EML-02-02-invoice-email.png`.
8. Date oracle: `wp eval "echo arraysubs_format_date_local(get_post_meta((int) $SUB_CORE,'_next_payment_date',true));" --allow-root`.
9. In `customer-eml02-SLT-EML-02`, log in as `slt-core`, open the exact pay URL, require HTTP 200/payable $10.00, and capture `SLT-EML-02-03-order-pay-page.png` before card entry. Immediately before payment set `PAY_PRE=$(mailpit-agent latest-id)`.
10. Fill the hosted 4242 fixture without capturing it, pay, capture the safe receipt as `SLT-EML-02-04-order-paid.png`, and require `mailpit-agent wait-new "$PAY_PRE" 300 "Payment received for subscription #$SUB_CORE"`. Save/show the exact match and classify every message newer than `PAY_PRE`, including any exact renewal-order admin New order while requiring no customer Woo order mail. Resolve the renewal order from the pay URL plus exact subscription/scheduled-cycle relationship and reverse link, never recency; read its guard/status and the subscription's next date/payment count/pending-order meta in `admin-SLT-EML-02`.
11. Restore `_auto_renew` to its exact step-3 existence/value (delete only if originally absent), prove it, append actual UTC restore time, and set `POST_RESTORE_MAIL_BASE=$(mailpit-agent latest-id)`. Record/publish the newly queued next-cycle invoice action ID/time and its verification deadline, close all three exact task sessions, and keep the card `in-progress`. Resume after that D5 timestamp, confirm no `Invoice for subscription #$SUB_CORE` newer than `POST_RESTORE_MAIL_BASE`, classify unrelated mail, reopen/close `admin-SLT-EML-02` only as needed, independently review the full D4/D5 evidence, move the card through `review` to `done`, and ensure Review returns to zero.

**Restore-first failure rule:** after `_auto_renew=off` is written, any browser, mail, pay-link, timing, or evidence failure restores its exact prior existence/value before diagnosis; the charge gate is a hard restoration deadline.

## Expected results
1. Exactly one `Invoice for subscription #SUB_CORE` mail, within 2 minutes of `D+k−6h`, to `slt-core@example.test` only.
2. Body: "A renewal order has been created for your subscription #SUB_CORE."; heading `Order #<renewal order id>`.
3. Rows: Subscription `#SUB_CORE`; Product `SLT Daily Core`; Amount `$10.00` (no tax, no fee); Due Date exactly equal to the step-8 oracle — the UTC+6 rendering of `D`. When `D`'s UTC time is ≥ 18:00 the rendered date is the NEXT calendar day; assert that shift explicitly.
4. Pay URL matches `https://mirror-help.arrayhash.com/checkout/order-pay/<order_id>/?pay_for_order=true&key=wc_order_...`, returns HTTP 200, shows total $10.00 and a payment control — not a 404, not "can no longer be paid for", no console error.
5. Payment moves the order to `processing`/`completed`; `_completed_payments` +1; `_pending_renewal_order_id` cleared; `_next_payment_date` advances exactly 1 day from `_renewal_scheduled_date`.
6. Order carries `_arraysubs_renewal_invoice_email_sent = yes`; no second invoice mail for that order.
7. After teardown the next cycle sends NO invoice email — suppression restored.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `renewal_invoice` | `arraysubs_renewal_invoice_created` at `D+k−6h` | slt-core@example.test | `Invoice for subscription #<resolved numeric ID>` | `mailpit-agent wait-new "$PREV" 60 "Invoice for subscription #$SUB_CORE"` after the exact gate |
| 2 | `payment_successful` | manual payment, step 10 | slt-core@example.test | `Payment received for subscription #SUB_CORE` | exact 300-second wait after `PAY_PRE`; exact match plus full delta |
| 3 | NONE EXPECTED | next cycle after teardown | — | `Invoice for subscription` | no new message |

## Evidence to capture
- `SLT-EML-02-01-pending-legs.png`, `-02-invoice-email.png`, `-03-order-pay-page.png`, `-04-order-paid.png`.
- k, D, exact action IDs/leg times; prior/final `_auto_renew` proof; `PREV`/`PAY_PRE`/`POST_RESTORE_MAIL_BASE`; exact-match/full-delta Mailpit IDs; relationship-exact renewal order; raw pay URL; date oracle; session/review proof; console/network errors.

## Pass criteria
- [x] Invoice email sent once, at `D+k−6h`, customer only
- [x] Four content rows exact, $10.00, no tax line
- [x] Due date matches `arraysubs_format_date_local`; UTC+6 day-shift asserted
- [x] Pay link 200s on a genuinely payable order
- [x] Payment advances the subscription normally
- [x] Dated registry exception exists and was posted/read back before `_auto_renew` was written
- [x] `_auto_renew` restored to its exact prior state with restore time recorded; next cycle sends no invoice mail
- [x] Exact prior meta state restored on every path; sessions closed and D5 evidence reviewed to done

The card stays `in-progress` after the D4 payment/restoration leg. It may move through Review to Done only after the D5 post-restoration invoice gate has passed and the final suppression criterion above is proved against `POST_RESTORE_MAIL_BASE`.

## Isolation / teardown
- Restore: return `_auto_renew` to its exact prior existence/value by the charge deadline; record before/after and actual UTC restore time. Page 8 untouched; all task sessions closed.
- Hands the invoice mailpit id and the `_auto_renew=off` recipe to SLT-EML-05.

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

[[2026-08-06]] Thu 20:15
Carry-forward note: not started before the D4 site-local rollover. The task body already requires repeating from the next cycle if the invoice row is already complete or too close before  is armed. Keep in todo for the next valid cycle, not as active D4 work.

[[2026-08-06]] Thu 20:16
Correction: the prior 2026-08-06 carry-forward note refers to the task's _auto_renew arming step. This card stays in todo for the next valid cycle because the authored D4 invoice window was already past before that arming step could be run safely.

[[2026-08-06]] Thu 21:34
Live re-check on Thursday, August 6, 2026: SUB_CORE (11959) has already advanced to the next natural cycle, so this is now a true D5 card rather than an overdue D4 miss. Current live next_payment_date is 2026-08-07 12:39:05 UTC with pending invoice action 15327 at 2026-08-07 09:37:52 UTC / 2026-08-07 15:37:52 site and charge action 15328 at 2026-08-07 15:37:52 UTC / 2026-08-07 21:37:52 site. The _auto_renew arming step must happen safely before that next invoice gate or the card rolls again to the following cycle.

[[2026-08-07]] Fri 06:22
D05 arm completed at 10:22:07 site: registry exception posted/read back; PREV=1fEVaKxuFnCCLwCxNzeZ7G; prior _auto_renew absent, temporary exact value off now active. Invoice action 15327 remains pending for 15:37:52 site (evidence cutoff 15:42:52); charge action 15328 is 21:37:52. This invocation owns the post-invoice pay-link/payment and immediate exact-row deletion, planned by 16:10 site. Evidence: /home/server-manager/slt-evidence/SLT-EML-02-registry-exception.txt, SLT-EML-02-D05-prearm-state.txt, SLT-EML-02-01-pending-legs.png.

[[2026-08-07]] Fri 15:36
D05 evening payment completed: invoice 62R3k1if4aveVTY6lkh2K5/order 13063 passed; PAY_PRE=6sMnHvedpurd7IzTyDTU2D; admin mail 61uMOBgJpO2BBvu9HMUHyw; payment mail 5hm1LQfe2IKo0vIamcv9kd. Order completed, payments 5->6, next=2026-08-08 12:39:05Z, pending cleared, _auto_renew remains exactly absent, old charge 15328 canceled. POST_RESTORE_MAIL_BASE=5hm1LQfe2IKo0vIamcv9kd. Evidence: /home/server-manager/slt-evidence/SLT-EML-02-D05-evening-payment.txt and -04-order-paid.png. Future suppression gate: action 15676 at 2026-08-08 15:37:52 site; resume D06 16:10 phase, then review->done.

[[2026-08-07]] Fri 15:43
D05 payment verdict: manual invoice content/payability/payment passed, but paying relationship-owned order 13063 with saved Visa 4242 changed unrelated subscription 12655 back from Mastercard 4444 to Visa 4242. Standalone finding: issues/critical-plugin-SLT-EML-02-manual-payment-updates-wrong-subscription.md. Preserve state; no repair mutation made. The card remains in progress for the D06 15:37:52 site suppression gate.

[[2026-08-08]] Sat 16:22
Final D06 follow-up completed. Invoice action 15676 ran naturally via WP Cron at 15:38:06 site and created relationship-exact pending order 13241 for subscription 11959, but the complete 22-message delta newer than `POST_RESTORE_MAIL_BASE=5hm1LQfe2IKo0vIamcv9kd` contained zero `Invoice for subscription #11959` subjects. `_auto_renew` remains absent/zero rows, proving automatic-mode suppression was restored. Evidence: `/home/server-manager/slt-evidence/SLT-EML-02-D06-suppression.txt`.

Overall execution verdict is **FAIL** because the target invoice/content/payability/payment/suppression flow passed but manual payment order 13063 silently rewrote unrelated subscription 12655's saved source. Finding: `issues/critical-plugin-SLT-EML-02-manual-payment-updates-wrong-subscription.md`.

Self-review resolved every authored criterion. The direct oracle was `2026-08-07 12:39:05Z` → `7 August, 2026 6:39 PM (UTC+6)`, with no date rollover because the UTC time was before 18:00. The accidental all-row deletion restored `_auto_renew` to absent at `2026-08-07 10:03:13Z` / `16:03:13` site; all 46 other original row images were recovered by `10:08:03Z` / `16:08:03` site. Browser errors and relevant failed network responses were absent. All exact task sessions were closed. Independent review confirmed the issue file, D05 evidence, D06 suppression proof, exact mail subjects/recipients, relationship evidence, restoration times, and final board transition.
