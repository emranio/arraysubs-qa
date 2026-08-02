---
id: 74
title: 'Segment 3 next_cycle: full charge now, first renewal exactly one cycle past segment 1''s'
status: todo
priority: critical
created: 2026-08-02T03:43:09.477309933+02:00
updated: 2026-08-02T03:43:20.19809405+02:00
tags:
    - renewal-sync
    - day-04
    - has-conflicts
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

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-13`, `SLT-CHK-08`, `SLT-CHK-13`, `SLT-SYN-11`, `SLT-SW-09`, `SLT-IMP-03`

- *Problem:* SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.
- *Required fix:* Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

**`high` · dependency-gap / unowned purchases** — with `SLT-ADM-07`, `SLT-MYA-04`, `SLT-ADM-08`, `SLT-SW-01`, `SLT-SW-03`, `SLT-SW-02`

- *Problem:* Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 time-travel renewals for month segment-1/segment-2, week segment-3 (SLT-SYN-07's tail, due 2026-08-15) and the flex-variable tail - audit C17 mandates one dedicated D8 owner and none exists. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.
- *Required fix:* Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add step 0 to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments on 2026-08-08 (D6) after 12:00 - day-in-cycle 8, past both boundaries, resolves to segment 3, next payment 2026-10-01 00:00 site = 2026-09-30 18:00 UTC'. Create SLT-TT-00 on D8 as the single time-travel owner: pre-flight pending-queue screenshot + the 13 non-SLT _next_payment_date snapshot, then the month seg1/seg2 and week seg3 renewals and the flex-variable tail, single-action-by-id only, then the post-drain non-SLT diff proof - and have SYN-10, SW-02, EML-08, EML-10 and LIFE-01 quote its snapshot instead of each taking their own.

---
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
1. From WP root re-dump the six `_arraysubs_flex_sync_*` keys for `<WEEK_ID>` to `slt-evidence/SLT-SYN-07-plan.csv`; abort unless `yes,2,5,yes,yes,yes`.
2. `agent-browser --session slt07cust open "https://mirror-help.arrayhash.com/my-account/"` → `snapshot -i`; log in as `slt-flex3`; `mailpit-agent latest-id` → `MAILID`.
3. `open ".../checkout/?add-to-cart=<WEEK_ID>"` → `snapshot -i`; screenshot `SLT-SYN-07-01-summary.png`. Total due today must read **$14.00** and the line must carry the bonus-access note naming **8 August, 2026** (`Hooks.php:483-531`); record it verbatim.
4. Confirm Paddle absent; click the Stripe radio explicitly; re-read the total (still $14.00); pay. Screenshot `SLT-SYN-07-02-received.png`; record `ORDER_ID`; `wait-new $MAILID 120 "is active"`.
5. Get `SUB_NC`; screenshot the schedule panel `SLT-SYN-07-03-schedule.png`; dump the five `_renewal_sync_*` keys + `_next_payment_date` + `_completed_payments` to `SLT-SYN-07-sub-meta.csv`; confirm no `_flex_covered_cycle_start` key; screenshot the order item mirror `SLT-SYN-07-04-item-meta.png`.
6. Record the delta: `php -r 'echo strtotime("<SUB_NC next payment> UTC")-strtotime("<ANCHOR> UTC");'` → must print `604800`.
7. Compute `k` (README crc32 one-liner); screenshot `page=action-scheduler&status=pending&s=<SUB_NC>` as `SLT-SYN-07-05-pending.png`.

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
| 1 | `new_subscription` | order paid | slt-flex3@example.test | `is active` | `wait-new $MAILID 120` |
| 2 | `admin_new_subscription` | order paid | admin_email | `New subscription #` | same |
| 3 | `renewal_invoice` NONE EXPECTED | order paid | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = a finding |

## Evidence to capture
- `SLT-SYN-07-01..05`; verbatim bonus note; `ORDER_ID`, `SUB_NC`, `k`; both files; `604800` output; console errors.

## Pass criteria
- [ ] Charge exactly $14.00, mode `next_cycle`
- [ ] `_renewal_sync_cycle_start_date` rewritten to `2026-08-07 18:00:00`
- [ ] `_next_payment_date` = `2026-08-14 18:00:00`, exactly 604800 s past SLT-SYN-05's
- [ ] Bonus-access note present, naming 8 August, 2026
- [ ] No `_flex_covered_cycle_start`; both created-mails arrived; no `renewal_invoice`

## Isolation / teardown
- Handed on: `SUB_NC` is due 2026-08-15, past D9 — it belongs to the sole authorized time-travel day (D8, 2026-08-10) and may be advanced only via its own single action id, never a bare hook drain (which would fire SLT-SYN-05/06/08's pending renewals and the 13 non-SLT subs).
- `slt-flex3` must not rebuy this product. Restores: none. Close `slt07cust`.


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
