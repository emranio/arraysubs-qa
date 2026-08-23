---
id: 41
title: Second unassisted renewal of the same subscription — schedule re-arms at the same offset, no drift
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - day-02
due: "2026-08-25"
estimate: 1h
depends_on:
    - 9
class: standard
---

> **SLT-REN-02** · group `renewal` · scheduled **D02** (2026-08-25)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the SLT-REN-01 subscription re-arms itself: after renewal #1 paid unattended on D1, both legs must be re-queued for D2 at the SAME offset `k`, renewal #2 must fire unattended, and `_next_payment_date` must advance from `_renewal_scheduled_date` (not payment time) so the anniversary never drifts.

## Scope
- Gateway: Stripe test
- Checkout: N/A (no purchase)
- Account: existing (`slt2-core`)
- Plugins: both

## Preconditions
- SLT-REN-01 PASSING: `SUBID`, `k`, anniversary time, order #1 id and both D1 action ids recorded.
- If renewal #1 did not fire, create/update the complete issue against SLT-REN-01 and move this dependent task to blocked. Resume it only after the upstream renewal is fixed and rerun; never drain or fabricate the missing cycle.
- Act on **D2 = 2026-08-25**, first pass 09:00–10:00 site, before the exact D2 invoice and charge rows handed off by SLT-REN-01. For the established control these are 15:37:52 and 21:37:52 site; the rows, not an estimate, are authoritative.
- **No `wp action-scheduler run`**, no settings changes: read-only plus browser observation.

## Test data
| Item | Value |
|---|---|
| Subscription | `SUBID` (SLT2 Daily Core, $10.00/day) |
| Account | slt2-core / SltQa!2026#Pass |
| Offset | `k` from SLT-REN-01 (must NOT change) |
| Sessions | `admin-SLT-REN-02`, `customer-SLT-REN-02` |
| Cycle-2 due | 2026-08-25 at the D0 purchase clock time |

## Steps
1. Resolve the `SUB_CORE`/SLT-REN-01 handoff from `slt2-catalog-registry` into shell variable `SUBID`; require the registry to contain exactly one numeric ID and abort unless `[[ "$SUBID" =~ ^[0-9]+$ ]]`. Cross-check its recorded parent order, customer, and product before continuing. Do not set the renewal-mail baseline during this morning read.
2. `wp post meta list "$SUBID" --allow-root` — record `_next_payment_date`, `_last_payment_date`, `_completed_payments`, `_pending_renewal_order_id`, both action-id metas, `_payment_retry_attempts`.
3. Re-run the offset one-liner for `SUBID`; confirm it still yields `k`.
4. From `wp_actionscheduler_actions WHERE args='[SUBID]'` list `hook,status,scheduled_date_gmt,last_attempt_gmt`. Expect 2 complete (D1) + 2 pending (D2).
5. In isolated `admin-SLT-REN-02`, screenshot **Tools → Scheduled Actions** for this exact subscription, then open `admin.php?page=arraysubs-mainadmin#/subscriptions`, search the exact numeric ID, and open its **ArraySubs → Subscriptions** detail screen for the second screenshot. There is no WooCommerce Subscriptions screen on this runtime.
6. `agent-browser --session customer-SLT-REN-02 open ".../my-account/subscriptions/"` → log in as `slt2-core` → open the subscription; screenshot the next-payment date and order list, confirming order #1 is paid and no customer action occurred.
7. Re-read the newly queued action ID after D1 and let that row, not a generic clock estimate, define the gate. Publish the exact action IDs/times and `charge−5m` deadline to the registry and D02 watch report, then close only the two task sessions after the morning read. At least five minutes before its exact D2 `due+k` timestamp, set `PRE2=$(mailpit-agent latest-id)` in both handoff locations. For the established control cadence the charge is expected at 21:37:52 site. Keep the card `in-progress` and **stop until after that exact gate**.
8. After the D2 charge window, run `mailpit-agent wait-new "$PRE2" 900 "Payment received for subscription #$SUBID"` and reconcile every message newer than `PRE2`. Reopen `admin-SLT-REN-02`, repeat steps 2 and 4, pull `wp_actionscheduler_logs` for the D2 IDs, identify the exact new HPOS renewal order by its subscription relationship, open that order, and capture `SLT-REN-02-03-renewal-order-2.png`; close the admin session and leave the card in progress. At the D3 morning watch (2026-08-26), reopen the required task sessions, repeat steps 2, 4, and 6 to prove the fresh D3 legs and customer-facing date, then close both sessions and move the card through review to done.

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
| 1 | payment_successful | `due2 + k` on D2 | slt2-core@example.test | `Payment received for subscription #SUBID` | `mailpit-agent wait-new "$PRE2" 900 ...`, then inspect every newer message |
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
- Read-only; nothing changed or restored. The subscription keeps renewing daily until SLT-SETUP-99A cancels it on D11.
- From D3 on, this subscription is the plan's known-good control: any morning it has not renewed is a renewal finding.
- Close only `admin-SLT-REN-02` and `customer-SLT-REN-02` after each dated leg; never keep either open across the evening or D3 gates.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
