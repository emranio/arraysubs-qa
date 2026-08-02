---
id: 82
title: 'Payment failed: one customer + one admin email per retry attempt, and whether the attempt number is visible'
status: todo
priority: critical
created: 2026-08-02T03:43:10.05742927+02:00
updated: 2026-08-02T03:43:20.946122474+02:00
tags:
    - email
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 1h30m
depends_on:
    - 23
    - 12
class: standard
---

> **SLT-EML-04** · group `emails` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · impossible-timing / cross-group date contradiction** — with `SLT-DUN-01`, `SLT-DUN-02`, `SLT-DUN-03`, `SLT-DUN-04`, `SLT-DUN-05`, `SLT-EML-14`

- *Problem:* SLT-DUN-01 is tagged d0 (buy SLT Retry Daily as slt-fail on 2026-08-02, D=08-03, hold 08-04, cancel 08-07). Four other tasks encode the opposite timeline as fact: SLT-EML-04 ('bought on D2 (2026-08-04 PM) ... D = 2026-08-05 PM ... attempts 08-05/06/07/08 -> watch D4..D7 ... on-hold 08-06 ... cancelled 08-09'), SLT-EML-14 ('Retry Daily fails 08-05 PM -> on-hold 08-06 -> cancelled 08-09'), SLT-ADM-09 ('bought D2 by slt-fail ... renewal failed D3 PM'), and SLT-MYA-05 ('Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as slt-fail with card 0341 that afternoon and the grant fires only on that activation'). slt-fail + SLT Retry Daily cannot be bought twice (auto-migrate), so exactly one timeline can exist. Additionally MYA-05's pro_member role-mapping rule MUST be written before the checkout - if DUN-01 runs on D0 the role grant never fires and MYA-05 is unrunnable.
- *Required fix:* DUN-01 moves to D2 (2026-08-04), checkout 13:00-14:00 site - which is what four downstream tasks already assume and what the audit's corrected calendar says. Resulting ladder, all fixed: D=08-05 13:00-14:00; failure at D+k (08-05 13:00-20:00, watch D4); on-hold at the first hourly sweep after D+24h = 08-06 ~14:00 (watch D5); retries at +24h/+48h/+72h = 08-06/07/08 (watch D5/D6/D7); 4th charge hits the cap 08-08; cancellation at max(D+96h, on_hold+72h) = 08-09 ~14:00-16:00 (watch D8). Re-day the group: DUN-01 D2, DUN-03 D4, DUN-02 D5 (with reads on D4 and D6), DUN-04 D7, DUN-05 D7 after 16:00 (S2 bought 08-09 16:30, fails 08-10 PM, recovered on the morning of 08-11 before N+24h). MYA-05 stays D2 morning, strictly before 13:00.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Run the real Stripe dunning ladder on SLT Retry Daily and prove each of the four charge attempts emits exactly one customer `payment_failed` and one `admin_payment_failed` with a working Pay Now link, and determine whether the attempt number is visible. Correlate each mail to its attempt via `_payment_retry_attempts` and the subscription notes.

## Scope
- Gateway: Stripe test
- Checkout: N/A — subscription already exists
- Account: existing (`slt-fail`)
- Plugins: both

## Preconditions
- SLT Retry Daily bought by `slt-fail` on D2 (2026-08-04 PM) with card `4000 0000 0000 0341` → `SUB_FAIL`, $13.00/day, parent order paid.
- SLT-REF-03: Stripe fixes `max_attempts=3, interval_seconds=86400`; retries are NOT spread (`time()+86400`). Grace: on-hold 1 day, cancel 3 days. No dedupe on failure mail — four identical-subject customer mails is expected.
- Customer and admin subjects are IDENTICAL; distinguish by the To: header (admin = `emails.admin_email` / `get_option('admin_email')`). No global setting is changed.

## Test data
| Item | Value |
|---|---|
| Subscription | `SUB_FAIL`, SLT Retry Daily, $13.00/day |
| `D` / k | `_next_payment_date` = 2026-08-05 PM / `crc32('arraysubs-spread-'.SUB_FAIL) % 21600` |
| Attempts 0–3 | `D+k` (08-05 PM), `+24h` (08-06), `+48h` (08-07), `+72h` (08-08) → watch D4…D7; then "reached retry limit" |
| On-hold / cancelled | first hourly sweep after `D+24h` → 08-06; ≈ `D+96h` and ≥ on_hold+72h → 08-09 |

## Steps
1. Read `_next_payment_date`; compute k and the four attempt windows; write them to evidence before anything fires.
2. Open `.../wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=SUB_FAIL` as `--session admin`; screenshot the pending `arraysubs_process_renewal` leg.
3. `PREV0=$(mailpit-agent latest-id)`; `mailpit-agent wait-new "$PREV0" 5400 "Payment failed for subscription #SUB_FAIL"`; then `list 50`, record BOTH ids with To: headers, `show` each, extract the Pay Now / View Subscription URLs.
4. `wp post meta list SUB_FAIL --keys=_payment_retry_attempts,_payment_retry_next_attempt_at,_last_payment_failure_reason,_last_payment_failure_category,_next_payment_date --allow-root`.
5. Open the `SUB_FAIL` edit screen; screenshot the `retry_scheduled` note and its next attempt time; confirm the renewal order is `failed` and the same order id is reused.
6. Open the customer Pay Now URL in `--session customer-eml04` logged in as `slt-fail`; assert the order-pay page loads with total $13.00. **Do not pay** — the ladder must continue.
7. Follow-ups (repeat steps 3–5, snapshotting `latest-id` first): 08-06 attempt 1, 08-07 attempt 2, 08-08 attempt 3.
8. On 08-06 assert the `subscription_on_hold` mail and status `arraysubs-on-hold`; on 08-09 assert `subscription_cancelled` + `admin_subscription_cancelled`, status `arraysubs-cancelled`, `_cancellation_reason = overdue_payment`.
9. After attempt 3 confirm NO fifth pair and a note containing "reached retry limit".

## Expected results
1. Four pairs: 4 customer mails to `slt-fail@example.test` and 4 admin mails, all subject `[<site title>] Payment failed for subscription #SUB_FAIL`, each pair within 5 min of its computed window, gaps 24h ±5 min (unspread).
2. `_payment_retry_attempts` reads 1, 2, 3, 3 after attempts 0–3; `_payment_retry_next_attempt_at` matches the next window and is stale after attempt 3.
3. **Attempt number is NOT rendered** — neither `customer-payment-failed.php` nor `admin-payment-failed.php` has a counter. File `issues/SLT-EML-04-no-attempt-number.md` (medium: four indistinguishable emails); prove attempt identity via mailpit timestamp + `_payment_retry_attempts` + the note.
4. Customer body: "the renewal payment for your subscription #SUB_FAIL could not be completed", Product `SLT Retry Daily`, Amount Due `$13.00`, a Status row, a Pay Now button whose href is the order-pay URL of the SAME failed order every time, and a "Manage your subscription" link.
5. Admin body: "The automatic renewal payment for subscription #SUB_FAIL from <customer name> has failed", plus Customer/Product/Order/Amount/Status rows and a View Subscription link into wp-admin.
6. Pay Now URL returns 200 with total $13.00 and a payment form; no 404, no console error.
7. `_next_payment_date` never moves; status active through attempt 0, on-hold 08-06, cancelled 08-09. No `payment_failed` mail for any non-SLT subscription in those windows.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1-4 | `payment_failed` | each attempt, `arraysubs_gateway_payment_failed` | slt-fail@example.test | `Payment failed for subscription #SUB_FAIL` | `wait-new` |
| 5-8 | `admin_payment_failed` | same instant | admin address | same subject | To: header on `show` |
| 9 | `subscription_on_hold` | 08-06 | slt-fail@example.test | `is on hold` | `list 50` |
| 10-11 | `subscription_cancelled` + admin | 08-09 | customer + admin | `has been cancelled` | `list 50` |
| 12 | NONE EXPECTED | after attempt 3 | — | a 5th pair | exactly 8 failure messages |

## Evidence to capture
- `SLT-EML-04-01-attempt0-pair.png` … `-04-attempt3-pair.png`, `-05-retry-notes.png`, `-06-order-pay-13.png`, `-07-cancelled.png`; k and the four windows; 8+ mailpit ids with To: headers and timestamps; every `_payment_retry_*` reading; the failed order id.

## Pass criteria
- [ ] 4 customer + 4 admin failure mails, one pair per attempt, 24h apart
- [ ] Attempt-number visibility determined and, if absent, filed as an issue
- [ ] Amount Due $13.00; Pay Now resolves to the same payable failed order each time
- [ ] `_payment_retry_attempts` sequence 1,2,3,3 matches the mail sequence
- [ ] on-hold mail 08-06, cancelled mail 08-09; no 5th attempt

## Isolation / teardown
- Read-only: the failed order is deliberately left unpaid so the ladder completes. `SUB_FAIL` ends the window `arraysubs-cancelled` — the expected handoff; SLT-SETUP-99B deletes it. Close the customer session.

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
