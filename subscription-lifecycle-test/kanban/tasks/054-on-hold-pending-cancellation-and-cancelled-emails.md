---
id: 54
title: On-hold, pending-cancellation and cancelled emails through real status transitions
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
estimate: 1h 15m
depends_on:
    - 18
class: standard
---

> **SLT-EML-07** · group `emails` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Drive `S_EML` through real transitions — active → on-hold → active → scheduled cancel → cancelled — proving `subscription_on_hold`, `subscription_pending_cancellation` (+admin) and `subscription_cancelled` (+admin) each fire once, with the right subject, recipient and gate.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt2-eml`)
- Plugins: free-only

## Preconditions
- SLT-EML-06 done; `S_EML` active on SLT2 Daily Core ($10.00/day, `slt2-eml`).
- Frozen baseline: `cancellation.cancel_immediately=false`, `require_reason=true`, `retention_offers_enabled=true`.
- Act late afternoon, after the day's renewal leg completed. Sessions `admin-SLT-EML-07`, `cust-SLT-EML-07`.

## Test data
| Item | Value |
|---|---|
| Subscription | `S_EML` (SLT2 Daily Core) |
| Gates | `emails.subscription_{on_hold,pending_cancellation,cancelled}.enabled`; `emails.admin_{pending_cancellation,cancelled}` |

## Steps
1. Resolve registry alias `S_EML` into shell variable `S_EML`, abort unless `[[ "$S_EML" =~ ^[0-9]+$ ]]`, and read `_next_payment_date`. Run `wp db query "SELECT action_id,hook,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE status='pending' AND JSON_UNQUOTE(JSON_EXTRACT(args,'\$[0]'))='$S_EML' ORDER BY scheduled_date_gmt,action_id;" --allow-root`. If a renewal leg is due within 90 min, wait — never force-run.
2. `P1=$(mailpit-agent latest-id)`. In `admin-SLT-EML-07`, open the exact ArraySubs subscription detail, set `S_EML` **Status = On hold**, and save; capture the resulting status/notice as `SLT-EML-07-01-onhold.png`. Run `mailpit-agent wait-new "$P1" 90 "is on hold"`; save the exact matched ID, inspect the complete P1 delta, and `mailpit-agent text <matched-id>`.
3. `P2=$(mailpit-agent latest-id)`. **Status = Active** → Update; `mailpit-agent wait-new "$P2" 90 "reactivated"`; save/show the exact matched id and inspect the complete P2 delta: no `is active` / `New subscription` mail attributable to S_EML (`EmailManager.php:345-349`).
4. `P3=$(mailpit-agent latest-id)`. In `cust-SLT-EML-07`, open `/my-account/subscriptions/` → exact `S_EML` → **Cancel**; enter reason `Not using it enough`, **decline** every retention offer (accepting writes `_retention_offer_accepted` and sends retention mail instead), and capture the fully prepared modal before confirmation as `SLT-EML-07-02-cancel-modal.png`; then confirm.
5. Run `mailpit-agent wait-new "$P3" 90 "Subscription #$S_EML scheduled to cancel"` once to gate arrival, then inspect every message newer than `P3`. Require exactly two messages with that exact subject — one to the customer and one to the admin — `show` both and record `To:`. Set a fresh no-duplicate baseline, reopen the exact customer detail, and capture the already-scheduled state. If the Cancel control remains available, repeat the same browser flow once; otherwise record that it is absent. In either UI branch require no second pair tied to `S_EML` and exactly one cancellation action (`_arraysubs_pending_cancel_email_sent_for` guard); classify unrelated mail.
6. `wp post meta list "$S_EML" --keys=_waiting_cancellation,_cancellation_scheduled_date,_cancellation_type --allow-root`; query the exact pending `arraysubs_cancel_subscription` row for numeric `S_EML`, require exactly one row, and publish its action ID/literal D4 gate plus `gate−5m` baseline deadline to the registry and D03 watch report. Close only `admin-SLT-EML-07` and `cust-SLT-EML-07`, keep this card `in-progress`, and **never force the cancel action**. No earlier than five minutes before that exact gate, record `P4=$(mailpit-agent latest-id)` in the registry/task evidence.
7. **Follow-up D4 (2026-08-27), after that timestamp:** reopen `admin-SLT-EML-07`, recheck exact status/action, then gate once on `mailpit-agent wait-new "$P4" 90 "been cancelled"` and inspect the complete delta after `P4`; require exactly one customer cancellation and one admin `cancelled by` message and show both. Capture the exact cancelled detail as `SLT-EML-07-03-cancelled-D4.png`, require the action complete, and prove no post-cancellation renewal order/action was created. If any live assertion fails, create a QA issue card under `qa/issues/` containing this task/plan, subscription/order/action and user/login/role IDs, exact admin/customer URL/context, reproduction, expected/actual, UI/Mailpit/meta/action proof, and the prior successful on-hold/reactivation counterexample; never force the action; leave the lifecycle task blocked. Close only `admin-SLT-EML-07`, independently review the full D3/D4 evidence, move this card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Step 2: status `arraysubs-on-hold`; one mail `[mirror-help.arrayhash.com] Your subscription #<S_EML> is on hold` to `slt2-eml@example.test`; no admin on-hold email exists.
2. Step 4: pending cancellation is not a status — the record stays `arraysubs-active` with `_waiting_cancellation=1`, `_cancellation_type=end_of_period` and `_cancellation_scheduled_date` = `_next_payment_date` (`cancellation-helpers.php:170-204`).
3. Two mails `Subscription #<S_EML> scheduled to cancel on <date>` — to `slt2-eml@example.test` and `admin@mirror-help.arrayhash.com`; no duplicate on re-open.
4. One pending `arraysubs_cancel_subscription` at that literal timestamp (no spread offset) and no pending renewal action (`RecurringBilling/Hooks.php:269-275`).
5. D4: status `arraysubs-cancelled`; `Your subscription #<S_EML> has been cancelled` to the customer and `Subscription #<S_EML> cancelled by SLT2 Eml` to admin; no renewal order afterwards; all five gates `true`.

## Emails expected
| # | Email | Trigger | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | subscription_on_hold | step 2 | slt2-eml | `#<S_EML> is on hold` | `mailpit-agent wait-new "$P1" 90 "is on hold"` |
| 2 | subscription_reactivated | step 3 | slt2-eml | `has been reactivated` | `mailpit-agent wait-new "$P2" 90 "reactivated"` |
| 3 | pending_cancellation | step 4 | slt2-eml | `Subscription #<S_EML> scheduled to cancel on` | one gated wait after `P3`, then complete delta; customer `To:` |
| 4 | admin_pending_cancellation | step 4 | admin | same exact subject | same complete `P3` delta; admin `To:` |
| 5 | subscription_cancelled | D4 | slt2-eml | `has been cancelled` | `mailpit-agent wait-new "$P4" 60 "been cancelled"` |
| 6 | admin_subscription_cancelled | D4 | admin | `cancelled by SLT2 Eml` | `mailpit-agent wait-new "$P4" 60 "cancelled by"` |
| 7 | NONE EXPECTED — new_subscription / retention mail | steps 3-4 | — | — | absent from the complete P2/P3 deltas for exact S_EML |

## Evidence to capture
- `SLT-EML-07-01-onhold.png`, `-02-cancel-modal.png`, `-03-cancelled-D4.png`; every Mailpit ID, step-6 dump, exact AS action/gate/baseline deadline, no-duplicate branch, session teardown, and review proof.

## Pass criteria
- [ ] on-hold mail once, right subject/recipient, no admin copy; reactivated mail once with no `new_subscription`
- [ ] pending-cancel pair carries the scheduled date, no duplicate pair, meta + one cancel action as results 2 and 4
- [ ] Cancelled pair after the scheduled timestamp, status `arraysubs-cancelled`; no retention mail; gates true
- [ ] Exact D4 gate handed off before D3 session closure; no post-cancel renewal; task reviewed to done with Review empty

## Isolation / teardown
- Leaves `S_EML` scheduled for cancellation late D3, then cancelled on D4 — silent after cancellation through D7; record the exact cancellation time in the registry so the watch can assert that silence.
- Hand-off: EML-08 (D8) reactivates it, EML-10 (D8) cancels it for good. No global setting touched, sessions closed.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
