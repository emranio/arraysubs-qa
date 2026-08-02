---
id: 57
title: Reconcile the full Mailpit set for one SLT Daily Core renewal — no double-send, nothing missing
status: todo
priority: critical
created: 2026-08-02T03:43:07.994935496+02:00
updated: 2026-08-02T03:43:18.406290997+02:00
tags:
    - email
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 2h
depends_on:
    - 10
    - 5
class: standard
---

> **SLT-EML-15** · group `emails` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · same-subscription collision / ambiguous target** — with `SLT-LIFE-02`, `SLT-EML-05`, `SLT-EML-02`, `SLT-MYA-02`

- *Problem:* SLT-LIFE-02 (d6) targets 'S1 - a live arraysubs-active SLT Daily Core subscription from the SLT-CHK-* run' without naming it, and its arithmetic uses $10.00 day/1, which describes SUB_CORE (slt-core, the control spine). It consumes one cycle by paying it early, replaces both legs and shifts the anniversary. SLT-EML-05 runs on the SAME day (d6) and also consumes one SUB_CORE cycle by setting _auto_renew=off and paying the invoice manually. Two tasks eating the same cycle on the same day makes both results unreadable, and either one silently invalidates the D1-D12 watch's 'SLT Daily Core renews $10.00 unattended every afternoon' baseline that REN-01/REN-02/EML-15/ADM-06 established.
- *Required fix:* Pin SLT-LIFE-02's S1 to SLT-CHK-02's subscription (slt-core2 + SLT Daily Core, day/1, $10.00, Stripe, saved token, unsynced, no pending skip) - structurally identical to the spine and claimed by nothing else after D0. Name the subscription id explicitly in LIFE-02's Test data and preconditions, and keep its step 8 registry note ('slt-core2's cycle N was paid early on 2026-08-08') so the watch does not read the missing unattended renewal as a failure. Leave SUB_CORE to EML-05 on D6. Add a standing registry section 'control-spine reservations' naming SUB_CORE's owning tasks per day.

**`medium` · shared-per-subscription-meta vs published watch contract** — with `SLT-EML-02`, `SLT-EML-05`, `SLT-REN-02`

- *Problem:* SLT-EML-15 (d2) publishes to the registry the reconciled expected-mail set for one SLT Daily Core renewal, explicitly asserting 'zero renewal_invoice - suppressed for automatic subs with auto-renew on' and states 'this is the reference the D3-D12 watch uses to classify daily renewal mail'. SLT-EML-02 (d4) and SLT-EML-05 (d6) then each write _auto_renew=off on that very subscription for one cycle, deliberately producing an 'Invoice for subscription #SUB_CORE' email plus a manually-paid renewal on D4 and D6. The watcher, reading EML-15's table, will classify both as UNMAPPED and file them as leaks - and will also see the charge leg leave the order in a non-standard state.
- *Required fix:* EML-02 and EML-05 must each post a dated exception to the registry BEFORE flipping the meta ('SUB_CORE cycle due <date>: _auto_renew=off, one renewal_invoice + one customer-paid renewal order expected; suppression restored at <time>'), and the watch schedule rows for D4/D5 and D6/D7 must carry those exceptions as expected rather than negative. Add to both tasks a pass criterion 'the registry exception exists and was posted before the meta write' and a teardown criterion 'the next cycle after restore sends no invoice mail'.

**`low` · duplicate-coverage** — with `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`, `SLT-EML-07`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Reconcile every Mailpit message produced by one unattended Stripe renewal of `SLT Daily Core` — invoice leg and charge leg — against a code-derived expected set. Prove nothing double-sends, nothing is missing, and that WooCommerce customer order emails are suppressed for renewal orders while the admin New Order email is not.

## Scope
- Gateway: Stripe test
- Checkout: N/A (observation only; no order placed)
- Account: existing (`slt-core`)
- Plugins: free-only

## Preconditions
- SLT-PROD-01 complete and `SLT Daily Core` bought by `slt-core` on D0 (2026-08-02, after 12:00); subscription **S1** is in the registry. Renewal #1 fired 2026-08-03; this task observes **renewal #2**, due 2026-08-04.
- Code basis: customer `processing`/`completed`/`on-hold`/`invoice`/`failed` order emails are suppressed for orders with `_is_renewal_order=yes` by blanking the recipient (`EmailManager.php:69,240-270`); `woocommerce_email_recipient_new_order` is **not** in that list, so the admin New Order email still sends. `renewal_invoice` is suppressed for automatic subs with auto-renew on (`:504-510`, L23); `payment_successful` is guarded per order by `_arraysubs_renewal_payment_success_email_sent` (`:530-539`).
- **No Action Scheduler drain.** A renewal that does not fire on its own is a genuine bug — capture evidence first.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core ($10.00, day/1), subscription **S1** |
| Offset | `k` = `crc32('arraysubs-spread-'.S1) % 21600` |
| Due #2 | `D2` = `_next_payment_date` read before the window (UTC) |
| Legs | invoice `D2+k−6h`; charge `D2+k` |

## Steps
1. `wp post meta get S1 _next_payment_date --allow-root` → `D2`; also read `_completed_payments`, `_payment_method`, `_auto_renew`.
2. `php -r '$h=(int)sprintf("%u",crc32("arraysubs-spread-".S1));printf("%d\n",$h%21600);'` → `k`. Write `D2`, `k` and both leg times (UTC + site) to `/home/server-manager/slt-evidence/SLT-EML-15-window.txt`.
3. Tools → Scheduled Actions, search `S1`; screenshot the two pending rows and confirm they match the computed legs to the minute.
4. `MPA=$(mailpit-agent latest-id)` at least 10 min **before** `D2+k−6h`.
5. After `D2+k−6h+10min`: `list 50`; list every id above `MPA`. **Invoice-leg assertion:** none addressed to `slt-core@example.test` or referencing the new renewal order.
6. WooCommerce → Orders (HPOS) filtered to `slt-core`: a **pending** renewal order `R2` exists; record id and total; `_pending_renewal_order_id` on S1 = R2.
7. `MPB=$(mailpit-agent latest-id)` at least 5 min before `D2+k`; then `wait-new $MPB 900 "Payment received for subscription #S1"`.
8. Immediately `list 50`; enumerate every id in `(MPB, latest]`, `show` each, classify into the table below or mark UNMAPPED; `html` the payment-successful message and capture To/From/Reply-To of both expected messages.
9. Re-read `R2` (status, total, `_is_renewal_order`, `_subscription_id`, `_renewal_cycle_number`, `_renewal_scheduled_date`) and S1 (`_next_payment_date`, `_completed_payments`, `_pending_renewal_order_id`).
10. Duplicate check: exactly one `Payment received for subscription #S1` and one `New order #R2` in the window. Screenshot the Mailpit list for the window.

## Expected results
1. Both legs fired unattended at `D2+k−6h` and `D2+k` (±5 min); no drain needed.
2. Invoice leg produced `R2`, status `pending`, total `$10.00`, no tax line, and **zero** email.
3. Charge leg moved `R2` pending → `processing` and produced exactly **two** messages: admin `New order #R2` and `Payment received for subscription #S1`.
4. Zero customer processing/completed/invoice order mail; zero `renewal_invoice`; zero `renewal_reminder` (1-day cycle < 3-day lead); zero `new_subscription`/`payment_failed`; no duplicates.
5. `S1._next_payment_date` = `D2 + 1 day`, `_completed_payments` +1, `_pending_renewal_order_id` removed; every id in `(MPA, latest]` maps to a row below or to another SLT task's declared mail — anything UNMAPPED is a finding.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED (invoice leg) | `D2+k−6h` | — | — | `list 50`; no new id to slt-core@ |
| 2 | WooCommerce New order (admin) | `D2+k` pending→processing | admin_email | `New order #R2` | `mailpit-agent show <id>` |
| 3 | payment_successful | renewal payment complete | slt-core@example.test | `Payment received for subscription #S1` | `wait-new $MPB 900` |
| 4 | NONE EXPECTED — customer order mail, renewal_invoice, renewal_reminder | whole window | — | — | zero subjects containing `order is now processing`, `order is complete`, `Invoice for order`, `Invoice for subscription #S1`, `renews soon` |

## Evidence to capture
- `SLT-EML-15-window.txt`; screenshots `-01-scheduled-actions.png`, `-02-pending-order.png`, `-03-order-processing.png`, `-04-mailpit-window.png`, `-05-payment-html.png`.
- IDs S1, R2, `MPA`, `MPB`; every window message id with its classification; `show` output for messages 2 and 3.

## Pass criteria
- [ ] Both legs fired unattended at the computed offsets, no drain
- [ ] Invoice leg: pending order created, zero mail; charge leg: exactly two messages
- [ ] Zero customer WooCommerce order emails for the renewal order
- [ ] Zero renewal_invoice, renewal_reminder, new_subscription; no duplicates
- [ ] Schedule and counters advanced correctly; every window message classified, zero UNMAPPED

## Isolation / teardown
- Read-only: no setting changed, no order placed, no status edited, no drain.
- Handed on: post the reconciled expected-set table to `slt-catalog-registry` — the reference the D3–D12 watch uses to classify daily renewal mail. The admin New Order email firing for renewal orders is an expected observation; if judged undesirable, file a product finding, not a task failure.
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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
