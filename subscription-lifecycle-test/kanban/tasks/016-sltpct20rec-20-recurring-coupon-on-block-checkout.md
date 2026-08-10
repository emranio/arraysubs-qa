---
id: 16
title: SLTPCT20REC 20% recurring coupon on block checkout - discounted first charge, discount persists to every renewal
status: done
priority: critical
created: 2026-08-02T03:43:04.433753843+02:00
updated: 2026-08-05T21:37:49.31375221+02:00
started: 2026-08-05T18:29:55.148131661+02:00
completed: 2026-08-05T18:29:55.148131661+02:00
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

> **SLT-CPN-01** · group `checkout` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove `SLTPCT20REC` (percent 20, apply-to-subs yes, `recurring`, cycles 0 = unlimited) discounts the block-checkout first charge AND every unattended renewal, the renewal discount arriving as a negative FEE named `Recurring Discount: sltpct20rec` (`CouponTracking\Services\Hooks::applyRecurringCoupons` on `arraysubs_renewal_invoice_created`), not a coupon line.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered (this task CREATES `slt-cpnrec`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-SETUP-04 (`SLTPCT20REC`), SLT-PROD-01 (`SLT Daily Core` $10.00 day/1) done.
- Coupon expiry must be `2026-08-15`, not `2026-08-12` or `2026-08-14`: WooCommerce stores the date at site-local midnight, and `applyRecurringCoupons()` returns silently once `time() > date_expires`. Fix and record if wrong.
- CREATES `slt-cpnrec` / `slt-cpnrec@example.test`, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4. Fresh account is mandatory so this coupon has one unambiguous subscription; at `one_per_customer=false`, repeat checkout creates a duplicate rather than migrating.
- Check out **18:00-19:00 site (UTC+6)**: satisfies C02 and keeps the invoice leg (`due+k-6h` => 12:00-19:00 site) clear of SLT-SYN-04's D3 09:00-11:00 bracket.
- Sessions `admin-SLT-CPN-01` / `customer-SLT-CPN-01` exclusive to this task (C09).

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, $10.00, day/1 |
| Account | slt-cpnrec (created here) |
| Coupon | SLTPCT20REC - percent 20, recurring, cycles 0 |
| Card | 4242 4242 4242 4242 |
| Amounts | first charge $8.00; every renewal $8.00 ($10.00 - $2.00 fee) |

## Steps
1. Record `SUBCOUNT_BEFORE=<exact current SLT subscription count>` and `U0=$(mailpit-agent latest-id)`, then in `admin-SLT-CPN-01`: `user-new.php` -> create `slt-cpnrec`, role Customer, **Send User Notification** unticked; fill billing on `user-edit.php` and record the numeric user ID. Use `mailpit-agent wait-new "$U0" 60 "New User Registration"` and classify exactly one admin-only setup message; verify no account/password message was sent to `slt-cpnrec@example.test`. Open the coupon, capture the **ArraySubs Subscription Settings** group + expiry as `SLT-CPN-01-01-coupon-settings.png`, and record coupon ID.
2. After the setup mail is fully classified, `M0=$(mailpit-agent latest-id)`; this is the checkout-mail baseline. Also record `ORDERCOUNT_BEFORE` from the HPOS `wp_wc_orders` `shop_order` rows and set `SUBMIT_AT_GMT` immediately before the payment click.
3. In `customer-SLT-CPN-01`, log in as `slt-cpnrec`; open `/cart/`, confirm both browser and persistent carts EMPTY, and capture `SLT-CPN-01-01b-cart-empty-before.png`; add SLT Daily Core from its product page. The frozen `checkout.one_click_mode=subscription_items` must redirect this addition directly to the block `/checkout/`; require the order summary subtotal to be $10.00 and do not treat the healthy redirect as a missing cart assertion.
4. On the resulting `https://mirror-help.arrayhash.com/checkout/` page (page 8, block), expand **Add a coupon** in **Order summary**, enter `SLTPCT20REC`, **Apply**; re-snapshot and capture the $8.00 totals as `SLT-CPN-01-02-block-totals-8.00.png` before entering any card data.
5. Select Stripe, enter the hosted test-card fields without capturing them, and click **Place order exactly once**. Poll for order-received in calls of at most 60 seconds through a two-minute cutoff. A browser wait timeout is not a failed payment: never click Place order again while the outcome is ambiguous. Instead inspect the checkout network request, reload once if needed, and query HPOS for this exact customer, `date_created_gmt >= SUBMIT_AT_GMT`, total `8.00`, and coupon before deciding whether the single submit failed; any matching order or successful/in-flight checkout request forbids a retry. Record numeric `PARENT_ORDER`, require `ORDERCOUNT_AFTER == ORDERCOUNT_BEFORE + 1`, and capture `SLT-CPN-01-03-order-received.png`. Read its exact linkage with `LINK_JSON=$(wp post meta get "$PARENT_ORDER" _subscription_ids --format=json --allow-root)` and resolve `SUB_ID` only through a strict one-element numeric `jq -e` guard. Cross-check exactly one reverse `_parent_order_id=$PARENT_ORDER`, the numeric user/product, and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE + 1`; never use `WC_Order::get_meta('_subscription_ids')` or select by recency on this HPOS runtime.
6. `wp post meta list "$SUB_ID" --allow-root | rg '_coupon|_next_payment_date'`; copy the capture note verbatim.
7. Compute k with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("offset=%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));' "$SUB_ID"`; query the exact pending invoice/charge rows for numeric `SUB_ID`, and record their IDs plus `due+k−6h` and `due+k` gates.
8. Poll `mailpit-agent wait-new "$M0" 60 "is active"` in calls of at most 60 seconds through the two-minute cutoff, then inspect and classify the complete owner-filtered delta after `$M0`. In the same customer session open `/cart/`, require the post-checkout cart to be EMPTY, and capture `SLT-CPN-01-03b-cart-empty-after.png` before closing the current-day leg.
9. Before closing the D1 leg, append the user/coupon/parent-order/`SUB_ID`, exact pending invoice and charge action IDs/times, and the latest safe `REN1_PRE` capture deadline (`charge−5m`) to `slt-catalog-registry` and the D01 watch report. Close both task-keyed sessions. Leave the card `in-progress` with that exact D2 gate; do not defer this fixture handoff until after the renewals.
10. Store `REN1_PRE=$(mailpit-agent latest-id)` only inside `[renewal #1 exact charge gate−300s, gate)` and persist it before the gate. **2026-08-05 after 09:00 site** (renewal #1 charged the evening of 08-04): resolve numeric `REN1` from the exact subscription/scheduled-cycle relationship and require its reverse link, never from customer/order recency. In `admin-SLT-CPN-01-R1`, open exact `REN1`, capture its item table as `SLT-CPN-01-04-renewal1-items.png`, dump fees with `wp eval '$o=wc_get_order((int)$argv[1]);foreach($o->get_fees() as $f){echo $f->get_name()."|".$f->get_total()."\n";}echo "total|".$o->get_total();' "$REN1" --allow-root`, poll `wait-new` on immutable `REN1_PRE` in ≤60-second calls through the 10-minute cutoff, reconcile every newer message, and close only that R1 session.
11. Store `REN2_PRE` only inside renewal #2's final-five-minute interval and repeat on 2026-08-06 with relationship-exact numeric `REN2` in `admin-SLT-CPN-01-R2`; capture `SLT-CPN-01-05-renewal2-items.png`, poll the immutable baseline in ≤60-second calls, and reconcile the complete delta. Append both renewal order/action/baseline/mail IDs to the registry and daily reports, close only the R2 session, independently review the full D1/D3/D4 evidence, move the card through `review` to `done`, and ensure Review returns to zero. Any live failure belongs only in `issues/SLT-CPN-01-<concise-slug>.md`, never in a kanban card, with task/stage/plan; coupon/product/parent/renewal/subscription/action IDs; user ID/login/email/role; exact URLs/sessions/gates; reproduction; expected/actual; UI/meta/order/Mailpit/screenshot proof; and the one-time coupon as counterexample where applicable.

## Expected results
1. Checkout total exactly **$8.00**; no tax line.
2. Metas: `_coupon_code=sltpct20rec` (lowercased by `wc_strtolower`), `_coupon_discount_type=recurring`, `_coupon_original_cycles=0`, `_coupon_remaining_cycles=0`, `_coupon_discount_percent=20`, `_coupon_discount_amount=0`, `_coupon_wc_discount_type=percent`, `_coupon_count_initial=no`, `_applied_coupon_id` set, `_coupon_initial_cycle_pending` ABSENT.
3. Note: `Coupon "sltpct20rec" captured from checkout order. Duration: recurring (unlimited). Discount: 20% off.`
4. `_next_payment_date` = checkout +1 day, same clock time; status `arraysubs-active`.
5. Renewal #1 (08-04): subtotal $10.00, one fee **`Recurring Discount: sltpct20rec`** at **-2.00** (meta `_subscription_coupon=yes`), total **$8.00**, `_is_renewal_order=yes`.
6. Renewal #2 (08-05): identical, total **$8.00** - no decay.
7. `_coupon_remaining_cycles` still 0 after both (`decrementCouponCycles()` exits when original cycles <= 0); no exhaustion note.
8. `get_coupon_codes()` empty on both renewal orders - fee, not coupon.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 0 | WordPress admin new-user notice (expected setup mail) | admin user creation | admin_email only | `New User Registration` | `mailpit-agent wait-new "$U0" 60 "New User Registration"`; prove no message to `slt-cpnrec@example.test` |
| 1 | new_subscription | parent paid | slt-cpnrec@ | `is active` | repeated `mailpit-agent wait-new "$M0" 60 "is active"` calls through the two-minute cutoff |
| 2 | admin_new_subscription | parent paid | admin_email | `New subscription #` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 3 | WC New order | parent paid | admin_email | `New order #<PARENT_ORDER>` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 4 | WC Completed order | paid virtual checkout | slt-cpnrec@ | `is on its way` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 5 | payment_successful | each renewal paid | slt-cpnrec@ | exact numeric `Payment received for subscription #$SUB_ID` | final-five-minute `REN1_PRE`/`REN2_PRE`, repeated ≤60-second waits, and complete deltas |
| 6 | renewal_invoice **NONE EXPECTED** | invoice leg | - | - | suppressed for auto-payment auto-renew subs (SLT-REF-04) |

## Evidence to capture
- Screenshots `SLT-CPN-01-01-coupon-settings.png`, `-01b-cart-empty-before.png`, `-02-block-totals-8.00.png`, `-03-order-received.png`, `-03b-cart-empty-after.png`, `-04-renewal1-items.png`, `-05-renewal2-items.png`.
- Coupon/user/subscription ID, count delta and bidirectional parent linkage, parent + 2 relationship-exact renewal order IDs, k/action gates, meta/`wp eval` dumps, admin-only setup-mail ID, all four checkout-message IDs, renewal Mailpit IDs, session/review proof, and checkout console/network errors.

## Pass criteria
- [ ] Checkout total exactly $8.00
- [ ] Exactly one admin-only `New User Registration` setup mail and no customer account/password mail
- [ ] Complete four-message paid-checkout set recorded: WC new-order, WC completed-order, and both ArraySubs signup messages
- [ ] Parent order links bidirectionally to the sole +1 subscription; exact future actions/deadlines handed off
- [ ] Customer cart proved empty before checkout and again after checkout
- [ ] All ten metas as listed; `_coupon_initial_cycle_pending` absent
- [ ] Capture note verbatim
- [ ] Renewal #1 $8.00 with the named fee at -2.00
- [ ] Renewal #2 $8.00 - persistence past one cycle
- [ ] `_coupon_remaining_cycles` still 0; no exhaustion note; no coupon line on either renewal
- [ ] payment_successful per renewal; zero renewal_invoice mail
- [ ] Exact sessions closed and final D4 evidence reviewed to done with Review empty

## Isolation / teardown
- Watch assertion D3..D9: every `slt-cpnrec` renewal totals $8.00 with the named fee; a $10.00 renewal is a defect.
- Leaves `slt-cpnrec` + one active daily subscription; add the subscription to SLT-SETUP-99A's cancel list and the account to SLT-SETUP-99B's deletion list. Only global change is the coupon expiry fix. Close `admin-SLT-CPN-01` and `customer-SLT-CPN-01` only.

## D01 execution handoff (2026-08-03)

- Canonical parent `12317` links exactly and bidirectionally to sole subscription `12318`; after exact operator-artifact cleanup, count is `365 -> 366` and user `361` owns only `12318`.
- Parent total is `$8.00`; the complete recurring coupon meta set and verbatim capture note pass. Canonical four-message set: `2y7jZKjKwGUFOUK5qGASh8`, `2GRJpUFlotj283NT9ID08E`, `7BcerHSHwTU0WuW10tQZuw`, `78TflwS4SGjdhAZ3xn3Gjl`.
- `k=15597`; invoice `14098` at `2026-08-04 16:27:27` site, charge `14099` at `22:27:27` site. Capture `REN1_PRE` only in `[22:22:27,22:27:27)` site; never force.
- Full D1 proof: `/home/server-manager/slt-evidence/SLT-CPN-01-D01-facts.txt`. Card correctly remains `in-progress` for D3/D4 renewal evidence.

## D3 renewal-1 checkpoint (2026-08-05)

**R1 PASS; R2 remains naturally armed for later today.** Invoice action `14098` and charge action `14099` ran unattended through WP Cron. The charge started at `2026-08-04 16:28:04Z`, 37 seconds after its gate, and completed at `16:28:12Z`. Relationship-exact cycle-2 order `12432` completed for USD `$8.00`: product subtotal `$10.00`, one fee `Recurring Discount: sltpct20rec` at `-$2.00` with `_subscription_coupon=yes`, zero tax, and `get_coupon_codes()=[]`.

Subscription `12318` remains active with `_completed_payments=2`, `_coupon_original_cycles=0`, `_coupon_remaining_cycles=0`, and next due `2026-08-05 12:07:30Z`. The exact natural mail pair is admin order `019JilhzKgpaXvSLGeupos` plus customer payment success `56ocFeowqMe8oB9jx94783`; an owner/subject sweep found no renewal-invoice mail. The required `REN1_PRE` cursor was missed contemporaneously. Mailpit chronology proves `2v8RP3qSZt1Mdi75zk4Re5` was the immediately preceding message and only the two task-owned messages followed through the gate; this is explicitly a post-hoc reconstruction.

Evidence: `/home/server-manager/slt-evidence/SLT-CPN-01-R1-facts.txt` and required screenshot `SLT-CPN-01-04-renewal1-items.png`. Browser page errors were empty.

Timed handoff: capture `REN2_PRE` only in `[2026-08-05 16:22:27Z, 16:27:27Z)` (`[22:22:27, 22:27:27)` site), then observe charge action `14561` naturally. Do not force it. Keep this card in progress until relationship-exact R2, its required screenshot, the immutable mail delta, and independent Review-to-Done audit are complete.


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

[[2026-08-05]] Wed 08:57
Checkpoint handoff: relationship-exact R1 passed and is documented in SLT-CPN-01-R1-facts.txt. Capture REN2_PRE only during 2026-08-05 16:22:27Z-16:27:26Z; action 14561 must run naturally after 16:27:27Z. Claim released while waiting.

[[2026-08-05]] Wed 14:35
Board hygiene checkpoint: no actionable step is available right now; the current body already records the exact next gate/window and required natural-watch constraints. Parking this future-gated card back in todo so In Progress reflects only currently active execution.

[[2026-08-05]] Wed 14:45
D03 watcher correction: the 18:35 board-hygiene note is superseded; this future-gated card remains in-progress. Capture REN2_PRE today only 16:22:27Z-16:27:26Z, then observe natural action 14561 after 16:27:27Z.

[[2026-08-05]] Wed 15:01
Board hygiene checkpoint: parked future-gated or watch-only work returned to todo so in-progress reflects only actively worked cards.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Tonight capture REN2_PRE only 2026-08-05 16:22:27Z-16:27:26Z (22:22:27-22:27:26 site), then observe natural action 14561; never force.

[[2026-08-05]] Wed 16:41
D3 R2 follow-up remains open for the next phase: capture baseline 2026-08-05 16:22:27Z-16:27:26Z for natural action 14561 at 16:27:27Z.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
D3 R2 next phase baseline 2026-08-05 16:22:27Z-16:27:26Z; action 14561 at 16:27:27Z.

[[2026-08-05]] Wed 17:44
D3 R2 follow-up: capture baseline 2026-08-05 16:22:27Z–16:27:26Z; observe natural action 14561 after 16:27:27Z.

[[2026-08-05]] Wed 18:23
Captured immutable REN2_PRE=5l3oNzrhZqwMtZ2pU1ed2j at 2026-08-05 16:22:53Z inside the required final-five-minute window. Action 14561 remained pending and unattempted for subscription 12318; natural charge is still due later today on Wednesday, August 5, 2026, at 2026-08-05 16:27:27Z.

[[2026-08-05]] Wed 18:29
R2 complete on Wednesday, August 5, 2026. Immutable REN2_PRE=5l3oNzrhZqwMtZ2pU1ed2j was captured at 2026-08-05 16:22:53Z; action 14561 completed naturally at 2026-08-05 16:28:19Z. Relationship-exact cycle-3 renewal order 12649 completed for USD 8.00 with fee 'Recurring Discount: sltpct20rec' -2.00 and coupons []. Subscription 12318 advanced to _completed_payments=3 and _next_payment_date=2026-08-06 12:07:30. Exact post-baseline delta is only admin new-order 00o5eUvzeDtBHwxoF2WanL and customer payment-success 49w7VyRvIEpqZBLbMPlyaM; no renewal_invoice mail appeared. Evidence: /home/server-manager/slt-evidence/SLT-CPN-01-R2-facts.txt.
