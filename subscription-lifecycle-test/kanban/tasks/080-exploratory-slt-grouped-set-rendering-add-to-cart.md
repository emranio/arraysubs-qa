---
id: 80
title: 'EXPLORATORY: SLT2 Grouped Set rendering, add-to-cart probes, and one order through the grouped form'
status: todo
priority: medium
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-05
due: "2026-08-28"
estimate: 1h 30m
depends_on:
    - 39
    - 5
    - 58
    - 12
class: standard
---

> **SLT-CHK-12** · group `checkout` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
EXPLORATORY. Grouped products get zero handling in either plugin (the `Subscription [ArraySubs]` checkbox is `show_if_simple show_if_variable`), so a grouped parent can never be a subscription. Document what `SLT2 Grouped Set` renders, whether a subscription child can be bought via the grouped form, and how the order and subscription differ from a direct buy. File what you find; assert no spec.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: admin-created (this task creates `slt2-grouped`)
- Plugins: free-only

## Preconditions
- `SLT-PROD-09` complete: `SLT2 Grouped Set` with children Daily Core, Signup Fee Daily, Grouped Extra; its refusal text is in the registry.
- `SLT-SETUP-02` baseline; `allow_multiple_in_cart=false`, `allow_mixed_cart=true`.
- **Creates one account**: `slt2-grouped` / `slt2-grouped@example.test`, Customer, pw `SltQa!2026#Pass`, billing address per `SLT-SETUP-03` step 4 — `slt2-core` already owns subs for both subscription children and must not rebuy them.
- Sessions `admin-SLT-CHK-12` and `grouped-CHK12-SLT-CHK-12`; browser and persistent carts empty first, between probes, and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Grouped Set (`slt2-grouped-set`) |
| Children | Daily Core $10.00/day; Signup Fee Daily $9.00/day + $15.00 fee; Grouped Extra $3.00 |
| Account | slt2-grouped (created here) |
| Card | 4242 4242 4242 4242 |
| Order | Daily Core x1 + Grouped Extra x1 = **$13.00** |

## Steps
1. Resolve strict numeric, distinct `GROUP_ID`, `DAILY_ID`, `FEE_ID`, and `EXTRA_ID` from the registry/slugs and verify the grouped-child relationship. Record `SUBCOUNT_BEFORE` and `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-CHK-12`, create the user at `/wp-admin/user-new.php` (untick **Send User Notification**), record numeric `USER_ID`/Customer role, then set billing address at its exact `user-edit.php?user_id=$USER_ID`. Classify exactly one admin-addressed `New User Registration` after `USER_PRE` and prove there is no customer account/password mail.
2. After setup mail is classified, `PREV=$(mailpit-agent latest-id)` as the checkout-only baseline.
3. In `grouped-CHK12-SLT-CHK-12`, log in as `slt2-grouped`; require both carts empty and capture `SLT-CHK-12-00-cart-empty-before.png`.
4. Open `/product/slt2-grouped-set/`; capture `SLT-CHK-12-01-grouped-page.png` and record each exact child ID's price, schedule/fee text, and quantity/link control.
5. Probe A: set qty 1 only for `$DAILY_ID` and submit. Handle one-click by explicitly reopening `/cart/`; capture `SLT-CHK-12-02-probe-a.png`, exact line total, and schedule rendering.
6. Probe B: from a cart containing only `$DAILY_ID`, submit qty 1 only for `$FEE_ID`; capture the resulting notice as `SLT-CHK-12-03-probe-b-refusal.png`, save it verbatim, and require the cart returns to exactly `$DAILY_ID` qty 1. Do not remove the surviving Daily line.
7. Probe C: add only `$EXTRA_ID` to that cart, handle any redirect, require exact lines Daily $10 + Extra $3, and open `/checkout/`. Capture the unpopulated $13 summary as `SLT-CHK-12-04-probe-c-checkout.png`; fill the hosted 4242 card without capturing it, pay, record numeric `ORDER_ID`, and capture safe receipt `SLT-CHK-12-04a-receipt.png`.
8. Resolve `SUB_ID` only from `ORDER_ID._subscription_ids` JSON with a strict one-element numeric guard; require reverse parent/customer/product linkage to `$DAILY_ID`, both exact order lines, and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE+1`. Run the exact numeric subscription meta dump and resolve/dump both exact order items.
9. Resolve the direct-purchase Daily subscription `S1` from its registry task key/ID, dump the same keys in stable key order, and `diff -u` the normalized files. Record all differences as observations; only an actionable live mismatch becomes a dedicated issue.
10. In `admin-SLT-CHK-12`, open exact HPOS order `$ORDER_ID`; capture `SLT-CHK-12-05-admin-order.png` and require two line items, subscription meta only on `$DAILY_ID`, and no grouped-parent linkage. Reconcile the complete `PREV` delta: WC customer paid-order, WC admin New order, exactly one ArraySubs customer signup, and exactly one ArraySubs admin signup; save/show all four IDs.
11. Compute k/action gates for numeric `SUB_ID`, publish exact invoice/charge IDs and `gate−5m` deadline to the registry/D05 report, prove both carts empty, capture `SLT-CHK-12-06-cart-empty-after.png`, close only the two D5 sessions, and leave the card `in-progress`.
12. On 2026-08-29 take `GROUPED_R1_PRE` only in the final five minutes before the exact natural charge gate, never force it, and use `admin-SLT-CHK-12-R1` to resolve the renewal order by exact subscription/cycle plus reverse meta. Require total $10.00 and no `$EXTRA_ID`/$3 line; reconcile the owner-filtered mail delta and close that phase session.
13. If a live assertion fails or an exploratory observation is actionable, create a dedicated `qa/issues/` kanban card named `SLT-CHK-12-<concise-slug>` (create the required QA issue card) with task/stage/plan, user/product/order/subscription/action IDs, login/email/role, exact URLs/sessions, reproduction, expected/actual or observation, UI/meta/Mailpit/network/screenshot proof, and direct-purchase counterexample. Keep non-actionable observations in evidence/report only. After renewal, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. The grouped page renders a child table; record whether each subscription child shows a recurring summary or a plain price — the observation is the deliverable, not a verdict.
2. Probe A adds one subscription line at $10.00.
3. Probe B refused: one subscription line survives with a notice matching the `SLT-PROD-09` string.
4. Probe C total **$13.00**, no tax line, status `processing`/`completed`.
5. Exactly ONE subscription, for `SLT2 Daily Core`: `_product_id`=child ID (never the grouped parent), `_variation_id`=0, `_billing_period=day`, `_billing_interval=1`, `_recurring_amount=10.00`, `_signup_fee` empty/0, `_next_payment_date` 2026-08-29.
6. `SLT2 Grouped Extra` produces no subscription and no schedule meta.
7. Step 9 diff is empty. Record every difference in evidence; an actionable mismatch creates/updates the mandatory `qa/issues/` kanban card and blocks this task.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC customer paid-order + WC New order + `new_subscription` + `admin_new_subscription` | Probe C paid | slt2-grouped / admin | order ID / `New order #` / `is active` / `New subscription #` | complete owner-filtered delta after `PREV`; save/show all four exact IDs |
| 2 | WP New User Registration | setup before `PREV` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |
| 3 | NONE EXPECTED — 2nd subscription mail | Probe C | — | — | Exactly one `is active` for this order |

## Evidence to capture
- Numeric count/user/product/order/sub/action IDs and bidirectional linkage; safe `SLT-CHK-12-00` through `-06` captures; meta/item dumps + normalized diff; verbatim refusal; setup/checkout/renewal baselines and exact mail IDs; gate/deadline, carts, console/network, sessions/review proof.

## Pass criteria
- [ ] Grouped page rendering documented per row
- [ ] Probe A/B behaviour recorded with refusal text
- [ ] Probe C totals exactly $13.00
- [ ] One subscription only, keyed to the child product ID
- [ ] Meta diff vs the direct purchase captured
- [ ] Complete four-message checkout set and setup mail captured; negative row 3 holds
- [ ] Setup mail isolated before `PREV`; no customer account/password mail; final cart and persistent-cart meta empty
- [ ] Natural renewal contains only the subscription child; exact sessions closed and final evidence reviewed to done

## Isolation / teardown
- Creates `slt2-grouped` (deleted by `SLT-SETUP-99B`) and one live daily sub for the watch, cancelled by `SLT-SETUP-99A` on D11.
- Nothing global changed; cart emptied; only the exact D5 and R1 task sessions closed. Do NOT flip `allow_multiple_in_cart`.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
