---
id: 33
title: 'First renewal declines on SLT2 Retry Daily: order failed, subscription stays active, retry #1 queued +24h'
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
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 23
class: standard
---

> **SLT-DUN-01** · group `renewal` · scheduled **D02** (2026-08-25)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy `SLT2 Retry Daily` as `slt2-fail` on the always-declines-off-session Stripe card and prove the first unattended renewal fails correctly — order `failed`, subscription still `arraysubs-active`, retry meta set, retry #1 queued exactly 24h later (not spread), one customer + one admin payment-failed email.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt2-fail`)
- Plugins: core-owned Stripe automatic-payment/retry path

## Preconditions
- `SLT-SETUP-01/02/03` + `SLT-PROD-16` done; quote the frozen WINDOW BASELINE table on `slt2-catalog-registry` (C14).
- `slt2-fail` owns no subscription; `SLT2 Retry Daily` has no other buyer.
- Checkout MUST land **13:00-14:00 site (07:00-08:00 UTC)**, clear of the D3 SLT-SYN-04 bracket (C02). Never run `wp action-scheduler run` (C07).
- **Missing-fixture branch:** if no numeric `S_FAIL` exists, create/update the upstream QA issue, move this card to blocked, and resume after the source checkout is corrected. Do not back-date, force an action, fabricate a source, or mark this card done.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Retry Daily `/product/slt2-retry-daily/` $13 day/1 |
| Account | `slt2-fail` / `SltQa!2026#Pass` |
| Card | `4000 0000 0000 0341` `12/34` CVC `123` ZIP `12345` |
| Sessions | `cust-dun-SLT-DUN-01`, `admin-SLT-DUN-01` (unique, task-keyed) |

## Steps
0. Resolve registry alias `S_FAIL`. If it is numeric, prove its exact owner/product/parent linkage and continue. If the registry/report carries the authored no-fixture marker, save that marker plus current zero-owner/product query and zero matching pending-action proof to `/home/server-manager/slt-evidence/SLT-DUN-01-no-source.txt`; append the conditional outcome to the registry and D03 report, independently review it, move this card through `review` to `done`, require Review empty, and stop this card. The remaining numbered steps apply only to a valid D2 fixture.
1. `MP0=$(mailpit-agent latest-id)`.
2. `agent-browser --session cust-dun-SLT-DUN-01 open ".../my-account/"` → `snapshot -i` → sign in as `slt2-fail`; open `/cart/`, assert EMPTY.
3. `/product/slt2-retry-daily/` → **Add to cart** → `/checkout/` (page 8, block); summary reads $13.00 every day.
4. Pick **Credit Card (Stripe)**, enter the card, TICK **Save payment information to my account**.
5. **Place Order** in the window. Wait for order-received, record order **O0**, and capture `SLT-DUN-01-01-order.png`. Read `LINK_JSON=$(wp post meta get "$O0" _subscription_ids --format=json --allow-root)`, resolve the sole numeric subscription through a strict `jq -e` guard, and cross-check parent/customer/product plus the subscription-count delta; never use `WC_Order::get_meta('_subscription_ids')` or recency. Publish that exact ID under canonical registry alias **`S_FAIL`**, assign it to shell variable `S`, abort unless `[[ "$S" =~ ^[0-9]+$ ]]`, and never use the literal letter `S` as a command argument. In the same session reopen `/cart/`, prove it is EMPTY and the persistent-cart user meta is empty, and capture `SLT-DUN-01-01a-cart-empty.png`.
6. Dump **M** = `wp post meta list "$S" --allow-root | rg '_next_payment_date|_completed_payments|_payment_retry|_last_payment_failure'` → record **D**.
7. `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));echo $h%21600;' "$S"` → **k** (seconds, 0-21600).
8. Listing **L** = `wp db query "SELECT action_id,hook,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE hook IN ('arraysubs_generate_renewal_invoice','arraysubs_process_renewal') AND status='pending' AND JSON_UNQUOTE(JSON_EXTRACT(args,'\$[0]'))='$S' ORDER BY scheduled_date_gmt,action_id;" --allow-root`.
9. `mailpit-agent wait-new "$MP0" 180 "is active"`; classify the **complete** Mailpit delta after `MP0` and require the exact four-message paid-checkout set: WC customer completed-order, WC admin new-order, customer `new_subscription`, and admin `admin_new_subscription`. Publish O0, S, D, k, both pending action IDs/times, and the latest safe attempt-0 baseline deadline (`D+k−5m`) to `slt2-catalog-registry` and the D02 watch report. Close both task-keyed sessions for the D2 leg; leave the card `in-progress`. Do not capture `DUN_ATTEMPT0_PRE` a day early.
10. **D3 (2026-08-26):** at `D+k−6h+10min`, query **Q** = orders whose `_subscription_id` = S, from HPOS `wp_wc_orders` + `wp_wc_orders_meta` (never `wp_posts`). Record renewal order **R** (`pending`). Later, at least five minutes before the exact `D+k` charge, set `MP1=$(mailpit-agent latest-id)` and publish it as **`DUN_ATTEMPT0_PRE`** with the UTC capture time in `slt2-catalog-registry`; this is the authoritative pre-attempt-0 handoff to SLT-EML-04.
11. At `D+k+10min` re-run **Q**, **M**, **L**, plus `wp post list --post_type=arraysubs_data --include="$S" --field=post_status --allow-root`. Reopen `admin-SLT-DUN-01`; capture the exact failed renewal as `SLT-DUN-01-02-failed-renewal.png`, the exact pending retry row as `SLT-DUN-01-03-retry-pending.png`, and the subscription's failure/retry notes as `SLT-DUN-01-04-notes.png`.
12. `mailpit-agent wait-new "$MP1" 900 "Payment failed for subscription #$S"`; inspect the **complete** delta after `MP1`, require exactly two messages whose subject names this exact subscription (one to `slt2-fail@example.test`, one to the recorded admin address), and `show` both for `To:`. After the complete delta is classified, run `DUN_RETRY1_PRE=$(mailpit-agent latest-id)` and append its exact value plus UTC capture time to `slt2-catalog-registry`; this is the authoritative pre-retry-1 handoff to SLT-DUN-02 and SLT-EML-04. Do not use a fixed `list N` window. Close only `admin-SLT-DUN-01` and `cust-dun-SLT-DUN-01`, independently review the complete D2/D3 evidence, and move this execution card through review to done while its live subscription continues into the downstream retry cards.

## Expected results
1. O0 `processing`/`completed`, total exactly `$13.00`, no tax line.
2. S `arraysubs-active`; `D` = checkout time + 1 day (same UTC clock); `_completed_payments` = 1.
3. R created `pending` at `D+k−6h` (±5 min) with `_is_renewal_order=yes`, `_renewal_cycle_number=2` (the initial payment is cycle 1), `_renewal_scheduled_date=D`; at `D+k` (±5 min) it flips to `failed`.
4. S is STILL `arraysubs-active` — retries never change subscription status (SLT-REF-03 §3).
5. `_payment_retry_attempts`=`1`; `_payment_retry_next_attempt_at` = attempt + **exactly 86400s** (SLT-REF-03 §2).
6. All three `_last_payment_failure*` set, no `_renewal_failure_resolved*`, `_next_payment_date` UNCHANGED (still `D`) — it drives the grace SQL.
7. One pending `arraysubs_process_renewal` for `[S]` at attempt+86400 (±60s), no new invoice action, plus a note `event_type = retry_scheduled`.
8. Exactly two new Mailpit messages at `D+k`, same subject, different recipients.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 0a | WC Completed order | paid parent checkout | slt2-fail@example.test | `is on its way` | complete MP0 delta; save/show exact id |
| 0b | WC New order | paid parent checkout | admin_email | `New order #<O0>` | complete MP0 delta; save/show exact id |
| 0c | `new_subscription` | paid parent checkout | slt2-fail@example.test | `subscription #<S> is active` | `mailpit-agent wait-new "$MP0" 180 "is active"` |
| 0d | `admin_new_subscription` | paid parent checkout | admin_email | `New subscription #<S>` | complete MP0 delta; save/show exact id |
| 1 | `renewal_invoice` **NONE EXPECTED** | `D+k−6h` | — | — | Suppressed for auto-renew Stripe (SLT-REF-01 §4); no `Invoice for subscription #S` |
| 2 | `payment_failed` | `D+k` | `slt2-fail@example.test` | `Payment failed for subscription #S` | complete MP1 delta, exact S + `To:` |
| 3 | `admin_payment_failed` | same tick | admin | `Payment failed for subscription #S` | same complete MP1 delta, exact S + admin `To:` |
| 4 | `subscription_on_hold` **NONE EXPECTED** | before `D + 24h` (2026-08-27 PM) | — | — | Any on-hold mail during the initial D3 failure leg fails this task |

## Evidence to capture
- Screenshots `SLT-DUN-01-01-order.png`, `-01a-cart-empty.png`, `-02-failed-renewal.png`, `-03-retry-pending.png`, `-04-notes.png`.
- IDs S, O0, R, k, `D`; all four checkout Mailpit ids; `DUN_ATTEMPT0_PRE`, `DUN_RETRY1_PRE`, both failure Mailpit ids with `To:`; M/L/Q output; console errors.

## Pass criteria
- [ ] Valid D2 fixture exists and every decline/retry assertion completed; a missing fixture leaves this card blocked
- [ ] ER1/2 O0 paid $13.00 no tax; S active; `D` = checkout +1 day
- [ ] Complete four-message paid-checkout set recorded before the D2 fixture handoff
- [ ] ER3/4 R `pending` at `D+k−6h` then `failed`; S still active
- [ ] ER5 attempts=1, next = +86400s exactly
- [ ] ER6 failure meta set, `_next_payment_date` unchanged
- [ ] ER7 one pending retry action + `retry_scheduled` note
- [ ] ER8 exactly 2 payment-failed emails, no invoice email
- [ ] Same-session cart and persistent-cart meta empty after the parent checkout

## Isolation / teardown
- Hands canonical `S_FAIL` plus R, k, D, `DUN_ATTEMPT0_PRE`, `DUN_RETRY1_PRE`, and the matched MP ids to SLT-DUN-02/03/04 and SLT-EML-04. Do not pay, retry, cancel or edit `S_FAIL` — the ladder runs untouched through terminal cancellation on 2026-08-30.
- If the fixture is missing, hand downstream tasks the issue ID and blocked state; they remain blocked and must not manufacture a replacement.
- `slt2-fail` locked here until SLT-DUN-04. Nothing global changed. Close only `admin-SLT-DUN-01` and `cust-dun-SLT-DUN-01` after each dated leg; reopen by the same names when needed.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
