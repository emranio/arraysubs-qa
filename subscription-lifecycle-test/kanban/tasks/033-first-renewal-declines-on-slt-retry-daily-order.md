---
id: 33
title: 'First renewal declines on SLT Retry Daily: order failed, subscription stays active, retry #1 queued +24h'
status: done
priority: critical
created: 2026-08-02T03:43:05.808257905+02:00
updated: 2026-08-05T14:07:15.756047721+02:00
started: 2026-08-05T14:07:15.232722056+02:00
completed: 2026-08-05T14:07:15.232722056+02:00
tags:
    - renewal
    - day-02
due: "2026-08-04"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 23
class: standard
---

> **SLT-DUN-01** · group `renewal` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy `SLT Retry Daily` as `slt-fail` on the always-declines-off-session Stripe card and prove the first unattended renewal fails correctly — order `failed`, subscription still `arraysubs-active`, retry meta set, retry #1 queued exactly 24h later (not spread), one customer + one admin payment-failed email.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-fail`)
- Plugins: pro-required (StripeDelegate publishes the retry config)

## Preconditions
- `SLT-SETUP-01/02/03` + `SLT-PROD-16` done; quote the frozen WINDOW BASELINE table on `slt-catalog-registry` (C14).
- `slt-fail` owns no subscription; `SLT Retry Daily` has no other buyer.
- Checkout MUST land **13:00-14:00 site (07:00-08:00 UTC)**, clear of the D3 SLT-SYN-04 bracket (C02). Never run `wp action-scheduler run` (C07).
- **Missed-fixture branch:** before opening a browser, read the D02/D03 watch reports and registry. If the authored D2 checkout window closed without a numeric `S_FAIL`, do not create a late substitute, back-date anything, or force an action. Record the exact no-fixture marker and source paths, mark the checkout/attempt assertions `UNVERIFIED (no source fixture)`, publish the same outcome for downstream consumers, and close this execution card through review to done.

## Test data
| Item | Value |
|---|---|
| Product | SLT Retry Daily `/product/slt-retry-daily/` $13 day/1 |
| Account | `slt-fail` / `SltQa!2026#Pass` |
| Card | `4000 0000 0000 0341` `12/34` CVC `123` ZIP `12345` |
| Sessions | `cust-dun-SLT-DUN-01`, `admin-SLT-DUN-01` (unique, task-keyed) |

## Steps
0. Resolve registry alias `S_FAIL`. If it is numeric, prove its exact owner/product/parent linkage and continue. If the registry/report carries the authored no-fixture marker, save that marker plus current zero-owner/product query and zero matching pending-action proof to `/home/server-manager/slt-evidence/SLT-DUN-01-no-source.txt`; append the conditional outcome to the registry and D03 report, independently review it, move this card through `review` to `done`, require Review empty, and stop this card. The remaining numbered steps apply only to a valid D2 fixture.
1. `MP0=$(mailpit-agent latest-id)`.
2. `agent-browser --session cust-dun-SLT-DUN-01 open ".../my-account/"` → `snapshot -i` → sign in as `slt-fail`; open `/cart/`, assert EMPTY.
3. `/product/slt-retry-daily/` → **Add to cart** → `/checkout/` (page 8, block); summary reads $13.00 every day.
4. Pick **Credit Card (Stripe)**, enter the card, TICK **Save payment information to my account**.
5. **Place Order** in the window. Wait for order-received, record order **O0**, and capture `SLT-DUN-01-01-order.png`. Read `LINK_JSON=$(wp post meta get "$O0" _subscription_ids --format=json --allow-root)`, resolve the sole numeric subscription through a strict `jq -e` guard, and cross-check parent/customer/product plus the subscription-count delta; never use `WC_Order::get_meta('_subscription_ids')` or recency. Publish that exact ID under canonical registry alias **`S_FAIL`**, assign it to shell variable `S`, abort unless `[[ "$S" =~ ^[0-9]+$ ]]`, and never use the literal letter `S` as a command argument. In the same session reopen `/cart/`, prove it is EMPTY and the persistent-cart user meta is empty, and capture `SLT-DUN-01-01a-cart-empty.png`.
6. Dump **M** = `wp post meta list "$S" --allow-root | rg '_next_payment_date|_completed_payments|_payment_retry|_last_payment_failure'` → record **D**.
7. `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));echo $h%21600;' "$S"` → **k** (seconds, 0-21600).
8. Listing **L** = `wp db query "SELECT action_id,hook,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE hook IN ('arraysubs_generate_renewal_invoice','arraysubs_process_renewal') AND status='pending' AND JSON_UNQUOTE(JSON_EXTRACT(args,'\$[0]'))='$S' ORDER BY scheduled_date_gmt,action_id;" --allow-root`.
9. `mailpit-agent wait-new "$MP0" 180 "is active"`; classify the **complete** Mailpit delta after `MP0` and require the exact four-message paid-checkout set: WC customer completed-order, WC admin new-order, customer `new_subscription`, and admin `admin_new_subscription`. Publish O0, S, D, k, both pending action IDs/times, and the latest safe attempt-0 baseline deadline (`D+k−5m`) to `slt-catalog-registry` and the D02 watch report. Close both task-keyed sessions for the D2 leg; leave the card `in-progress`. Do not capture `DUN_ATTEMPT0_PRE` a day early.
10. **D3 (2026-08-05):** at `D+k−6h+10min`, query **Q** = orders whose `_subscription_id` = S, from HPOS `wp_wc_orders` + `wp_wc_orders_meta` (never `wp_posts`). Record renewal order **R** (`pending`). Later, at least five minutes before the exact `D+k` charge, set `MP1=$(mailpit-agent latest-id)` and publish it as **`DUN_ATTEMPT0_PRE`** with the UTC capture time in `slt-catalog-registry`; this is the authoritative pre-attempt-0 handoff to SLT-EML-04.
11. At `D+k+10min` re-run **Q**, **M**, **L**, plus `wp post list --post_type=arraysubs_data --include="$S" --field=post_status --allow-root`. Reopen `admin-SLT-DUN-01`; capture the exact failed renewal as `SLT-DUN-01-02-failed-renewal.png`, the exact pending retry row as `SLT-DUN-01-03-retry-pending.png`, and the subscription's failure/retry notes as `SLT-DUN-01-04-notes.png`.
12. `mailpit-agent wait-new "$MP1" 900 "Payment failed for subscription #$S"`; inspect the **complete** delta after `MP1`, require exactly two messages whose subject names this exact subscription (one to `slt-fail@example.test`, one to the recorded admin address), and `show` both for `To:`. After the complete delta is classified, run `DUN_RETRY1_PRE=$(mailpit-agent latest-id)` and append its exact value plus UTC capture time to `slt-catalog-registry`; this is the authoritative pre-retry-1 handoff to SLT-DUN-02 and SLT-EML-04. Do not use a fixed `list N` window. Close only `admin-SLT-DUN-01` and `cust-dun-SLT-DUN-01`, independently review the complete D2/D3 evidence, and move this execution card through review to done while its live subscription continues into the downstream retry cards.

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
| 0a | WC Completed order | paid parent checkout | slt-fail@example.test | `is on its way` | complete MP0 delta; save/show exact id |
| 0b | WC New order | paid parent checkout | admin_email | `New order #<O0>` | complete MP0 delta; save/show exact id |
| 0c | `new_subscription` | paid parent checkout | slt-fail@example.test | `subscription #<S> is active` | `mailpit-agent wait-new "$MP0" 180 "is active"` |
| 0d | `admin_new_subscription` | paid parent checkout | admin_email | `New subscription #<S>` | complete MP0 delta; save/show exact id |
| 1 | `renewal_invoice` **NONE EXPECTED** | `D+k−6h` | — | — | Suppressed for auto-renew Stripe (SLT-REF-01 §4); no `Invoice for subscription #S` |
| 2 | `payment_failed` | `D+k` | `slt-fail@example.test` | `Payment failed for subscription #S` | complete MP1 delta, exact S + `To:` |
| 3 | `admin_payment_failed` | same tick | admin | `Payment failed for subscription #S` | same complete MP1 delta, exact S + admin `To:` |
| 4 | `subscription_on_hold` **NONE EXPECTED** | before `D + 24h` (2026-08-06 PM) | — | — | Any on-hold mail during the initial D3 failure leg fails this task |

## Evidence to capture
- Screenshots `SLT-DUN-01-01-order.png`, `-01a-cart-empty.png`, `-02-failed-renewal.png`, `-03-retry-pending.png`, `-04-notes.png`.
- IDs S, O0, R, k, `D`; all four checkout Mailpit ids; `DUN_ATTEMPT0_PRE`, `DUN_RETRY1_PRE`, both failure Mailpit ids with `To:`; M/L/Q output; console errors.

## Pass criteria
- [ ] Valid D2 fixture branch completed, or the missed-fixture branch closed explicitly as `UNVERIFIED (no source fixture)` with zero fabricated checkout/action/mail
- [ ] ER1/2 O0 paid $13.00 no tax; S active; `D` = checkout +1 day
- [ ] Complete four-message paid-checkout set recorded before the D2 fixture handoff
- [ ] ER3/4 R `pending` at `D+k−6h` then `failed`; S still active
- [ ] ER5 attempts=1, next = +86400s exactly
- [ ] ER6 failure meta set, `_next_payment_date` unchanged
- [ ] ER7 one pending retry action + `retry_scheduled` note
- [ ] ER8 exactly 2 payment-failed emails, no invoice email
- [ ] Same-session cart and persistent-cart meta empty after the parent checkout

## Isolation / teardown
- Hands canonical `S_FAIL` plus R, k, D, `DUN_ATTEMPT0_PRE`, `DUN_RETRY1_PRE`, and the matched MP ids to SLT-DUN-02/03/04 and SLT-EML-04. Do not pay, retry, cancel or edit `S_FAIL` — the ladder runs untouched through terminal cancellation on 2026-08-09.
- In the missed-fixture branch, hand downstream tasks the immutable `S_FAIL unavailable` marker instead; they must close their ladder-only assertions `UNVERIFIED` and must not manufacture a replacement.
- `slt-fail` locked here until SLT-DUN-04. Nothing global changed. Close only `admin-SLT-DUN-01` and `cust-dun-SLT-DUN-01` after each dated leg; reopen by the same names when needed.

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

[[2026-08-05]] Wed 14:06
D03 missed-fixture branch completed as authored: D02 report and registry had no numeric S_FAIL; exact live query returned zero subscriptions for user 351/product 12108 and zero matching pending arraysubs actions. Published immutable S_FAIL unavailable marker to registry page 11847 at 2026-08-05 12:05:07Z; no late checkout, date/meta change, or forced action. Downstream DUN-02/03/04 and EML-04 must close ladder-only assertions UNVERIFIED without a substitute. Evidence /home/server-manager/slt-evidence/SLT-DUN-01-no-source.txt and D03 report.
