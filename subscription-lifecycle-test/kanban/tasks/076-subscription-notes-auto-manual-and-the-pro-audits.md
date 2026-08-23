---
id: 76
title: Subscription notes (auto + manual) and the pro Audits screens for a failed renewal
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - admin
    - portal
    - day-05
due: "2026-08-28"
estimate: 1h30m
depends_on:
    - 23
    - 5
    - 1
    - 33
class: standard
---

> **SLT-ADM-09** · group `admin` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Check the auto-notes lifecycle events write (`AutoNotes`, `RenewalProcessor`) against their exact source strings, that a manual note differs from a system note, and that the pro Audits screens surface the failure with a usable message.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (slt2-fail, slt2-core)
- Plugins: both

## Preconditions
- `SLT2 Retry Daily` ($13.00/day, card `4000 0000 0000 0341`) bought D2 by `slt2-fail` -> S_FAIL: renewal failed D3 PM, retry #1 and the on-hold transition occur D4 PM. S1 = `SLT2 Daily Core` (slt2-core), success notes.
- Run D5 = 2026-08-28 after 18:00 site so the failure, a `retry_scheduled` and the on-hold transition all exist.
- Stripe retry config is hardcoded `enabled/3/86400` and retries are NOT spread (SLT-REF-03 §2): retry N = first failure + N*24h. Leave `audits.job_log_mode` = `all`.

## Test data
| Item | Value |
|---|---|
| Failing | S_FAIL (SLT2 Retry Daily $13.00, slt2-fail) |
| Healthy | S1 (SLT2 Daily Core $10.00, slt2-core) |

## Steps
1. Resolve strict numeric, distinct `S_FAIL` and `S1`, their exact customers/products, first-failure and healthy-renewal order IDs, and owned scheduler/sweep gates from the registry and prior watch reports; abort as upstream `BLOCKED` if either source fixture is absent rather than selecting by recency. Set `M0=$(mailpit-agent latest-id)` immediately before the manual-note mutation.
2. In `agent-browser --session admin-SLT-ADM-09`, open `...#/subscriptions/detail/$S_FAIL` → `snapshot -i`; capture `SLT-ADM-09-01-notes-fail.png` and copy every relevant note verbatim with timestamp. Repeat for `$S1` as `SLT-ADM-09-02-notes-ok.png`.
3. Query `arraysubs_sub_note` posts by exact numeric `_subscription_id` separately for `$S_FAIL` and `$S1`; record every numeric note ID/content. For each asserted note run `wp post meta list "$NOTE_ID" --keys=_note_type,_is_system,_added_by,_event_type --allow-root`; never use a recent-note assumption.
4. Record the exact pre-mutation S1 note-ID set. In the notes box on `$S1`, add `SLT-ADM-09 manual private note` (private) then `SLT-ADM-09 manual customer note` (customer); capture `SLT-ADM-09-03-manual.png`. Resolve exactly two new numeric note IDs by set difference, match each exact content/visibility, and inspect the complete delta after `M0`; require zero task-attributable message and classify background mail.
5. Open `/my-account/view-subscription/$S1/` in `cust-adm09-SLT-ADM-09` (`slt2-core`), capture `SLT-ADM-09-03a-customer-note-visibility.png`, and require only the exact customer-note ID/content to appear.
6. In `admin-SLT-ADM-09`, open `...#/audits/scheduled-job-logs`; locate the failed `arraysubs_process_renewal` row by `$S_FAIL` plus its exact action/order/time, and the successful row by `$S1` plus its exact action/order/time. Capture `SLT-ADM-09-04-jobs.png` and copy both two-line summaries; do not require an impossible successful renewal row for S_FAIL.
7. Open `...#/audits/renewal-failures`; confirm `$S_FAIL` is listed with exact reason, attempts and last-attempt time, and capture `SLT-ADM-09-05-failures.png`. Do NOT click Retry or Resolve.
8. Open `...#/audits/activity-audits` and capture the exact failure/healthy contexts as `SLT-ADM-09-06-activity.png`; then open `...#/settings/gateways` (Gateway Logs), capture `SLT-ADM-09-07-gateway.png`, and record whether the same decline appears and its exact message.
9. Run `wp post meta list "$S_FAIL" --keys=_payment_retry_attempts,_payment_retry_next_attempt_at,_last_payment_failure_reason,_on_hold_date --allow-root`; reconcile values against the exact failure/action timestamps. Cite/show the earlier customer/admin failure-mail IDs only from their registered bounded `DUN_*_PRE` deltas. Close the two D5 sessions and leave the card `in-progress` for step 10.
10. Deferred D7 (2026-08-30): use only the exact natural cancel-sweep gate published by `SLT-DUN-03`, never force it, and open `admin-SLT-ADM-09-D7` after completion. Resolve the new cancellation note for `$S_FAIL` by exact pre/post note-ID set difference, capture it as `SLT-ADM-09-08-cancellation-note.png`, and require `Automatically cancelled by System after the unpaid renewal remained overdue beyond the grace period.` with its system/event metadata.
11. If any live assertion fails, create a dedicated `qa/issues/` kanban card named `SLT-ADM-09-<concise-slug>` (create the required QA issue card) containing this progress task/stage and plan path; subscription/order/action/note IDs; affected user ID/login/email/role; exact routes and sessions; reproduction; expected/actual; UI, meta, audit, Mailpit, and screenshot proof; and the healthy/failing counterexample. Continue unaffected read-only checks. After D7, independently review all evidence, close only `admin-SLT-ADM-09-D7`, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Success note on S1: `Payment successful - Order #<id> (<total>)` linking to the order; `renewal_payment_succeeded`, `_is_system = 1`, `_audit_entity = order`.
2. Failure notes on S_FAIL: `Payment failed for Order #<id>. Gateway: <label>. Reason: <message>` plus `Renewal payment failed: <reason>. Gateway message: "<raw>".`, both `renewal_payment_failed`.
3. Retry note: `Scheduled retry 1 of 3 at <site-local time>. Plugin verifies with the gateway before re-charging…`, `retry_scheduled`, time = first failure + 24 h.
4. On-hold note: `Status changed from Active to On hold. Changed by <actor>.` plus `The subscription was placed on hold because the payment did not complete.`
5. Invoice note: `Renewal invoice #<id> generated for payment due on <date>.` Manual notes: `_is_system = 0`, `_added_by = <admin id>`, only the customer one in my-account, zero mail.
6. Scheduled-Job Logs marks the failed `arraysubs_process_renewal` Failed with the gateway reason on line 2; successes read `Processed renewal for subscription #<id>.` Renewal Failures lists S_FAIL with a message matching `_last_payment_failure_reason`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Manual notes (step 4) | — | — | Complete delta after `M0`; zero task-attributable mail, while unrelated/background mail is allowed and classified |
| 2 | Earlier failure mail (read-only) | past attempts | slt2-fail | `Payment failed for subscription` | cite/show the exact ids from the registered bounded `DUN_*_PRE` deltas, one customer/admin pair per attempt |

## Evidence to capture
- Screenshots `SLT-ADM-09-01` through `-08`, each bound to the exact note/audit/gateway/cancellation state above.
- Numeric subscription/customer/product/order/action/note IDs; pre/post note sets; verbatim texts with `_event_type`/`_is_system`/`_added_by`; step-9 dump; exact Mailpit IDs; session/review proof; console errors on the Audits screens (REST 4xx/5xx is a failure).

## Pass criteria
- [ ] Success, failure, retry_scheduled, invoice and status-change notes present with exact texts/event types
- [ ] Retry time = first failure + 24 h (unspread)
- [ ] Manual notes non-system, admin-attributed, only the customer one in my-account, no mail
- [ ] Scheduled-Job Logs shows the failed renewal with a useful message
- [ ] Renewal Failures matches S_FAIL's metas; Gateway Logs recorded
- [ ] D7 cancellation note selected by exact set difference; all phase sessions closed and final evidence reviewed to done

## Isolation / teardown
- Read-only apart from two manual notes on S1 (leave them; SLT-SETUP-99B deletes the sub). Never click Retry or Resolve — it breaks the dunning ladder. Nothing else changed. Close only `admin-SLT-ADM-09` and `cust-adm09-SLT-ADM-09` after D5, then only `admin-SLT-ADM-09-D7` after the deferred phase.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
