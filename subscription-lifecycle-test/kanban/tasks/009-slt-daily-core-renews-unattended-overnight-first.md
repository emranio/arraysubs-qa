---
id: 9
title: SLT2 Daily Core renews unattended overnight — first cycle, spread-offset window, cron-not-CLI proof
status: blocked
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T22:35:28.151378759+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - day-00
due: "2026-08-23"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 1
    - 131
class: standard
---

## Current execution blocker — 2026-08-23 site date

Blocked by critical shared issue `qa/issues` #1 / preflight task `131` and by missing source subscription task `1`. Product `31340` exists, but there is intentionally no subscription/action/mail gate to watch yet. After task 131 and checkout task 1 pass, publish the fresh live IDs/timestamps and begin the natural cron-only watch; never synthesize or force-run the renewal.

> **SLT-REN-01** · group `renewal` · scheduled **D00** (2026-08-23)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Observe the `SLT2 Daily Core` control subscription created by `SLT-CHK-01` and prove its first renewal fires unattended overnight — no click, no drain — inside the spread window `due+k`, with an Action Scheduler trail that separates cron execution from a forced run.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt2-core`)
- Plugins: both

## Preconditions
- SLT-SETUP-01/02/03 + SLT-PROD-01 and `SLT-CHK-01` done; `SUB_CORE`, parent order, anniversary, action IDs and `k` are published in the registry.
- **This task places no order.** `SLT-CHK-01` is the sole owner of the D0 control purchase. Its fresh registry values and live action timestamps govern this task; no authored clock or prior-cycle action ID is valid.
- **No `wp action-scheduler run` in this task, any day.** A renewal that does not fire is the finding — capture it, never force it.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core, $10.00/day |
| Account | slt2-core / SltQa!2026#Pass |
| Source | `SUB_CORE` and parent order from the registry / `SLT-CHK-01` |
| Session | `admin-SLT-REN-01` (read-only verification only) |
| Due | 2026-08-24 ≈ purchase clock time |

## Steps
1. Read the numeric `SUB_CORE`, parent order, anniversary, action IDs and `k` from the registry; assign the numeric value to shell variable `SUB_CORE`. Do not create a customer session or place an order.
2. From `wp_wc_orders`: record parent `id,status,total_amount,created_via,payment_method`.
3. `wp post meta list "$SUB_CORE" --allow-root` — keep `_next_payment_date`, `_completed_payments`, `_payment_gateway`, `_auto_renew`, `_renewal_action_id`.
4. Independently recompute `k` with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));echo $h%21600;' "$SUB_CORE"` and compare it with the registry. Abort unless `SUB_CORE` is numeric; never hash an alias string.
5. From `wp_actionscheduler_actions WHERE args='[SUB_CORE]'` record `action_id,hook,status,scheduled_date_gmt,last_attempt_gmt`; screenshot **Tools → Scheduled Actions**.
6. **Stop until D1.** The D1 early watch (06:10 site, 2026-08-24) precedes both registered legs: it verifies only that they are still pending at their computed timestamps.
7. At least five minutes before the freshly registered charge action, store `REN1_PRE=$(mailpit-agent latest-id)` with that action ID and gate. After its D1 charge time, poll in calls no longer than 60 seconds through the allowed window for `Payment received for subscription #$SUB_CORE`, then reconcile every message newer than `REN1_PRE`. Re-run steps 2, 3 and 5 after the gate and again at the D2 watch (2026-08-25), pulling logs for the two exact fresh IDs.

## Expected results
1. Parent order paid, `total_amount=10.00`, `created_via=store-api`, `payment_method=stripe`; subscription `arraysubs-active`, `_completed_payments=1`, `_next_payment_date` = purchase time + exactly 24 h.
2. Pending `arraysubs_generate_renewal_invoice` `[SUBID]` at `due + k − 21600s`; pending `arraysubs_process_renewal` `[SUBID]` at `due + k`; `k` matches step 4.
3. After D1 both rows are `complete`, each `last_attempt_gmt` within 90 s of its `scheduled_date_gmt`.
4. Logs for both ids read `action started via WP Cron` / `action complete via WP Cron`, never `via WP CLI`.
5. Renewal order: `_is_renewal_order=yes`, `_subscription_id=SUBID`, `_renewal_cycle_number=2` (the initial payment is cycle 1), `_renewal_scheduled_date` = the D1 due date, $10.00, paid, **`created_via` EMPTY** (renewal orders come from `wc_create_order()`; a `store-api` value means a human placed it).
6. Post-renewal: `_completed_payments=2`, `_last_payment_date` set, `_pending_renewal_order_id` deleted, `_next_payment_date` = D2 same clock time.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_successful` | `due+k` on D1 | slt2-core@example.test | `Payment received for subscription #<SUB_CORE>` | `mailpit-agent wait-new "$REN1_PRE" 900 ...`, then inspect every newer message |
| 2 | NONE EXPECTED: duplicate signup mail, renewal_invoice (Stripe auto-renew suppression), renewal_reminder (3-day lead > 1-day cycle), payment_failed. Woo order mail: record-only | observation window | — | — | inspect every message newer than `REN1_PRE`; cite `SLT-CHK-01` for the original signup mail set |

## Evidence to capture
- `SLT-REN-01-02-pending-actions.png`, `-03-renewal-order-D1.png`; SUBID, order ids, action ids, `k`, `REN1_PRE`, resulting Mailpit ids, query output. Cite `SLT-CHK-01` for checkout evidence.

## Pass criteria
- [ ] Parent order paid $10.00, `created_via=store-api`, `_completed_payments=1`
- [ ] Legs queued at `due+k−6h` and `due+k` with the computed `k`
- [ ] Both actions complete within 90 s, logged `via WP Cron`, not `via WP CLI`
- [ ] Renewal order $10.00 paid, `_renewal_cycle_number=2`, `created_via` empty, `_completed_payments=2`, `_next_payment_date` +24 h
- [ ] Payment-successful mail present, row-2 negatives absent, no drain issued; no duplicate purchase/signup mail

## Isolation / teardown
- Hands SUBID, `k`, the anniversary time and both action ids to SLT-REN-02; the offset recipe is reused by REN-03/05.
- The recorded D3 invoice/charge timestamps for this control fall outside the 09:00–11:00 `SLT-SYN-04` bracket. If either action is observed inside that bracket or `_next_payment_date` re-anchors to `18:00:00` UTC, write a QA issue card under `qa/issues/`.
- Nothing restored; lives until SLT-SETUP-99A. Close only this session.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
