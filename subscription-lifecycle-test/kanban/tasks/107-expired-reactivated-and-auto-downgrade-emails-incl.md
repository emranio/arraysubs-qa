---
id: 107
title: Expired, reactivated and auto-downgrade emails, incl. the expiry-suppression negative
status: done
priority: high
created: 2026-08-02T03:43:11.922944538+02:00
updated: 2026-08-10T02:49:36.95411422+02:00
started: 2026-08-10T02:49:36.8411465+02:00
completed: 2026-08-10T02:49:36.8411465+02:00
tags:
    - email
    - day-08
due: "2026-08-10"
estimate: 1h 30m
depends_on:
    - 54
    - 6
    - 60
    - 4
    - 111
class: standard
---

> **SLT-EML-08** · group `emails` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove three end-of-life emails on real transitions: `subscription_expired` from the natural 2026-08-06 expiry of SLT Fixed Three Cycles, `subscription_reactivated` from a customer reactivation of `S_EML`, and `auto_downgrade` from `SLT-SW-02`'s `on_expire` downgrade of `SUB_BASIC` from SLT Plan Pro to Basic — plus the verified negative that the expired mail is **suppressed** when a downgrade target exists (`EmailManager.php:317-322`).

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-eml`, `slt-switch`)
- Plugins: both; auto-downgrade observation is pro-required

## Preconditions
- SLT-EML-07 left `S_EML` cancelled; `S4` (SLT Fixed Three Cycles, slt-core) expired 2026-08-06; SLT-PROD-11 set `_arraysubs_auto_downgrade_product` = Basic on SLT Plan Pro; `SLT-SW-02` has just expired and auto-downgraded `SUB_BASIC` from Pro back to Basic.
- Frozen baseline: `allow_reactivation=true`; `plan_switching.auto_downgrade_timing=on_expire`.
- The strict D8 order has reached this task only after `SLT-TT-00` → `SLT-SYN-10` → `SLT-SW-02`. Quote their shared non-SLT schedule baseline and empty post-target diff. `SLT-SW-02` owns the hand-set `_end_date` and targeted expiry hook; this task is observation-only for that leg and must not queue or fire it again.

## Test data
| Item | Value |
|---|---|
| Subscriptions | `S4` expired, `S_EML` cancelled, `SUB_BASIC` (Pro $15.00 → Basic $5.00 under SLT-SW-02) |
| Gates | `emails.subscription_expired.enabled`, `emails.auto_downgrade.enabled`; `subscription_reactivated` has **no** ArraySubs key — only the WooCommerce → Emails → `[ArraySubs] Subscription Reactivated` checkbox (record it) |

## Steps
1. Load `LIFE04_REN2_PRE` and the exact `has expired` Mailpit id published by SLT-LIFE-04. `show` that exact id and inspect the complete bounded owner delta; confirm recipient, `S4` status `arraysubs-expired`, `_end_date` is the 2026-08-06 final-charge moment, and no admin expiry variant. Do not search a fixed-size recent list.
2. `R1=$(mailpit-agent latest-id)` immediately before the mutation. In customer session `cust-SLT-EML-08`, open the exact numeric `S_EML` portal detail, capture the cancelled state, click **Reactivate**, and poll immutable R1 in calls no longer than 60 seconds through the two-minute cutoff for `has been reactivated`; save the matched id and text.
3. Confirm `S_EML` is `arraysubs-active` with a recomputed `_next_payment_date`; inspect the complete delta after R1 and require no `is active` / `New subscription` mail for exact `S_EML`. Classify unrelated shared-site mail.
4. `wp post meta get <Pro product ID> _arraysubs_auto_downgrade_product --allow-root` = Basic ID. Quote `SLT-TT-00`'s shared pre-flight queue/schedule snapshot and `SLT-SW-02`'s exact targeted `do_action('arraysubs_expire_subscription', SUB_BASIC)` command timestamp/output; that call does not create an Action Scheduler ID. Do not queue or run another expiry action.
5. Load SLT-SW-02's `M1` baseline and exact matched auto-downgrade Mailpit id; show that exact `has been changed to SLT Plan Basic` message for `SUB_BASIC`.
6. Inspect the complete SLT-SW-02 delta after M1 and confirm there is no `has expired` message for exact `SUB_BASIC`; classify unrelated shared-site mail.
7. Verify numeric `SUB_BASIC` now bills SLT Plan Basic $5.00 (product meta, next payment, note) and quote the shared post-drain schedule diff — no non-SLT `_next_payment_date` moved. Publish the exact active `S_EML` handoff consumed by SLT-EML-10, close `cust-SLT-EML-08`, independently review all three transition owners, then move the card through `review` to `done` with Review empty. Any live defect goes only in `issues/SLT-EML-08-<concise-slug>.md` with task/stage/plan path; subscription/product/action/message IDs; user IDs/logins/emails/roles; exact routes/session/hook timestamp; reproduction; expected/actual; and UI/meta/queue/log/Mailpit/shared-diff proof.

## Expected results
1. One `[mirror-help.arrayhash.com] Your subscription #<S4> has expired` to `slt-core@example.test` at the 2026-08-06 expiry; no admin copy exists.
2. `[mirror-help.arrayhash.com] Your subscription for SLT Daily Core has been reactivated` to `slt-eml@example.test` once; `S_EML` is `arraysubs-active`; no `new_subscription` (`EmailManager.php:345-349`).
3. `[mirror-help.arrayhash.com] Your subscription #<SUB_BASIC> has been changed to SLT Plan Basic` arrives once, gated by `emails.auto_downgrade.enabled=true`.
4. **No** `subscription_expired` for `SUB_BASIC` — suppression is by design; if one arrives, write a standalone issue file under `issues/` citing `EmailManager.php:317-322`.
5. `SUB_BASIC` renews at $5.00 on SLT Plan Basic; no non-SLT schedule moved and no non-SLT action ran.

## Emails expected
| # | Email | Trigger | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | subscription_expired | 2026-08-06 expiry | slt-core | `#<S4> has expired` | exact SLT-LIFE-04 handoff id + bounded owner delta |
| 2 | subscription_reactivated | step 2 | slt-eml | `has been reactivated` | immutable-baseline polls ≤60 seconds through the two-minute cutoff |
| 3 | auto_downgrade | SLT-SW-02 targeted expiry | slt-switch | `has been changed to SLT Plan Basic` | exact SLT-SW-02 handoff id + complete M1 delta |
| 4 | NONE EXPECTED — expired mail for `SUB_BASIC`; `new_subscription` on reactivation | steps 2, 5 | — | `has expired` / `is active` | absent from the respective complete owner deltas |

## Evidence to capture
- `SLT-EML-08-01-expired.png`, `-02-reactivate.png`, `-03-pending-queue.png`, `-04-downgrade.png`; `S4`/`S_EML`/`SUB_BASIC` ids, Mailpit ids, quoted SLT-SW-02 targeted-hook timestamp/output, and both shared schedule snapshots.

## Pass criteria
- [ ] Expired mail once for `S4`, exact subject, no admin copy
- [ ] Reactivated mail once, `S_EML` active, no `new_subscription`; ungated status noted
- [ ] Auto-downgrade mail once, exact subject, Basic named
- [ ] No expired mail for `SUB_BASIC` (suppression proven)
- [ ] Only the targeted `SUB_BASIC` expiry hook ran; no duplicate expiry action and no non-SLT schedule moved
- [ ] Exact observation session closes and independent review reaches `done` with Review empty

## Isolation / teardown
- Leaves `S_EML` **active** on purpose — SLT-EML-10 uses it the same day and cancels it. `SUB_BASIC` stays on SLT Plan Basic; record the switch in the registry and hand it to SLT-SETUP-99A.
- Restores: no global setting or subscription date written by this observation task; sessions closed.

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

[[2026-08-06]] Thu 20:42
Source-block note on 2026-08-06: this observation task depends on card 111 / SLT-SW-02 having actually executed the targeted downgrade/expiry chain. Card 111 is currently source-blocked behind the missing ladder-switch fixtures, so this card cannot start until that upstream chain exists on a later valid execution.

[[2026-08-10]] Mon 06:48
D08 execution closes `UNVERIFIED` under the authored source-block note: card 111 / SLT-SW-02 completed its execution record but could not produce a numeric `SUB_BASIC`, targeted expiry hook, M1 cursor, auto-downgrade message, Basic handoff, or expiry-suppression owner delta. This task therefore did not reactivate `S_EML=12263`; it remains `arraysubs-cancelled`, and no R1 baseline, portal mutation, date/action/order/setting/product/mail mutation, or browser session was used. The independent historical S4 branch was read by exact owner ID: subscription 12017 remains `arraysubs-expired`, payments 3, `_end_date=2026-08-06 17:01:16Z`, no next payment; final order 12929 completed for `$7.00`; exact Mailpit ID `7LFSsjbiAzEYI2AHiljER5` is the customer-only-form subject `Your subscription #12017 has expired` to `slt-core@example.test`. However, `LIFE04_REN2_PRE` was never published—only its intended window exists—so the strict bounded exact-once/no-admin-copy claims remain `UNVERIFIED`, not inferred from a fixed recent list. Full closeout: `/home/server-manager/slt-evidence/SLT-EML-08-D08-source-block.txt`. No product issue was filed.
