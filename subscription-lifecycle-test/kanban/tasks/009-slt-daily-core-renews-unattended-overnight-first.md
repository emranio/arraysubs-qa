---
id: 9
title: SLT Daily Core renews unattended overnight — first cycle, spread-offset window, cron-not-CLI proof
status: done
priority: critical
created: 2026-08-02T03:43:03.652641586+02:00
updated: 2026-08-03T17:42:49.052685132+02:00
started: 2026-08-03T17:42:49.052684+02:00
completed: 2026-08-03T17:42:49.052684+02:00
tags:
    - renewal
    - day-00
due: "2026-08-02"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 1
class: standard
---

> **SLT-REN-01** · group `renewal` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Observe the `SLT Daily Core` control subscription created by `SLT-CHK-01` and prove its first renewal fires unattended overnight — no click, no drain — inside the spread window `due+k`, with an Action Scheduler trail that separates cron execution from a forced run.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT-SETUP-01/02/03 + SLT-PROD-01 and `SLT-CHK-01` done; `SUB_CORE`, parent order, anniversary, action IDs and `k` are published in the registry.
- **This task places no order.** `SLT-CHK-01` is the sole owner of the D0 control purchase. That completed at 18:39:05 site, so the exact registry and action timestamps—not the superseded planned 13:00–13:30 slot—govern this task.
- **No `wp action-scheduler run` in this task, any day.** A renewal that does not fire is the finding — capture it, never force it.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, $10.00/day |
| Account | slt-core / SltQa!2026#Pass |
| Source | `SUB_CORE` and parent order from the registry / `SLT-CHK-01` |
| Session | `admin-SLT-REN-01` (read-only verification only) |
| Due | 2026-08-03 ≈ purchase clock time |

## Steps
1. Read the numeric `SUB_CORE`, parent order, anniversary, action IDs and `k` from the registry; assign the numeric value to shell variable `SUB_CORE`. Do not create a customer session or place an order.
2. From `wp_wc_orders`: record parent `id,status,total_amount,created_via,payment_method`.
3. `wp post meta list "$SUB_CORE" --allow-root` — keep `_next_payment_date`, `_completed_payments`, `_payment_gateway`, `_auto_renew`, `_renewal_action_id`.
4. Independently recompute `k` with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));echo $h%21600;' "$SUB_CORE"` and compare it with the registry. Abort unless `SUB_CORE` is numeric; never hash an alias string.
5. From `wp_actionscheduler_actions WHERE args='[SUB_CORE]'` record `action_id,hook,status,scheduled_date_gmt,last_attempt_gmt`; screenshot **Tools → Scheduled Actions**.
6. **Stop until D1.** The D1 early watch (06:10 site, 2026-08-03) precedes both registered legs: it verifies only that they are still pending at their computed timestamps.
7. At least five minutes before action `13694`, store `REN1_PRE=$(mailpit-agent latest-id)` in the registry. On D1 after its registered charge time—`2026-08-03 15:37:52Z` = **21:37:52 site**—run `mailpit-agent wait-new "$REN1_PRE" 900 "Payment received for subscription #$SUB_CORE"` and reconcile every message newer than `REN1_PRE`. Then, and again at the D2 watch (2026-08-04), re-run steps 2, 3 and 5 and pull `wp_actionscheduler_logs` for both ids. Never use the superseded planned purchase slot to derive this gate.

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
| 1 | `payment_successful` | `due+k` on D1 | slt-core@example.test | `Payment received for subscription #<SUB_CORE>` | `mailpit-agent wait-new "$REN1_PRE" 900 ...`, then inspect every newer message |
| 2 | NONE EXPECTED: duplicate signup mail, renewal_invoice (Stripe auto-renew suppression), renewal_reminder (3-day lead > 1-day cycle), payment_failed. Woo order mail: record-only | observation window | — | — | inspect every message newer than `REN1_PRE`; cite `SLT-CHK-01` for the original signup mail set |

## Evidence to capture
- `SLT-REN-01-02-pending-actions.png`, `-03-renewal-order-D1.png`; SUBID, order ids, action ids, `k`, `REN1_PRE`, resulting Mailpit ids, query output. Cite `SLT-CHK-01` for checkout evidence.

## Pass criteria
- [x] Parent order paid $10.00, `created_via=store-api`, `_completed_payments=1`
- [x] Legs queued at `due+k−6h` and `due+k` with the computed `k`
- [x] Both actions complete within 90 s, logged `via WP Cron`, not `via WP CLI`
- [x] Renewal order $10.00 paid, `_renewal_cycle_number=2`, `created_via` empty, `_completed_payments=2`, `_next_payment_date` +24 h
- [x] Payment-successful mail present, row-2 negatives absent, no drain issued; no duplicate purchase/signup mail

## Isolation / teardown
- Hands SUBID, `k`, the anniversary time and both action ids to SLT-REN-02; the offset recipe is reused by REN-03/05.
- The recorded D3 invoice/charge timestamps for this control fall outside the 09:00–11:00 `SLT-SYN-04` bracket. If either action is observed inside that bracket or `_next_payment_date` re-anchors to `18:00:00` UTC, write a standalone issue file under `issues/`.
- Nothing restored; lives until SLT-SETUP-99A. Close only this session.

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

---

## D0 observation handoff — 2026-08-02

- No order was placed by this task. It consumes the `SLT-CHK-01` registry record as required by audit C01.
- `SUB_CORE=11959`; parent order `11949`; purchase/start `2026-08-02 12:39:05Z`; due `2026-08-03 12:39:05Z`.
- Independent offset result: `k=10727` seconds.
- Pending invoice action `13693`: `2026-08-03 09:37:52Z` (`15:37:52` site).
- Pending renewal action `13694`: `2026-08-03 15:37:52Z` (`21:37:52` site).
- D0 subscription state: `arraysubs-active`, `_completed_payments=1`, `_payment_gateway=stripe`; parent is paid/completed, USD `10.00`, `created_via=store-api`.
- D1 06:10 site watch must find both actions pending. No scheduler drain is authorized.
- D1 19:10 site phase must record `REN1_PRE` before action `13694`; the phase owns the 21:37:52 gate and must not defer baseline capture until the 21:42 phase.

## D1 natural renewal execution — 2026-08-03 — PASS

- `REN1_PRE=31P5AaXwGjwpSfrOt6IN4S` was captured and published at `21:33:40` site while action `13694` was pending/unattempted, order `12276` was `wc-pending`, and subscription `11959` was active at one completed payment.
- Action `13694` completed naturally at `15:38:12Z` (`21:38:12` site), 20 seconds after its schedule. Together with invoice action `13693`, both lifecycle legs completed inside 90 seconds and log `action started via WP Cron` / `action complete via WP Cron`; neither was forced.
- Exact cycle-2 order `12276` is `wc-completed`, USD `10.00`, Stripe transaction `ch_3U0NwGJG5OzSNVs21njzrcBc`, with absent `_created_via`, exact bidirectional subscription metas, scheduled date `2026-08-03 12:39:05Z`, and one product `11927` line at quantity `1`, subtotal/total `10`, tax `0`.
- Subscription `11959` remains active, advanced `_completed_payments` `1 -> 2`, cleared its pending-order link, set `_last_payment_date=2026-08-03 15:38:10Z`, and advanced `_next_payment_date` exactly 24 hours to `2026-08-04 12:39:05Z`. Replacement actions are invoice `14156` at `09:37:52Z` and charge `14157` at `15:37:52Z` on D2.
- Exactly two messages followed `REN1_PRE`: admin new order `2CEu1sN30Jpdh12kC2t75l` and customer payment-success `28jnpTDTOBQs0wXw7mWbNz`. No invoice, reminder, failure, duplicate signup, or customer WC processing/completed mail appeared. Pre-baseline order `12348` / subscription `7809` mail was non-SLT background and excluded.
- Browser evidence: `/home/server-manager/slt-evidence/SLT-REN-01-03-renewal-order-D1.png`, `/home/server-manager/slt-evidence/SLT-REN-01-04-actions-D1.png`; full facts: `/home/server-manager/slt-evidence/SLT-REN-01-D01-facts.txt`. The task-keyed browser session was closed.
