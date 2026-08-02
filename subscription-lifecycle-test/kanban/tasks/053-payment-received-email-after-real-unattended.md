---
id: 53
title: Payment received email after real unattended renewals on Stripe and Paddle
status: todo
priority: critical
created: 2026-08-02T03:43:07.639401324+02:00
updated: 2026-08-02T03:43:18.044193462+02:00
tags:
    - email
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h
depends_on:
    - 5
    - 23
class: standard
---

> **SLT-EML-03** · group `emails` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`, `SLT-EML-13`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-06`, `SLT-CHK-01`, `SLT-EML-07`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Prove `payment_successful` is sent exactly once per paid renewal order, right after a real unattended renewal, with correct amount, payment method and next-payment date — on Stripe (local charge) and Paddle (webhook-driven; the local charge leg is a no-op). Capture the Paddle ordering hazard: the email renders before `syncNextPaymentDate()` overwrites `_next_payment_date` with Paddle's `next_billed_at`.

## Scope
- Gateway: both (Stripe test, Paddle sandbox)
- Checkout: N/A — both subscriptions already exist
- Account: existing (`slt-core`, `slt-paddle`)
- Plugins: both

## Preconditions
- SLT Daily Core bought by `slt-core` D0 (2026-08-02 PM, card 4242) → `SUB_CORE`, $10.00/day. SLT Paddle Daily bought by `slt-paddle` D2 (2026-08-04 PM, Paddle sandbox card) → `SUB_PAD`, $11.00/day; never touch it with Stripe.
- Today (D3, 2026-08-05) both are due in the afternoon: `SUB_CORE` renewal #3, `SUB_PAD` renewal #1.
- Guard `_arraysubs_renewal_payment_success_email_sent` = once per order (`EmailManager.php:524-546`).
- SLT-REF-09: Paddle `processRenewalPayment()` returns `pending` and charges nothing; paid state and email arrive only when `transaction.completed` hits `POST /wp-json/arraysubs/v1/webhooks/arraysubs_paddle`.
- **Force-run nothing.** A renewal that misses its window is a bug — capture evidence first.

## Test data
| Item | Value |
|---|---|
| Stripe | `SUB_CORE`, SLT Daily Core, $10.00 |
| Paddle | `SUB_PAD`, SLT Paddle Daily, $11.00 |
| Charge moment | `_next_payment_date + k`, k = `crc32('arraysubs-spread-'.$id) % 21600` |
| Subject | `[<site title>] Payment received for subscription #<id>` |

## Steps
1. Read both `_next_payment_date`; compute both k: `php -r '$h=(int)sprintf("%u",crc32("arraysubs-spread-ID"));printf("%ds\n",$h%21600);'`; write both windows (`D+k` … `D+k+5min`) to evidence before anything fires.
2. `agent-browser --session admin open ".../wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=SUB_CORE"`; screenshot the pending `arraysubs_process_renewal` row; repeat for `SUB_PAD`.
3. `PREV=$(mailpit-agent latest-id)`; `mailpit-agent wait-new "$PREV" 5400 "Payment received for subscription #SUB_CORE"`. On exit 124, screenshot the AS row and the subscription notes before anything else.
4. `mailpit-agent show <id>`; `mailpit-agent text <id>`.
5. wp-admin → Orders: open the new renewal order; screenshot status, total, `_is_renewal_order`, `_renewal_cycle_number`. Then `wp post meta list SUB_CORE --keys=_next_payment_date,_last_payment_date,_completed_payments,_payment_retry_attempts --allow-root`.
6. `PREV2=$(mailpit-agent latest-id)`; `mailpit-agent wait-new "$PREV2" 5400 "Payment received for subscription #SUB_PAD"`.
7. Open the `SUB_PAD` edit screen; screenshot the notes; `wp post meta get SUB_PAD _next_payment_date --allow-root`; compare it with the `Next Payment Date` printed in the Paddle email.
8. `mailpit-agent list 50`: exactly one `Payment received` per subscription this cycle, none for non-SLT subscriptions.

## Expected results
1. `SUB_CORE`: order created `pending` at `D+k−6h`, paid at `D+k` ±5 min, status `processing`/`completed`, total `$10.00`, no tax line.
2. One mail to `slt-core@example.test`: "We have received your payment for subscription #SUB_CORE. Your subscription has been renewed.", Product `SLT Daily Core`, Amount Paid `$10.00`, a Payment Method row naming the Stripe card, `Next Payment Date` in UTC+6.
3. `_completed_payments` +1, `_last_payment_date` set, `_payment_retry_attempts` absent/0, `_next_payment_date` advanced exactly 1 day from `_renewal_scheduled_date`.
4. Order carries `_arraysubs_renewal_payment_success_email_sent = yes`; no duplicate mail.
5. `SUB_PAD`: `arraysubs_process_renewal` completes without charging, leaving a note containing "awaiting automatic charge from Paddle".
6. One mail to `slt-paddle@example.test`, Amount Paid `$11.00`, Product `SLT Paddle Daily`.
7. If step 7's `_next_payment_date` differs from the date in that email, file `issues/SLT-EML-03-paddle-next-date-in-email.md` citing `PaddleGateway :1379-1384, :2289-2306` vs `OrderIntegration.php:1236`.
8. No Paddle webhook in the window ⇒ Paddle leg is `UNVERIFIED`, never rounded up to PASS.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_successful` Stripe | `arraysubs_renewal_payment_complete` at `D+k` | slt-core@example.test | `Payment received for subscription #SUB_CORE` | `wait-new $PREV 5400` |
| 2 | `payment_successful` Paddle | `transaction.completed` webhook | slt-paddle@example.test | `…#SUB_PAD` | `wait-new $PREV2 5400` |
| 3 | NONE EXPECTED | — | — | `Invoice for subscription` | suppressed: automatic + auto-renew on |
| 4 | NONE EXPECTED | — | — | `Payment failed` (either sub) | `mailpit-agent list 50` |

## Evidence to capture
- `SLT-EML-03-01-pending-legs.png`, `-02-stripe-email.png`, `-03-renewal-order.png`, `-04-paddle-email.png`, `-05-paddle-notes.png`; both k values and windows; mailpit ids; order ids; meta dumps; send times vs predicted windows.

## Pass criteria
- [ ] Both renewals fired unattended inside `D+k … D+k+5min`, nothing force-run
- [ ] Exactly one `Payment received` per subscription, correct recipient
- [ ] $10.00 and $11.00, no tax line, exact product names
- [ ] Next Payment Date in UTC+6; Paddle divergence recorded
- [ ] Guard meta on both orders; no invoice or failure mail for either

## Isolation / teardown
- Read-only; nothing written; the schedule was allowed to run.
- Hands the Stripe `payment_successful` mailpit id to SLT-EML-05 as the HTML baseline.

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
