---
id: 74
title: 'Segment 3 next_cycle: full charge now, first renewal exactly one cycle past segment 1''s'
status: done
priority: critical
created: 2026-08-02T03:43:09.477309933+02:00
updated: 2026-08-06T20:33:42.632086289+02:00
started: 2026-08-06T20:33:42.632085007+02:00
completed: 2026-08-06T20:33:42.632085007+02:00
tags:
    - renewal-sync
    - day-04
due: "2026-08-06"
estimate: 1.5h
depends_on:
    - 14
    - 8
    - 13
class: standard
---

> **SLT-SYN-07** · group `sync` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove segment-3 `next_cycle`: the full amount is charged today but buys the NEXT cycle, `_renewal_sync_cycle_start_date` is rewritten to the upcoming boundary, and `_next_payment_date` is `pushBoundaryForward()`'d one more cycle (`Hooks.php:394-414`, `SegmentPlan.php:382-404`) — 604800 s past SLT-SYN-05's.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-flex3`, created by SLT-SYN-05)
- Plugins: pro-required

## Preconditions
- SLT-SYN-05 done, its `_next_payment_date` (`2026-08-07 18:00:00` UTC) is `ANCHOR`; SLT-PROD-13 and SLT-SYN-01 done, restores proven. Global sync OFF; never run in SLT-SYN-04's bracket.
- `flex_covered_cycle_start` is a **context key only**, never persisted as meta (`Hooks.php:411`) — expect no `_flex_covered_cycle_start` row.

## Test data
Buy on **D4 = 2026-08-06** (site time) as `slt-flex3`, card `4242 4242 4242 4242`.

| Item | Value |
|---|---|
| Product | `SLT Flex Week Segments` $14.00 week/1, boundaries [2,5] |
| Cycle (+06) | 2026-08-01 → 2026-08-08; `cycle_days`=7 |
| day_in_cycle | 6 → past both boundaries → last active = **seg 3** → `next_cycle` |
| Charge today | **$14.00** (ratio 1.0, gateway minimum forced 0.0) |
| `_renewal_sync_cycle_start_date` | rewritten to `2026-08-07 18:00:00` UTC (2026-08-08 00:00 +06) |
| `_next_payment_date` | `2026-08-14 18:00:00` UTC (= **2026-08-15 00:00 +06**) |
| Delta vs `ANCHOR` | 1786730400 − 1786125600 = **604800 s = 1 week** |
| Bonus access | 2026-08-06 → 2026-08-08 free |

## Steps
1. Resolve numeric `WEEK_ID` and exact prior `ANCHOR` from the registry, record `SUBCOUNT_BEFORE`, and re-dump the six flex keys to `/home/server-manager/slt-evidence/SLT-SYN-07-plan.csv`; abort unless `yes,2,5,yes,yes,yes`.
2. `agent-browser --session slt07cust-SLT-SYN-07 open "https://mirror-help.arrayhash.com/my-account/"` → `snapshot -i`; log in as `slt-flex3`; require `/cart/` and the persistent-cart user meta empty; capture `SLT-SYN-07-00-cart-empty-before.png`; `MAILID=$(mailpit-agent latest-id)`.
3. Open `/checkout/?add-to-cart=$WEEK_ID`, capture the $14.00 summary/bonus note as `SLT-SYN-07-01-summary.png` before card entry, and record it verbatim.
4. Confirm Paddle absent, select Stripe, re-read $14.00, fill the hosted card without capturing it, pay, record numeric `ORDER_ID`, and capture the safe receipt as `SLT-SYN-07-02-received.png`. Reconcile the complete `MAILID` delta and require WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs. Prove both carts empty and capture `-02a-cart-empty-after.png`.
5. Resolve `SUB_W3` only from `ORDER_ID._subscription_ids` JSON with a strict one-element numeric guard; require reverse parent/customer/product and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE+1`. Assign `SUB_W3_NEXT=$(wp post meta get "$SUB_W3" _next_payment_date --allow-root)`, abort unless it is one exact UTC datetime, and use only that assigned value in step 6. In `admin-SLT-SYN-07`, capture exact schedule as `-03-schedule.png` and order-item mirror as `-04-item-meta.png`; dump sync/date/payment meta and confirm no `_flex_covered_cycle_start`.
6. Compute delta with `php -r 'echo strtotime($argv[1]." UTC")-strtotime($argv[2]." UTC");' "$SUB_W3_NEXT" "$ANCHOR"`; require `604800`.
7. Compute k with the README argv command; query/capture exact reminder/invoice/charge rows as `SLT-SYN-07-05-pending.png`, and publish their IDs/gates plus each future baseline deadline to the registry/D04 report for `SLT-TT-00`. If any live amount/sync/date/gateway assertion fails, create a standalone issue with task/plan, order/subscription/product/user/action IDs and contexts, reproduction, expected/actual, UI/meta/mail proof, and SYN-05/06 counterexamples; never add a kanban bug card. Close only `slt07cust-SLT-SYN-07` and `admin-SLT-SYN-07`, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Mode `next_cycle`; `_renewal_sync_initial_recurring_amount=14`; order total exactly `$14.00`, no tax — full price despite only 2 days left in the cycle.
2. `_renewal_sync_cycle_start_date = 2026-08-07 18:00:00` — **rewritten forward**, unlike SLT-SYN-05/06 where it stayed `2026-07-31 18:00:00`.
3. `_next_payment_date = _renewal_sync_first_full_renewal_date = 2026-08-14 18:00:00`, and minus `ANCHOR` = `604800` s exactly — one full week/1 cycle, not 6 or 8 days.
4. Checkout showed the bonus note naming 8 August, 2026; SLT-SYN-05/06 showed none.
5. `_completed_payments=1`; sub `arraysubs-active`; order `processing`/`completed`.
6. Pending: invoice at `2026-08-14 18:00:00 +k −6h`, `arraysubs_process_renewal` at `+k`, reminder `2026-08-11 18:00:00 +k` — windows, not points.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` | order paid | slt-flex3@example.test | `is active` | `mailpit-agent wait-new "$MAILID" 120` |
| 2 | `admin_new_subscription` | order paid | admin_email | `New subscription #` | same |
| 2a | WC paid-order + WC New order | order paid | customer + admin | exact order / `New order #` | complete `MAILID` delta; save/show both exact IDs |
| 3 | `renewal_invoice` NONE EXPECTED | order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = a finding |

## Evidence to capture
- `SLT-SYN-07-00` through `-05`; count/bidirectional linkage, verbatim bonus note, order/sub/user/action IDs, k/deadlines, four mail IDs, both files, delta output, sessions/review, console/network errors.

## Pass criteria
- [ ] Charge exactly $14.00, mode `next_cycle`
- [ ] `_renewal_sync_cycle_start_date` rewritten to `2026-08-07 18:00:00`
- [ ] `_next_payment_date` = `2026-08-14 18:00:00`, exactly 604800 s past SLT-SYN-05's
- [ ] Bonus-access note present, naming 8 August, 2026
- [ ] No `_flex_covered_cycle_start`; both created-mails arrived; no `renewal_invoice`
- [ ] Complete four-message checkout set and D8 action handoff recorded; exact sessions closed and card reviewed to done

## Isolation / teardown
- Handed on: `SUB_W3` is due 2026-08-15, past D9 — it belongs to the sole authorized time-travel day (D8, 2026-08-10) and may be advanced only via its own single action id, never a bare hook drain (which would fire SLT-SYN-05/06/08's pending renewals and the 13 non-SLT subs).
- `slt-flex3` must not rebuy this product. Restores: none. Cart and persistent-cart meta must be empty before closing only `slt07cust-SLT-SYN-07`.


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

[[2026-08-06]] Thu 20:15
Missed-window note: not started before the D4 site-local rollover to 2026-08-07 00:14 +06. Do not backfill this as if it were still same-day D4 execution; keep in todo until a valid reschedule/next-day decision is made.

[[2026-08-06]] Thu 20:33
UNVERIFIED closeout on 2026-08-06: this D4 same-day execution window was missed after the site-local rollover into 2026-08-07. The card is closed rather than carried forward as if its original dated setup and downstream timings were still valid.
