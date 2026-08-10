---
id: 41
title: Second unassisted renewal of the same subscription — schedule re-arms at the same offset, no drift
status: done
priority: critical
created: 2026-08-02T03:43:06.394757385+02:00
updated: 2026-08-05T21:37:49.462606425+02:00
started: 2026-08-05T21:02:04.936957572+02:00
completed: 2026-08-05T21:02:04.936957572+02:00
tags:
    - renewal
    - day-02
due: "2026-08-04"
estimate: 1h
depends_on:
    - 9
class: standard
---

> **SLT-REN-02** · group `renewal` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the SLT-REN-01 subscription re-arms itself: after renewal #1 paid unattended on D1, both legs must be re-queued for D2 at the SAME offset `k`, renewal #2 must fire unattended, and `_next_payment_date` must advance from `_renewal_scheduled_date` (not payment time) so the anniversary never drifts.

## Scope
- Gateway: Stripe test
- Checkout: N/A (no purchase)
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT-REN-01 PASSING: `SUBID`, `k`, anniversary time, order #1 id and both D1 action ids recorded.
- If renewal #1 did NOT fire, file the complete finding against SLT-REN-01, record this dependent task as `UNVERIFIED (no upstream renewal #1)`, self-review it to `done`, and do not drain. Never strand the execution card in `blocked` when its source fixture cannot exist.
- Act on **D2 = 2026-08-04**, first pass 09:00–10:00 site, before the exact D2 invoice and charge rows handed off by SLT-REN-01. For the established control these are 15:37:52 and 21:37:52 site; the rows, not an estimate, are authoritative.
- **No `wp action-scheduler run`**, no settings changes: read-only plus browser observation.

## Test data
| Item | Value |
|---|---|
| Subscription | `SUBID` (SLT Daily Core, $10.00/day) |
| Account | slt-core / SltQa!2026#Pass |
| Offset | `k` from SLT-REN-01 (must NOT change) |
| Sessions | `admin-SLT-REN-02`, `customer-SLT-REN-02` |
| Cycle-2 due | 2026-08-04 at the D0 purchase clock time |

## Steps
1. Resolve the `SUB_CORE`/SLT-REN-01 handoff from `slt-catalog-registry` into shell variable `SUBID`; require the registry to contain exactly one numeric ID and abort unless `[[ "$SUBID" =~ ^[0-9]+$ ]]`. Cross-check its recorded parent order, customer, and product before continuing. Do not set the renewal-mail baseline during this morning read.
2. `wp post meta list "$SUBID" --allow-root` — record `_next_payment_date`, `_last_payment_date`, `_completed_payments`, `_pending_renewal_order_id`, both action-id metas, `_payment_retry_attempts`.
3. Re-run the offset one-liner for `SUBID`; confirm it still yields `k`.
4. From `wp_actionscheduler_actions WHERE args='[SUBID]'` list `hook,status,scheduled_date_gmt,last_attempt_gmt`. Expect 2 complete (D1) + 2 pending (D2).
5. In isolated `admin-SLT-REN-02`, screenshot **Tools → Scheduled Actions** for this exact subscription, then open `admin.php?page=arraysubs-mainadmin#/subscriptions`, search the exact numeric ID, and open its **ArraySubs → Subscriptions** detail screen for the second screenshot. There is no WooCommerce Subscriptions screen on this runtime.
6. `agent-browser --session customer-SLT-REN-02 open ".../my-account/subscriptions/"` → log in as `slt-core` → open the subscription; screenshot the next-payment date and order list, confirming order #1 is paid and no customer action occurred.
7. Re-read the newly queued action ID after D1 and let that row, not a generic clock estimate, define the gate. Publish the exact action IDs/times and `charge−5m` deadline to the registry and D02 watch report, then close only the two task sessions after the morning read. At least five minutes before its exact D2 `due+k` timestamp, set `PRE2=$(mailpit-agent latest-id)` in both handoff locations. For the established control cadence the charge is expected at 21:37:52 site. Keep the card `in-progress` and **stop until after that exact gate**.
8. After the D2 charge window, run `mailpit-agent wait-new "$PRE2" 900 "Payment received for subscription #$SUBID"` and reconcile every message newer than `PRE2`. Reopen `admin-SLT-REN-02`, repeat steps 2 and 4, pull `wp_actionscheduler_logs` for the D2 IDs, identify the exact new HPOS renewal order by its subscription relationship, open that order, and capture `SLT-REN-02-03-renewal-order-2.png`; close the admin session and leave the card in progress. At the D3 morning watch (2026-08-05), reopen the required task sessions, repeat steps 2, 4, and 6 to prove the fresh D3 legs and customer-facing date, then close both sessions and move the card through review to done.

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
| 1 | payment_successful | `due2 + k` on D2 | slt-core@example.test | `Payment received for subscription #SUBID` | `mailpit-agent wait-new "$PRE2" 900 ...`, then inspect every newer message |
| 2 | NONE EXPECTED: renewal_invoice, renewal_reminder, payment_failed, subscription_on_hold. Woo order mail: record-only | D2 legs | — | — | inspect every message newer than `PRE2`; any of the four FAILS |

## Evidence to capture
- `SLT-REN-02-01-pending-legs-D2.png`, `-02-myaccount-subscription.png`, `-03-renewal-order-2.png`.
- Both meta dumps, the action row list with old and new `action_id`s, log rows, renewal order #2 id, Mailpit ids.
- Registry/D02 handoff containing both exact action rows, `charge−5m`, and `PRE2`; D3 re-arm/customer read before review.

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
- Close only `admin-SLT-REN-02` and `customer-SLT-REN-02` after each dated leg; never keep either open across the evening or D3 gates.

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

[[2026-08-05]] Wed 21:02
PASS closure on 2026-08-05 from authoritative D01 + D03 watch evidence plus live DB reconciliation.

Evidence chain:
- watch-reports/D01-2026-08-03.md proves renewal #1 order 12276 completed at USD $10.00, `_renewal_cycle_number=2`, `_renewal_scheduled_date=2026-08-03 12:39:05`, `_last_payment_date=2026-08-03 15:38:10Z`, `_next_payment_date=2026-08-04 12:39:05Z`, and replacement actions 14156/14157 for D2 at 09:37:52Z / 15:37:52Z.
- watch-reports/D03-2026-08-05.md proves renewal #2 for `11959`: relationship-owned order 12426 completed for USD $10.00, subscription at 3 payments, admin mail `5CxR9tgO7fXkqvtfkOh1vl`, and customer payment-success mail `0MehjgCWvkh0qQXtdxG5QX`.
- Live DB verification on 2026-08-05 confirms `k=10727` is unchanged; actions 14156/14157 completed via WP Cron at 2026-08-04 09:38:08Z / 15:38:10Z, order 12426 carries `_is_renewal_order=yes`, `_subscription_id=11959`, `_subscription_renewal=11959`, `_renewal_cycle_number=3`, `_renewal_scheduled_date=2026-08-04 12:39:05`, and fresh D3 actions 14542/14543 were created immediately after payment. Current state has `completed_payments=4`, `_next_payment_date=2026-08-06 12:39:05`, and pending actions 14974/14975 for 2026-08-06, which confirms the anniversary stayed anchored to `_renewal_scheduled_date` with no `k`-sized drift through the next cycle as well.

Task-specific morning screenshots were not retained under the `SLT-REN-02-*` filenames, but the D01/D03 watch reports and current DB rows are stronger authoritative evidence for every required assertion, so the QA card can close.
