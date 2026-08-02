---
id: 101
title: 'Grace phase 2: terminal cancellation three days after the hold, with customer and admin cancel emails'
status: todo
priority: high
created: 2026-08-02T03:43:11.420598897+02:00
updated: 2026-08-02T03:43:22.764796198+02:00
tags:
    - renewal
    - day-07
    - has-conflicts
due: "2026-08-09"
estimate: 1h
depends_on:
    - 81
    - 66
class: standard
---

> **SLT-DUN-04** · group `renewal` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · impossible-timing / cross-group date contradiction** — with `SLT-DUN-01`, `SLT-DUN-02`, `SLT-DUN-03`, `SLT-DUN-05`, `SLT-EML-04`, `SLT-EML-14`

- *Problem:* SLT-DUN-01 is tagged d0 (buy SLT Retry Daily as slt-fail on 2026-08-02, D=08-03, hold 08-04, cancel 08-07). Four other tasks encode the opposite timeline as fact: SLT-EML-04 ('bought on D2 (2026-08-04 PM) ... D = 2026-08-05 PM ... attempts 08-05/06/07/08 -> watch D4..D7 ... on-hold 08-06 ... cancelled 08-09'), SLT-EML-14 ('Retry Daily fails 08-05 PM -> on-hold 08-06 -> cancelled 08-09'), SLT-ADM-09 ('bought D2 by slt-fail ... renewal failed D3 PM'), and SLT-MYA-05 ('Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as slt-fail with card 0341 that afternoon and the grant fires only on that activation'). slt-fail + SLT Retry Daily cannot be bought twice (auto-migrate), so exactly one timeline can exist. Additionally MYA-05's pro_member role-mapping rule MUST be written before the checkout - if DUN-01 runs on D0 the role grant never fires and MYA-05 is unrunnable.
- *Required fix:* DUN-01 moves to D2 (2026-08-04), checkout 13:00-14:00 site - which is what four downstream tasks already assume and what the audit's corrected calendar says. Resulting ladder, all fixed: D=08-05 13:00-14:00; failure at D+k (08-05 13:00-20:00, watch D4); on-hold at the first hourly sweep after D+24h = 08-06 ~14:00 (watch D5); retries at +24h/+48h/+72h = 08-06/07/08 (watch D5/D6/D7); 4th charge hits the cap 08-08; cancellation at max(D+96h, on_hold+72h) = 08-09 ~14:00-16:00 (watch D8). Re-day the group: DUN-01 D2, DUN-03 D4, DUN-02 D5 (with reads on D4 and D6), DUN-04 D7, DUN-05 D7 after 16:00 (S2 bought 08-09 16:30, fails 08-10 PM, recovered on the morning of 08-11 before N+24h). MYA-05 stays D2 morning, strictly before 13:00.

---
## Objective
Prove grace phase 2: with `grace_days_before_cancel = 3`, S is cancelled by the hourly sweep at `max(D + 4 days, _on_hold_date + 3 days)`, unpaid order R is cancelled, every Action Scheduler leg for `[S]` is unscheduled, cancellation meta is stamped, and both `subscription_cancelled` and `admin_subscription_cancelled` fire.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-fail`)
- Plugins: both

## Preconditions
- `SLT-DUN-01/02/03` done. S `arraysubs-on-hold`, retries exhausted (attempts = 3, none pending), R still `failed`, `_next_payment_date` still `D`, `_on_hold_date` recorded.
- Both gates must pass: SQL cutoff `_next_payment_date < now − 4 days` AND per-row `now ≥ _on_hold_date + 3 days` (SLT-REF-03 §4). With the hold at ≈ `D+24h` both land ≈ `D + 4 days` → **2026-08-07, ≈ 14:00-16:00 site**.
- Never run `wp action-scheduler run` (C07); D5 is not the authorized drain day.

## Test data
| Item | Value |
|---|---|
| Subscription | S (on-hold), order R (`failed`) |
| Cancel window | first sweep after `max(D+96h, _on_hold_date+72h)` |
| Expected meta | `_cancelled_by=system`, `_cancellation_reason=overdue_payment` |
| Session | `admin-dun4` |

## Steps
1. **D5 (2026-08-07), 30+ min before the window:** `mailpit-agent latest-id` → **MPC**; re-run **M**, **Q**, **L**; S must still be `arraysubs-on-hold` with no pending retry.
2. `mailpit-agent wait-new MPC 7200 "has been cancelled"` (exit 124 = no cancel → capture evidence, file an issue, do NOT force the sweep).
3. On the match, re-run **M**, **Q**, the post-status query, plus `wp action-scheduler list --group=arraysubs_renewals --status=pending --fields=ID,hook,args,scheduled_date_gmt --allow-root | grep "\[S\]"`.
4. `mailpit-agent list 20`; `show` both cancellation messages, record `To:` and subjects.
5. `agent-browser --session admin-dun4 open ".../wp-admin/post.php?post=S&action=edit"` → `snapshot -i`; screenshot **Cancelled** + notes. Then open R (`admin.php?page=wc-orders&action=edit&id=R`) and screenshot its status and notes.
6. `agent-browser --session cust-dun open ".../my-account/subscriptions/"` → `snapshot -i`; screenshot the row.
7. Check nothing else moved in the tick: `wp post list --post_type=arraysubs_data --post_status=arraysubs-cancelled --fields=ID,post_title,post_modified --allow-root`.

## Expected results
1. S is `arraysubs-cancelled` at the first hourly sweep after `max(D+96h, _on_hold_date+72h)`; record the UTC time and lag (expect < 60 min).
2. `_end_date` and `_cancelled_date` = transition time (±60s); `_cancelled_by`=`system`; `_cancellation_reason`=`overdue_payment`.
3. R is `cancelled` by `cancelPendingRenewalOrders()`; total stays `$13.00`, no refund line, no new order created.
4. NO pending action in group `arraysubs_renewals` mentions `[S]` — all legs unscheduled.
5. `_payment_retry_attempts` remains `3` — cancel does not clear retry meta; only a later success does (SLT-REF-03 §3).
6. `_next_payment_date` still `D` (no rollover); `_on_hold_date` retained.
7. Exactly TWO new emails: `subscription_cancelled` (customer) and `admin_subscription_cancelled` (admin) — different subjects.
8. No further `payment_failed` email after cancellation; no 5th charge ever fires.
9. My Account shows S **Cancelled** with no Retry Payment button.
10. Step 7 lists only `SLT `-titled rows modified in that tick.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `subscription_cancelled` | cancel sweep | `slt-fail@example.test` | `subscription #S has been cancelled` | `wait-new MPC 7200`, check `To:` |
| 2 | `admin_subscription_cancelled` | same tick | admin | `Subscription #S cancelled by` | `list 20`, admin `To:` |
| 3 | `payment_failed` **NONE EXPECTED** | after cancel | — | — | No new `Payment failed for subscription #S` after the cancel time |
| 4 | `subscription_expired` **NONE EXPECTED** | ever | — | — | Cancellation, not expiry: no `has expired` for S |

## Evidence to capture
- Screenshots `SLT-DUN-04-01-cancelled`, `-02-order-R`, `-03-myaccount`, `-04-no-pending`.
- UTC cancel time and lag; cancellation meta; both Mailpit ids with `To:`/subjects; the empty pending-action listing.

## Pass criteria
- [ ] ER1 cancelled within 60 min of the computed window, unattended
- [ ] ER2 `_cancelled_by=system`, `_cancellation_reason=overdue_payment`, dates set
- [ ] ER3 R `cancelled` at $13.00, no new order
- [ ] ER4 no pending `arraysubs_renewals` action for `[S]`
- [ ] ER5/6 retry meta retained at 3; `_next_payment_date` still `D`
- [ ] ER7/8 exactly 2 cancellation emails; no further failure mail or charge
- [ ] ER9/10 My Account Cancelled; no non-SLT data touched

## Isolation / teardown
- Closes the ladder `active → failed → 3 retries → on-hold → cancelled` in 5 days: 4 charges, 8 payment-failed emails.
- Releases `slt-fail` and `SLT Retry Daily` for `SLT-DUN-05` on D6. Leave S and R as evidence; `SLT-SETUP-99` deletes them.

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
