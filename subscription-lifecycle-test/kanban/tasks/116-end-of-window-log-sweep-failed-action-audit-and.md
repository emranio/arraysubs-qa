---
id: 116
title: End-of-window log sweep, failed-action audit and cycle-to-order reconciliation
status: todo
priority: high
created: 2026-08-02T03:43:12.60952465+02:00
updated: 2026-08-02T03:43:24.173496322+02:00
tags:
    - edge-cases
    - day-09
    - has-conflicts
due: "2026-08-11"
estimate: 4h
depends_on:
    - 35
    - 83
    - 69
    - 92
class: standard
---

> **SLT-IMP-05** · group `implied` · scheduled **D09** (2026-08-11)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
End-of-window regression sweep: read every log surface for D0-D9, enumerate failed Action Scheduler actions, walk the main admin and portal screens with the console open, and reconcile each SLT subscription's cycle count against its paid orders. Runs before SLT-SETUP-99A.

## Scope
- Gateway: both
- Checkout: N/A
- Account: admin + a read-only pass as slt-core
- Plugins: both

## Preconditions
- Every SLT task D0-D8 done or closed; SLT-SETUP-99A/99B have NOT run.
- Read-only: no setting changes, drains or cancellations. Found a defect? File it, don't fix.
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
1. `mailpit-agent latest-id` -> `M0` (this task must not send mail).
2. `grep -Ei "Aug-2026" wp-content/debug.log | grep -Ei "fatal|uncaught|deprecat|notice|warning" > SLT-IMP-05-php-log.txt`; bucket by message, count, attribute to `arraysubs/`, `arraysubspro/` or other by path.
3. `agent-browser --session admin open ".../admin.php?page=wc-status&tab=logs"`; open every log dated 2026-08-02..08-11 for the three sources, copy each `error`/`critical`/`warning`.
4. Open `tools.php?page=action-scheduler&status=failed`, **Per page** 100, screenshot each page, record hook / args / time / message per row, cross-check args against the SLT id list; NON-SLT failures are out of scope but logged as pre-existing.
5. Screenshot `status=pending` for the next 72 h so SLT-SETUP-99A knows what it will cancel. Open the four Audits screens, screenshot each, record all SLT rows.
6. Console sweep as admin on seven screens: `page=arraysubs#/subscriptions`, `#/reports`, `#/audits/gateway-logs`, `#/settings/general`, `page=wc-orders`, `edit.php?post_type=product`, `page=action-scheduler`. Repeat as `slt-core` (`--session customer-sweep`) on `/my-account/`, `/subscriptions/`, `/view-subscription/<id>/`, `/orders/`, `/payment-methods/`. Record console errors and 4xx/5xx.
7. RECONCILE per SLT subscription: `wp post meta get <ID> _completed_payments`, then `wp db query "SELECT id,status,total_amount FROM wp_wc_orders o JOIN wp_wc_orders_meta m ON m.order_id=o.id WHERE m.meta_key='_subscription_id' AND m.meta_value='<ID>' AND o.status IN ('wc-processing','wc-completed')"`, plus the parent.
8. Write `sub id | product | status | cycles | paid orders >0 | delta | explanation`; flag every non-zero delta. Confirm `watch-reports/` holds a report or stub for each day D1-D9.

## Expected results
1. Zero **Fatal error** / **Uncaught** entries in the window from `arraysubs/` or `arraysubspro/`; every deprecation/notice bucket is listed with a count and `file:line` and filed.
2. `status=failed` holds zero rows referencing an SLT subscription; non-SLT rows are logged as pre-existing.
3. Zero console errors and zero 4xx/5xx on the twelve screens.
4. Reconciliation delta is **0** for every SLT subscription, with two carve-outs to name explicitly, not assume: (a) `_completed_payments` increments only when order total > 0 (SLT-REF-01), so `SLT Free Signup Daily`, `SLT Trial Four Day` and the $0.00 `SLT Variable Daily` variation exceed their cycle count by their $0.00 orders; (b) `pending`/`failed` orders count on neither side.
5. `SLT Lifetime One Time`: `_completed_payments = 1`, one order, no scheduled renewal. Dunning-cancelled subs show ONE renewal order per cycle, not one per retry (SLT-REF-03).
6. Every watch day D1-D9 has a report file; Mailpit `latest-id` is still `M0`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| — | **NONE EXPECTED** | read-only | — | — | `mailpit-agent latest-id` at the end == `M0` |

## Evidence to capture
- `SLT-IMP-05-php-log.txt`, `-wc-logs/*.txt`, `-01-failed.png`, `-02-pending.png`, `-03..06-audits.png`, `-07-console-admin.txt`, `-08-console-portal.txt`, `-09-reconciliation.md`; SLT id list with product, status, cycles, orders.

## Pass criteria
- [ ] no plugin fatal/uncaught; every notice bucket counted and filed
- [ ] zero SLT-attributable failed Action Scheduler rows
- [ ] zero console errors and 4xx/5xx on all twelve screens
- [ ] reconciliation delta 0, or a named carve-out
- [ ] SLT Lifetime One Time never renewed; no one-order-per-retry
- [ ] D1-D9 watch reports present; Mailpit latest-id unchanged

## Isolation / teardown
- Strictly read-only; nothing to restore.
- Hands SLT-SETUP-99A the pending-action screenshot and reconciliation table; 99A must not cancel anything flagged here until its issue exists.

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
