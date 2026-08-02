---
id: 81
title: Retry attempts 2 and 3 reuse the same failed order, then the 4th charge hits the 3-retry cap
status: todo
priority: high
created: 2026-08-02T03:43:09.990586698+02:00
updated: 2026-08-02T03:43:20.862517675+02:00
tags:
    - renewal
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 1h
depends_on:
    - 33
class: standard
---

> **SLT-DUN-02** · group `renewal` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · impossible-timing / cross-group date contradiction** — with `SLT-DUN-01`, `SLT-DUN-03`, `SLT-DUN-04`, `SLT-DUN-05`, `SLT-EML-04`, `SLT-EML-14`

- *Problem:* SLT-DUN-01 is tagged d0 (buy SLT Retry Daily as slt-fail on 2026-08-02, D=08-03, hold 08-04, cancel 08-07). Four other tasks encode the opposite timeline as fact: SLT-EML-04 ('bought on D2 (2026-08-04 PM) ... D = 2026-08-05 PM ... attempts 08-05/06/07/08 -> watch D4..D7 ... on-hold 08-06 ... cancelled 08-09'), SLT-EML-14 ('Retry Daily fails 08-05 PM -> on-hold 08-06 -> cancelled 08-09'), SLT-ADM-09 ('bought D2 by slt-fail ... renewal failed D3 PM'), and SLT-MYA-05 ('Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as slt-fail with card 0341 that afternoon and the grant fires only on that activation'). slt-fail + SLT Retry Daily cannot be bought twice (auto-migrate), so exactly one timeline can exist. Additionally MYA-05's pro_member role-mapping rule MUST be written before the checkout - if DUN-01 runs on D0 the role grant never fires and MYA-05 is unrunnable.
- *Required fix:* DUN-01 moves to D2 (2026-08-04), checkout 13:00-14:00 site - which is what four downstream tasks already assume and what the audit's corrected calendar says. Resulting ladder, all fixed: D=08-05 13:00-14:00; failure at D+k (08-05 13:00-20:00, watch D4); on-hold at the first hourly sweep after D+24h = 08-06 ~14:00 (watch D5); retries at +24h/+48h/+72h = 08-06/07/08 (watch D5/D6/D7); 4th charge hits the cap 08-08; cancellation at max(D+96h, on_hold+72h) = 08-09 ~14:00-16:00 (watch D8). Re-day the group: DUN-01 D2, DUN-03 D4, DUN-02 D5 (with reads on D4 and D6), DUN-04 D7, DUN-05 D7 after 16:00 (S2 bought 08-09 16:30, fails 08-10 PM, recovered on the morning of 08-11 before N+24h). MYA-05 stays D2 morning, strictly before 13:00.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Prove the retry ladder: retry #1 (D2) and #2 (D3) re-charge the SAME failed renewal order R, drive `_payment_retry_attempts` to 2 then 3, reschedule at exactly +86400s each, and emit one customer + one admin payment-failed email per attempt with no dedup. Then confirm on D4 that the 4th charge stops at Stripe's hardcoded cap of 3 retries.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-fail`)
- Plugins: pro-required

## Preconditions
- `SLT-DUN-01` done; S, R, k, D recorded. `SLT-DUN-03` (hold) fires D2 independently — retries must keep firing after it, since `process()` accepts `arraysubs-on-hold` (SLT-REF-03 §3).
- Stripe retry config is hardcoded `enabled=true, max_attempts=3, interval_seconds=86400` and is NOT admin-settable — `retry_enabled` / `retry_max_attempts` fields do not exist (SLT-REF-03 §1).
- Never run `wp action-scheduler run` (C07); retries must fire unattended.

## Test data
| Item | Value |
|---|---|
| Subscription | S (SLT-DUN-01), renewal order R |
| Attempt clock | #1 `D+k+24h`, #2 `+48h`, #3 `+72h` (±60s) |
| Cap | 3 retries → 4 charges |
| Session | `admin-dun` |

## Steps
1. **D2 (2026-08-04), 10 min before `D+k+24h`:** `mailpit-agent latest-id` → **MP2**.
2. 10 min after `D+k+24h`: re-run SLT-DUN-01's **M** (subscription meta), **Q** (HPOS orders for `_subscription_id`=S), **L** (pending actions for `[S]`).
3. `mailpit-agent wait-new MP2 900 "Payment failed for subscription #S"`; `list 10`; `show` both new ids for `To:`; `latest-id` → **MP3**.
4. **D3 (2026-08-05), 10 min after `D+k+48h`:** repeat steps 2-3 against MP3; snapshot `latest-id` → **MP4**.
5. `agent-browser --session admin-dun open ".../wp-admin/post.php?post=S&action=edit"` → `snapshot -i`; screenshot the notes panel. Then open R (`/wp-admin/admin.php?page=wc-orders&action=edit&id=R`) and screenshot its notes — one gateway decline per attempt.
6. **D4 (2026-08-06) follow-up, 10 min after `D+k+72h`:** re-run **M**, **Q**, **L**; `mailpit-agent wait-new MP4 900 "Payment failed for subscription #S"`; read the newest subscription note.

## Expected results
1. Exactly ONE renewal order exists for S — R is reused every attempt (`getPendingRenewalOrder()` accepts `pending|on-hold|failed`); **Q** never returns a second `_is_renewal_order=yes` row.
2. R stays `failed` at `$13.00`, no partial capture or refund.
3. `_payment_retry_attempts` = `2` after the D2 attempt and `3` after the D3 attempt.
4. After each, `_payment_retry_next_attempt_at` = that attempt + **exactly 86400s** (retries bypass the spread; only invoice/charge legs carry `k`), and **L** shows one pending `arraysubs_process_renewal` for `[S]`.
5. `_next_payment_date` is still `D` throughout — never advanced by a failed attempt.
6. Retries keep firing while S is `arraysubs-on-hold` (SLT-DUN-03, D2); the hold neither cancels nor defers them.
7. `arraysubs_pre_retry_charge_verification` runs before each retry because attempts > 0 (SLT-REF-01 §1); no note claims the cycle was already charged.
8. D4: the 4th charge fails, attempts stays `3` (not 4), a note records the retry limit reached, and **L** returns NO pending `arraysubs_process_renewal` for `[S]`.
9. Cumulative with SLT-DUN-01: 4 charges → 8 payment-failed messages (4 customer, 4 admin), identical subjects, no dedup (SLT-REF-03 §6).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_failed` + `admin_payment_failed` | `D+k+24h` | customer / admin | `Payment failed for subscription #S` | `wait-new MP2 900`, check both `To:` |
| 2 | `payment_failed` + `admin_payment_failed` | `D+k+48h` | customer / admin | same subject | `wait-new MP3 900` |
| 3 | `payment_failed` + `admin_payment_failed` | `D+k+72h` (D4) | customer / admin | same subject | `wait-new MP4 900` |
| 4 | `payment_successful` / `renewal_invoice` **NONE EXPECTED** | any retry | — | — | No `Payment received for subscription #S`, no `Invoice for #S` |

## Evidence to capture
- Screenshots `SLT-DUN-02-01-notes-d2`, `-02-notes-d3`, `-03-order-R-notes`, `-04-pending-d4`.
- Each **M**/**Q**/**L** dump with UTC time; MP2-MP4 and every matched Mailpit id with `To:`; the `_payment_retry_next_attempt_at` values proving 86400s spacing.

## Pass criteria
- [ ] ER1/2 one renewal order only, reused, still `failed` at $13.00
- [ ] ER3 counter goes 1 → 2 → 3 on the expected days
- [ ] ER4/5 reschedules exactly +86400s; `_next_payment_date` never moves
- [ ] ER6/7 retries keep firing while on-hold, pre-retry verification runs
- [ ] ER8 4th charge stops at the cap, no further pending retry
- [ ] ER9 8 payment-failed messages total, no success/invoice mail

## Isolation / teardown
- Read-only. Do not click **Retry Payment** anywhere — manual retry forces attempts >= 1 and re-enters the cap branch, corrupting the counter evidence (SLT-REF-03 §7).
- Hands the exhausted ladder to `SLT-DUN-04` (cancellation, D5). S, R and `slt-fail` stay untouched.

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
