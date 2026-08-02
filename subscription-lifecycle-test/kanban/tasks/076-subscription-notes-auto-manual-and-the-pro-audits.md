---
id: 76
title: Subscription notes (auto + manual) and the pro Audits screens for a failed renewal
status: todo
priority: high
created: 2026-08-02T03:43:09.614552651+02:00
updated: 2026-08-02T03:43:20.382620042+02:00
tags:
    - admin
    - portal
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 1h30m
depends_on:
    - 23
    - 5
class: standard
---

> **SLT-ADM-09** · group `admin` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · impossible-timing / cross-group date contradiction** — with `SLT-DUN-01`, `SLT-DUN-02`, `SLT-DUN-03`, `SLT-DUN-04`, `SLT-DUN-05`, `SLT-EML-04`

- *Problem:* SLT-DUN-01 is tagged d0 (buy SLT Retry Daily as slt-fail on 2026-08-02, D=08-03, hold 08-04, cancel 08-07). Four other tasks encode the opposite timeline as fact: SLT-EML-04 ('bought on D2 (2026-08-04 PM) ... D = 2026-08-05 PM ... attempts 08-05/06/07/08 -> watch D4..D7 ... on-hold 08-06 ... cancelled 08-09'), SLT-EML-14 ('Retry Daily fails 08-05 PM -> on-hold 08-06 -> cancelled 08-09'), SLT-ADM-09 ('bought D2 by slt-fail ... renewal failed D3 PM'), and SLT-MYA-05 ('Must finish before 12:00 site on D2 (2026-08-04): the dunning group buys SLT Retry Daily as slt-fail with card 0341 that afternoon and the grant fires only on that activation'). slt-fail + SLT Retry Daily cannot be bought twice (auto-migrate), so exactly one timeline can exist. Additionally MYA-05's pro_member role-mapping rule MUST be written before the checkout - if DUN-01 runs on D0 the role grant never fires and MYA-05 is unrunnable.
- *Required fix:* DUN-01 moves to D2 (2026-08-04), checkout 13:00-14:00 site - which is what four downstream tasks already assume and what the audit's corrected calendar says. Resulting ladder, all fixed: D=08-05 13:00-14:00; failure at D+k (08-05 13:00-20:00, watch D4); on-hold at the first hourly sweep after D+24h = 08-06 ~14:00 (watch D5); retries at +24h/+48h/+72h = 08-06/07/08 (watch D5/D6/D7); 4th charge hits the cap 08-08; cancellation at max(D+96h, on_hold+72h) = 08-09 ~14:00-16:00 (watch D8). Re-day the group: DUN-01 D2, DUN-03 D4, DUN-02 D5 (with reads on D4 and D6), DUN-04 D7, DUN-05 D7 after 16:00 (S2 bought 08-09 16:30, fails 08-10 PM, recovered on the morning of 08-11 before N+24h). MYA-05 stays D2 morning, strictly before 13:00.

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
Check the auto-notes lifecycle events write (`AutoNotes`, `RenewalProcessor`) against their exact source strings, that a manual note differs from a system note, and that the pro Audits screens surface the failure with a usable message.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (slt-fail, slt-core)
- Plugins: both

## Preconditions
- `SLT Retry Daily` ($13.00/day, card `4000 0000 0000 0341`) bought D2 by `slt-fail` -> S_FAIL: renewal failed D3 PM, retry #1 D4 PM, on-hold D5. S1 = `SLT Daily Core` (slt-core), success notes.
- Run D5 = 2026-08-07 after 18:00 site so the failure, a `retry_scheduled` and the on-hold transition all exist.
- Stripe retry config is hardcoded `enabled/3/86400` and retries are NOT spread (SLT-REF-03 §2): retry N = first failure + N*24h. Leave `audits.job_log_mode` = `all`.

## Test data
| Item | Value |
|---|---|
| Failing | S_FAIL (SLT Retry Daily $13.00, slt-fail) |
| Healthy | S1 (SLT Daily Core $10.00, slt-core) |

## Steps
1. `mailpit-agent latest-id` -> `M0` (manual notes must send no mail).
2. Open `...#/subscriptions/detail/<S_FAIL>` -> `snapshot -i`; screenshot the notes timeline, copy each note verbatim with timestamp. Repeat for `<S1>`.
3. `wp post list --post_type=arraysubs_sub_note --meta_key=_subscription_id --meta_value=<S_FAIL> --fields=ID,post_content --allow-root`, then `wp post meta list <note_id> --keys=_note_type,_is_system,_added_by,_event_type`.
4. In the notes box on `<S1>` add `SLT-ADM-09 manual private note` (private) then `SLT-ADM-09 manual customer note` (customer); re-snapshot. `mailpit-agent latest-id` must still equal `M0`.
5. Open `/my-account/view-subscription/<S1>/` as `--session cust-adm09` (`slt-core`); record which manual note is visible.
6. Open `...#/audits/scheduled-job-logs`; find `arraysubs_process_renewal` rows for `<S_FAIL>`, copy both summary lines of a failed and a successful run.
7. Open `...#/audits/renewal-failures`; confirm S_FAIL is listed with reason, attempts and last-attempt time. Do NOT click Retry or Resolve.
8. Open `...#/audits/activity-audits` and `...#/settings/gateways` (Gateway Logs); record whether the decline shows and its message.
9. `wp post meta list <S_FAIL> --keys=_payment_retry_attempts,_payment_retry_next_attempt_at,_last_payment_failure_reason,_on_hold_date`.
10. Deferred (watch, D7 = 2026-08-09): after the cancel sweep record the cancellation note — expect `Automatically cancelled by System after the unpaid renewal remained overdue beyond the grace period.`

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
| 1 | NONE EXPECTED | Manual notes (step 4) | — | — | `mailpit-agent latest-id` equals `M0` |
| 2 | Earlier failure mail (read-only) | past attempts | slt-fail | `Payment failed for subscription` | `mailpit-agent list 50`, one pair per attempt |

## Evidence to capture
- Screenshots `SLT-ADM-09-01-notes-fail.png`, `-02-notes-ok.png`, `-03-manual.png`, `-04-jobs.png`, `-05-failures.png`, `-06-gw.png`.
- Verbatim note texts with `_event_type`/`_is_system`/`_added_by`, step-9 dump, Mailpit ids, console errors on the Audits screens (REST 4xx/5xx is a failure).

## Pass criteria
- [ ] Success, failure, retry_scheduled, invoice and status-change notes present with exact texts/event types
- [ ] Retry time = first failure + 24 h (unspread)
- [ ] Manual notes non-system, admin-attributed, only the customer one in my-account, no mail
- [ ] Scheduled-Job Logs shows the failed renewal with a useful message
- [ ] Renewal Failures matches S_FAIL's metas; Gateway Logs recorded

## Isolation / teardown
- Read-only apart from two manual notes on S1 (leave them; SLT-SETUP-99B deletes the sub). Never click Retry or Resolve — it breaks the dunning ladder. Nothing else changed. Close `cust-adm09`.

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
