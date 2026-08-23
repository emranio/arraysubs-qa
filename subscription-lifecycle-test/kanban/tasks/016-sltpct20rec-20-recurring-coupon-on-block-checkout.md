---
id: 16
title: SLT2PCT20REC 20% recurring coupon on block checkout - discounted first charge, discount persists to every renewal
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

> **SLT-CPN-01** · group `checkout` · scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove `SLT2PCT20REC` (percent 20, apply-to-subs yes, `recurring`, cycles 0 = unlimited) discounts the block-checkout first charge AND every unattended renewal, the renewal discount arriving as a negative FEE named `Recurring Discount: sltpct20rec` (`CouponTracking\Services\Hooks::applyRecurringCoupons` on `arraysubs_renewal_invoice_created`), not a coupon line.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered (this task CREATES `slt2-cpnrec`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-SETUP-04 (`SLT2PCT20REC`), SLT-PROD-01 (`SLT2 Daily Core` $10.00 day/1) done.
- Coupon expiry must be `2026-09-05`, not `2026-09-02` or `2026-09-04`: WooCommerce stores the date at site-local midnight, and `applyRecurringCoupons()` returns silently once `time() > date_expires`. Fix and record if wrong.
- CREATES `slt2-cpnrec` / `slt2-cpnrec@example.test`, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4. Fresh account is mandatory so this coupon has one unambiguous subscription; at `one_per_customer=false`, repeat checkout creates a duplicate rather than migrating.
- Check out **18:00-19:00 site (UTC+6)**: satisfies C02 and keeps the invoice leg (`due+k-6h` => 12:00-19:00 site) clear of SLT-SYN-04's D3 09:00-11:00 bracket.
- Sessions `admin-SLT-CPN-01` / `customer-SLT-CPN-01` exclusive to this task (C09).

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core, $10.00, day/1 |
| Account | slt2-cpnrec (created here) |
| Coupon | SLT2PCT20REC - percent 20, recurring, cycles 0 |
| Card | 4242 4242 4242 4242 |
| Amounts | first charge $8.00; every renewal $8.00 ($10.00 - $2.00 fee) |

## Steps
1. Record `SUBCOUNT_BEFORE=<exact current SLT2 subscription count>` and `U0=$(mailpit-agent latest-id)`, then in `admin-SLT-CPN-01`: `user-new.php` -> create `slt2-cpnrec`, role Customer, **Send User Notification** unticked; fill billing on `user-edit.php` and record the numeric user ID. Use `mailpit-agent wait-new "$U0" 60 "New User Registration"` and classify exactly one admin-only setup message; verify no account/password message was sent to `slt2-cpnrec@example.test`. Open the coupon, capture the **ArraySubs Subscription Settings** group + expiry as `SLT-CPN-01-01-coupon-settings.png`, and record coupon ID.
2. After the setup mail is fully classified, `M0=$(mailpit-agent latest-id)`; this is the checkout-mail baseline. Also record `ORDERCOUNT_BEFORE` from the HPOS `wp_wc_orders` `shop_order` rows and set `SUBMIT_AT_GMT` immediately before the payment click.
3. In `customer-SLT-CPN-01`, log in as `slt2-cpnrec`; open `/cart/`, confirm both browser and persistent carts EMPTY, and capture `SLT-CPN-01-01b-cart-empty-before.png`; add SLT2 Daily Core from its product page. The frozen `checkout.one_click_mode=subscription_items` must redirect this addition directly to the block `/checkout/`; require the order summary subtotal to be $10.00 and do not treat the healthy redirect as a missing cart assertion.
4. On the resulting `https://mirror-help.arrayhash.com/checkout/` page (page 8, block), expand **Add a coupon** in **Order summary**, enter `SLT2PCT20REC`, **Apply**; re-snapshot and capture the $8.00 totals as `SLT-CPN-01-02-block-totals-8.00.png` before entering any card data.
5. Select Stripe, enter the hosted test-card fields without capturing them, and click **Place order exactly once**. Poll for order-received in calls of at most 60 seconds through a two-minute cutoff. A browser wait timeout is not a failed payment: never click Place order again while the outcome is ambiguous. Instead inspect the checkout network request, reload once if needed, and query HPOS for this exact customer, `date_created_gmt >= SUBMIT_AT_GMT`, total `8.00`, and coupon before deciding whether the single submit failed; any matching order or successful/in-flight checkout request forbids a retry. Record numeric `PARENT_ORDER`, require `ORDERCOUNT_AFTER == ORDERCOUNT_BEFORE + 1`, and capture `SLT-CPN-01-03-order-received.png`. Read its exact linkage with `LINK_JSON=$(wp post meta get "$PARENT_ORDER" _subscription_ids --format=json --allow-root)` and resolve `SUB_ID` only through a strict one-element numeric `jq -e` guard. Cross-check exactly one reverse `_parent_order_id=$PARENT_ORDER`, the numeric user/product, and `SUBCOUNT_AFTER == SUBCOUNT_BEFORE + 1`; never use `WC_Order::get_meta('_subscription_ids')` or select by recency on this HPOS runtime.
6. `wp post meta list "$SUB_ID" --allow-root | rg '_coupon|_next_payment_date'`; copy the capture note verbatim.
7. Compute k with `php -r '$id=(int)$argv[1];$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("offset=%ds (%s)\n",$h%21600,gmdate("H:i:s",$h%21600));' "$SUB_ID"`; query the exact pending invoice/charge rows for numeric `SUB_ID`, and record their IDs plus `due+k−6h` and `due+k` gates.
8. Poll `mailpit-agent wait-new "$M0" 60 "is active"` in calls of at most 60 seconds through the two-minute cutoff, then inspect and classify the complete owner-filtered delta after `$M0`. In the same customer session open `/cart/`, require the post-checkout cart to be EMPTY, and capture `SLT-CPN-01-03b-cart-empty-after.png` before closing the current-day leg.
9. Before closing the D1 leg, append the user/coupon/parent-order/`SUB_ID`, exact pending invoice and charge action IDs/times, and the latest safe `REN1_PRE` capture deadline (`charge−5m`) to `slt2-catalog-registry` and the D01 watch report. Close both task-keyed sessions. Leave the card `in-progress` with that exact D2 gate; do not defer this fixture handoff until after the renewals.
10. Store `REN1_PRE=$(mailpit-agent latest-id)` only inside `[renewal #1 exact charge gate−300s, gate)` and persist it before the gate. **2026-08-26 after 09:00 site** (renewal #1 charged the evening of 08-25): resolve numeric `REN1` from the exact subscription/scheduled-cycle relationship and require its reverse link, never from customer/order recency. In `admin-SLT-CPN-01-R1`, open exact `REN1`, capture its item table as `SLT-CPN-01-04-renewal1-items.png`, dump fees with `wp eval '$o=wc_get_order((int)$argv[1]);foreach($o->get_fees() as $f){echo $f->get_name()."|".$f->get_total()."\n";}echo "total|".$o->get_total();' "$REN1" --allow-root`, poll `wait-new` on immutable `REN1_PRE` in ≤60-second calls through the 10-minute cutoff, reconcile every newer message, and close only that R1 session.
11. Store `REN2_PRE` only inside renewal #2's final-five-minute interval and repeat on 2026-08-27 with relationship-exact numeric `REN2` in `admin-SLT-CPN-01-R2`; capture `SLT-CPN-01-05-renewal2-items.png`, poll the immutable baseline in ≤60-second calls, and reconcile the complete delta. Append both renewal order/action/baseline/mail IDs to the registry and daily reports, close only the R2 session, independently review the full D1/D3/D4 evidence, move the card through `review` to `done`, and ensure Review returns to zero. Any live failure belongs only in `qa/issues/` kanban card named `SLT-CPN-01-<concise-slug>`, in the mandatory `qa/issues/` kanban card, with task/stage/plan; coupon/product/parent/renewal/subscription/action IDs; user ID/login/email/role; exact URLs/sessions/gates; reproduction; expected/actual; UI/meta/order/Mailpit/screenshot proof; and the one-time coupon as counterexample where applicable.

## Expected results
1. Checkout total exactly **$8.00**; no tax line.
2. Metas: `_coupon_code=sltpct20rec` (lowercased by `wc_strtolower`), `_coupon_discount_type=recurring`, `_coupon_original_cycles=0`, `_coupon_remaining_cycles=0`, `_coupon_discount_percent=20`, `_coupon_discount_amount=0`, `_coupon_wc_discount_type=percent`, `_coupon_count_initial=no`, `_applied_coupon_id` set, `_coupon_initial_cycle_pending` ABSENT.
3. Note: `Coupon "sltpct20rec" captured from checkout order. Duration: recurring (unlimited). Discount: 20% off.`
4. `_next_payment_date` = checkout +1 day, same clock time; status `arraysubs-active`.
5. Renewal #1 (08-25): subtotal $10.00, one fee **`Recurring Discount: sltpct20rec`** at **-2.00** (meta `_subscription_coupon=yes`), total **$8.00**, `_is_renewal_order=yes`.
6. Renewal #2 (08-26): identical, total **$8.00** - no decay.
7. `_coupon_remaining_cycles` still 0 after both (`decrementCouponCycles()` exits when original cycles <= 0); no exhaustion note.
8. `get_coupon_codes()` empty on both renewal orders - fee, not coupon.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 0 | WordPress admin new-user notice (expected setup mail) | admin user creation | admin_email only | `New User Registration` | `mailpit-agent wait-new "$U0" 60 "New User Registration"`; prove no message to `slt2-cpnrec@example.test` |
| 1 | new_subscription | parent paid | slt2-cpnrec@ | `is active` | repeated `mailpit-agent wait-new "$M0" 60 "is active"` calls through the two-minute cutoff |
| 2 | admin_new_subscription | parent paid | admin_email | `New subscription #` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 3 | WC New order | parent paid | admin_email | `New order #<PARENT_ORDER>` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 4 | WC Completed order | paid virtual checkout | slt2-cpnrec@ | `is on its way` | Complete owner-filtered delta after `$M0`; save/show the exact matching id |
| 5 | payment_successful | each renewal paid | slt2-cpnrec@ | exact numeric `Payment received for subscription #$SUB_ID` | final-five-minute `REN1_PRE`/`REN2_PRE`, repeated ≤60-second waits, and complete deltas |
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
- Watch assertion D3..D9: every `slt2-cpnrec` renewal totals $8.00 with the named fee; a $10.00 renewal is a defect.
- Leaves `slt2-cpnrec` + one active daily subscription; add the subscription to SLT-SETUP-99A's cancel list and the account to SLT-SETUP-99B's deletion list. Only global change is the coupon expiry fix. Close `admin-SLT-CPN-01` and `customer-SLT-CPN-01` only.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
