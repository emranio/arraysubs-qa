---
id: 103
title: Customer view of an unpaid renewal invoice and paying it from the portal before the automatic charge leg
status: todo
priority: high
created: 2026-08-02T03:43:11.573841575+02:00
updated: 2026-08-02T03:43:22.990570614+02:00
tags:
    - admin
    - portal
    - day-07
    - has-conflicts
due: "2026-08-09"
estimate: 1.5h
depends_on:
    - 70
    - 58
class: standard
---

> **SLT-MYA-04** · group `admin` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / same-day bracket collision** — with `SLT-SW-08`, `SLT-SW-04`, `SLT-SW-02`, `SLT-ADM-01`, `SLT-DUN-05`

- *Problem:* SLT-SW-08 (d7) sets proration.switch_fees.upgrade from 0 to 7.50 globally and restores it in the same task, declaring 'no other SLT switch may run between set and restore'. SLT-SW-04 (d7) performs a Basic->Pro upgrade the same day and asserts its proration order matches SLT-SW-01's record-for-record with 'no switch-fee row'. If SW-04 runs inside SW-08's bracket its order gains a $7.50 'Plan Upgrade switch fee' line and the comparison fails for the wrong reason. The bracket file exists but nothing sequences the two tasks.
- *Required fix:* Fix the D7 order explicitly in the calendar and in both task bodies: SLT-SW-04 completes and its proration order is PAID before SLT-SW-08 opens its bracket. SW-08's step 2 gains a pre-flight assertion: 'SLT-SW-04 is done on the board and no plan_switch order created today is still unpaid'. SW-08's bracket file must record open/close UTC and be posted to the registry so any switch order created inside it can be attributed and re-run.

**`high` · shared-global-setting / multi-day deviation vs frozen baseline** — with `SLT-LIFE-03`, `SLT-MYA-01`, `SLT-SW-07`, `SLT-SW-10`, `SLT-LIFE-02`, `SLT-MYA-03`

- *Problem:* SLT-LIFE-03 flips two global settings out of baseline - skip_renewal.enabled false->true and skip_renewal.cutoff_days 2->0 - and restores them only at its step 7, which happens two days later (after the shifted cycle charges). That is a 2-3 day site-wide deviation in which every customer portal renders a 'Skip Next Renewal' control. Colliding audits: SLT-MYA-01 expected result 5 lists 'Skip Next Renewal' among the five actions an active subscription must expose - which is wrong against the frozen baseline (skip_renewal.enabled=false) and only accidentally right if MYA-01 happens to run inside LIFE-03's bracket. SLT-ADM-03 asserts the opposite ('Skip Renewal is expectedly unavailable'), so the two tasks contradict each other. SLT-SW-07, SLT-SW-10, SLT-LIFE-02, SLT-MYA-03 and SLT-MYA-04 all screenshot the portal Actions card on D5-D7 and would file the Skip control as unexpected UI.
- *Required fix:* Two changes. (1) Correct SLT-MYA-01 expected result 5 to the four baseline actions - Change Plan, Cancel Subscription, Renew Early, Pause Subscription - and add 'Skip Next Renewal MUST be absent (skip_renewal.enabled=false)'; quote the registry WINDOW BASELINE table as C14 requires. (2) Compress LIFE-03's deviation to a single short bracket: settings ON, perform skip / undo / 5-cycle clamp / undo / final 1-cycle skip, settings RESTORED, all inside one <30 min window on D5 with open/close UTC recorded - the pending skip lives in subscription meta (_skip_cycles_remaining, _original_next_payment_date) and completeSkippedCycles() runs off the renewal path, so the setting does not need to stay on for the shifted cycle to complete. Verify that on the day; if completion does prove to require the flag, move LIFE-03 wholesale to D8-D9 where no portal audit runs. Also correct LIFE-03's internal dates: it is a D5 (2026-08-07) task, so D_now = 08-08, skip1 -> 08-09, skip3 -> 08-11, original due 08-08 shows nothing (watch D7 negative) and the shifted $20.00 charge lands 08-09 PM (watch D8) - which also clears 2026-08-10 for SLT-LIFE-01.

**`high` · dependency-gap / unowned purchases** — with `SLT-ADM-07`, `SLT-ADM-08`, `SLT-SW-01`, `SLT-SW-03`, `SLT-SW-02`, `SLT-EML-08`

- *Problem:* Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 time-travel renewals for month segment-1/segment-2, week segment-3 (SLT-SYN-07's tail, due 2026-08-15) and the flex-variable tail - audit C17 mandates one dedicated D8 owner and none exists. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.
- *Required fix:* Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add step 0 to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments on 2026-08-08 (D6) after 12:00 - day-in-cycle 8, past both boundaries, resolves to segment 3, next payment 2026-10-01 00:00 site = 2026-09-30 18:00 UTC'. Create SLT-TT-00 on D8 as the single time-travel owner: pre-flight pending-queue screenshot + the 13 non-SLT _next_payment_date snapshot, then the month seg1/seg2 and week seg3 renewals and the flex-variable tail, single-action-by-id only, then the post-drain non-SLT diff proof - and have SYN-10, SW-02, EML-08, EML-10 and LIFE-01 quote its snapshot instead of each taking their own.

---
## Objective
Exercise the only window in which a customer can see and pay an unpaid renewal invoice: between the invoice leg at `due + k - 6h` and the charge leg at `due + k` (`RenewalScheduler.php:135-188`). Verify the portal view of that pending order, pay it from the `Pay` button, and prove the schedule math matches the unassisted path and the charge leg is a clean no-op.

## Scope
- Gateway: Stripe test
- Checkout: N/A (WooCommerce order-pay page reached from the portal)
- Account: existing (`slt-core`)
- Plugins: both

## Preconditions
- SLT-MYA-01 done. `SLT Signup Fee Daily` (`SUB_FEE`) is `arraysubs-active`, bought by slt-core on D3.
- **This task deliberately customer-pays exactly one cycle of one subscription.** Before acting, post to `slt-catalog-registry`: "SLT-MYA-04 customer-pays the 2026-08-09 cycle of SUB_FEE; that cycle is NOT an unattended charge." Without this note the watch files a false bug.
- Do NOT use `SLT Retry Daily` - paying its failed order would rescue another task's dunning ladder. This task runs no Action Scheduler drain.

## Test data
| Item | Value |
|---|---|
| Account | slt-core / `SltQa!2026#Pass`, session `--session customer-MYA-04` |
| Product | SLT Signup Fee Daily, $9.00/day; signup fee charged on the initial order only |
| Renewal total | `$9.00` - no fee line, no tax line |
| Card | the saved Stripe method on the account |

## Steps
1. `php -r '$id=<SUB_FEE>;$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("k=%ds\n",$h%21600);'`. Read `D = _next_payment_date` (UTC): invoice fires at `D+k-6h`, charge at `D+k`. Convert both to UTC+6 into Notes.
2. `mailpit-agent latest-id` -> `MB04`.
3. Shortly after `D+k-6h` find the newest slt-core order with `_subscription_id = SUB_FEE`; confirm status `pending`, `_is_renewal_order=yes`, `_renewal_scheduled_date` = `D`, and that `SUB_FEE` has `_pending_renewal_order_id` and is STILL `arraysubs-active` (`RenewalProcessor.php:114-122`).
4. Open `/my-account/view-subscription/<SUB_FEE>/` -> log in -> screenshot `Related Orders`. Record the new row (number, date, status, total) and that it offers `View` + `Pay` (`view-subscription.php:811`) while older rows offer only `View`.
5. Open the order via `View` and screenshot: line item `SLT Signup Fee Daily`, total `$9.00`, no fee line, no tax line.
6. Click `Pay` -> `snapshot -i`; confirm amount due `$9.00` and the Stripe box offers the saved card. `mailpit-agent latest-id` -> `MB04b`, then submit payment.
7. Screenshot the order-received page. Verify order `processing`/`completed`, `_last_gateway_transaction_id` set, `_pending_renewal_order_id` deleted, `_completed_payments` +1, and `_next_payment_date` = `D + 1 day` exactly - computed from `_renewal_scheduled_date`, not the payment time (`OrderIntegration.php:1629-1652`).
8. Reload the portal detail page: `Next Payment:` shows the new date in site-local time and the paid order offers only `View`.
9. **Same day after `D+k`:** confirm `arraysubs_process_renewal` for SUB_FEE completed with no new order and no second charge - `isRenewalDue()` needs `_next_payment_date <= now` and it already advanced (`RenewalProcessor.php:530-542`). Screenshot the Scheduled Actions row.
10. **Watch day D8 = 2026-08-10 (morning check):** exactly one paid renewal order for the 2026-08-09 cycle, one `payment_successful` mail, the 2026-08-10 cycle queued normally.

## Expected results
1. A pending renewal order for SUB_FEE exists after `D+k-6h`, total `$9.00`, `_is_renewal_order=yes`, `_renewal_scheduled_date = D`; SUB_FEE stays `arraysubs-active` with `_pending_renewal_order_id` until payment.
2. The portal row shows Pending payment and offers `View` + `Pay`; paid rows offer only `View`. The order view shows one $9.00 line, no fee line, no tax line.
3. The order-pay page charges `$9.00` on the saved card and succeeds; afterwards `_pending_renewal_order_id` is deleted, `_completed_payments` +1, a transaction id recorded.
4. `_next_payment_date` = `D + 1 day` to the second - identical to the unattended path.
5. The charge leg at `D+k` is a no-op: no second order, no second Stripe charge, no failure note.
6. Watch day D8 (2026-08-10): one paid renewal order and one payment-successful mail for that cycle.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Invoice at `D+k-6h` (suppressed for auto-payment subs, `EmailManager.php:504-510`) and the `D+k` charge leg | - | - | No `Invoice for subscription #<SUB_FEE>` between `MB04` and step 6; no `payment_failed` and no second `Payment received` after step 9 |
| 2 | payment_successful, plus the WooCommerce processing/completed order mail | Customer pays from the order-pay page | slt-core | `Payment received for subscription #<SUB_FEE>`; the order number | `mailpit-agent wait-new <MB04b> 300 "Payment received"`, then `list 50` to record the order-mail id |

## Evidence to capture
- Screenshots `SLT-MYA-04-01-orders-pending.png`, `-02-order-detail.png`, `-03-order-pay.png`, `-04-received.png`, `-05-action-noop.png`; `k`, `D` and both window boundaries in UTC and site-local; renewal order id; before/after `_next_payment_date`.

## Pass criteria
- [ ] Pending renewal order appeared inside `[D+k-6h, D+k)` with the correct total and metas
- [ ] Pay button shown only on the unpaid order; total $9.00 with no fee and no tax line
- [ ] Payment succeeded and all post-payment metas are correct
- [ ] `_next_payment_date` = `D + 1 day` exactly; charge leg at `D+k` was a no-op with no second order
- [ ] Invoice mail absent; payment-successful mail present exactly once

## Isolation / teardown
- Touches only slt-core's SUB_FEE and only the 2026-08-09 cycle; the registry note is mandatory so the D8 watch does not read this as a failed unattended renewal.
- Nothing force-run, no hook drained, no setting changed; the subscription is left active with its next cycle queued. Close only `--session customer-MYA-04`.

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
