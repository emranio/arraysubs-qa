---
id: 54
title: On-hold, pending-cancellation and cancelled emails through real status transitions
status: todo
priority: high
created: 2026-08-02T03:43:07.722706245+02:00
updated: 2026-08-02T03:43:18.146275044+02:00
tags:
    - email
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h 15m
depends_on:
    - 18
class: standard
---

> **SLT-EML-07** · group `emails` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Drive `S_EML` through real transitions — active → on-hold → active → scheduled cancel → cancelled — proving `subscription_on_hold`, `subscription_pending_cancellation` (+admin) and `subscription_cancelled` (+admin) each fire once, with the right subject, recipient and gate.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-eml`)
- Plugins: free-only

## Preconditions
- SLT-EML-06 done; `S_EML` active on SLT Daily Core ($10.00/day, `slt-eml`).
- Frozen baseline: `cancellation.cancel_immediately=false`, `require_reason=true`, `retention_offers_enabled=true`.
- Act late afternoon, after the day's renewal leg completed. Sessions `admin-SLT-EML-07`, `cust-SLT-EML-07`.

## Test data
| Item | Value |
|---|---|
| Subscription | `S_EML` (SLT Daily Core) |
| Gates | `emails.subscription_{on_hold,pending_cancellation,cancelled}.enabled`; `emails.admin_{pending_cancellation,cancelled}` |

## Steps
1. Read `_next_payment_date`; `wp action-scheduler list --status=pending --search=<S_EML> --allow-root`. If a renewal leg is due within 90 min, wait — never force-run.
2. `mailpit-agent latest-id` → `$P1`. Admin → edit `S_EML` → **Status = On hold** → Update; `wait-new "$P1" 90 "is on hold"`; `text latest`.
3. `latest-id` → `$P2`. **Status = Active** → Update; `wait-new "$P2" 90 "reactivated"`; `list 20`: no `is active` / `New subscription` mail (`EmailManager.php:345-349`).
4. `latest-id` → `$P3`. Customer → `/my-account/subscriptions/` → `S_EML` → **Cancel**; reason `Not using it enough`; **decline** every retention offer (accepting writes `_retention_offer_accepted` and sends retention mail instead); confirm.
5. `wait-new "$P3" 90 "scheduled to cancel"` twice (customer + admin); `show` both, record To:. Re-open Cancel: no 2nd pair (`_arraysubs_pending_cancel_email_sent_for` guard).
6. `wp post meta list <S_EML> --keys=_waiting_cancellation,_cancellation_scheduled_date,_cancellation_type --allow-root`; `wp action-scheduler list --hooks=arraysubs_cancel_subscription --status=pending --allow-root`. Record `$P4 = latest-id`. **Never force the cancel action.**
7. **Follow-up D3 (2026-08-05), after that timestamp:** recheck status, then `wait-new "$P4" 60 "been cancelled"` and `wait-new "$P4" 60 "cancelled by"`. If still active with the action pending or failed, screenshot the AS row and file an issue — never force it.

## Expected results
1. Step 2: status `arraysubs-on-hold`; one mail `[mirror-help.arrayhash.com] Your subscription #<S_EML> is on hold` to `slt-eml@example.test`; no admin on-hold email exists.
2. Step 4: pending cancellation is not a status — the record stays `arraysubs-active` with `_waiting_cancellation=1`, `_cancellation_type=end_of_period` and `_cancellation_scheduled_date` = `_next_payment_date` (`cancellation-helpers.php:170-204`).
3. Two mails `Subscription #<S_EML> scheduled to cancel on <date>` — to `slt-eml@example.test` and `admin@mirror-help.arrayhash.com`; no duplicate on re-open.
4. One pending `arraysubs_cancel_subscription` at that literal timestamp (no spread offset) and no pending renewal action (`RecurringBilling/Hooks.php:269-275`).
5. D3: status `arraysubs-cancelled`; `Your subscription #<S_EML> has been cancelled` to the customer and `Subscription #<S_EML> cancelled by SLT Eml` to admin; no renewal order afterwards; all five gates `true`.

## Emails expected
| # | Email | Trigger | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | subscription_on_hold | step 2 | slt-eml | `#<S_EML> is on hold` | `wait-new "$P1" 90 "is on hold"` |
| 2 | subscription_reactivated | step 3 | slt-eml | `has been reactivated` | `wait-new "$P2" 90 "reactivated"` |
| 3 | pending_cancellation | step 4 | slt-eml | `scheduled to cancel on` | `wait-new "$P3" 90 "scheduled to cancel"` |
| 4 | admin_pending_cancellation | step 4 | admin | same subject | same call, 2nd message |
| 5 | subscription_cancelled | D3 | slt-eml | `has been cancelled` | `wait-new "$P4" 60 "been cancelled"` |
| 6 | admin_subscription_cancelled | D3 | admin | `cancelled by SLT Eml` | `wait-new "$P4" 60 "cancelled by"` |
| 7 | NONE EXPECTED — new_subscription / retention mail | steps 3-4 | — | — | absent from `list 20` |

## Evidence to capture
- `SLT-EML-07-01-onhold.png`, `-02-cancel-modal.png`, `-03-cancelled-D3.png`; Mailpit ids, step 6 dump, AS action id.

## Pass criteria
- [ ] on-hold mail once, right subject/recipient, no admin copy; reactivated mail once with no `new_subscription`
- [ ] pending-cancel pair carries the scheduled date, no duplicate pair, meta + one cancel action as results 2 and 4
- [ ] Cancelled pair after the scheduled timestamp, status `arraysubs-cancelled`; no retention mail; gates true

## Isolation / teardown
- Leaves `S_EML` cancelled — silent D3..D7; record the cancellation time in the registry so the watch can assert that silence.
- Hand-off: EML-08 (D8) reactivates it, EML-10 (D8) cancels it for good. No global setting touched, sessions closed.

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
