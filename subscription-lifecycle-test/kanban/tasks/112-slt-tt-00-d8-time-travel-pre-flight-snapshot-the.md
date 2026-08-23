---
id: 112
title: 'SLT-TT-00 D8 time-travel owner: pre-flight plus targeted month-segment-2 and week-segment-3 renewals'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - day-08
due: "2026-08-31"
estimate: 2h
depends_on:
    - 45
    - 74
    - 46
class: standard
---

> **SLT-TT-00** · group `renewal` · scheduled **D08** (2026-08-31)

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
- `SLT-SYN-07` handed off active week-segment-3 subscription `<SUB_W3>` with first full renewal `2026-09-04 18:00:00` UTC. `SUB_NC` is a different, daily next-cycle subscription and is never a D8 forced target.
- The `SLT-SYN-13` Full and Next Cycle subscriptions are natural-watch fixtures owned by task 46. Confirm their fresh registry state, but never edit or run their actions in this bracket.

## Test data
| Item | Value |
|---|---|
| Scope | every `arraysubs_data` post, SLT2 and non-SLT |
| Output | `/home/server-manager/slt-evidence/SLT-TT-00-preflight.txt` and the `slt2-catalog-registry` page |
| Forced targets | `<SUB_M>` (month segment 2), then `<SUB_W3>` (week segment 3) |

## Steps
1. Load the agent-browser core guide if needed. Open **Tools → Scheduled Actions → Pending** with `--session admin-SLT-TT-00`, filter to `arraysubs`, and save `/home/server-manager/slt-evidence/SLT-TT-00-01-pending-before.png`. Do not set a renewal-mail baseline before the safety preflight.
2. From `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public`, dump every subscription's schedule state to `/home/server-manager/slt-evidence/SLT-TT-00-preflight.txt`:
   `wp db query "SELECT p.ID, p.post_status, MAX(CASE WHEN pm.meta_key='_next_payment_date' THEN pm.meta_value END) AS next_payment FROM wp_posts p LEFT JOIN wp_postmeta pm ON pm.post_id=p.ID WHERE p.post_type='arraysubs_data' GROUP BY p.ID, p.post_status ORDER BY p.ID;" --allow-root`
3. Split the dump into **registered SLT2 subscription IDs** and **non-SLT2** IDs by using the fresh registry as the authoritative membership list, not creation date alone. Record every non-SLT2 post status and `_next_payment_date` verbatim, compare with the D0 baseline, and explain any delta before proceeding.
4. Publish both lists to `slt2-catalog-registry`. Every other D8 task must quote the non-SLT2 list in its own evidence.
5. Post the execution contract to the registry: every `wp action-scheduler run --hooks=...` or `--group=...` command is banned. Only **Run** on an already-recorded exact action ID in the Scheduled Actions UI is permitted, one row at a time. Never use a guessed hook or a row whose args are not exactly the intended subscription ID.
6. State the STOP condition in the registry: if a non-SLT2 `_next_payment_date` changes, or the target query returns anything other than one pending invoice row plus one pending charge row, stop mutations immediately and create/update a critical `qa/issues/` kanban card.
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
10. Resolve the sole new SUB_M renewal by scheduled-cycle relationship plus reverse link; require paid `$30.00`, payments +1 and next date `2026-09-30 18:00:00` UTC, derived from its stored first-full-renewal anchor rather than the temporary forced due. Poll immutable M0 in repeated ≤60-second calls through five minutes for the exact success mail, assert no invoice mail, then set M_WEEK immediately before the week pair. Re-dump non-SLT2 and require empty diff.
11. Repeat for numeric SUB_W3 using only its IDs. Record its pre-run order set, run exact invoice/charge rows, resolve the sole scheduled-cycle/reverse-linked renewal, require `$14.00`, payments +1 and next date `2026-09-11 18:00:00` UTC, one weekly boundary after its stored first-full renewal. Poll immutable M_WEEK in repeated ≤60-second calls through five minutes; assert no invoice mail.
12. Capture final queue, require empty non-SLT2 diff, append exact actions/orders/mails/dates, close `admin-SLT-TT-00`, independently review both targets, then move through `review` to `done` with Review empty. Any defect goes only in `qa/issues/` kanban card named `SLT-TT-00-<concise-slug>` with task/stage/plan path; targets/products/users/orders/actions/messages; exact session/bracket/commands; reproduction; expected/actual; and pre/post meta/queue/log/order/Mailpit/non-SLT2 proof.

## Expected results
1. A complete, timestamped pre-flight snapshot exists in the canonical evidence directory and on the registry page.
2. The non-SLT2 subscription list is captured with every post status and `_next_payment_date` verbatim, and its final diff is empty.
3. The permitted-command rule and STOP condition are published before any D8 mutation.
4. `<SUB_M>` renews exactly once for `$30.00` and advances to `2026-09-30 18:00:00` UTC.
5. `<SUB_W3>` renews exactly once for `$14.00` and advances to `2026-09-11 18:00:00` UTC.
6. Exactly the four recorded target rows are manually executed, invoice then charge for each subscription; no hook/group drain and no variable-flex action is run.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_successful` | `<SUB_M>` charge action | slt2-flex2@example.test | exact numeric subscription subject | just-in-time baseline; repeated ≤60-second polls through five minutes |
| 2 | `payment_successful` | `<SUB_W3>` charge action | the `SLT-SYN-07` customer | exact numeric subscription subject | just-in-time baseline; repeated ≤60-second polls through five minutes |
| 3 | `renewal_invoice` NONE EXPECTED | both invoice actions | — | — | automatic-payment suppression; any invoice mail is a finding |

## Evidence to capture
- `SLT-TT-00-01..06` screenshots; pre/post full dumps; SLT2 and non-SLT2 lists; both target meta dumps; four exact action IDs; two renewal order IDs/totals; Mailpit IDs; final empty non-SLT2 diff; registry revision.

## Pass criteria
- [ ] Pending queue screenshotted before any D8 mutation
- [ ] Every subscription's `_next_payment_date` captured, split SLT2 vs non-SLT
- [ ] Permitted-command rule and STOP condition published to the registry
- [ ] `<SUB_M>` renewed once for $30.00 and advanced to 2026-09-30 18:00:00 UTC
- [ ] `<SUB_W3>` renewed once for $14.00 and advanced to 2026-09-11 18:00:00 UTC
- [ ] Four exact target rows run one-at-a-time; no hook/group drain; no other subscription touched
- [ ] Two payment-success mails and no renewal-invoice mail
- [ ] Both orders relationship-exact; session closes and independent review reaches `done` with Review empty

## Isolation / teardown
- The temporary forced due values are consumed by the two renewal orders; the successful synced lifecycle recalculates from each immutable first-full-renewal anchor and establishes the new real schedule dates above. `SLT-SYN-10` separately owns month segment 3. Variable-flex renewals remain natural-watch evidence and are untouched.
- The snapshot is the safety contract for the whole of D8. This task proves an empty non-SLT2 diff after each target, and the matching end-of-day diff is repeated by `SLT-EML-14`.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
