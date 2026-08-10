---
id: 102
title: 'Mid-grace recovery: new card in My Account, pay the failed renewal, and prove the next-payment anchor'
status: done
priority: high
created: 2026-08-02T03:43:11.49741294+02:00
updated: 2026-08-05T21:40:05.780856193+02:00
started: 2026-08-05T21:40:05.780855342+02:00
completed: 2026-08-05T21:40:05.780855342+02:00
tags:
    - renewal
    - day-07
due: "2026-08-09"
estimate: 2h
depends_on:
    - 101
    - 36
class: standard
---

> **SLT-DUN-05** · group `renewal` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Mid-grace recovery. A second `SLT Retry Daily` subscription, canonical alias `SUB_FAIL_RECOVERY`, fails its first renewal; the customer updates the payment method in My Account and pays the failed renewal order before the on-hold sweep. Assert it returns to `arraysubs-active`, retry meta clears, and state whether the new `_next_payment_date` anchors on `_renewal_scheduled_date` (the original due date, SLT-REF-01 §1) or on payment time.

## Scope
- Gateway: Stripe test
- Checkout: block signup, My Account order-pay for recovery
- Account: existing (`slt-fail`)
- Plugins: pro-required

## Preconditions
- `SLT-DUN-04` done and `SLT-MYA-05` follow-up C closed; canonical original `S_FAIL` is `arraysubs-cancelled` and its final role/access state is captured — only then are `slt-fail` and the product free, so the ladders never overlap.
- **Deliberate card deviation:** do NOT use the catalog's `9995`; it declines on-session so the parent order would never be paid. Reuse `0341`.
- `SLT-DUN-04` is done and original `S_FAIL` is cancelled; only then is `slt-fail` released. The frozen `one_per_customer=false` setting gates auto-migration off, so require a NEW subscription `SUB_FAIL_RECOVERY`; any reuse of cancelled `S_FAIL` is written as a standalone markdown file under `issues/`, never as a lifecycle-board card.
- Buy on D7 (2026-08-09) after 16:30 site, after the first ladder is closed. Never run `wp action-scheduler run` (C07).

## Test data
| Item | Value |
|---|---|
| Product | SLT Retry Daily $13.00 day/1 |
| Signup card | `4000 0000 0000 0341` (off-session decline) |
| Recovery card | `4242 4242 4242 4242` `12/34` CVC `123` |
| Sessions | `customer-SLT-DUN-05`, `admin-SLT-DUN-05` |

## Steps
1. **D7 (2026-08-09), 16:30-17:00 site, after both SLT-DUN-04 and SLT-MYA-05 follow-up C close.** Sign in as `slt-fail` in `customer-SLT-DUN-05-D7`; require the browser and serialized persistent carts empty and record exact order/subscription counts. Set `MPR=$(mailpit-agent latest-id)` immediately before adding the product.
2. Buy exact `SLT Retry Daily` at `/checkout/` on the `0341` card, saving it. Capture the unpopulated total before card entry, never capture populated hosted fields, and capture only the safe receipt. Record numeric order **O2** from that receipt, read `wp post meta get "$O2" _subscription_ids --format=json --allow-root`, and resolve its exactly one subscription through a strict numeric `jq -e` guard. Cross-check reverse parent/customer/product plus exact `+1` counts, assign that ID to `SUB_FAIL_RECOVERY`, and abort unless numeric; never use the WooCommerce order meta accessor or recency. Publish it and assert it differs from numeric `S_FAIL`; record **N**, **k2**, and exact invoice/charge action IDs/gates. Poll immutable MPR in repeated ≤60-second calls through the two-minute cutoff, reconcile the complete four-message parent-checkout set, prove both carts empty, close the D7 session, and leave the card in progress. Inside exact `[N+k2−300s,N+k2)` set/publish **`DUN05_FAIL_PRE=$(mailpit-agent latest-id)`**.
3. **D8 (2026-08-10), after `N + k2`:** resolve the same numeric subscription, require charge action `via WP Cron`, and resolve **R2** from its exact subscription/scheduled-cycle relationship plus reverse link; require R2 failed and attempts=1. Poll immutable `DUN05_FAIL_PRE` in repeated calls no longer than 60 seconds through the 10-minute cutoff, then require the exact customer/admin failure pair by subscription id and `To:`. Do not set the recovery-payment baseline a day early. Deadline: the hold sweep hits at `N + 24h` on D9.
4. **D9 (2026-08-11) morning, before `N + 24h`:** use fresh `customer-SLT-DUN-05-D9`; open `/my-account/view-subscription/$SUB_FAIL_RECOVERY/` → `snapshot -i`; screenshot the failure state; click **Manage payment methods**. On `/my-account/payment-methods/` use **Add payment method** for the `4242` card and make it default. Never capture the populated card form; capture only the safe saved-method row showing brand/last4.
5. Open `/my-account/orders/`, find exact R2 (`Failed`), click **Pay**; on order-pay pick the saved `4242` card, set `MPS=$(mailpit-agent latest-id)` immediately before submission, and click **Pay for order**.
6. Re-run **M**, **Q**, **L** and the post-status query; record payment UTC time **P**.
7. Poll immutable `MPS` in repeated calls no longer than 60 seconds through the five-minute cutoff for `Payment received for subscription #$SUB_FAIL_RECOVERY`; save/show the exact match and classify the complete MPS delta, allowing only WooCommerce mail linked to exact R2 in addition to that success mail.
8. Open numeric `SUB_FAIL_RECOVERY` in fresh `admin-SLT-DUN-05-D9`; screenshot status and notes. Report BOTH candidates — (a) `N + 1 day`, (b) `P + 1 day` — and which one `_next_payment_date` equals. Empty/prove both carts, close the D9 customer/admin sessions, independently review all three dated phases, then move through `review` to `done` with Review empty. Every defect goes only in `issues/SLT-DUN-05-<concise-slug>.md` with task/stage/plan path; original/recovery subscription, parent/renewal/action/message IDs; user ID/login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and UI/meta/queue/order/log/Mailpit proof.

## Expected results
1. A NEW subscription `SUB_FAIL_RECOVERY` is created (`SUB_FAIL_RECOVERY != S_FAIL`); if cancelled `S_FAIL` is migrated instead, write a standalone markdown file under `issues/`.
2. R2 is `failed` at `N + k2`, then `processing`/`completed` after order-pay, `$13.00`, on the `4242` card.
3. `SUB_FAIL_RECOVERY` is `arraysubs-active` after payment, never on-hold (recovery beat `N + 24h`).
4. Meta cleared: `_payment_retry_*` and `_last_payment_failure*` gone/zero, `_renewal_failure_resolved*` present, `_pending_renewal_order_id` deleted.
5. `_completed_payments` = 2; `_last_payment_date` = **P**.
6. **Headline:** new `_next_payment_date` = `N + 1 day` (base `_renewal_scheduled_date`, not payment time — SLT-REF-01 §1). If it equals `P + 1 day`, write a standalone issue file under `issues/` with both values.
7. Being future, both legs re-queue: invoice at `new_due + k2 − 6h`, charge at `new_due + k2`.
8. The retry action queued at `attempt + 86400s` is gone from **L**. If it survives, write a standalone candidate-bug file under `issues/` — an extra charge would fire near the new due date.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 0 | parent checkout set | O2 paid | customer / admin | exact O2 / SUB_FAIL_RECOVERY activation subjects | complete MPR delta |
| 1 | `payment_failed` + `admin_payment_failed` | `N + k2` | customer / admin | `Payment failed for subscription #SUB_FAIL_RECOVERY` | complete `DUN05_FAIL_PRE` delta, exact `To:` pair |
| 2 | `payment_successful` | order-pay done | `slt-fail@example.test` | `Payment received for subscription #SUB_FAIL_RECOVERY` | complete MPS delta |
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
- `SUB_FAIL_RECOVERY` stays live and renews daily on the `4242` card — hand it to `SLT-SETUP-99A` (D10) so the D11/D12 watch is not polluted.
- Nothing global changed; the `4242` token dies with `slt-fail` in `SLT-SETUP-99B`. Close only the exact phase sessions named above.

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

[[2026-08-05]] Wed 21:40
UNVERIFIED (no original S_FAIL ladder; downstream recovery scenario impossible) on 2026-08-05.

This card cannot start as authored because both mandatory preconditions are impossible now: (1) `SLT-DUN-04` must finish with the canonical original `S_FAIL` subscription cancelled, and (2) `SLT-MYA-05` follow-up C must have closed after observing the same ladder's final role/access state, so `slt-fail` is only then released for a SECOND `SLT Retry Daily` purchase. Upstream execution proved the opposite. Task #33 published the immutable `S_FAIL unavailable` branch on 2026-08-05, task #101 then closed `UNVERIFIED` because the terminal cancellation can never occur without that source ladder, and task #36 closed `UNVERIFIED` because the MYA-05 setup bracket never opened. Live re-check on 2026-08-05 still returns zero ArraySubs subscription rows for user `351` / product `12108`, and the expected `members_access` rule ids `slt_role_pro_member` / `slt_url_member_area` are absent because the underlying activation never happened.

Without an original cancelled `S_FAIL`, this card's core assertion — proving a NEW `SUB_FAIL_RECOVERY` distinct from the cancelled first ladder and then recovering its failed renewal before the hold sweep — cannot be executed honestly. No later recovery path authorizes fabricating the missing first ladder, back-dating it, or treating a first-ever late purchase as the required second subscription. Closing this card rather than inventing a substitute scenario outside the authored calendar and isolation contract.
