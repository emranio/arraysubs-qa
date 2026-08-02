---
id: 65
title: 'Buy SLT Box Daily through the wizard: contents selection, order lines and box meta on the subscription'
status: todo
priority: high
created: 2026-08-02T03:43:08.790085884+02:00
updated: 2026-08-02T03:43:19.235367494+02:00
tags:
    - checkout
    - day-04
    - has-conflicts
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

### ⚠ Conflict resolutions that apply to this task

**`critical` · evidence-destruction / teardown vs watch window** — with `SLT-SETUP-99`, `SLT-CHK-14`, `SLT-EML-14`, `SLT-SYN-09`, `SLT-SYN-13`, `SLT-SYN-12`

- *Problem:* SLT-SETUP-99 is authored as a single d10 task that cancels AND permanently deletes every SLT subscription, order, product, coupon, page and user. With D10 = 2026-08-12 and the watch running to D12 = 2026-08-14, that deletes exactly the evidence D11 and D12 exist to collect. Events after D10: SUB_W1 + SUB_W (both week flex subs) renew 2026-08-14 00:00 site - the last scheduled events in the whole window and SYN-09's 'second charge full on the boundary' proof; the SLT-SYN-04 globally-synced day/3 subscription renews 08-14; SLT-SYN-13's Full and Next Cycle variations renew 08-13; SLT-CHK-13's Box Daily renews 08-12; SLT-CHK-14's lifetime negative control must be asserted on all 12 watch days including 08-13 and 08-14 (its own isolation note wrongly says '99A/99B'); SLT-EML-14 step 9 mandates a delta sweep on the morning of 08-14 and explicitly states 99B must not run before it, because a cancellation mail would contaminate the silence proof.
- *Required fix:* Split, as audit C06 directs, with the dates shifted +1. SLT-SETUP-99A on D10 (2026-08-12), after that morning's watch read and after SLT-DUN-05's recovery evidence is closed: Part 1 settings restore (five booleans, empty jq diff) plus cancellation of the COMPLETED-EVIDENCE COHORT ONLY - the day/1 workhorses (SLT Daily Core spine and its clones, Signup Fee Daily, Renewal Price Step, Paddle Daily, plan-ladder rungs, Free Signup Daily, Trial Four Day, Variable tiers, all CPN and CHK day/1 subs, IMP-03 concurrency subs, DUN-05's S2). No deletions. SLT-SETUP-99B on 2026-08-15 (Sat), strictly after the D12 watch report and SLT-EML-14's 08-14 delta are written: cancel the TAIL COHORT (both week flex subs, Sync Global Daily, SYN-13's two variation subs, SYN-12's two probes, SYN-14's qty sub, Box Daily, the lifetime controls, the flex month subs) then Parts 2-4 deletion. Correct SLT-CHK-14's and SLT-CHK-13's isolation notes to name 99B only. Publish the two cohort lists to the registry on D9 so the watcher can assert on D11/D12 that every 99A-cancelled subscription shows no renewal after its cancellation timestamp.

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-01`, `SLT-CHK-14`, `SLT-LIFE-04`, `SLT-CHK-11`, `SLT-MYA-02`, `SLT-ADM-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

**`high` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-13`, `SLT-CHK-08`, `SLT-SYN-07`, `SLT-SYN-11`, `SLT-SW-09`, `SLT-IMP-03`

- *Problem:* SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.
- *Required fix:* Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

---
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
- Session `core-CHK13`; cart empty first and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT Box Daily (`slt-box-daily`), day/2 |
| Selection | Box Item A x1 ($4.00) + Box Item B x1 ($6.00) |
| Account | slt-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Today | **$10.00**; renewal $10.00 every 2 days; next payment 2026-08-08 |

## Steps
1. `PREV=$(mailpit-agent latest-id)`.
2. `agent-browser --session core-CHK13 open "https://mirror-help.arrayhash.com/my-account/"` -> log in `slt-core`.
3. Add `SLT Box Item A` ($4.00) on its own so the cart is non-empty before the box.
4. Open `/slt-box-daily` -> `snapshot -i` -> launch the box -> in **Pick your items** choose A x1, B x1. Record the running total, whether `SLT Box Sub Item` is offered (do not select it), and any REST error.
5. Add the box -> `/cart/` -> `snapshot -i`. Record whether the $4.00 line survived, the box line title, and whether contents list under it.
6. `/checkout/` -> `snapshot -i` -> confirm $10.00, no fee, no tax line -> pay Stripe 4242 -> **Place Order**.
7. Record order + sub ID. In wp-admin record each line item's total and its `_arraysubs_box_child` / `_arraysubs_box_parent_key` meta.
8. `wp post meta list <SUB_ID> --keys=_product_id,_billing_period,_billing_interval,_recurring_amount,_signup_fee,_trial_length,_next_payment_date,_arraysubs_box_contents,_arraysubs_box_child_subscriptions --allow-root`.
9. Compute `k = crc32('arraysubs-spread-'.SUBID) % 21600`; derive invoice `due+k−6h`, charge `due+k`; check both rows in Tools -> Scheduled Actions (Pending).
10. Open the `/my-account/` subscription view -> screenshot how contents render to the customer.
11. Empty cart; `close --session core-CHK13`. Watch: renewal #1 on 08-08 is $10.00 with the same contents, no wizard re-run.

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
| 2 | admin_new_subscription | order paid | admin | `New subscription #` | `mailpit-agent list 50` |
| 3 | NONE EXPECTED — trial_started | order paid | — | — | No `free trial for` mail; the box forces `_trial_length=0` |
| 4 | NONE EXPECTED — extra sub mail | order paid | — | — | Exactly one `is active` despite three order lines |

## Evidence to capture
- `SLT-CHK-13-01-wizard.png`, `-02-cart-after-box.png`, `-03-checkout.png`, `-04-admin-order-lines.png`, `-05-myaccount-box.png`, `-06-scheduled-actions.png`.
- Order/sub IDs, meta dump, `_arraysubs_box_contents` JSON verbatim, offset, Mailpit IDs, REST/console errors from the wizard.

## Pass criteria
- [ ] Wizard computed $10.00 every 2 days
- [ ] Adding the box emptied the prior cart line
- [ ] Order total $10.00 with flagged $0.00 child lines
- [ ] Sub carries day/2, $10.00, no fee, no trial
- [ ] `_arraysubs_box_contents` matches the selection
- [ ] Both renewal legs at the offset-adjusted times
- [ ] Emails 1-2 captured; negatives 3-4 hold

## Isolation / teardown
- One live box sub for the watch (renews 08-08, 08-10, 08-12). It is in the D10 **tail cohort**: `SLT-SETUP-99A` must not cancel it; `SLT-SETUP-99B` does on 08-13.
- Nothing global changed; cart emptied; only `core-CHK13` closed.

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
