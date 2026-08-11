---
id: 116
title: End-of-window log sweep, failed-action audit and cycle-to-order reconciliation
status: done
priority: high
created: 2026-08-02T03:43:12.60952465+02:00
updated: 2026-08-11T03:43:07.416241945+02:00
started: 2026-08-11T03:43:07.416240652+02:00
completed: 2026-08-11T03:43:07.416240652+02:00
tags:
    - edge-cases
    - day-09
due: "2026-08-11"
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
claimed_by: steam-tide
claimed_at: 2026-08-11T03:43:07.416241844+02:00
class: standard
---

> **SLT-IMP-05** · group `implied` · scheduled **D09** (2026-08-11)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
End-of-window regression sweep: read every log surface for D0-D9, enumerate failed Action Scheduler actions, walk the main admin and portal screens with the console open, and reconcile each SLT subscription's cycle count against its paid orders. Runs before SLT-SETUP-99A.

## Scope
- Gateway: both
- Checkout: N/A
- Account: admin + a read-only pass as slt-core
- Plugins: both

## Preconditions
- Every task whose final execution leg is scheduled by this D9 sweep is done. Two standing-watch tasks intentionally remain open: `SLT-SYN-09` for its D10 follow-up and `SLT-EML-14` for its D12 silence delta; their evidence through D9 must already be current. SLT-SETUP-99A/99B have NOT run.
- Read-only: no setting changes, drains or cancellations. For each defect, create one standalone markdown report under this suite's `issues/` directory; do not create a bug/remediation card on the lifecycle kanban.
- `slt-catalog-registry` lists every SLT product, account and subscription id, including those from SLT-IMP-01/03/04.

## Test data
| Item | Value |
|---|---|
| PHP log | `wp-content/debug.log` |
| WC logs | `wp-content/uploads/wc-logs/`; `page=wc-status&tab=logs` |
| Sources | `arraysubs-gateway`, `wc_logger`, `woocommerce-gateway-stripe` |
| Failed | `tools.php?page=action-scheduler&status=failed` |
| Pro audits | `#/audits/` + `gateway-logs`, `renewal-failures`, `portal-action-failures`, `scheduled-job-logs` |
| Window | 2026-08-02 .. 2026-08-11 site |

## Steps
1. `M0=$(mailpit-agent latest-id)` (this task must not send mail).
2. From the WordPress root, run `rg -i "Aug-2026" wp-content/debug.log | rg -i "fatal|uncaught|deprecat|notice|warning" > /home/server-manager/slt-evidence/SLT-IMP-05-php-log.txt`; bucket by message, count, and attribute runtime log entries to `arraysubs/`, `arraysubspro/`, or other by the path printed in the log. This reads the runtime log only; do not open either product source tree.
3. `agent-browser --session admin-SLT-IMP-05 open ".../admin.php?page=wc-status&tab=logs"`; open every log dated 2026-08-02..08-11 for the three sources, copy each `error`/`critical`/`warning`.
4. Open `tools.php?page=action-scheduler&status=failed`, **Per page** 100, screenshot each page, record hook / args / time / message per row, cross-check args against the SLT id list; NON-SLT failures are out of scope but logged as pre-existing.
5. Screenshot `status=pending` for the next 72 h so SLT-SETUP-99A knows what it will cancel. Open the four Audits screens, screenshot each, record all SLT rows.
6. Console sweep as admin on seven screens: `admin.php?page=arraysubs-mainadmin#/subscriptions`, `admin.php?page=arraysubs-mainadmin#/reports`, `admin.php?page=arraysubs-mainadmin#/audits/gateway-logs`, `admin.php?page=arraysubs-mainadmin#/settings/general`, `admin.php?page=wc-orders`, `edit.php?post_type=product`, `tools.php?page=action-scheduler`. Repeat as `slt-core` (`--session customer-sweep-SLT-IMP-05`) on `/my-account/`, `/subscriptions/`, `/view-subscription/<id>/`, `/orders/`, `/payment-methods/`. Record console errors and 4xx/5xx.
7. RECONCILE per SLT subscription: `wp post meta get <ID> _completed_payments --allow-root`, then `wp db query "SELECT id,status,total_amount FROM wp_wc_orders o JOIN wp_wc_orders_meta m ON m.order_id=o.id WHERE m.meta_key='_subscription_id' AND m.meta_value='<ID>' AND o.status IN ('wc-processing','wc-completed')" --allow-root`, plus the parent.
8. Write `sub id | product | status | cycles | paid orders >0 | delta | explanation`; flag every non-zero delta. Confirm `watch-reports/` holds a report or stub for each day D1-D9. Inspect the complete M0 delta, close `admin-SLT-IMP-05` and `customer-sweep-SLT-IMP-05`, independently review every log/failed-action/screen/reconciliation bucket, then move through `review` to `done` with Review empty. Every confirmed defect gets only its own `issues/SLT-IMP-05-<concise-slug>.md` with task/stage/plan path; affected product/user/subscription/order/action/message IDs; user login/email/role; exact log source/file/time range/routes/sessions; reproduction where applicable; expected/actual; and redacted log/UI/queue/console/network/reconciliation proof. Do not inspect product source.

## Expected results
1. Zero **Fatal error** / **Uncaught** entries in the window from `arraysubs/` or `arraysubspro/`; every deprecation/notice bucket is listed with a count and `file:line` and filed.
2. `status=failed` holds zero rows referencing an SLT subscription; non-SLT rows are logged as pre-existing.
3. Zero console errors and zero 4xx/5xx on the twelve screens.
4. Reconciliation delta is **0** for every SLT subscription, with two carve-outs to name explicitly, not assume: (a) `_completed_payments` increments only when order total > 0 (SLT-REF-01), so `SLT Free Signup Daily`, `SLT Trial Four Day` and the $0.00 `SLT Variable Daily` variation exceed their cycle count by their $0.00 orders; (b) `pending`/`failed` orders count on neither side.
5. `SLT Lifetime One Time`: `_completed_payments = 1`, one order, no scheduled renewal. Dunning-cancelled subs show ONE renewal order per cycle, not one per retry (SLT-REF-03).
6. Every watch day D1-D9 has a report file. Inspect the complete Mailpit delta after `M0`; zero message may be attributable to this read-only task, while independently scheduled/background messages are classified against their actual owner and do not fail this task.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| — | **NONE EXPECTED** | read-only | — | — | complete delta after `M0`; zero task-attributable message, classify unrelated mail |

## Evidence to capture
- `SLT-IMP-05-php-log.txt`, `-wc-logs/*.txt`, `-01-failed.png`, `-02-pending.png`, `-03..06-audits.png`, `-07-console-admin.txt`, `-08-console-portal.txt`, `-09-reconciliation.md`; SLT id list with product, status, cycles, orders.

## Pass criteria
- [ ] no plugin fatal/uncaught; every notice bucket counted and filed
- [ ] zero SLT-attributable failed Action Scheduler rows
- [ ] zero console errors and 4xx/5xx on all twelve screens
- [ ] reconciliation delta 0, or a named carve-out
- [ ] SLT Lifetime One Time never renewed; no one-order-per-retry
- [ ] D1-D9 watch reports present; complete M0 delta contains no task-attributable mail
- [ ] Exact sessions closed; each confirmed finding standalone; independent review reaches `done` with Review empty

## Isolation / teardown
- Strictly read-only; nothing to restore.
- Hands SLT-SETUP-99A the pending-action screenshot and reconciliation table; 99A must not cancel anything flagged here until its issue exists.
- Close only the exact admin/customer sweep sessions named above.

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

## D09 execution — 2026-08-11 early-morning — FAIL

- Completed the full read-only D0-D9 log, queue, audit-screen, browser-route, report-presence,
  Mailpit, and 25-subscription reconciliation sweep. Full evidence and self-review:
  `/home/server-manager/slt-evidence/SLT-IMP-05-D09-execution.md`.
- PASS: zero product-path fatal/uncaught entries; zero SLT-attributable failed scheduler rows;
  all 25 live subscriptions reconcile at delta zero (`103` completed payments / `103`
  historically paid positive relationship orders); both lifetime controls never renewed;
  D01-D09 reports exist; Mailpit cursor stayed `4cogsKDuQGssjfWbN3yhKp`; zero 4xx/5xx on
  all twelve routes; exact browser sessions closed.
- FAIL: routine Paddle intermediary events are emitted as misleading unhandled warnings, and
  all five customer My Account routes emit `AbortError: Transition was skipped`. Standalone
  findings:
  - `issues/light-plugin-SLT-IMP-05-paddle-routine-webhooks-logged-as-unhandled-warnings.md`
  - `issues/light-plugin-SLT-IMP-05-my-account-routes-emit-view-transition-aborterror.md`
- UNVERIFIED only for the authored same-order-across-retries assertion because canonical
  `S_FAIL` never existed; no substitute was inferred. The live set contains zero failed/on-hold
  relationship orders and no one-order-per-retry proliferation.
- No setting or data mutation occurred and nothing requires restoration. The pending 72-hour
  teardown handoff is `/home/server-manager/slt-evidence/SLT-IMP-05-pending-72h.txt`.
