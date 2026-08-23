---
id: 107
title: Expired, reactivated and auto-downgrade emails, incl. the expiry-suppression negative
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - email
    - day-08
due: "2026-08-31"
estimate: 1h 30m
depends_on:
    - 54
    - 6
    - 60
    - 4
    - 111
class: standard
---

> **SLT-EML-08** · group `emails` · scheduled **D08** (2026-08-31)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove three end-of-life emails on real transitions: `subscription_expired` from the natural 2026-08-27 expiry of SLT2 Fixed Three Cycles, `subscription_reactivated` from a customer reactivation of `S_EML`, and `auto_downgrade` from `SLT-SW-02`'s `on_expire` downgrade of `SUB_BASIC` from SLT2 Plan Pro to Basic — plus the verified negative that the expired mail is **suppressed** when a downgrade target exists (`EmailManager.php:317-322`).

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt2-eml`, `slt2-switch`)
- Plugins: both; auto-downgrade observation is pro-required

## Preconditions
- SLT-EML-07 left `S_EML` cancelled; `S4` (SLT2 Fixed Three Cycles, slt2-core) expired 2026-08-27; SLT-PROD-11 set `_arraysubs_auto_downgrade_product` = Basic on SLT2 Plan Pro; `SLT-SW-02` has just expired and auto-downgraded `SUB_BASIC` from Pro back to Basic.
- Frozen baseline: the retired `customer_actions.allow_reactivation` key is absent. The `S_EML` Reactivate action must be granted by the current cancelled-status/capability contract; `pause_subscription.customer_can_resume` governs paused resume only. `plan_switching.auto_downgrade_timing=on_expire`.
- The strict D8 order has reached this task only after `SLT-TT-00` → `SLT-SYN-10` → `SLT-SW-02`. Quote their shared non-SLT2 schedule baseline and empty post-target diff. `SLT-SW-02` owns the hand-set `_end_date` and targeted expiry hook; this task is observation-only for that leg and must not queue or fire it again.

## Test data
| Item | Value |
|---|---|
| Subscriptions | `S4` expired, `S_EML` cancelled, `SUB_BASIC` (Pro $15.00 → Basic $5.00 under SLT-SW-02) |
| Gates | `emails.subscription_expired.enabled`, `emails.auto_downgrade.enabled`; `subscription_reactivated` has **no** ArraySubs key — only the WooCommerce → Emails → `[ArraySubs] Subscription Reactivated` checkbox (record it) |

## Steps
1. Load `LIFE04_REN2_PRE` and the exact `has expired` Mailpit id published by SLT-LIFE-04. `show` that exact id and inspect the complete bounded owner delta; confirm recipient, `S4` status `arraysubs-expired`, `_end_date` is the 2026-08-27 final-charge moment, and no admin expiry variant. Do not search a fixed-size recent list.
2. `R1=$(mailpit-agent latest-id)` immediately before the mutation. In customer session `cust-SLT-EML-08`, open the exact numeric `S_EML` portal detail, capture the cancelled state, click **Reactivate**, and poll immutable R1 in calls no longer than 60 seconds through the two-minute cutoff for `has been reactivated`; save the matched id and text.
3. Confirm `S_EML` is `arraysubs-active` with a recomputed `_next_payment_date`; inspect the complete delta after R1 and require no `is active` / `New subscription` mail for exact `S_EML`. Classify unrelated shared-site mail.
4. `wp post meta get <Pro product ID> _arraysubs_auto_downgrade_product --allow-root` = Basic ID. Quote `SLT-TT-00`'s shared pre-flight queue/schedule snapshot and `SLT-SW-02`'s exact targeted `do_action('arraysubs_expire_subscription', SUB_BASIC)` command timestamp/output; that call does not create an Action Scheduler ID. Do not queue or run another expiry action.
5. Load SLT-SW-02's `M1` baseline and exact matched auto-downgrade Mailpit id; show that exact `has been changed to SLT2 Plan Basic` message for `SUB_BASIC`.
6. Inspect the complete SLT-SW-02 delta after M1 and confirm there is no `has expired` message for exact `SUB_BASIC`; classify unrelated shared-site mail.
7. Verify numeric `SUB_BASIC` now bills SLT2 Plan Basic $5.00 (product meta, next payment, note) and quote the shared post-drain schedule diff — no non-SLT `_next_payment_date` moved. Publish the exact active `S_EML` handoff consumed by SLT-EML-10, close `cust-SLT-EML-08`, independently review all three transition owners, then move the card through `review` to `done` with Review empty. Any live defect goes only in `qa/issues/` kanban card named `SLT-EML-08-<concise-slug>` with task/stage/plan path; subscription/product/action/message IDs; user IDs/logins/emails/roles; exact routes/session/hook timestamp; reproduction; expected/actual; and UI/meta/queue/log/Mailpit/shared-diff proof.

## Expected results
1. One `[mirror-help.arrayhash.com] Your subscription #<S4> has expired` to `slt2-core@example.test` at the 2026-08-27 expiry; no admin copy exists.
2. `[mirror-help.arrayhash.com] Your subscription for SLT2 Daily Core has been reactivated` to `slt2-eml@example.test` once; `S_EML` is `arraysubs-active`; no `new_subscription` (`EmailManager.php:345-349`).
3. `[mirror-help.arrayhash.com] Your subscription #<SUB_BASIC> has been changed to SLT2 Plan Basic` arrives once, gated by `emails.auto_downgrade.enabled=true`.
4. **No** `subscription_expired` for `SUB_BASIC` — suppression is by design; if one arrives, write a QA issue card under `qa/issues/` citing `EmailManager.php:317-322`.
5. `SUB_BASIC` renews at $5.00 on SLT2 Plan Basic; no non-SLT2 schedule moved and no non-SLT2 action ran.

## Emails expected
| # | Email | Trigger | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | subscription_expired | 2026-08-27 expiry | slt2-core | `#<S4> has expired` | exact SLT-LIFE-04 handoff id + bounded owner delta |
| 2 | subscription_reactivated | step 2 | slt2-eml | `has been reactivated` | immutable-baseline polls ≤60 seconds through the two-minute cutoff |
| 3 | auto_downgrade | SLT-SW-02 targeted expiry | slt2-switch | `has been changed to SLT2 Plan Basic` | exact SLT-SW-02 handoff id + complete M1 delta |
| 4 | NONE EXPECTED — expired mail for `SUB_BASIC`; `new_subscription` on reactivation | steps 2, 5 | — | `has expired` / `is active` | absent from the respective complete owner deltas |

## Evidence to capture
- `SLT-EML-08-01-expired.png`, `-02-reactivate.png`, `-03-pending-queue.png`, `-04-downgrade.png`; `S4`/`S_EML`/`SUB_BASIC` ids, Mailpit ids, quoted SLT-SW-02 targeted-hook timestamp/output, and both shared schedule snapshots.

## Pass criteria
- [ ] Expired mail once for `S4`, exact subject, no admin copy
- [ ] Reactivated mail once, `S_EML` active, no `new_subscription`; ungated status noted
- [ ] Auto-downgrade mail once, exact subject, Basic named
- [ ] No expired mail for `SUB_BASIC` (suppression proven)
- [ ] Only the targeted `SUB_BASIC` expiry hook ran; no duplicate expiry action and no non-SLT2 schedule moved
- [ ] Exact observation session closes and independent review reaches `done` with Review empty

## Isolation / teardown
- Leaves `S_EML` **active** on purpose — SLT-EML-10 uses it the same day and cancels it. `SUB_BASIC` stays on SLT2 Plan Basic; record the switch in the registry and hand it to SLT-SETUP-99A.
- Restores: no global setting or subscription date written by this observation task; sessions closed.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
