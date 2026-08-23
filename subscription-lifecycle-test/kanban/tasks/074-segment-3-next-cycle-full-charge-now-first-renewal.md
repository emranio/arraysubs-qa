---
id: 74
title: 'Segment 3 next_cycle: full charge now, first renewal exactly one cycle past segment 1''s'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal-sync
    - day-04
due: "2026-08-27"
estimate: 1.5h
depends_on:
    - 14
    - 8
    - 13
class: standard
---

> **SLT-SYN-07** · group `sync` · scheduled **D04** (2026-08-27)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove segment-3 `next_cycle`: the full amount is charged today but buys the NEXT cycle, `_renewal_sync_cycle_start_date` is rewritten to the upcoming boundary, and `_next_payment_date` is `pushBoundaryForward()`'d one more cycle (`Hooks.php:394-414`, `SegmentPlan.php:382-404`) — 604800 s past SLT-SYN-05's.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt2-flex3`, created by SLT-SYN-05)
- Plugins: pro-required

## Preconditions
- SLT-SYN-05 done, its `_next_payment_date` (`2026-08-28 18:00:00` UTC) is `ANCHOR`; SLT-PROD-13 and SLT-SYN-01 done, restores proven. Global sync OFF; never run in SLT-SYN-04's bracket.
- `flex_covered_cycle_start` is a **context key only**, never persisted as meta (`Hooks.php:411`) — expect no `_flex_covered_cycle_start` row.

## Test data
Buy on **D4 = 2026-08-27** (site time) as `slt2-flex3`, card `4242 4242 4242 4242`.

| Item | Value |
|---|---|
| Product | `SLT2 Flex Week Segments` $14.00 week/1, boundaries [2,5] |
| Cycle (+06) | 2026-08-22 → 2026-08-29; `cycle_days`=7 |
| day_in_cycle | 6 → past both boundaries → last active = **seg 3** → `next_cycle` |
| Charge today | **$14.00** (ratio 1.0, gateway minimum forced 0.0) |
| `_renewal_sync_cycle_start_date` | rewritten to `2026-08-28 18:00:00` UTC (2026-08-29 00:00 +06) |
| `_next_payment_date` | `2026-09-04 18:00:00` UTC (= **2026-09-05 00:00 +06**) |
| Delta vs `ANCHOR` | 1786730400 − 1786125600 = **604800 s = 1 week** |
| Bonus access | 2026-08-27 → 2026-08-29 free |

## Steps
1. Resolve numeric `WEEK_ID` and exact prior `ANCHOR` from the registry, record `SUBCOUNT_BEFORE`, and re-dump the six flex keys to `/home/server-manager/slt-evidence/SLT-SYN-07-plan.csv`; abort unless `yes,2,5,yes,yes,yes`.
2. `agent-browser --session slt07cust-SLT-SYN-07 open "https://mirror-help.arrayhash.com/my-account/"` → `snapshot -i`; log in as `slt2-flex3`; require `/cart/` and the persistent-cart user meta empty; capture `SLT-SYN-07-00-cart-empty-before.png`; `MAILID=$(mailpit-agent latest-id)`.
3. Open `/checkout/?add-to-cart=$WEEK_ID`, capture the $14.00 summary/bonus note as `SLT-SYN-07-01-summary.png` before card entry, and record it verbatim.
4. Confirm Paddle absent, select Stripe, re-read $14.00, fill the hosted card without capturing it, pay, record numeric `ORDER_ID`, and capture the safe receipt as `SLT-SYN-07-02-received.png`. Reconcile the complete `MAILID` delta and require WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs. Prove both carts empty and capture `-02a-cart-empty-after.png`.
5. Resolve `SUB_W3` only from `ORDER_ID._subscription_ids` JSON with a strict one-element numeric guard; require reverse parent/customer/product and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE+1`. Assign `SUB_W3_NEXT=$(wp post meta get "$SUB_W3" _next_payment_date --allow-root)`, abort unless it is one exact UTC datetime, and use only that assigned value in step 6. In `admin-SLT-SYN-07`, capture exact schedule as `-03-schedule.png` and order-item mirror as `-04-item-meta.png`; dump sync/date/payment meta and confirm no `_flex_covered_cycle_start`.
6. Compute delta with `php -r 'echo strtotime($argv[1]." UTC")-strtotime($argv[2]." UTC");' "$SUB_W3_NEXT" "$ANCHOR"`; require `604800`.
7. Compute k with the README argv command; query/capture exact reminder/invoice/charge rows as `SLT-SYN-07-05-pending.png`, and publish their IDs/gates plus each future baseline deadline to the registry/D04 report for `SLT-TT-00`. If any live amount/sync/date/gateway assertion fails, create a dedicated issue with task/plan, order/subscription/product/user/action IDs and contexts, reproduction, expected/actual, UI/meta/mail proof, and SYN-05/06 counterexamples; create or update the mandatory `qa/issues/` kanban card. Close only `slt07cust-SLT-SYN-07` and `admin-SLT-SYN-07`, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Mode `next_cycle`; `_renewal_sync_initial_recurring_amount=14`; order total exactly `$14.00`, no tax — full price despite only 2 days left in the cycle.
2. `_renewal_sync_cycle_start_date = 2026-08-28 18:00:00` — **rewritten forward**, unlike SLT-SYN-05/06 where it stayed `2026-08-21 18:00:00`.
3. `_next_payment_date = _renewal_sync_first_full_renewal_date = 2026-09-04 18:00:00`, and minus `ANCHOR` = `604800` s exactly — one full week/1 cycle, not 6 or 8 days.
4. Checkout showed the bonus note naming 29 August 2026; SLT-SYN-05/06 showed none.
5. `_completed_payments=1`; sub `arraysubs-active`; order `processing`/`completed`.
6. Pending: invoice at `2026-09-04 18:00:00 +k −6h`, `arraysubs_process_renewal` at `+k`, reminder `2026-09-01 18:00:00 +k` — windows, not points.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` | order paid | slt2-flex3@example.test | `is active` | `mailpit-agent wait-new "$MAILID" 120` |
| 2 | `admin_new_subscription` | order paid | admin_email | `New subscription #` | same |
| 2a | WC paid-order + WC New order | order paid | customer + admin | exact order / `New order #` | complete `MAILID` delta; save/show both exact IDs |
| 3 | `renewal_invoice` NONE EXPECTED | order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = a finding |

## Evidence to capture
- `SLT-SYN-07-00` through `-05`; count/bidirectional linkage, verbatim bonus note, order/sub/user/action IDs, k/deadlines, four mail IDs, both files, delta output, sessions/review, console/network errors.

## Pass criteria
- [ ] Charge exactly $14.00, mode `next_cycle`
- [ ] `_renewal_sync_cycle_start_date` rewritten to `2026-08-28 18:00:00`
- [ ] `_next_payment_date` = `2026-09-04 18:00:00`, exactly 604800 s past SLT-SYN-05's
- [ ] Bonus-access note present, naming 29 August 2026
- [ ] No `_flex_covered_cycle_start`; both created-mails arrived; no `renewal_invoice`
- [ ] Complete four-message checkout set and D8 action handoff recorded; exact sessions closed and card reviewed to done

## Isolation / teardown
- Handed on: `SUB_W3` is due 2026-09-05, past D9 — it belongs to the sole authorized time-travel day (D8, 2026-08-31) and may be advanced only via its own single action id, never a bare hook drain (which would fire SLT-SYN-05/06/08's pending renewals and the 13 non-SLT2 subs).
- `slt2-flex3` must not rebuy this product. Restores: none. Cart and persistent-cart meta must be empty before closing only `slt07cust-SLT-SYN-07`.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
