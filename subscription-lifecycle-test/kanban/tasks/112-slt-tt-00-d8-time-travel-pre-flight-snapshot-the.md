---
id: 112
title: 'SLT-TT-00 D8 time-travel pre-flight: snapshot the queue and every non-SLT next-payment date'
status: todo
priority: critical
created: 2026-08-02T03:43:12.31221155+02:00
updated: 2026-08-02T03:43:12.31221155+02:00
tags:
    - renewal
    - day-08
due: "2026-08-10"
estimate: 45m
class: standard
---

> **SLT-TT-00** · group `renewal` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
D8 is the only day on which this plan manipulates date meta. Six tasks do so. The audit found that a single mistimed or unfiltered Action Scheduler drain on that day would fire other tests' pending renewals **and** pre-existing non-SLT subscriptions, destroying evidence irrecoverably. This task runs first, establishes the shared safety baseline every other D8 task quotes, and is the only place the non-SLT diff is defined.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: admin only
- Plugins: both

## Preconditions
- Runs **first** on D8, before `SLT-SYN-10`, `SLT-SW-02`, `SLT-EML-08`, `SLT-EML-10`, `SLT-LIFE-01`, `SLT-EML-14`.
- No other task may touch date meta until this one publishes its snapshot.

## Test data
| Item | Value |
|---|---|
| Scope | every `arraysubs_data` post, SLT and non-SLT |
| Output | `evidence/D8/SLT-TT-00-preflight.txt` and the `slt-catalog-registry` page |

## Steps
1. Screenshot the full **Tools → Scheduled Actions → Pending** queue filtered to `arraysubs`. Save as `SLT-TT-00-01-pending-before.png`.
2. Dump every subscription's schedule state to the evidence file:
   `wp db query "SELECT p.ID, p.post_status, MAX(CASE WHEN pm.meta_key='_next_payment_date' THEN pm.meta_value END) AS next_payment FROM wp_posts p LEFT JOIN wp_postmeta pm ON pm.post_id=p.ID WHERE p.post_type='arraysubs_data' GROUP BY p.ID, p.post_status ORDER BY p.ID;" --allow-root`
3. Split the dump into two lists: **SLT** (post_date >= 2026-08-02) and **non-SLT** (everything older). Record the non-SLT count and each of their `_next_payment_date` values verbatim. There were 13 active non-SLT subscriptions at plan start — confirm the current number.
4. Publish both lists to `slt-catalog-registry`. Every other D8 task must quote the non-SLT list in its own evidence.
5. Record the exact allowed drain form for the day, and post it to the registry as the only permitted command shape:
   `wp action-scheduler run --hooks=<single-hook> --allow-root` is **BANNED** on D8.
   Only single-action-by-ID execution from the Scheduled Actions UI is permitted, one action at a time, each screenshotted before and after.
6. State the STOP condition in the registry: if at any point a non-SLT `_next_payment_date` differs from this snapshot, **all D8 time-travel halts immediately**, an issue is filed at critical severity, and the remaining D8 tasks move to `blocked`.

## Expected results
1. A complete, timestamped pre-flight snapshot exists in the evidence directory and on the registry page.
2. The non-SLT subscription list is captured with every `_next_payment_date` verbatim.
3. The permitted-command rule and the STOP condition are published before any D8 task runs.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | this task is read-only | — | — | `mailpit-agent latest-id` before and after must be identical |

## Evidence to capture
- `SLT-TT-00-01-pending-before.png`, the SQL dump, both split lists, the registry revision.

## Pass criteria
- [ ] Pending queue screenshotted before any D8 mutation
- [ ] Every subscription's `_next_payment_date` captured, split SLT vs non-SLT
- [ ] Permitted-command rule and STOP condition published to the registry
- [ ] Zero mail sent

## Isolation / teardown
- Read-only. Its output is the safety contract for the whole of D8. The matching post-drain diff is run by the day's last task (`SLT-EML-14`) and must show **no** non-SLT date moved.

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
