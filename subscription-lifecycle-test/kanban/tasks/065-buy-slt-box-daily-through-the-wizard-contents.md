---
id: 65
title: 'Buy SLT2 Box Daily through the wizard: contents selection, order lines and box meta on the subscription'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-04
due: "2026-08-27"
estimate: 1h 30m
depends_on:
    - 59
    - 11
    - 12
class: standard
---

> **SLT-CHK-13** · group `checkout` · scheduled **D04** (2026-08-27)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy `SLT2 Box Daily` through the storefront wizard and prove the free Subscription Box contract: the configurator computes the recurring total from the selection, adding the box empties the cart first, the order carries the box line at the full recurring amount with contents as $0.00 child lines, and the frozen selection lands on the sub.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing
- Plugins: free/core-owned Subscription Box

## Preconditions
- `SLT-PROD-10` complete: `SLT2 Box Daily` (`arraysubs_subscription_box`, day/2, `_sold_individually=yes`, no stored price) + Item A $4.00, Item B $6.00, Box Sub Item $5.00 day/2. Quote all four IDs from the registry.
- `SLT-SETUP-02` baseline; `SLT-SETUP-03` (`slt2-core` + billing address).
- Box flex sync was left DISABLED in the modal, so this sub schedules on anniversary, not midnight.
- Sessions `core-CHK13-SLT-CHK-13`, `admin-SLT-CHK-13`; cart and persistent cart empty first and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Box Daily (`slt2-box-daily`), day/2 |
| Selection | Box Item A x1 ($4.00) + Box Item B x1 ($6.00) |
| Account | slt2-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Today | **$10.00**; renewal $10.00 every 2 days; next payment 2026-08-29 |

## Steps
1. Record `SUBCOUNT_BEFORE=<exact current SLT2 subscription count>` and purchase-only `PREV=$(mailpit-agent latest-id)`.
2. In `core-CHK13-SLT-CHK-13`, open `https://mirror-help.arrayhash.com/my-account/`, log in as `slt2-core`, and require both browser and persistent carts empty.
3. Add `SLT2 Box Item A` ($4.00) on its own so the cart is non-empty before the box.
4. Open `/product/slt2-box-daily/` -> `snapshot -i` -> launch the box -> in **Pick your items** choose A x1, B x1. Record the running total, whether `SLT2 Box Sub Item` is offered (do not select it), any REST error, and capture `SLT-CHK-13-01-wizard.png`.
5. Add the box. If one-click redirects to checkout, record it and explicitly reopen `/cart/`; re-snapshot, record whether the prior $4.00 line survived, require the box line/contents, and capture `SLT-CHK-13-02-cart-after-box.png`.
6. Open `/checkout/`, confirm $10.00/no fee/no tax, and capture `SLT-CHK-13-03-checkout.png` before card entry. Fill the hosted Stripe test card without capturing it, place the order, record numeric `ORDER_BOX`, and capture the safe receipt as `SLT-CHK-13-03a-order-received.png`.
7. Resolve `SUB_BOX` only from `wp post meta get "$ORDER_BOX" _subscription_ids --format=json --allow-root` with a strict one-element numeric `jq -e` guard; require reverse parent/customer/product and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE + 1`. In `admin-SLT-CHK-13`, open exact `ORDER_BOX`, record every line total plus `_arraysubs_box_child` / `_arraysubs_box_parent_key`, and capture `SLT-CHK-13-04-admin-order-lines.png`.
8. `wp post meta list "$SUB_BOX" --keys=_product_id,_billing_period,_billing_interval,_recurring_amount,_signup_fee,_trial_length,_next_payment_date,_arraysubs_box_contents,_arraysubs_box_child_subscriptions --allow-root`.
9. Compute k with the README numeric argv command; query the exact pending invoice/charge rows, capture them as `SLT-CHK-13-06-scheduled-actions.png`, and publish their IDs/gates plus the first `charge−5m` deadline to the registry and D04 report.
10. Open the exact `/my-account/` subscription view and capture contents as `SLT-CHK-13-05-myaccount-box.png`.
11. `mailpit-agent wait-new "$PREV" 180 "is active"`; inspect the complete owner-filtered delta and require exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs, while proving no trial or extra subscription mail. Empty/prove browser and persistent carts, close only `core-CHK13-SLT-CHK-13` and `admin-SLT-CHK-13`, independently review the purchase/action evidence, move the card through `review` to `done`, and ensure Review returns to zero. Any live wizard/order/meta failure creates/updates one fully evidenced mandatory `qa/issues/` kanban card with task/plan, order/subscription/product/user IDs and context, reproduction, expected/actual, UI/REST/meta/mail proof and a plain-subscription counterexample, and blocks this task. Watch renewal #1 on 08-29 at the handed-off gate; do not force it.

## Expected results
1. Wizard total for A+B reads `$10.00` every 2 days.
2. Adding the box removed the dedicated $4.00 line — the cart holds the box only.
3. Order total exactly **$10.00**, `processing`/`completed`, no tax line, no `Subscription Signup Fee`.
4. One box line at $10.00 plus A and B child lines at **$0.00**, flagged `_arraysubs_box_child=yes`, sharing one `_arraysubs_box_parent_key`.
5. Sub: `arraysubs-active`, `_product_id`=box ID, `_billing_period=day`, `_billing_interval=2`, `_recurring_amount=10.00`, `_signup_fee` empty/0, `_trial_length=0` (forced off in a box), `_next_payment_date` 2026-08-29 at checkout clock time.
6. `_arraysubs_box_contents` is JSON naming exactly Item A x1 and Item B x1; `_arraysubs_box_child_subscriptions` is empty.
7. Both renewal legs pending at the step-9 timestamps.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | order paid | slt2-core@example.test | `is active` | `mailpit-agent wait-new "$PREV" 180 "is active"` |
| 2 | admin_new_subscription | order paid | admin | `New subscription #` | Complete owner-filtered delta after `$PREV`; save/show the exact matching id |
| 2a | WC paid-order | order paid | slt2-core@example.test | exact order / `is on its way` | Complete owner-filtered delta after `$PREV`; save/show exact id |
| 2b | WC New order | order paid | admin | `New order #<ORDER_BOX>` | Complete owner-filtered delta after `$PREV`; save/show exact id |
| 3 | NONE EXPECTED — trial_started | order paid | — | — | No `free trial for` mail; the box forces `_trial_length=0` |
| 4 | NONE EXPECTED — extra sub mail | order paid | — | — | Exactly one `is active` despite three order lines |

## Evidence to capture
- `SLT-CHK-13-01-wizard.png`, `-02-cart-after-box.png`, `-03-checkout.png`, `-03a-order-received.png`, `-04-admin-order-lines.png`, `-05-myaccount-box.png`, `-06-scheduled-actions.png`.
- Order/sub IDs, +1/bidirectional linkage, meta dump, `_arraysubs_box_contents` JSON verbatim, offset/action/deadline handoff, four checkout Mailpit IDs, carts/session/review proof, REST/console/network errors.

## Pass criteria
- [ ] Wizard computed $10.00 every 2 days
- [ ] Adding the box emptied the prior cart line
- [ ] Order total $10.00 with flagged $0.00 child lines
- [ ] Sub carries day/2, $10.00, no fee, no trial
- [ ] `_arraysubs_box_contents` matches the selection
- [ ] Both renewal legs at the offset-adjusted times
- [ ] Emails 1-2 captured; negatives 3-4 hold
- [ ] Full four-message checkout set and exact future action handoff recorded; card reviewed to done

## Isolation / teardown
- One live box sub for the watch (renews 08-29, 08-31, 09-02). It is in the D11 **tail cohort**: `SLT-SETUP-99A` must not cancel it; `SLT-SETUP-99B` cancels it during final teardown on 2026-09-05.
- Nothing global changed; browser/persistent carts emptied; both exact task sessions closed.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
