---
id: 82
title: 'Payment failed: one customer + one admin email per retry attempt, and whether the attempt number is visible'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - day-05
due: "2026-08-28"
estimate: 1h30m
depends_on:
    - 23
    - 12
    - 33
class: standard
---

> **SLT-EML-04** · group `emails` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Run the real Stripe dunning ladder on SLT2 Retry Daily and prove each of the four charge attempts emits exactly one customer `payment_failed` and one `admin_payment_failed` with a working Pay Now link, and determine whether the attempt number is visible. Correlate each mail to its attempt via `_payment_retry_attempts` and the subscription notes.

## Scope
- Gateway: Stripe test
- Checkout: N/A — subscription already exists
- Account: existing (`slt2-fail`)
- Plugins: both

## Preconditions
- SLT2 Retry Daily bought by `slt2-fail` on D2 (2026-08-25 PM) with card `4000 0000 0000 0341` → canonical registry alias `S_FAIL`, $13.00/day, parent order paid.
- SLT-REF-03: Stripe fixes `max_attempts=3, interval_seconds=86400`; retries are NOT spread (`time()+86400`). Grace: on-hold 1 day, cancel 3 days. No dedupe on failure mail — four identical-subject customer mails is expected.
- Customer and admin subjects are IDENTICAL; distinguish by the To: header (admin = `emails.admin_email` / `get_option('admin_email')`). No global setting is changed.

## Test data
| Item | Value |
|---|---|
| Subscription | `S_FAIL`, SLT2 Retry Daily, $13.00/day |
| `D` / k | `_next_payment_date` = 2026-08-26 PM / `crc32('arraysubs-spread-'.S_FAIL) % 21600` |
| Attempts 0–3 | `D+k` (08-26 PM), `+24h` (08-27), `+48h` (08-28), `+72h` (08-29) → watch D4…D7; then "reached retry limit" |
| On-hold / cancelled | first hourly sweep after `D+24h` → 08-27; ≈ `D+96h` and ≥ on_hold+72h → 08-30 |

## Steps
1. Resolve registry alias `S_FAIL` into shell variable `S_FAIL`, abort unless `[[ "$S_FAIL" =~ ^[0-9]+$ ]]`, then read `_next_payment_date`; compute k from that numeric ID and write the four attempt windows to evidence before anything fires.
2. In `admin-SLT-EML-04-D5`, open `.../wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=$S_FAIL`; capture the exact numeric `arraysubs_process_renewal` row as `SLT-EML-04-00-pending.png` and require its indexed args/action ID/gate match the registry.
3. **D5 start:** load the registered `DUN_ATTEMPT0_PRE`, `DUN_RETRY1_PRE`, and `DUN_RETRY2_PRE` values/timestamps. Treat them as immutable task-owned boundaries: attempt 0 is the complete delta `[DUN_ATTEMPT0_PRE, DUN_RETRY1_PRE)`, and retry 1 is `[DUN_RETRY1_PRE, DUN_RETRY2_PRE)`. In each bounded delta require exactly two messages whose subject names exact S_FAIL, one to `slt2-fail@example.test` and one to the recorded admin address. `show` all four, render/capture the exact pairs as `SLT-EML-04-01-attempt0-pair.png` and `-02-attempt1-pair.png`, extract Pay Now / View Subscription URLs, and classify unrelated shared-site mail rather than using `list 50`.
4. `wp post meta list "$S_FAIL" --keys=_payment_retry_attempts,_payment_retry_next_attempt_at,_last_payment_failure_reason,_last_payment_failure_category,_next_payment_date --allow-root`.
5. Open numeric `$S_FAIL` through the ArraySubs subscriptions app; capture the exact attempt-owned notes as `SLT-EML-04-05-retry-notes.png`, confirm the relationship-owned renewal order is failed, and prove every message names that same order. Do not use a legacy `post.php` subscription route or a newest-note assumption.
6. For each attempt, open its customer Pay Now URL in a cycle-keyed `customer-eml04-SLT-EML-04-A<n>` session logged in as `slt2-fail`; capture the safe unpopulated $13 order-pay page as `SLT-EML-04-06-order-pay-13-A<n>.png`, assert all URLs resolve to the same numeric failed order, do not enter payment data, and close that session immediately.
7. Close only `admin-SLT-EML-04-D5` after its reads. **D6 before retry 3:** once SLT-DUN-02 captures `DUN_RETRY3_PRE` in the final five minutes, open `admin-SLT-EML-04-D6`, classify retry 2's bounded delta `[DUN_RETRY2_PRE, DUN_RETRY3_PRE)` exactly as step 3, capture `SLT-EML-04-03-attempt2-pair.png`, then repeat steps 4–6.
8. **D6 retry 3:** consume that unchanged `DUN_RETRY3_PRE`; poll with repeated `mailpit-agent wait-new ... 60` calls only, never a 3600/1800-second blocking wait, through the 10-minute post-gate cutoff. Classify the complete delta, require and capture the exact pair as `SLT-EML-04-04-attempt3-pair.png`, repeat steps 4–6, and select the "reached retry limit" note by exact pre/post ID difference. After classification publish `DUN_FAILURES_DONE_PRE=$(mailpit-agent latest-id)` with UTC capture time and close `admin-SLT-EML-04-D6`.
9. **D7 cancellation:** consume exact `DUN_CANCEL_PRE` and natural sweep/action gate published by SLT-DUN-04; inspect the complete delta and require the exact customer/admin cancelled pair, status, and reason. Capture `SLT-EML-04-07-cancelled.png` in `admin-SLT-EML-04-D7`, then close it. Across `DUN_ATTEMPT0_PRE` through cancellation, require exactly eight failure messages for exact `$S_FAIL` and no fifth pair.
10. Determine attempt-number visibility from all eight rendered live bodies without product-source access. If absent, create `qa/issues/` kanban card named `SLT-EML-04-no-attempt-number`; if any other assertion fails, create `qa/issues/` kanban card named `SLT-EML-04-<concise-slug>`. Every issue card must include this progress task/stage and plan path; subscription/order/action/note IDs; user ID/login/email/role and admin recipient; exact URLs/sessions/gates; reproduction; expected/actual; rendered body, Mailpit headers/timestamps, meta/note/UI/screenshot proof; and another attempt as counterexample. create or update the mandatory `qa/issues/` kanban card. Continue unaffected reads. After D7, independently review all evidence, close only remaining exact task sessions, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Four pairs: 4 customer mails to `slt2-fail@example.test` and 4 admin mails, all subject `[<site title>] Payment failed for subscription #S_FAIL`, each pair within 5 min of its computed window, gaps 24h ±5 min (unspread).
2. `_payment_retry_attempts` reads 1, 2, 3, 3 after attempts 0–3; `_payment_retry_next_attempt_at` matches the next window and is stale after attempt 3.
3. Determine attempt-number visibility from the four **live rendered customer and admin bodies**, without opening product source. If the attempt number is absent, file `qa/issues/` kanban card named `SLT-EML-04-no-attempt-number` as the observed product finding (medium: four indistinguishable emails) and prove attempt identity via Mailpit timestamp + `_payment_retry_attempts` + the note. If it is rendered, record the exact text and do not create that issue.
4. Customer body: "the renewal payment for your subscription #S_FAIL could not be completed", Product `SLT2 Retry Daily`, Amount Due `$13.00`, a Status row, a Pay Now button whose href is the order-pay URL of the SAME failed order every time, and a "Manage your subscription" link.
5. Admin body: "The automatic renewal payment for subscription #S_FAIL from <customer name> has failed", plus Customer/Product/Order/Amount/Status rows and a View Subscription link into wp-admin.
6. Pay Now URL returns 200 with total $13.00 and a payment form; no 404, no console error.
7. `_next_payment_date` never moves; status active through attempt 0, on-hold 08-27, cancelled 08-30. No `payment_failed` mail for any non-SLT2 subscription in those windows.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1-4 | `payment_failed` | each attempt, `arraysubs_gateway_payment_failed` | slt2-fail@example.test | `Payment failed for subscription #S_FAIL` | four task-owned bounded/full deltas; exact S_FAIL + customer `To:` |
| 5-8 | `admin_payment_failed` | same instant | admin address | same subject | same deltas; exact S_FAIL + admin `To:` |
| 9 | `subscription_on_hold` | 08-27 | slt2-fail@example.test | `is on hold` | registered ladder delta; exact S_FAIL + customer `To:` |
| 10-11 | `subscription_cancelled` + admin | 08-30 | customer + admin | `has been cancelled` | complete delta after `DUN_CANCEL_PRE` |
| 12 | NONE EXPECTED | after attempt 3 | — | a 5th pair | exactly 8 failure messages |

## Evidence to capture
- `SLT-EML-04-00` through `-07`, including per-attempt pair and safe cycle-keyed pay-page captures; k/windows; exact subscription/order/action/note/user IDs; all registered dunning baselines; 8+ Mailpit IDs with To/headers/timestamps and rendered bodies; every retry-meta read; sessions/review proof.

## Pass criteria
- [ ] 4 customer + 4 admin failure mails, one pair per attempt, 24h apart
- [ ] Attempt-number visibility determined and, if absent, filed as an issue
- [ ] Amount Due $13.00; Pay Now resolves to the same payable failed order each time
- [ ] `_payment_retry_attempts` sequence 1,2,3,3 matches the mail sequence
- [ ] on-hold mail 08-27, cancelled mail 08-30; no 5th attempt
- [ ] Polls were ≤60 seconds, every phase session closed, QA issue cards only under `qa/issues/`, and final evidence reviewed to done

## Isolation / teardown
- Read-only: the failed order is deliberately left unpaid so the ladder completes. Keep this card in progress through the D7 cancellation read; only then review it through done. `S_FAIL` ends `arraysubs-cancelled`; SLT-SETUP-99B deletes it. Close only the cycle-keyed task admin/customer sessions at each phase.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
