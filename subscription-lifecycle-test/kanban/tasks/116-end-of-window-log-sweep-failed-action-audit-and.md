---
id: 116
title: End-of-window log sweep, failed-action audit and cycle-to-order reconciliation
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - edge-cases
    - day-09
due: "2026-09-01"
estimate: 4h
depends_on:
    - 1
    - 2
    - 3
    - 4
    - 5
    - 6
    - 7
    - 8
    - 9
    - 10
    - 11
    - 12
    - 13
    - 14
    - 15
    - 16
    - 17
    - 18
    - 19
    - 20
    - 21
    - 22
    - 23
    - 24
    - 25
    - 26
    - 27
    - 28
    - 29
    - 30
    - 31
    - 32
    - 33
    - 34
    - 35
    - 36
    - 37
    - 38
    - 39
    - 40
    - 41
    - 42
    - 43
    - 44
    - 45
    - 46
    - 47
    - 48
    - 49
    - 50
    - 51
    - 52
    - 53
    - 54
    - 55
    - 56
    - 57
    - 58
    - 59
    - 60
    - 61
    - 62
    - 63
    - 64
    - 65
    - 66
    - 67
    - 68
    - 69
    - 70
    - 71
    - 72
    - 73
    - 74
    - 75
    - 76
    - 77
    - 78
    - 79
    - 80
    - 81
    - 82
    - 83
    - 84
    - 85
    - 86
    - 87
    - 88
    - 89
    - 90
    - 91
    - 92
    - 93
    - 94
    - 95
    - 96
    - 97
    - 98
    - 99
    - 100
    - 101
    - 102
    - 103
    - 104
    - 105
    - 107
    - 108
    - 110
    - 111
    - 112
    - 113
    - 114
    - 115
class: standard
---

> **SLT-IMP-05** · group `implied` · scheduled **D09** (2026-09-01)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
End-of-window regression sweep: read every log surface for D0-D9, enumerate failed Action Scheduler actions, walk the main admin and portal screens with the console open, and reconcile each SLT2 subscription's cycle count against its paid orders. Runs before SLT-SETUP-99A.

## Scope
- Gateway: both
- Checkout: N/A
- Account: admin + a read-only pass as slt2-core
- Plugins: both

## Preconditions
- Every task whose final execution leg is scheduled by this D9 sweep is done. Two standing-watch tasks intentionally remain open: `SLT-SYN-09` for its D10 follow-up and `SLT-EML-14` for its D12 silence delta; their evidence through D9 must already be current. SLT-SETUP-99A/99B have NOT run.
- Read-only: no setting changes, drains or cancellations. For each defect, create/update one complete card in the shared `qa/issues/` kanban board and keep this lifecycle card blocked until the affected assertion is rerun successfully.
- `slt2-catalog-registry` lists every SLT2 product, account and subscription id, including those from SLT-IMP-01/03/04.

## Test data
| Item | Value |
|---|---|
| PHP log | `wp-content/debug.log` |
| WC logs | `wp-content/uploads/wc-logs/`; `page=wc-status&tab=logs` |
| Sources | `arraysubs-gateway`, `wc_logger`, `woocommerce-gateway-stripe` |
| Failed | `tools.php?page=action-scheduler&status=failed` |
| Pro audits | `#/audits/` + `gateway-logs`, `renewal-failures`, `portal-action-failures`, `scheduled-job-logs` |
| Window | 2026-08-23 .. 2026-09-01 site |

## Steps
1. `M0=$(mailpit-agent latest-id)` (this task must not send mail).
2. From the WordPress root, run `rg -i "Aug-2026" wp-content/debug.log | rg -i "fatal|uncaught|deprecat|notice|warning" > /home/server-manager/slt-evidence/SLT-IMP-05-php-log.txt`; bucket by message, count, and attribute runtime log entries to `arraysubs/`, `arraysubspro/`, or other by the path printed in the log. This reads the runtime log only; do not open either product source tree.
3. `agent-browser --session admin-SLT-IMP-05 open ".../admin.php?page=wc-status&tab=logs"`; open every log dated 2026-08-23..09-01 for the three sources, copy each `error`/`critical`/`warning`.
4. Open `tools.php?page=action-scheduler&status=failed`, **Per page** 100, screenshot each page, record hook / args / time / message per row, cross-check args against the SLT2 id list; NON-SLT2 failures are out of scope but logged as pre-existing.
5. Screenshot `status=pending` for the next 72 h so SLT-SETUP-99A knows what it will cancel. Open the four Audits screens, screenshot each, record all SLT2 rows.
6. Console sweep as admin on seven screens: `admin.php?page=arraysubs-mainadmin#/subscriptions`, `admin.php?page=arraysubs-mainadmin#/reports`, `admin.php?page=arraysubs-mainadmin#/audits/gateway-logs`, `admin.php?page=arraysubs-mainadmin#/settings/general`, `admin.php?page=wc-orders`, `edit.php?post_type=product`, `tools.php?page=action-scheduler`. Repeat as `slt2-core` (`--session customer-sweep-SLT-IMP-05`) on `/my-account/`, `/subscriptions/`, `/view-subscription/<id>/`, `/orders/`, `/payment-methods/`. Record console errors and 4xx/5xx.
7. RECONCILE per SLT2 subscription: `wp post meta get <ID> _completed_payments --allow-root`, then `wp db query "SELECT id,status,total_amount FROM wp_wc_orders o JOIN wp_wc_orders_meta m ON m.order_id=o.id WHERE m.meta_key='_subscription_id' AND m.meta_value='<ID>' AND o.status IN ('wc-processing','wc-completed')" --allow-root`, plus the parent.
8. Write `sub id | product | status | cycles | paid orders >0 | delta | explanation`; flag every non-zero delta. Confirm `watch-reports/` holds a report or stub for each day D1-D9. Inspect the complete M0 delta, close `admin-SLT-IMP-05` and `customer-sweep-SLT-IMP-05`, independently review every log/failed-action/screen/reconciliation bucket, then move through `review` to `done` with Review empty. Every confirmed defect gets only its own `qa/issues/` kanban card named `SLT-IMP-05-<concise-slug>` with task/stage/plan path; affected product/user/subscription/order/action/message IDs; user login/email/role; exact log source/file/time range/routes/sessions; reproduction where applicable; expected/actual; and redacted log/UI/queue/console/network/reconciliation proof. Do not inspect product source.

## Expected results
1. Zero **Fatal error** / **Uncaught** entries in the window from `arraysubs/` or `arraysubspro/`; every deprecation/notice bucket is listed with a count and `file:line` and filed.
2. `status=failed` holds zero rows referencing an SLT2 subscription; non-SLT2 rows are logged as pre-existing.
3. Zero console errors and zero 4xx/5xx on the twelve screens.
4. Reconciliation delta is **0** for every SLT2 subscription, with two carve-outs to name explicitly, not assume: (a) `_completed_payments` increments only when order total > 0 (SLT-REF-01), so `SLT2 Free Signup Daily`, `SLT2 Trial Four Day` and the $0.00 `SLT2 Variable Daily` variation exceed their cycle count by their $0.00 orders; (b) `pending`/`failed` orders count on neither side.
5. `SLT2 Lifetime One Time`: `_completed_payments = 1`, one order, no scheduled renewal. Dunning-cancelled subs show ONE renewal order per cycle, not one per retry (SLT-REF-03).
6. Every watch day D1-D9 has a report file. Inspect the complete Mailpit delta after `M0`; zero message may be attributable to this read-only task, while independently scheduled/background messages are classified against their actual owner and do not fail this task.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| — | **NONE EXPECTED** | read-only | — | — | complete delta after `M0`; zero task-attributable message, classify unrelated mail |

## Evidence to capture
- `SLT-IMP-05-php-log.txt`, `-wc-logs/*.txt`, `-01-failed.png`, `-02-pending.png`, `-03..06-audits.png`, `-07-console-admin.txt`, `-08-console-portal.txt`, `-09-reconciliation.md`; SLT2 id list with product, status, cycles, orders.

## Pass criteria
- [ ] no plugin fatal/uncaught; every notice bucket counted and filed
- [ ] zero SLT-attributable failed Action Scheduler rows
- [ ] zero console errors and 4xx/5xx on all twelve screens
- [ ] reconciliation delta 0, or a named carve-out
- [ ] SLT2 Lifetime One Time never renewed; no one-order-per-retry
- [ ] D1-D9 watch reports present; complete M0 delta contains no task-attributable mail
- [ ] Exact sessions closed; each confirmed finding dedicated; independent review reaches `done` with Review empty

## Isolation / teardown
- Strictly read-only; nothing to restore.
- Hands SLT-SETUP-99A the pending-action screenshot and reconciliation table; 99A must not cancel anything flagged here until its issue exists.
- Close only the exact admin/customer sweep sessions named above.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
