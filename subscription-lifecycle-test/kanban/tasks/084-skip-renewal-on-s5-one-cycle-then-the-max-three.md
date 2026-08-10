---
id: 84
title: 'SKIP renewal on S5: one cycle then the max three, with undo, clamp, and the zero-email finding'
status: done
priority: medium
created: 2026-08-02T03:43:10.208850941+02:00
updated: 2026-08-09T13:55:12.406374788+02:00
started: 2026-08-09T13:55:12.11366528+02:00
completed: 2026-08-09T13:55:12.11366528+02:00
tags:
    - renewal
    - day-05
due: "2026-08-07"
estimate: 2h
depends_on:
    - 19
class: standard
---

> **SLT-LIFE-03** · group `renewal` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Skip renewals on S5 (SLT Renewal Price Step, day/1, $20.00) for one cycle, then for the maximum three. Proves the date math (`calculateSkippedDate()` adds interval x cycles to the current `_next_payment_date`, SkipManager.php:470-501), the re-queue of both legs, undo/clamp, and that a skip sends NO customer email: `arraysubs_send_subscription_email('subscription_skipped')` resolves an unregistered WC email id (email-helpers.php:29-35).

## Scope
- Gateway: Stripe test (the skip takes no payment)
- Checkout: N/A
- Account: existing (`slt-core`)
- Plugins: free-only

## Preconditions
- SLT-LIFE-05 done; S5 active, `_recurring_amount=20`.
- **Out-of-baseline change (isolation rule 7).** `skip_renewal.enabled=false` and `cutoff_days=2`; a 1-day cycle can never satisfy `days_until_renewal >= 2` (SkipManager.php:82-95), so both change here and are restored here. `max_cycles` (3) and `customer_can_skip` stay untouched.
- Act after S5's 2026-08-07 renewal completes and require its current `_next_payment_date` to be 2026-08-08 T. Finish the settings ON→OFF bracket in under 30 minutes and before the next invoice leg (due+k-6h).

## Test data
| Item | Value |
|---|---|
| Subscription | S5 (SLT Renewal Price Step, `slt-core`), $20.00/cycle |
| Settings | `skip_renewal.enabled` false->true; `cutoff_days` 2->0 |
| Portal / settings | `/my-account/view-subscription/S5/`; `admin.php?page=arraysubs-mainadmin#/settings/skip-pause` |
| Dates | D_now = 08-08 T; skip1 -> 08-09 T; skip3 -> 08-11 T |

## Steps
1. Resolve numeric `S5`, its exact user/product, current cycle, and pending invoice/charge IDs from the registry; abort as upstream `UNVERIFIED` through review if missing rather than selecting by recency. Save exact option presence/value and canonical `.skip_renewal` JSON as `/home/server-manager/slt-evidence/SLT-LIFE-03-skip-before.json`. Record bracket-open UTC, then in `admin-SLT-LIFE-03` set only **Enable Skip Renewal** ON and cutoff `0`; capture `SLT-LIFE-03-01-settings.png` and prove only those two paths changed. On any later failure, restore this exact snapshot first before other investigation.
2. Confirm `_pending_renewal_order_id` empty; record `_next_payment_date` (D_now), recompute k, and save the exact pre-skip order/action/note ID sets.
3. In `life03-SLT-LIFE-03`, open the exact portal route, capture the Skip/Renew Early state as `SLT-LIFE-03-02-skip-panel.png`.
4. Set `SKIP1_PRE=$(mailpit-agent latest-id)`, choose one cycle and confirm; dump exact meta, capture `SLT-LIFE-03-03-after-skip1.png`, then in the admin session open `admin.php?page=wc-status&tab=action-scheduler&status=pending&s=$S5` and capture the exact indexed invoice/charge rows as `SLT-LIFE-03-04-pending-shifted.png`.
5. Capture the portal with Renew Early absent as `SLT-LIFE-03-05-no-renew-early.png`. Prove no task mail with two immutable-baseline waits of at most 60 then 30 seconds; both must return 124, then classify the complete delta.
6. Use distinct `UNDO_PRE`, `CLAMP_PRE`, and `FINAL_SKIP_PRE` baselines for Undo, five-cycle clamp, second Undo, and final one-cycle skip. After each state-changing group re-dump exact metas/notes/actions and prove zero task mail using waits no longer than 60 seconds plus complete-delta classification. Require date/action restoration after each Undo and exactly three-cycle clamping before the final one-cycle state.
7. Immediately restore **Enable Skip Renewal** OFF and **Skip Cutoff (Days)** = `2` while leaving the per-subscription skip meta intact. Save `/home/server-manager/slt-evidence/SLT-LIFE-03-skip-after.json`, require its canonical diff against `SLT-LIFE-03-skip-before.json` to be empty, append the UTC close timestamp to the bracket file/registry, and require elapsed bracket time <30 minutes. Close the admin settings session.
8. Close the two D5 sessions after exact restoration and leave the card `in-progress`. On D6 take `ORIGINAL_PRE` inside the final five minutes before the now-empty original gate, snapshot exact order/action/note sets before/after, and require no order/charge/mail; force nothing. On D7 take `SHIFT_PRE` only inside `[shifted charge gate−300s, gate)`, then poll `wait-new` in ≤60-second intervals through the 10-minute cutoff. Resolve the $20 renewal order by exact subscription/cycle plus reverse meta, require skip meta clears and settings remain byte-identical, and reconcile the complete delta. Use and close `admin-SLT-LIFE-03-ORIGINAL` / `admin-SLT-LIFE-03-SHIFTED` only for those phases.
9. If any assertion fails, restore settings first, then create a standalone `issues/SLT-LIFE-03-<concise-slug>.md` (never a kanban bug card) with task/stage/plan, subscription/order/action/note/product IDs, user ID/login/email/role, exact routes/sessions/gates, reproduction, expected/actual, option/meta/scheduler/Mailpit/UI/screenshot proof, and another skip/undo state as counterexample. Continue unaffected observations. After D7, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. With `cutoff_days=0` the portal offers **Skip Next Renewal**; with the shipped `2` it refuses ("Cannot skip within 2 day(s) of renewal date.").
2. After the 1-cycle skip: `_original_next_payment_date` = D_now, `_next_payment_date` = D_now + 24h exactly, `_skip_cycles_count`/`_remaining` = 1, one `_skip_history` `skip` entry; both legs re-queued at new_due+k-6h and +k, one row each, no orphans.
3. **Undo Skip** restores D_now and clears `_skip_cycles_*`; 5 cycles clamps to 3 (`_next_payment_date` = D_now + 72h).
4. Skip/undo/modify send zero customer email, add a subscription note each, and hide Renew Early while pending.
5. Nothing fires at the original due moment.
6. The global settings are restored before the bracket closes on D5; the pending skip survives solely in subscription meta. At the shifted moment `shouldCompleteSkipAndGenerateNow()` -> `completeSkippedCycles()` (Hooks.php:214-230) generates the invoice in the same pass, $20.00 is charged, `_skip_cycles_remaining=0`, meta cleared.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | skip / undo / modify (steps 4, 6) | — | — | task-owned immutable baselines; each wait call ≤60 seconds and complete deltas empty of task mail |
| 2 | payment_successful + task-attributable WC order mails | shifted renewal 08-09 | customer + admin | `Payment received for subscription #<numeric S5>`, exact order | repeated ≤60-second waits on `SHIFT_PRE`, then complete owner-filtered delta |
| 3 | NONE EXPECTED: renewal_reminder | — | — | `renews soon` | no reminder AS row (1-day cycle) |

## Evidence to capture
- Absolute-path `SLT-LIFE-03-skip-before.json`, `SLT-LIFE-03-skip-after.json`, the empty diff, `SLT-LIFE-03-settings-bracket.txt`, notes, and the three timeout exit codes; S5, shifted order ID, k, `SHIFT_PRE`, resulting Mailpit IDs, and meta dumps.
- Screenshots `SLT-LIFE-03-01-settings.png`, `-02-skip-panel.png`, `-03-after-skip1.png`, `-04-pending-shifted.png`, `-05-no-renew-early.png`.

## Pass criteria
- [ ] Skip panel appears only after `enabled=true` + `cutoff_days=0`
- [ ] 1-cycle skip moves the date one interval, stores the original, re-queues both legs
- [ ] Undo restores the date; a 5-cycle request clamps to 3
- [ ] Zero email on skip/undo/modify; Renew Early hidden while pending
- [ ] Nothing at the original due moment; the shifted cycle charges $20.00
- [ ] `skip_renewal` settings restored inside the <30-minute D5 bracket, before either timed follow-up; jq diff empty
- [ ] Original/shifted gates selected exactly, all phase sessions closed, and final evidence reviewed to done

## Isolation / teardown
- S5 returns to a normal daily grid for SLT-LIFE-01 on D8; no skip remains after the 08-09 shifted renewal.
- `skip_renewal.enabled=false` and `cutoff_days=2` are restored in step 7 on D5, not after the timed follow-ups. Record the closed change window in the registry.


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

[[2026-08-06]] Thu 21:26
Preflight 2026-08-06: live S5 is sub 12234 for slt-core, next payment 2026-08-07 07:50:13 UTC with offset 03:53:23; pending AS rows are invoice 15266 at 2026-08-07 11:43:36 site and charge 15267 at 2026-08-07 17:43:36 site. skip_renewal baseline is enabled=false, cutoff_days=2, max_cycles=3, customer_can_skip=true. Keep in todo until the August 7 renewal settles and the next-payment anchor advances to August 8.

[[2026-08-06]] Thu 22:22
As of 2026-08-06 readiness review: this card stays future-gated until Friday, August 7, 2026 and should only start after the live S5 renewal settles. Current preflight remains: S5 is subscription 12234 with next payment 2026-08-07 07:50:13Z and pending renewal order/charge gates already recorded in this task; do not open it early on D4.

[[2026-08-07]] Fri 23:02
D05 browser leg executed after S5's renewal settled. One-cycle shift, undo, maximum-three 72-hour shift, second undo, final one-cycle state, Renew Early suppression, notes/actions, and zero task mail passed. A literal five-cycle submission is UNVERIFIED because the live number input enforces native `max=3` and never sent that request; the visible maximum-three result passed. Final S5 state: next `2026-08-09 07:50:13Z`, original `2026-08-08 07:50:13Z`, one cycle remaining, invoice action 15737 at D7 11:43:36 site and charge 15738 at D7 17:43:36 site. Global settings are byte-identical to preflight after two safely closed brackets (18m54s and 7m45s). Restoration defect filed at `issues/SLT-LIFE-03-disabled-save-leaves-hidden-cutoff.md`; evidence is `/home/server-manager/slt-evidence/SLT-LIFE-03-D05-execution.txt`. Keep in progress. D6 ORIGINAL_PRE must be captured only during 17:38:36–17:43:35 site before the now-empty original charge gate; D7 SHIFT_PRE only during 17:38:36–17:43:35 site before action 15738. Force nothing.

[[2026-08-08]] Sat 17:53
D06 original-gate follow-up **PASS**. `ORIGINAL_PRE=0E69hBWfCpCsxIL6T7CyTL` was captured at 17:39:33 site inside the authored final-five-minute interval. Pre/post subscription-meta, action, relationship-order, note, and settings sets were identical: cycles stayed 5; next/original dates stayed 2026-08-09/08 07:50:13Z; actions remained only pending/unattempted 15737/15738; orders remained 12414/12539/12897/13050; latest note remained 13167; settings remained 13248 bytes / MD5 `2b118a4a8e0bba8bfc1af96da1081999`. Five owner waits returned 124 through 17:51:23, latest Mailpit ID never moved, and browser errors were empty. Screenshots: `SLT-LIFE-03-09-original-pre.png`, `SLT-LIFE-03-10-original-post.png`; full proof: `/home/server-manager/slt-evidence/SLT-LIFE-03-D06-original-gate.txt`. Exact session closed; nothing forced or mutated. Keep in progress. Exact next gate: D7 `SHIFT_PRE` only during 2026-08-09 17:38:36–17:43:35 site before action 15738; observe the natural `$20.00` renewal/skip clearance through its ten-minute cutoff.

[[2026-08-09]] Sun 13:54
D07 shifted-gate follow-up complete. SHIFT_PRE=3jUrzcrxsS5qMrFvzDePXI captured at 17:38:43 site inside the authored window. Action 15738 ran naturally via WP Cron at 17:44:03-17:44:08; existing cycle-6 order 13450 alone completed for USD 20.00 with one product line, payments 5->6, next date 2026-08-10 07:50:13Z, pending/original meta absent, semantic skip state cleared, and replacement actions 16429/16430 preserve k. Settings remained 13248 bytes / MD5 2b118a4a8e0bba8bfc1af96da1081999. Complete Mailpit delta through 17:53:38 is exactly admin order 7D2ineQmRUpJ0BCXKaehEj plus customer payment-success 1Za9O6dBnnyoH7hFNEwcTe. Pre/post screenshots reviewed, browser errors empty, exact session closed. Functional lifecycle PASS; literal-five server request remains UNVERIFIED because native max=3 prevented submission, as already recorded; existing restoration issue remains the only finding. Evidence: /home/server-manager/slt-evidence/SLT-LIFE-03-D07-shifted-gate.txt.
