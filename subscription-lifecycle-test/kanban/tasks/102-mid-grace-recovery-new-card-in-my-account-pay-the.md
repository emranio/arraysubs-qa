---
id: 102
title: 'Mid-grace recovery: new card in My Account, pay the failed renewal, and prove the next-payment anchor'
status: todo
priority: high
created: 2026-08-02T03:43:11.49741294+02:00
updated: 2026-08-02T03:43:22.891867995+02:00
tags:
    - renewal
    - day-07
    - has-conflicts
due: "2026-08-09"
estimate: 2h
depends_on:
    - 101
class: standard
---

> **SLT-DUN-05** · group `renewal` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · impossible-timing / cross-group date contradiction** — with `SLT-DUN-01`, `SLT-DUN-02`, `SLT-DUN-03`, `SLT-DUN-04`, `SLT-EML-04`, `SLT-EML-14`

- *Problem:* SLT-DUN-01 is tagged d0 (buy SLT Retry Daily as slt-fail on 2026-08-02, D=08-03, hold 08-04, cancel 08-07). Four other tasks encode the opposite timeline as fact: SLT-EML-04 ('bought on D2 (2026-08-04 PM) ... D = 2026-08-05 PM ... attempts 08-05/06/07/08 -> watch D4..D7 ... on-hold 08-06 ... cancelled 08-09'), SLT-EML-14 ('Retry Daily fails 08-05 PM -> on-hold 08-06 -> cancelled 08-09'), SLT-ADM-09 ('bought D2 by slt-fail ... renewal failed D3 PM'), and SLT-MYA-05 ('Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as slt-fail with card 0341 that afternoon and the grant fires only on that activation'). slt-fail + SLT Retry Daily cannot be bought twice (auto-migrate), so exactly one timeline can exist. Additionally MYA-05's pro_member role-mapping rule MUST be written before the checkout - if DUN-01 runs on D0 the role grant never fires and MYA-05 is unrunnable.
- *Required fix:* DUN-01 moves to D2 (2026-08-04), checkout 13:00-14:00 site - which is what four downstream tasks already assume and what the audit's corrected calendar says. Resulting ladder, all fixed: D=08-05 13:00-14:00; failure at D+k (08-05 13:00-20:00, watch D4); on-hold at the first hourly sweep after D+24h = 08-06 ~14:00 (watch D5); retries at +24h/+48h/+72h = 08-06/07/08 (watch D5/D6/D7); 4th charge hits the cap 08-08; cancellation at max(D+96h, on_hold+72h) = 08-09 ~14:00-16:00 (watch D8). Re-day the group: DUN-01 D2, DUN-03 D4, DUN-02 D5 (with reads on D4 and D6), DUN-04 D7, DUN-05 D7 after 16:00 (S2 bought 08-09 16:30, fails 08-10 PM, recovered on the morning of 08-11 before N+24h). MYA-05 stays D2 morning, strictly before 13:00.

**`high` · shared-global-setting / same-day bracket collision** — with `SLT-SW-08`, `SLT-SW-04`, `SLT-SW-02`, `SLT-ADM-01`, `SLT-MYA-04`

- *Problem:* SLT-SW-08 (d7) sets proration.switch_fees.upgrade from 0 to 7.50 globally and restores it in the same task, declaring 'no other SLT switch may run between set and restore'. SLT-SW-04 (d7) performs a Basic->Pro upgrade the same day and asserts its proration order matches SLT-SW-01's record-for-record with 'no switch-fee row'. If SW-04 runs inside SW-08's bracket its order gains a $7.50 'Plan Upgrade switch fee' line and the comparison fails for the wrong reason. The bracket file exists but nothing sequences the two tasks.
- *Required fix:* Fix the D7 order explicitly in the calendar and in both task bodies: SLT-SW-04 completes and its proration order is PAID before SLT-SW-08 opens its bracket. SW-08's step 2 gains a pre-flight assertion: 'SLT-SW-04 is done on the board and no plan_switch order created today is still unpaid'. SW-08's bracket file must record open/close UTC and be posted to the registry so any switch order created inside it can be attributed and re-run.

**`medium` · impossible-timing / single-day contention** — with `SLT-LIFE-01`, `SLT-SW-02`, `SLT-SYN-10`, `SLT-EML-08`, `SLT-EML-10`, `SLT-EML-14`

- *Problem:* D8 (2026-08-10) is the single authorized time-travel day and six tasks are stacked on it, each of which demands exclusive control of the pending Action Scheduler queue: SLT-SYN-10 (runs one month-renewal action by id and must prove no non-SLT date moved), SLT-SW-02 Leg B (hand-set _end_date + expire), SLT-EML-08 (expects an empty pending queue for its own _end_date write), SLT-EML-10 (queues an expiring-soon action in the past and runs it), SLT-LIFE-01 (back-dates S5's legs twice and leaves the queue empty for up to 3h waiting for the recovery sweep), SLT-EML-14 (read-only sweep whose whole value is that nothing moved). Each takes its own 'abort if a non-SLT action is due within 24h' pre-flight, and each would abort on the others' queued work. Run in any order but the right one, they invalidate each other's proofs.
- *Required fix:* Fix a strict D8 running order in the calendar and make it a precondition line in each body: (0) SLT-TT-00 pre-flight - one shared pending-queue screenshot plus the 13 non-SLT _next_payment_date snapshot, published to the registry and quoted by every other D8 task instead of re-taken; (1) SLT-TT-00 executes the month seg1/seg2 + week seg3 + flex-variable-tail renewals; (2) SLT-SYN-10 (month overflow, one action by id); (3) SLT-SW-02 (Leg A downgrade, then Leg B expiry auto-downgrade); (4) SLT-EML-08 (observes SW-02 Leg B; reactivates S_EML); (5) SLT-EML-10 (expiring-soon + card-expiring probes; cancels S_EML at teardown); (6) SLT-LIFE-01 (late-renewal phases A and B on S5 - last, because Phase B deliberately leaves S5 with zero legs and a past date for up to 3h); (7) SLT-EML-14 (read-only negative sweep, after everything). Close the day with the shared post-drain non-SLT diff.

---
## Objective
Mid-grace recovery. A second `SLT Retry Daily` subscription S2 fails its first renewal; the customer updates the payment method in My Account and pays the failed renewal order before the on-hold sweep. Assert S2 returns to `arraysubs-active`, retry meta clears, and state whether the new `_next_payment_date` anchors on `_renewal_scheduled_date` (the original due date, SLT-REF-01 §1) or on payment time.

## Scope
- Gateway: Stripe test
- Checkout: block signup, My Account order-pay for recovery
- Account: existing (`slt-fail`)
- Plugins: pro-required

## Preconditions
- `SLT-DUN-04` done, S `arraysubs-cancelled` — `slt-fail` and the product are free, so the ladders never overlap.
- **Deliberate card deviation:** do NOT use the catalog's `9995`; it declines on-session so the parent order would never be paid. Reuse `0341`.
- `auto_migrate_on_checkout = true` and `slt-fail` owns cancelled S — record whether checkout creates a NEW subscription or migrates it (C08).
- Buy after 12:00 site (C02). Never run `wp action-scheduler run` (C07).

## Test data
| Item | Value |
|---|---|
| Product | SLT Retry Daily $13.00 day/1 |
| Signup card | `4000 0000 0000 0341` (off-session decline) |
| Recovery card | `4242 4242 4242 4242` `12/34` CVC `123` |
| Sessions | `cust-dun5`, `admin-dun5` (C09) |

## Steps
1. **D6 (2026-08-08), 13:00-14:00 site.** `mailpit-agent latest-id` → **MPR**; sign in as `slt-fail` in `--session cust-dun5`; cart must be empty.
2. Buy `SLT Retry Daily` at `/checkout/` on the `0341` card, saving it. Record order **O2**, subscription **S2**, assert `S2 != S`; record **N** = `_next_payment_date`, **k2** = `crc32('arraysubs-spread-'.S2) % 21600`.
3. **D7 (2026-08-09), 10 min after `N + k2`:** run **Q** → **R2** is `failed`; **M** → attempts = 1; `wait-new MPR 900 "Payment failed for subscription #S2"`; `latest-id` → **MPS**. Deadline: the hold sweep hits at `N + 24h`.
4. Open `/my-account/view-subscription/S2/` → `snapshot -i`; screenshot the failure state; click **Manage payment methods**. On `/my-account/payment-methods/` use **Add payment method** for the `4242` card and make it default.
5. Open `/my-account/orders/`, find R2 (`Failed`), click **Pay**; on order-pay pick the saved `4242` card and click **Pay for order**.
6. Re-run **M**, **Q**, **L** and the post-status query; record payment UTC time **P**.
7. `mailpit-agent wait-new MPS 300 "Payment received for subscription #S2"`; `list 10`.
8. Open S2 in wp-admin (`--session admin-dun5`); screenshot status and notes. Report BOTH candidates — (a) `N + 1 day`, (b) `P + 1 day` — and which one `_next_payment_date` equals.

## Expected results
1. A NEW subscription S2 is created (`S2 != S`); if cancelled S is migrated instead, record a finding.
2. R2 is `failed` at `N + k2`, then `processing`/`completed` after order-pay, `$13.00`, on the `4242` card.
3. S2 is `arraysubs-active` after payment, never on-hold (recovery beat `N + 24h`).
4. Meta cleared: `_payment_retry_*` and `_last_payment_failure*` gone/zero, `_renewal_failure_resolved*` present, `_pending_renewal_order_id` deleted.
5. `_completed_payments` = 2; `_last_payment_date` = **P**.
6. **Headline:** new `_next_payment_date` = `N + 1 day` (base `_renewal_scheduled_date`, not payment time — SLT-REF-01 §1). If it equals `P + 1 day`, file an issue with both.
7. Being future, both legs re-queue: invoice at `new_due + k2 − 6h`, charge at `new_due + k2`.
8. The retry action queued at `attempt + 86400s` is gone from **L**. If it survives, record a candidate bug — an extra charge would fire near the new due date.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_failed` + `admin_payment_failed` | `N + k2` | customer / admin | `Payment failed for subscription #S2` | `wait-new MPR 900`, both `To:` |
| 2 | `payment_successful` | order-pay done | `slt-fail@example.test` | `Payment received for subscription #S2` | `wait-new MPS 300` |
| 3 | hold / cancel / 2nd `new_subscription` **NONE EXPECTED** | ever | — | — | No `is on hold`, `has been cancelled` or 2nd `is active` for S2 |

## Evidence to capture
- Screenshots `SLT-DUN-05-01-failure`, `-02-pay-methods`, `-03-pay`, `-04-active`.
- S2, O2, R2, k2, N, P; both candidate next-payment values and the match; M/Q/L before/after; Mailpit ids + `To:`.

## Pass criteria
- [ ] ER1/2 new S2 (not a migration); R2 `failed` → paid $13.00 on the new card
- [ ] ER3/4/5 S2 active, never on-hold; meta cleared; `_completed_payments` = 2
- [ ] ER6 next-payment anchor stated, with both candidates
- [ ] ER7/8 both legs re-queued with k2; no stale retry action for `[S2]`
- [ ] All three email rows satisfied, including the negative row

## Isolation / teardown
- S2 stays live and renews daily on the `4242` card — hand it to `SLT-SETUP-99A` (D10) so the D11/D12 watch is not polluted.
- Nothing global changed; the `4242` token dies with `slt-fail` in `SLT-SETUP-99B`. Close both sessions.

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
