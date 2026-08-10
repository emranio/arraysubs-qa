---
id: 81
title: Retry attempts 2 and 3 reuse the same failed order, then the 4th charge hits the 3-retry cap
status: done
priority: high
created: 2026-08-02T03:43:09.990586698+02:00
updated: 2026-08-05T21:37:49.5720067+02:00
started: 2026-08-05T21:07:19.664016457+02:00
completed: 2026-08-05T21:07:19.664016457+02:00
tags:
    - renewal
    - day-05
due: "2026-08-07"
estimate: 1h
depends_on:
    - 33
    - 66
class: standard
---

> **SLT-DUN-02** · group `renewal` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the retry ladder: retry #1 (D4) and #2 (D5) re-charge the SAME failed renewal order R, drive `_payment_retry_attempts` to 2 then 3, reschedule at exactly +86400s each, and emit one customer + one admin payment-failed email per attempt with no dedup. Then confirm on D6 that the 4th charge stops at Stripe's hardcoded cap of 3 retries.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-fail`)
- Plugins: pro-required

## Preconditions
- `SLT-DUN-01` done; S, R, k, D recorded. `SLT-DUN-03` (hold) fires D4 independently — retries must keep firing after it, since `process()` accepts `arraysubs-on-hold` (SLT-REF-03 §3).
- Stripe retry config is hardcoded `enabled=true, max_attempts=3, interval_seconds=86400` and is NOT admin-settable — `retry_enabled` / `retry_max_attempts` fields do not exist (SLT-REF-03 §1).
- Never run `wp action-scheduler run` (C07); retries must fire unattended.

## Test data
| Item | Value |
|---|---|
| Subscription | S (SLT-DUN-01), renewal order R |
| Attempt clock | #1 `D+k+24h`, #2 `+48h`, #3 `+72h` (±60s) |
| Cap | 3 retries → 4 charges |
| Session | `admin-dun-SLT-DUN-02` |

## Steps
1. **D5 (2026-08-07) morning, before `D+k+48h`:** resolve registry alias `S_FAIL` into shell variable `S`, load numeric renewal order `R`, D, k, and the exact `DUN_RETRY1_PRE` value/timestamp published by SLT-DUN-01; abort unless `S` and `R` are numeric and the baseline is non-empty. Inspect the complete Mailpit delta after that baseline and require exactly two D4 retry-1 messages whose subject names exact numeric `$S`: one to `slt-fail@example.test` and one to the recorded admin address. `mailpit-agent show` both and classify unrelated shared-site mail instead of relying on a fixed recent-message count.
2. Re-run SLT-DUN-01's **M** (subscription meta), **Q** (all HPOS renewal orders relationship-filtered to `$S`), and **L** (pending actions for indexed args `[$S]`) to prove the settled D4 state. Read the exact D5 retry action ID/gate from **L**, publish its `gate−5m` deadline, and set `DUN_RETRY2_PRE=$(mailpit-agent latest-id)` only inside `[gate−300s, gate)`; append the exact value/UTC capture time to the registry for SLT-EML-04.
3. **D5 at/after `D+k+48h`:** poll `mailpit-agent wait-new "$DUN_RETRY2_PRE" 60 "Payment failed for subscription #$S"` in repeated intervals of at most 60 seconds until the authored 10-minute post-gate cutoff. Inspect the complete delta, require the exact customer/admin pair, and re-run **M/Q/L**. Resolve the sole renewal order by exact `$S` relationship and require it is still numeric `$R`. Publish the D6 retry action ID/gate and its `gate−5m` deadline, but do not take `DUN_RETRY3_PRE` a day early.
4. In `admin-dun-SLT-DUN-02-D5`, open the exact ArraySubs detail route for `$S`, capture `SLT-DUN-02-01-notes-d4.png` and `-02-notes-d5.png`; then open exact HPOS order `$R` and capture `SLT-DUN-02-03-order-R-notes.png`, requiring one relationship-owned gateway decline per attempt. Close only this D5 session.
5. **D6:** set `DUN_RETRY3_PRE` only inside the published final-five-minute interval and persist it before the exact gate. Poll the same immutable baseline in ≤60-second calls through the 10-minute post-gate cutoff; require the exact pair, re-run **M/Q/L**, and select the new cap note by exact pre/post note-ID set difference rather than newest-note recency. In `admin-dun-SLT-DUN-02-D6`, capture the cap/no-pending state as `SLT-DUN-02-04-pending-d6.png`; publish matched IDs and settled timestamp for SLT-EML-04, then close the session.
6. If any live assertion fails, create a standalone `issues/SLT-DUN-02-<concise-slug>.md` (never a kanban bug card) with task/stage/plan, subscription/order/action/note IDs, user ID/login/email/role, exact routes/sessions/gates, reproduction, expected/actual, M/Q/L, Mailpit, UI, and screenshot proof, plus the preceding attempt as counterexample. Continue unaffected reads. After D6, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Exactly ONE renewal order exists for S — R is reused every attempt (`getPendingRenewalOrder()` accepts `pending|on-hold|failed`); **Q** never returns a second `_is_renewal_order=yes` row.
2. R stays `failed` at `$13.00`, no partial capture or refund.
3. `_payment_retry_attempts` = `2` after the D4 attempt and `3` after the D5 attempt.
4. After each, `_payment_retry_next_attempt_at` = that attempt + **exactly 86400s** (retries bypass the spread; only invoice/charge legs carry `k`), and **L** shows one pending `arraysubs_process_renewal` for `[S]`.
5. `_next_payment_date` is still `D` throughout — never advanced by a failed attempt.
6. Retries keep firing while S is `arraysubs-on-hold` (SLT-DUN-03, D4); the hold neither cancels nor defers them.
7. `arraysubs_pre_retry_charge_verification` runs before each retry because attempts > 0 (SLT-REF-01 §1); no note claims the cycle was already charged.
8. D6: the 4th charge fails, attempts stays `3` (not 4), a note records the retry limit reached, and **L** returns NO pending `arraysubs_process_renewal` for `[S]`.
9. Cumulative with SLT-DUN-01: 4 charges → 8 payment-failed messages (4 customer, 4 admin), identical subjects, no dedup (SLT-REF-03 §6).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_failed` + `admin_payment_failed` | `D+k+24h` | customer / admin | `Payment failed for subscription #S` | complete delta after registered `DUN_RETRY1_PRE`; exact S + both `To:` values |
| 2 | `payment_failed` + `admin_payment_failed` | `D+k+48h` | customer / admin | same subject | complete delta after `DUN_RETRY2_PRE` |
| 3 | `payment_failed` + `admin_payment_failed` | `D+k+72h` (D6) | customer / admin | same subject | complete delta after `DUN_RETRY3_PRE` |
| 4 | `payment_successful` / `renewal_invoice` **NONE EXPECTED** | any retry | — | — | No `Payment received for subscription #S`, no `Invoice for #S` |

## Evidence to capture
- Screenshots `SLT-DUN-02-01-notes-d4.png`, `-02-notes-d5.png`, `-03-order-R-notes.png`, `-04-pending-d6.png`.
- Each **M**/**Q**/**L** dump with UTC time; the three registered `DUN_RETRYn_PRE` baselines and every matched Mailpit id with `To:`; the `_payment_retry_next_attempt_at` values proving 86400s spacing.

## Pass criteria
- [ ] ER1/2 one renewal order only, reused, still `failed` at $13.00
- [ ] ER3 counter goes 1 → 2 → 3 on the expected days
- [ ] ER4/5 reschedules exactly +86400s; `_next_payment_date` never moves
- [ ] ER6/7 retries keep firing while on-hold, pre-retry verification runs
- [ ] ER8 4th charge stops at the cap, no further pending retry
- [ ] ER9 8 payment-failed messages total, no success/invoice mail
- [ ] Final-five-minute baselines and ≤60-second polling used; phase sessions closed and evidence reviewed to done

## Isolation / teardown
- Read-only. Do not click **Retry Payment** anywhere — manual retry forces attempts >= 1 and re-enters the cap branch, corrupting the counter evidence (SLT-REF-03 §7).
- Hands the exhausted ladder to `SLT-DUN-04` (cancellation, D7). S, R and `slt-fail` stay untouched; close only the exact D5/D6 task sessions.

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

`SLT-DUN-01` completed its authored missed-fixture branch on 2026-08-05: registry page 11847 stores `S_FAIL unavailable`, live verification found zero subscriptions for user 351/product 12108, and the D03 watch report instructs `SLT-DUN-02/03/04` plus `SLT-EML-04` to close ladder-only assertions `UNVERIFIED` without manufacturing a substitute. With no attempt-0 failure and no shared failed order, the retry-2 / retry-3 assertions owned by this card can never become real. This card closes without forcing the ladder.
