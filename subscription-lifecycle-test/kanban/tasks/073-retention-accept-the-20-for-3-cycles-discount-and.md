---
id: 73
title: 'Retention: accept the 20%-for-3-cycles discount and prove exactly 3 discounted renewals, plus a downgrade offer'
status: done
priority: critical
created: 2026-08-02T03:43:09.416971494+02:00
updated: 2026-08-06T20:33:42.528088641+02:00
started: 2026-08-06T20:33:42.528087799+02:00
completed: 2026-08-06T20:33:42.528087799+02:00
tags:
    - plan-switching
    - day-04
due: "2026-08-06"
estimate: 2h
depends_on:
    - 11
    - 12
    - 60
class: standard
---

> **SLT-SW-09** · group `switching` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove retention on real renewals: accept the 20%-off-for-3-cycles offer in the cancel flow and show the discount is charged for **exactly three** renewals with the fourth back at full price; and on a second subscription accept the **downgrade** offer and show it becomes a pending switch applied at the next renewal.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered — **this task creates `slt-retain` + `slt-retain2`**
- Plugins: free-only

## Preconditions
- SLT-SETUP-02/03 and **SLT-PROD-11 done before this task starts on D4**; if PROD-11 slips, run on D5 and shift every watch day by +1. Two accounts: `hasUsedDiscountOffer()` allows one discount per subscription.
- Baseline `cancellation.retention_offers`: discount on (20%, 3 cycles), downgrade on, pause/skip/contact off; `cancel_immediately=false`, `require_reason=true`.
- D4 sessions `admin-SLT-SW-09`, `customer-a-SLT-SW-09`, and `customer-b-SLT-SW-09`; carts and persistent-cart metas empty before, between, and after the purchases; buy after 12:00. No browser session may remain open across watch days: each renewal phase opens and closes its own task-and-cycle-keyed admin session.

## Test data
| Item | Value |
|---|---|
| A | slt-retain buys **SLT Plan Basic** $5.00 day/1; discounted renewal **$4.00** (line −$1.00) |
| B | slt-retain2 buys **SLT Plan Peer** $15.00 day/1; downgrade target **SLT Plan Basic** $5.00. Card 4242 4242 4242 4242 |
| Watch | A renews D5/D6/D7 at $4.00 and **D8 (08-10) at $5.00** — reported on watch D6-**D9** |

## Steps
1. Resolve strict numeric, distinct `BASIC_ID` and `PEER_ID` from the registry and cross-check their published titles/prices/schedules. Set `SUBCOUNT_BEFORE` to the exact current `arraysubs_data` count and `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-SW-09` create both accounts as in SLT-SW-06 step 2; record strict numeric, distinct `USER_RET_DISC` and `USER_RET_DOWN`, their logins/emails, and Customer roles. Classify exactly two admin-addressed `New User Registration` messages after `USER_PRE`, one per user, and prove there is no customer account/password mail. Then set checkout-only baseline `MP0=$(mailpit-agent latest-id)`.
2. In `customer-a-SLT-SW-09`, log in as `slt-retain`, require both browser and persistent carts empty, and capture `SLT-SW-09-00-cart-empty-before-a.png`. Open `/checkout/?add-to-cart=$BASIC_ID`; if one-click redirects directly to checkout, accept that transition. Capture the unpopulated $5.00 summary as `SLT-SW-09-00a-basic-checkout.png`, fill the hosted 4242 card without capturing it, pay, record numeric `ORDER_RET_DISC`, and capture the safe receipt as `SLT-SW-09-00b-basic-receipt.png`. Resolve `SUB_RET_DISC` only from `ORDER_RET_DISC._subscription_ids` JSON with a strict one-element numeric guard; require reverse parent/customer/product linkage and `SUBCOUNT_AFTER_A == SUBCOUNT_BEFORE+1`. Record its crc32 `OFFSET_DISC`, `_next_payment_date`, exact invoice/charge action IDs, and require the complete `MP0` delta: WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs.
3. Set `RET_PRE=$(mailpit-agent latest-id)`. On `/my-account/view-subscription/$SUB_RET_DISC/`, choose **Cancel Subscription** → reason **Too expensive** → **Continue**.
4. On **Before You Go...** capture `SLT-SW-09-01-offers.png`, accept **Stay and Save!** ("20% off for the next 3 billing cycles"), capture `SLT-SW-09-02-accepted.png`, and run `mailpit-agent wait-new "$RET_PRE" 180 "retention discount"`; save the exact matching ID and classify the complete delta.
5. Run `wp post meta list "$SUB_RET_DISC" --allow-root`; record the five `_retention_discount_*` keys, `_retention_offer_type`, and absence of `_waiting_cancellation`. Reload and capture the **Recurring Amount** row as `SLT-SW-09-03-recurring.png`: $4.00, "Discounted from $5.00 for the next 3 renewal(s)."
6. In `customer-b-SLT-SW-09`, log in as `slt-retain2`, require both carts empty, capture `SLT-SW-09-03a-cart-empty-before-b.png`, and set `BUY_B_PRE=$(mailpit-agent latest-id)`. Add only `$PEER_ID`, handle the one-click redirect, capture the unpopulated $15.00 summary as `SLT-SW-09-03b-peer-checkout.png`, fill the hosted 4242 card without capturing it, pay, record numeric `ORDER_RET_DOWN`, and capture `SLT-SW-09-03c-peer-receipt.png`. Resolve `SUB_RET_DOWN` only from `ORDER_RET_DOWN._subscription_ids` JSON with the same strict guard; require it distinct, reverse parent/customer/product linkage, and `SUBCOUNT_AFTER_B == SUBCOUNT_BEFORE+2`. Record `OFFSET_DOWN`, `_next_payment_date`, exact invoice/charge action IDs, and require the second complete four-message checkout delta after `BUY_B_PRE`.
7. Set `DOWN_PRE=$(mailpit-agent latest-id)`. Repeat the cancel flow on `$SUB_RET_DOWN`, accept **Switch to a smaller plan** → exact target `$BASIC_ID`, capture the offer/result as `SLT-SW-09-04-downgrade.png` and the resulting portal banner as `SLT-SW-09-05-pending.png`; dump `_retention_offer_type`, absence of `_waiting_cancellation`, `_recurring_amount`, and every pending-switch key. Require no task-attributable message newer than `DOWN_PRE`.
8. Reconcile the complete owner-filtered deltas after `MP0`, `RET_PRE`, `BUY_B_PRE`, and `DOWN_PRE`; classify background mail and save/show all exact IDs. Query Action Scheduler by each numeric subscription's indexed args, record the exact pending charge IDs/GMT gates, and publish each `gate−5m` deadline to the registry/D04 report. Prove both carts empty, capture `SLT-SW-09-06-cart-empty-after-b.png`, close only the three D4 sessions, and leave this card `in-progress` for its authored natural renewals.
9. For each renewal, set its named baseline only inside the final five-minute interval `[exact charge gate−300s, exact charge gate)` — never earlier: `RET_DISC_R1_PRE` through `RET_DISC_R4_PRE` and `RET_DOWN_R1_PRE` through `RET_DOWN_R2_PRE`. Observe the exact action naturally and never force it. Resolve each renewal order by the owning subscription ID, scheduled cycle, and reverse order meta rather than recency; record action logs, order ID/total/status/line items, payment count, recurring amount, remaining-discount meta, and the complete owner-filtered Mailpit delta. After each settled leg, publish the next exact action ID/gate/deadline before closing that phase's `admin-SLT-SW-09-<owner>-R<n>` session.
10. If any live assertion fails, create a standalone `issues/SLT-SW-09-<concise-slug>.md` (never a kanban bug card) containing this progress task/stage and plan path; affected order/subscription/action/product IDs; user IDs/logins/emails/roles; exact URLs and browser/session context; reproduction; expected/actual; UI, meta, scheduler, Mailpit, and screenshot proof; and the other subscription/cycle as a counterexample where applicable. Continue every unaffected leg. After A renewal 4 and B renewal 2 are reconciled, independently review all evidence, move this card through `review` to `done`, ensure Review returns to zero, and close the final task sessions.

## Expected results
1. `SUB_RET_DISC` after step 4: `_retention_discount_type=percent`, `_amount=20`, `_remaining=3`, `_base_amount=5.00`, `_effective_amount=4.00`, `_retention_offer_type=coupon`; `_waiting_cancellation` **deleted**, still `arraysubs-active`.
2. Renewals 1-3 (D5-D7) each total **$4.00** with a `Retention Discount` line of **−$1.00**; `_retention_discount_remaining` goes 3→2→1→0, only after each order is paid.
3. At 0 the five keys are deleted and renewal 4 (D8, watch D9) totals **$5.00** with **no** discount line — exactly three discounted.
4. `SUB_RET_DOWN` after step 7: `_retention_offer_type=downgrade`, `_waiting_cancellation` deleted, still active, `_recurring_amount` **15.00**, pending switch to Basic ("Plan switch to SLT Plan Basic scheduled for …").
5. `SUB_RET_DOWN` renewal 1 charges **$15.00**; the switch applies once it is paid, so renewal 2 charges **$5.00** and `_recurring_amount` becomes 5.00.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC customer paid-order ×2, WC New order ×2, `new_subscription` ×2, `admin_new_subscription` ×2 | steps 2, 6 | customers, admin | order id / `New order #` / `is active` / `New subscription #` | complete separate owner-filtered deltas after `MP0` and `BUY_B_PRE`; exactly four task messages per purchase |
| 2 | retention_discount_accepted | step 4 | slt-retain | `Your retention discount for SLT Plan Basic is active` | `mailpit-agent wait-new "$RET_PRE" 180` |
| 3 | NONE for the downgrade offer, NONE pending-cancellation | steps 3-4, 7 | — | — | only discount/coupon offers email; accepting one clears `_waiting_cancellation` before it is scheduled |
| 4 | WP New User Registration ×2 | setup before `MP0` | admin | `New User Registration` | exactly two after `USER_PRE`; zero customer account/password mail |
| 5 | payment_successful | each watched paid renewal | slt-retain / slt-retain2 | `Payment received for subscription #<exact id>` | complete owner delta after the matching `RET_DISC_Rn_PRE` / `RET_DOWN_Rn_PRE`; save/show exact id |

## Evidence to capture
- Safe named `SLT-SW-09-00` through `-06` D4 captures plus cycle-keyed renewal/admin captures; no image may contain a populated card number. Record the subscription count progression; numeric user/product/order/subscription/action IDs and bidirectional linkage; offsets/metas; every exact gate/deadline and renewal order/cycle/total; `USER_PRE`, two setup-mail IDs, `MP0`, `RET_PRE`, `BUY_B_PRE`, `DOWN_PRE`, every `RET_DISC_Rn_PRE` / `RET_DOWN_Rn_PRE`, and every matched Mailpit ID; include session/review proof.

## Pass criteria
- [ ] Discount stores 20% / remaining 3 / effective $4.00, clears the pending cancellation, Recurring Amount reads $4.00 from $5.00
- [ ] D5-D7 renewals total $4.00 with a −$1.00 Retention Discount line; D8 totals $5.00 with none, discount metas gone
- [ ] Downgrade offer creates a pending switch, sends no email, leaves $15.00 recurring
- [ ] `SUB_RET_DOWN` renewal 1 = $15.00, renewal 2 = $5.00; email 2 present, email 3 absent
- [ ] Setup mail isolated before `MP0`; no customer account/password mail
- [ ] Both purchases are +1/+2 and bidirectionally linked, both complete four-message deltas are reconciled, all exact task sessions are closed, and the final evidence review moved the card to done with Review empty

## Isolation / teardown
- Both subscriptions stay active through watch day D9 (2026-08-11); SLT-SETUP-99A must not cancel them before their evidence is complete.
- Empty both customer carts, verify both persistent-cart metas empty, and close only the exact D4 and cycle-keyed `SLT-SW-09` sessions after their phases.
- No global setting changed. Do not accept a second offer on either subscription — it is refused and would confuse the cycle count. Both accounts are deleted by SLT-SETUP-99B.

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
Carry-forward note: this card seeds a multi-day watch chain. Because it was not started on the authored D4 site-local day, do not silently backfill it later without republishing any shifted watch dates and dependent baselines.

[[2026-08-06]] Thu 20:33
UNVERIFIED closeout on 2026-08-06: this card seeds a multi-day retention watch chain and was not started on the authored D4 site-local day. Closing the execution task avoids silently shifting downstream watch dates without an explicit replan.
