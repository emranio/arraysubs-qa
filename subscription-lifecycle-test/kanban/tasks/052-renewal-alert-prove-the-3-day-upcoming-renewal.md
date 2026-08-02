---
id: 52
title: 'Renewal ALERT: prove the 3-day upcoming-renewal reminder fires once, on the right subscription, and never twice'
status: todo
priority: high
created: 2026-08-02T03:43:07.563946069+02:00
updated: 2026-08-02T03:43:17.950748418+02:00
tags:
    - email
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h
depends_on:
    - 22
    - 5
    - 12
class: standard
---

> **SLT-EML-01** · group `emails` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · dependency-inversion / date contradiction** — with `SLT-SYN-08`, `SLT-PROD-14`, `SLT-SYN-01`, `SLT-SYN-09`

- *Problem:* SLT-SYN-08 is tagged d0 and buys SLT Flex Daily Two Seg + SLT Flex Daily Next Cycle, but SLT-PROD-14 creates those products on D1 in the corrected calendar and audit C10 forbids purchasing a flex product before SLT-SYN-01's destructive meta surgery has run and been restored. Worse, SYN-08's stated dates encode a D0 purchase (cycle_start 2026-08-01 18:00 UTC, Two Seg next payment 08-04 18:00 UTC) while SLT-EML-01 - which owns the only reachable renewal_reminder in the window - encodes a D1 purchase (SUB_2SEG due 2026-08-06 00:00 site, SUB_NC due 2026-08-09 00:00 site, reminder fires 08-06 00:00-06:00 = watch D4). Both cannot be true and neither product can be bought twice by the same account.
- *Required fix:* SLT-SYN-08 moves to D1 (2026-08-03), purchases after 12:00, strictly after SLT-PROD-14 and after SLT-SYN-01B's restore is proven. That makes EML-01's numbers correct as written (SUB_2SEG due 08-06 00:00 site, SUB_NC due 08-09 00:00 site, reminder 08-06 00:00-06:00 site, watch D4) and SYN-08's own Test data must be recomputed to cycle_start 2026-08-02 18:00 UTC, Two Seg next payment 2026-08-05 18:00 UTC, Next Cycle cycle_start rewritten to 2026-08-05 18:00 UTC and next payment 2026-08-08 18:00 UTC. Knock-on: SLT-SYN-09's SUB_A row is now wrong (it assumes #2 at 08-04 18:00 and #3 at 08-07 18:00). Move SLT-SYN-09 from D6 to D7 (2026-08-09 morning) where the week pair's 08-08 00:00 renewals AND SUB_A's #2 at 08-09 00:00 are both already visible; hand SUB_A's #3 (08-12 00:00) to watch D10 as a grid assertion.

**`high` · session collision (shared admin session)** — with `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`, `SLT-EML-13`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`medium` · contradictory-expected-result** — with `SLT-CHK-15`, `SLT-EML-09`

- *Problem:* SLT-CHK-15 expected result 7 requires SLT Trial Four Day's subscription to carry `_renewal_reminder_action_id` due 2026-08-05 (trial end 08-08 minus the 3-day lead) and asserts it exists. SLT-EML-09 step 4 asserts the opposite - 'wp action-scheduler list --hooks=arraysubs_send_renewal_reminder ... | grep <S_TR>' must return nothing - and expected result 3 says 'No pending arraysubs_send_renewal_reminder or arraysubs_send_expiring_soon action for S_TR'. Both tasks buy/attach to the same subscription on D2. One of them will file a bug the other declares correct.
- *Required fix:* Separate the action from the mail. Per SLT-REF-05 / EmailManager.php:806 the reminder handler requires post_status exactly `arraysubs-active`, and a trialling subscription is `arraysubs-trial` - so the ACTION may legitimately be scheduled while the MAIL is legitimately never sent. Restate CHK-15 ER7 as 'an arraysubs_send_renewal_reminder action for S_TR exists at trial_end - 3d + k; record whether it does'. Restate EML-09 ER3 as 'no reminder MAIL for S_TR on 2026-08-05; whether the action exists is recorded, not asserted'. Make the D4 watch row carry both as an explicit paired check.

**`medium` · action-scheduler policy / broad-fire risk** — with `SLT-LIFE-04`, `SLT-EML-10`, `SLT-LIFE-01`, `SLT-ADM-05`, `SLT-SETUP-99`

- *Problem:* No task in the index issues a bare `wp action-scheduler run --hooks=<hook> --force`, so the largest hazard the audit named is currently absent - but the 'D8 is the only authorized Action Scheduler day' rule is broken by tasks that legitimately need to run one action: SLT-LIFE-04 step 9 hand-schedules HOOK_SEND_EXPIRING_SOON and runs it by id on D3 (2026-08-05) - which is also SLT-SYN-04's exclusive bracket day; SLT-EML-01 step 8 queues a duplicate reminder action on D3 and lets wp-cron claim it; SLT-ADM-05/ADM-03 depend on cron claiming their legs on D3/D4. Residual broad-fire risks that DO exist: (a) SLT-LIFE-01 back-dates S5's legs and relies on the per-minute runner, whose batch will claim any other action already due in that same tick; (b) SLT-EML-10 schedules HOOK_SEND_EXPIRING_SOON at time()-60; (c) SLT-SETUP-99's step 7 cancels pending actions found by searching the Scheduled Actions screen, which can match non-SLT rows; (d) SLT-ADM-01's bulk 'Delete Permanently' path issues DELETE wp/v2/arraysubs_data/<id>?force=true per selected id with no onDeleteCheck guard - one accidental confirm force-deletes irrecoverably.
- *Required fix:* Refine the rule into three tiers and publish it in the README isolation contract. (1) BANNED on every day, no exceptions: any `wp action-scheduler run` without a specific action id, and any `--hooks=` drain. (2) PERMITTED on any day: running ONE action by id from Tools -> Scheduled Actions, and queueing a single-subscription action and letting the per-minute cron claim it - provided the task first screenshots the Pending queue for the next 60 minutes and aborts if any non-SLT action is due. (3) D8 ONLY: editing _next_payment_date / _end_date / _renewal_scheduled_date to move an event in time, always paired with the 13 non-SLT _next_payment_date before/after proof. Under this rule LIFE-04 step 9, EML-01 step 8, EML-10 and ADM-05/03 are legal where they are; LIFE-01 and SETUP-99 stay on D8/D10 with the pre-flight. For SETUP-99, replace 'search and cancel' with 'cancel by action id, taken from the per-subscription action-id metas recorded in the registry'. For SLT-ADM-01, keep the bulk dialog cancelled and file the missing-guard finding as a bug, as authored.

---
## Objective
Prove the upcoming-renewal reminder (`emails.renewal_upcoming.days_before = 3`) is scheduled at exactly `_next_payment_date − 3 days + spread offset`, delivered exactly once to exactly one customer, never delivered again for the same `_next_payment_date`, and correctly NOT scheduled where the reminder moment is already past at scheduling time.

## Scope
- Gateway: Stripe test
- Checkout: N/A — this task places no order
- Account: existing (`slt-flex3`, `slt-flex2`, `slt-core`)
- Plugins: both

## Preconditions
- SLT-PROD-14 and SLT-PROD-01 done; D1 purchases (2026-08-03 after 12:00 site) done: **SLT Flex Daily Next Cycle** by `slt-flex3`, **SLT Flex Daily Two Seg** by `slt-flex2`. SLT Daily Core owned by `slt-core` since D0.
- Contract (SLT-REF-05): fire moment = `_next_payment_date − 3d + offset`; not scheduled when that moment is past (`EmailManager.php:779`); send guard requires status exactly `arraysubs-active` (`:806`); dedupe key `_arraysubs_renewal_reminder_sent_for = "{_next_payment_date}|3"` (`:816-820`).
- No time travel, no hook drain. Step 8 queues ONE action for ONE SLT subscription and lets wp-cron run it.

## Test data
| Item | Value |
|---|---|
| Primary | SLT Flex Daily Next Cycle, `slt-flex3`, $9.00, day/3 — `SUB_NC` |
| `SUB_NC` due | 2026-08-09 00:00 site = 2026-08-08 18:00 UTC |
| Fire window | `2026-08-05 18:00 UTC + k`, k = 0..21600 s → **2026-08-06 00:00–06:00 site** |
| Watch day it lands on | **watch D4 = 2026-08-06** (watch runs 02:10 UTC / 08:10 site) |
| Negative A | SLT Flex Daily Two Seg `SUB_2SEG` — due 2026-08-06 00:00 site, reminder moment 2026-08-03 00:00+k was past at checkout |
| Negative B | SLT Daily Core `SUB_CORE` (day/1), SLT Fixed Three Cycles (day/2) — lead exceeds cycle |
| Hook/args/group | `arraysubs_send_renewal_reminder` / `[SUB_ID, 3]` / `arraysubs-emails` |

## Steps
1. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/edit.php?post_type=arraysubs_data"` → `snapshot -i`; record `SUB_NC`, `SUB_2SEG`, `SUB_CORE` and each `_next_payment_date`.
2. `php -r 'foreach([SUB_NC,SUB_2SEG] as $i){$h=(int)sprintf("%u",crc32("arraysubs-spread-".$i));printf("%d => %ds (%s)\n",$i,$h%21600,gmdate("H:i:s",$h%21600));}'`
3. `wp db query "SELECT action_id,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE hook='arraysubs_send_renewal_reminder' ORDER BY action_id DESC LIMIT 30" --allow-root`
4. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=arraysubs_send_renewal_reminder"`; screenshot.
5. Record `mailpit-agent latest-id` as PREV; assert `mailpit-agent list 50` shows no "renews soon" yet.
6. **Follow-up 2026-08-06 after 08:10 site (watch D4):** find the message, `mailpit-agent show <id>` and `mailpit-agent text <id>`.
7. `wp post meta list SUB_NC --keys=_arraysubs_renewal_reminder_sent_for,_arraysubs_renewal_reminder_sent_at,_next_payment_date --allow-root`; re-run step 3 and confirm the row is `complete` with no new pending row.
8. Dedupe probe: snapshot `latest-id`, then `wp eval '\ArraySubs\Supports\ActionScheduler::scheduleSingle(\ArraySubs\Supports\ActionScheduler::HOOK_SEND_RENEWAL_REMINDER, time()+120, [SUB_NC,3], \ArraySubs\Supports\ActionScheduler::GROUP_EMAILS);' --allow-root`; wait 5 min for wp-cron; assert `latest-id` unchanged. Never run `wp action-scheduler run`.

## Expected results
1. Exactly ONE action for args `[SUB_NC,3]`, group `arraysubs-emails`, `scheduled_date_gmt` = `2026-08-05 18:00:00` + step-2 offset, asserted to the second.
2. ZERO rows for `SUB_2SEG`, `SUB_CORE`, Fixed Three Cycles — the `:779` guard refused all three.
3. One mail, 2026-08-06 00:00–06:00 site, to `slt-flex3@example.test` only.
4. Subject `[<site title>] Your subscription #SUB_NC renews soon` — `renewal` context, not "trial … ends soon", not "is ending soon".
5. Body: "will renew in 3 days", `Subscription #SUB_NC`, Product `SLT Flex Daily Next Cycle`, Renewal Amount `$9.00`, label `Next Payment Date` showing the UTC+6 rendering of 2026-08-09.
6. `_arraysubs_renewal_reminder_sent_for` = `<_next_payment_date>|3`; `_arraysubs_renewal_reminder_sent_at` inside the window.
7. Step 8's duplicate action completes and sends nothing.
8. No reminder mail for any non-SLT subscription in the window.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `renewal_reminder` | AS action at due − 3d + k | slt-flex3@example.test | `renews soon`, `#SUB_NC` | `mailpit-agent list 50`, `text <id>` |
| 2 | NONE EXPECTED | — | — | `renews soon` naming SUB_2SEG / SUB_CORE / Fixed Three Cycles | absent all window |
| 3 | NONE EXPECTED | step 8 duplicate action | — | — | `latest-id` unchanged after 5 min |

## Evidence to capture
- `SLT-EML-01-01-pending-action.png`, `-02-mailpit-reminder.png`, `-03-completed-action.png`.
- Both offsets; the db query output before/after; both dedupe metas; mailpit ids; `latest-id` before/after step 8.

## Pass criteria
- [ ] Exactly one action, exact second, args `[SUB_NC,3]`
- [ ] Zero actions for the three negative subscriptions
- [ ] One mail, one recipient, `renews soon` context, product/amount/date exact
- [ ] Dedupe meta written as `<next_payment>|3`
- [ ] Duplicate action sends no second mail

## Isolation / teardown
- Hands the confirmed reminder mailpit id to SLT-EML-05 as a template baseline.
- Nothing to restore: no settings, no orders. Step 8's action is a completed one-shot on an SLT subscription.

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
