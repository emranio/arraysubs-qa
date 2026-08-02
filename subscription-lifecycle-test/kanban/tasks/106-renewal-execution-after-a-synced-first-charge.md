---
id: 106
title: 'Renewal execution after a synced first charge: second charge full on the boundary, third on the grid'
status: todo
priority: critical
created: 2026-08-02T03:43:11.848514479+02:00
updated: 2026-08-02T03:43:23.232258228+02:00
tags:
    - renewal-sync
    - day-07
    - has-conflicts
due: "2026-08-09"
estimate: 2h
depends_on:
    - 14
    - 45
    - 28
class: standard
---

> **SLT-SYN-09** · group `sync` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · dependency-inversion / date contradiction** — with `SLT-SYN-08`, `SLT-PROD-14`, `SLT-SYN-01`, `SLT-EML-01`

- *Problem:* SLT-SYN-08 is tagged d0 and buys SLT Flex Daily Two Seg + SLT Flex Daily Next Cycle, but SLT-PROD-14 creates those products on D1 in the corrected calendar and audit C10 forbids purchasing a flex product before SLT-SYN-01's destructive meta surgery has run and been restored. Worse, SYN-08's stated dates encode a D0 purchase (cycle_start 2026-08-01 18:00 UTC, Two Seg next payment 08-04 18:00 UTC) while SLT-EML-01 - which owns the only reachable renewal_reminder in the window - encodes a D1 purchase (SUB_2SEG due 2026-08-06 00:00 site, SUB_NC due 2026-08-09 00:00 site, reminder fires 08-06 00:00-06:00 = watch D4). Both cannot be true and neither product can be bought twice by the same account.
- *Required fix:* SLT-SYN-08 moves to D1 (2026-08-03), purchases after 12:00, strictly after SLT-PROD-14 and after SLT-SYN-01B's restore is proven. That makes EML-01's numbers correct as written (SUB_2SEG due 08-06 00:00 site, SUB_NC due 08-09 00:00 site, reminder 08-06 00:00-06:00 site, watch D4) and SYN-08's own Test data must be recomputed to cycle_start 2026-08-02 18:00 UTC, Two Seg next payment 2026-08-05 18:00 UTC, Next Cycle cycle_start rewritten to 2026-08-05 18:00 UTC and next payment 2026-08-08 18:00 UTC. Knock-on: SLT-SYN-09's SUB_A row is now wrong (it assumes #2 at 08-04 18:00 and #3 at 08-07 18:00). Move SLT-SYN-09 from D6 to D7 (2026-08-09 morning) where the week pair's 08-08 00:00 renewals AND SUB_A's #2 at 08-09 00:00 are both already visible; hand SUB_A's #3 (08-12 00:00) to watch D10 as a grid assertion.

**`critical` · evidence-destruction / teardown vs watch window** — with `SLT-SETUP-99`, `SLT-CHK-14`, `SLT-CHK-13`, `SLT-EML-14`, `SLT-SYN-13`, `SLT-SYN-12`

- *Problem:* SLT-SETUP-99 is authored as a single d10 task that cancels AND permanently deletes every SLT subscription, order, product, coupon, page and user. With D10 = 2026-08-12 and the watch running to D12 = 2026-08-14, that deletes exactly the evidence D11 and D12 exist to collect. Events after D10: SUB_W1 + SUB_W (both week flex subs) renew 2026-08-14 00:00 site - the last scheduled events in the whole window and SYN-09's 'second charge full on the boundary' proof; the SLT-SYN-04 globally-synced day/3 subscription renews 08-14; SLT-SYN-13's Full and Next Cycle variations renew 08-13; SLT-CHK-13's Box Daily renews 08-12; SLT-CHK-14's lifetime negative control must be asserted on all 12 watch days including 08-13 and 08-14 (its own isolation note wrongly says '99A/99B'); SLT-EML-14 step 9 mandates a delta sweep on the morning of 08-14 and explicitly states 99B must not run before it, because a cancellation mail would contaminate the silence proof.
- *Required fix:* Split, as audit C06 directs, with the dates shifted +1. SLT-SETUP-99A on D10 (2026-08-12), after that morning's watch read and after SLT-DUN-05's recovery evidence is closed: Part 1 settings restore (five booleans, empty jq diff) plus cancellation of the COMPLETED-EVIDENCE COHORT ONLY - the day/1 workhorses (SLT Daily Core spine and its clones, Signup Fee Daily, Renewal Price Step, Paddle Daily, plan-ladder rungs, Free Signup Daily, Trial Four Day, Variable tiers, all CPN and CHK day/1 subs, IMP-03 concurrency subs, DUN-05's S2). No deletions. SLT-SETUP-99B on 2026-08-15 (Sat), strictly after the D12 watch report and SLT-EML-14's 08-14 delta are written: cancel the TAIL COHORT (both week flex subs, Sync Global Daily, SYN-13's two variation subs, SYN-12's two probes, SYN-14's qty sub, Box Daily, the lifetime controls, the flex month subs) then Parts 2-4 deletion. Correct SLT-CHK-14's and SLT-CHK-13's isolation notes to name 99B only. Publish the two cohort lists to the registry on D9 so the watcher can assert on D11/D12 that every 99A-cancelled subscription shows no renewal after its cancellation timestamp.

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
Prove a synced first charge does not distort the schedule: the SECOND charge is the FULL recurring amount on the boundary (even where #1 was prorated to $6.00), and the THIRD stays on the grid because the next due derives from `_renewal_scheduled_date`, not payment time (`OrderIntegration.php:1629-1652` → `:1472-1526`).

## Scope
- Gateway: Stripe test
- Checkout: N/A (unattended renewals)
- Account: existing `slt-flex` + `slt-flex2`
- Plugins: pro-required

## Preconditions
- SLT-SYN-05 (`SUB_W1`, week seg-1, #1 $14.00), SLT-SYN-06 (`SUB_W`, week seg-2, #1 $6.00), SLT-SYN-08 (`SUB_A`, `SLT Flex Daily Two Seg`, #1 $9.00) done, `k` recorded.
- **Act on D6 = 2026-08-08 after 07:00 site.** Renewals fire at boundary + `k` (0–6 h), so an earlier read proves nothing.
- **Nothing may be force-run.** A renewal not fired by `boundary + k + 15 min` is a real bug — capture evidence and file it first; a bare `--hooks=` drain is forbidden.

## Test data
| Sub | #1 | #2 due UTC / amount | #3 due UTC / amount |
|---|---|---|---|
| `SUB_W1` week seg-1 | $14.00 `full` | `08-07 18:00` +k / **$14.00** | `08-14 18:00`, past D9 / $14.00 |
| `SUB_W` week seg-2 | $6.00 `prorate` | `08-07 18:00` +k / **$14.00** | `08-14 18:00`, past D9 / $14.00 |
| `SUB_A` day/3 two-seg | $9.00 | `08-04 18:00` +k, fired D3 / **$9.00** | `08-07 18:00` +k / **$9.00** |

`SUB_A` after #3: `_next_payment_date = 2026-08-10 18:00:00` (2026-08-11 00:00 +06) — exactly 3 days, no drift to the payment clock.

## Steps
1. Recompute `k` for all three subs (README crc32 one-liner); write the window `[boundary+k, +15min]` into the notes BEFORE looking at results.
2. Per sub open `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=action-scheduler&status=complete&s=<SUBID>` (`--session admin`); screenshot `SLT-SYN-09-01-completed.png`; confirm `arraysubs_generate_renewal_invoice` at `due +k −6h`, `arraysubs_process_renewal` at `due +k`.
3. Open `admin.php?page=wc-orders` per customer; screenshot `SLT-SYN-09-02-orders.png`; per renewal order record total, status, `_is_renewal_order`, `_renewal_cycle_number`, `_renewal_scheduled_date`.
4. Per sub dump `_next_payment_date`, `_last_payment_date`, `_completed_payments`, `_pending_renewal_order_id`, `_payment_retry_attempts` to `slt-evidence/SLT-SYN-09-after.csv`; screenshot each schedule panel `SLT-SYN-09-03-sched.png`.
5. `mailpit-agent list 50`; confirm one `Payment received for subscription #<id>` per renewal, record ids, and confirm no `Payment failed`, `on hold`, `Invoice for subscription`.
6. Re-open each pending queue; screenshot `SLT-SYN-09-04-pending.png`; confirm re-queued legs sit at the NEW due + SAME `k`.
7. Follow-up on **D9 = 2026-08-11**: `SUB_A` #4 lands at `2026-08-10 18:00:00 +k`, leaving `_next_payment_date = 2026-08-13 18:00:00`. Record it in that watch report.

## Expected results
1. `SUB_W` charge #2 is exactly **$14.00**, not $6.00 — proration hit the signup only; `_renewal_sync_initial_recurring_amount` stays `6`, never reused.
2. `SUB_W1` #2 is exactly **$14.00**; both week renewal orders carry `_renewal_scheduled_date = 2026-08-07 18:00:00`.
3. Both week subs: `_next_payment_date = 2026-08-14 18:00:00`, `_completed_payments = 2`, `arraysubs-active`, orders paid, `_pending_renewal_order_id` cleared.
4. `SUB_A`: #2 `$9.00` at `2026-08-04 18:00:00 +k`, #3 `$9.00` at `2026-08-07 18:00:00 +k`, `_completed_payments = 3`, `_next_payment_date = 2026-08-10 18:00:00`; consecutive dues exactly 259200 s apart — the grid holds.
5. Every renewal fired inside `[due+k, due+k+15min]`, and the same `k` is reused for the re-queued legs (the offset is permanent per sub). No retries, no on-hold, no failed orders, no tax lines.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_successful` ×3 on D6 (+1 on D3) | renewal ok | slt-flex / slt-flex2 | `Payment received for subscription #` | `list 50`, by sub id |
| 2 | `renewal_invoice` NONE EXPECTED | invoice leg | — | — | suppressed for auto-pay subs (REF-04 §4); arrival = bug |
| 3 | `payment_failed`/`subscription_on_hold` NONE EXPECTED | — | — | — | absent from `list 50`; presence = failure |

## Evidence to capture
- `SLT-SYN-09-01..04`; `-after.csv`; renewal order IDs and totals; the three `k` values and windows; Mailpit ids; any failed AS rows.

## Pass criteria
- [ ] `SUB_W` charge #2 is $14.00, not $6.00
- [ ] Both week subs land #2 at `2026-08-07 18:00:00 +k`, next due `2026-08-14 18:00:00`
- [ ] `SUB_A` #2/#3 are $9.00, next due `2026-08-10 18:00:00`, dues 259200 s apart, same `k`
- [ ] Three `payment_successful` mails; no invoice, failed or on-hold mail
- [ ] Nothing was force-run

## Isolation / teardown
- Handed on: `SUB_A` (due 2026-08-11) and both week subs (due 2026-08-14) stay alive into the watch tail — they must NOT be cancelled by the D10 wind-down (plan-audit's SLT-SETUP-99 split).
- Restores: none; read-only. Close the admin session.


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
