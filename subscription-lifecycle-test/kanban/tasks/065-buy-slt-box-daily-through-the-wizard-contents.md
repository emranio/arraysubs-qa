---
id: 65
title: 'Buy SLT Box Daily through the wizard: contents selection, order lines and box meta on the subscription'
status: done
priority: high
created: 2026-08-02T03:43:08.790085884+02:00
updated: 2026-08-06T20:33:41.461263391+02:00
started: 2026-08-06T20:33:41.461262509+02:00
completed: 2026-08-06T20:33:41.461262509+02:00
tags:
    - checkout
    - day-04
due: "2026-08-06"
estimate: 1h 30m
depends_on:
    - 59
    - 11
    - 12
class: standard
---

> **SLT-CHK-13** · group `checkout` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy `SLT Box Daily` through the storefront wizard and prove the pro Subscription Box contract: the configurator computes the recurring total from the selection, adding the box empties the cart first, the order carries the box line at the full recurring amount with contents as $0.00 child lines, and the frozen selection lands on the sub.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing
- Plugins: pro-required

## Preconditions
- `SLT-PROD-10` complete: `SLT Box Daily` (`arraysubs_subscription_box`, day/2, `_sold_individually=yes`, no stored price) + Item A $4.00, Item B $6.00, Box Sub Item $5.00 day/2. Quote all four IDs from the registry.
- `SLT-SETUP-02` baseline; `SLT-SETUP-03` (`slt-core` + billing address).
- Box flex sync was left DISABLED in the modal, so this sub schedules on anniversary, not midnight.
- Sessions `core-CHK13-SLT-CHK-13`, `admin-SLT-CHK-13`; cart and persistent cart empty first and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT Box Daily (`slt-box-daily`), day/2 |
| Selection | Box Item A x1 ($4.00) + Box Item B x1 ($6.00) |
| Account | slt-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Today | **$10.00**; renewal $10.00 every 2 days; next payment 2026-08-08 |

## Steps
1. Record `SUBCOUNT_BEFORE=<exact current SLT subscription count>` and purchase-only `PREV=$(mailpit-agent latest-id)`.
2. In `core-CHK13-SLT-CHK-13`, open `https://mirror-help.arrayhash.com/my-account/`, log in as `slt-core`, and require both browser and persistent carts empty.
3. Add `SLT Box Item A` ($4.00) on its own so the cart is non-empty before the box.
4. Open `/product/slt-box-daily/` -> `snapshot -i` -> launch the box -> in **Pick your items** choose A x1, B x1. Record the running total, whether `SLT Box Sub Item` is offered (do not select it), any REST error, and capture `SLT-CHK-13-01-wizard.png`.
5. Add the box. If one-click redirects to checkout, record it and explicitly reopen `/cart/`; re-snapshot, record whether the prior $4.00 line survived, require the box line/contents, and capture `SLT-CHK-13-02-cart-after-box.png`.
6. Open `/checkout/`, confirm $10.00/no fee/no tax, and capture `SLT-CHK-13-03-checkout.png` before card entry. Fill the hosted Stripe test card without capturing it, place the order, record numeric `ORDER_BOX`, and capture the safe receipt as `SLT-CHK-13-03a-order-received.png`.
7. Resolve `SUB_BOX` only from `wp post meta get "$ORDER_BOX" _subscription_ids --format=json --allow-root` with a strict one-element numeric `jq -e` guard; require reverse parent/customer/product and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE + 1`. In `admin-SLT-CHK-13`, open exact `ORDER_BOX`, record every line total plus `_arraysubs_box_child` / `_arraysubs_box_parent_key`, and capture `SLT-CHK-13-04-admin-order-lines.png`.
8. `wp post meta list "$SUB_BOX" --keys=_product_id,_billing_period,_billing_interval,_recurring_amount,_signup_fee,_trial_length,_next_payment_date,_arraysubs_box_contents,_arraysubs_box_child_subscriptions --allow-root`.
9. Compute k with the README numeric argv command; query the exact pending invoice/charge rows, capture them as `SLT-CHK-13-06-scheduled-actions.png`, and publish their IDs/gates plus the first `charge−5m` deadline to the registry and D04 report.
10. Open the exact `/my-account/` subscription view and capture contents as `SLT-CHK-13-05-myaccount-box.png`.
11. `mailpit-agent wait-new "$PREV" 180 "is active"`; inspect the complete owner-filtered delta and require exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs, while proving no trial or extra subscription mail. Empty/prove browser and persistent carts, close only `core-CHK13-SLT-CHK-13` and `admin-SLT-CHK-13`, independently review the purchase/action evidence, move the card through `review` to `done`, and ensure Review returns to zero. Any live wizard/order/meta failure becomes a fully evidenced standalone `issues/*.md` file with task/plan, order/subscription/product/user IDs and context, reproduction, expected/actual, UI/REST/meta/mail proof and a plain-subscription counterexample—never a kanban bug card. Watch renewal #1 on 08-08 at the handed-off gate; do not force it.

## Expected results
1. Wizard total for A+B reads `$10.00` every 2 days.
2. Adding the box removed the standalone $4.00 line — the cart holds the box only.
3. Order total exactly **$10.00**, `processing`/`completed`, no tax line, no `Subscription Signup Fee`.
4. One box line at $10.00 plus A and B child lines at **$0.00**, flagged `_arraysubs_box_child=yes`, sharing one `_arraysubs_box_parent_key`.
5. Sub: `arraysubs-active`, `_product_id`=box ID, `_billing_period=day`, `_billing_interval=2`, `_recurring_amount=10.00`, `_signup_fee` empty/0, `_trial_length=0` (forced off in a box), `_next_payment_date` 2026-08-08 at checkout clock time.
6. `_arraysubs_box_contents` is JSON naming exactly Item A x1 and Item B x1; `_arraysubs_box_child_subscriptions` is empty.
7. Both renewal legs pending at the step-9 timestamps.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | order paid | slt-core@example.test | `is active` | `mailpit-agent wait-new "$PREV" 180 "is active"` |
| 2 | admin_new_subscription | order paid | admin | `New subscription #` | Complete owner-filtered delta after `$PREV`; save/show the exact matching id |
| 2a | WC paid-order | order paid | slt-core@example.test | exact order / `is on its way` | Complete owner-filtered delta after `$PREV`; save/show exact id |
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
- One live box sub for the watch (renews 08-08, 08-10, 08-12). It is in the D10 **tail cohort**: `SLT-SETUP-99A` must not cancel it; `SLT-SETUP-99B` cancels it during final teardown on 2026-08-15.
- Nothing global changed; browser/persistent carts emptied; both exact task sessions closed.

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

[[2026-08-06]] Thu 20:15
Missed-window note: not started before the D4 site-local rollover to 2026-08-07 00:14 +06. Do not backfill this as if it were still same-day D4 execution; keep in todo until a valid reschedule/next-day decision is made.

[[2026-08-06]] Thu 20:33
UNVERIFIED closeout on 2026-08-06: this D4 same-day execution window was missed after the site-local rollover into 2026-08-07. The card is closed rather than carried forward as if its original dated setup and downstream timings were still valid.
