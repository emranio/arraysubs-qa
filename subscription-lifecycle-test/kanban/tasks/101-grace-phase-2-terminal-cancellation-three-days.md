---
id: 101
title: 'Grace phase 2: terminal cancellation three days after the hold, with customer and admin cancel emails'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - day-07
due: "2026-08-30"
estimate: 1h
depends_on:
    - 81
    - 66
class: standard
---

> **SLT-DUN-04** · group `renewal` · scheduled **D07** (2026-08-30)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove grace phase 2: with `grace_days_before_cancel = 3`, S is cancelled by the hourly sweep at `max(D + 4 days, _on_hold_date + 3 days)`, unpaid order R is cancelled, every Action Scheduler leg for `[S]` is unscheduled, cancellation meta is stamped, and both `subscription_cancelled` and `admin_subscription_cancelled` fire.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt2-fail`)
- Plugins: both

## Preconditions
- `SLT-DUN-01/02/03` done. S `arraysubs-on-hold`, retries exhausted (attempts = 3, none pending), R still `failed`, `_next_payment_date` still `D`, `_on_hold_date` recorded.
- Both gates must pass: SQL cutoff `_next_payment_date < now − 4 days` AND per-row `now ≥ _on_hold_date + 3 days` (SLT-REF-03 §4). With the hold at ≈ `D+24h` both land ≈ `D + 4 days` → **2026-08-30, ≈ 14:00-16:00 site**.
- Never run `wp action-scheduler run` (C07); this natural cancellation is never forced.

## Test data
| Item | Value |
|---|---|
| Subscription | S (on-hold), order R (`failed`) |
| Cancel window | first sweep after `max(D+96h, _on_hold_date+72h)` |
| Expected meta | `_cancelled_by=system`, `_cancellation_reason=overdue_payment` |
| Sessions | `admin-dun4-SLT-DUN-04`, `customer-SLT-DUN-04` |

## Steps
1. Resolve registry aliases `S_FAIL` and its exact failed renewal order into numeric shell variables `S` and `R`; abort unless both are numeric and bidirectionally linked. Compute `ELIGIBLE_AT=max(D+96h,_on_hold_date+72h)`, query the exact next pending hourly cancellation-sweep row after that instant, and record its action ID/GMT without running it. Save a complete ID/status/modified snapshot of all subscription posts as the before-state, then re-run **M**, **Q**, **L**; numeric `$S` must still be `arraysubs-on-hold` with no pending retry. Inside exact `[eligible sweep−300s, eligible sweep)` set `MPC=$(mailpit-agent latest-id)` and publish it as **`DUN_CANCEL_PRE`** with UTC capture time for SLT-EML-04; never capture it 30+ minutes early.
2. Poll immutable `MPC` in repeated calls no longer than 60 seconds through the exact eligible sweep plus five minutes for `subscription #$S has been cancelled`. Only absence after that final cutoff is a finding: capture the unchanged exact action/subscription/order/mail state, write the dedicated issue, and do NOT force the sweep.
3. On the match, re-run **M**, **Q**, the post-status query, plus `wp db query "SELECT a.action_id,a.hook,a.status,a.scheduled_date_gmt,a.args FROM wp_actionscheduler_actions a JOIN wp_actionscheduler_groups g ON g.group_id=a.group_id WHERE g.slug='arraysubs-renewals' AND a.status='pending' AND JSON_UNQUOTE(JSON_EXTRACT(a.args,'\$[0]'))='$S' ORDER BY a.scheduled_date_gmt,a.action_id;" --allow-root`.
4. Inspect every message newer than `MPC`; require exactly one customer cancellation subject naming `S` and exactly one admin cancellation subject naming `S`, `show` both, and record `To:` and subjects. Classify unrelated mail instead of relying on a fixed recent-message count.
5. `agent-browser --session admin-dun4-SLT-DUN-04 open ".../wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions"` → `snapshot -i`; search exact numeric ID `$S`, open **View Details**, and screenshot **Cancelled** + notes. Then open the recorded numeric renewal order R (`admin.php?page=wc-orders&action=edit&id=<R>`) and screenshot its status and notes. Do not use a legacy `post.php` subscription route.
6. `agent-browser --session customer-SLT-DUN-04 open ".../my-account/subscriptions/"` → `snapshot -i`; screenshot the row.
7. Save the same complete ID/status/modified snapshot as the after-state and diff it against step 1. Require the target `$S` transition exactly once. Cross-check every other changed SLT-registry ID against its owning task; classify unrelated shared-site changes separately rather than pretending a historical all-cancelled list proves this tick changed only SLT2 data. Close both exact sessions, independently review the gate/action/order/meta/mail/UI evidence, move the card through `review` to `done` with Review empty, and hand the completed cancellation to `SLT-MYA-05` follow-up C. Any failure goes only in `qa/issues/` kanban card named `SLT-DUN-04-<concise-slug>` with task/stage/plan path; subscription/order/sweep/action/message IDs; user ID/login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and DB/meta/queue/log/UI/Mailpit proof.

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
10. The before/after snapshot identifies the exact `$S` transition; no other SLT-registry subscription moved without an authored owner, and unrelated concurrent changes are preserved separately.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `subscription_cancelled` | exact eligible cancel sweep | `slt2-fail@example.test` | `subscription #S has been cancelled` | final-five-minute `MPC`; repeated ≤60-second polls through sweep+5 min; check `To:` |
| 2 | `admin_subscription_cancelled` | same tick | admin | `Subscription #S cancelled by` | complete `MPC` delta, exact subscription id and admin `To:` |
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
- [ ] ER9/10 My Account Cancelled; no non-SLT2 data touched
- [ ] Exact sessions close and independent review reaches `done` with Review empty

## Isolation / teardown
- Closes the ladder `active → failed → 3 retries → on-hold → cancelled` in 5 days: 4 charges, 8 payment-failed emails.
- Hands the closed cancellation evidence first to `SLT-MYA-05` follow-up C. Only after that read-only role check closes may `SLT-DUN-05` reuse `slt2-fail` and `SLT2 Retry Daily`. Leave S and R as evidence; `SLT-SETUP-99B` deletes them.
- Close only `admin-dun4-SLT-DUN-04` and `customer-SLT-DUN-04`; preserve unrelated browser sessions.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
