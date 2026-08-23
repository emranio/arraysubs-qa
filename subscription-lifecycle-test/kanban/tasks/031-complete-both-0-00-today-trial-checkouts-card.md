---
id: 31
title: 'Complete both $0.00-today trial checkouts: card still collected, first real charge scheduled'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-02
due: "2026-08-25"
estimate: 1h 30m
depends_on:
    - 37
    - 38
    - 10
    - 12
    - 11
class: standard
---

> **SLT-CHK-15** · group `checkout` · scheduled **D02** (2026-08-25)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Run both $0.00-today checkouts as `slt2-trial`: `SLT2 Free Signup Daily` (2-day trial, $8.00) on block checkout, `SLT2 Trial Four Day` (4-day trial, $12.00) on the classic harness page. Prove `require_payment_method=true` still collects and stores a card on a zero-total order, the sub opens `arraysubs-trial`, and the first real charge lands at trial end + offset.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: existing
- Plugins: free-only

## Preconditions
- `SLT-PROD-02`, `SLT-PROD-03` complete; neither has a signup fee (a fee forces payment). Quote both IDs from the registry.
- `SLT-SETUP-01` (harness pages), `SLT-SETUP-02`, `SLT-SETUP-03` (`slt2-trial` + address). Buy A first, then B (`SLT-PROD-03` handoff).
- The trial reminder is `RenewalReminderEmail` in trial context on `renewal_upcoming.days_before=3`; `trial_ending.days_before` is inert (REF-04 B3).
- **Execute after 12:00 site time**. Sessions `trial-CHK15-SLT-CHK-15` and `admin-SLT-CHK-15`; cart empty first/last.

## Test data
| Item | Value |
|---|---|
| A | SLT2 Free Signup Daily — $0.00 today, 2-day trial, then $8.00/day |
| B | SLT2 Trial Four Day — $0.00 today, 4-day trial, then $12.00/day |
| Account | slt2-trial / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Dates | A: logical trial end 08-27, charge at its recorded `trial_end+k` gate. B: logical trial end 08-29, charge at `trial_end+k`; reminder action at `trial_end−3d+k` |

## Steps
1. `PREV1=$(mailpit-agent latest-id)` and `SUBCOUNT_PRE=<exact current SLT2 subscription count>`.
2. `agent-browser --session trial-CHK15-SLT-CHK-15 open "https://mirror-help.arrayhash.com/my-account/"` -> log in `slt2-trial`; open `/cart/`, prove it is EMPTY, and capture `SLT-CHK-15-00-cart-empty-before.png`.
3. Add **SLT2 Free Signup Daily**. The frozen `checkout.one_click_mode=subscription_items` may redirect directly to block checkout; record the zero-due summary there, then explicitly reopen `/cart/` and `snapshot -i`: $0.00, no fee row, trial summary. Capture `SLT-CHK-15-01-cart-a.png`.
4. `/checkout/` -> `snapshot -i`. **Negative probe first**: leave card fields empty, **Place Order**, capture `SLT-CHK-15-02-no-card-refused.png`, and quote the blocking message.
5. Enter the Stripe test card only inside its hosted fields and **Place Order**. Do not save any screenshot while a full card number is visible. On order-received capture `SLT-CHK-15-03-received-a.png` and record numeric order `ORDER_A`. Read `wp post meta get "$ORDER_A" _subscription_ids --format=json --allow-root`, resolve exactly one numeric `SUB_A` through a strict `jq -e` guard, and cross-check reverse parent/customer/product plus `SUBCOUNT_AFTER_A == SUBCOUNT_PRE + 1`; never use the WooCommerce order meta accessor or recency. `mailpit-agent wait-new "$PREV1" 180 "free trial for"`; inspect the complete PREV1 delta, save the exact trial-started ID, and classify every Woo/background message before continuing.
6. Set `PREV2=$(mailpit-agent latest-id)` only after A's delta is fully classified. Reopen `/cart/` and prove it is EMPTY after A; add **SLT2 Trial Four Day**. If one-click redirects to block checkout, record the $0.00 summary, then explicitly reopen `/slt2-classic-cart`, verify $0.00, and capture `SLT-CHK-15-04-classic-cart-b.png`. Open `/slt2-classic-checkout`, verify the unpopulated $0.00 form and capture `SLT-CHK-15-05-classic-checkout-b.png`; leave the card fields empty and click **Place Order** first, then capture `SLT-CHK-15-05a-classic-no-card-refused.png` and quote the blocking message. Fill the hosted card fields and click the current **Place Order** ref without capturing the populated frame. On order-received capture `SLT-CHK-15-06-received-b.png`, record numeric `ORDER_B`, resolve exact numeric `S_TR` through the same post-meta JSON path and strict guard, and cross-check reverse parent/customer/product plus `SUBCOUNT_AFTER_B == SUBCOUNT_PRE + 2`. `mailpit-agent wait-new "$PREV2" 180 "SLT2 Trial Four Day"`; inspect and classify the complete PREV2 delta before continuing.
7. Both subs: `wp post meta list <SUB_ID> --keys=_billing_period,_billing_interval,_recurring_amount,_signup_fee,_trial_length,_trial_end_date,_next_payment_date,_payment_method,_renewal_reminder_action_id --allow-root`.
8. Confirm a reusable method stored: `_payment_method=stripe` + the order's Stripe token meta.
9. Per sub compute `k = crc32('arraysubs-spread-'.SUBID) % 21600`; invoice `trial_end+k−6h`, charge `trial_end+k`. In isolated `admin-SLT-CHK-15`, verify both rows for each exact numeric ID in Scheduled Actions.
10. Append both user/order/subscription IDs, cumulative count deltas, checkout timestamps, offsets, exact action IDs/times, each reminder/charge `gate−5m` deadline, and trial-started Mailpit IDs to the registry and D02 watch report. Mark the `SLT2 Trial Four Day` subscription explicitly as `S_TR`, the sole hand-off consumed by `SLT-EML-09`; that task must place no order and intentionally does not wait for this parent card to reach `done`.
11. Empty cart, prove the persistent-cart meta empty, and close only `trial-CHK15-SLT-CHK-15` and `admin-SLT-CHK-15`.
12. Leave this card `in-progress` after the D2 checkout evidence is complete. Watch handoffs: at B's exact recorded `trial_end−3d+k` reminder gate (normally 08-26 but allowed to cross local midnight), the action may run but must send no mail while status is trial. At the final phase at least five minutes before A's exact `trial_end+k` charge gate, publish `TRIAL_A_CHARGE_PRE=$(mailpit-agent latest-id)` with A's action id/GMT to the registry. That gate may fall late 08-27 or shortly after midnight 08-28; the D5 settled read must inspect the complete owner delta, save/show the exact paid-transition mail, and cite the $8.00 order/activation evidence. Require `payment_successful` when the renewal path activates A; require `trial_converted` only if A is still trial after the gate and the fallback bulk converter activates it. `SLT-EML-09` owns B's exact `trial_end+k` observation on 08-29/09 and cites it back here. That rider closes both cards only after both paid-transition criteria are evidenced.

## Expected results
1. Both orders total exactly **$0.00**, no tax or fee line, `processing`/`completed`.
2. Step-4 probe: refused without a card, message quoted. If it succeeds, that contradicts `require_payment_method=true` and is filed.
3. Both subs open `arraysubs-trial`, not `arraysubs-active`.
4. A: `_trial_length=2`, `_trial_period=day`, `_trial_end_date` 08-27, `_recurring_amount=8.00`, `_billing_interval=1`, `_signup_fee` empty/0. B: `_trial_length=4`, `_trial_end_date` 08-29, `_recurring_amount=12.00`. On both `_next_payment_date` = trial end.
5. `_payment_method=stripe` on both with a stored Stripe token, so off-session charging works at trial end.
6. Both renewal legs pending at the step-9 times.
7. B has `_renewal_reminder_action_id` due 08-26 (trial end − 3 days); A has none, its due point being past at checkout — that suppression is A's purpose.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | trial_started (A) | A paid | slt2-trial@example.test | `free trial for SLT2 Free Signup Daily` | `mailpit-agent wait-new "$PREV1" 180 "free trial"` |
| 2 | trial_started (B) | B paid | slt2-trial@example.test | `free trial for SLT2 Trial Four Day` | `mailpit-agent wait-new "$PREV2" 180 "SLT2 Trial Four Day"` |
| 2a | WooCommerce order mail | each zero-due checkout | actual configured recipient(s) | order id/product | classify every message in the separate PREV1/PREV2 deltas; record presence/absence and exact ids without counting them as ArraySubs signup mail |
| 3 | NONE EXPECTED — new_subscription | either order | — | — | No `is active` to slt2-trial today; that is conversion day |
| 4 | NONE EXPECTED — reminder for A | ever | — | — | 2-day trial vs 3-day lead: no `ends soon` for product A |
| 5 | paid-transition mail for A | exact `trial_end+k` path | slt2-trial@example.test | exact A subscription id | complete delta after `TRIAL_A_CHARGE_PRE`; `payment_successful` on renewal activation, `trial_converted` only on fallback conversion |

## Evidence to capture
- `SLT-CHK-15-00-cart-empty-before.png`, `-01-cart-a.png`, `-02-no-card-refused.png`, `-03-received-a.png`, `-04-classic-cart-b.png`, `-05-classic-checkout-b.png`, `-05a-classic-no-card-refused.png`, `-06-received-b.png`. No evidence image may contain a full card number.
- User/order/sub IDs, cumulative count deltas, meta dumps, offsets, exact action/gate handoff, no-card messages, `TRIAL_A_CHARGE_PRE`, exact paid-transition Mailpit ID, console/network errors.

## Pass criteria
- [ ] Both orders total exactly $0.00, no fee line
- [ ] Checkout refuses a $0.00 order with no card (block + classic)
- [ ] Both subs in `arraysubs-trial` with the right `_trial_end_date`
- [ ] Both receipt orders resolve exactly one relationship-linked subscription; separate complete PREV1/PREV2 mail deltas classified with no `new_subscription`
- [ ] Cumulative subscription counts are +1/+2 and both action/deadline sets are published before D2 session teardown
- [ ] A payment method stored on both
- [ ] First charges at each recorded `trial_end+k` gate ($8.00 and $12.00), with the observed site date recorded rather than assumed
- [ ] Reminder action exists for B, not for A
- [ ] Emails 1-2 captured; negatives 3-4 hold

## Isolation / teardown
- Two live trial subs for the watch; cancelled by `SLT-SETUP-99A` on D11.
- Nothing global changed; cart and persistent-cart meta emptied; `trial-CHK15-SLT-CHK-15` and `admin-SLT-CHK-15` closed. `SLT-EML-09` is an observation rider on `S_TR`, not a second purchaser.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
