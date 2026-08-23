---
id: 106
title: 'Renewal execution after a synced first charge: second charge full on the boundary, third on the grid'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal-sync
    - day-07
due: "2026-08-30"
estimate: 2h
depends_on:
    - 14
    - 45
    - 28
class: standard
---

> **SLT-SYN-09** · group `sync` · scheduled **D07** (2026-08-30)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove a synced first charge does not distort the schedule: the SECOND charge is the FULL recurring amount on the boundary (even where #1 was prorated to $6.00), and the THIRD stays on the grid because the next due derives from `_renewal_scheduled_date`, not payment time (`OrderIntegration.php:1629-1652` → `:1472-1526`).

## Scope
- Gateway: Stripe test
- Checkout: N/A (unattended renewals)
- Account: existing `slt2-flex` + `slt2-flex2`
- Plugins: pro-required

## Preconditions
- SLT-SYN-05 (`SUB_W1`, week seg-1, #1 $14.00), SLT-SYN-06 (`SUB_W`, week seg-2, #1 $6.00), SLT-SYN-08 (`SUB_2SEG`, `SLT2 Flex Daily Two Seg`, #1 $9.00) done, `k` recorded.
- The preceding unattended-watch phases must publish four immutable, task-specific Mailpit baselines inside each live action's final five-minute window: `SYN09_2SEG_D4_PRE`, `SYN09_W1_D6_PRE`, `SYN09_W_D6_PRE`, and `SYN09_2SEG_D7_PRE`. No clock time is hard-coded. If one is missing, create/update the prerequisite issue and keep this card blocked; do not substitute a recent-message list.
- **Act on D7 = 2026-08-30 after 07:00 site.** Renewals fire at boundary + `k` (0–6 h), so an earlier read proves nothing.
- **Nothing may be force-run.** A renewal not fired by `boundary + k + 15 min` is a real bug — capture evidence and create or update the mandatory `qa/issues/` kanban card; leave the lifecycle task blocked; a bare `--hooks=` drain is forbidden.

## Test data
| Sub | #1 | #2 due UTC / amount | #3 due UTC / amount |
|---|---|---|---|
| `SUB_W1` week seg-1 | $14.00 `full` | `08-28 18:00` +k / **$14.00** | `09-04 18:00`, past D9 / $14.00 |
| `SUB_W` week seg-2 | $6.00 `prorate` | `08-28 18:00` +k / **$14.00** | `09-04 18:00`, past D9 / $14.00 |
| `SUB_2SEG` day/3 two-seg | $9.00 | `08-26 18:00` +k, fired D4 / **$9.00** | `08-29 18:00` +k / **$9.00** |

`SUB_2SEG` after #3: `_next_payment_date = 2026-09-01 18:00:00` (2026-09-02 00:00 +06) — exactly 3 days, no drift to the payment clock.

## Steps
1. Resolve registry aliases `SUB_W1`, `SUB_W`, and `SUB_2SEG` into same-named shell variables; require exactly one registry match for each, abort unless all three match `^[0-9]+$` and are distinct, then cross-check their recorded parent order, customer, and product relationships. Recompute `k` from each numeric ID with the README argv-based crc32 one-liner; write the window `[boundary+k, +15min]` into the notes BEFORE looking at results.
2. Per sub open the exact numeric subscription-filtered completed actions in `admin-SLT-SYN-09-D7`; capture uniquely named `SLT-SYN-09-01-completed-<alias>.png`; confirm the exact invoice/charge action IDs, gates, and `via WP Cron` logs.
3. For every expected cycle, resolve the renewal order from numeric subscription plus `_renewal_scheduled_date`/cycle and require reverse linkage, never customer recency. Capture uniquely named `SLT-SYN-09-02-orders-<alias>-<cycle>.png` and record total, status, `_is_renewal_order`, cycle number, scheduled date, and order-mail ID.
4. Per sub dump `_next_payment_date`, `_last_payment_date`, `_completed_payments`, `_pending_renewal_order_id`, `_payment_retry_attempts` to `/home/server-manager/slt-evidence/SLT-SYN-09-after.csv`; screenshot each schedule panel `SLT-SYN-09-03-sched.png`.
5. Consume the four registered baselines separately. For each, inspect every newer message and require exactly one subject tied to the expected subscription: `SUB_2SEG` after `SYN09_2SEG_D4_PRE`, `SUB_W1` after `SYN09_W1_D6_PRE`, `SUB_W` after `SYN09_W_D6_PRE`, and `SUB_2SEG` after `SYN09_2SEG_D7_PRE`. Save/show each exact `Payment received for subscription #<id>` match and classify its complete baseline delta; confirm no `Payment failed`, `on hold`, or `Invoice for subscription` in those deltas. This is **four renewal-success messages for four renewal events**, even though only three distinct subscriptions are involved.
6. Re-open each pending queue; screenshot `SLT-SYN-09-04-pending.png`; confirm re-queued legs sit at the NEW due + SAME `k`.
7. Publish the exact D10 charge action/gate and `charge−300s` deadline, then close `admin-SLT-SYN-09-D7`. Inside the D9 watch's exact `[charge−300s,charge)` interval save immutable `SYN09_2SEG_D10_PRE` with its action ID. Follow-up on **D10 = 2026-09-02**: poll in repeated calls no longer than 60 seconds through the 10-minute cutoff, require/save/show the exact payment-success match, and resolve linked #4 from numeric SUB_2SEG plus scheduled-cycle relationship/reverse link. In fresh `admin-SLT-SYN-09-D10` capture the order/action proof, verify the due/next-date grid, close it, independently review all five renewal events, then move through `review` to `done` with Review empty. Any defect goes only in `qa/issues/` kanban card named `SLT-SYN-09-<concise-slug>` with task/stage/plan path; product/customer/subscription/parent/renewal/action/message IDs; user login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and UI/meta/queue/log/order/Mailpit proof.

## Expected results
1. `SUB_W` charge #2 is exactly **$14.00**, not $6.00 — proration hit the signup only; `_renewal_sync_initial_recurring_amount` stays `6`, never reused.
2. `SUB_W1` #2 is exactly **$14.00**; both week renewal orders carry `_renewal_scheduled_date = 2026-08-28 18:00:00`.
3. Both week subs: `_next_payment_date = 2026-09-04 18:00:00`, `_completed_payments = 2`, `arraysubs-active`, orders paid, `_pending_renewal_order_id` cleared.
4. `SUB_2SEG`: #2 `$9.00` at `2026-08-26 18:00:00 +k`, #3 `$9.00` at `2026-08-29 18:00:00 +k`, `_completed_payments = 3`, `_next_payment_date = 2026-09-01 18:00:00`; consecutive dues exactly 259200 s apart — the grid holds.
5. Every renewal fired inside `[due+k, due+k+15min]`, and the same `k` is reused for the re-queued legs (the offset is permanent per sub). No retries, no on-hold, no failed orders, no tax lines.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_successful` ×4 by D7: `SUB_2SEG` D4 and D7, `SUB_W1` D6, `SUB_W` D6 | renewal ok | slt2-flex / slt2-flex2 | `Payment received for subscription #<exact ID>` | four distinct registered pre-event baselines; exact match plus full delta for each |
| follow-up | `payment_successful` ×1 for `SUB_2SEG` #4 | D10 renewal ok | slt2-flex2 | `Payment received for subscription #<SUB_2SEG>` | `SYN09_2SEG_D10_PRE`; exact match plus full delta |
| 2 | `renewal_invoice` NONE EXPECTED | invoice leg | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = bug |
| 3 | `payment_failed`/`subscription_on_hold` NONE EXPECTED | — | — | — | absent from each complete registered owner delta; presence tied to an exact target is failure |

## Evidence to capture
- `SLT-SYN-09-01..04`; `-after.csv`; renewal order IDs and totals; the three `k` values and windows; all five registered baseline values and their pending action IDs; exact-match/full-delta Mailpit ids; any failed AS rows.

## Pass criteria
- [ ] `SUB_W` charge #2 is $14.00, not $6.00
- [ ] Both week subs land #2 at `2026-08-28 18:00:00 +k`, next due `2026-09-04 18:00:00`
- [ ] `SUB_2SEG` #2/#3 are $9.00, next due `2026-09-01 18:00:00`, dues 259200 s apart, same `k`
- [ ] Four bounded `payment_successful` mails through D7; no invoice, failed or on-hold mail
- [ ] D10 `SUB_2SEG` #4 and its bounded payment-success mail proved before closing the card
- [ ] Nothing was force-run
- [ ] Every order/action is relationship-exact, phase sessions close, and D10 review reaches `done` with Review empty

## Isolation / teardown
- Handed on: `SUB_2SEG` (due 2026-09-02 site, then 2026-09-05) and both week subs (due 2026-09-04) stay alive into the watch tail — they must NOT be cancelled by the D11 restore (plan-audit's SLT-SETUP-99 split).
- Restores: none; read-only. Close only the exact D7/D10 admin sessions named above.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
