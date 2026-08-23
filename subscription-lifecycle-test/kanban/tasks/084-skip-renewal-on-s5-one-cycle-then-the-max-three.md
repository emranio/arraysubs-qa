---
id: 84
title: 'SKIP renewal on S5: one cycle, max-three clamp, undo, notifications and shifted charge'
status: todo
priority: medium
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - day-05
due: "2026-08-28"
estimate: 2h
depends_on:
    - 19
class: standard
---

> **SLT-LIFE-03** · group `renewal` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Skip renewals on S5 for one cycle and then the maximum three. Prove date math, both-leg rescheduling, undo, clamping, customer skip/undo notifications, original-gate silence and one successful charge at the shifted gate. Revalidate this build without carrying forward the previous missing-email observation.

## Scope
- Gateway: Stripe test (the skip takes no payment)
- Checkout: N/A
- Account: existing (`slt2-core`)
- Plugins: free-only

## Preconditions
- SLT-LIFE-05 done; S5 active, `_recurring_amount=20`.
- **Out-of-baseline change (isolation rule 7).** `skip_renewal.enabled=false` and `cutoff_days=2`; a 1-day cycle can never satisfy `days_until_renewal >= 2` (SkipManager.php:82-95), so both change here and are restored here. `max_cycles` (3) and `customer_can_skip` stay untouched.
- Act after S5's 2026-08-28 renewal completes and require its current `_next_payment_date` to be 2026-08-29 T. Finish the settings ON→OFF bracket in under 30 minutes and before the next invoice leg (due+k-6h).

## Test data
| Item | Value |
|---|---|
| Subscription | S5 (SLT2 Renewal Price Step, `slt2-core`), $20.00/cycle |
| Settings | `skip_renewal.enabled` false->true; `cutoff_days` 2->0 |
| Portal / settings | `/my-account/view-subscription/S5/`; `admin.php?page=arraysubs-mainadmin#/settings/skip-pause` |
| Dates | D_now = 08-29 T; skip1 -> 08-30 T; skip3 -> 09-01 T |

## Steps
1. Resolve numeric `S5`, its exact user/product, current cycle, and pending invoice/charge IDs from the registry. If missing, create/update the upstream issue and move this card to blocked rather than selecting by recency. Save the exact option snapshot, then enable only Skip Renewal and cutoff 0 inside a recorded restore bracket.
2. Confirm `_pending_renewal_order_id` empty; record `_next_payment_date` (D_now), recompute k, and save the exact pre-skip order/action/note ID sets.
3. In `life03-SLT-LIFE-03`, open the exact portal route, capture the Skip/Renew Early state as `SLT-LIFE-03-02-skip-panel.png`.
4. Set `SKIP1_PRE=$(mailpit-agent latest-id)`, choose one cycle and confirm; dump exact meta, capture `SLT-LIFE-03-03-after-skip1.png`, then in the admin session open `admin.php?page=wc-status&tab=action-scheduler&status=pending&s=$S5` and capture the exact indexed invoice/charge rows as `SLT-LIFE-03-04-pending-shifted.png`.
5. Capture the portal with Renew Early absent as `SLT-LIFE-03-05-no-renew-early.png`. From `SKIP1_PRE`, require exactly one customer `subscription_skipped` message containing the cycle count and new date; reject duplicates and unrelated lifecycle mail.
6. Use distinct baselines for Undo, five-cycle clamp, second Undo and final one-cycle skip. After each state change re-dump metas/notes/actions and require the matching single skip or skip-undone customer notification with correct cycles/date. Require date/action restoration after each Undo and exactly three-cycle clamping before the final one-cycle state.
7. Immediately restore **Enable Skip Renewal** OFF and **Skip Cutoff (Days)** = `2` while leaving the per-subscription skip meta intact. Save `/home/server-manager/slt-evidence/SLT-LIFE-03-skip-after.json`, require its canonical diff against `SLT-LIFE-03-skip-before.json` to be empty, append the UTC close timestamp to the bracket file/registry, and require elapsed bracket time <30 minutes. Close the admin settings session.
8. Close the two D5 sessions after exact restoration and leave the card `in-progress`. On D6 take `ORIGINAL_PRE` inside the final five minutes before the now-empty original gate, snapshot exact order/action/note sets before/after, and require no order/charge/mail; force nothing. On D7 take `SHIFT_PRE` only inside `[shifted charge gate−300s, gate)`, then poll `wait-new` in ≤60-second intervals through the 10-minute cutoff. Resolve the $20 renewal order by exact subscription/cycle plus reverse meta, require skip meta clears and settings remain byte-identical, and reconcile the complete delta. Use and close `admin-SLT-LIFE-03-ORIGINAL` / `admin-SLT-LIFE-03-SHIFTED` only for those phases.
9. If any assertion fails, restore settings first, then create a dedicated `qa/issues/` kanban card named `SLT-LIFE-03-<concise-slug>` (create the required QA issue card) with task/stage/plan, subscription/order/action/note/product IDs, user ID/login/email/role, exact routes/sessions/gates, reproduction, expected/actual, option/meta/scheduler/Mailpit/UI/screenshot proof, and another skip/undo state as counterexample. Continue unaffected observations. After D7, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. With `cutoff_days=0` the portal offers **Skip Next Renewal**; with the shipped `2` it refuses ("Cannot skip within 2 day(s) of renewal date.").
2. After the 1-cycle skip: `_original_next_payment_date` = D_now, `_next_payment_date` = D_now + 24h exactly, `_skip_cycles_count`/`_remaining` = 1, one `_skip_history` `skip` entry; both legs re-queued at new_due+k-6h and +k, one row each, no orphans.
3. **Undo Skip** restores D_now and clears `_skip_cycles_*`; 5 cycles clamps to 3 (`_next_payment_date` = D_now + 72h).
4. Each skip/undo mutation sends exactly one matching customer notification, adds one subscription note, and hides Renew Early while a skip is pending.
5. Nothing fires at the original due moment.
6. The global settings are restored before the bracket closes on D5; the pending skip survives solely in subscription meta. At the shifted moment `shouldCompleteSkipAndGenerateNow()` -> `completeSkippedCycles()` (Hooks.php:214-230) generates the invoice in the same pass, $20.00 is charged, `_skip_cycles_remaining=0`, meta cleared.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | subscription_skipped x3 | one-cycle, clamped three-cycle, final one-cycle skip | customer | `renewal has been skipped` | exact mutation baselines and full deltas |
| 2 | skip_undone x2 | both Undo actions | customer | `skip has been undone` | exact mutation baselines and full deltas |
| 3 | payment_successful + task-attributable WC order mails | shifted renewal | customer + admin | `Payment received for subscription #<numeric S5>`, exact order | repeated bounded waits on `SHIFT_PRE`, then complete owner-filtered delta |
| 4 | NONE EXPECTED: renewal_reminder | — | — | `renews soon` | no reminder AS row (1-day cycle) |

## Evidence to capture
- Absolute-path `SLT-LIFE-03-skip-before.json`, `SLT-LIFE-03-skip-after.json`, the empty diff, `SLT-LIFE-03-settings-bracket.txt`, notes, and the three timeout exit codes; S5, shifted order ID, k, `SHIFT_PRE`, resulting Mailpit IDs, and meta dumps.
- Screenshots `SLT-LIFE-03-01-settings.png`, `-02-skip-panel.png`, `-03-after-skip1.png`, `-04-pending-shifted.png`, `-05-no-renew-early.png`.

## Pass criteria
- [ ] Skip panel appears only after `enabled=true` + `cutoff_days=0`
- [ ] 1-cycle skip moves the date one interval, stores the original, re-queues both legs
- [ ] Undo restores the date; a 5-cycle request clamps to 3
- [ ] Exactly one correct skip/undo email per mutation; Renew Early hidden while pending
- [ ] Nothing at the original due moment; the shifted cycle charges $20.00
- [ ] `skip_renewal` settings restored inside the <30-minute D5 bracket, before either timed follow-up; jq diff empty
- [ ] Original/shifted gates selected exactly, all phase sessions closed, and final evidence reviewed to done

## Isolation / teardown
- S5 returns to a normal daily grid for SLT-LIFE-01 on D8; no skip remains after the 08-30 shifted renewal.
- `skip_renewal.enabled=false` and `cutoff_days=2` are restored in step 7 on D5, not after the timed follow-ups. Record the closed change window in the registry.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
