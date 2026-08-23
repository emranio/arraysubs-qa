---
id: 102
title: 'Mid-grace recovery: new card in My Account, pay the failed renewal, and prove the next-payment anchor'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - day-07
due: "2026-08-30"
estimate: 2h
depends_on:
    - 101
    - 36
class: standard
---

> **SLT-DUN-05** · group `renewal` · scheduled **D07** (2026-08-30)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Mid-grace recovery. A second `SLT2 Retry Daily` subscription, canonical alias `SUB_FAIL_RECOVERY`, fails its first renewal; the customer updates the payment method in My Account and pays the failed renewal order before the on-hold sweep. Assert it returns to `arraysubs-active`, retry meta clears, and state whether the new `_next_payment_date` anchors on `_renewal_scheduled_date` (the original due date, SLT-REF-01 §1) or on payment time.

## Scope
- Gateway: Stripe test
- Checkout: block signup, My Account order-pay for recovery
- Account: existing (`slt2-fail`)
- Plugins: core-owned Stripe automatic-payment/recovery path

## Preconditions
- `SLT-DUN-04` done and `SLT-MYA-05` follow-up C closed; canonical original `S_FAIL` is `arraysubs-cancelled` and its final role/access state is captured — only then are `slt2-fail` and the product free, so the ladders never overlap.
- **Deliberate card deviation:** do NOT use the catalog's `9995`; it declines on-session so the parent order would never be paid. Reuse `0341`.
- `SLT-DUN-04` is done and original `S_FAIL` is cancelled; only then is `slt2-fail` released. The frozen `one_per_customer=false` setting gates auto-migration off, so require a NEW subscription `SUB_FAIL_RECOVERY`; any reuse of cancelled `S_FAIL` creates/updates the mandatory `qa/issues/` kanban card and blocks this task.
- Buy on D7 (2026-08-30) after 16:30 site, after the first ladder is closed. Never run `wp action-scheduler run` (C07).

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Retry Daily $13.00 day/1 |
| Signup card | `4000 0000 0000 0341` (off-session decline) |
| Recovery card | `4242 4242 4242 4242` `12/34` CVC `123` |
| Sessions | `customer-SLT-DUN-05`, `admin-SLT-DUN-05` |

## Steps
1. **D7 (2026-08-30), 16:30-17:00 site, after both SLT-DUN-04 and SLT-MYA-05 follow-up C close.** Sign in as `slt2-fail` in `customer-SLT-DUN-05-D7`; require the browser and serialized persistent carts empty and record exact order/subscription counts. Set `MPR=$(mailpit-agent latest-id)` immediately before adding the product.
2. Buy exact `SLT2 Retry Daily` at `/checkout/` on the `0341` card, saving it. Capture the unpopulated total before card entry, never capture populated hosted fields, and capture only the safe receipt. Record numeric order **O2** from that receipt, read `wp post meta get "$O2" _subscription_ids --format=json --allow-root`, and resolve its exactly one subscription through a strict numeric `jq -e` guard. Cross-check reverse parent/customer/product plus exact `+1` counts, assign that ID to `SUB_FAIL_RECOVERY`, and abort unless numeric; never use the WooCommerce order meta accessor or recency. Publish it and assert it differs from numeric `S_FAIL`; record **N**, **k2**, and exact invoice/charge action IDs/gates. Poll immutable MPR in repeated ≤60-second calls through the two-minute cutoff, reconcile the complete four-message parent-checkout set, prove both carts empty, close the D7 session, and leave the card in progress. Inside exact `[N+k2−300s,N+k2)` set/publish **`DUN05_FAIL_PRE=$(mailpit-agent latest-id)`**.
3. **D8 (2026-08-31), after `N + k2`:** resolve the same numeric subscription, require charge action `via WP Cron`, and resolve **R2** from its exact subscription/scheduled-cycle relationship plus reverse link; require R2 failed and attempts=1. Poll immutable `DUN05_FAIL_PRE` in repeated calls no longer than 60 seconds through the 10-minute cutoff, then require the exact customer/admin failure pair by subscription id and `To:`. Do not set the recovery-payment baseline a day early. Deadline: the hold sweep hits at `N + 24h` on D9.
4. **D9 (2026-09-01) morning, before `N + 24h`:** use fresh `customer-SLT-DUN-05-D9`; open `/my-account/view-subscription/$SUB_FAIL_RECOVERY/` → `snapshot -i`; screenshot the failure state; click **Manage payment methods**. On `/my-account/payment-methods/` use **Add payment method** for the `4242` card and make it default. Never capture the populated card form; capture only the safe saved-method row showing brand/last4.
5. Open `/my-account/orders/`, find exact R2 (`Failed`), click **Pay**; on order-pay pick the saved `4242` card, set `MPS=$(mailpit-agent latest-id)` immediately before submission, and click **Pay for order**.
6. Re-run **M**, **Q**, **L** and the post-status query; record payment UTC time **P**.
7. Poll immutable `MPS` in repeated calls no longer than 60 seconds through the five-minute cutoff for `Payment received for subscription #$SUB_FAIL_RECOVERY`; save/show the exact match and classify the complete MPS delta, allowing only WooCommerce mail linked to exact R2 in addition to that success mail.
8. Open numeric `SUB_FAIL_RECOVERY` in fresh `admin-SLT-DUN-05-D9`; screenshot status and notes. Report BOTH candidates — (a) `N + 1 day`, (b) `P + 1 day` — and which one `_next_payment_date` equals. Empty/prove both carts, close the D9 customer/admin sessions, independently review all three dated phases, then move through `review` to `done` with Review empty. Every defect goes only in `qa/issues/` kanban card named `SLT-DUN-05-<concise-slug>` with task/stage/plan path; original/recovery subscription, parent/renewal/action/message IDs; user ID/login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and UI/meta/queue/order/log/Mailpit proof.

## Expected results
1. A NEW subscription `SUB_FAIL_RECOVERY` is created (`SUB_FAIL_RECOVERY != S_FAIL`); if cancelled `S_FAIL` is migrated instead, create or update the mandatory `qa/issues/` kanban card.
2. R2 is `failed` at `N + k2`, then `processing`/`completed` after order-pay, `$13.00`, on the `4242` card.
3. `SUB_FAIL_RECOVERY` is `arraysubs-active` after payment, never on-hold (recovery beat `N + 24h`).
4. Meta cleared: `_payment_retry_*` and `_last_payment_failure*` gone/zero, `_renewal_failure_resolved*` present, `_pending_renewal_order_id` deleted.
5. `_completed_payments` = 2; `_last_payment_date` = **P**.
6. **Headline:** new `_next_payment_date` = `N + 1 day` (base `_renewal_scheduled_date`, not payment time — SLT-REF-01 §1). If it equals `P + 1 day`, write a QA issue card under `qa/issues/` with both values.
7. Being future, both legs re-queue: invoice at `new_due + k2 − 6h`, charge at `new_due + k2`.
8. The retry action queued at `attempt + 86400s` is gone from **L**. If it survives, create or update the mandatory `qa/issues/` kanban card — an extra charge would fire near the new due date.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 0 | parent checkout set | O2 paid | customer / admin | exact O2 / SUB_FAIL_RECOVERY activation subjects | complete MPR delta |
| 1 | `payment_failed` + `admin_payment_failed` | `N + k2` | customer / admin | `Payment failed for subscription #SUB_FAIL_RECOVERY` | complete `DUN05_FAIL_PRE` delta, exact `To:` pair |
| 2 | `payment_successful` | order-pay done | `slt2-fail@example.test` | `Payment received for subscription #SUB_FAIL_RECOVERY` | complete MPS delta |
| 3 | hold / cancel / 2nd `new_subscription` **NONE EXPECTED** | after the parent checkout | — | — | No `is on hold`, `has been cancelled` or 2nd `is active` for `SUB_FAIL_RECOVERY` |

## Evidence to capture
- Screenshots `SLT-DUN-05-01-failure`, `-02-pay-methods`, `-03-pay`, `-04-active`.
- `SUB_FAIL_RECOVERY`, O2, R2, k2, N, P; both candidate next-payment values and the match; M/Q/L before/after; MPR, `DUN05_FAIL_PRE`, MPS and exact Mailpit ids + `To:`.

## Pass criteria
- [ ] ER1/2 new `SUB_FAIL_RECOVERY` (not a migration); R2 `failed` → paid $13.00 on the new card
- [ ] ER3/4/5 `SUB_FAIL_RECOVERY` active, never on-hold; meta cleared; `_completed_payments` = 2
- [ ] ER6 next-payment anchor stated, with both candidates
- [ ] ER7/8 both legs re-queued with k2; no stale retry action for `[SUB_FAIL_RECOVERY]`
- [ ] All three email rows satisfied, including the negative row
- [ ] D7/D8/D9 sessions and relationships are exact; Review returns to zero

## Isolation / teardown
- `SUB_FAIL_RECOVERY` stays live and renews daily on the `4242` card — hand it to `SLT-SETUP-99A` (D11) so the D11/D12 watch is not polluted.
- Nothing global changed; the `4242` token dies with `slt2-fail` in `SLT-SETUP-99B`. Close only the exact phase sessions named above.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
