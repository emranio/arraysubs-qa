---
id: 17
title: SLT2FIX5FIRST $5 fixed one-time coupon on classic checkout - first order discounted, first renewal at full price
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-01
due: "2026-08-24"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 5
class: standard
---

> **SLT-CPN-02** · group `checkout` · scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove `SLT2FIX5FIRST` (fixed cart $5.00, apply-to-subs yes, duration `one-time`) discounts only the classic-checkout parent order and that the FIRST scheduled renewal is charged at full list price: `applyRecurringCoupons()` exits at its `'recurring' !== $discount_type` gate, so no `Recurring Discount:` fee may ever appear on a renewal here.

## Scope
- Gateway: Stripe test
- Checkout: classic (`/slt2-classic-cart` + `/slt2-classic-checkout`)
- Account: new registered (this task CREATES `slt2-cpnfirst`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-SETUP-04 (`SLT2FIX5FIRST`), SLT-PROD-01 (`SLT2 Daily Core` $10.00 day/1) done.
- CREATES `slt2-cpnfirst` / `slt2-cpnfirst@example.test`, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4. Fresh account is mandatory so this coupon has one unambiguous subscription; `auto_migrate_on_checkout` is inert while `one_per_customer=false`.
- Check out **18:00-19:00 site (UTC+6)**: satisfies C02 and keeps the invoice leg (`due+k-6h` => 12:00-19:00 site) clear of SLT-SYN-04's D3 09:00-11:00 bracket.
- $10.00 product vs $5.00 coupon is deliberate: the order must stay above $0.00 so the real Stripe charge runs, not `PaymentProcessor`'s zero-total short-circuit.
- Sessions `admin-SLT-CPN-02` / `customer-SLT-CPN-02` exclusive to this task (C09).

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core, $10.00, day/1 |
| Account | slt2-cpnfirst (created here) |
| Coupon | SLT2FIX5FIRST - fixed cart 5.00, one-time |
| Card | 4242 4242 4242 4242 |
| Amounts | first order $5.00; renewal #1 and later $10.00 |

## Steps
1. Record `SUBCOUNT_BEFORE=<exact current SLT2 subscription count>` and `U0=$(mailpit-agent latest-id)`, then in `admin-SLT-CPN-02` create `slt2-cpnfirst` (**Send User Notification** unticked), fill billing, and record the numeric user ID. Use `mailpit-agent wait-new "$U0" 60 "New User Registration"` and classify exactly one admin-only setup message; verify no account/password message was sent to `slt2-cpnfirst@example.test`. Open the coupon; confirm **Discount duration** = `One-time (initial order only)` and expiry `2026-09-05`; capture `SLT-CPN-02-01-coupon-settings.png`; record coupon ID.
2. After the setup mail is fully classified, `M0=$(mailpit-agent latest-id)`; this is the checkout-mail baseline. Also record `ORDERCOUNT_BEFORE` from the HPOS `wp_wc_orders` `shop_order` rows and set `SUBMIT_AT_GMT` immediately before the payment click.
3. In `customer-SLT-CPN-02`, log in as `slt2-cpnfirst`; open `/slt2-classic-cart`, confirm both the classic browser cart and persistent cart empty, and capture `SLT-CPN-02-01b-cart-empty-before.png`; add SLT2 Daily Core. The frozen `checkout.one_click_mode=subscription_items` may redirect the add action to block `/checkout/`; if it does, require the $10.00 summary there, then reopen `/slt2-classic-cart` and require the single SLT2 Daily Core row and $10.00 subtotal before applying the coupon. Do not treat the healthy redirect as a classic-cart failure.
4. Type `SLT2FIX5FIRST` in **Coupon code** -> **Apply coupon**; re-snapshot and capture `Coupon: sltfix5first` **-$5.00**, Total **$5.00** as `SLT-CPN-02-02-classic-cart-5.00.png`.
5. Open `/slt2-classic-checkout`; confirm the review table repeats Total **$5.00** with the coupon row, and capture `SLT-CPN-02-03-checkout-review.png` before any card entry.
6. Select Stripe, enter the hosted test-card fields without capturing them, and click **Place order exactly once**. Poll for order-received in calls of at most 60 seconds through a two-minute cutoff. A browser wait timeout is not a failed payment: never click Place order again while the outcome is ambiguous. Instead inspect the checkout network request, reload once if needed, and query HPOS for this exact customer, `date_created_gmt >= SUBMIT_AT_GMT`, total `5.00`, and coupon before deciding whether the single submit failed; any matching order or successful/in-flight checkout request forbids a retry. Record numeric `PARENT_ORDER`, require `ORDERCOUNT_AFTER == ORDERCOUNT_BEFORE + 1`, and capture `SLT-CPN-02-04-order-received.png`. Read its exact linkage with `LINK_JSON=$(wp post meta get "$PARENT_ORDER" _subscription_ids --format=json --allow-root)` and resolve `SUB_ID` only through a strict one-element numeric `jq -e` guard. Cross-check exactly one reverse `_parent_order_id=$PARENT_ORDER`, the numeric user/product, and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE + 1`; never use `WC_Order::get_meta('_subscription_ids')` or select by recency on this HPOS runtime.
7. `wp post meta list "$SUB_ID" --allow-root | rg '_coupon|_next_payment_date'`; copy the capture note verbatim.
8. Compute k with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("offset=%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));' "$SUB_ID"`; query the exact pending invoice/charge rows for numeric `SUB_ID`, and record their IDs plus `due+k−6h` and `due+k` gates.
9. Poll `mailpit-agent wait-new "$M0" 60 "is active"` in calls of at most 60 seconds through the two-minute cutoff; record the complete checkout-mail delta. In the same customer session open `/slt2-classic-cart`, require the post-checkout cart to be EMPTY, and capture `SLT-CPN-02-04b-cart-empty-after.png` before closing the current-day leg.
10. Before closing the D1 leg, append the user/coupon/parent-order/`SUB_ID`, exact pending invoice and charge action IDs/times, and the latest safe `REN1_PRE` capture deadline (`charge−5m`) to `slt2-catalog-registry` and the D01 watch report. Close both task-keyed sessions. Leave the card `in-progress` with that exact D2 gate; do not defer this fixture handoff until after the renewals.
11. Store `REN1_PRE=$(mailpit-agent latest-id)` only inside `[renewal #1 exact charge gate−300s, gate)` and persist it before the gate. **2026-08-26 after 09:00 site** (renewal #1 charged the evening of 08-25): resolve numeric `REN1` from the exact subscription/scheduled-cycle relationship and require its reverse link, never recency. In `admin-SLT-CPN-02-R1`, capture its exact item table as `SLT-CPN-02-05-renewal1-full-price.png`, run `wp eval '$o=wc_get_order((int)$argv[1]);echo count($o->get_fees())."|".implode(",",$o->get_coupon_codes())."|".$o->get_total();' "$REN1" --allow-root`, poll immutable `REN1_PRE` in ≤60-second calls through the 10-minute cutoff, reconcile every newer message, and close only that R1 session.
12. Store `REN2_PRE` only inside renewal #2's final-five-minute interval and repeat on 2026-08-27 with relationship-exact numeric `REN2` in `admin-SLT-CPN-02-R2`, using ≤60-second polling and complete-delta reconciliation. Append both renewal order/action/baseline/mail IDs to the registry and daily reports. Any live failure creates/updates the mandatory `qa/issues/` kanban card named `SLT-CPN-02-<concise-slug>` with task/stage/plan; coupon/product/parent/renewal/subscription/action IDs; user ID/login/email/role; exact URLs/sessions/gates; reproduction; expected/actual; UI/meta/order/Mailpit/screenshot proof; and the recurring-coupon counterexample. A misleading one-time note receives its own issue card. Close only the R2 session, independently review the D1/D3/D4 evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Classic cart and classic checkout both show Total **$5.00**, discount row **-$5.00**, no tax line.
2. Parent order total **$5.00**, status processing/completed, coupon `sltfix5first` on the order.
3. Metas: `_coupon_code=sltfix5first`, `_coupon_discount_type=one-time`, `_coupon_discount_amount=5.00`, `_coupon_discount_percent=0`, `_coupon_wc_discount_type=fixed_cart`, `_coupon_original_cycles=0`, `_coupon_remaining_cycles=0`, `_coupon_count_initial=no`, `_coupon_initial_cycle_pending` ABSENT.
4. Capture the current note. It must describe the one-time initial-order discount without claiming it applies per renewal. Any misleading wording creates/updates the mandatory `qa/issues/` kanban card while the monetary behavior is scored independently.
5. Renewal #1 (08-25): subtotal $10.00, total **$10.00**, **zero** fee items, **zero** coupon codes.
6. Renewal #2 (08-26): total **$10.00**, same shape.
7. No recurring-discount or cycle note ever added to the subscription.
8. Status stays `arraysubs-active`; `_next_payment_date` advances exactly 1 day per renewal, anchored on `_renewal_scheduled_date`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 0 | WordPress admin new-user notice (expected setup mail) | admin user creation | admin_email only | `New User Registration` | `mailpit-agent wait-new "$U0" 60 "New User Registration"`; prove no message to `slt2-cpnfirst@example.test` |
| 1 | new_subscription | parent paid | slt2-cpnfirst@ | `is active` | repeated `mailpit-agent wait-new "$M0" 60 "is active"` calls through the two-minute cutoff |
| 2 | admin_new_subscription | parent paid | admin_email | `New subscription #` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 3 | WC New order | parent paid | admin_email | `New order #<PARENT_ORDER>` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 4 | WC Completed order | paid virtual checkout | slt2-cpnfirst@ | `is on its way` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 5 | payment_successful | each renewal paid | slt2-cpnfirst@ | exact numeric `Payment received for subscription #$SUB_ID` | final-five-minute `REN1_PRE`/`REN2_PRE`, repeated ≤60-second waits, and complete deltas |
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
- [ ] Capture note verbatim; if the misleading wording is observed, its QA issue card exists under `qa/issues/`
- [ ] Renewal #1 exactly $10.00 with zero fees and zero coupons
- [ ] Renewal #2 exactly $10.00; no recurring-discount note ever added
- [ ] payment_successful per renewal; zero renewal_invoice mail
- [ ] Exact sessions closed and final D4 evidence reviewed to done with Review empty

## Isolation / teardown
- Watch assertion D3..D9: every `slt2-cpnfirst` renewal is $10.00 with no fee line - the direct counterexample to SLT-CPN-01. A discounted renewal here is a defect.
- Leaves `slt2-cpnfirst` + one active daily subscription; add the subscription to SLT-SETUP-99A's cancel list and the account to SLT-SETUP-99B's deletion list. Nothing global changed. Close `admin-SLT-CPN-02` and `customer-SLT-CPN-02` only.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
