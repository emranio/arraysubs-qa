---
id: 33
title: 'First renewal declines on SLT Retry Daily: order failed, subscription stays active, retry #1 queued +24h'
status: todo
priority: critical
created: 2026-08-02T03:43:05.808257905+02:00
updated: 2026-08-02T03:43:16.11767534+02:00
tags:
    - renewal
    - day-02
    - has-conflicts
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

### ⚠ Conflict resolutions that apply to this task

**`critical` · impossible-timing / cross-group date contradiction** — with `SLT-DUN-02`, `SLT-DUN-03`, `SLT-DUN-04`, `SLT-DUN-05`, `SLT-EML-04`, `SLT-EML-14`

- *Problem:* SLT-DUN-01 is tagged d0 (buy SLT Retry Daily as slt-fail on 2026-08-02, D=08-03, hold 08-04, cancel 08-07). Four other tasks encode the opposite timeline as fact: SLT-EML-04 ('bought on D2 (2026-08-04 PM) ... D = 2026-08-05 PM ... attempts 08-05/06/07/08 -> watch D4..D7 ... on-hold 08-06 ... cancelled 08-09'), SLT-EML-14 ('Retry Daily fails 08-05 PM -> on-hold 08-06 -> cancelled 08-09'), SLT-ADM-09 ('bought D2 by slt-fail ... renewal failed D3 PM'), and SLT-MYA-05 ('Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as slt-fail with card 0341 that afternoon and the grant fires only on that activation'). slt-fail + SLT Retry Daily cannot be bought twice (auto-migrate), so exactly one timeline can exist. Additionally MYA-05's pro_member role-mapping rule MUST be written before the checkout - if DUN-01 runs on D0 the role grant never fires and MYA-05 is unrunnable.
- *Required fix:* DUN-01 moves to D2 (2026-08-04), checkout 13:00-14:00 site - which is what four downstream tasks already assume and what the audit's corrected calendar says. Resulting ladder, all fixed: D=08-05 13:00-14:00; failure at D+k (08-05 13:00-20:00, watch D4); on-hold at the first hourly sweep after D+24h = 08-06 ~14:00 (watch D5); retries at +24h/+48h/+72h = 08-06/07/08 (watch D5/D6/D7); 4th charge hits the cap 08-08; cancellation at max(D+96h, on_hold+72h) = 08-09 ~14:00-16:00 (watch D8). Re-day the group: DUN-01 D2, DUN-03 D4, DUN-02 D5 (with reads on D4 and D6), DUN-04 D7, DUN-05 D7 after 16:00 (S2 bought 08-09 16:30, fails 08-10 PM, recovered on the morning of 08-11 before N+24h). MYA-05 stays D2 morning, strictly before 13:00.

---
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

## Test data
| Item | Value |
|---|---|
| Product | SLT Retry Daily `/product/slt-retry-daily/` $13 day/1 |
| Account | `slt-fail` / `SltQa!2026#Pass` |
| Card | `4000 0000 0000 0341` `12/34` CVC `123` ZIP `12345` |
| Session | `cust-dun` (unique, C09) |

## Steps
1. `mailpit-agent latest-id` → **MP0**.
2. `agent-browser --session cust-dun open ".../my-account/"` → `snapshot -i` → sign in as `slt-fail`; open `/cart/`, assert EMPTY.
3. `/product/slt-retry-daily/` → **Add to cart** → `/checkout/` (page 8, block); summary reads $13.00 every day.
4. Pick **Credit Card (Stripe)**, enter the card, TICK **Save payment information to my account**.
5. **Place Order** in the window. Record order **O0**, subscription **S**.
6. Dump **M** = `wp post meta list S --allow-root | grep -E '_next_payment_date|_completed_payments|_payment_retry|_last_payment_failure'` → record **D**.
7. `php -r 'echo ((int)sprintf("%u",crc32("arraysubs-spread-".S)))%21600;'` → **k** (seconds, 0-21600).
8. Listing **L** = `wp action-scheduler list --hook=<h> --status=pending --fields=ID,args,scheduled_date_gmt --allow-root | grep "\[S\]"` for hooks `arraysubs_generate_renewal_invoice` and `arraysubs_process_renewal`.
9. `mailpit-agent wait-new MP0 180 "is active"`; then `latest-id` → **MP1**.
10. **D1 (2026-08-03) at `D+k−6h+10min`:** query **Q** = orders whose `_subscription_id` = S, from HPOS `wp_wc_orders` + `wp_wc_orders_meta` (never `wp_posts`). Record renewal order **R** (`pending`).
11. At `D+k+10min` re-run **Q**, **M**, **L**, plus `wp post list --post_type=arraysubs_data --include=S --field=post_status --allow-root`.
12. `mailpit-agent wait-new MP1 900 "Payment failed for subscription #S"`; `list 10`; `show` both matches for `To:`.

## Expected results
1. O0 `processing`/`completed`, total exactly `$13.00`, no tax line.
2. S `arraysubs-active`; `D` = checkout time + 1 day (same UTC clock); `_completed_payments` = 1.
3. R created `pending` at `D+k−6h` (±5 min) with `_is_renewal_order=yes`, `_renewal_cycle_number=1`, `_renewal_scheduled_date=D`; at `D+k` (±5 min) it flips to `failed`.
4. S is STILL `arraysubs-active` — retries never change subscription status (SLT-REF-03 §3).
5. `_payment_retry_attempts`=`1`; `_payment_retry_next_attempt_at` = attempt + **exactly 86400s** (SLT-REF-03 §2).
6. All three `_last_payment_failure*` set, no `_renewal_failure_resolved*`, `_next_payment_date` UNCHANGED (still `D`) — it drives the grace SQL.
7. One pending `arraysubs_process_renewal` for `[S]` at attempt+86400 (±60s), no new invoice action, plus a note `event_type = retry_scheduled`.
8. Exactly two new Mailpit messages at `D+k`, same subject, different recipients.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `renewal_invoice` **NONE EXPECTED** | `D+k−6h` | — | — | Suppressed for auto-renew Stripe (SLT-REF-01 §4); no `Invoice for subscription #S` |
| 2 | `payment_failed` | `D+k` | `slt-fail@example.test` | `Payment failed for subscription #S` | `wait-new MP1 900`, check `To:` |
| 3 | `admin_payment_failed` | same tick | admin | `Payment failed for subscription #S` | `list 10`, admin `To:` |
| 4 | `subscription_on_hold` **NONE EXPECTED** | before 2026-08-04 | — | — | Any `is on hold` mail on D0/D1 fails the task |

## Evidence to capture
- Screenshots `SLT-DUN-01-01-order`, `-02-failed-renewal`, `-03-retry-pending`, `-04-notes`.
- IDs S, O0, R, k, `D`; both Mailpit ids with `To:`; M/L/Q output; console errors.

## Pass criteria
- [ ] ER1/2 O0 paid $13.00 no tax; S active; `D` = checkout +1 day
- [ ] ER3/4 R `pending` at `D+k−6h` then `failed`; S still active
- [ ] ER5 attempts=1, next = +86400s exactly
- [ ] ER6 failure meta set, `_next_payment_date` unchanged
- [ ] ER7 one pending retry action + `retry_scheduled` note
- [ ] ER8 exactly 2 payment-failed emails, no invoice email

## Isolation / teardown
- Hands S, R, k, D, MP ids to SLT-DUN-02/03/04. Do not pay, retry, cancel or edit S — the ladder runs untouched to 2026-08-07.
- `slt-fail` locked here until SLT-DUN-04. Nothing global changed. `agent-browser close --session cust-dun` when done.

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
