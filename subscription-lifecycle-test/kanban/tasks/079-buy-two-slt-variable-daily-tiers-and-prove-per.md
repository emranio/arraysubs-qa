---
id: 79
title: Buy two SLT Variable Daily tiers and prove per-variation config lands on the subscription
status: done
priority: high
created: 2026-08-02T03:43:09.833133473+02:00
updated: 2026-08-07T19:06:32.092057938+02:00
started: 2026-08-07T19:06:32.092056926+02:00
completed: 2026-08-07T19:06:32.092056926+02:00
tags:
    - checkout
    - day-05
due: "2026-08-07"
estimate: 2h
depends_on:
    - 71
    - 10
    - 11
    - 12
claimed_by: plume-coal
claimed_at: 2026-08-07T19:06:32.092057837+02:00
class: standard
---

> **SLT-CHK-11** · group `checkout` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy two `SLT Variable Daily` tiers — Starter (day/1, $6.00) on block checkout, Plus (day/2, $11.00 + $4.00 signup fee) on the classic harness page — and prove each variation's period, interval, price and fee land verbatim on its own subscription. Trialist and Zero Probe get cart previews only.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: existing
- Plugins: free-only

## Preconditions
- `SLT-PROD-08` complete; quote the four variation IDs from `slt-catalog-registry`.
- `SLT-SETUP-01` (classic harness pages), `SLT-SETUP-02`, `SLT-SETUP-03` (`slt-core` + billing address).
- `one_per_customer=false`, so auto-migrate (`CartValidationTrait.php:140-148`) is unreachable: two tiers of one parent give two independent subs.
- D5 sessions `core-CHK11-SLT-CHK-11` and `admin-SLT-CHK-11`; browser and persistent carts empty before, between, and after purchases. Future renewals use separate cycle-keyed admin sessions.

## Test data
| Item | Value |
|---|---|
| Product | SLT Variable Daily (`slt-variable-daily`), attr `SLT Tier` |
| Account | slt-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Starter | $6.00 today; day/1; next payment 2026-08-08 |
| Plus | $11.00 + fee $4.00 = **$15.00** today; day/2; next payment 2026-08-09; renewal $11.00, no fee |
| Trialist / Zero Probe | preview only |

## Steps
1. Resolve strict numeric parent plus all four variation IDs from the registry, require them distinct, and cross-check exact labels/prices/schedules. Record `SUBCOUNT_BEFORE` and `PREVIEW_PRE=$(mailpit-agent latest-id)`.
2. In `core-CHK11-SLT-CHK-11`, log in as `slt-core`, require both carts empty, and capture `SLT-CHK-11-00-cart-empty-before.png`.
3. Open `/product/slt-variable-daily/`; select each exact variation and capture its price/subscription summary as `SLT-CHK-11-01-tier-starter.png` through `-04-tier-zero-probe.png`.
4. Add exact **Trialist**; handle a one-click redirect by explicitly reopening `/cart/`, capture `SLT-CHK-11-05-trialist-cart.png`, and remove it. Repeat for exact **Zero Probe**, capture `SLT-CHK-11-06-zero-probe-cart.png`, and record its behavior/notice verbatim. Prove both carts empty, reconcile the complete `PREVIEW_PRE` delta with zero preview-attributable mail, then set `PRE_STARTER=$(mailpit-agent latest-id)`.
5. Add exact **Starter**, handle one-click, open `/checkout/`, and capture the unpopulated $6.00/no-fee summary as `SLT-CHK-11-07-block-starter.png`. Fill the hosted 4242 card without capturing it, pay, record numeric `ORDER_STARTER`, and capture safe receipt `SLT-CHK-11-07a-starter-receipt.png`. Resolve `SUB_STARTER` only from that order's `_subscription_ids` JSON with a strict one-element numeric guard; require reverse parent/variation/customer linkage and `SUBCOUNT_AFTER_STARTER == SUBCOUNT_BEFORE+1`. Reconcile the complete `PRE_STARTER` four-message delta: WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup.
6. Prove both carts empty, set `PRE_PLUS=$(mailpit-agent latest-id)`, and add exact **Plus**. If one-click redirects, explicitly reopen `/slt-classic-cart`; capture the $4 fee/$15 total as `SLT-CHK-11-08-classic-cart-fee.png`, then open `/slt-classic-checkout` and capture its unpopulated summary as `-08a-classic-checkout.png`. Fill the hosted card without capturing it, pay, record numeric `ORDER_PLUS`, and capture `-08b-plus-receipt.png`. Resolve `SUB_PLUS` by the same strict relationship, require it distinct and `SUBCOUNT_AFTER_PLUS == SUBCOUNT_BEFORE+2`, and reconcile the second complete four-message delta after `PRE_PLUS`.
7. Run the exact meta command separately for numeric `SUB_STARTER` and `SUB_PLUS`; require the recorded parent/variation IDs and values, never a literal placeholder.
8. Compute each k with the README argv command and its numeric sub; derive invoice/charge gates and final-five-minute baseline deadlines.
9. In `admin-SLT-CHK-11`, capture exact pending invoice/charge rows for both numeric IDs as `SLT-CHK-11-09-scheduled-actions.png`, compare with step 8, and publish IDs/gates/deadlines to the registry/D05 report.
10. Prove both carts empty, capture `SLT-CHK-11-10-cart-empty-after.png`, close only the two D5 sessions, and leave the card `in-progress`.
11. Watch naturally: take `STARTER_R1_PRE` only in `[Starter charge gate−300s, gate)` on 08-08 and `PLUS_R1_PRE` only in the corresponding final-five-minute interval on 08-09. Never force either action. Resolve each renewal order by exact subscription/cycle and reverse meta, reconcile its complete mail delta, and require Starter $6.00 and Plus $11.00 with no signup-fee line. Use/close `admin-SLT-CHK-11-STARTER-R1` and `admin-SLT-CHK-11-PLUS-R1` only for their phases.
12. If any live assertion fails, create a standalone `issues/SLT-CHK-11-<concise-slug>.md` (never a kanban bug card) with task/stage/plan, parent/variation/order/subscription/action IDs, user ID/login/email/role, exact URLs/sessions, reproduction, expected/actual, UI/meta/scheduler/Mailpit/screenshot proof, and the other variation as counterexample. Continue unaffected legs. After both renewals, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Two orders `processing`/`completed` at $6.00 and $15.00.
2. Starter sub: `_variation_id`=Starter ID, `_product_id`=parent ID, `_billing_period=day`, `_billing_interval=1`, `_recurring_amount=6.00`, `_signup_fee` empty/0, `_trial_length=0`, `arraysubs-active`, `_next_payment_date` 2026-08-08 at checkout clock time.
3. Plus sub: `_billing_interval=2`, `_recurring_amount=11.00`, `_signup_fee=4.00`, `_next_payment_date` 2026-08-09.
4. The two `_variation_id` values differ; neither sub inherited the other's config.
5. Four distinct front-end summaries: $6.00/day; $11.00/2 days + $4.00 fee; $9.00/day after a 3-day trial; Zero Probe.
6. Trialist cart total $0.00; Zero Probe behaviour recorded.
7. Both legs pending at the step-8 timestamps, not at the bare `_next_payment_date`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC customer paid-order + WC New order + `new_subscription` + `admin_new_subscription` | Starter paid | slt-core / admin | order id / `New order #` / `is active` / `New subscription #` | complete owner-filtered delta after `PRE_STARTER`; save/show all four exact IDs |
| 2 | Same complete four-message set | Plus paid | slt-core / admin | exact Plus order/subscription | complete owner-filtered delta after `PRE_PLUS`; save/show all four exact IDs |
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
- Two live subs to the watch (Starter daily from 08-08, Plus every 2 days from 08-09); cancelled by `SLT-SETUP-99A` on D10.
- Nothing global changed; cart emptied; only the exact D5 and renewal-phase task sessions closed. Trialist left unpurchased.

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

[[2026-08-06]] Thu 20:27
Source-block note on Thursday, August 6, 2026: D4 source card 71 is now done only because it failed and filed qa/subscription-lifecycle-test/issues/done-critical-plugin-SLT-PROD-08-variable-subscription-draft-is-trashed-on-save.md. Intended parent 13012 and child variations 13013/13015/13017/13019 landed in trash, so this card remains blocked until a usable SLT Variable Daily fixture exists.

[[2026-08-07]] Fri 23:05
Final D05 read confirmed the source remains absent: parent 13012 and variations 13013/13015/13017/13019 are all still trash. No later valid replan recreated them. Execution closes UNVERIFIED without opening a browser/cart/checkout or inventing replacement IDs. Evidence: `/home/server-manager/slt-evidence/D05-night-source-block-and-window-close.txt`; originating issue is now resolved at `issues/done-critical-plugin-SLT-PROD-08-variable-subscription-draft-is-trashed-on-save.md`.
