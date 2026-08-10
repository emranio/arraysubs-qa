---
id: 66
title: 'Grace phase 1: active to on-hold one day after the due date, with the customer-only on-hold email'
status: done
priority: high
created: 2026-08-02T03:43:08.902788936+02:00
updated: 2026-08-05T21:37:49.56534576+02:00
started: 2026-08-05T21:07:18.930928332+02:00
completed: 2026-08-05T21:07:18.930928332+02:00
tags:
    - renewal
    - day-04
due: "2026-08-06"
estimate: 45m
depends_on:
    - 33
class: standard
---

> **SLT-DUN-03** · group `renewal` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove grace phase 1: `grace_days_before_on_hold = 1` moves S from `arraysubs-active` to `arraysubs-on-hold` on the first hourly `arraysubs_check_overdue_renewals` sweep after `D + 24h`, writes `_on_hold_date`, sends one customer `subscription_on_hold` email and NO admin counterpart, and leaves `_next_payment_date` and the retry ladder intact.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-fail`)
- Plugins: both (sweep lives in free `RecurringBilling\Services\Hooks`)

## Preconditions
- `SLT-DUN-01` done; S, R, k, D recorded, R `failed`. The sweep only holds a subscription that already has an unpaid renewal order — with `hasUnpaidRenewalOrder()` false it creates an invoice and skips the hold that pass (SLT-REF-03 §4).
- Sweep gates: `_next_payment_date < now − 1 day` AND `now ≥ D + k + 1h`. With `k ≤ 6h` the 1-day term dominates, so expect the first hourly tick after `D + 24h`.
- There is **no** `admin_subscription_on_hold` email in the inventory (SLT-REF-04) — its absence is an assertion, not an omission.
- Never run `wp action-scheduler run` (C07). Retry #1 also fires today; the two are independent.

## Test data
| Item | Value |
|---|---|
| Subscription | S, renewal order R, offset k, due date D |
| Hold window | first hourly sweep after `D + 24h` (≈ 13:00-15:00 site) |
| Hook | `arraysubs_check_overdue_renewals` (hourly, `arraysubs_renewals`) |
| Sessions | `admin-dun3-SLT-DUN-03`, `customer-SLT-DUN-03` |

## Steps
1. Resolve registry aliases `S_FAIL`, its failed renewal order, due `D`, and retry action into numeric/dated variables; abort on any mismatch. Query the exact recurring `arraysubs_check_overdue_renewals` rows and select the first natural sweep at or after `D+24h`; publish that sweep gate and its `gate−5m` baseline deadline to the registry/D04 report. No earlier than five minutes before it, set `MPH=$(mailpit-agent latest-id)` and confirm numeric `$S` is still `arraysubs-active` with `wp post list --post_type=arraysubs_data --include="$S" --field=post_status --allow-root`.
2. Poll the exact subscription status, sweep row/log, and `mailpit-agent wait-new "$MPH" 60 "is on hold"` in intervals no longer than 60 seconds through five minutes after the selected sweep. If that sweep completes without the hold, continue the same bounded polling through five minutes after the next natural hourly sweep. Only then record the missing hold: capture evidence, create a standalone issue with the required task/plan, subscription/order/action/user/login/role, admin/customer URLs, reproduction timeline, expected/actual, status/meta/Mailpit/sweep proof and the still-pending retry counterexample, and do **not** force either sweep or add a kanban bug card.
3. On the match, re-run SLT-DUN-01's **M** (subscription meta), **Q** (HPOS orders for `_subscription_id`=S) and the post-status query.
4. Run `mailpit-agent show <matched-hold-mail-id>` and record `To:`, subject, and the body's next-payment/amount lines.
5. Inspect the complete delta after `MPH` — confirm exactly one hold message for exact numeric `$S` and NO hold message to the admin address. A payment-failed customer/admin pair for retry #1 may legitimately share this interval; classify it as SLT-DUN-02 evidence rather than failing this hold task. Do not rely on a fixed recent-message count.
6. In `admin-dun3-SLT-DUN-03`, open the real `wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions` route, search exact numeric ID `$S`, capture its **On hold** row as `SLT-DUN-03-01-admin-on-hold.png`, open **View Details**, and capture notes as `SLT-DUN-03-02-notes.png`. Do not use legacy CPT routes.
7. In `customer-SLT-DUN-03`, open `https://mirror-help.arrayhash.com/my-account/view-subscription/$S/`, capture the exact status/actions as `SLT-DUN-03-03-myaccount.png`, and do not click Retry Payment or payment-method controls.
8. Re-run listing **L** for `arraysubs_process_renewal` on numeric `[S]`, reconcile the exact hold/retry action and complete Mailpit delta, close only `admin-dun3-SLT-DUN-03` and `customer-SLT-DUN-03`, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. S is `arraysubs-on-hold` on the first hourly sweep after `D + 24h`; report the UTC timestamp and its lag from `D + 24h` (expect < 60 min).
2. `_on_hold_date` written as a UTC MySQL datetime = the transition time (±60s).
3. `_next_payment_date` is STILL `D` — the hold does not advance it, and phase 2 reads it.
4. R stays `failed` (not cancelled); **Q** still returns exactly one renewal order.
5. `_payment_retry_attempts` is untouched by the hold itself (1 before retry #1, 2 after).
6. **L** still shows one pending `arraysubs_process_renewal` for `[S]`: the ladder survives the hold.
7. Exactly ONE task-relevant hold email — `subscription_on_hold` to `slt-fail@example.test`; no admin hold mail (SLT-REF-04). Retry #1's separately owned failure pair may coexist in the same baseline delta.
8. My Account shows S **On hold** with a **Retry Payment** button and a **Manage payment methods** link (allowed in `arraysubs-on-hold`).
9. No `subscription_cancelled` mail today — cancellation belongs to D7 (2026-08-09).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `subscription_on_hold` | first sweep after `D+24h` | `slt-fail@example.test` | `Your subscription #S is on hold` | repeated same-baseline waits of at most 60 seconds through the authored two-sweep deadline; check `To:` |
| 2 | admin hold notice **NONE EXPECTED** | same tick | — | — | complete MPH delta: no admin hold message; no such class exists |
| 3 | `subscription_cancelled` **NONE EXPECTED** | D4 | — | — | No `has been cancelled` before the D7 cancellation window on 2026-08-09 |
| 4 | `payment_failed` pair | retry #1, same day | customer / admin | `Payment failed for subscription #S` | Owned by SLT-DUN-02 |

## Evidence to capture
- Screenshots `SLT-DUN-03-01-admin-on-hold.png`, `-02-notes.png`, `-03-myaccount.png`.
- Exact selected/backup sweep IDs and gates, UTC transition time, `_on_hold_date`, lag from `D+24h`; Mailpit ID/To/body; M/Q/L dumps; session/review proof.

## Pass criteria
- [ ] ER1/2 hold within 60 min of `D+24h`, `_on_hold_date` matches
- [ ] ER3 `_next_payment_date` still `D`
- [ ] ER4/5 R still `failed`; retry counter untouched by the hold
- [ ] ER6 retry action still pending
- [ ] ER7 exactly one task-relevant `is on hold` email, customer only; any retry pair in the interval is handed to SLT-DUN-02
- [ ] ER8 My Account: On hold + Retry Payment + payment-method link
- [ ] ER9 no cancellation mail on D4
- [ ] Exact sessions closed and fully evidenced execution reviewed to done

## Isolation / teardown
- Read-only. Do not retry, pay or reactivate S — `_on_hold_date` anchors the phase-2 cancel gate in `SLT-DUN-04`.
- If the hold is late or missing, record it and let the ladder run; forcing the sweep destroys SLT-DUN-04's timing proof.

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

[[2026-08-05]] Wed 21:07
UNVERIFIED (no S_FAIL source fixture) on 2026-08-05.

`SLT-DUN-01` completed its authored missed-fixture branch on 2026-08-05: registry page 11847 stores `S_FAIL unavailable`, live verification found zero subscriptions for user 351/product 12108, and the D03 watch report instructs `SLT-DUN-02/03/04` plus `SLT-EML-04` to close ladder-only assertions `UNVERIFIED` without manufacturing a substitute. With no source renewal failure, the D4 on-hold transition observed by this card cannot occur. This card closes without a replacement checkout, date mutation, or forced action.
