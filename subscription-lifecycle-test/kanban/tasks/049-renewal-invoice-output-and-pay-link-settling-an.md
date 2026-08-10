---
id: 49
title: Renewal invoice output and pay-link settling an unpaid invoice on Stripe and Paddle
status: done
priority: high
created: 2026-08-02T03:43:07.248285977+02:00
updated: 2026-08-06T20:33:40.426786064+02:00
started: 2026-08-06T20:33:40.426785222+02:00
completed: 2026-08-06T20:33:40.426785222+02:00
tags:
    - admin
    - portal
    - day-03
due: "2026-08-05"
estimate: 2h
depends_on:
    - 58
    - 23
    - 29
class: standard
---

> **SLT-ADM-07** · group `admin` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Verify the customer-facing renewal invoice: what an unpaid renewal order shows in my-account and on order-pay, that totals are right with taxes off and no signup-fee line, and that the Pay link settles it on Stripe and Paddle. The unpaid window is real on both — Stripe leaves the order `pending` from `due+k-6h` to `due+k`, Paddle's `processRenewalPayment()` no-ops (SLT-REF-09 A).

## Scope
- Gateway: both
- Checkout: block (order-pay page)
- Account: existing (slt-core, slt-paddle)
- Plugins: both

## Preconditions
- `SLT-PROD-04` is done and its `SLT Signup Fee Daily` product id is in the registry. This task is the sole owner of the D3 `slt-core` purchase; no other task may create `S_FEE`.
- For the D4 invoice checks, SLT-ADM-06 is done; `S_FEE` was created by this task's Phase A; `SLT Paddle Daily` ($11.00/day) was bought D2 by `slt-paddle`; ids are in the registry.
- C51 fallback: if CHK-04 published `SUB_PAD unavailable`, Phase A and the D4 Stripe `S_FEE` leg still run in full. Mark only the Paddle invoice/pay-link leg `UNVERIFIED (no source subscription)`, cite the standalone CHK-04 issue, and do not buy a substitute, invent an alias, or strand this card.
- Stripe's renewal-invoice email is suppressed for auto-renew automatic subs (`EmailManager.php:504-510`) — a negative check, not a bug.
- Sessions: `cust-adm07-stripe-SLT-ADM-07`, `cust-adm07-paddle-SLT-ADM-07`.

## Test data
| Item | Value |
|---|---|
| Stripe | S_FEE, $9.00 renewal, card `4242 4242 4242 4242` |
| Paddle | SUB_PAD, $11.00 renewal, sandbox overlay |
| Amounts | USD, no tax/fee row |

## Steps
### Phase A — D3 (2026-08-05), after 12:00 and before SLT-EML-12

0. In `cust-adm07-stripe-SLT-ADM-07`, log in as `slt-core`. Verify both `/cart/` and the customer's persistent-cart meta are empty. Resolve the registry's exact `SLT Signup Fee Daily` product ID, record `SUBCOUNT_PRE=<exact current SLT subscription count>`, and prove `slt-core` has no subscription for it. If the source product/unique-buyer preflight fails, write a standalone plan-execution issue under `issues/` with the full task/fixture/context/proof fields, close the session, mark both later gateway legs `UNVERIFIED (no S_FEE fixture)`, and move this execution card through review to done rather than stranding it.
1. Save `M_CREATE=$(mailpit-agent latest-id 2>/dev/null || true)`. Open the block checkout with only `SLT Signup Fee Daily`; if one-click redirects there automatically, record it and continue. Explicitly select Stripe, require the total to be **$24.00** (`$9.00` first cycle + one `$15.00` signup fee), with no tax row, and capture `SLT-ADM-07-00-create.png` before card entry. Pay with `4242 4242 4242 4242`, `12/34`, CVC `123` without capturing the populated hosted frame.
2. Capture the safe receipt as `SLT-ADM-07-00a-created-receipt.png` and record numeric shell variable `ORDER_FEE`. Resolve that order's exact `_subscription_ids` JSON through a strict one-element numeric `jq -e` guard and assign it to `S_FEE`; abort unless both variables match `^[0-9]+$`. Cross-check numeric `$S_FEE` against reverse `_parent_order_id=$ORDER_FEE`, the recorded slt-core customer/product IDs, and `SUBCOUNT_POST == SUBCOUNT_PRE + 1`. Require the subscription active with `_recurring_amount=9.00`, `_completed_payments=1`, and the initial order total `$24.00`. Record `_next_payment_date`; compute `k` without interpolating an alias (`php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("k=%ds\n",$h%21600);' "$S_FEE"`), and derive the D4 invoice window `[due+k-6h, due+k]` in both UTC and site time.
3. Reconcile the complete initial purchase delta after `M_CREATE` and require the exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs. Empty the cart, verify persistent-cart meta empty, then append `ORDER_FEE`, `S_FEE`, count delta, due, `k`, both exact action IDs/times, `invoice−5m`/`charge−5m`, the four mail IDs, and the future D4 manual-payment exception to `slt-catalog-registry` and the D03 watch report. Close the Stripe session and leave this card `in-progress`.

### Phase B — D4 (2026-08-06), inside each real invoice window

4. Resolve registry alias `S_FEE` into a same-named numeric shell variable and re-check its order/customer/product relationship. Resolve `SUB_PAD` only if the CHK-04 handoff exists; otherwise record the cited `UNVERIFIED` Paddle branch and continue Stripe without aborting. Recompute each available target's `k` and window. No earlier than five minutes before S_FEE's exact invoice action, publish `FEE_INVOICE_PRE=$(mailpit-agent latest-id)`; after it runs, resolve numeric `R_FEE` from `_pending_renewal_order_id`, require reverse `_subscription_id=$S_FEE` plus the exact scheduled-date/cycle metadata, and inspect the bounded delta for no `renewal_invoice`. For available Paddle, use the same exact relationship/gate procedure and `PAD_INVOICE_PRE`; never select either renewal order by recency.
5. Inside the S_FEE window, reopen `cust-adm07-stripe-SLT-ADM-07`, log in at `https://mirror-help.arrayhash.com/my-account/` as `slt-core`, open `/my-account/view-subscription/$S_FEE/`, and capture `SLT-ADM-07-01-unpaid.png` showing exact `R_FEE` as `Pending payment` with its **Pay** button.
6. Click **View** on exact `R_FEE`; record `SLT Signup Fee Daily x 1` at `$9.00`, total `$9.00`, confirm no `Subscription Signup Fee` or tax row, and capture `SLT-ADM-07-02-totals.png`.
7. Click **Pay**; record the order-pay URL (`/checkout/order-pay/<R_FEE>/?pay_for_order=true&key=…`), offered methods, and capture the unpopulated page as `SLT-ADM-07-03-pay.png`. Immediately before submitting payment save `STRIPE_PAY_PRE=$(mailpit-agent latest-id)`. Pay with the test card without capturing the populated frame; capture the safe receipt as `SLT-ADM-07-04-paid.png`.
8. Require `mailpit-agent wait-new "$STRIPE_PAY_PRE" 180 "Payment received for subscription #$S_FEE"`; save/show the exact match and classify every message newer than `STRIPE_PAY_PRE`, including only WooCommerce mail linked to `R_FEE`.
9. `wp db query "SELECT id,status,total_amount FROM wp_wc_orders WHERE id=$R_FEE" --allow-root`; `wp post meta list "$S_FEE" --keys=_next_payment_date,_completed_payments,_pending_renewal_order_id --allow-root`.
10. If `SUB_PAD` is available, open `/my-account/view-subscription/$SUB_PAD/` as `--session cust-adm07-paddle-SLT-ADM-07` (login `slt-paddle`) and capture `SLT-ADM-07-05-paddle.png` showing exact `R_PAD` plus the note "awaiting automatic charge from Paddle". Otherwise skip only the Paddle assertions in steps 10-11 under the recorded fallback; still execute step 12's final review immediately after Stripe closes, without waiting for a nonexistent D5 Paddle check.
11. For available `R_PAD`, open its detail/pay flow; total must read `$11.00`, with no fee or tax. Capture the unpopulated pay state as `SLT-ADM-07-06-paddle-pay.png`. Immediately before submitting save `PADDLE_PAY_PRE=$(mailpit-agent latest-id)`, complete the Paddle sandbox overlay without capturing populated card fields, and capture only the safe paid result as `SLT-ADM-07-07-paddle-paid.png`. Require `mailpit-agent wait-new "$PADDLE_PAY_PRE" 180 "Payment received for subscription #$SUB_PAD"`; save/show the exact match and classify the complete delta, allowing only WooCommerce mail linked to `R_PAD`. Record any Paddle overlay console error.
12. Deferred watch check (24 h): close both exact customer sessions after D4, then at the D5 deadline reopen only the available Paddle session and re-check exact `R_PAD`/`SUB_PAD`. A second paid or retroactive order for the SAME cycle means Paddle charged twice: write a standalone issue under `issues/` with task/plan path, exact order/subscription/user/gateway/URL context, steps, expected/actual, HPOS/Paddle/mail proof, and the successful Stripe counterexample. Close the session, independently review all available Phase A/B/deferred evidence, and move this card through review to done; the no-source Paddle branch is `UNVERIFIED`, not a blocker.

## Expected results
1. R_FEE and R_PAD exist unpaid inside the predicted window with a **Pay** button that disappears once paid.
2. R_FEE detail: one line, qty 1, `$9.00`; total `$9.00`; no signup-fee line (that $15.00 cart fee is charged only at signup) and no tax row.
3. After paying R_FEE: status Processing/Completed, `total_amount = 9.00`; on S_FEE `_pending_renewal_order_id` deleted, `_last_payment_date` set, `_completed_payments` +1, `_next_payment_date` +1 day from `_renewal_scheduled_date`, not from the pay time.
4. The later `arraysubs_process_renewal` no-ops (`isRenewalDue()` false); no second order for the same `_renewal_cycle_number`.
5. R_PAD reaches a paid status at `$11.00` with `_paddle_transaction_id` set.
6. One `payment_successful` per leg plus WooCommerce's order mail; no renewal-invoice mail on Stripe.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` + admin counterpart | Phase A initial order paid | slt-core + admin | `is active` / `New subscription #` | list after `M_CREATE` |
| 2 | WooCommerce initial-order pair | Phase A initial order paid | slt-core + admin | order subject | list after `M_CREATE` |
| 3 | `payment_successful` | R_FEE paid (step 7) | slt-core | `Payment received for subscription #<S_FEE>` | exact wait after `STRIPE_PAY_PRE`; save/show match and full delta |
| 4 | WooCommerce order mail | R_FEE paid | slt-core | order number `R_FEE` | correlate inside the `STRIPE_PAY_PRE` delta |
| 5 | `payment_successful` | R_PAD paid (step 11) | slt-paddle | `Payment received for subscription #<SUB_PAD>` | exact wait after `PADDLE_PAY_PRE`; save/show match and full delta |
| 6 | `renewal_invoice` NONE EXPECTED | automatic invoice creation | — | `Invoice for subscription` | No such subject in the bounded `FEE_INVOICE_PRE` or available `PAD_INVOICE_PRE` delta |

## Evidence to capture
- Screenshots `SLT-ADM-07-00-create.png`, `-00a-created-receipt.png`, `-01-unpaid.png`, `-02-totals.png`, `-03-pay.png`, `-04-paid.png`, `-05-paddle.png`, `-06-paddle-pay.png`, `-07-paddle-paid.png`; no image may contain a full card number.
- Order/subscription IDs, count delta, `k` values, `M_CREATE`, `FEE_INVOICE_PRE`, optional `PAD_INVOICE_PRE`, `STRIPE_PAY_PRE`, `PADDLE_PAY_PRE`, exact-match/full-delta Mailpit IDs, gateway transaction IDs.

## Pass criteria
- [x] Phase A creates exactly one `S_FEE` for `slt-core`; initial total is $24.00 and its ids/schedule are registered
- [x] Phase A exact +1 HPOS relationship and complete four-message checkout set proved; D4 invoice/charge deadlines handed off before session closure
- [ ] Unpaid renewal order with a Pay button on both gateways
- [ ] Totals exact ($9.00, $11.00); no fee row, no tax row
- [ ] Pay link URL correct; settles the invoice on Stripe and on Paddle
- [ ] Schedule advanced from `_renewal_scheduled_date`; no duplicate order
- [ ] `payment_successful` per leg; no Stripe renewal-invoice mail

## Isolation / teardown
- Paying inside the invoice window moves each charge forward by at most 6 h; cadence unchanged. Post the paid timestamps to the registry so the watch does not misread them as early renewals.
- Touches only S_FEE, available SUB_PAD, and their orders; no setting changed. Empty both carts, verify both persistent-cart metas are empty, and close only `cust-adm07-stripe-SLT-ADM-07` and `cust-adm07-paddle-SLT-ADM-07` after each dated leg.

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

## D03 Phase A checkpoint — 2026-08-05

**PHASE A PASS; FUTURE D4/D5 LEGS ARMED.** Exact preflight proved user `347` (`slt-core`) had no subscription for published product `12577`, and both browser/persistent carts were empty. The real product-page one-click flow redirected to block checkout. With the masked saved Stripe test method selected, the page showed recurring USD `$9.00`, one `Subscription Signup Fee` of `$15.00`, exact total `$24.00`, and no tax row. No populated payment frame or full card number was captured.

Completed `ORDER_FEE=12654` links exactly to sole `S_FEE=12655`; the reverse customer/product/parent relationship also passes. Recorded counters moved subscriptions `369 -> 370`, all HPOS rows `578 -> 579`, and HPOS shop orders `564 -> 565`. Order `12654` is `wc-completed`, USD `$24.00`, Stripe, one product-`12577` line at USD `$9.00`, one fee at USD `$15.00`, and zero tax/coupons. Subscription `12655` is `arraysubs-active`, recurring USD `$9`, one completed payment, start `2026-08-05 10:44:10Z`, last payment `10:44:29Z`, and `NPD=2026-08-06 10:44:10Z`.

With `k=2111s`, final pending invoice action `14860` is due `2026-08-06 05:19:21Z` (`11:19:21` site) and final pending charge action `14861` is due `11:19:21Z` (`17:19:21` site); synchronized originals `14858`/`14859` are canceled. Capture `FEE_INVOICE_PRE` only inside `[2026-08-06 05:14:21Z, 05:19:21Z)`, then let the invoice run naturally and manually pay its exact relationship-linked order before the charge gate. Never force either action. This planned manual payment is registered as a D4 watch exception.

The exact consecutive delta after `M_CREATE=50GJjz3ekgXoIfje5d6UwY` is customer completed-order `2TtbOh5TSYJeNmspd35IGu`, admin new-order `3DK0SUDCNVP2rnNPt78lXO`, customer active-subscription `69BAOmc5jTo2tfvKVoJyoA`, and admin new-subscription `3ZCeSaTCACAsOHVebKoABv`. The final browser/persistent carts are empty. Browser page errors were empty; only the already-known WooCommerce dependency warning appeared. The exact Stripe session was closed after publication; no Paddle session was opened.

Evidence: `/home/server-manager/slt-evidence/SLT-ADM-07-D03-armed-facts.txt`, `SLT-ADM-07-00-create.png`, `SLT-ADM-07-00a-created-receipt.png`, and supplemental `SLT-ADM-07-00b-cart-empty.png`. Card remains in progress and unclaimed for Phase B inside the exact D4 invoice window and its D5 duplicate-charge read.

[[2026-08-05]] Wed 14:35
Board hygiene checkpoint: no actionable step is available right now; the current body already records the exact next gate/window and required natural-watch constraints. Parking this future-gated card back in todo so In Progress reflects only currently active execution.

[[2026-08-05]] Wed 14:45
D03 watcher correction: the 18:35 board-hygiene note is superseded; this future-gated card remains in-progress. D4 invoice baseline 05:14:21Z-05:19:20Z, let action 14860 run naturally, perform the authored manual pay before action 14861 at 11:19:21Z, then retain for D5 read.

[[2026-08-05]] Wed 15:01
Board hygiene checkpoint: parked future-gated or watch-only work returned to todo so in-progress reflects only actively worked cards.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Next gate D4: invoice baseline 2026-08-06 05:14:21Z-05:19:20Z (11:14:21-11:19:20 site), natural action 14860 at 05:19:21Z, then authored manual payment before charge 14861.

[[2026-08-05]] Wed 16:30
Registry publication verified on 2026-08-05. Exact next gate remains D4 invoice baseline 2026-08-06 05:14:21Z-05:19:20Z / 11:14:21-11:19:20 site, then natural invoice action 14860 and manual pay before charge 14861.

[[2026-08-05]] Wed 16:41
D4 invoice/payment follow-up: capture FEE_INVOICE_PRE only inside 2026-08-06 05:14:21Z-05:19:20Z for natural invoice action 14860 at 05:19:21Z.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
D4 invoice baseline 2026-08-06 05:14:21Z-05:19:20Z; action 14860 at 05:19:21Z.

[[2026-08-05]] Wed 17:44
D4 invoice baseline 2026-08-06 05:14:21Z–05:19:20Z; observe natural invoice action 14860, then authored payment before charge 14861.

[[2026-08-06]] Thu 20:15
Late carry-forward note: at local 2026-08-06 20:14 CEST / site 2026-08-07 00:14 +06, the authored D4 Stripe manual-pay window was already gone. D4 evidence now proves only the natural Stripe renewal counterexample (order 12894, mail 55o2Ey8570ZJEXMs3w4Mgv); the Paddle pay-link branch was not executed. Keep in todo for an explicit closeout decision, not as active D4 work.

[[2026-08-06]] Thu 20:33
UNVERIFIED closeout on 2026-08-06: the authored D4 Stripe manual-pay window and optional Paddle pay-link leg were missed after the site-local rollover into 2026-08-07. Preserve the existing Phase A and natural Stripe counterexample evidence; do not keep this card open as if the original same-cycle pay-link assertions were still executable.
