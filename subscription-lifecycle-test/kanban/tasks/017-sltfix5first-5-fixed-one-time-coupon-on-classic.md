---
id: 17
title: SLTFIX5FIRST $5 fixed one-time coupon on classic checkout - first order discounted, first renewal at full price
status: done
priority: critical
created: 2026-08-02T03:43:04.505709602+02:00
updated: 2026-08-05T21:37:49.318769416+02:00
started: 2026-08-05T17:55:08.288565135+02:00
completed: 2026-08-05T17:55:08.288565135+02:00
tags:
    - checkout
    - day-01
due: "2026-08-03"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 5
class: standard
---

> **SLT-CPN-02** · group `checkout` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove `SLTFIX5FIRST` (fixed cart $5.00, apply-to-subs yes, duration `one-time`) discounts only the classic-checkout parent order and that the FIRST scheduled renewal is charged at full list price: `applyRecurringCoupons()` exits at its `'recurring' !== $discount_type` gate, so no `Recurring Discount:` fee may ever appear on a renewal here.

## Scope
- Gateway: Stripe test
- Checkout: classic (`/slt-classic-cart` + `/slt-classic-checkout`)
- Account: new registered (this task CREATES `slt-cpnfirst`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-SETUP-04 (`SLTFIX5FIRST`), SLT-PROD-01 (`SLT Daily Core` $10.00 day/1) done.
- CREATES `slt-cpnfirst` / `slt-cpnfirst@example.test`, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4. Fresh account is mandatory so this coupon has one unambiguous subscription; `auto_migrate_on_checkout` is inert while `one_per_customer=false`.
- Check out **18:00-19:00 site (UTC+6)**: satisfies C02 and keeps the invoice leg (`due+k-6h` => 12:00-19:00 site) clear of SLT-SYN-04's D3 09:00-11:00 bracket.
- $10.00 product vs $5.00 coupon is deliberate: the order must stay above $0.00 so the real Stripe charge runs, not `PaymentProcessor`'s zero-total short-circuit.
- Sessions `admin-SLT-CPN-02` / `customer-SLT-CPN-02` exclusive to this task (C09).

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, $10.00, day/1 |
| Account | slt-cpnfirst (created here) |
| Coupon | SLTFIX5FIRST - fixed cart 5.00, one-time |
| Card | 4242 4242 4242 4242 |
| Amounts | first order $5.00; renewal #1 and later $10.00 |

## Steps
1. Record `SUBCOUNT_BEFORE=<exact current SLT subscription count>` and `U0=$(mailpit-agent latest-id)`, then in `admin-SLT-CPN-02` create `slt-cpnfirst` (**Send User Notification** unticked), fill billing, and record the numeric user ID. Use `mailpit-agent wait-new "$U0" 60 "New User Registration"` and classify exactly one admin-only setup message; verify no account/password message was sent to `slt-cpnfirst@example.test`. Open the coupon; confirm **Discount duration** = `One-time (initial order only)` and expiry `2026-08-15`; capture `SLT-CPN-02-01-coupon-settings.png`; record coupon ID.
2. After the setup mail is fully classified, `M0=$(mailpit-agent latest-id)`; this is the checkout-mail baseline. Also record `ORDERCOUNT_BEFORE` from the HPOS `wp_wc_orders` `shop_order` rows and set `SUBMIT_AT_GMT` immediately before the payment click.
3. In `customer-SLT-CPN-02`, log in as `slt-cpnfirst`; open `/slt-classic-cart`, confirm both the classic browser cart and persistent cart empty, and capture `SLT-CPN-02-01b-cart-empty-before.png`; add SLT Daily Core. The frozen `checkout.one_click_mode=subscription_items` may redirect the add action to block `/checkout/`; if it does, require the $10.00 summary there, then reopen `/slt-classic-cart` and require the single SLT Daily Core row and $10.00 subtotal before applying the coupon. Do not treat the healthy redirect as a classic-cart failure.
4. Type `SLTFIX5FIRST` in **Coupon code** -> **Apply coupon**; re-snapshot and capture `Coupon: sltfix5first` **-$5.00**, Total **$5.00** as `SLT-CPN-02-02-classic-cart-5.00.png`.
5. Open `/slt-classic-checkout`; confirm the review table repeats Total **$5.00** with the coupon row, and capture `SLT-CPN-02-03-checkout-review.png` before any card entry.
6. Select Stripe, enter the hosted test-card fields without capturing them, and click **Place order exactly once**. Poll for order-received in calls of at most 60 seconds through a two-minute cutoff. A browser wait timeout is not a failed payment: never click Place order again while the outcome is ambiguous. Instead inspect the checkout network request, reload once if needed, and query HPOS for this exact customer, `date_created_gmt >= SUBMIT_AT_GMT`, total `5.00`, and coupon before deciding whether the single submit failed; any matching order or successful/in-flight checkout request forbids a retry. Record numeric `PARENT_ORDER`, require `ORDERCOUNT_AFTER == ORDERCOUNT_BEFORE + 1`, and capture `SLT-CPN-02-04-order-received.png`. Read its exact linkage with `LINK_JSON=$(wp post meta get "$PARENT_ORDER" _subscription_ids --format=json --allow-root)` and resolve `SUB_ID` only through a strict one-element numeric `jq -e` guard. Cross-check exactly one reverse `_parent_order_id=$PARENT_ORDER`, the numeric user/product, and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE + 1`; never use `WC_Order::get_meta('_subscription_ids')` or select by recency on this HPOS runtime.
7. `wp post meta list "$SUB_ID" --allow-root | rg '_coupon|_next_payment_date'`; copy the capture note verbatim.
8. Compute k with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("offset=%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));' "$SUB_ID"`; query the exact pending invoice/charge rows for numeric `SUB_ID`, and record their IDs plus `due+k−6h` and `due+k` gates.
9. Poll `mailpit-agent wait-new "$M0" 60 "is active"` in calls of at most 60 seconds through the two-minute cutoff; record the complete checkout-mail delta. In the same customer session open `/slt-classic-cart`, require the post-checkout cart to be EMPTY, and capture `SLT-CPN-02-04b-cart-empty-after.png` before closing the current-day leg.
10. Before closing the D1 leg, append the user/coupon/parent-order/`SUB_ID`, exact pending invoice and charge action IDs/times, and the latest safe `REN1_PRE` capture deadline (`charge−5m`) to `slt-catalog-registry` and the D01 watch report. Close both task-keyed sessions. Leave the card `in-progress` with that exact D2 gate; do not defer this fixture handoff until after the renewals.
11. Store `REN1_PRE=$(mailpit-agent latest-id)` only inside `[renewal #1 exact charge gate−300s, gate)` and persist it before the gate. **2026-08-05 after 09:00 site** (renewal #1 charged the evening of 08-04): resolve numeric `REN1` from the exact subscription/scheduled-cycle relationship and require its reverse link, never recency. In `admin-SLT-CPN-02-R1`, capture its exact item table as `SLT-CPN-02-05-renewal1-full-price.png`, run `wp eval '$o=wc_get_order((int)$argv[1]);echo count($o->get_fees())."|".implode(",",$o->get_coupon_codes())."|".$o->get_total();' "$REN1" --allow-root`, poll immutable `REN1_PRE` in ≤60-second calls through the 10-minute cutoff, reconcile every newer message, and close only that R1 session.
12. Store `REN2_PRE` only inside renewal #2's final-five-minute interval and repeat on 2026-08-06 with relationship-exact numeric `REN2` in `admin-SLT-CPN-02-R2`, using ≤60-second polling and complete-delta reconciliation. Append both renewal order/action/baseline/mail IDs to the registry and daily reports. Any live failure goes only in `issues/SLT-CPN-02-<concise-slug>.md` with task/stage/plan; coupon/product/parent/renewal/subscription/action IDs; user ID/login/email/role; exact URLs/sessions/gates; reproduction; expected/actual; UI/meta/order/Mailpit/screenshot proof; and the recurring-coupon counterexample. If the misleading one-time note appears, use that standalone file contract and never add a kanban bug card. Close only the R2 session, independently review the D1/D3/D4 evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Classic cart and classic checkout both show Total **$5.00**, discount row **-$5.00**, no tax line.
2. Parent order total **$5.00**, status processing/completed, coupon `sltfix5first` on the order.
3. Metas: `_coupon_code=sltfix5first`, `_coupon_discount_type=one-time`, `_coupon_discount_amount=5.00`, `_coupon_discount_percent=0`, `_coupon_wc_discount_type=fixed_cart`, `_coupon_original_cycles=0`, `_coupon_remaining_cycles=0`, `_coupon_count_initial=no`, `_coupon_initial_cycle_pending` ABSENT.
4. Capture note: `Coupon "sltfix5first" captured from checkout order. Duration: one-time (initial order only). Discount: $5.00 off per eligible renewal.` The trailing **"per eligible renewal" is wrong for a one-time coupon** - record verbatim and, if observed, write a separate issue file under `issues/`; do not create a lifecycle-board bug card. Behaviour is still correct.
5. Renewal #1 (08-04): subtotal $10.00, total **$10.00**, **zero** fee items, **zero** coupon codes.
6. Renewal #2 (08-05): total **$10.00**, same shape.
7. No recurring-discount or cycle note ever added to the subscription.
8. Status stays `arraysubs-active`; `_next_payment_date` advances exactly 1 day per renewal, anchored on `_renewal_scheduled_date`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 0 | WordPress admin new-user notice (expected setup mail) | admin user creation | admin_email only | `New User Registration` | `mailpit-agent wait-new "$U0" 60 "New User Registration"`; prove no message to `slt-cpnfirst@example.test` |
| 1 | new_subscription | parent paid | slt-cpnfirst@ | `is active` | repeated `mailpit-agent wait-new "$M0" 60 "is active"` calls through the two-minute cutoff |
| 2 | admin_new_subscription | parent paid | admin_email | `New subscription #` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 3 | WC New order | parent paid | admin_email | `New order #<PARENT_ORDER>` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 4 | WC Completed order | paid virtual checkout | slt-cpnfirst@ | `is on its way` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 5 | payment_successful | each renewal paid | slt-cpnfirst@ | exact numeric `Payment received for subscription #$SUB_ID` | final-five-minute `REN1_PRE`/`REN2_PRE`, repeated ≤60-second waits, and complete deltas |
| 6 | renewal_invoice **NONE EXPECTED** | invoice leg | - | - | suppressed for auto-payment auto-renew subs (SLT-REF-04) |

## Evidence to capture
- Screenshots `SLT-CPN-02-01-coupon-settings.png`, `-01b-cart-empty-before.png`, `-02-classic-cart-5.00.png`, `-03-checkout-review.png`, `-04-order-received.png`, `-04b-cart-empty-after.png`, `-05-renewal1-full-price.png`.
- Coupon/user/subscription ID, count delta and bidirectional parent linkage, parent + 2 relationship-exact renewal order IDs, k/action gates, verbatim note, meta/`wp eval` dumps, admin-only setup-mail ID, all four checkout-message IDs, renewal Mailpit IDs, session/review proof, and checkout console/network errors.

## Pass criteria
- [ ] Classic cart and classic checkout both total $5.00
- [ ] Exactly one admin-only `New User Registration` setup mail and no customer account/password mail
- [ ] Complete four-message paid-checkout set recorded: WC new-order, WC completed-order, and both ArraySubs signup messages
- [ ] Parent order links bidirectionally to the sole +1 subscription; exact future actions/deadlines handed off
- [ ] Customer cart proved empty before checkout and again after checkout
- [ ] `_coupon_discount_type=one-time`, `_coupon_discount_amount=5.00`, `_coupon_discount_percent=0`
- [ ] Capture note verbatim; if the misleading wording is observed, its standalone issue file exists under `issues/`
- [ ] Renewal #1 exactly $10.00 with zero fees and zero coupons
- [ ] Renewal #2 exactly $10.00; no recurring-discount note ever added
- [ ] payment_successful per renewal; zero renewal_invoice mail
- [ ] Exact sessions closed and final D4 evidence reviewed to done with Review empty

## Isolation / teardown
- Watch assertion D3..D9: every `slt-cpnfirst` renewal is $10.00 with no fee line - the direct counterexample to SLT-CPN-01. A discounted renewal here is a defect.
- Leaves `slt-cpnfirst` + one active daily subscription; add the subscription to SLT-SETUP-99A's cancel list and the account to SLT-SETUP-99B's deletion list. Nothing global changed. Close `admin-SLT-CPN-02` and `customer-SLT-CPN-02` only.

## D01 execution handoff (2026-08-03)

- Parent `12331` links exactly and bidirectionally to sole subscription `12332`; subscription count is `366 -> 367`, HPOS shop-order count `543 -> 544`, and user `362` owns only `12332`.
- Classic cart, checkout, and receipt prove coupon `sltfix5first`, discount `-$5.00`, and total `$5.00`. Exact four-message set: `31AFTGijF7XijCMZnsLwmt`, `6TYILieHiiDmsMSWULDuyp`, `1mZMnuPHnnZbZ4CzGyUVAf`, `3scBdxmEgq298byftBwV3Q`.
- Product finding: subscription coupon meta count `0` and coupon-note count `0`; full standalone context is in `issues/critical-plugin-SLT-CPN-02-one-time-coupon-capture-missing.md`. No kanban bug card was created.
- `k=12625`; invoice `14106` at `2026-08-04 15:51:37` site, charge `14107` at `21:51:37` site. Capture `REN1_PRE` only in `[21:46:37,21:51:37)` site; never force.
- Full D1 proof: `/home/server-manager/slt-evidence/SLT-CPN-02-D01-facts.txt`. Card correctly remains `in-progress` for D3/D4 renewal evidence.

## D3 renewal-1 checkpoint (2026-08-05)

**R1 PASS; R2 remains naturally armed for later today.** Invoice action `14106` and charge action `14107` ran unattended through WP Cron. The charge started at `2026-08-04 15:52:04Z`, 27 seconds after its gate, and completed at `15:52:13Z`. Relationship-exact cycle-2 order `12429` completed for the full USD `$10.00`, zero tax, zero fee items, and `get_coupon_codes()=[]`.

Subscription `12332` remains active with `_completed_payments=2` and next due `2026-08-05 12:21:12Z`. Coupon-related subscription meta remains absent as already recorded in `issues/critical-plugin-SLT-CPN-02-one-time-coupon-capture-missing.md`; R1 added no recurring-discount or coupon-cycle note. The exact natural mail pair is admin order `3NYhutsKZpT6wE8nkmphqE` plus customer payment success `2v8RP3qSZt1Mdi75zk4Re5`; an owner/subject sweep found no renewal-invoice mail.

The required `REN1_PRE` cursor was missed contemporaneously. Mailpit chronology proves `0MehjgCWvkh0qQXtdxG5QX` was the immediately preceding message and only the two task-owned messages followed through the gate; this is explicitly a post-hoc reconstruction.

Evidence: `/home/server-manager/slt-evidence/SLT-CPN-02-R1-facts.txt` and required screenshot `SLT-CPN-02-05-renewal1-full-price.png`. Browser page errors were empty.

Timed handoff: capture `REN2_PRE` only in `[2026-08-05 15:46:37Z, 15:51:37Z)` (`[21:46:37, 21:51:37)` site), then observe charge action `14547` naturally. Do not force it. Keep this card in progress until relationship-exact R2, its immutable mail delta, and independent Review-to-Done audit are complete.


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

[[2026-08-05]] Wed 09:01
Checkpoint handoff: relationship-exact R1 passed and is documented in SLT-CPN-02-R1-facts.txt. Capture REN2_PRE only during 2026-08-05 15:46:37Z-15:51:36Z; action 14547 must run naturally after 15:51:37Z. Claim released while waiting.

[[2026-08-05]] Wed 14:35
Board hygiene checkpoint: no actionable step is available right now; the current body already records the exact next gate/window and required natural-watch constraints. Parking this future-gated card back in todo so In Progress reflects only currently active execution.

[[2026-08-05]] Wed 14:45
D03 watcher correction: the 18:35 board-hygiene note is superseded; this future-gated card remains in-progress. Capture REN2_PRE today only 15:46:37Z-15:51:36Z, then observe natural action 14547 after 15:51:37Z.

[[2026-08-05]] Wed 15:01
Board hygiene checkpoint: parked future-gated or watch-only work returned to todo so in-progress reflects only actively worked cards.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Tonight capture REN2_PRE only 2026-08-05 15:46:37Z-15:51:36Z (21:46:37-21:51:36 site), then observe natural action 14547; never force.

[[2026-08-05]] Wed 16:41
D3 R2 follow-up remains open for the next phase: capture baseline 2026-08-05 15:46:37Z-15:51:36Z for natural action 14547 at 15:51:37Z.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
D3 R2 next phase baseline 2026-08-05 15:46:37Z-15:51:36Z; action 14547 at 15:51:37Z.

[[2026-08-05]] Wed 17:49
Captured immutable REN2_PRE=2i0R3FNNwSKttHCGnF3brU at 2026-08-05 15:48:46Z inside the required final-five-minute window. Action 14547 remained pending and unattempted for relationship-owned subscription 12332 / prior renewal order 12429; natural charge is still due at 2026-08-05 15:51:37Z.

[[2026-08-05]] Wed 17:55
R2 complete on 2026-08-05. Immutable REN2_PRE=2i0R3FNNwSKttHCGnF3brU was captured at 2026-08-05 15:48:46Z; action 14547 completed naturally at 2026-08-05 15:52:18Z. Relationship-exact cycle-3 renewal order 12612 completed for USD 10.00 with zero fees and coupons []; subscription 12332 advanced to _completed_payments=3 and _next_payment_date=2026-08-06 12:21:12. Exact post-baseline delta is only admin new-order 0B8SUdM4iGj1JuzJJHFTPz and customer payment-success 5l3oNzrhZqwMtZ2pU1ed2j; no renewal_invoice mail appeared. Evidence: /home/server-manager/slt-evidence/SLT-CPN-02-R2-facts.txt.
