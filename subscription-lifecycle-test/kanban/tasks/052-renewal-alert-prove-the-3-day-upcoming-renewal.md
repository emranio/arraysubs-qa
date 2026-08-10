---
id: 52
title: 'Renewal ALERT: prove the 3-day upcoming-renewal reminder fires once, on the right subscription, and never twice'
status: done
priority: high
created: 2026-08-02T03:43:07.563946069+02:00
updated: 2026-08-05T20:46:34.101275353+02:00
started: 2026-08-05T20:46:34.101274492+02:00
completed: 2026-08-05T20:46:34.101274492+02:00
tags:
    - email
    - day-03
due: "2026-08-05"
estimate: 1h
depends_on:
    - 22
    - 5
    - 12
    - 28
    - 1
class: standard
---

> **SLT-EML-01** · group `emails` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

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
| Watch day it lands on | **watch D4 = 2026-08-06** (earliest watch phase 06:10 site) |
| Negative A | SLT Flex Daily Two Seg `SUB_2SEG` — due 2026-08-06 00:00 site, reminder moment 2026-08-03 00:00+k was past at checkout |
| Negative B | SLT Daily Core `SUB_CORE` (day/1), SLT Fixed Three Cycles (day/2) — lead exceeds cycle |
| Hook/args/group | `arraysubs_send_renewal_reminder` / `[SUB_ID, 3]` / `arraysubs-emails` |

## Steps
1. `agent-browser --session admin-SLT-EML-01 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions"` → `snapshot -i`; search each exact registry ID and record `SUB_NC`, `SUB_2SEG`, `SUB_CORE` and each `_next_payment_date` from **View Details**.
2. Resolve registry aliases `SUB_NC` and `SUB_2SEG` into same-named shell variables and abort unless both match `^[0-9]+$`. Then run `php -r 'foreach(array_slice($argv,1) as $i){$i=(int)$i;$h=(int)sprintf("%u",crc32("arraysubs-spread-".$i));printf("%d => %ds (%s)\n",$i,$h%21600,gmdate("H:i:s",$h%21600));}' "$SUB_NC" "$SUB_2SEG"`.
3. `wp db query "SELECT action_id,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE hook='arraysubs_send_renewal_reminder' ORDER BY action_id DESC LIMIT 30" --allow-root`
4. `agent-browser --session admin-SLT-EML-01 open "https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=arraysubs_send_renewal_reminder"`; capture the exact numeric `SUB_NC` row as `SLT-EML-01-01-pending-action.png`.
5. Publish the exact reminder action ID/time and its `gate−5m` baseline deadline to the registry and D03 watch report, close only `admin-SLT-EML-01`, and keep this card `in-progress`. No earlier than five minutes before that exact action, record `PREV=$(mailpit-agent latest-id)` in the registry/task evidence; prove there is no earlier `renews soon` message for `SUB_NC` and let wp-cron fire naturally.
6. **Follow-up 2026-08-06 after 06:10 site (watch D4):** require `mailpit-agent wait-new "$PREV" 900 "subscription #$SUB_NC renews soon"`, save/show the exact match, run `mailpit-agent text <matched id>`, and classify every other message newer than `PREV`. In exact session `mail-SLT-EML-01`, open the matched message in the local Mailpit UI, capture `SLT-EML-01-02-mailpit-reminder.png`, and close only that session.
7. `wp post meta list "$SUB_NC" --keys=_arraysubs_renewal_reminder_sent_for,_arraysubs_renewal_reminder_sent_at,_next_payment_date --allow-root`; re-run step 3 and confirm the exact row is `complete` with no new pending row. Reopen `admin-SLT-EML-01`, show the exact completed action and capture `SLT-EML-01-03-completed-action.png`.
8. Dedupe probe: first capture the site-wide Pending queue for the next five minutes as `SLT-EML-01-04-dedupe-preflight.png`. If any non-SLT action is already overdue or due during that interval, defer until it completes naturally and repeat the pre-flight; never alter it. Set `DEDUPE_PRE=$(mailpit-agent latest-id)`, then `wp eval "\\ArraySubs\\Supports\\ActionScheduler::scheduleSingle(\\ArraySubs\\Supports\\ActionScheduler::HOOK_SEND_RENEWAL_REMINDER, time()+120, [(int) $SUB_NC,3], \\ArraySubs\\Supports\\ActionScheduler::GROUP_EMAILS);" --allow-root`; record the returned exact action ID, wait up to 5 min for wp-cron, require that exact action to complete, inspect every message newer than `DEDUPE_PRE`, and assert zero additional `renews soon` message for numeric subscription `$SUB_NC`. Classify unrelated mail instead of requiring the global latest ID to remain unchanged. Never run `wp action-scheduler run`. Close only `admin-SLT-EML-01`, independently review the D3/D4 evidence, move the card through `review` to `done`, and ensure Review returns to zero. Any observed product failure belongs in a standalone `issues/*.md` file with the required fixture/context/reproduction/proof fields, never in a new kanban card.

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
| 1 | `renewal_reminder` | AS action at due − 3d + k | slt-flex3@example.test | `subscription #<SUB_NC> renews soon` | exact 900-second wait after `PREV`; save/show exact match and full delta |
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
- Nothing to restore: no settings, no orders. Step 8's action is a completed one-shot on an SLT subscription.

## D3 live handoff — 2026-08-05

- Registry IDs confirmed live: `SUB_CORE=11959`, `SUB_2SEG=12172`, `SUB_NC=12193`, Fixed Three Cycles `12017`.
- Spread offsets: `12193 => 2435s (00:40:35)` and `12172 => 699s (00:11:39)`.
- Exact reminder action proved in DB: `14001`, hook `arraysubs_send_renewal_reminder`, args `[12193,3]`, status `pending`, scheduled `2026-08-05 18:40:35Z` = `2026-08-06 00:40:35` site (`UTC+6`).
- Negative set proved zero rows for exact args `[12172,3]`, `[11959,3]`, and `[12017,3]`.
- `PREV=$(mailpit-agent latest-id)` must be captured no earlier than `2026-08-05 18:35:35Z` / `2026-08-06 00:35:35` site and before `2026-08-05 18:40:35Z` / `2026-08-06 00:40:35` site.
- Pending-row screenshot captured: `/home/server-manager/slt-evidence/SLT-EML-01-01-pending-action.png`.

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

[[2026-08-05]] Wed 18:47
Pre-send preflight captured at 2026-08-05 16:47:39Z in /home/server-manager/slt-evidence/SLT-EML-01-preflight.txt: SUB_NC 12193 remains arraysubs-active with _next_payment_date 2026-08-08 18:00:00, reminder dedupe metas absent, action 14001 still pending with zero attempts, and Mailpit has no prior 'renews soon' mail for #12193.

[[2026-08-05]] Wed 18:51
Exact gate runbook prepared at /home/server-manager/slt-evidence/SLT-EML-01-runbook.txt using live ids/times: SUB_NC=12193, reminder action 14001 at 2026-08-05 18:40:35Z, baseline PREV window 18:35:35Z-18:40:34Z, and the exact post-fire + dedupe command sequence.

[[2026-08-05]] Wed 18:54
Helper added: /home/server-manager/slt-evidence/SLT-EML-01-baseline-capture.sh writes /home/server-manager/slt-evidence/SLT-EML-01-baseline.txt and prints PREV for the exact baseline window. It is read-only against WordPress data and writes only task evidence under /home/server-manager/slt-evidence/.

[[2026-08-05]] Wed 18:55
Helper added: /home/server-manager/slt-evidence/SLT-EML-01-postfire-check.sh <PREV> writes /home/server-manager/slt-evidence/SLT-EML-01-postfire.txt, waits for the exact reminder mail after PREV, then records relevant Mailpit hits plus post-fire reminder metas and action rows for 12193/12172/11959/12017.

[[2026-08-05]] Wed 18:56
Helper added: /home/server-manager/slt-evidence/SLT-EML-01-dedupe-check.sh <DEDUPE_PRE> writes /home/server-manager/slt-evidence/SLT-EML-01-dedupe.txt and records the relevant Mailpit hits, reminder metas, and reminder action rows for the positive/negative set after the duplicate reminder probe.

[[2026-08-05]] Wed 20:46
D4 completion evidence:
- Baseline window opened at 2026-08-05 18:35:45Z; PREV=4HFIQNafPncizUMYmtzzmd.
- Natural reminder action 14001 completed at 2026-08-05 18:41:06Z for [12193,3].
- Reminder mail id 4hDpBKdzRVitPWnpuJ6c7x arrived at 2026-08-05T18:41:05Z to slt-flex3@example.test with subject [mirror-help.arrayhash.com] Your subscription #12193 renews soon.
- Dedupe meta confirmed: _arraysubs_renewal_reminder_sent_for=2026-08-08 18:00:00|3 and _arraysubs_renewal_reminder_sent_at=2026-08-05 18:41:06.
- Duplicate probe action 15015 completed at 2026-08-05 18:45:06Z; latest Mailpit id remained 4hDpBKdzRVitPWnpuJ6c7x, proving zero second reminder mail after DEDUPE_PRE.
- Evidence files: /home/server-manager/slt-evidence/SLT-EML-01-baseline.txt, /home/server-manager/slt-evidence/SLT-EML-01-postfire.txt, /home/server-manager/slt-evidence/SLT-EML-01-dedupe.txt, /home/server-manager/slt-evidence/SLT-EML-01-02-mailpit-reminder.png, /home/server-manager/slt-evidence/SLT-EML-01-03-completed-action.png, /home/server-manager/slt-evidence/SLT-EML-01-04-dedupe-preflight.png.
