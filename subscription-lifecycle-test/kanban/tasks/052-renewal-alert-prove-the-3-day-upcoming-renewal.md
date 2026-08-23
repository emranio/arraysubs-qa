---
id: 52
title: 'Renewal ALERT: prove the 3-day upcoming-renewal reminder fires once, on the right subscription, and never twice'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - day-03
due: "2026-08-26"
estimate: 1h
depends_on:
    - 22
    - 5
    - 12
    - 28
    - 1
class: standard
---

> **SLT-EML-01** · group `emails` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the upcoming-renewal reminder (`emails.renewal_upcoming.days_before = 3`) is scheduled at exactly `_next_payment_date − 3 days + spread offset`, delivered exactly once to exactly one customer, never delivered again for the same `_next_payment_date`, and correctly NOT scheduled where the reminder moment is already past at scheduling time.

## Scope
- Gateway: Stripe test
- Checkout: N/A — this task places no order
- Account: existing (`slt2-flex3`, `slt2-flex2`, `slt2-core`)
- Plugins: both

## Preconditions
- SLT-PROD-14 and SLT-PROD-01 done; D1 purchases (2026-08-24 after 12:00 site) done: **SLT2 Flex Daily Next Cycle** by `slt2-flex3`, **SLT2 Flex Daily Two Seg** by `slt2-flex2`. SLT2 Daily Core owned by `slt2-core` since D0.
- Contract (SLT-REF-05): fire moment = `_next_payment_date − 3d + offset`; not scheduled when that moment is past (`EmailManager.php:779`); send guard requires status exactly `arraysubs-active` (`:806`); dedupe key `_arraysubs_renewal_reminder_sent_for = "{_next_payment_date}|3"` (`:816-820`).
- No time travel, no hook drain. Step 8 queues ONE action for ONE SLT2 subscription and lets wp-cron run it.

## Test data
| Item | Value |
|---|---|
| Primary | SLT2 Flex Daily Next Cycle, `slt2-flex3`, $9.00, day/3 — `SUB_NC` |
| `SUB_NC` due | 2026-08-30 00:00 site = 2026-08-29 18:00 UTC |
| Fire window | `2026-08-26 18:00 UTC + k`, k = 0..21600 s → **2026-08-27 00:00–06:00 site** |
| Watch day it lands on | **watch D4 = 2026-08-27** (earliest watch phase 06:10 site) |
| Negative A | SLT2 Flex Daily Two Seg `SUB_2SEG` — due 2026-08-27 00:00 site, reminder moment 2026-08-24 00:00+k was past at checkout |
| Negative B | SLT2 Daily Core `SUB_CORE` (day/1), SLT2 Fixed Three Cycles (day/2) — lead exceeds cycle |
| Hook/args/group | `arraysubs_send_renewal_reminder` / `[SUB_ID, 3]` / `arraysubs-emails` |

## Steps
1. `agent-browser --session admin-SLT-EML-01 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions"` → `snapshot -i`; search each exact registry ID and record `SUB_NC`, `SUB_2SEG`, `SUB_CORE` and each `_next_payment_date` from **View Details**.
2. Resolve registry aliases `SUB_NC` and `SUB_2SEG` into same-named shell variables and abort unless both match `^[0-9]+$`. Then run `php -r 'foreach(array_slice($argv,1) as $i){$i=(int)$i;$h=(int)sprintf("%u",crc32("arraysubs-spread-".$i));printf("%d => %ds (%s)\n",$i,$h%21600,gmdate("H:i:s",$h%21600));}' "$SUB_NC" "$SUB_2SEG"`.
3. `wp db query "SELECT action_id,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE hook='arraysubs_send_renewal_reminder' ORDER BY action_id DESC LIMIT 30" --allow-root`
4. `agent-browser --session admin-SLT-EML-01 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=arraysubs_send_renewal_reminder"`; capture the exact numeric `SUB_NC` row as `SLT-EML-01-01-pending-action.png`.
5. Publish the exact reminder action ID/time and its `gate−5m` baseline deadline to the registry and D03 watch report, close only `admin-SLT-EML-01`, and keep this card `in-progress`. No earlier than five minutes before that exact action, record `PREV=$(mailpit-agent latest-id)` in the registry/task evidence; prove there is no earlier `renews soon` message for `SUB_NC` and let wp-cron fire naturally.
6. **Follow-up 2026-08-27 after 06:10 site (watch D4):** require `mailpit-agent wait-new "$PREV" 900 "subscription #$SUB_NC renews soon"`, save/show the exact match, run `mailpit-agent text <matched id>`, and classify every other message newer than `PREV`. In exact session `mail-SLT-EML-01`, open the matched message in the local Mailpit UI, capture `SLT-EML-01-02-mailpit-reminder.png`, and close only that session.
7. `wp post meta list "$SUB_NC" --keys=_arraysubs_renewal_reminder_sent_for,_arraysubs_renewal_reminder_sent_at,_next_payment_date --allow-root`; re-run step 3 and confirm the exact row is `complete` with no new pending row. Reopen `admin-SLT-EML-01`, show the exact completed action and capture `SLT-EML-01-03-completed-action.png`.
8. Dedupe probe: first capture the site-wide Pending queue for the next five minutes as `SLT-EML-01-04-dedupe-preflight.png`. If any non-SLT2 action is already overdue or due during that interval, defer until it completes naturally and repeat the pre-flight; never alter it. Set `DEDUPE_PRE=$(mailpit-agent latest-id)`, then `wp eval "\\ArraySubs\\Supports\\ActionScheduler::scheduleSingle(\\ArraySubs\\Supports\\ActionScheduler::HOOK_SEND_RENEWAL_REMINDER, time()+120, [(int) $SUB_NC,3], \\ArraySubs\\Supports\\ActionScheduler::GROUP_EMAILS);" --allow-root`; record the returned exact action ID, wait up to 5 min for wp-cron, require that exact action to complete, inspect every message newer than `DEDUPE_PRE`, and assert zero additional `renews soon` message for numeric subscription `$SUB_NC`. Classify unrelated mail instead of requiring the global latest ID to remain unchanged. Never run `wp action-scheduler run`. Close only `admin-SLT-EML-01`, independently review the D3/D4 evidence, move the card through `review` to `done`, and ensure Review returns to zero. Any observed product failure creates/updates one mandatory `qa/issues/` kanban card with the required fixture/context/reproduction/proof fields and blocks this task.

## Expected results
1. Exactly ONE action for args `[SUB_NC,3]`, group `arraysubs-emails`, `scheduled_date_gmt` = `2026-08-26 18:00:00` + step-2 offset, asserted to the second.
2. ZERO rows for `SUB_2SEG`, `SUB_CORE`, Fixed Three Cycles — the `:779` guard refused all three.
3. One mail, 2026-08-27 00:00–06:00 site, to `slt2-flex3@example.test` only.
4. Subject `[<site title>] Your subscription #SUB_NC renews soon` — `renewal` context, not "trial … ends soon", not "is ending soon".
5. Body: "will renew in 3 days", `Subscription #SUB_NC`, Product `SLT2 Flex Daily Next Cycle`, Renewal Amount `$9.00`, label `Next Payment Date` showing the UTC+6 rendering of 2026-08-30.
6. `_arraysubs_renewal_reminder_sent_for` = `<_next_payment_date>|3`; `_arraysubs_renewal_reminder_sent_at` inside the window.
7. Step 8's duplicate action completes and sends nothing.
8. No reminder mail for any non-SLT2 subscription in the window.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `renewal_reminder` | AS action at due − 3d + k | slt2-flex3@example.test | `subscription #<SUB_NC> renews soon` | exact 900-second wait after `PREV`; save/show exact match and full delta |
| 2 | NONE EXPECTED | — | — | `renews soon` naming SUB_2SEG / SUB_CORE / Fixed Three Cycles | absent all window |
| 3 | NONE EXPECTED | step 8 duplicate action | — | — | zero additional reminder for `SUB_NC` in the full `DEDUPE_PRE` delta; unrelated mail classified |

## Evidence to capture
- `SLT-EML-01-01-pending-action.png`, `-02-mailpit-reminder.png`, `-03-completed-action.png`, `-04-dedupe-preflight.png`.
- Both offsets; the db query output before/after; exact action ID/gate/baseline deadline; both dedupe metas; `PREV`/`DEDUPE_PRE`; exact-match/full-delta Mailpit ids; exact session-closure and review proof.

## Pass criteria
- [ ] Exactly one action, exact second, args `[SUB_NC,3]`
- [ ] Zero actions for the three negative subscriptions
- [ ] One mail, one recipient, `renews soon` context, product/amount/date exact
- [ ] Dedupe meta written as `<next_payment>|3`
- [ ] Duplicate action sends no second mail
- [ ] Exact D4 gate handed off before D3 session closure; task sessions closed and card reviewed to done

## Isolation / teardown
- Hands the confirmed reminder mailpit id to SLT-EML-05 as a template baseline.
- Nothing to restore: no settings, no orders. Step 8's action is a completed one-shot on an SLT2 subscription.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
