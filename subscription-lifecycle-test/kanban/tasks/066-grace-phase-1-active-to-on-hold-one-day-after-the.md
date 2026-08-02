---
id: 66
title: 'Grace phase 1: active to on-hold one day after the due date, with the customer-only on-hold email'
status: todo
priority: high
created: 2026-08-02T03:43:08.902788936+02:00
updated: 2026-08-02T03:43:19.326793824+02:00
tags:
    - renewal
    - day-04
    - has-conflicts
due: "2026-08-06"
estimate: 45m
depends_on:
    - 33
class: standard
---

> **SLT-DUN-03** · group `renewal` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · impossible-timing / cross-group date contradiction** — with `SLT-DUN-01`, `SLT-DUN-02`, `SLT-DUN-04`, `SLT-DUN-05`, `SLT-EML-04`, `SLT-EML-14`

- *Problem:* SLT-DUN-01 is tagged d0 (buy SLT Retry Daily as slt-fail on 2026-08-02, D=08-03, hold 08-04, cancel 08-07). Four other tasks encode the opposite timeline as fact: SLT-EML-04 ('bought on D2 (2026-08-04 PM) ... D = 2026-08-05 PM ... attempts 08-05/06/07/08 -> watch D4..D7 ... on-hold 08-06 ... cancelled 08-09'), SLT-EML-14 ('Retry Daily fails 08-05 PM -> on-hold 08-06 -> cancelled 08-09'), SLT-ADM-09 ('bought D2 by slt-fail ... renewal failed D3 PM'), and SLT-MYA-05 ('Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as slt-fail with card 0341 that afternoon and the grant fires only on that activation'). slt-fail + SLT Retry Daily cannot be bought twice (auto-migrate), so exactly one timeline can exist. Additionally MYA-05's pro_member role-mapping rule MUST be written before the checkout - if DUN-01 runs on D0 the role grant never fires and MYA-05 is unrunnable.
- *Required fix:* DUN-01 moves to D2 (2026-08-04), checkout 13:00-14:00 site - which is what four downstream tasks already assume and what the audit's corrected calendar says. Resulting ladder, all fixed: D=08-05 13:00-14:00; failure at D+k (08-05 13:00-20:00, watch D4); on-hold at the first hourly sweep after D+24h = 08-06 ~14:00 (watch D5); retries at +24h/+48h/+72h = 08-06/07/08 (watch D5/D6/D7); 4th charge hits the cap 08-08; cancellation at max(D+96h, on_hold+72h) = 08-09 ~14:00-16:00 (watch D8). Re-day the group: DUN-01 D2, DUN-03 D4, DUN-02 D5 (with reads on D4 and D6), DUN-04 D7, DUN-05 D7 after 16:00 (S2 bought 08-09 16:30, fails 08-10 PM, recovered on the morning of 08-11 before N+24h). MYA-05 stays D2 morning, strictly before 13:00.

---
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
| Session | `admin-dun3` |

## Steps
1. **D2 (2026-08-04), 20+ min before `D + 24h`:** `mailpit-agent latest-id` → **MPH**; confirm S is still `arraysubs-active` (`wp post list --post_type=arraysubs_data --include=S --field=post_status --allow-root`).
2. `mailpit-agent wait-new MPH 5400 "is on hold"` (exit 124 = no hold → capture evidence, file an issue, do NOT force the sweep).
3. On the match, re-run SLT-DUN-01's **M** (subscription meta), **Q** (HPOS orders for `_subscription_id`=S) and the post-status query.
4. `mailpit-agent show <id>` — record `To:`, subject and the body's next-payment / amount lines.
5. `mailpit-agent list 20` — confirm NO second message about the hold went to the admin address.
6. `agent-browser --session admin-dun3 open ".../wp-admin/edit.php?post_type=arraysubs_data"` → `snapshot -i`; screenshot S as **On hold**, then open S and screenshot the notes panel.
7. `agent-browser --session cust-dun open ".../my-account/view-subscription/S/"` → `snapshot -i`; screenshot.
8. Re-run listing **L** for `arraysubs_process_renewal` on `[S]`.

## Expected results
1. S is `arraysubs-on-hold` on the first hourly sweep after `D + 24h`; report the UTC timestamp and its lag from `D + 24h` (expect < 60 min).
2. `_on_hold_date` written as a UTC MySQL datetime = the transition time (±60s).
3. `_next_payment_date` is STILL `D` — the hold does not advance it, and phase 2 reads it.
4. R stays `failed` (not cancelled); **Q** still returns exactly one renewal order.
5. `_payment_retry_attempts` is untouched by the hold itself (1 before retry #1, 2 after).
6. **L** still shows one pending `arraysubs_process_renewal` for `[S]`: the ladder survives the hold.
7. Exactly ONE new email — `subscription_on_hold` to `slt-fail@example.test`; no admin hold mail (SLT-REF-04).
8. My Account shows S **On hold** with a **Retry Payment** button and a **Manage payment methods** link (allowed in `arraysubs-on-hold`).
9. No `subscription_cancelled` mail today — cancellation belongs to D5.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `subscription_on_hold` | first sweep after `D+24h` | `slt-fail@example.test` | `Your subscription #S is on hold` | `wait-new MPH 5400`, check `To:` |
| 2 | admin hold notice **NONE EXPECTED** | same tick | — | — | `list 20`: no second hold message; no such class exists |
| 3 | `subscription_cancelled` **NONE EXPECTED** | D2 | — | — | No `has been cancelled` before 2026-08-07 |
| 4 | `payment_failed` pair | retry #1, same day | customer / admin | `Payment failed for subscription #S` | Owned by SLT-DUN-02 |

## Evidence to capture
- Screenshots `SLT-DUN-03-01-admin-on-hold`, `-02-notes`, `-03-myaccount`.
- UTC transition time, `_on_hold_date`, lag from `D+24h`; Mailpit id + `To:` + body; M/Q/L dumps.

## Pass criteria
- [ ] ER1/2 hold within 60 min of `D+24h`, `_on_hold_date` matches
- [ ] ER3 `_next_payment_date` still `D`
- [ ] ER4/5 R still `failed`; retry counter untouched by the hold
- [ ] ER6 retry action still pending
- [ ] ER7 exactly one `is on hold` email, customer only
- [ ] ER8 My Account: On hold + Retry Payment + payment-method link
- [ ] ER9 no cancellation mail on D2

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
