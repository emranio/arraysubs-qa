---
id: 119
title: SLT-SETUP-99B Post-watch teardown on 2026-08-15 — delete every SLT artifact
status: todo
priority: high
created: 2026-08-02T03:43:12.818482128+02:00
updated: 2026-08-02T03:43:24.618344606+02:00
tags:
    - setup
    - day-10
due: "2026-08-12"
estimate: 1h30m
depends_on:
    - 118
class: standard
---

> **SLT-SETUP-99B** · group `foundation` · scheduled **D10** (2026-08-12)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Remove every artifact this plan created, returning the shared staging site to its pre-plan state. Runs **2026-08-15**, strictly after the D12 watch report and `SLT-EML-14`'s final delta sweep are written.

> Scheduled as day 10 on the board only because the board's day field stops at 10. **The real execution date is 2026-08-15.** Running this on 2026-08-13 or 2026-08-14 destroys the last renewals of the window and invalidates the watch.

## Scope
- Gateway: N/A
- Checkout: N/A
- Account: admin only
- Plugins: both

## Preconditions
- `SLT-SETUP-99A` closed: settings already restored, evidence already consolidated.
- The D12 watch report (`watch-reports/D12-2026-08-14.md`) exists and is signed off.
- `SLT-EML-14`'s 2026-08-14 delta sweep is complete.
- **Every SLT task on the board is `done`, `blocked`, or explicitly waived.** If any task is still `todo` or `in-progress`, STOP — deleting its fixtures makes it unrunnable.

## Test data
| Item | Value |
|---|---|
| Delete | all `SLT `-titled products, `SLT*` coupons, `slt-*` users, their orders and subscriptions, the SLT pages (classic checkout, evidence, registry) |
| Preserve | **everything else on the site**, without exception |

## Steps
1. Cancel the tail cohort that `SLT-SETUP-99A` deliberately kept alive: both week-flex subscriptions, Sync Global Daily, the two `SLT-SYN-13` variation subscriptions, the two `SLT-SYN-12` probes, `SLT-SYN-14`'s quantity subscription, Box Daily, both lifetime controls, the flex month subscriptions, and `SLT-SYN-11`'s probes. Work from the registry list, not from memory.
2. Take a full pre-deletion inventory and save it to `evidence/teardown/inventory-before.txt`: counts of products, coupons, users, orders, subscriptions — split SLT vs non-SLT.
3. Unschedule every pending Action Scheduler action belonging to an SLT subscription, so nothing fires against deleted data.
4. Delete in dependency order: subscriptions → orders → products → coupons → pages → users. Reassign nothing; these users own no non-SLT content.
5. Take the post-deletion inventory. **Diff it against the plan-start baseline in `README.md`:** 64 products, 354 subscriptions, 437 orders, 8 coupons.
6. Confirm zero orphans: no `arraysubs_data` post referencing a deleted product or user, no scheduled action referencing a deleted subscription, no `wp_wc_orders` row referencing a deleted customer.
7. Verify the non-SLT data is byte-identical in count to plan start, and that the 13 pre-existing active subscriptions are still active and still scheduled.

## Expected results
1. Zero `SLT `-titled products, `SLT*` coupons, `slt-*` users, or SLT subscriptions/orders remain.
2. Non-SLT counts match the plan-start baseline exactly.
3. No orphaned scheduled actions, subscriptions, or orders.
4. The 13 pre-existing active subscriptions are untouched and still have future renewal legs queued.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `subscription_cancelled` × tail cohort | step 1 cancellations | each customer | `cancelled` | `list 60` |
| 2 | NONE EXPECTED | steps 3-7 | — | — | deletion must send no mail; `latest-id` must not move across steps 3-7 |

## Evidence to capture
- `inventory-before.txt`, `inventory-after.txt`, the baseline diff, the orphan checks, the Mailpit IDs from step 1.

## Pass criteria
- [ ] Every SLT artifact deleted
- [ ] Non-SLT counts match plan start exactly (64 / 354 / 437 / 8)
- [ ] Zero orphaned actions, subscriptions, or orders
- [ ] Pre-existing active subscriptions untouched and still scheduled
- [ ] No mail sent by the deletion steps themselves

## Isolation / teardown
- Terminal task. After it, the plan directory (board, reports, issues, evidence) is the only trace the run leaves — and that is intentional and kept.

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
