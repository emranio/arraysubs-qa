---
id: 112
title: 'SLT-TT-00 D8 time-travel owner: pre-flight plus targeted month-segment-2 and week-segment-3 renewals'
status: done
priority: critical
created: 2026-08-02T03:43:12.31221155+02:00
updated: 2026-08-10T02:24:12.416712633+02:00
started: 2026-08-10T02:24:12.416711882+02:00
completed: 2026-08-10T02:24:12.416711882+02:00
tags:
    - renewal
    - day-08
due: "2026-08-10"
estimate: 2h
depends_on:
    - 45
    - 74
    - 46
class: standard
---

> **SLT-TT-00** · group `renewal` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
D8 is the only day on which this plan manipulates date meta. This task runs first, establishes the shared safety baseline every other D8 task quotes, and owns the two live forced-renewal cohorts that otherwise fall outside the window: month segment 2 (`SUB_M`) and week segment 3 (`SUB_W3`). It re-queues only each target subscription, then runs its invoice and charge rows one at a time by exact Action Scheduler ID. It never drains a hook or group.

## Scope
- Gateway: Stripe test (existing tokens on both target subscriptions)
- Checkout: N/A
- Account: admin only
- Plugins: pro-required

## Preconditions
- Runs **first** on D8, before `SLT-SYN-10`, `SLT-SW-02`, `SLT-EML-08`, `SLT-EML-10`, `SLT-LIFE-01`, `SLT-EML-14`.
- No other task may touch date meta until this one publishes its snapshot.
- `SLT-SYN-06` handed off active month-segment-2 subscription `<SUB_M>` with first full renewal `2026-08-31 18:00:00` UTC.
- `SLT-SYN-07` handed off active week-segment-3 subscription `<SUB_W3>` with first full renewal `2026-08-14 18:00:00` UTC. `SUB_NC` is a different, daily next-cycle subscription and is never a D8 forced target.
- The `SLT-SYN-13` Full and Next Cycle subscriptions are conditional-only natural-watch fixtures from the current Thursday, August 6, 2026 evidence state: task 46's appended 2026-08-05 closeout says those purchases never existed, so this task must not edit or run them and must not assume they will appear unless the live D8 registry disproves that note.

## Test data
| Item | Value |
|---|---|
| Scope | every `arraysubs_data` post, SLT and non-SLT |
| Output | `/home/server-manager/slt-evidence/SLT-TT-00-preflight.txt` and the `slt-catalog-registry` page |
| Forced targets | `<SUB_M>` (month segment 2), then `<SUB_W3>` (week segment 3) |

## Steps
1. Load the agent-browser core guide if needed. Open **Tools → Scheduled Actions → Pending** with `--session admin-SLT-TT-00`, filter to `arraysubs`, and save `/home/server-manager/slt-evidence/SLT-TT-00-01-pending-before.png`. Do not set a renewal-mail baseline before the safety preflight.
2. From `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public`, dump every subscription's schedule state to `/home/server-manager/slt-evidence/SLT-TT-00-preflight.txt`:
   `wp db query "SELECT p.ID, p.post_status, MAX(CASE WHEN pm.meta_key='_next_payment_date' THEN pm.meta_value END) AS next_payment FROM wp_posts p LEFT JOIN wp_postmeta pm ON pm.post_id=p.ID WHERE p.post_type='arraysubs_data' GROUP BY p.ID, p.post_status ORDER BY p.ID;" --allow-root`
3. Split the dump into **registered SLT subscription IDs** and **non-SLT** IDs by using the `slt-catalog-registry` as the authoritative membership list, not creation date alone. Record every non-SLT post status and `_next_payment_date` verbatim. There were 13 active non-SLT subscriptions at plan start; record the current count and explain any delta before proceeding.
4. Publish both lists to `slt-catalog-registry`. Every other D8 task must quote the non-SLT list in its own evidence.
5. Post the execution contract to the registry: every `wp action-scheduler run --hooks=...` or `--group=...` command is banned. Only **Run** on an already-recorded exact action ID in the Scheduled Actions UI is permitted, one row at a time. Never use a guessed hook or a row whose args are not exactly the intended subscription ID.
6. State the STOP condition in the registry: if a non-SLT `_next_payment_date` changes, or the target query returns anything other than one pending invoice row plus one pending charge row, stop the D8 mutations immediately and write a separate issue file under `issues/` with critical severity. Do not create a lifecycle-board bug card.
7. Resolve registry aliases `SUB_M` and `SUB_W3` into same-named shell variables and abort unless both match `^[0-9]+$`. For numeric `$SUB_M`, capture its original `_next_payment_date`, `_completed_payments`, order IDs, `_renewal_action_id`, `_renewal_invoice_action_id`, and the pending queue. Re-read the six product flex metas and the subscription's `_renewal_sync_*` anchors. Abort this target if it is not active, is awaiting cancellation, has a pending renewal order, or its recorded first-full-renewal anchor differs from the handoff.
8. Re-queue numeric `$SUB_M` without exposing a past-due action to cron. Assign `TARGET_ID="$SUB_M"`, revalidate it as numeric, and run this exact target-scoped recipe:
   ```bash
   TARGET_ID="$SUB_M"
   [[ "$TARGET_ID" =~ ^[0-9]+$ ]] || exit 1
   wp eval "
   \$id = (int) $TARGET_ID;
   \$due = gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS);
   update_post_meta(\$id, '_next_payment_date', \$due);
   \\ArraySubs\\Features\\RecurringBilling\\Services\\RenewalScheduler::unschedule(\$id);
   \\ArraySubs\\Features\\RecurringBilling\\Services\\RenewalScheduler::schedule(\$id, time() + 12 * HOUR_IN_SECONDS);
   printf('id=%d forced_due=%s invoice_id=%s renewal_id=%s\n', \$id, \$due, get_post_meta(\$id, '_renewal_invoice_action_id', true), get_post_meta(\$id, '_renewal_action_id', true));
   " --allow-root
   ```
   The stored due is past so the processor accepts the renewal; the two new action rows are parked safely in the future so the minute cron cannot race the browser. Query the two rows by the exact numeric `$TARGET_ID`, capturing IDs and scheduled GMT values:
   `wp db query "SELECT action_id,hook,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE status='pending' AND hook IN ('arraysubs_generate_renewal_invoice','arraysubs_process_renewal') AND JSON_UNQUOTE(JSON_EXTRACT(args,'\$[0]'))='$TARGET_ID' ORDER BY scheduled_date_gmt,action_id;" --allow-root`
9. Record numeric SUB_M's exact pre-run renewal-order set and set `M0=$(/usr/local/bin/mailpit-agent latest-id 2>/dev/null || true)` immediately before the pair. In `admin-SLT-TT-00`, re-snapshot before each click; run exact invoice ID then exact charge ID, waiting on each ID only. Capture the two images.
10. Resolve the sole new SUB_M renewal by scheduled-cycle relationship plus reverse link; require paid `$30.00`, payments +1 and next date exact. Poll immutable M0 in repeated ≤60-second calls through five minutes for the exact success mail, assert no invoice mail, then set M_WEEK immediately before the week pair. Re-dump non-SLT and require empty diff.
11. Repeat for numeric SUB_W3 using only its IDs. Record its pre-run order set, run exact invoice/charge rows, resolve the sole scheduled-cycle/reverse-linked renewal, require `$14.00`, payments +1 and exact next date, and poll immutable M_WEEK in repeated ≤60-second calls through five minutes; assert no invoice mail.
12. Capture final queue, require empty non-SLT diff, append exact actions/orders/mails/dates, close `admin-SLT-TT-00`, independently review both targets, then move through `review` to `done` with Review empty. Any defect goes only in `issues/SLT-TT-00-<concise-slug>.md` with task/stage/plan path; targets/products/users/orders/actions/messages; exact session/bracket/commands; reproduction; expected/actual; and pre/post meta/queue/log/order/Mailpit/non-SLT proof.

## Expected results
1. A complete, timestamped pre-flight snapshot exists in the canonical evidence directory and on the registry page.
2. The non-SLT subscription list is captured with every post status and `_next_payment_date` verbatim, and its final diff is empty.
3. The permitted-command rule and STOP condition are published before any D8 mutation.
4. `<SUB_M>` renews exactly once for `$30.00` and advances to `2026-09-30 18:00:00` UTC.
5. `<SUB_W3>` renews exactly once for `$14.00` and advances to `2026-08-21 18:00:00` UTC.
6. Exactly the four recorded target rows are manually executed, invoice then charge for each subscription; no hook/group drain and no variable-flex action is run.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_successful` | `<SUB_M>` charge action | slt-flex2@example.test | exact numeric subscription subject | just-in-time baseline; repeated ≤60-second polls through five minutes |
| 2 | `payment_successful` | `<SUB_W3>` charge action | the `SLT-SYN-07` customer | exact numeric subscription subject | just-in-time baseline; repeated ≤60-second polls through five minutes |
| 3 | `renewal_invoice` NONE EXPECTED | both invoice actions | — | — | automatic-payment suppression; any invoice mail is a finding |

## Evidence to capture
- `SLT-TT-00-01..06` screenshots; pre/post full dumps; SLT and non-SLT lists; both target meta dumps; four exact action IDs; two renewal order IDs/totals; Mailpit IDs; final empty non-SLT diff; registry revision.

## Pass criteria
- [ ] Pending queue screenshotted before any D8 mutation
- [ ] Every subscription's `_next_payment_date` captured, split SLT vs non-SLT
- [ ] Permitted-command rule and STOP condition published to the registry
- [ ] `<SUB_M>` renewed once for $30.00 and advanced to 2026-09-30 18:00:00 UTC
- [ ] `<SUB_W3>` renewed once for $14.00 and advanced to 2026-08-21 18:00:00 UTC
- [ ] Four exact target rows run one-at-a-time; no hook/group drain; no other subscription touched
- [ ] Two payment-success mails and no renewal-invoice mail
- [ ] Both orders relationship-exact; session closes and independent review reaches `done` with Review empty

## Isolation / teardown
- The deliberately forced target dates are not restored; the successful renewals establish the new real schedule anchors above. `SLT-SYN-10` separately owns month segment 3. Variable-flex renewals remain natural-watch evidence and are untouched.
- The snapshot is the safety contract for the whole of D8. This task proves an empty non-SLT diff after each target, and the matching end-of-day diff is repeated by `SLT-EML-14`.

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

[[2026-08-06]] Thu 22:03
Current evidence correction on 2026-08-06: variable-flex `SLT-SYN-13` renewals are not guaranteed D8/D11 observations from the present board state because task 46 now closes `UNVERIFIED` and says the two authored purchases never existed. Keep them conditional-only unless the live registry on the actual Monday, August 10, 2026 D8 run disproves that note. This task still owns only the real `SUB_M` and `SUB_W3` targeted time-travel pairs.

[[2026-08-10]] Mon 02:24
D08 execution complete with mixed result. Safety preflight PASS: browser Pending queue captured in /home/server-manager/slt-evidence/SLT-TT-00-01-pending-before.png; complete 379-subscription schedule dump, live 25-row SLT split, 354-row non-SLT split, 13-record baseline cohort, source checks, and failed-action query saved in /home/server-manager/slt-evidence/SLT-TT-00-preflight.txt. Registry page 11847 was updated/read back at 2026-08-10T00:23:12Z with the lists, exact-ID-only contract, and STOP condition. Non-SLT digest remained d2aa321f3fabe9bebfaca3baf6ba9b2c5d42346fa3c761c3f0d179dad09e9285 before/after. Forced SUB_M and SUB_W3 branches are UNVERIFIED: user 354 owns only 12172/product 12099; user 355 owns only 12193/product 12102 and 13277/product 12093. No substitute, date write, queue mutation, Run click, order, or mail was used. Independent review checked the screenshot, registry readback, source cards 45/74, and exact digest; session closed and Review returned to zero.
