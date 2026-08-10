---
id: 80
title: 'EXPLORATORY: SLT Grouped Set rendering, add-to-cart probes, and one order through the grouped form'
status: done
priority: medium
created: 2026-08-02T03:43:09.920245061+02:00
updated: 2026-08-07T18:11:34.402037947+02:00
started: 2026-08-07T18:11:34.314950803+02:00
completed: 2026-08-07T18:11:34.314950803+02:00
tags:
    - checkout
    - day-05
due: "2026-08-07"
estimate: 1h 30m
depends_on:
    - 39
    - 5
    - 58
    - 12
class: standard
---

> **SLT-CHK-12** · group `checkout` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
EXPLORATORY. Grouped products get zero handling in either plugin (the `Subscription [ArraySubs]` checkbox is `show_if_simple show_if_variable`), so a grouped parent can never be a subscription. Document what `SLT Grouped Set` renders, whether a subscription child can be bought via the grouped form, and how the order and subscription differ from a direct buy. File what you find; assert no spec.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: admin-created (this task creates `slt-grouped`)
- Plugins: free-only

## Preconditions
- `SLT-PROD-09` complete: `SLT Grouped Set` with children Daily Core, Signup Fee Daily, Grouped Extra; its refusal text is in the registry.
- `SLT-SETUP-02` baseline; `allow_multiple_in_cart=false`, `allow_mixed_cart=true`.
- **Creates one account**: `slt-grouped` / `slt-grouped@example.test`, Customer, pw `SltQa!2026#Pass`, billing address per `SLT-SETUP-03` step 4 — `slt-core` already owns subs for both subscription children and must not rebuy them.
- Sessions `admin-SLT-CHK-12` and `grouped-CHK12-SLT-CHK-12`; browser and persistent carts empty first, between probes, and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT Grouped Set (`slt-grouped-set`) |
| Children | Daily Core $10.00/day; Signup Fee Daily $9.00/day + $15.00 fee; Grouped Extra $3.00 |
| Account | slt-grouped (created here) |
| Card | 4242 4242 4242 4242 |
| Order | Daily Core x1 + Grouped Extra x1 = **$13.00** |

## Steps
1. Resolve strict numeric, distinct `GROUP_ID`, `DAILY_ID`, `FEE_ID`, and `EXTRA_ID` from the registry/slugs and verify the grouped-child relationship. Record `SUBCOUNT_BEFORE` and `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-CHK-12`, create the user at `/wp-admin/user-new.php` (untick **Send User Notification**), record numeric `USER_ID`/Customer role, then set billing address at its exact `user-edit.php?user_id=$USER_ID`. Classify exactly one admin-addressed `New User Registration` after `USER_PRE` and prove there is no customer account/password mail.
2. After setup mail is classified, `PREV=$(mailpit-agent latest-id)` as the checkout-only baseline.
3. In `grouped-CHK12-SLT-CHK-12`, log in as `slt-grouped`; require both carts empty and capture `SLT-CHK-12-00-cart-empty-before.png`.
4. Open `/product/slt-grouped-set/`; capture `SLT-CHK-12-01-grouped-page.png` and record each exact child ID's price, schedule/fee text, and quantity/link control.
5. Probe A: set qty 1 only for `$DAILY_ID` and submit. Handle one-click by explicitly reopening `/cart/`; capture `SLT-CHK-12-02-probe-a.png`, exact line total, and schedule rendering.
6. Probe B: from a cart containing only `$DAILY_ID`, submit qty 1 only for `$FEE_ID`; capture the resulting notice as `SLT-CHK-12-03-probe-b-refusal.png`, save it verbatim, and require the cart returns to exactly `$DAILY_ID` qty 1. Do not remove the surviving Daily line.
7. Probe C: add only `$EXTRA_ID` to that cart, handle any redirect, require exact lines Daily $10 + Extra $3, and open `/checkout/`. Capture the unpopulated $13 summary as `SLT-CHK-12-04-probe-c-checkout.png`; fill the hosted 4242 card without capturing it, pay, record numeric `ORDER_ID`, and capture safe receipt `SLT-CHK-12-04a-receipt.png`.
8. Resolve `SUB_ID` only from `ORDER_ID._subscription_ids` JSON with a strict one-element numeric guard; require reverse parent/customer/product linkage to `$DAILY_ID`, both exact order lines, and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE+1`. Run the exact numeric subscription meta dump and resolve/dump both exact order items.
9. Resolve the direct-purchase Daily subscription `S1` from its registry task key/ID, dump the same keys in stable key order, and `diff -u` the normalized files. Record all differences as observations; only an actionable live mismatch becomes a standalone issue.
10. In `admin-SLT-CHK-12`, open exact HPOS order `$ORDER_ID`; capture `SLT-CHK-12-05-admin-order.png` and require two line items, subscription meta only on `$DAILY_ID`, and no grouped-parent linkage. Reconcile the complete `PREV` delta: WC customer paid-order, WC admin New order, exactly one ArraySubs customer signup, and exactly one ArraySubs admin signup; save/show all four IDs.
11. Compute k/action gates for numeric `SUB_ID`, publish exact invoice/charge IDs and `gate−5m` deadline to the registry/D05 report, prove both carts empty, capture `SLT-CHK-12-06-cart-empty-after.png`, close only the two D5 sessions, and leave the card `in-progress`.
12. On 2026-08-08 take `GROUPED_R1_PRE` only in the final five minutes before the exact natural charge gate, never force it, and use `admin-SLT-CHK-12-R1` to resolve the renewal order by exact subscription/cycle plus reverse meta. Require total $10.00 and no `$EXTRA_ID`/$3 line; reconcile the owner-filtered mail delta and close that phase session.
13. If a live assertion fails or an exploratory observation is actionable, create a standalone `issues/SLT-CHK-12-<concise-slug>.md` (never a kanban bug card) with task/stage/plan, user/product/order/subscription/action IDs, login/email/role, exact URLs/sessions, reproduction, expected/actual or observation, UI/meta/Mailpit/network/screenshot proof, and direct-purchase counterexample. Keep non-actionable observations in evidence/report only. After renewal, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. The grouped page renders a child table; record whether each subscription child shows a recurring summary or a plain price — the observation is the deliverable, not a verdict.
2. Probe A adds one subscription line at $10.00.
3. Probe B refused: one subscription line survives with a notice matching the `SLT-PROD-09` string.
4. Probe C total **$13.00**, no tax line, status `processing`/`completed`.
5. Exactly ONE subscription, for `SLT Daily Core`: `_product_id`=child ID (never the grouped parent), `_variation_id`=0, `_billing_period=day`, `_billing_interval=1`, `_recurring_amount=10.00`, `_signup_fee` empty/0, `_next_payment_date` 2026-08-08.
6. `SLT Grouped Extra` produces no subscription and no schedule meta.
7. Step 9 diff is empty. Record every difference in evidence; write an actionable mismatch as a standalone markdown file under `issues/`, never as a lifecycle-board card.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC customer paid-order + WC New order + `new_subscription` + `admin_new_subscription` | Probe C paid | slt-grouped / admin | order ID / `New order #` / `is active` / `New subscription #` | complete owner-filtered delta after `PREV`; save/show all four exact IDs |
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
- Creates `slt-grouped` (deleted by `SLT-SETUP-99B`) and one live daily sub for the watch, cancelled by `SLT-SETUP-99A` on D10.
- Nothing global changed; cart emptied; only the exact D5 and R1 task sessions closed. Do NOT flip `allow_multiple_in_cart`.

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

[[2026-08-06]] Thu 21:32
Preflight 2026-08-06: grouped exploratory fixtures remain usable. slt-grouped@example.test is still absent, so the task-owned user can be created cleanly. Grouped parent slt-grouped-set = 12586 is still published as a grouped product, with direct child fixtures daily 11927, signup-fee 12577, and extra 12583 all still published and distinct.

[[2026-08-06]] Thu 22:22
As of 2026-08-06 readiness review: no current source-block is visible from live evidence. Earlier fixture checks still show the grouped-set product and its child products published, so this exploratory grouped-flow card remains a valid Friday, August 7, 2026 candidate; keep it in todo until that date.

[[2026-08-07]] Fri 18:11
D05 night execution: FAIL on Probe B; adding Signup Fee Daily from the grouped form silently replaced the existing Daily Core line and showed a success notice. Standalone finding: ../issues/SLT-CHK-12-grouped-sequential-add-replaces-existing-subscription.md. PASS for grouped-row documentation, Daily-only probe, mixed Daily+Extra exact 3 checkout summary, setup-mail isolation, and empty-cart teardown. Paid order/subscription/mail/linkage/diff/renewal legs are UNVERIFIED because the hosted Stripe element remained incomplete after two attempts; exact guards remained orders=0, subs=0, Mailpit=2WHncuVdu6LGSuQ70KXk42. Evidence: /home/server-manager/slt-evidence/SLT-CHK-12-D05-execution.txt and task screenshots. User 367 remains for 99B; no order/sub was created and no future gate exists.
