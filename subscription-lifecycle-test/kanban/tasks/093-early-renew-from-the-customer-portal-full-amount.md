---
id: 93
title: 'EARLY renew from the customer portal: full amount, next date anchored to the original due date, legs replaced'
status: todo
priority: high
created: 2026-08-02T03:43:10.879397181+02:00
updated: 2026-08-02T03:43:22.027466186+02:00
tags:
    - renewal
    - day-06
    - has-conflicts
due: "2026-08-08"
estimate: 1h30m
depends_on:
    - 11
    - 5
class: standard
---

> **SLT-LIFE-02** · group `renewal` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / multi-day deviation vs frozen baseline** — with `SLT-LIFE-03`, `SLT-MYA-01`, `SLT-SW-07`, `SLT-SW-10`, `SLT-MYA-03`, `SLT-MYA-04`

- *Problem:* SLT-LIFE-03 flips two global settings out of baseline - skip_renewal.enabled false->true and skip_renewal.cutoff_days 2->0 - and restores them only at its step 7, which happens two days later (after the shifted cycle charges). That is a 2-3 day site-wide deviation in which every customer portal renders a 'Skip Next Renewal' control. Colliding audits: SLT-MYA-01 expected result 5 lists 'Skip Next Renewal' among the five actions an active subscription must expose - which is wrong against the frozen baseline (skip_renewal.enabled=false) and only accidentally right if MYA-01 happens to run inside LIFE-03's bracket. SLT-ADM-03 asserts the opposite ('Skip Renewal is expectedly unavailable'), so the two tasks contradict each other. SLT-SW-07, SLT-SW-10, SLT-LIFE-02, SLT-MYA-03 and SLT-MYA-04 all screenshot the portal Actions card on D5-D7 and would file the Skip control as unexpected UI.
- *Required fix:* Two changes. (1) Correct SLT-MYA-01 expected result 5 to the four baseline actions - Change Plan, Cancel Subscription, Renew Early, Pause Subscription - and add 'Skip Next Renewal MUST be absent (skip_renewal.enabled=false)'; quote the registry WINDOW BASELINE table as C14 requires. (2) Compress LIFE-03's deviation to a single short bracket: settings ON, perform skip / undo / 5-cycle clamp / undo / final 1-cycle skip, settings RESTORED, all inside one <30 min window on D5 with open/close UTC recorded - the pending skip lives in subscription meta (_skip_cycles_remaining, _original_next_payment_date) and completeSkippedCycles() runs off the renewal path, so the setting does not need to stay on for the shifted cycle to complete. Verify that on the day; if completion does prove to require the flag, move LIFE-03 wholesale to D8-D9 where no portal audit runs. Also correct LIFE-03's internal dates: it is a D5 (2026-08-07) task, so D_now = 08-08, skip1 -> 08-09, skip3 -> 08-11, original due 08-08 shows nothing (watch D7 negative) and the shifted $20.00 charge lands 08-09 PM (watch D8) - which also clears 2026-08-10 for SLT-LIFE-01.

**`high` · same-subscription collision / ambiguous target** — with `SLT-EML-05`, `SLT-EML-02`, `SLT-EML-15`, `SLT-MYA-02`

- *Problem:* SLT-LIFE-02 (d6) targets 'S1 - a live arraysubs-active SLT Daily Core subscription from the SLT-CHK-* run' without naming it, and its arithmetic uses $10.00 day/1, which describes SUB_CORE (slt-core, the control spine). It consumes one cycle by paying it early, replaces both legs and shifts the anniversary. SLT-EML-05 runs on the SAME day (d6) and also consumes one SUB_CORE cycle by setting _auto_renew=off and paying the invoice manually. Two tasks eating the same cycle on the same day makes both results unreadable, and either one silently invalidates the D1-D12 watch's 'SLT Daily Core renews $10.00 unattended every afternoon' baseline that REN-01/REN-02/EML-15/ADM-06 established.
- *Required fix:* Pin SLT-LIFE-02's S1 to SLT-CHK-02's subscription (slt-core2 + SLT Daily Core, day/1, $10.00, Stripe, saved token, unsynced, no pending skip) - structurally identical to the spine and claimed by nothing else after D0. Name the subscription id explicitly in LIFE-02's Test data and preconditions, and keep its step 8 registry note ('slt-core2's cycle N was paid early on 2026-08-08') so the watch does not read the missing unattended renewal as a failure. Leave SUB_CORE to EML-05 on D6. Add a standing registry section 'control-spine reservations' naming SUB_CORE's owning tasks per day.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Renew a live subscription EARLY from the customer portal and prove SLT-REF-07 Part A: the charge is the ordinary full renewal total, no discount or proration (EarlyRenewManager.php:180); `_next_payment_date` advances one full cycle from the ORIGINAL due date, not the payment moment, because the order carries `_renewal_scheduled_date = D_original` (OrderIntegration.php:1637-1643); the stale legs are replaced, not duplicated. Plus the Paddle negative - `PaddleGateway` declares `early_renewal => false`.

## Scope
- Gateway: Stripe test (Paddle read-only negative)
- Checkout: N/A (portal action)
- Account: existing - owner of the live SLT Daily Core subscription
- Plugins: pro-required

## Preconditions
- `customer_actions.allow_early_renew = true` from the SLT-SETUP-02 baseline; quote the "WINDOW BASELINE (frozen)" registry table (audit C14) and toggle nothing here.
- A live `arraysubs-active` SLT Daily Core subscription (S1) from the SLT-CHK-* run, Stripe with a saved token; log in as its owner. S1 must be unsynced, with no pending skip and no open renewal order.
- **Timing gate:** eligibility returns `invoice_pending` once the invoice leg has run - act BEFORE `due + k - 6h`.

## Test data
| Item | Value |
|---|---|
| Subscription | S1 (SLT Daily Core, day/1, $10.00), portal `/my-account/view-subscription/S1/` |
| Expected charge | $10.00 (full renewal, no proration) |
| D_original / new date | S1's `_next_payment_date` at click time / D_original + 24h exactly |

## Steps
1. Dump `_next_payment_date,_completed_payments,_recurring_amount,_renewal_sync_enabled,_pending_renewal_order_id` for S1; record D_original; compute k and verify now < D_original + k - 6h, else defer a cycle.
2. Screenshot `tools.php?page=action-scheduler&s=S1&status=pending`. `PREV=$(mailpit-agent latest-id)`.
3. `agent-browser --session life02 open` the portal, log in; screenshot the **Early Renewal:** notice naming D_original and the resulting date.
4. Click **Renew Early**; screenshot the dialog (must state $10.00, D_original and the new date); click **Renew Now**; screenshot the success state and the console/network output of `/my-subscriptions/S1/early-renew`.
5. Record order OE; repeat the step-1 dump; read OE's `_arraysubs_early_renewal`, `_renewal_scheduled_date`, `_renewal_cycle_number` and the note naming D_original.
6. Re-screenshot the pending actions; `wait-new "$PREV" 120 "Payment received"`, then `list 20`; re-open the portal and record whether Renew Early is offered again or blocked, and why.
7. Paddle negative: screenshot the `slt-paddle` portal page showing no early-renewal control; if no such subscription exists, mark UNVERIFIED.
8. Registry note: "S1 cycle N was paid early by SLT-LIFE-02 on 2026-08-08", so the watch does not read the missing unattended renewal as a failure.

## Expected results
1. The portal shows the early-renewal notice and an enabled **Renew Early** button (baseline on, status active, Stripe `early_renewal => true`).
2. OE total is exactly $10.00 - no proration, no discount, no tax line.
3. OE carries `_arraysubs_early_renewal = yes`, anchor `_renewal_scheduled_date` = D_original and a note naming it; reaches `processing`; `_completed_payments` +1.
4. `_next_payment_date` = D_original + 24h EXACTLY, NOT click_time + 24h - paying early does not shorten the paid-through period.
5. Old legs gone; one invoice and one charge leg remain, at (D_original+24h)+k-6h and +k, k unchanged.
6. The Paddle subscription shows no early-renewal UI at all.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | payment_successful + WC new order/processing | early charge succeeds, OE -> processing | owner + admin | `Payment received for subscription #S1`, `#OE` | `wait-new $PREV 120`, `list 20` |
| 2 | NONE EXPECTED: renewal_invoice (Stripe suppression, EmailManager.php:504-510), new_subscription, renewal_reminder (1-day cycle) | — | — | `Invoice for subscription`, `is active`, `renews soon` | absent in `list 30` |

## Evidence to capture
- Screenshots `SLT-LIFE-02-01-notice.png`, `-02-dialog.png`, `-03-success.png`, `-04-pending-before.png`, `-05-pending-after.png`, `-06-paddle-no-button.png`.
- S1, OE, D_original, k, meta dumps, order meta + note, Mailpit IDs.

## Pass criteria
- [ ] Renew Early offered; dialog states the amount and both dates
- [ ] Charge exactly $10.00, no proration or discount
- [ ] `_next_payment_date` = D_original + 1 cycle, not payment time + 1 cycle
- [ ] OE anchored to D_original; one invoice + one charge leg at the new date + same k
- [ ] Exactly the 2 email rows, negatives included
- [ ] Paddle shows no early-renewal control (or marked UNVERIFIED)

## Isolation / teardown
- S1 keeps its daily grid, one cycle further on; the daily watch must be told (step 8) that this cycle was paid manually.
- Nothing to restore - `allow_early_renew` is baseline, reverted by SLT-SETUP-99A.


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
