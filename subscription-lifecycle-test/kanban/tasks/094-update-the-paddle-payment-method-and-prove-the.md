---
id: 94
title: Update the Paddle payment method and prove the next Paddle-driven renewal uses it, plus the missing local update surface
status: todo
priority: high
created: 2026-08-02T03:43:10.950263974+02:00
updated: 2026-08-02T03:43:22.092437529+02:00
tags:
    - admin
    - portal
    - day-06
    - has-conflicts
due: "2026-08-08"
estimate: 1.5h
depends_on:
    - 70
    - 26
    - 23
class: standard
---

> **SLT-MYA-03** · group `admin` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / multi-day deviation vs frozen baseline** — with `SLT-LIFE-03`, `SLT-MYA-01`, `SLT-SW-07`, `SLT-SW-10`, `SLT-LIFE-02`, `SLT-MYA-04`

- *Problem:* SLT-LIFE-03 flips two global settings out of baseline - skip_renewal.enabled false->true and skip_renewal.cutoff_days 2->0 - and restores them only at its step 7, which happens two days later (after the shifted cycle charges). That is a 2-3 day site-wide deviation in which every customer portal renders a 'Skip Next Renewal' control. Colliding audits: SLT-MYA-01 expected result 5 lists 'Skip Next Renewal' among the five actions an active subscription must expose - which is wrong against the frozen baseline (skip_renewal.enabled=false) and only accidentally right if MYA-01 happens to run inside LIFE-03's bracket. SLT-ADM-03 asserts the opposite ('Skip Renewal is expectedly unavailable'), so the two tasks contradict each other. SLT-SW-07, SLT-SW-10, SLT-LIFE-02, SLT-MYA-03 and SLT-MYA-04 all screenshot the portal Actions card on D5-D7 and would file the Skip control as unexpected UI.
- *Required fix:* Two changes. (1) Correct SLT-MYA-01 expected result 5 to the four baseline actions - Change Plan, Cancel Subscription, Renew Early, Pause Subscription - and add 'Skip Next Renewal MUST be absent (skip_renewal.enabled=false)'; quote the registry WINDOW BASELINE table as C14 requires. (2) Compress LIFE-03's deviation to a single short bracket: settings ON, perform skip / undo / 5-cycle clamp / undo / final 1-cycle skip, settings RESTORED, all inside one <30 min window on D5 with open/close UTC recorded - the pending skip lives in subscription meta (_skip_cycles_remaining, _original_next_payment_date) and completeSkippedCycles() runs off the renewal path, so the setting does not need to stay on for the shifted cycle to complete. Verify that on the day; if completion does prove to require the flag, move LIFE-03 wholesale to D8-D9 where no portal audit runs. Also correct LIFE-03's internal dates: it is a D5 (2026-08-07) task, so D_now = 08-08, skip1 -> 08-09, skip3 -> 08-11, original due 08-08 shows nothing (watch D7 negative) and the shifted $20.00 charge lands 08-09 PM (watch D8) - which also clears 2026-08-10 for SLT-LIFE-01.

---
## Objective
Establish what "update payment method" means for a Paddle subscription here, then prove the next unassisted renewal charges the new card. Code-verified: ArraySubs renders no Paddle update control (the portal only links to `/my-account/payment-methods/`) and `PaddleGateway::handlePaymentMethodUpdated()` (`:1479-1503`) only syncs `next_billed_at` and status, writing no card metas. The local-surface half is EXPLORATORY - document behaviour, do not assert a spec.

## Scope
- Gateway: Paddle sandbox
- Checkout: N/A (portal + Paddle-hosted update page)
- Account: existing (`slt-paddle`)
- Plugins: pro-required

## Preconditions
- `SLT Paddle Daily` bought by `slt-paddle` on D2 after 12:00; `SUB_PADDLE` is `arraysubs-active` with `_gateway_paddle_subscription_id` set.
- SLT-SETUP-05's matrix is binding: `sca:false`, `early_renewal:false`, no renewal sync - those absences are not bugs. Renewals are Paddle-driven; the local `arraysubs_process_renewal` leg is a no-op returning `pending` (`:598-629`) and must never be force-run.
- **Act 08:00-11:00 site on D6 (2026-08-08)**, before the afternoon `next_billed_at` anniversary, so the next Paddle charge lands that same afternoon.

## Test data
| Item | Value |
|---|---|
| Account | slt-paddle / `SltQa!2026#Pass`, session `--session customer-MYA-03` |
| Product | SLT Paddle Daily, $11.00 every day |
| New card | a Paddle sandbox test card whose last4 differs from the one on file - **record the exact number used** |
| API key | sandbox api_key from `wp option get woocommerce_arraysubs_paddle_settings --allow-root` |

## Steps
1. `mailpit-agent latest-id` -> `MB03`. Record `SUB_PADDLE`, its Paddle subscription id, `_next_payment_date` and crc32 offset.
2. BEFORE dump -> `SLT-MYA-03-before.txt`: `wp post meta list <SUB_PADDLE> --keys=_payment_method_title,_gateway_payment_method_id,_payment_method_brand,_payment_method_last4,_payment_method_updated_at --allow-root`.
3. Open `/my-account/payment-methods/` -> log in -> screenshot. **Exploratory:** record whether any saved method exists, whether Add payment method is offered, and which gateway it targets.
4. Open `/my-account/view-subscription/<SUB_PADDLE>/`; screenshot the `Payment Method:` row and `Subscription Actions`. `Renew Early` must be absent; record any Paddle "update card" link (expected: none).
5. `curl -s -H "Authorization: Bearer <api_key>" https://sandbox-api.paddle.com/subscriptions/<paddle_sub_id>` -> read `data.management_urls.update_payment_method`; save the JSON. If absent, stop at UNVERIFIED.
6. Open that URL in the same session, enter the new sandbox card, submit, screenshot.
7. Within 5 min repeat the step-2 dump into `-after.txt` and diff; in wp-admin read the subscription notes for `Paddle subscription updated - state synchronized.`
8. Re-poll the API; record `payment_method_details` last4 and `next_billed_at` and compare with local `_next_payment_date`. `syncNextPaymentDate()` writes the meta but does not reschedule the AS legs (`:2305`) - record any misalignment.
9. **Follow-up, watch day D7 = 2026-08-09 (morning check):** for the 2026-08-08 PM charge confirm a paid $11.00 renewal order with `_is_renewal_order=yes` and a `_paddle_transaction_id`, the sandbox transaction showing the NEW last4, and `_payment_retry_attempts` still 0.

## Expected results
1. `/my-account/payment-methods/` offers no Paddle saved method and no Paddle add-card path: there is no local Paddle update surface. The detail page shows `Payment Method:` from `_payment_method_title`, links only to that page, and has no `Renew Early`.
2. The Paddle-hosted page accepts the new card and the subscription gains the note `Paddle subscription updated - state synchronized.`
3. **`_payment_method_brand`, `_payment_method_last4`, `_gateway_payment_method_id` and `_payment_method_updated_at` are UNCHANGED locally**, so the portal still shows stale card details.
4. `_next_payment_date` matches `next_billed_at`, `_gateway_status` maps `active->active`, the post status is unchanged.
5. Watch day D7 (2026-08-09): the 2026-08-08 PM charge produced a paid $11.00 renewal order on the new card and `_payment_retry_attempts` is 0 - Paddle never reaches `scheduleNextRetry()`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Card update, `subscription.updated`, and card-expiring (never emitted, `card_expiry_notice:false`) | - | - | `latest-id` unchanged from `MB03` for 10 min after step 6; no `Update the card` mail |
| 2 | payment_successful | `transaction.completed`, 2026-08-08 PM | slt-paddle | `Payment received for subscription #<SUB_PADDLE>` | `mailpit-agent wait-new <id> 900 "Payment received"` |

## Evidence to capture
- Screenshots `SLT-MYA-03-01-methods-empty.png`, `-02-detail-row.png`, `-03-update-page.png`, `-04-notes.png`; both meta dumps and their diff; both API responses; the card used; renewal order id; Mailpit ids.

## Pass criteria
- [ ] Local Paddle update surface documented (expected: none) with screenshots
- [ ] Paddle page accepted the new card, sync note appeared, local card metas unchanged
- [ ] `_next_payment_date` reconciled to `next_billed_at`, any AS misalignment recorded
- [ ] The 2026-08-08 PM charge used the new card and produced a paid $11.00 renewal order (watch day D7 = 2026-08-09), no retry scheduled, no unexpected mail

## Isolation / teardown
- Touches only `slt-paddle` and its own sandbox subscription; sandbox objects are left in place. Close only `--session customer-MYA-03`.
- If the update page is unreachable or the webhook never arrives, record UNVERIFIED with both API payloads; never hand-edit gateway or card metas to fake the result.

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
