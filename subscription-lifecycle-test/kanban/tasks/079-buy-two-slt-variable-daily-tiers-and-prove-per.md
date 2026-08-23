---
id: 79
title: Buy two SLT2 Variable Daily tiers and prove per-variation config lands on the subscription
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-05
due: "2026-08-28"
estimate: 2h
depends_on:
    - 71
    - 10
    - 11
    - 12
claimed_by: plume-coal
class: standard
---

> **SLT-CHK-11** · group `checkout` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy two `SLT2 Variable Daily` tiers — Starter (day/1, $6.00) on block checkout, Plus (day/2, $11.00 + $4.00 signup fee) on the classic harness page — and prove each variation's period, interval, price and fee land verbatim on its own subscription. Trialist and Zero Probe get cart previews only.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: existing
- Plugins: free-only

## Preconditions
- `SLT-PROD-08` complete; quote the four variation IDs from `slt2-catalog-registry`.
- `SLT-SETUP-01` (classic harness pages), `SLT-SETUP-02`, `SLT-SETUP-03` (`slt2-core` + billing address).
- `one_per_customer=false`, so auto-migrate (`CartValidationTrait.php:140-148`) is unreachable: two tiers of one parent give two independent subs.
- D5 sessions `core-CHK11-SLT-CHK-11` and `admin-SLT-CHK-11`; browser and persistent carts empty before, between, and after purchases. Future renewals use separate cycle-keyed admin sessions.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Variable Daily (`slt2-variable-daily`), attr `SLT2 Tier` |
| Account | slt2-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Starter | $6.00 today; day/1; next payment 2026-08-29 |
| Plus | $11.00 + fee $4.00 = **$15.00** today; day/2; next payment 2026-08-30; renewal $11.00, no fee |
| Trialist / Zero Probe | preview only |

## Steps
1. Resolve strict numeric parent plus all four variation IDs from the registry, require them distinct, and cross-check exact labels/prices/schedules. Record `SUBCOUNT_BEFORE` and `PREVIEW_PRE=$(mailpit-agent latest-id)`.
2. In `core-CHK11-SLT-CHK-11`, log in as `slt2-core`, require both carts empty, and capture `SLT-CHK-11-00-cart-empty-before.png`.
3. Open `/product/slt2-variable-daily/`; select each exact variation and capture its price/subscription summary as `SLT-CHK-11-01-tier-starter.png` through `-04-tier-zero-probe.png`.
4. Add exact **Trialist**; handle a one-click redirect by explicitly reopening `/cart/`, capture `SLT-CHK-11-05-trialist-cart.png`, and remove it. Repeat for exact **Zero Probe**, capture `SLT-CHK-11-06-zero-probe-cart.png`, and record its behavior/notice verbatim. Prove both carts empty, reconcile the complete `PREVIEW_PRE` delta with zero preview-attributable mail, then set `PRE_STARTER=$(mailpit-agent latest-id)`.
5. Add exact **Starter**, handle one-click, open `/checkout/`, and capture the unpopulated $6.00/no-fee summary as `SLT-CHK-11-07-block-starter.png`. Fill the hosted 4242 card without capturing it, pay, record numeric `ORDER_STARTER`, and capture safe receipt `SLT-CHK-11-07a-starter-receipt.png`. Resolve `SUB_STARTER` only from that order's `_subscription_ids` JSON with a strict one-element numeric guard; require reverse parent/variation/customer linkage and `SUBCOUNT_AFTER_STARTER == SUBCOUNT_BEFORE+1`. Reconcile the complete `PRE_STARTER` four-message delta: WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup.
6. Prove both carts empty, set `PRE_PLUS=$(mailpit-agent latest-id)`, and add exact **Plus**. If one-click redirects, explicitly reopen `/slt2-classic-cart`; capture the $4 fee/$15 total as `SLT-CHK-11-08-classic-cart-fee.png`, then open `/slt2-classic-checkout` and capture its unpopulated summary as `-08a-classic-checkout.png`. Fill the hosted card without capturing it, pay, record numeric `ORDER_PLUS`, and capture `-08b-plus-receipt.png`. Resolve `SUB_PLUS` by the same strict relationship, require it distinct and `SUBCOUNT_AFTER_PLUS == SUBCOUNT_BEFORE+2`, and reconcile the second complete four-message delta after `PRE_PLUS`.
7. Run the exact meta command separately for numeric `SUB_STARTER` and `SUB_PLUS`; require the recorded parent/variation IDs and values, never a literal placeholder.
8. Compute each k with the README argv command and its numeric sub; derive invoice/charge gates and final-five-minute baseline deadlines.
9. In `admin-SLT-CHK-11`, capture exact pending invoice/charge rows for both numeric IDs as `SLT-CHK-11-09-scheduled-actions.png`, compare with step 8, and publish IDs/gates/deadlines to the registry/D05 report.
10. Prove both carts empty, capture `SLT-CHK-11-10-cart-empty-after.png`, close only the two D5 sessions, and leave the card `in-progress`.
11. Watch naturally: take `STARTER_R1_PRE` only in `[Starter charge gate−300s, gate)` on 08-29 and `PLUS_R1_PRE` only in the corresponding final-five-minute interval on 08-30. Never force either action. Resolve each renewal order by exact subscription/cycle and reverse meta, reconcile its complete mail delta, and require Starter $6.00 and Plus $11.00 with no signup-fee line. Use/close `admin-SLT-CHK-11-STARTER-R1` and `admin-SLT-CHK-11-PLUS-R1` only for their phases.
12. If any live assertion fails, create a dedicated `qa/issues/` kanban card named `SLT-CHK-11-<concise-slug>` (create the required QA issue card) with task/stage/plan, parent/variation/order/subscription/action IDs, user ID/login/email/role, exact URLs/sessions, reproduction, expected/actual, UI/meta/scheduler/Mailpit/screenshot proof, and the other variation as counterexample. Continue unaffected legs. After both renewals, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Two orders `processing`/`completed` at $6.00 and $15.00.
2. Starter sub: `_variation_id`=Starter ID, `_product_id`=parent ID, `_billing_period=day`, `_billing_interval=1`, `_recurring_amount=6.00`, `_signup_fee` empty/0, `_trial_length=0`, `arraysubs-active`, `_next_payment_date` 2026-08-29 at checkout clock time.
3. Plus sub: `_billing_interval=2`, `_recurring_amount=11.00`, `_signup_fee=4.00`, `_next_payment_date` 2026-08-30.
4. The two `_variation_id` values differ; neither sub inherited the other's config.
5. Four distinct front-end summaries: $6.00/day; $11.00/2 days + $4.00 fee; $9.00/day after a 3-day trial; Zero Probe.
6. Trialist cart total $0.00; Zero Probe behaviour recorded.
7. Both legs pending at the step-8 timestamps, not at the bare `_next_payment_date`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC customer paid-order + WC New order + `new_subscription` + `admin_new_subscription` | Starter paid | slt2-core / admin | order id / `New order #` / `is active` / `New subscription #` | complete owner-filtered delta after `PRE_STARTER`; save/show all four exact IDs |
| 2 | Same complete four-message set | Plus paid | slt2-core / admin | exact Plus order/subscription | complete owner-filtered delta after `PRE_PLUS`; save/show all four exact IDs |
| 3 | NONE EXPECTED | step 4 previews | — | — | Complete preview-step delta; zero preview-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Safe named `SLT-CHK-11-00` through `-10` captures; no populated-card image. Count progression; numeric parent/variation/order/subscription/action IDs and bidirectional linkage; meta dumps, offsets/gates/deadlines, preview/purchase/renewal baselines and exact Mailpit IDs; console/network plus session/review proof.

## Pass criteria
- [ ] Orders placed at exactly $6.00 and $15.00
- [ ] Per-variation period, interval, price, fee land on the matching sub
- [ ] `_variation_id` correct and distinct on both
- [ ] Fee charged once on Plus, absent on Starter
- [ ] Trialist preview $0.00; Zero Probe recorded
- [ ] Renewal legs at the offset-adjusted times
- [ ] Both four-message purchase sets captured; negative preview row 3 holds
- [ ] Both natural renewals are relationship-exact, exact sessions closed, and final evidence reviewed to done with Review empty

## Isolation / teardown
- Two live subs to the watch (Starter daily from 08-29, Plus every 2 days from 08-30); cancelled by `SLT-SETUP-99A` on D11.
- Nothing global changed; cart emptied; only the exact D5 and renewal-phase task sessions closed. Trialist left unpurchased.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
