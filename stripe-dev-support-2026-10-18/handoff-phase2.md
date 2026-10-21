# Phase 2 handoff (S03, S04 — configuration A, 2026-10-19)

Configuration A: core `4791832` + Pro `edaa401`. Times UTC. All actions run were fixture actions (registered in `fixtures.json`).

## Fixtures

| Customer | Subscription | Orders | Purpose |
|---|---|---|---|
| 579 sqa-p2-decline | 68418 (4242) | 68417, renewal 68443 | portal card change (#56) |
| 579 sqa-p2-decline | 68439 (trial 68435, 0341) | 68438, failed renewal 68473 | S03 decline, customer/admin recovery |
| 580 sqa-p2-auth | 68466 (3184) | 68465, pending renewal 68494 | S03 authentication |
| 583 sqa-p2-ladder | 68512 (trial, 0341) | 68511, failed renewal 68550 | S04 retry ladder / exhaustion |
| 581 sqa-p2-noretry | 68599 (trial, 0341) | 68598, failed renewal 68614 | S04 retry disabled |
| 582 sqa-p2-grace | 68525 (trial, 0341, grace override 0/0) | 68524, failed renewal 68553 | S04 grace override, on-hold, cancel, grace_ended |

Product fixture 68435 "SQA Daily Trial Plan" (1-day trial, 7.31/day) was added because card 0341 only fails when charged: a trial checkout attaches it through a SetupIntent, and the first collection after `endTrial()` + `setNextPaymentDate()` is a genuine off-session decline. Changing the card of an existing subscription was not possible (issue #56).

## Results

| Case | Result | Evidence |
|---|---|---|
| S03 decline — unpaid state, feedback | PASS | 68473 failed; notes + "Payment failed for subscription #68439" email (reason, Pay Now link, Mailpit 2a73AYLye38cWmerwEr4eX); admin detail shows failure and Retry Payment; admin retry: confirm modal, spinner, REST 402, one new PI pi_3USDE3JG5OzSNVs20iOTMPYX declined, no duplicate |
| S03 decline — classification | FAIL (#57) | Stripe code card_declined / decline_code generic_decline stored as hard `card_declined`; retry not scheduled under default settings |
| S03 decline — customer payment path | FAIL (#59, #56) | Pay Now refuses a new card ("Use the saved Stripe payment method shown for this renewal verification.") after the verification sweep marks the order; portal card change refused |
| S03 authentication — off-session | PASS | 68494 pending, pi_3USCsOJG5OzSNVs21pOjRAHA requires_payment_method (authentication_required); verification email (Mailpit 3bgg6BwCvhhIrEuNjHuHNG) with grace date; no duplicate collection |
| S03 authentication — customer verification | FAIL (#58) | verification and Pay pages refuse every submission; customer has no WC token after the 3DS checkout |
| S04 retry timing/counts | PASS (with stop_on_hard_decline=false) | 68512: attempts at 09:43:24, retries 1–3 each scheduled exactly +24 h after the failure (100973, 100975, 100976, run explicitly), 4 Stripe PIs all generic_decline, `retry_exhausted` after retry 3 |
| S04 retry disabled | PASS | 68599: "Automatic retries are disabled…", no retry action |
| S04 hard decline handling | PASS as configured, but see #57 | 68439 with stop_on_hard_decline=true: "No automatic retry was scheduled…" |
| S04 exhaustion ≠ grace end | PASS | 68512 after exhaustion: active, has_view_entitlement true, phase before_on_hold, hold 10-22 09:43:18 (due+3 d), cancel 10-29 09:43:18 (due+3+7 d) |
| S04 individual grace override | PASS | 68525 policy 0/0 (source subscription): on-hold 13:18:07, cancelled 13:19:04 (see grace section) |
| S04 settlement guards | NOT COVERED | no Stripe path in this setup produces an awaiting-settlement renewal (card methods settle synchronously; recovery payments blocked by #56/#58/#59) |
| S04 grace_ended order_id / stable event_id | PASS | order_id 68553, operation-scoped deterministic event_id (see grace section) |

Lifecycle (68512): every `renewal_failed`, `retry_attempted`, `retry_failed`, `retry_scheduled` has a distinct operation-scoped `event_id` (cfcfaac fix verified); `_arraysubs_lifecycle_attempt_seq` = 4 after 4 attempts. `grace_entered` is re-emitted after each failure with one stable `event_id` (idempotent identity). `retry_scheduled` carries `order_id: 0`.

## Grace (68525)

Override `before_on_hold=0`, `before_cancel=0` (source `subscription`) set with `setGracePolicy()` at 09:42Z; renewal 68553 declined at 09:43:24 (0341, retry 1 queued as 100974 because stop_on_hard_decline was false at that moment).

| Time (UTC) | What happened | Evidence |
|---|---|---|
| 09:43:29 | developer recovery: hold_eligible_at 12:57:00 (due 09:43:19 + spread offset + 1 h), cancel_eligible clamps to the on-hold date | `snapshots/sub-68525-s04-after-attempt0.json` |
| 09:51–13:17 | stays active, `has_view_entitlement` true, phase before_on_hold | watcher, `sub-68525-s04-grace-arraysubs-active.json` |
| 13:18:07 | hourly overdue sweep (WP-Cron): on-hold, `_on_hold_date` set, events `revoked` / `status_changed` / `on_hold`; entitlement false; email "Your subscription #68525 is on hold" | `sub-68525-s04-grace-arraysubs-on-hold.json`, Mailpit 2HJGNJfVcbNagkLhzUFOHy |
| 13:19:04 | phase 2 of the same sweep chain: cancelled (`_cancellation_reason` overdue_payment, `_cancelled_by` system), unpaid renewal 68553 cancelled, queued retry 100974 canceled; emails to customer and admin | `sub-68525-s04-grace-arraysubs-cancelled.json`, Mailpit 0ZNpY2EGC1gf3pMwWABdYS / 48dVYp4pOmLTmqS34ZNnyH |
| 13:19:06 | `grace_ended`: `order_id` **68553**, `reason` overdue, `identity_scope` operation, `event_id` c7044455…e3ad = sha256("grace_ended:68525:order:68553:attempt::result:") → stable on redelivery by construction; followed by `cancelled` (cancellation_mode overdue) | `capture/lifecycle-events.jsonl` |

Result: **S04 grace / individual override / grace_ended order_id + stable event_id: PASS.** Notes: `on_hold`, `revoked` and `cancelled` events carry `order_id: 0` and request-scoped (random) event IDs, so only `grace_ended` is redelivery-stable; developer `recovery.next_retry_at` still reports 2026-10-20 09:43:29 after cancellation (extends #60).

## Settings

- Snapshot before change: `snapshots/settings-before-phase2.json` (identical to `settings-original-r2.json`).
- Changed through the shipped REST route `POST /arraysubs/v1/settings/save` (no admin UI exists, #61): `payment_retry.stop_on_hard_decline=false` (09:41), then `payment_retry.enabled=false` (09:46).
- Restored with `scripts/restore-settings.php … apply` at 09:49; `snapshots/diff-original-vs-after-phase2-retry-restore.json`: `all_identical: true`.

## Issues logged

#56 portal card change refused · #57 Stripe declines always hard · #58 authentication renewal cannot be completed after 3DS checkout · #59 verification sweep locks Pay Now on ordinary declines · #60 stale next_retry_at · #61 no retry settings UI.

## Other observations

- 3DS checkout order 68465 stayed pending ~4 min until WooCommerce Stripe's deferred webhook (WC Stripe behavior).
- `endTrial()` sends "trial has converted to a paid subscription" before any payment; the first collection then failed.
- The site has two independent recurring `arraysubs_check_overdue_renewals` chains (actions 100891, 100895; first runs May and July 2026); the overlap transient prevents double processing.
- `scripts/restore-settings.php` now also accepts the bare word `apply` (WP-CLI rejects unknown `--apply`).
