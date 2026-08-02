---
id: 41
title: Second unassisted renewal of the same subscription — schedule re-arms at the same offset, no drift
status: todo
priority: critical
created: 2026-08-02T03:43:06.394757385+02:00
updated: 2026-08-02T03:43:16.89992965+02:00
tags:
    - renewal
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 1h
depends_on:
    - 9
class: standard
---

> **SLT-REN-02** · group `renewal` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`medium` · shared-per-subscription-meta vs published watch contract** — with `SLT-EML-02`, `SLT-EML-05`, `SLT-EML-15`

- *Problem:* SLT-EML-15 (d2) publishes to the registry the reconciled expected-mail set for one SLT Daily Core renewal, explicitly asserting 'zero renewal_invoice - suppressed for automatic subs with auto-renew on' and states 'this is the reference the D3-D12 watch uses to classify daily renewal mail'. SLT-EML-02 (d4) and SLT-EML-05 (d6) then each write _auto_renew=off on that very subscription for one cycle, deliberately producing an 'Invoice for subscription #SUB_CORE' email plus a manually-paid renewal on D4 and D6. The watcher, reading EML-15's table, will classify both as UNMAPPED and file them as leaks - and will also see the charge leg leave the order in a non-standard state.
- *Required fix:* EML-02 and EML-05 must each post a dated exception to the registry BEFORE flipping the meta ('SUB_CORE cycle due <date>: _auto_renew=off, one renewal_invoice + one customer-paid renewal order expected; suppression restored at <time>'), and the watch schedule rows for D4/D5 and D6/D7 must carry those exceptions as expected rather than negative. Add to both tasks a pass criterion 'the registry exception exists and was posted before the meta write' and a teardown criterion 'the next cycle after restore sends no invoice mail'.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`, `SLT-EML-07`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Prove the SLT-REN-01 subscription re-arms itself: after renewal #1 paid unattended on D1, both legs must be re-queued for D2 at the SAME offset `k`, renewal #2 must fire unattended, and `_next_payment_date` must advance from `_renewal_scheduled_date` (not payment time) so the anniversary never drifts.

## Scope
- Gateway: Stripe test
- Checkout: N/A (no purchase)
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT-REN-01 PASSING: `SUBID`, `k`, anniversary time, order #1 id and both D1 action ids recorded.
- If renewal #1 did NOT fire, this task is blocked, not failed: file against SLT-REN-01, do not drain.
- Act on **D2 = 2026-08-04**, first pass 09:00–10:00 site, before the D2 charge leg (~13:15+k).
- **No `wp action-scheduler run`**, no settings changes: read-only plus browser observation.

## Test data
| Item | Value |
|---|---|
| Subscription | `SUBID` (SLT Daily Core, $10.00/day) |
| Account | slt-core / SltQa!2026#Pass |
| Offset | `k` from SLT-REN-01 (must NOT change) |
| Session | `customer-SLT-REN-02` |
| Cycle-2 due | 2026-08-04 at the D0 purchase clock time |

## Steps
1. `PRE2=$(mailpit-agent latest-id)`; record it.
2. `wp post meta list SUBID` — record `_next_payment_date`, `_last_payment_date`, `_completed_payments`, `_pending_renewal_order_id`, both action-id metas, `_payment_retry_attempts`.
3. Re-run the offset one-liner for `SUBID`; confirm it still yields `k`.
4. From `wp_actionscheduler_actions WHERE args='[SUBID]'` list `hook,status,scheduled_date_gmt,last_attempt_gmt`. Expect 2 complete (D1) + 2 pending (D2).
5. Screenshot **Tools → Scheduled Actions** for this subscription and the **WooCommerce → Subscriptions** detail screen.
6. `agent-browser --session customer-SLT-REN-02 open ".../my-account/subscriptions/"` → log in as `slt-core` → open the subscription; screenshot the next-payment date and order list, confirming order #1 is paid and no customer action occurred.
7. **Stop until after `due+k` on D2** (worst case 19:15 site).
8. After the D2 charge window and again at the D3 watch (2026-08-05): repeat 2 and 4; pull `wp_actionscheduler_logs` for the D2 ids; list `wp_wc_orders` for `customer_id=<uid>`; `mailpit-agent list 50`.

## Expected results
1. Pre-fire: `_completed_payments=2`, `_last_payment_date` = the D1 payment moment, `_pending_renewal_order_id` ABSENT (cleared on payment), `_payment_retry_attempts` absent or 0.
2. `_next_payment_date` = D1 due + exactly 24 h, same clock time as the D0 purchase: it is computed from `_renewal_scheduled_date`, so the `k`-sized lateness of the D1 charge causes **zero** drift.
3. `k` unchanged — the offset is `crc32('arraysubs-spread-'.SUBID) % 21600` and the id did not change.
4. Exactly two pending rows for `[SUBID]`: `arraysubs_generate_renewal_invoice` at `due2 + k − 21600s`, `arraysubs_process_renewal` at `due2 + k`; both `action_id`s are NEW (higher than the D1 ids) and match `_renewal_invoice_action_id` / `_renewal_action_id`.
5. After the D2 window both rows are `complete`, `last_attempt_gmt` within 90 s of schedule, logs `via WP Cron`, never `via WP CLI`.
6. Renewal order #2: $10.00, paid, `_is_renewal_order=yes`, `_renewal_cycle_number` exactly one more than order #1, `_renewal_scheduled_date` = the D2 due date, `created_via` EMPTY.
7. `_completed_payments=3`; `_next_payment_date` = D3 same clock time; two fresh legs queued for D3 at the same `k`.
8. Status stays `arraysubs-active` throughout; never `arraysubs-on-hold`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | payment_successful | `due2 + k` on D2 | slt-core@example.test | `Payment received for subscription #SUBID` | `list 50` after 19:15 site |
| 2 | NONE EXPECTED: renewal_invoice, renewal_reminder, payment_failed, subscription_on_hold. Woo order mail: record-only | D2 legs | — | — | `list 50`; any of the four FAILS |

## Evidence to capture
- `SLT-REN-02-01-pending-legs-D2.png`, `-02-myaccount-subscription.png`, `-03-renewal-order-2.png`.
- Both meta dumps, the action row list with old and new `action_id`s, log rows, renewal order #2 id, Mailpit ids.

## Pass criteria
- [ ] `_completed_payments` went 2 → 3 with no human action
- [ ] `_next_payment_date` advanced exactly 24 h, same clock time, no `k`-sized drift
- [ ] `k` identical to SLT-REN-01; both D2 legs queued at `due2+k−6h` and `due2+k`
- [ ] New action ids differ from the D1 ids and match the action-id metas
- [ ] Both D2 actions complete within 90 s, logged `via WP Cron`
- [ ] Renewal order #2 paid $10.00, cycle number +1, `created_via` empty
- [ ] Legs re-armed for D3; email 1 present, row-2 negatives absent

## Isolation / teardown
- Read-only; nothing changed or restored. The subscription keeps renewing daily until SLT-SETUP-99A cancels it on D10.
- From D3 on, this subscription is the plan's known-good control: any morning it has not renewed is a renewal finding.
- Close only `customer-SLT-REN-02`.

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
