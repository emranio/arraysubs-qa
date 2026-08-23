---
id: 69
title: 'Concurrent renewals in one Action Scheduler window: no skips, no double charges, offsets stagger'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - edge-cases
    - day-04
due: "2026-08-27"
estimate: 2h on D4 + 45m follow-up on D5
depends_on:
    - 11
    - 12
    - 5
class: standard
---

> **SLT-IMP-03** · group `implied` · scheduled **D04** (2026-08-27)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Make several SLT2 subscriptions fall due in one Action Scheduler window and prove all renew, none is skipped, none double-charges, the locks hold, and the crc32 spread offset really staggers the charge legs.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered x3 (created by this task)
- Plugins: both

## Preconditions
- SLT-PROD-01 done; `SLT2 Daily Core` ($10.00 day/1) published; SLT-SETUP-02 baseline held.
- ALSO CREATES Customer accounts `slt2-conc1/2/3@example.test`, pw `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4; `slt2-*`, so SLT-SETUP-99B clears them.
- The three purchases run back-to-back in ONE 10-min window after 13:00 site on 2026-08-27 (D4). Each account buys once (C08).
- Sessions `admin-SLT-IMP-03`, `customer-conc1-SLT-IMP-03`, `customer-conc2-SLT-IMP-03`, and `customer-conc3-SLT-IMP-03` (C09). NO `wp action-scheduler run`: renewals fire unattended; one that does not is a genuine bug.

## Test data
| Item | Value |
|---|---|
| Product / buyers | SLT2 Daily Core $10.00 day/1 / slt2-conc1,2,3 |
| Card | 4242 4242 4242 4242 |
| Window T | 2026-08-27, 10-min slot after 13:00 site |
| Legs | due `T+24h`; charge `+k_i`, invoice `+k_i-6h`; `k_i = crc32('arraysubs-spread-'.$id_i) % 21600` |

## Steps
1. Record `SUBCOUNT_BEFORE=<exact current SLT2 subscription count>` and `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-IMP-03`, create the three users (untick **Send User Notification**), set billing, record all numeric user IDs, classify exactly three admin-addressed `New User Registration` messages after `USER_PRE`, and prove there is no customer account/password message.
2. After setup mail is fully classified, record the window's UTC start and set `BUY_PRE_1=$(mailpit-agent latest-id)`.
3. Run one purchase per named customer session inside the same 10-minute window. For each buyer: log in, require browser/persistent carts empty, add only SLT2 Daily Core, accept the one-click block-checkout redirect, capture the $10.00 summary before card entry as `SLT-IMP-03-00-<concN>-checkout.png`, fill the hosted card without capturing it, pay, record numeric parent order/click time, and capture `SLT-IMP-03-00-<concN>-receipt.png`. Resolve its sole subscription from `wp post meta get "$ORDER_N" _subscription_ids --format=json --allow-root` through a strict one-element numeric `jq -e` guard; require reverse parent/customer/product and cumulative count `SUBCOUNT_BEFORE+N`. For each separate `BUY_PRE_N`, require the exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs, then prove both carts empty before setting the next baseline. Never reuse a baseline.
4. Assign numeric `SUB1/SUB2/SUB3`; compute offsets with the documented crc32 command. Query/publish every exact invoice/charge action ID/time plus each `charge−5m` baseline deadline. If two offsets legitimately collide, record that deterministic fixture fact and evaluate concurrency at the shared timestamp; uniqueness itself is not a product assertion. No earlier than five minutes before each exact charge gate, store its `REN_PRE_N`.
5. Enumerate the natural cohort with `wp post list --post_type=arraysubs_data --post_status=arraysubs-active --fields=ID --allow-root`: every SLT2 sub due 2026-08-28.
6. In `admin-SLT-IMP-03`, open `admin.php?page=wc-status&tab=action-scheduler&status=pending&s=arraysubs_process_renewal`, capture the exact three rows as `SLT-IMP-03-01-pending.png`, then close all four exact D4 sessions and keep the card `in-progress`.
7. **Follow-up 2026-08-28 (D5):** reopen `admin-SLT-IMP-03`, capture exact completed rows as `SLT-IMP-03-02-complete.png` and failed search as `SLT-IMP-03-03-failed.png`; dump next/last/payment counts for all three.
8. Resolve exactly one renewal order per subscription through exact scheduled-cycle and reverse relationships, never customer/date recency; capture them as `SLT-IMP-03-04-orders.png`. Run one exact subject wait per subscription against its own `REN_PRE_N`, reconcile all three deltas, and use `rg` over runtime `debug.log`/dated Woo logs for the exact IDs plus `already_processing|lock|Fatal`. Any actual skip/double-charge/failure becomes a dedicated issue with this task/plan, all subscription/parent/renewal/action/user IDs and contexts, reproduction/timeline, expected/actual, action/order/mail/log proof and successful cohort counterexamples; create or update the mandatory `qa/issues/` kanban card. Close `admin-SLT-IMP-03`, independently review D4/D5 evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. `k_1..k_3` are pairwise different and the three `arraysubs_process_renewal` actions carry three different timestamps despite due dates within 10 min.
2. All three, plus every cohort subscription due 2026-08-28, end `complete` on both `arraysubs_generate_renewal_invoice` and `arraysubs_process_renewal`.
3. `status=failed` gains **zero** rows referencing an SLT2 subscription.
4. Exactly ONE renewal order per subscription per cycle, `processing`/`completed`, `$10.00`; no two renewal orders share a `_renewal_cycle_number`.
5. `_completed_payments` +1 each; `_next_payment_date` advances exactly 24 h from `_renewal_scheduled_date`, not charge time — `k` must not accumulate.
6. Exactly one `Payment received for subscription #<id>` each — the `_arraysubs_renewal_payment_success_email_sent` guard holds. An action requeued at `now+2min` on lock contention still completes; one left `pending` is a failure.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 0 | WP New User Registration x3 | setup before `BUY_PRE_1` | admin only | `New User Registration` | exactly three after `USER_PRE`; zero customer account/password mail |
| 1-3 | new_subscription x3 | each checkout | slt2-conc1/2/3 | `is active` | separate `BUY_PRE_1`/`BUY_PRE_2`/`BUY_PRE_3`, each timeout 180 |
| 4-6 | admin_new_subscription x3 | each checkout | admin_email | `New subscription #` | Separate complete buyer deltas; save/show each exact ID |
| 4a-6a | WC paid-order + WC New order x3 | each checkout | buyer + admin | exact order / `New order #` | Separate complete buyer deltas; save/show six exact IDs |
| 7-9 | payment_successful x3 | `due+k_i` on D5 | each buyer | `Payment received for subscription #<id>` | separate `REN_PRE_1`/`REN_PRE_2`/`REN_PRE_3`, each timeout 900 and exact subject |

No `Invoice for subscription #` expected (suppressed for automatic-payment subs, SLT-REF-10 L23) — negative check on D5.

## Evidence to capture
- Three safe checkout/receipt pairs plus `SLT-IMP-03-01-pending.png`, `-02-complete.png`, `-03-failed.png`, `-04-orders.png`; cumulative count/linkage, all action/order/sub/user IDs, offsets/cohort, setup and twelve checkout-mail IDs, three renewal baselines/mail IDs, carts/session/review proof, runtime `rg` output.

## Pass criteria
- [ ] three distinct offsets and three distinct scheduled timestamps
- [ ] every leg completes; zero SLT2 rows under `status=failed`
- [ ] exactly one renewal order per subscription per cycle at $10.00
- [ ] `_completed_payments` +1 each, next due exactly +24 h
- [ ] exactly one payment_successful email per subscription
- [ ] exactly three admin-only setup mails, no customer account/password mail; all three carts and persistent-cart metas empty before and after
- [ ] no fatal or lock-timeout line in the window
- [ ] Exact future gates handed off, sessions closed per phase, and execution reviewed to done

## Isolation / teardown
- Adds three SLT2 Daily Core subscriptions renewing daily until SLT-SETUP-99A cancels them; register the ids for the D5-D9 watch.
- Creates slt2-conc1..3; nothing global changes. Empty each cart, close each session.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
