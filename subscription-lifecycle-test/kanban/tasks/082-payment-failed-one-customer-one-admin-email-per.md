---
id: 82
title: 'Payment failed: one customer + one admin email per retry attempt, and whether the attempt number is visible'
status: done
priority: critical
created: 2026-08-02T03:43:10.05742927+02:00
updated: 2026-08-05T21:37:49.577077455+02:00
started: 2026-08-05T21:07:20.282816341+02:00
completed: 2026-08-05T21:07:20.282816341+02:00
tags:
    - email
    - day-05
due: "2026-08-07"
estimate: 1h30m
depends_on:
    - 23
    - 12
    - 33
class: standard
---

> **SLT-EML-04** · group `emails` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Run the real Stripe dunning ladder on SLT Retry Daily and prove each of the four charge attempts emits exactly one customer `payment_failed` and one `admin_payment_failed` with a working Pay Now link, and determine whether the attempt number is visible. Correlate each mail to its attempt via `_payment_retry_attempts` and the subscription notes.

## Scope
- Gateway: Stripe test
- Checkout: N/A — subscription already exists
- Account: existing (`slt-fail`)
- Plugins: both

## Preconditions
- SLT Retry Daily bought by `slt-fail` on D2 (2026-08-04 PM) with card `4000 0000 0000 0341` → canonical registry alias `S_FAIL`, $13.00/day, parent order paid.
- SLT-REF-03: Stripe fixes `max_attempts=3, interval_seconds=86400`; retries are NOT spread (`time()+86400`). Grace: on-hold 1 day, cancel 3 days. No dedupe on failure mail — four identical-subject customer mails is expected.
- Customer and admin subjects are IDENTICAL; distinguish by the To: header (admin = `emails.admin_email` / `get_option('admin_email')`). No global setting is changed.

## Test data
| Item | Value |
|---|---|
| Subscription | `S_FAIL`, SLT Retry Daily, $13.00/day |
| `D` / k | `_next_payment_date` = 2026-08-05 PM / `crc32('arraysubs-spread-'.S_FAIL) % 21600` |
| Attempts 0–3 | `D+k` (08-05 PM), `+24h` (08-06), `+48h` (08-07), `+72h` (08-08) → watch D4…D7; then "reached retry limit" |
| On-hold / cancelled | first hourly sweep after `D+24h` → 08-06; ≈ `D+96h` and ≥ on_hold+72h → 08-09 |

## Steps
1. Resolve registry alias `S_FAIL` into shell variable `S_FAIL`, abort unless `[[ "$S_FAIL" =~ ^[0-9]+$ ]]`, then read `_next_payment_date`; compute k from that numeric ID and write the four attempt windows to evidence before anything fires.
2. In `admin-SLT-EML-04-D5`, open `.../wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=$S_FAIL`; capture the exact numeric `arraysubs_process_renewal` row as `SLT-EML-04-00-pending.png` and require its indexed args/action ID/gate match the registry.
3. **D5 start:** load the registered `DUN_ATTEMPT0_PRE`, `DUN_RETRY1_PRE`, and `DUN_RETRY2_PRE` values/timestamps. Treat them as immutable task-owned boundaries: attempt 0 is the complete delta `[DUN_ATTEMPT0_PRE, DUN_RETRY1_PRE)`, and retry 1 is `[DUN_RETRY1_PRE, DUN_RETRY2_PRE)`. In each bounded delta require exactly two messages whose subject names exact S_FAIL, one to `slt-fail@example.test` and one to the recorded admin address. `show` all four, render/capture the exact pairs as `SLT-EML-04-01-attempt0-pair.png` and `-02-attempt1-pair.png`, extract Pay Now / View Subscription URLs, and classify unrelated shared-site mail rather than using `list 50`.
4. `wp post meta list "$S_FAIL" --keys=_payment_retry_attempts,_payment_retry_next_attempt_at,_last_payment_failure_reason,_last_payment_failure_category,_next_payment_date --allow-root`.
5. Open numeric `$S_FAIL` through the ArraySubs subscriptions app; capture the exact attempt-owned notes as `SLT-EML-04-05-retry-notes.png`, confirm the relationship-owned renewal order is failed, and prove every message names that same order. Do not use a legacy `post.php` subscription route or a newest-note assumption.
6. For each attempt, open its customer Pay Now URL in a cycle-keyed `customer-eml04-SLT-EML-04-A<n>` session logged in as `slt-fail`; capture the safe unpopulated $13 order-pay page as `SLT-EML-04-06-order-pay-13-A<n>.png`, assert all URLs resolve to the same numeric failed order, do not enter payment data, and close that session immediately.
7. Close only `admin-SLT-EML-04-D5` after its reads. **D6 before retry 3:** once SLT-DUN-02 captures `DUN_RETRY3_PRE` in the final five minutes, open `admin-SLT-EML-04-D6`, classify retry 2's bounded delta `[DUN_RETRY2_PRE, DUN_RETRY3_PRE)` exactly as step 3, capture `SLT-EML-04-03-attempt2-pair.png`, then repeat steps 4–6.
8. **D6 retry 3:** consume that unchanged `DUN_RETRY3_PRE`; poll with repeated `mailpit-agent wait-new ... 60` calls only, never a 3600/1800-second blocking wait, through the 10-minute post-gate cutoff. Classify the complete delta, require and capture the exact pair as `SLT-EML-04-04-attempt3-pair.png`, repeat steps 4–6, and select the "reached retry limit" note by exact pre/post ID difference. After classification publish `DUN_FAILURES_DONE_PRE=$(mailpit-agent latest-id)` with UTC capture time and close `admin-SLT-EML-04-D6`.
9. **D7 cancellation:** consume exact `DUN_CANCEL_PRE` and natural sweep/action gate published by SLT-DUN-04; inspect the complete delta and require the exact customer/admin cancelled pair, status, and reason. Capture `SLT-EML-04-07-cancelled.png` in `admin-SLT-EML-04-D7`, then close it. Across `DUN_ATTEMPT0_PRE` through cancellation, require exactly eight failure messages for exact `$S_FAIL` and no fifth pair.
10. Determine attempt-number visibility from all eight rendered live bodies without product-source access. If absent, create `issues/SLT-EML-04-no-attempt-number.md`; if any other assertion fails, create `issues/SLT-EML-04-<concise-slug>.md`. Every standalone file must include this progress task/stage and plan path; subscription/order/action/note IDs; user ID/login/email/role and admin recipient; exact URLs/sessions/gates; reproduction; expected/actual; rendered body, Mailpit headers/timestamps, meta/note/UI/screenshot proof; and another attempt as counterexample. Never add a kanban bug card. Continue unaffected reads. After D7, independently review all evidence, close only remaining exact task sessions, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Four pairs: 4 customer mails to `slt-fail@example.test` and 4 admin mails, all subject `[<site title>] Payment failed for subscription #S_FAIL`, each pair within 5 min of its computed window, gaps 24h ±5 min (unspread).
2. `_payment_retry_attempts` reads 1, 2, 3, 3 after attempts 0–3; `_payment_retry_next_attempt_at` matches the next window and is stale after attempt 3.
3. Determine attempt-number visibility from the four **live rendered customer and admin bodies**, without opening product source. If the attempt number is absent, file `issues/SLT-EML-04-no-attempt-number.md` as the observed product finding (medium: four indistinguishable emails) and prove attempt identity via Mailpit timestamp + `_payment_retry_attempts` + the note. If it is rendered, record the exact text and do not create that issue.
4. Customer body: "the renewal payment for your subscription #S_FAIL could not be completed", Product `SLT Retry Daily`, Amount Due `$13.00`, a Status row, a Pay Now button whose href is the order-pay URL of the SAME failed order every time, and a "Manage your subscription" link.
5. Admin body: "The automatic renewal payment for subscription #S_FAIL from <customer name> has failed", plus Customer/Product/Order/Amount/Status rows and a View Subscription link into wp-admin.
6. Pay Now URL returns 200 with total $13.00 and a payment form; no 404, no console error.
7. `_next_payment_date` never moves; status active through attempt 0, on-hold 08-06, cancelled 08-09. No `payment_failed` mail for any non-SLT subscription in those windows.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1-4 | `payment_failed` | each attempt, `arraysubs_gateway_payment_failed` | slt-fail@example.test | `Payment failed for subscription #S_FAIL` | four task-owned bounded/full deltas; exact S_FAIL + customer `To:` |
| 5-8 | `admin_payment_failed` | same instant | admin address | same subject | same deltas; exact S_FAIL + admin `To:` |
| 9 | `subscription_on_hold` | 08-06 | slt-fail@example.test | `is on hold` | registered ladder delta; exact S_FAIL + customer `To:` |
| 10-11 | `subscription_cancelled` + admin | 08-09 | customer + admin | `has been cancelled` | complete delta after `DUN_CANCEL_PRE` |
| 12 | NONE EXPECTED | after attempt 3 | — | a 5th pair | exactly 8 failure messages |

## Evidence to capture
- `SLT-EML-04-00` through `-07`, including per-attempt pair and safe cycle-keyed pay-page captures; k/windows; exact subscription/order/action/note/user IDs; all registered dunning baselines; 8+ Mailpit IDs with To/headers/timestamps and rendered bodies; every retry-meta read; sessions/review proof.

## Pass criteria
- [ ] 4 customer + 4 admin failure mails, one pair per attempt, 24h apart
- [ ] Attempt-number visibility determined and, if absent, filed as an issue
- [ ] Amount Due $13.00; Pay Now resolves to the same payable failed order each time
- [ ] `_payment_retry_attempts` sequence 1,2,3,3 matches the mail sequence
- [ ] on-hold mail 08-06, cancelled mail 08-09; no 5th attempt
- [ ] Polls were ≤60 seconds, every phase session closed, standalone findings only under `issues/`, and final evidence reviewed to done

## Isolation / teardown
- Read-only: the failed order is deliberately left unpaid so the ladder completes. Keep this card in progress through the D7 cancellation read; only then review it through done. `S_FAIL` ends `arraysubs-cancelled`; SLT-SETUP-99B deletes it. Close only the cycle-keyed task admin/customer sessions at each phase.

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

`SLT-DUN-01` completed its authored missed-fixture branch on 2026-08-05: registry page 11847 stores `S_FAIL unavailable`, live verification found zero subscriptions for user 351/product 12108, and the D03 watch report instructs `SLT-DUN-02/03/04` plus `SLT-EML-04` to close ladder-only assertions `UNVERIFIED` without manufacturing a substitute. Without the failed renewal ladder, the repeated customer/admin payment-failed email assertions owned by this card have no valid source event. This card closes without inventing retry mail.
