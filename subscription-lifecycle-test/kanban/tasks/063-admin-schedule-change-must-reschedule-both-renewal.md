---
id: 63
title: Admin schedule change must reschedule both renewal legs; next-payment-date is API-locked
status: todo
priority: critical
created: 2026-08-02T03:43:08.611941568+02:00
updated: 2026-08-02T03:43:18.93116241+02:00
tags:
    - admin
    - portal
    - day-04
    - has-conflicts
due: "2026-08-06"
estimate: 1h30m
depends_on:
    - 47
    - 11
class: standard
---

> **SLT-ADM-03** · group `admin` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / multi-day deviation vs frozen baseline** — with `SLT-LIFE-03`, `SLT-MYA-01`, `SLT-SW-07`, `SLT-SW-10`, `SLT-LIFE-02`, `SLT-MYA-03`

- *Problem:* SLT-LIFE-03 flips two global settings out of baseline - skip_renewal.enabled false->true and skip_renewal.cutoff_days 2->0 - and restores them only at its step 7, which happens two days later (after the shifted cycle charges). That is a 2-3 day site-wide deviation in which every customer portal renders a 'Skip Next Renewal' control. Colliding audits: SLT-MYA-01 expected result 5 lists 'Skip Next Renewal' among the five actions an active subscription must expose - which is wrong against the frozen baseline (skip_renewal.enabled=false) and only accidentally right if MYA-01 happens to run inside LIFE-03's bracket. SLT-ADM-03 asserts the opposite ('Skip Renewal is expectedly unavailable'), so the two tasks contradict each other. SLT-SW-07, SLT-SW-10, SLT-LIFE-02, SLT-MYA-03 and SLT-MYA-04 all screenshot the portal Actions card on D5-D7 and would file the Skip control as unexpected UI.
- *Required fix:* Two changes. (1) Correct SLT-MYA-01 expected result 5 to the four baseline actions - Change Plan, Cancel Subscription, Renew Early, Pause Subscription - and add 'Skip Next Renewal MUST be absent (skip_renewal.enabled=false)'; quote the registry WINDOW BASELINE table as C14 requires. (2) Compress LIFE-03's deviation to a single short bracket: settings ON, perform skip / undo / 5-cycle clamp / undo / final 1-cycle skip, settings RESTORED, all inside one <30 min window on D5 with open/close UTC recorded - the pending skip lives in subscription meta (_skip_cycles_remaining, _original_next_payment_date) and completeSkippedCycles() runs off the renewal path, so the setting does not need to stay on for the shifted cycle to complete. Verify that on the day; if completion does prove to require the flag, move LIFE-03 wholesale to D8-D9 where no portal audit runs. Also correct LIFE-03's internal dates: it is a D5 (2026-08-07) task, so D_now = 08-08, skip1 -> 08-09, skip3 -> 08-11, original due 08-08 shows nothing (watch D7 negative) and the shifted $20.00 charge lands 08-09 PM (watch D8) - which also clears 2026-08-10 for SLT-LIFE-01.

**`high` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-13`, `SLT-CHK-08`, `SLT-CHK-13`, `SLT-SYN-07`, `SLT-SYN-11`, `SLT-SW-09`

- *Problem:* SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.
- *Required fix:* Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

---
## Objective
Prove what an admin schedule change does to the queue: the next payment date is editable nowhere and both REST paths reject it, while Skip & Pause → Vacation Mode moves `_next_payment_date` and must pull *and* restore **both** legs with the spread offset intact.

## Scope
- Gateway: N/A (gateway-less canvas)
- Checkout: N/A
- Account: admin-created (slt-admincreated)
- Plugins: both

## Preconditions
- SLT-ADM-05 done; reuse its create + arm recipe (create lands Pending and schedules nothing; **On Hold→Active** arms it).
- Frozen baseline: pause enabled, customers may pause, reason required, max 30 days / 2 pauses.
- **Do not** flip `skip_renewal.enabled` (`false`): Skip Renewal is expectedly unavailable and `cutoff_days=2` blocks a day/1 sub anyway. Record it.
- Act after 12:00 site time.

## Test data
| Item | Value |
|---|---|
| Canvas | **SUB-B**, created here: slt-admincreated + SLT Daily Core, $10.00, Day/1 |
| Session | `--session admin-SLT-ADM-03` |

## Steps
1. `mailpit-agent latest-id` → `M0`. Create **SUB-B** at `#/subscriptions/form` as in SLT-ADM-05 steps 2-4.
2. Arm it: `#/subscriptions/edit/SUB-B` → Change Status **Active**, **On Hold**, **Active**. Record `D` = `wp post meta get SUB-B _next_payment_date --allow-root`, **k** = `crc32('arraysubs-spread-'.SUB-B) % 21600`.
3. Screenshot the **before** queue `admin.php?page=wc-status&tab=action-scheduler&status=pending&s=SUB-B`: invoice at `D+k−6h`, charge at `D+k`.
4. Negative A: screenshot every field on `#/subscriptions/edit/SUB-B` — no next-payment-date input may exist.
5. Negative B: from WP root, `wp eval` + `rest_do_request()` POSTing `{"next_payment_date":"2026-09-01 12:00:00"}` to `/arraysubs/v1/subscriptions/SUB-B/update` and `.../manual/update-dates`; `var_dump()` status and data.
6. On `#/subscriptions/detail/SUB-B` open **Skip & Pause → Vacation Mode**, set **Duration (Days)** `2` and **Reason** `SLT-ADM-03 probe`, click **Pause Subscription**, confirm.
7. Re-read `_next_payment_date`, `_pause_original_next_payment_date`, `_pause_end_date`, `_pause_count` and status; re-screenshot the queue.
8. **Resume Now**, confirm. Re-read those metas plus both action-id metas; re-screenshot the queue. Capture Mailpit ids around steps 6 and 8.
9. Run no `wp action-scheduler` command; close the session. **Follow-up on watch day D6 (2026-08-08):** confirm the restored schedule fired (result 6).

## Expected results
1. The Edit screen exposes Status, Invoice Email and addresses only — **no next-payment-date field** — and both REST calls return HTTP **400** `Manual next payment date changes are no longer supported…` (`SubscriptionController.php:812-818`), leaving the date unchanged.
2. After Pause: status `arraysubs-on-hold`; `_pause_original_next_payment_date` = `D`; `_next_payment_date` = `D + 2 days`; `_pause_end_date` ≈ now + 2 days; `_pause_count` = 1.
3. After Pause the queue holds **zero** invoice and charge rows for SUB-B (`PauseManager.php:202`) and **one** `arraysubs_resume_subscription` row (`arraysubs-status`) at `_pause_end_date` — while paused the shifted date has no legs behind it.
4. After Resume Now the same day (`calculateActualPauseDays()`=0): `_next_payment_date` restored to exactly `D`; pause metas cleared; status `arraysubs-active`; resume row gone.
5. Both legs return unchanged — invoice `D+k−6h`, charge `D+k` (±60 s) as in step 3 — with **new** action ids in both metas.
6. **Watch D6**: both actions **Complete** at `D+k−6h` / `D+k`; a `pending` $10.00 renewal order exists.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | subscription_on_hold | step 2 →On Hold **and** step 6 Pause | slt-admincreated | `is on hold` | expect **twice**; pause is a real on-hold |
| 2 | subscription_reactivated | step 2 →Active **and** step 8 Resume | slt-admincreated | `has been reactivated` | `wait-new <prev> 120 "reactivated"` |
| 3 | renewal_invoice | invoice leg, 08-07 | slt-admincreated | `Invoice for subscription #SUB-B` | watch D6 `list 50` |
| 4 | NONE EXPECTED | steps 4-5 | — | — | `latest-id` unchanged across step 5 |

## Evidence to capture
- Screenshots `SLT-ADM-03-01-edit-no-date-field.png`, `-02-queue-before.png`, `-03-queue-paused.png`, `-04-queue-after-resume.png`; SUB-B id, `D`, k, all meta reads, both `var_dump` outputs, Mailpit ids, action ids.

## Pass criteria
- [ ] No next-payment-date field; both REST paths return 400 and change nothing
- [ ] Pause shifts the date by 2 days, removes both legs, queues `arraysubs_resume_subscription`; Resume restores the date exactly and re-queues both legs at `D+k−6h` / `D+k`
- [ ] Watch D6: both legs Complete, `pending` renewal order created, no non-SLT action moved

## Isolation / teardown
- Hands SUB-B, `D` and k to SLT-ADM-04, which runs the status ladder on D6 **before 12:00**; baseline untouched, `skip_renewal.enabled` left `false`. SUB-B is cancelled by SLT-ADM-04, deleted by SLT-SETUP-99B.


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
